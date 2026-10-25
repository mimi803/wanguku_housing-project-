<?php

namespace App\Services;

class KenyanPhoneNumber
{
    public function normalize(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '254'.substr($digits, 1);
        } elseif ((str_starts_with($digits, '7') || str_starts_with($digits, '1')) && strlen($digits) === 9) {
            $digits = '254'.$digits;
        } elseif (str_starts_with($digits, '2540') && strlen($digits) === 13) {
            $digits = '254'.substr($digits, 4);
        }

        if (str_starts_with($digits, '254') && preg_match('/^254[17][0-9]{8}$/', $digits)) {
            return '+'.$digits;
        }

        return strlen($digits) === 12 && str_starts_with($digits, '254') && preg_match('/^254[17][0-9]{8}$/', $digits)
            ? '+'.$digits
            : null;
    }

    public function lookupVariants(string $phone): array
    {
        $normalized = $this->normalize($phone);
        if (! $normalized) return [];

        $digits = ltrim($normalized, '+');
        $variants = [$normalized, $digits, '+'.$digits];
        if (str_starts_with($digits, '254')) {
            $variants[] = '0'.substr($digits, 3);
        }

        return array_values(array_unique($variants));
    }
}
