<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'card_model_id',
        'max_price',
        'condition_preference',
        'notify_on_match'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cardModel()
    {
        return $this->belongsTo(CardModel::class);
    }

    protected $casts = [
        'max_price' => 'decimal:2',
        'notify_on_match' => 'boolean',
    ];
}
