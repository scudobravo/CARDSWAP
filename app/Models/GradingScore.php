<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradingScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_company_id',
        'score',
        'description',
        'short_code',
        'is_special',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'score' => 'decimal:1',
        'is_special' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the grading company for this score
     */
    public function gradingCompany()
    {
        return $this->belongsTo(GradingCompany::class);
    }

    /**
     * Get the card models with this grading score
     */
    public function cardModels()
    {
        return $this->hasMany(CardModel::class, 'grading_score', 'score');
    }

    /**
     * Scope per voti attivi
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
        return $query->orderBy('sort_order')->orderBy('score', 'desc');
    }

    /**
     * Scope per voti speciali
     */
    public function scopeSpecial($query)
    {
        return $query->where('is_special', true);
    }

    /**
     * Scope per azienda specifica
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('grading_company_id', $companyId);
    }

    /**
     * Scope per score minimo
     */
    public function scopeMinScore($query, $minScore)
    {
        return $query->where('score', '>=', $minScore);
    }
}
