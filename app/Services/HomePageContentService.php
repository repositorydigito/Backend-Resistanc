<?php

namespace App\Services;

use App\Models\HomePageContent;
use Illuminate\Support\Collection;

class HomePageContentService
{
    /**
     * Get content for a specific section and key
     */
    public static function get(string $section, string $key, string $default = ''): string
    {
        return HomePageContent::getContent($section, $key, $default);
    }

    /**
     * Get all content for a section as a collection
     */
    public static function getSection(string $section): Collection
    {
        return HomePageContent::getSectionContent($section);
    }

    /**
     * Get hero section content
     */
    public static function getHeroContent(): Collection
    {
        return self::getSection('hero');
    }

    /**
     * Get packages section content
     */
    public static function getPackagesContent(): Collection
    {
        return self::getSection('packages');
    }

    /**
     * Get services section content
     */
    public static function getServicesContent(): Collection
    {
        return self::getSection('services');
    }

    /**
     * Get download section content
     */
    public static function getDownloadContent(): Collection
    {
        return self::getSection('download');
    }

    /**
     * Get location section content
     */
    public static function getLocationContent(): Collection
    {
        return self::getSection('location');
    }

    /**
     * Get FAQ section content
     */
    public static function getFaqContent(): Collection
    {
        return self::getSection('faq');
    }

    /**
     * Get disciplines section content
     */
    public static function getDisciplinesContent(): Collection
    {
        return self::getSection('disciplines');
    }

    /**
     * Check if an image path is a full URL or relative path
     */
    public static function getImageUrl(string $imagePath): string
    {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, '/')) {
            return $imagePath;
        }

        return asset('storage/' . $imagePath);
    }
}