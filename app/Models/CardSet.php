<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'brand',
        'year',
        'season',
        'description',
        'logo_url',
        'cover_image_url',
        'total_cards',
        'is_official',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'year' => 'string',
        'total_cards' => 'integer',
        'is_official' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category for this card set
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the card models for this set
     */
    public function cardModels()
    {
        return $this->hasMany(CardModel::class);
    }

    /**
     * Scope per set attivi
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope per ordinamento
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('year', 'desc')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope per brand
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope per anno
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }
}
