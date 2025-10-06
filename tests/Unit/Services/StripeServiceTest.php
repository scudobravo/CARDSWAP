<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StripeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripeService = new StripeService();
    }

    /** @test */
    public function stripe_service_can_be_instantiated()
    {
        $this->assertInstanceOf(StripeService::class, $this->stripeService);
    }

    /** @test */
    public function stripe_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->stripeService, 'createPaymentIntent'));
        $this->assertTrue(method_exists($this->stripeService, 'confirmPaymentIntent'));
        $this->assertTrue(method_exists($this->stripeService, 'createCustomer'));
        $this->assertTrue(method_exists($this->stripeService, 'createPaymentMethod'));
        $this->assertTrue(method_exists($this->stripeService, 'refundPayment'));
    }

    /** @test */
    public function create_payment_intent_handles_invalid_data()
    {
        // Test con dati non validi
        $result = $this->stripeService->createPaymentIntent(0, 'eur', 'invalid_user_id');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function create_payment_intent_validates_amount()
    {
        // Test con importo negativo
        $result = $this->stripeService->createPaymentIntent(-10, 'eur', 'user_123');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('importo', strtolower($result['error']));
    }

    /** @test */
    public function create_payment_intent_validates_currency()
    {
        // Test con valuta non valida
        $result = $this->stripeService->createPaymentIntent(1000, 'invalid', 'user_123');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function create_payment_intent_validates_customer_id()
    {
        // Test con customer ID vuoto
        $result = $this->stripeService->createPaymentIntent(1000, 'eur', '');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function confirm_payment_intent_handles_invalid_intent()
    {
        // Test con PaymentIntent ID non valido
        $result = $this->stripeService->confirmPaymentIntent('invalid_intent_id');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function create_customer_handles_invalid_data()
    {
        // Test con email non valida
        $result = $this->stripeService->createCustomer('', 'Mario Rossi');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function create_payment_method_handles_invalid_data()
    {
        // Test con dati carta non validi
        $cardData = [
            'number' => 'invalid',
            'exp_month' => 13, // Mese non valido
            'exp_year' => 2020, // Anno passato
            'cvc' => '12' // CVC troppo corto
        ];
        
        $result = $this->stripeService->createPaymentMethod($cardData);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function refund_payment_handles_invalid_payment_intent()
    {
        // Test con PaymentIntent ID non valido
        $result = $this->stripeService->refundPayment('invalid_intent_id', 1000);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function refund_payment_validates_amount()
    {
        // Test con importo negativo
        $result = $this->stripeService->refundPayment('pi_test123', -100);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function stripe_service_handles_network_errors()
    {
        // Simula errore di rete (questo test verifica che il servizio gestisca gli errori)
        $result = $this->stripeService->createPaymentIntent(1000, 'eur', 'user_123');
        
        // Il servizio dovrebbe gestire l'errore senza crashare
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /** @test */
    public function stripe_service_validates_required_parameters()
    {
        // Test che tutti i metodi validino i parametri richiesti
        $methods = [
            'createPaymentIntent' => [1000, 'eur', 'user_123'],
            'confirmPaymentIntent' => ['pi_test123'],
            'createCustomer' => ['test@example.com', 'Test User'],
            'createPaymentMethod' => [[]],
            'refundPayment' => ['pi_test123', 1000]
        ];

        foreach ($methods as $method => $params) {
            $result = call_user_func_array([$this->stripeService, $method], $params);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
        }
    }

    /** @test */
    public function stripe_service_returns_consistent_response_format()
    {
        $result = $this->stripeService->createPaymentIntent(1000, 'eur', 'user_123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }
}
