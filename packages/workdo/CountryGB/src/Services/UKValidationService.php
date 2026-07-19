<?php

namespace Workdo\CountryGB\Services;

class UKValidationService
{
    public function validatePostcode(string $postcode): array
    {
        $postcode = strtoupper(preg_replace('/\s+/', '', $postcode));

        $pattern = '/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/';

        $valid = preg_match($pattern, $postcode) === 1;

        return [
            'valid' => $valid,
            'formatted' => $valid ? $this->formatPostcode($postcode) : null,
            'error' => $valid ? null : 'Invalid UK postcode format',
        ];
    }

    private function formatPostcode(string $postcode): string
    {
        if (strlen($postcode) <= 4) {
            return $postcode;
        }

        $inward = substr($postcode, -3);
        $outward = substr($postcode, 0, -3);

        return $outward . ' ' . $inward;
    }

    public function validateVATNumber(string $vatNumber): array
    {
        $vatNumber = str_replace([' ', '-', '.'], '', strtoupper($vatNumber));

        if (!str_starts_with($vatNumber, 'GB')) {
            $vatNumber = 'GB' . $vatNumber;
        }

        $pattern = '/^GB[0-9]{9}$/';

        if (!preg_match($pattern, $vatNumber)) {
            return [
                'valid' => false,
                'error' => 'Invalid UK VAT number format. Expected: GB123456789',
            ];
        }

        return [
            'valid' => true,
            'formatted' => substr($vatNumber, 2) . ' ' . substr($vatNumber, 2, 3),
            'full' => $vatNumber,
        ];
    }

    public function validateCompanyNumber(string $companyNumber): array
    {
        $companyNumber = strtoupper(preg_replace('/\s+/', '', $companyNumber));

        $pattern = '/^[A-Z]{2}[0-9]{6}$/';

        if (!preg_match($pattern, $companyNumber)) {
            return [
                'valid' => false,
                'error' => 'Invalid UK company number format. Expected: AB123456',
            ];
        }

        return [
            'valid' => true,
            'formatted' => $companyNumber,
        ];
    }

    public function validatePhoneNumber(string $phone): array
    {
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        if (str_starts_with($phone, '+44')) {
            $phone = '0' . substr($phone, 3);
        }

        if (str_starts_with($phone, '44')) {
            $phone = '0' . substr($phone, 2);
        }

        $pattern = '/^0[1-9][0-9]{8,9}$/';

        $valid = preg_match($pattern, $phone) === 1;

        if ($valid) {
            $formatted = $this->formatUKPhone($phone);
        } else {
            $formatted = null;
        }

        return [
            'valid' => $valid,
            'formatted' => $formatted,
            'error' => $valid ? null : 'Invalid UK phone number format',
        ];
    }

    private function formatUKPhone(string $phone): string
    {
        if (strlen($phone) === 11 && str_starts_with($phone, '07')) {
            return preg_replace('/(07\d{4})(\d{6})/', '+44 $1 $2', $phone);
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '01') || str_starts_with($phone, '02')) {
            return preg_replace('/(01\d{1,2})(\d{3})(\d{4})/', '+44 $1 $2 $3', $phone);
        }

        return '+44 ' . $phone;
    }

    public function validateSortCodeAccountNumber(string $sortCode, string $accountNumber): array
    {
        $sortCode = preg_replace('/[\s\-]+/', '', $sortCode);
        $accountNumber = preg_replace('/[\s\-]+/', '', $accountNumber);

        if (strlen($sortCode) === 5) {
            $sortCode = substr($sortCode, 0, 2) . '-' . substr($sortCode, 2, 3);
        }

        $sortPattern = '/^\d{2}-\d{2}-\d{2}$/';
        $accountPattern = '/^\d{8}$/';

        $sortValid = preg_match($sortPattern, $sortCode) === 1;
        $accountValid = preg_match($accountPattern, $accountNumber) === 1;

        if (!$sortValid) {
            return [
                'valid' => false,
                'error' => 'Invalid UK sort code format. Expected: 12-34-56',
            ];
        }

        if (!$accountValid) {
            return [
                'valid' => false,
                'error' => 'Invalid UK account number format. Expected: 12345678',
            ];
        }

        return [
            'valid' => true,
            'sort_code' => $sortCode,
            'account_number' => $accountNumber,
        ];
    }

    public function validateUTR(string $utr): array
    {
        $utr = preg_replace('/\s+/', '', $utr);

        $pattern = '/^[0-9]{10}$/';

        $valid = preg_match($pattern, $utr) === 1;

        return [
            'valid' => $valid,
            'formatted' => $valid ? $this->formatUTR($utr) : null,
            'error' => $valid ? null : 'Invalid UTR format. Expected: 10 digits',
        ];
    }

    private function formatUTR(string $utr): string
    {
        return substr($utr, 0, 5) . ' ' . substr($utr, 5, 5);
    }

    public function validateNINO(string $nino): array
    {
        $nino = strtoupper(preg_replace('/\s+/', '', $nino));

        $pattern = '/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-DFM]{0,1}$/';

        $valid = preg_match($pattern, $nino) === 1;

        return [
            'valid' => $valid,
            'formatted' => $valid ? $this->formatNINO($nino) : null,
            'error' => $valid ? null : 'Invalid National Insurance number format',
        ];
    }

    private function formatNINO(string $nino): string
    {
        if (strlen($nino) === 9) {
            return substr($nino, 0, 2) . ' ' . substr($nino, 2, 2) . ' ' . substr($nino, 4, 4);
        }

        return substr($nino, 0, 2) . ' ' . substr($nino, 2, 6) . ' ' . substr($nino, 8, 1);
    }

    public function validateCISNumber(string $cisNumber): array
    {
        $cisNumber = strtoupper(preg_replace('/\s+/', '', $cisNumber));

        $pattern = '/^[0-9]{9}[A-Z]{2}$/';

        $valid = preg_match($pattern, $cisNumber) === 1;

        return [
            'valid' => $valid,
            'formatted' => $valid ? $cisNumber : null,
            'error' => $valid ? null : 'Invalid CIS contractor number format',
        ];
    }
}
