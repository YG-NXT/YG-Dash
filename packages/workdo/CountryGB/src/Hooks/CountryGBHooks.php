<?php

namespace Workdo\CountryGB\Hooks;

use App\Classes\Hooks;
use App\Classes\GovernmentIntegrationRegistry;
use App\Services\CurrencyService;
use Workdo\CountryGB\Services\HMRCVATService;
use Workdo\CountryGB\Services\HMRCRTIService;
use Workdo\CountryGB\Services\CompaniesHouseService;
use Workdo\CountryGB\Services\FSAService;
use Workdo\CountryGB\Services\CQCService;
use Workdo\CountryGB\Services\NHSSpineService;
use Workdo\CountryGB\Services\UKTaxEngine;
use Workdo\CountryGB\Services\UKPayrollEngine;
use Workdo\CountryGB\Services\UKValidationService;
use Workdo\CountryGB\Services\UKReportsService;
use Workdo\CountryGB\Services\UKOnboardingService;

class CountryGBHooks
{
    public static function register(): void
    {
        $registry = app(GovernmentIntegrationRegistry::class);

        // Tax integrations
        $registry->register('HMRC_VAT', new HMRCVATService(), ['GB'], 'tax');
        $registry->register('HMRC_RTI', new HMRCRTIService(), ['GB'], 'payroll');

        // Business registry
        $registry->register('CompaniesHouse', new CompaniesHouseService(), ['GB'], 'business_registry');

        // Healthcare integrations
        $registry->register('NHS', new NHSSpineService(), ['GB'], 'healthcare_registry');
        $registry->register('CQC', new CQCService(), ['GB'], 'healthcare_regulator');

        // Food safety
        $registry->register('FSA', new FSAService(), ['GB'], 'food_safety');

        // Core services (not government, but UK-specific)
        $registry->register('UK_Tax_Engine', new UKTaxEngine(), ['GB'], 'tax_engine');
        $registry->register('UK_Payroll_Engine', new UKPayrollEngine(), ['GB'], 'payroll_engine');
        $registry->register('UK_Validation', new UKValidationService(), ['GB'], 'validation');
        $registry->register('UK_Reports', new UKReportsService(), ['GB'], 'reports');
        $registry->register('UK_Onboarding', new UKOnboardingService(), ['GB'], 'onboarding');

        // Core ERP hooks
        self::registerCoreHooks();
    }

    protected static function registerCoreHooks(): void
    {
        // Invoice hooks
        Hooks::add_action('invoice_created', [self::class, 'addVATNumberToInvoice']);
        Hooks::add_filter('invoice_number_format', [self::class, 'ukInvoiceNumberFormat'], 10, 1);
        Hooks::add_filter('document_template', [self::class, 'useUKDocumentTemplate'], 10, 3);

        // Tax hooks
        Hooks::add_filter('calculate_tax', [self::class, 'calculateUKVAT'], 10, 3);

        // Contact hooks
        Hooks::add_filter('contact_tax_id_label', [self::class, 'vatNumberLabel']);
        Hooks::add_filter('contact_tax_id_validation', [self::class, 'validateVATNumber']);

        // Landing page hooks
        Hooks::add_filter('landing_page_hero', [self::class, 'ukHero']);
        Hooks::add_filter('landing_page_features', [self::class, 'ukFeatures']);
        Hooks::add_filter('landing_page_pricing', [self::class, 'ukPricing']);
        Hooks::add_filter('landing_page_compliance', [self::class, 'ukCompliance']);
        Hooks::add_filter('landing_page_testimonials', [self::class, 'ukTestimonials']);
        Hooks::add_filter('landing_page_cta', [self::class, 'ukCTA']);
    }

    public static function addVATNumberToInvoice($invoice): void
    {
        $company = $invoice->createdBy;

        if ($company->country_code === 'GB' && !empty($company->vat_number)) {
            $invoice->update(['vat_number' => $company->vat_number]);
        }
    }

    public static function ukInvoiceNumberFormat(string $format): string
    {
        return 'INV-{year}-{sequence}';
    }

    public static function useUKDocumentTemplate(string $template, string $type, $company): string
    {
        if ($company->country_code === 'GB') {
            $templates = [
                'invoice' => 'CountryGB::documents.invoice',
                'quote' => 'CountryGB::documents.quote',
                'credit_note' => 'CountryGB::documents.credit-note',
            ];

            return $templates[$type] ?? $template;
        }

        return $template;
    }

    public static function calculateUKVAT($taxResult, $items, $context): mixed
    {
        $engine = new UKTaxEngine();

        return $engine->calculate($items, $context);
    }

    public static function vatNumberLabel(string $label): string
    {
        return 'VAT Registration No:';
    }

    public static function validateVATNumber(string $taxNumber, string $type): array
    {
        if ($type === 'vat') {
            $validationService = new UKValidationService();
            return $validationService->validateVATNumber($taxNumber);
        }

        return ['valid' => true];
    }

    public static function ukHero($hero, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $hero;
        }

        return [
            'title' => "UK's Leading ERP Software",
            'subtitle' => 'HMRC MTD compliant. VAT invoicing built-in. NHS-ready for healthcare.',
            'cta_text' => 'Start your free UK trial',
            'cta_link' => '/uk/onboarding',
            'badge' => 'Trusted by 2,000+ UK businesses',
            'currency' => 'GBP',
            'symbol' => '£',
        ];
    }

    public static function ukFeatures($features, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $features;
        }

        return [
            [
                'title' => 'HMRC MTD VAT',
                'description' => 'Submit VAT returns directly to HMRC. Automatic 9-box VAT returns.',
                'icon' => 'file-text',
            ],
            [
                'title' => 'UK Payroll (PAYE/RTI)',
                'description' => 'Full PAYE payroll with RTI submission. Tax codes, NI, pension auto-enrollment.',
                'icon' => 'users',
            ],
            [
                'title' => 'NHS Integration',
                'description' => 'Connect to NHS Spine for patient lookup and GP referrals.',
                'icon' => 'heart',
            ],
            [
                'title' => 'CQC Compliance',
                'description' => 'Track CQC registration and inspection ratings.',
                'icon' => 'shield-check',
            ],
            [
                'title' => 'Companies House',
                'description' => 'Auto-fill company details from Companies House API.',
                'icon' => 'building',
            ],
            [
                'title' => 'UK Payroll (PAYE/RTI)',
                'description' => 'Full PAYE payroll with RTI submission. Tax codes, NI, pension auto-enrollment.',
                'icon' => 'users',
            ],
        ];
    }

    public static function ukPricing($pricing, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $pricing;
        }

        $currencyService = new CurrencyService();
        $currency = $currencyService->getCurrencyForCountry('GB');
        $symbol = $currencyService->getSymbol($currency);

        return [
            'currency' => $currency,
            'symbol' => $symbol,
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => 49,
                    'period' => 'month',
                    'features' => ['1 user', '10 invoices/month', 'HMRC MTD VAT', 'UK support'],
                ],
                [
                    'name' => 'Business',
                    'price' => 99,
                    'period' => 'month',
                    'features' => ['5 users', 'Unlimited invoices', 'PAYE payroll', 'NHS integration', 'UK support'],
                    'popular' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 249,
                    'period' => 'month',
                    'features' => ['Unlimited users', 'Everything in Business', 'CQC compliance', 'Dedicated UK account manager'],
                ],
            ],
            'annual_discount' => '20%',
        ];
    }

    public static function ukCompliance($compliance, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $compliance;
        }

        return [
            'badges' => [
                ['name' => 'HMRC MTD', 'description' => 'Making Tax Digital compliant'],
                ['name' => 'GDPR', 'description' => 'UK Data Protection compliant'],
                ['name' => 'Companies House', 'description' => 'Integrated company filings'],
                ['name' => 'CQC', 'description' => 'Healthcare quality standards'],
                ['name' => 'NHS', 'description' => 'NHS Spine connected'],
            ],
        ];
    }

    public static function ukTestimonials($testimonials, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $testimonials;
        }

        return [
            [
                'name' => 'Sarah Johnson',
                'role' => 'Finance Director, London Clinic',
                'quote' => 'DashSaaS transformed our VAT reporting. HMRC MTD submission is now automatic.',
                'avatar' => null,
            ],
            [
                'name' => 'James Smith',
                'role' => 'Owner, Manchester Restaurant Group',
                'quote' => 'The UK payroll engine and Companies House integration saved us hours every week.',
                'avatar' => null,
            ],
            [
                'name' => 'Dr. Emily Brown',
                'role' => 'Practice Manager, Bristol Healthcare',
                'quote' => 'NHS Spine integration and CQC compliance tracking made us fully compliant.',
                'avatar' => null,
            ],
        ];
    }

    public static function ukCTA($cta, $company): array
    {
        if ($company->country_code !== 'GB') {
            return $cta;
        }

        return [
            'title' => 'Ready to transform your UK business?',
            'subtitle' => 'Join 2,000+ UK companies using DashSaaS. HMRC MTD compliant.',
            'button_text' => 'Start your free trial',
            'button_link' => '/uk/onboarding',
            'secondary_text' => 'No credit card required. 14-day free trial.',
        ];
    }
}
