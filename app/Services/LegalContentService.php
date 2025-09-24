<?php

namespace App\Services;

use App\Models\LegalPolicy;
use App\Models\LegalFaq;

class LegalContentService
{
    /**
     * Get privacy policy
     */
    public static function getPrivacyPolicy(): ?LegalPolicy
    {
        return LegalPolicy::getPolicy(LegalPolicy::TYPE_PRIVACY);
    }

    /**
     * Get terms and conditions
     */
    public static function getTermsAndConditions(): ?LegalPolicy
    {
        return LegalPolicy::getPolicy(LegalPolicy::TYPE_TERMS);
    }

    /**
     * Get privacy policy FAQs
     */
    public static function getPrivacyFaqs()
    {
        return LegalFaq::getFaqsByType(LegalFaq::TYPE_PRIVACY);
    }

    /**
     * Get terms and conditions FAQs
     */
    public static function getTermsFaqs()
    {
        return LegalFaq::getFaqsByType(LegalFaq::TYPE_TERMS);
    }

    /**
     * Get all FAQs by type
     */
    public static function getFaqsByType(string $type)
    {
        return LegalFaq::getFaqsByType($type);
    }

    /**
     * Get policy by type
     */
    public static function getPolicyByType(string $type): ?LegalPolicy
    {
        return LegalPolicy::getPolicy($type);
    }
}