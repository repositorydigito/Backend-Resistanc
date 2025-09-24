<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePageContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'key',
        'value',
        'type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Scopes for different sections
    public function scopeHero($query)
    {
        return $query->where('section', 'hero');
    }

    public function scopePackages($query)
    {
        return $query->where('section', 'packages');
    }

    public function scopeServices($query)
    {
        return $query->where('section', 'services');
    }

    public function scopeDownload($query)
    {
        return $query->where('section', 'download');
    }

    public function scopeLocation($query)
    {
        return $query->where('section', 'location');
    }

    public function scopeFaq($query)
    {
        return $query->where('section', 'faq');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper method to get content by section and key
    public static function getContent($section, $key, $default = '')
    {
        $content = static::where('section', $section)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        return $content ? $content->value : $default;
    }

    // Helper method to get all content for a section
    public static function getSectionContent($section)
    {
        return static::where('section', $section)
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->pluck('value', 'key');
    }
}