<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\CardListing;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $seller;
    protected $cardModel;
    protected $listing1;
    protected $listing2;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crea utente acquirente
        $this->user = User::factory()->create([
            'role' => 'buyer',
            'email_verified_at' => now()
        ]);

        // Crea utente venditore
        $this->seller = User::factory()->create([
            'role' => 'seller',
            'kyc_status' => 'approved',
            'stripe_identity_verified' => true,
            'stripe_account_id' => 'acct_test123'
        ]);

        // Crea dati di test
        $category = Category::factory()->create(['name' => 'Calcio']);
        $cardSet = CardSet::factory()->create(['name' => 'Panini 2024']);
        $player = Player::factory()->create(['name' => 'Lionel Messi']);
        $team = Team::factory()->create(['name' => 'Inter Miami']);
        $league = League::factory()->create(['name' => 'MLS']);

        $this->cardModel = CardModel::factory()->create([
            'category_id' => $category->id,
            'card_set_id' => $cardSet->id,
            'player_id' => $player->id,
            'team_id' => $team->id,
            'league_id' => $league->id,
        ]);

        // Crea inserzioni di test
        $this->listing1 = CardListing::factory()->create([
            'card_model_id' => $this->cardModel->id,
            'seller_id' => $this->seller->id,
            'price' => 10.00,
            'quantity' => 5,
            'status' => 'active'
        ]);

        $this->listing2 = CardListing::factory()->create([
            'card_model_id' => $this->cardModel->id,
            'seller_id' => $this->seller->id,
            'price' => 15.00,
            'quantity' => 3,
            'status' => 'active'
        ]);

        // Crea indirizzo di test
        $this->address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function user_can_create_order_with_valid_data()
    {
        $cartData = [
            $this->seller->id => [
                    [
                        'id' => $this->listing1->id,
                        'unit_price' => 10.00,
                        'quantity' => 2,
                        'condition' => 'Mint'
                    ],
                    [
                        'id' => $this->listing2->id,
                        'unit_price' => 15.00,
                        'quantity' => 1,
                        'condition' => 'Near Mint'
                    ]
            ]
        ];

        $shippingMethods = [
            $this->seller->id => 'standard'
        ];

        $addressData = [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'address_line_1' => 'Via Roma 123',
            'city' => 'Milano',
            'country' => 'IT',
            'postal_code' => '20100'
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => $shippingMethods,
            'address' => $addressData,
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Ordine creato con successo'
                ]);

        // Verifica che l'ordine sia stato creato
        $this->assertDatabaseHas('orders', [
            'buyer_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'status' => 'paid'
        ]);

        // Verifica che gli articoli dell'ordine siano stati creati
        $order = Order::where('buyer_id', $this->user->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'card_listing_id' => $this->listing1->id,
            'quantity' => 2,
            'unit_price' => 10.00
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'card_listing_id' => $this->listing2->id,
            'quantity' => 1,
            'unit_price' => 15.00
        ]);

        // Verifica che le quantità siano state aggiornate
        $this->assertDatabaseHas('card_listings', [
            'id' => $this->listing1->id,
            'quantity' => 3 // 5 - 2
        ]);
        $this->assertDatabaseHas('card_listings', [
            'id' => $this->listing2->id,
            'quantity' => 2 // 3 - 1
        ]);
    }

    /** @test */
    public function order_creation_fails_with_invalid_cart_data()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => 99999, // Inserzione inesistente
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrello non valido'
                ]);
    }

    /** @test */
    public function order_creation_fails_with_insufficient_quantity()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 10, // Quantità maggiore di quella disponibile
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrello non valido'
                ]);
    }

    /** @test */
    public function order_creation_fails_with_missing_shipping_method()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [], // Metodi di spedizione mancanti
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Metodi di spedizione non validi'
                ]);
    }

    /** @test */
    public function order_creation_fails_with_invalid_address()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                // Indirizzo incompleto
                'first_name' => 'Mario',
                'last_name' => 'Rossi'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Dati di validazione non validi'
                ]);
    }

    /** @test */
    public function order_creation_calculates_correct_totals()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 2,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(200);

        $order = Order::where('buyer_id', $this->user->id)->first();
        
        // Verifica i calcoli
        // Subtotale: 10.00 * 2 = 20.00
        // Spedizione standard: 5.00
        // IVA (22%): 20.00 * 0.22 = 4.40
        // Totale: 20.00 + 5.00 + 4.40 = 29.40

        $this->assertEquals(20.00, $order->subtotal);
        $this->assertEquals(5.00, $order->shipping_cost);
        $this->assertEquals(4.40, $order->tax_amount);
        $this->assertEquals(29.40, $order->total_amount);
    }

    /** @test */
    public function order_creation_handles_express_shipping()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'express'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(200);

        $order = Order::where('buyer_id', $this->user->id)->first();
        
        // Verifica spedizione express
        $this->assertEquals(16.00, $order->shipping_cost);
    }

    /** @test */
    public function order_creation_creates_address_if_not_exists()
    {
        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $addressData = [
            'first_name' => 'Luigi',
            'last_name' => 'Bianchi',
            'address_line_1' => 'Via Milano 456',
            'city' => 'Roma',
            'country' => 'IT',
            'postal_code' => '00100'
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => $addressData,
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(200);

        // Verifica che l'indirizzo sia stato creato
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'first_name' => 'Luigi',
            'last_name' => 'Bianchi',
            'address_line_1' => 'Via Milano 456'
        ]);
    }

    /** @test */
    public function order_creation_handles_inactive_listing()
    {
        // Disattiva l'inserzione
        $this->listing1->update(['status' => 'inactive']);

        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00,
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrello non valido'
                ]);
    }

    /** @test */
    public function order_creation_handles_price_change()
    {
        // Cambia il prezzo dell'inserzione
        $this->listing1->update(['price' => 15.00]);

        $cartData = [
            $this->seller->id => [
                [
                    'id' => $this->listing1->id,
                    'unit_price' => 10.00, // Prezzo vecchio nel carrello
                    'quantity' => 1,
                    'condition' => 'Mint'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => $cartData,
            'shipping_methods' => [$this->seller->id => 'standard'],
            'address' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrello non valido'
                ]);
    }

    /** @test */
    public function order_creation_requires_authentication()
    {
        // Rimuovi autenticazione
        $this->withoutMiddleware();

        $response = $this->postJson('/api/checkout/create-order', [
            'cart_data' => [],
            'shipping_methods' => [],
            'address' => [],
            'payment_method' => 'credit-card'
        ]);

        $response->assertStatus(401);
    }
}
