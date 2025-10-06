<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderConversation extends Model
{
    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'status',
        'last_sender_id',
        'last_message_at',
        'last_email_notification_at',
        'unread_count_buyer',
        'unread_count_seller',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_email_notification_at' => 'datetime',
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

    public function lastSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_sender_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'conversation_id');
    }
}


