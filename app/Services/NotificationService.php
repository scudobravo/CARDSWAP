<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * CardSwap Shipping V1 – FASE D3.
 * Servizio notifiche: crea record in-app e invia email (triggerate solo da eventi backend).
 * Nessuna logica di business: i messaggi sono definiti dai listener/eventi.
 */
class NotificationService
{
    /** Finestra (minuti) entro cui considerare una notifica duplicata per stesso user + type + order_id. */
    private const DEDUP_WINDOW_MINUTES = 15;

    /** Invia notifica a un utente (DB + email se abilitata). Evita doppie in-app: stessa user+type+order_id in finestra → nessun nuovo record, nessuna seconda email. */
    public function send(User $user, string $type, array $data): UserNotification
    {
        $orderId = isset($data['data']['order_id']) ? (int) $data['data']['order_id'] : null;
        $existing = $orderId !== null ? $this->findDuplicate($user->id, $type, $orderId) : null;
        if ($existing) {
            Log::info('NotificationService: notifica duplicata ignorata (in-app)', [
                'user_id' => $user->id,
                'type' => $type,
                'order_id' => $orderId,
            ]);
            return $existing;
        }

        $title = $data['title'] ?? $type;
        $message = $data['message'] ?? $data['body'] ?? '';
        $actionUrl = $data['action_url'] ?? null;
        $actionText = $data['action_text'] ?? null;
        $sendEmail = $data['send_email'] ?? true;

        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data['data'] ?? null,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
        ]);

        if ($sendEmail && $user->email) {
            $this->sendEmail($user, $title, $message, $actionUrl, $data);
        }

        return $notification;
    }

    /** Cerca una notifica uguale (stesso user, type e order_id) creata nella finestra di dedup. */
    private function findDuplicate(int $userId, string $type, ?int $orderId): ?UserNotification
    {
        $q = UserNotification::where('user_id', $userId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES));
        if ($orderId !== null) {
            $q->where('data->order_id', $orderId);
        }
        return $q->first();
    }

    protected function sendEmail(User $user, string $title, string $message, ?string $actionUrl, array $data): void
    {
        $driver = config('mail.default');
        Log::info('NotificationService: invio email', [
            'to' => $user->email,
            'subject' => $title . ' - CardSwap',
            'driver' => $driver,
        ]);

        if ($driver === 'log' || $driver === 'array') {
            Log::warning('NotificationService: le email non vengono inviate realmente. Imposta MAIL_MAILER=smtp (e SMTP) in .env per inviare.', [
                'driver' => $driver,
            ]);
        }

        try {
            $messageBody = is_string($message) ? $message : (string) $message;
            Mail::send('emails.shipping-notification', [
                'user' => $user,
                'title' => $title,
                'message_body' => $messageBody,
                'action_url' => $actionUrl,
                'order_number' => $data['order_number'] ?? null,
            ], function ($m) use ($user, $title) {
                $m->to($user->email, $user->name)
                    ->subject($title . ' - CardSwap');
            });
        } catch (\Throwable $e) {
            Log::warning('NotificationService: invio email fallito', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $data['type'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
