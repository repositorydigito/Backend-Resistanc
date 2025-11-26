<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'question',
        'answer',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Tipos de FAQs legales
    const TYPE_PRIVACY = 'privacy';
    const TYPE_TERMS = 'terms';

    public static function getTypes(): array
    {
        return [
            self::TYPE_PRIVACY => 'Políticas de Privacidad',
            self::TYPE_TERMS => 'Términos y Condiciones',
        ];
    }

    // Scopes
    public function scopePrivacy($query)
    {
        return $query->where('type', self::TYPE_PRIVACY);
    }

    public function scopeTerms($query)
    {
        return $query->where('type', self::TYPE_TERMS);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public static function getFaqsByType($type)
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getTypeNameAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }
}