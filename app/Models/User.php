<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'role',
        'phone',
        'bio',
        'avatar',
        'is_verified',
        'is_suspended',
        'addresses',
        'payment_methods',
        'rating',
        'total_sales',
        'total_purchases',
        // Nuovi campi KYC
        'fiscal_code',
        'birth_date',
        'birth_place',
        'nationality',
        'kyc_status',
        'kyc_submitted_at',
        'kyc_verified_at',
        'kyc_rejection_reason',
        // Campi indirizzo
        'address',
        'city',
        'postal_code',
        'country',
        // Campi per venditori
        'account_type',
        'business_name',
        'vat_number',
        'business_address',
        'business_phone',
        'business_description',
        // Preferenze
        'notification_preferences',
        'language',
        'timezone',
        'currency',
        // Sicurezza
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'email_verified_at',
        'email_verification_token',
        // Campi Stripe
        'stripe_account_id', 'stripe_charges_enabled', 'stripe_payouts_enabled', 'stripe_details_submitted',
        'stripe_verification_session_id', 'stripe_identity_verified', 'stripe_identity_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'addresses' => 'array',
            'payment_methods' => 'array',
            'rating' => 'decimal:2',
            // Nuovi cast
            'birth_date' => 'date',
            'kyc_submitted_at' => 'datetime',
            'kyc_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
            'two_factor_enabled' => 'boolean',
            // Cast Stripe
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
            'stripe_details_submitted' => 'boolean',
            'stripe_identity_verified' => 'boolean',
            'stripe_identity_verified_at' => 'datetime',
        ];
    }

    /**
     * Get the card listings for this user (as seller)
     */
    public function cardListings(): HasMany
    {
        return $this->hasMany(CardListing::class, 'seller_id');
    }

    /**
     * Get the orders where this user is the buyer
     */
    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * Get the orders where this user is the seller
     */
    public function sellerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    /**
     * Get the wishlist items for this user
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Feedback ricevuti come venditore
     */
    public function receivedFeedbacks(): HasMany
    {
        return $this->hasMany(OrderFeedback::class, 'seller_id');
    }

    /**
     * Feedback lasciati come acquirente
     */
    public function givenFeedbacks(): HasMany
    {
        return $this->hasMany(OrderFeedback::class, 'buyer_id');
    }

    // NUOVE RELAZIONI PER SETTIMANA 3

    /**
     * Get the KYC documents for this user
     */
    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    /**
     * Get the addresses for this user
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get the payment methods for this user
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    /**
     * Get the notifications for this user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * Get the default address for this user
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    /**
     * Get the default payment method for this user
     */
    public function defaultPaymentMethod(): HasOne
    {
        return $this->hasOne(UserPaymentMethod::class)->where('is_default', true);
    }

    /**
     * Get the latest KYC document for this user
     */
    public function latestKycDocument(): HasOne
    {
        return $this->hasOne(KycDocument::class)->latest();
    }

    // METODI AGGIUNTI PER SETTIMANA 3

    /**
     * Check if user has completed KYC
     */
    public function hasCompletedKyc(): bool
    {
        return $this->kyc_status === 'approved';
    }

    /**
     * Check if user has pending KYC
     */
    public function hasPendingKyc(): bool
    {
        return $this->kyc_status === 'pending';
    }

    /**
     * Check if user has rejected KYC
     */
    public function hasRejectedKyc(): bool
    {
        return $this->kyc_status === 'rejected';
    }

    /**
     * Check if user has submitted KYC
     */
    public function hasSubmittedKyc(): bool
    {
        return in_array($this->kyc_status, ['pending', 'approved', 'rejected']);
    }

    /**
     * Check if user can sell (has approved KYC and is seller)
     */
    public function canSell(): bool
    {
        return $this->isSeller() && $this->hasCompletedKyc();
    }

    /**
     * Check if user needs to complete KYC
     */
    public function needsKyc(): bool
    {
        return $this->isSeller() && !$this->hasSubmittedKyc();
    }

    /**
     * Check if user has verified email
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user has addresses
     */
    public function hasAddresses(): bool
    {
        return $this->addresses()->exists();
    }

    /**
     * Check if user has payment methods
     */
    public function hasPaymentMethods(): bool
    {
        return $this->paymentMethods()->exists();
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get user's display name (business name for sellers, full name for others)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isSeller() && $this->business_name) {
            return $this->business_name;
        }
        return $this->name;
    }

    /**
     * Get user's status for display
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_suspended) {
            return 'Sospeso';
        }
        
        if ($this->isSeller()) {
            if ($this->hasCompletedKyc()) {
                return 'Venditore verificato';
            } elseif ($this->hasPendingKyc()) {
                return 'Verifica in corso';
            } elseif ($this->hasRejectedKyc()) {
                return 'Verifica rifiutata';
            } else {
                return 'Venditore non verificato';
            }
        }
        
        return 'Acquirente';
    }

    /**
     * Check if user is a seller
     */
    public function isSeller(): bool
    {
        return in_array($this->role, ['seller', 'admin']);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Update last login information
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip
        ]);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): void
    {
        $this->update(['email_verified_at' => now()]);
    }

    /**
     * Update KYC status
     */
    public function updateKycStatus(string $status, ?string $reason = null): void
    {
        $data = ['kyc_status' => $status];
        
        if ($status === 'pending') {
            $data['kyc_submitted_at'] = now();
        } elseif ($status === 'approved') {
            $data['kyc_verified_at'] = now();
        } elseif ($status === 'rejected') {
            $data['kyc_rejection_reason'] = $reason;
        }
        
        $this->update($data);
    }

    // METODI STRIPE

    /**
     * Check if user has Stripe Connect account
     */
    public function hasStripeAccount(): bool
    {
        return !is_null($this->stripe_account_id);
    }

    /**
     * Check if user can receive payments via Stripe
     */
    public function canReceivePayments(): bool
    {
        return $this->hasStripeAccount() && 
               $this->stripe_charges_enabled && 
               $this->stripe_payouts_enabled;
    }

    /**
     * Check if user has completed Stripe onboarding
     */
    public function hasCompletedStripeOnboarding(): bool
    {
        return $this->hasStripeAccount() && $this->stripe_details_submitted;
    }

    /**
     * Check if user has verified identity via Stripe
     */
    public function hasStripeIdentityVerified(): bool
    {
        return $this->stripe_identity_verified;
    }

    /**
     * Get Stripe account status
     */
    public function getStripeAccountStatus(): array
    {
        return [
            'has_account' => $this->hasStripeAccount(),
            'account_id' => $this->stripe_account_id,
            'charges_enabled' => $this->stripe_charges_enabled,
            'payouts_enabled' => $this->stripe_payouts_enabled,
            'details_submitted' => $this->stripe_details_submitted,
            'can_receive_payments' => $this->canReceivePayments(),
            'identity_verified' => $this->stripe_identity_verified,
            'verification_session_id' => $this->stripe_verification_session_id,
        ];
    }

    /**
     * Update Stripe account status
     */
    public function updateStripeAccountStatus(array $status): void
    {
        $this->update([
            'stripe_charges_enabled' => $status['charges_enabled'] ?? $this->stripe_charges_enabled,
            'stripe_payouts_enabled' => $status['payouts_enabled'] ?? $this->stripe_payouts_enabled,
            'stripe_details_submitted' => $status['details_submitted'] ?? $this->stripe_details_submitted,
        ]);
    }

    /**
     * Mark Stripe identity as verified
     */
    public function markStripeIdentityVerified(): void
    {
        $this->update([
            'stripe_identity_verified' => true,
            'stripe_identity_verified_at' => now(),
            'kyc_status' => 'approved'
        ]);
    }

    /**
     * Enhanced canSell method with Stripe checks
     */
    public function canSellWithStripe(): bool
    {
        // Deve essere un venditore
        if (!$this->isSeller()) {
            return false;
        }

        // Deve avere KYC completato (Stripe Identity o manuale)
        if (!$this->hasCompletedKyc() && !$this->hasStripeIdentityVerified()) {
            return false;
        }

        // Deve avere account Stripe Connect configurato
        if (!$this->canReceivePayments()) {
            return false;
        }

        return true;
    }
}
