<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CardListing;
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

        \Log::info('Conversations API called - START', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
        ]);

        // Prima verifica quante conversazioni ci sono totali nel database
        $totalInDb = OrderConversation::count();
        $totalForUserAsBuyer = OrderConversation::where('buyer_id', $user->id)->count();
        $totalForUserAsSeller = OrderConversation::where('seller_id', $user->id)->count();
        $totalForUser = OrderConversation::where(function($q) use ($user) {
            $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        })->count();

        \Log::info('Conversations counts', [
            'total_in_db' => $totalInDb,
            'total_for_user_as_buyer' => $totalForUserAsBuyer,
            'total_for_user_as_seller' => $totalForUserAsSeller,
            'total_for_user' => $totalForUser,
        ]);

        $query = OrderConversation::query()
            ->with([
                'order', 
                'listing.cardModel.player',
                'listing.cardModel.cardSet',
                'buyer', 
                'seller'
            ])
            ->orderByDesc('last_message_at');

        // Filtra le conversazioni in base al ruolo dell'utente
        if ($user->role === 'buyer') {
            $query->where('buyer_id', $user->id);
            \Log::info('Applied buyer filter', ['buyer_id' => $user->id]);
        } elseif ($user->role === 'seller') {
            $query->where('seller_id', $user->id);
            \Log::info('Applied seller filter', ['seller_id' => $user->id]);
        } elseif ($user->role === 'admin') {
            // admin: può vedere tutto, opzionale filtri
            \Log::info('Admin user - showing all conversations');
        } else {
            // Per altri ruoli o utenti senza ruolo, mostra le conversazioni dove l'utente è buyer o seller
            $query->where(function($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
            \Log::info('Applied general filter', ['user_id' => $user->id]);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        if ($request->filled('listing_id')) {
            $query->where('listing_id', $request->integer('listing_id'));
        }

        // Log della query SQL prima dell'esecuzione
        \Log::info('Query SQL', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

        try {
            $perPage = $request->integer('per_page', 15);
            $conversations = $query->paginate($perPage);
            
            \Log::info('Conversations API called - SUCCESS', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'total_conversations' => $conversations->total(),
                'conversations_count' => $conversations->count(),
                'conversation_ids' => $conversations->pluck('id')->toArray(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Conversations API error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
        
        return response()->json([
            'success' => true,
            'data' => $conversations
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
     * Crea o trova conversazione tra buyer e seller per un listing/prodotto
     */
    public function startForListing(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer|exists:card_listings,id',
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
        $listing = CardListing::findOrFail($request->listing_id);
        $sellerId = $request->integer('seller_id');

        // Verifica che il venditore sia il proprietario del listing
        if ($listing->seller_id !== $sellerId) {
            return response()->json(['success' => false, 'message' => 'Venditore non valido per questo listing'], 403);
        }

        // Se l'utente è il venditore stesso, cerca conversazioni esistenti
        if ($user->id === $sellerId) {
            // Il venditore può solo vedere conversazioni esistenti, non crearne di nuove
            $conversation = OrderConversation::where('listing_id', $listing->id)
                ->where('seller_id', $sellerId)
                ->orderByDesc('last_message_at')
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna conversazione trovata per questo prodotto. I compratori possono iniziare una conversazione cliccando su "Chat".'
                ], 404);
            }

            // Carica le relazioni
            $conversation->load([
                'listing.cardModel.player',
                'listing.cardModel.cardSet',
                'buyer', 
                'seller'
            ]);

            return response()->json(['success' => true, 'data' => $conversation]);
        }

        // Se l'utente è un buyer, può creare o trovare una conversazione
        if ($user->role !== 'buyer') {
            return response()->json(['success' => false, 'message' => 'Solo i compratori possono avviare una nuova conversazione'], 403);
        }

        // Crea o trova la conversazione
        $conversation = OrderConversation::firstOrCreate(
            [
                'listing_id' => $listing->id,
                'buyer_id' => $user->id,
                'seller_id' => $sellerId,
            ],
            [
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );

        // Carica le relazioni
        $conversation->load([
            'listing.cardModel.player',
            'listing.cardModel.cardSet',
            'buyer', 
            'seller'
        ]);

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

        $totalMessages = $conversation->messages()->count();
        $visibleMessages = $conversation->messages()->where('is_hidden', false)->count();
        
        \Log::info('Loading messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'total_messages' => $totalMessages,
            'visible_messages' => $visibleMessages,
        ]);

        $messages = $conversation->messages()
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc')
            ->paginate($request->integer('per_page', 20));

        \Log::info('Messages loaded', [
            'conversation_id' => $conversation->id,
            'messages_count' => $messages->count(),
            'sender_ids' => $messages->pluck('sender_id')->toArray(),
        ]);

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

        \Log::info('Sending message', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'buyer_id' => $conversation->buyer_id,
            'seller_id' => $conversation->seller_id,
            'body_length' => strlen($request->get('body')),
        ]);

        $message = OrderMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->get('body'),
        ]);

        \Log::info('Message created', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'is_hidden' => $message->is_hidden,
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
            'listing_title' => optional($conversation->listing)->cardModel?->player?->name ?? 'Prodotto',
            'sender_name' => optional($message->sender)->name,
            'message_preview' => mb_strimwidth($message->body, 0, 120, '...'),
            'conversation_url' => config('app.url') . '/chat',
        ];

        $subject = $conversation->order 
            ? 'Nuovo messaggio sull\'ordine #' . $conversation->order->order_number
            : 'Nuovo messaggio sul prodotto: ' . ($conversation->listing->cardModel?->player?->name ?? 'Prodotto');

        Mail::send('emails.new-message', $emailData, function ($m) use ($recipient, $recipientName, $subject) {
            $m->to($recipient, (string) $recipientName)
              ->subject($subject);
        });

        $conversation->last_email_notification_at = $now;
        $conversation->save();
    }
}


