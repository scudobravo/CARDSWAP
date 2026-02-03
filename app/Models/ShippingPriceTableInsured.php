<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingPriceTableInsured extends Model
{
    use HasFactory;

    /** @var string Nome tabella (singolare, non plurale Eloquent) */
    protected $table = 'shipping_price_table_insured';

    protected $fillable = [
        'shipping_price_table_id',
        'package_bucket',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Relazione con la tabella prezzi
     */
    public function shippingPriceTable(): BelongsTo
    {
        return $this->belongsTo(ShippingPriceTable::class);
    }

    /**
     * Verifica se l'assicurazione è abilitata per questo bucket
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }
}
