<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'issuing_country',
        'issue_date',
        'expiry_date',
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'fiscal_code',
        'front_image_path',
        'back_image_path',
        'file_path', // Nuovo campo per file upload
        'original_name', // Nome originale file
        'file_size', // Dimensione file
        'mime_type', // Tipo MIME
        'status',
        'rejection_reason',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'birth_date' => 'date',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Relazione con l'utente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relazione con l'admin che ha verificato
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Controlla se il documento è scaduto
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Controlla se il documento è in scadenza (entro 30 giorni)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Controlla se il documento è approvato
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Controlla se il documento è in attesa
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Controlla se il documento è rifiutato
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Ottieni il nome completo del titolare
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Ottieni il tipo di documento in italiano
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'identity_card' => 'Carta d\'identità',
            'passport' => 'Passaporto',
            'driving_license' => 'Patente di guida',
            default => $this->document_type
        };
    }

    /**
     * Ottieni lo stato in italiano
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'In attesa',
            'approved' => 'Approvato',
            'rejected' => 'Rifiutato',
            default => $this->status
        };
    }

    /**
     * Scope per documenti in attesa
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope per documenti approvati
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope per documenti rifiutati
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope per documenti scaduti
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope per documenti in scadenza
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expiry_date', '<=', now()->addDays(30))
                    ->where('expiry_date', '>', now());
    }
}
