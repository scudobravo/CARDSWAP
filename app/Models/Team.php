<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'slug',
        'city',
        'stadium',
        'logo_url',
        'primary_color',
        'secondary_color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the league for this team
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the players for this team
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the card models for this team
     */
    public function cardModels()
    {
        return $this->hasMany(CardModel::class);
    }

    /**
     * Scope per squadre attive
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
}
