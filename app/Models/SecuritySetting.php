<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecuritySetting extends Model
{
    public const MAX_LOGIN_ATTEMPTS = 3;

    public const LOGIN_LOCK_SECONDS = 180;

    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    protected $fillable = [
        'rate_limit_status',
        'recaptcha_status',
        'recaptcha_site_key',
        'recaptcha_secret_key',
    ];

    protected $hidden = ['recaptcha_site_key', 'recaptcha_secret_key'];

    protected $casts = [
        'rate_limit_status' => 'boolean',
        'recaptcha_status' => 'boolean',
        'recaptcha_site_key' => 'encrypted',
        'recaptcha_secret_key' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'rate_limit_status' => false,
            'recaptcha_status' => false,
        ]);
    }

    public function isRecaptchaActive(): bool
    {
        return $this->recaptcha_status
            && filled($this->recaptcha_site_key)
            && filled($this->recaptcha_secret_key);
    }

    public function passesRecaptcha(?string $token, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::RECAPTCHA_VERIFY_URL, [
                'secret' => $this->recaptcha_secret_key,
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (ConnectionException $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->json('success') === true;
    }

    public function loginRetryAfter(string $ip): int
    {
        $key = $this->loginThrottleKey($ip);

        if (!$this->rate_limit_status || !RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            return 0;
        }

        return RateLimiter::availableIn($key);
    }

    public function registerFailedLogin(string $ip): void
    {
        if (!$this->rate_limit_status) {
            return;
        }

        $key = $this->loginThrottleKey($ip);

        RateLimiter::hit($key, self::LOGIN_LOCK_SECONDS);

        if (RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            // The limiter window starts at the first failure, so restart it to
            // keep the lock for the full duration after the last failed attempt.
            RateLimiter::clear($key);
            RateLimiter::increment($key, self::LOGIN_LOCK_SECONDS, self::MAX_LOGIN_ATTEMPTS);
        }
    }

    public function clearFailedLogins(string $ip): void
    {
        RateLimiter::clear($this->loginThrottleKey($ip));
    }

    private function loginThrottleKey(string $ip): string
    {
        return "admin-login|{$ip}";
    }
}
