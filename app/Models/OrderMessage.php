<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachments',
        'is_read_by_buyer',
        'is_read_by_seller',
        'is_flagged',
        'flagged_by',
        'flagged_reason',
        'is_hidden',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read_by_buyer' => 'boolean',
        'is_read_by_seller' => 'boolean',
        'is_flagged' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(OrderConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}


