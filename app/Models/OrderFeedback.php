<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFeedback extends Model
{
    protected $table = 'order_feedbacks';
    
    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'rating',
        'comment',
        'is_public',
        'is_hidden',
        'seller_response',
        'seller_response_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
        'is_hidden' => 'boolean',
        'seller_response_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Scope per feedback pubblici e non nascosti
     */
    public function scopeVisible($query)
    {
        return $query->where('is_public', true)->where('is_hidden', false);
    }

    /**
     * Scope per feedback di un venditore specifico
     */
    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
