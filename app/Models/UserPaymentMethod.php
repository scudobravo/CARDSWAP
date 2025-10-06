<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'last_four_digits',
        'card_brand',
        'expiry_date',
        'account_email',
        'bank_name',
        'iban',
        'bic_swift',
        'is_default',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Relazione con l'utente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ottieni il tipo di metodo di pagamento in italiano
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'credit_card' => 'Carta di credito',
            'debit_card' => 'Carta di debito',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bonifico bancario',
            default => $this->type
        };
    }

    /**
     * Ottieni il marchio della carta in italiano
     */
    public function getCardBrandLabelAttribute(): string
    {
        return match($this->card_brand) {
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'discover' => 'Discover',
            'maestro' => 'Maestro',
            default => $this->card_brand
        };
    }

    /**
     * Controlla se il metodo è attivo
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Controlla se è il metodo predefinito
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Controlla se la carta è scaduta
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Controlla se la carta è in scadenza (entro 30 giorni)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Ottieni la maschera della carta (es. **** **** **** 1234)
     */
    public function getMaskedCardNumberAttribute(): string
    {
        if (!$this->last_four_digits) {
            return '**** **** **** ****';
        }
        return '**** **** **** ' . $this->last_four_digits;
    }

    /**
     * Ottieni la descrizione completa del metodo
     */
    public function getDescriptionAttribute(): string
    {
        switch ($this->type) {
            case 'credit_card':
            case 'debit_card':
                $description = $this->card_brand_label;
                if ($this->last_four_digits) {
                    $description .= ' ****' . $this->last_four_digits;
                }
                if ($this->expiry_date) {
                    $description .= ' (Scadenza: ' . $this->expiry_date->format('m/Y') . ')';
                }
                return $description;
                
            case 'paypal':
                return 'PayPal - ' . ($this->account_email ?: 'Account collegato');
                
            case 'bank_transfer':
                $description = 'Bonifico bancario';
                if ($this->bank_name) {
                    $description .= ' - ' . $this->bank_name;
                }
                if ($this->iban) {
                    $description .= ' - IBAN: ' . substr($this->iban, 0, 8) . '****';
                }
                return $description;
                
            default:
                return $this->name;
        }
    }

    /**
     * Imposta come metodo predefinito
     */
    public function setAsDefault(): void
    {
        // Rimuovi il flag predefinito da tutti gli altri metodi dell'utente
        $this->user->paymentMethods()->where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Imposta questo come predefinito
        $this->update(['is_default' => true]);
    }

    /**
     * Aggiorna la data di ultimo utilizzo
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope per metodi attivi
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope per metodi predefiniti
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope per tipo specifico
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope per metodi non scaduti
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', now());
        });
    }
}
