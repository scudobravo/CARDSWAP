<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class SendShipmentReminders extends Command
{
    protected $signature = 'orders:send-shipment-reminders {--hours=24}';

    protected $description = 'Invia promemoria di spedizione ai venditori per ordini confermati non ancora spediti';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        $orders = Order::query()
            ->where('status', 'confirmed')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_shipment_reminder_at')
                  ->orWhere('last_shipment_reminder_at', '<', $threshold);
            })
            ->with(['buyer', 'seller'])
            ->limit(200)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            // invia promemoria al venditore principale
            $emailData = [
                'order' => [
                    'order_number' => $order->order_number,
                    'total_amount' => (float) $order->total_amount,
                ],
                'seller' => [
                    'name' => (string) optional($order->seller)->name,
                    'email' => (string) optional($order->seller)->email,
                ],
                'buyer' => [
                    'name' => (string) optional($order->buyer)->name,
                ],
                'dashboard_url' => config('app.url') . '/dashboard/orders/' . $order->id,
            ];

            if (!empty($order->seller) && !empty($order->seller->email)) {
                Mail::send('emails.shipment-reminder', $emailData, function ($message) use ($order) {
                    $message->to($order->seller->email, (string) $order->seller->name)
                            ->subject('Promemoria spedizione ordine #' . $order->order_number);
                });
                $order->update(['last_shipment_reminder_at' => now()]);
                $count++;
            }
        }

        $this->info("Promemoria spedizione inviati: {$count}");
        return self::SUCCESS;
    }
}


