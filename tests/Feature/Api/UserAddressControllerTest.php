<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class UserAddressControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'buyer',
            'email_verified_at' => now()
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function user_can_view_their_addresses()
    {
        $address1 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'Casa',
            'is_default' => true
        ]);

        $address2 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'Ufficio',
            'is_default' => false
        ]);

        $response = $this->getJson('/api/user/addresses');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Indirizzi recuperati con successo'
                ])
                ->assertJsonCount(2, 'data');

        // Verifica che gli indirizzi siano ordinati correttamente (default first)
        $data = $response->json('data');
        $this->assertEquals($address1->id, $data[0]['id']);
        $this->assertEquals($address2->id, $data[1]['id']);
    }

    /** @test */
    public function user_can_create_new_address()
    {
        $addressData = [
            'label' => 'Casa',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'address_line_1' => 'Via Roma 123',
            'city' => 'Milano',
            'country' => 'IT',
            'postal_code' => '20100',
            'phone' => '+39 123 456 7890',
            'is_default' => true
        ];

        $response = $this->postJson('/api/user/addresses', $addressData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Indirizzo creato con successo'
                ]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'label' => 'Casa',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'address_line_1' => 'Via Roma 123',
            'city' => 'Milano',
            'country' => 'IT',
            'postal_code' => '20100',
            'is_default' => true
        ]);
    }

    /** @test */
    public function user_can_create_address_with_optional_fields()
    {
        $addressData = [
            'label' => 'Ufficio',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'company' => 'Azienda SRL',
            'address_line_1' => 'Via Milano 456',
            'address_line_2' => 'Interno 2',
            'city' => 'Roma',
            'state_province' => 'Lazio',
            'country' => 'IT',
            'postal_code' => '00100',
            'is_billing' => true,
            'is_shipping' => false
        ];

        $response = $this->postJson('/api/user/addresses', $addressData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'company' => 'Azienda SRL',
            'address_line_2' => 'Interno 2',
            'state_province' => 'Lazio',
            'is_billing' => true,
            'is_shipping' => false
        ]);
    }

    /** @test */
    public function creating_default_address_removes_default_from_others()
    {
        // Crea un indirizzo predefinito esistente
        $existingAddress = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);

        $addressData = [
            'label' => 'Nuovo Default',
            'first_name' => 'Luigi',
            'last_name' => 'Bianchi',
            'address_line_1' => 'Via Napoli 789',
            'city' => 'Napoli',
            'country' => 'IT',
            'postal_code' => '80100',
            'is_default' => true
        ];

        $response = $this->postJson('/api/user/addresses', $addressData);

        $response->assertStatus(201);

        // Verifica che il nuovo indirizzo sia predefinito
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'label' => 'Nuovo Default',
            'is_default' => true
        ]);

        // Verifica che il vecchio indirizzo non sia più predefinito
        $this->assertDatabaseHas('user_addresses', [
            'id' => $existingAddress->id,
            'is_default' => false
        ]);
    }

    /** @test */
    public function user_can_update_existing_address()
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'label' => 'Casa',
            'city' => 'Milano'
        ]);

        $updateData = [
            'label' => 'Casa Aggiornata',
            'city' => 'Roma',
            'postal_code' => '00100'
        ];

        $response = $this->putJson("/api/user/addresses/{$address->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Indirizzo aggiornato con successo'
                ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Casa Aggiornata',
            'city' => 'Roma',
            'postal_code' => '00100'
        ]);
    }

    /** @test */
    public function user_can_set_address_as_default()
    {
        $address1 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);

        $address2 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false
        ]);

        $response = $this->postJson("/api/user/addresses/{$address2->id}/set-default");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Indirizzo impostato come predefinito'
                ]);

        // Verifica che il nuovo indirizzo sia predefinito
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address2->id,
            'is_default' => true
        ]);

        // Verifica che il vecchio indirizzo non sia più predefinito
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address1->id,
            'is_default' => false
        ]);
    }

    /** @test */
    public function user_can_delete_address()
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Indirizzo eliminato con successo'
                ]);

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id
        ]);
    }

    /** @test */
    public function user_cannot_access_other_users_addresses()
    {
        $otherUser = User::factory()->create();
        $otherAddress = UserAddress::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // Tentativo di visualizzare indirizzo di altro utente
        $response = $this->getJson("/api/user/addresses/{$otherAddress->id}");
        $response->assertStatus(404);

        // Tentativo di aggiornare indirizzo di altro utente
        $response = $this->putJson("/api/user/addresses/{$otherAddress->id}", [
            'label' => 'Hackerato'
        ]);
        $response->assertStatus(404);

        // Tentativo di eliminare indirizzo di altro utente
        $response = $this->deleteJson("/api/user/addresses/{$otherAddress->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function address_creation_requires_required_fields()
    {
        $response = $this->postJson('/api/user/addresses', [
            'label' => 'Casa'
            // Campi obbligatori mancanti
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Dati di validazione non validi'
                ])
                ->assertJsonValidationErrors([
                    'first_name',
                    'last_name',
                    'address_line_1',
                    'city',
                    'country',
                    'postal_code'
                ]);
    }

    /** @test */
    public function address_update_validates_data()
    {
        $address = UserAddress::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->putJson("/api/user/addresses/{$address->id}", [
            'first_name' => '', // Nome vuoto
            'country' => 'INVALID' // Paese troppo lungo
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['first_name', 'country']);
    }

    /** @test */
    public function user_cannot_access_nonexistent_address()
    {
        $response = $this->getJson('/api/user/addresses/99999');
        $response->assertStatus(404);

        $response = $this->putJson('/api/user/addresses/99999', [
            'label' => 'Test'
        ]);
        $response->assertStatus(404);

        $response = $this->deleteJson('/api/user/addresses/99999');
        $response->assertStatus(404);
    }

    /** @test */
    public function address_operations_require_authentication()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/user/addresses');
        $response->assertStatus(401);

        $response = $this->postJson('/api/user/addresses', []);
        $response->assertStatus(401);

        $response = $this->putJson('/api/user/addresses/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/user/addresses/1');
        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_have_multiple_addresses()
    {
        // Crea diversi indirizzi per lo stesso utente
        $addresses = [
            [
                'label' => 'Casa',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Roma 123',
                'city' => 'Milano',
                'country' => 'IT',
                'postal_code' => '20100'
            ],
            [
                'label' => 'Ufficio',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Milano 456',
                'city' => 'Roma',
                'country' => 'IT',
                'postal_code' => '00100'
            ],
            [
                'label' => 'Villa',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_line_1' => 'Via Napoli 789',
                'city' => 'Napoli',
                'country' => 'IT',
                'postal_code' => '80100'
            ]
        ];

        foreach ($addresses as $addressData) {
            $response = $this->postJson('/api/user/addresses', $addressData);
            $response->assertStatus(201);
        }

        // Verifica che tutti gli indirizzi siano stati creati
        $response = $this->getJson('/api/user/addresses');
        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
}