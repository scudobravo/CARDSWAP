<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'card_set_id',
        'player_id',
        'team_id',
        'league_id',
        'name',
        'slug',
        'description',
        'set_name',
        'year',
        'rarity',
        'card_number',
        'card_number_in_set',
        'parallel_type',
        'insert_type',
        'is_rookie',
        'is_star',
        'is_legend',
        'is_autograph',
        'is_relic',
        'artist',
        'image_url',
        'grading_company_id',
        'grading_score',
        'grading_notes',
        'price',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_rookie' => 'boolean',
        'is_star' => 'boolean',
        'is_legend' => 'boolean',
        'is_autograph' => 'boolean',
        'is_relic' => 'boolean',
        'grading_score' => 'decimal:1',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'attributes' => 'array',
    ];

    /**
     * Get the category for this card model
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the card set for this card model
     */
    public function cardSet()
    {
        return $this->belongsTo(CardSet::class);
    }

    /**
     * Get the player for this card model
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the team for this card model
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the league for this card model
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the grading company for this card model
     */
    public function gradingCompany()
    {
        return $this->belongsTo(GradingCompany::class);
    }

    /**
     * Get the card listings for this card model
     */
    public function cardListings()
    {
        return $this->hasMany(CardListing::class);
    }

    /**
     * Scope per carte attive
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope per set specifico
     */
    public function scopeBySet($query, $setId)
    {
        return $query->where('card_set_id', $setId);
    }

    /**
     * Scope per giocatore specifico
     */
    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    /**
     * Scope per squadra specifica
     */
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope per lega specifica
     */
    public function scopeByLeague($query, $leagueId)
    {
        return $query->where('league_id', $leagueId);
    }

    /**
     * Scope per anno specifico
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope per rarità specifica
     */
    public function scopeByRarity($query, $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    /**
     * Scope per rookie cards
     */
    public function scopeRookie($query)
    {
        return $query->where('is_rookie', true);
    }

    /**
     * Scope per star cards
     */
    public function scopeStar($query)
    {
        return $query->where('is_star', true);
    }

    /**
     * Scope per legend cards
     */
    public function scopeLegend($query)
    {
        return $query->where('is_legend', true);
    }

    /**
     * Scope per autograph cards
     */
    public function scopeAutograph($query)
    {
        return $query->where('is_autograph', true);
    }

    /**
     * Scope per relic cards
     */
    public function scopeRelic($query)
    {
        return $query->where('is_relic', true);
    }

    /**
     * Scope per grading company specifica
     */
    public function scopeByGradingCompany($query, $companyId)
    {
        return $query->where('grading_company_id', $companyId);
    }

    /**
     * Scope per score grading minimo
     */
    public function scopeMinGradingScore($query, $score)
    {
        return $query->where('grading_score', '>=', $score);
    }
}
