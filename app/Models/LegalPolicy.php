<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'content',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Tipos de políticas
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

    // Helper methods
    public static function getPolicy($type)
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    public function getTypeNameAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    // Relationship with user who updated
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}