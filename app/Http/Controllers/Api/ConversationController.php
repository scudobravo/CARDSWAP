<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderConversation;
use App\Models\OrderMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class ConversationController extends Controller
{
    /**
     * Lista conversazioni dell'utente autenticato
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = OrderConversation::query()
            ->with(['order', 'buyer', 'seller'])
            ->orderByDesc('last_message_at');

        if ($user->role === 'buyer') {
            $query->where('buyer_id', $user->id);
        } elseif ($user->role === 'seller') {
            $query->where('seller_id', $user->id);
        } else {
            // admin: può vedere tutto, opzionale filtri
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        $perPage = $request->integer('per_page', 15);
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)
        ]);
    }

    /**
     * Crea o trova conversazione tra buyer e seller per un ordine
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'seller_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $order = Order::findOrFail($request->order_id);

        // L'utente deve essere il buyer dell'ordine o il venditore indicato
        if (!in_array($user->role, ['buyer', 'seller'])) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }

        if ($user->role === 'buyer' && $order->buyer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }

        if ($user->role === 'seller' && !$order->getSellers()->contains('id', $user->id)) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }

        $sellerId = $request->integer('seller_id');

        $conversation = OrderConversation::firstOrCreate(
            [
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'seller_id' => $sellerId,
            ],
            [
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'data' => $conversation]);
    }

    /**
     * Lista messaggi di una conversazione
     */
    public function messages(OrderConversation $conversation, Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && !in_array($user->id, [$conversation->buyer_id, $conversation->seller_id])) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }

        $messages = $conversation->messages()
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc')
            ->paginate($request->integer('per_page', 20));

        // Mark as read for viewer
        $this->markAsRead($conversation, $user->id);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /**
     * Invia un messaggio nella conversazione
     */
    public function sendMessage(OrderConversation $conversation, Request $request): JsonResponse
    {
        // Rate limiting: max 10 messaggi per conversazione ogni 5 minuti
        $rateLimitKey = "conversation_messages:{$conversation->id}:" . now()->format('Y-m-d-H-i');
        $messageCount = Cache::get($rateLimitKey, 0);
        
        if ($messageCount >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Troppi messaggi inviati. Riprova tra 5 minuti.',
            ], 429);
        }

        // Rate limiting: max 5 messaggi per utente ogni minuto (globale)
        $userRateLimitKey = "user_messages:" . Auth::id() . ":" . now()->format('Y-m-d-H-i');
        $userMessageCount = Cache::get($userRateLimitKey, 0);
        
        if ($userMessageCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Troppi messaggi inviati. Riprova tra 1 minuto.',
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        if ($user->role !== 'admin' && !in_array($user->id, [$conversation->buyer_id, $conversation->seller_id])) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }

        if ($conversation->status !== 'open') {
            return response()->json(['success' => false, 'message' => 'Conversazione chiusa'], 400);
        }

        $message = OrderMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->get('body'),
        ]);

        // Incrementa contatori rate limiting
        Cache::put($rateLimitKey, $messageCount + 1, 300); // 5 minuti
        Cache::put($userRateLimitKey, $userMessageCount + 1, 60); // 1 minuto

        // Aggiorna stato conversazione e conteggi unread
        $conversation->last_sender_id = $user->id;
        $conversation->last_message_at = now();
        if ($user->id === $conversation->buyer_id) {
            $conversation->unread_count_seller += 1;
        } else {
            $conversation->unread_count_buyer += 1;
        }
        $conversation->save();

        // Email notifica con throttling semplice (>=15 minuti tra email)
        $this->notifyByEmailIfNeeded($conversation, $message, 15);

        return response()->json(['success' => true, 'data' => $message]);
    }

    /**
     * Marca conversazione come letta per il chiamante
     */
    public function markRead(OrderConversation $conversation): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && !in_array($user->id, [$conversation->buyer_id, $conversation->seller_id])) {
            return response()->json(['success' => false, 'message' => 'Non autorizzato'], 403);
        }
        $this->markAsRead($conversation, $user->id);
        return response()->json(['success' => true]);
    }

    private function markAsRead(OrderConversation $conversation, int $viewerId): void
    {
        if ($viewerId === $conversation->buyer_id && $conversation->unread_count_buyer > 0) {
            $conversation->unread_count_buyer = 0;
        }
        if ($viewerId === $conversation->seller_id && $conversation->unread_count_seller > 0) {
            $conversation->unread_count_seller = 0;
        }
        $conversation->save();

        // Aggiorna flag di lettura sugli ultimi messaggi (non realtime, best-effort)
        $isBuyer = $viewerId === $conversation->buyer_id;
        OrderMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $viewerId)
            ->when($isBuyer, fn($q) => $q->where('is_read_by_buyer', false), fn($q) => $q->where('is_read_by_seller', false))
            ->limit(200)
            ->update($isBuyer ? ['is_read_by_buyer' => true] : ['is_read_by_seller' => true]);
    }

    private function notifyByEmailIfNeeded(OrderConversation $conversation, OrderMessage $message, int $cooldownMinutes = 15): void
    {
        $now = now();
        $shouldSend = !$conversation->last_email_notification_at || $conversation->last_email_notification_at->lt($now->copy()->subMinutes($cooldownMinutes));
        if (!$shouldSend) return;

        $recipient = null;
        $recipientName = null;

        if ($message->sender_id === $conversation->buyer_id) {
            $recipient = optional($conversation->seller)->email;
            $recipientName = optional($conversation->seller)->name;
        } else {
            $recipient = optional($conversation->buyer)->email;
            $recipientName = optional($conversation->buyer)->name;
        }

        if (!$recipient) return;

        $emailData = [
            'order_number' => optional($conversation->order)->order_number,
            'sender_name' => optional($message->sender)->name,
            'message_preview' => mb_strimwidth($message->body, 0, 120, '...'),
            'conversation_url' => config('app.url') . '/dashboard/messages/' . $conversation->id,
        ];

        Mail::send('emails.new-message', $emailData, function ($m) use ($recipient, $recipientName, $conversation) {
            $m->to($recipient, (string) $recipientName)
              ->subject('Nuovo messaggio sull\'ordine #' . optional($conversation->order)->order_number);
        });

        $conversation->last_email_notification_at = $now;
        $conversation->save();
    }
}


