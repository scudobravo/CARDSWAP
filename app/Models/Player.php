<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'first_name',
        'last_name',
        'position',
        'nationality',
        'photo_url',
        'birth_date',
        'height_cm',
        'preferred_foot',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'height_cm' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the team for this player
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the card models for this player
     */
    public function cardModels()
    {
        return $this->hasMany(CardModel::class);
    }

    /**
     * Scope per giocatori attivi
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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope per posizione
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope per nazionalità
     */
    public function scopeByNationality($query, $nationality)
    {
        return $query->where('nationality', $nationality);
    }
}
