<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_url',
        'action_text',
        'is_read',
        'read_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relazione con l'utente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Controlla se la notifica è stata letta
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Controlla se la notifica è scaduta
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Controlla se la notifica è attiva (non scaduta)
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Marca la notifica come letta
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    /**
     * Marca la notifica come non letta
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }

    /**
     * Ottieni il tipo di notifica in italiano
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'order_status' => 'Stato ordine',
            'kyc_update' => 'Aggiornamento KYC',
            'new_message' => 'Nuovo messaggio',
            'payment_received' => 'Pagamento ricevuto',
            'shipment_update' => 'Aggiornamento spedizione',
            'price_alert' => 'Avviso prezzo',
            'stock_alert' => 'Avviso disponibilità',
            'system' => 'Sistema',
            'promotion' => 'Promozione',
            default => $this->type
        };
    }

    /**
     * Ottieni l'icona per il tipo di notifica
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'order_status' => '📦',
            'kyc_update' => '📋',
            'new_message' => '💬',
            'payment_received' => '💰',
            'shipment_update' => '🚚',
            'price_alert' => '📊',
            'stock_alert' => '📦',
            'system' => '⚙️',
            'promotion' => '🎉',
            default => '🔔'
        };
    }

    /**
     * Ottieni il tempo trascorso dalla creazione
     */
    public function getTimeAgoAttribute(): string
    {
        $diff = $this->created_at->diffForHumans();
        return $diff;
    }

    /**
     * Ottieni i dati formattati per la visualizzazione
     */
    public function getFormattedDataAttribute(): array
    {
        if (!$this->data) {
            return [];
        }

        $formatted = [];
        foreach ($this->data as $key => $value) {
            switch ($key) {
                case 'order_id':
                    $formatted['order_id'] = 'Ordine #' . $value;
                    break;
                case 'amount':
                    $formatted['amount'] = '€' . number_format($value, 2, ',', '.');
                    break;
                case 'status':
                    $formatted['status'] = ucfirst($value);
                    break;
                default:
                    $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Scope per notifiche non lette
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope per notifiche lette
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope per notifiche attive (non scadute)
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope per tipo specifico
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope per notifiche recenti (ultimi 30 giorni)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Scope per notifiche ordinate per data
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
