<?php

namespace Workdo\CountryGB\Services;

class UKOnboardingService
{
    private UKValidationService $validation;
    private UKTaxEngine $taxEngine;
    private UKPayrollEngine $payrollEngine;
    private CompaniesHouseService $companiesHouse;

    public function __construct()
    {
        $this->validation = new UKValidationService();
        $this->taxEngine = new UKTaxEngine();
        $this->payrollEngine = new UKPayrollEngine();
        $this->companiesHouse = new CompaniesHouseService();
    }

    public function getOnboardingSteps(): array
    {
        return [
            'company_details' => [
                'title' => 'Company Details',
                'fields' => [
                    'company_name' => ['label' => 'Company Name', 'type' => 'text', 'required' => true],
                    'company_number' => ['label' => 'Companies House Number', 'type' => 'text', 'required' => false],
                    'vat_number' => ['label' => 'VAT Registration Number', 'type' => 'text', 'required' => false],
                    'utr' => ['label' => 'Unique Taxpayer Reference (UTR)', 'type' => 'text', 'required' => false],
                    'address_line_1' => ['label' => 'Address Line 1', 'type' => 'text', 'required' => true],
                    'address_line_2' => ['label' => 'Address Line 2', 'type' => 'text', 'required' => false],
                    'city' => ['label' => 'City/Town', 'type' => 'text', 'required' => true],
                    'postcode' => ['label' => 'Postcode', 'type' => 'text', 'required' => true],
                ],
            ],
            'contact_details' => [
                'title' => 'Contact Details',
                'fields' => [
                    'phone' => ['label' => 'Phone Number', 'type' => 'tel', 'required' => true],
                    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
                    'website' => ['label' => 'Website', 'type' => 'url', 'required' => false],
                ],
            ],
            'tax_details' => [
                'title' => 'Tax Details',
                'fields' => [
                    'vat_registered' => ['label' => 'Are you VAT registered?', 'type' => 'checkbox', 'required' => true],
                    'vat_number' => ['label' => 'VAT Number', 'type' => 'text', 'required' => false],
                    'vat_scheme' => ['label' => 'VAT Scheme', 'type' => 'select', 'options' => ['standard', 'flat_rate', 'cash_scheme', 'annual'], 'required' => false],
                    'fiscal_year_end' => ['label' => 'Fiscal Year End', 'type' => 'select', 'options' => ['31_March', '30_April', '31_May', '30_June', '31_July', '31_August', '30_September', '31_October', '30_November', '31_December'], 'required' => false],
                ],
            ],
            'payroll_details' => [
                'title' => 'Payroll Details',
                'fields' => [
                    'paye_reference' => ['label' => 'PAYE Reference', 'type' => 'text', 'required' => false],
                    'cis_number' => ['label' => 'CIS Contractor Number', 'type' => 'text', 'required' => false],
                    'accounts_office_reference' => ['label' => 'Accounts Office Reference', 'type' => 'text', 'required' => false],
                ],
            ],
            'bank_details' => [
                'title' => 'Bank Details',
                'fields' => [
                    'bank_name' => ['label' => 'Bank Name', 'type' => 'text', 'required' => true],
                    'sort_code' => ['label' => 'Sort Code', 'type' => 'text', 'required' => true],
                    'account_number' => ['label' => 'Account Number', 'type' => 'text', 'required' => true],
                    'account_name' => ['label' => 'Account Name', 'type' => 'text', 'required' => true],
                ],
            ],
            'preferences' => [
                'title' => 'Preferences',
                'fields' => [
                    'currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['GBP'], 'default' => 'GBP'],
                    'date_format' => ['label' => 'Date Format', 'type' => 'select', 'options' => ['DD/MM/YYYY', 'MM/DD/YYYY'], 'default' => 'DD/MM/YYYY'],
                    'timezone' => ['label' => 'Timezone', 'type' => 'select', 'options' => ['Europe/London'], 'default' => 'Europe/London'],
                    'language' => ['label' => 'Language', 'type' => 'select', 'options' => ['en-GB'], 'default' => 'en-GB'],
                ],
            ],
        ];
    }

    public function validateOnboardingData(array $data, string $step): array
    {
        $errors = [];
        $steps = $this->getOnboardingSteps();

        if (!isset($steps[$step])) {
            return ['valid' => false, 'errors' => ['Invalid step']];
        }

        $fields = $steps[$step]['fields'];

        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig['required'] ?? false) {
                if (empty($data[$fieldName] ?? null)) {
                    $errors[$fieldName] = $fieldConfig['label'] . ' is required';
                }
            }

            if (!empty($data[$fieldName] ?? null)) {
                if ($fieldName === 'postcode') {
                    $result = $this->validation->validatePostcode($data[$fieldName]);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                } elseif ($fieldName === 'vat_number') {
                    $result = $this->validation->validateVATNumber($data[$fieldName]);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                } elseif ($fieldName === 'company_number') {
                    $result = $this->validation->validateCompanyNumber($data[$fieldName]);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                } elseif ($fieldName === 'phone') {
                    $result = $this->validation->validatePhoneNumber($data[$fieldName]);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                } elseif ($fieldName === 'utr') {
                    $result = $this->validation->validateUTR($data[$fieldName]);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                } elseif (in_array($fieldName, ['sort_code', 'account_number'])) {
                    $sortCode = $data['sort_code'] ?? '';
                    $accountNumber = $data['account_number'] ?? '';
                    $result = $this->validation->validateSortCodeAccountNumber($sortCode, $accountNumber);
                    if (!$result['valid']) {
                        $errors[$fieldName] = $result['error'];
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function getDefaultSettings(): array
    {
        return [
            'defaultCurrency' => 'GBP',
            'currencySymbol' => '£',
            'currencySymbolPosition' => 'before',
            'currencySymbolSpace' => false,
            'decimalFormat' => '2',
            'decimalSeparator' => '.',
            'thousandsSeparator' => ',',
            'dateFormat' => 'DD/MM/YYYY',
            'timeFormat' => '24',
            'timezone' => 'Europe/London',
            'defaultLanguage' => 'en-GB',
            'layout_direction' => 'ltr',
            'fiscal_year_end' => '03-31',
        ];
    }

    public function getRequiredGovernmentIntegrations(): array
    {
        return [
            'HMRC_VAT' => [
                'name' => 'HMRC VAT (Making Tax Digital)',
                'description' => 'Submit VAT returns and manage your VAT account',
                'required_for_vat_registered' => true,
            ],
            'HMRC_RTI' => [
                'name' => 'HMRC RTI (Real Time Information)',
                'description' => 'Submit payroll information to HMRC',
                'required_for_employers' => true,
            ],
            'CompaniesHouse' => [
                'name' => 'Companies House',
                'description' => 'File confirmation statements and accounts',
                'required_for_limited_companies' => true,
            ],
        ];
    }

    public function getRecommendedPaymentGateways(): array
    {
        return [
            'stripe' => [
                'name' => 'Stripe',
                'description' => 'Recommended for card payments, subscriptions, and Apple Pay/Google Pay',
                'use_cases' => ['general', 'subscription', 'one_off'],
            ],
            'paypal' => [
                'name' => 'PayPal UK',
                'description' => 'Alternative payment method for UK customers',
                'use_cases' => ['general'],
            ],
            'bacs' => [
                'name' => 'BACS Bank Transfer',
                'description' => 'Traditional UK bank transfer (BACS)',
                'use_cases' => ['b2b', 'large_payments'],
            ],
        ];
    }
}
