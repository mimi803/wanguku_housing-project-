<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PhoneVerificationCodes
{
    public function __construct(private readonly AfricasTalkingSms $sms) {}

    public function send(string $phone, string $purpose): void
    {
        $sendKey = 'phone-code-send:'.hash('sha256', $phone);
        if (RateLimiter::tooManyAttempts($sendKey, 3)) {
            throw new \RuntimeException('Too many code requests. Please wait before trying again.');
        }

        if (! in_array($purpose, ['register', 'login', 'change_phone'], true)) {
            throw new \InvalidArgumentException('Unsupported phone verification purpose.');
        }

        $code = (string) random_int(100000, 999999);
        $purposeText = match ($purpose) {
            'register' => 'finish creating your account',
            'change_phone' => 'verify your new phone number',
            default => 'sign in',
        };
        $this->sms->send($phone, "Farmers Connect code: {$code}. Use it to {$purposeText}. Expires in 10 minutes.");

        Cache::put($this->cacheKey($phone), [
            'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
            'purpose' => $purpose,
            'attempts' => 0,
        ], now()->addMinutes(10));
        RateLimiter::hit($sendKey, 1800);
    }

    public function verify(string $phone, string $purpose, string $code): bool
    {
        $key = $this->cacheKey($phone);
        $entry = Cache::get($key);
        if (! is_array($entry) || ($entry['purpose'] ?? null) !== $purpose) return false;

        $attempts = (int) ($entry['attempts'] ?? 0) + 1;
        $valid = hash_equals((string) ($entry['hash'] ?? ''), hash_hmac('sha256', $code, (string) config('app.key')));
        if ($valid) {
            Cache::forget($key);
            RateLimiter::clear('phone-code-send:'.hash('sha256', $phone));
            return true;
        }

        if ($attempts >= 5) {
            Cache::forget($key);
            return false;
        }

        $entry['attempts'] = $attempts;
        Cache::put($key, $entry, now()->addMinutes(10));
        return false;
    }

    private function cacheKey(string $phone): string
    {
        return 'phone-code:'.hash('sha256', $phone);
    }
}
