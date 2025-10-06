<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone',
        'is_default',
        'is_billing',
        'is_shipping',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_billing' => 'boolean',
        'is_shipping' => 'boolean',
    ];

    /**
     * Relazione con l'utente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ottieni il nome completo
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Ottieni l'indirizzo completo formattato
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        $address .= ', ' . $this->postal_code . ' ' . $this->city;
        
        if ($this->state_province) {
            $address .= ', ' . $this->state_province;
        }
        
        $address .= ', ' . $this->country;
        
        return $address;
    }

    /**
     * Ottieni l'indirizzo breve (città, provincia, paese)
     */
    public function getShortAddressAttribute(): string
    {
        $address = $this->city;
        
        if ($this->state_province) {
            $address .= ', ' . $this->state_province;
        }
        
        $address .= ', ' . $this->country;
        
        return $address;
    }

    /**
     * Controlla se è l'indirizzo predefinito
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Controlla se è un indirizzo di fatturazione
     */
    public function isBilling(): bool
    {
        return $this->is_billing;
    }

    /**
     * Controlla se è un indirizzo di spedizione
     */
    public function isShipping(): bool
    {
        return $this->is_shipping;
    }

    /**
     * Imposta come indirizzo predefinito
     */
    public function setAsDefault(): void
    {
        // Rimuovi il flag predefinito da tutti gli altri indirizzi dell'utente
        $this->user->addresses()->where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Imposta questo come predefinito
        $this->update(['is_default' => true]);
    }

    /**
     * Scope per indirizzi predefiniti
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope per indirizzi di fatturazione
     */
    public function scopeBilling($query)
    {
        return $query->where('is_billing', true);
    }

    /**
     * Scope per indirizzi di spedizione
     */
    public function scopeShipping($query)
    {
        return $query->where('is_shipping', true);
    }

    /**
     * Scope per indirizzi attivi (non eliminati)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
