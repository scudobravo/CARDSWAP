<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CardSwap Shipping V1 – FASE D3.
 * Invia notifiche di reminder al venditore: 1 giorno prima scadenza.
 * - Untracked: reminder al 5° giorno (1 giorno prima dei 5 giorni per segnare come spedito).
 * - Tracked: reminder al 6° giorno (1 giorno prima dei 7 giorni per inserire tracking).
 */
class SendShipmentReminderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $this->sendUntrackedReminders($notificationService);
        $this->sendTrackedReminders($notificationService);
    }

    /** Reminder spedizione NON tracciata: 1 giorno prima scadenza (es. giorno 4 se scadenza giorno 5). */
    protected function sendUntrackedReminders(NotificationService $notificationService): void
    {
        $daysToDeadline = (int) config('shipping.untracked_mark_shipped_days', 5);
        $reminderDay = $daysToDeadline - 1; // 1 giorno prima

        $from = now()->subDays($reminderDay)->startOfDay();
        $to = now()->subDays($reminderDay)->endOfDay();

        $orders = Order::whereIn('status', ['paid_funds_held', 'paid', 'confirmed'])
            ->whereNotNull('paid_at')
            ->whereNull('shipped_at')
            ->whereNull('tracking_number')
            ->whereBetween('paid_at', [$from, $to])
            ->with(['seller', 'buyer'])
            ->limit(100)
            ->get();

        foreach ($orders as $order) {
            $order->refresh();
            if (in_array($order->status, ['cancelled', 'refunded', 'dispute_hold', 'completed'], true) || $order->has_dispute) {
                Log::info('D5-JOB: SendShipmentReminder skip ordine (stato non valido)', ['order_id' => $order->id, 'status' => $order->status]);
                continue;
            }
            $seller = $order->seller;
            if (!$seller) {
                continue;
            }
            $notificationService->send($seller, 'seller_reminder_untracked', [
                'title' => 'Promemoria: segna come spedito',
                'message' => "Ricordati di segnare l'ordine come spedito.\nIn caso contrario verrà annullato automaticamente.",
                'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
                'order_number' => $order->order_number,
            ]);
            Log::info('FASE D3: reminder untracked inviato', ['order_id' => $order->id]);
        }
    }

    /** Reminder tracking TRACCIATA: 1 giorno prima scadenza (es. giorno 6 se scadenza 7 giorni). */
    protected function sendTrackedReminders(NotificationService $notificationService): void
    {
        $daysToDeadline = (int) config('shipping.tracking_required_within_days', 7);
        $reminderDay = $daysToDeadline - 1;

        $from = now()->subDays($reminderDay)->startOfDay();
        $to = now()->subDays($reminderDay)->endOfDay();

        $orders = Order::whereIn('status', ['paid_funds_held', 'paid', 'confirmed'])
            ->whereNotNull('paid_at')
            ->whereNull('tracking_number')
            ->whereBetween('paid_at', [$from, $to])
            ->with(['seller', 'buyer'])
            ->limit(100)
            ->get();

        foreach ($orders as $order) {
            $order->refresh();
            if (in_array($order->status, ['cancelled', 'refunded', 'dispute_hold', 'completed'], true) || $order->has_dispute) {
                Log::info('D5-JOB: SendShipmentReminder skip ordine (stato non valido)', ['order_id' => $order->id, 'status' => $order->status]);
                continue;
            }
            $seller = $order->seller;
            if (!$seller) {
                continue;
            }
            $notificationService->send($seller, 'seller_reminder_tracked', [
                'title' => 'Promemoria: inserisci tracking',
                'message' => "Inserisci il tracking entro 24 ore per evitare l'annullamento dell'ordine.",
                'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
                'order_number' => $order->order_number,
            ]);
            Log::info('FASE D3: reminder tracked inviato', ['order_id' => $order->id]);
        }
    }
}
