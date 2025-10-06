<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Gestisce webhook da Stripe
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verifica la firma del webhook
        if (!$this->stripeService->verifyWebhook($payload, $signature)) {
            Log::error('Stripe webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $event = json_decode($payload, false);
            
            Log::info('Stripe webhook received: ' . $event->type, [
                'event_id' => $event->id,
                'type' => $event->type
            ]);

            // Gestisce l'evento
            $this->stripeService->handleWebhookEvent($event);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Endpoint per test webhook (solo in sviluppo)
     */
    public function test(Request $request)
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $eventType = $request->input('type', 'identity.verification_session.verified');
        
        // Simula un evento Stripe per test
        $mockEvent = (object) [
            'id' => 'evt_test_' . uniqid(),
            'type' => $eventType,
            'data' => (object) [
                'object' => (object) [
                    'id' => 'vs_test_' . uniqid(),
                    'metadata' => (object) [
                        'user_id' => $request->input('user_id', '1'),
                        'user_email' => $request->input('user_email', 'test@example.com')
                    ],
                    'status' => 'verified'
                ]
            ]
        ];

        Log::info('Testing Stripe webhook with mock event: ' . $eventType);

        $this->stripeService->handleWebhookEvent($mockEvent);

        return response()->json([
            'message' => 'Test webhook processed successfully',
            'event_type' => $eventType,
            'mock_event' => $mockEvent
        ]);
    }
}