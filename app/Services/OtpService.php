<?php

namespace App\Services;

use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\OtpMail;
use Exception;

class OtpService
{
    // Constants for better maintainability
    private const OTP_LENGTH = 6;
    private const OTP_MIN = 100000;
    private const OTP_MAX = 999999;
    private const RATE_LIMIT_SECONDS = 60;
    private const OTP_EXPIRY_MINUTES = 5;
    
    // Supported OTP types
    private const TYPE_PHONE = 'phone';
    private const TYPE_EMAIL = 'email';
    
    // Cache key prefixes
    private const CACHE_PREFIX_OTP = 'otp_';
    private const CACHE_PREFIX_RATE_LIMIT = 'otp_sent_';

    protected $twilio;

    public function __construct()
    {
        $this->initializeTwilio();
    }

    /**
     * Initialize Twilio client if credentials are available
     */
    private function initializeTwilio(): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        
        if ($sid && $token) {
            $this->twilio = new TwilioClient($sid, $token);
        }
    }

    /**
     * Send OTP to phone number or email address
     *
     * @param string $to The phone number or email address
     * @param string $type Either 'phone' or 'email'
     * @return bool
     * @throws ValidationException When rate limited
     * @throws Exception When sending fails
     */
    public function sendOtp(string $to, string $type): bool
    {
        $this->validateOtpType($type);
        $this->enforceRateLimit($to, $type);
        
        $otp = $this->generateOtp();
        $this->cacheOtp($to, $type, $otp);
        $this->sendOtpMessage($to, $type, $otp);
        
        return true;
    }

    /**
     * Validate OTP type
     */
    private function validateOtpType(string $type): void
    {
        if (!in_array($type, [self::TYPE_PHONE, self::TYPE_EMAIL], true)) {
            throw new Exception("Invalid OTP type: {$type}");
        }
    }

    /**
     * Enforce rate limiting for OTP requests
     */
    private function enforceRateLimit(string $to, string $type): void
    {
        $rateLimitKey = $this->getRateLimitKey($to, $type);
        
        if (Cache::has($rateLimitKey)) {
            $displayType = $type === self::TYPE_PHONE ? 'mobile' : $type;
            throw ValidationException::withMessages([
                $type => ["OTP was already sent to this {$displayType}. Please wait " . self::RATE_LIMIT_SECONDS . " seconds before requesting another OTP."]
            ]);
        }
        
        Cache::put($rateLimitKey, true, now()->addSeconds(self::RATE_LIMIT_SECONDS));
    }

    /**
     * Generate a random OTP
     */
    private function generateOtp(): string
    {
        return (string) rand(self::OTP_MIN, self::OTP_MAX);
    }

    /**
     * Cache the OTP for verification
     */
    private function cacheOtp(string $to, string $type, string $otp): void
    {
        $otpKey = $this->getOtpKey($to, $type);
        $expiryTime = now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        
        Cache::put($otpKey, $otp, $expiryTime);
        
        // Debug logging
        \Log::info('OtpService: OTP cached', [
            'to' => $to,
            'type' => $type,
            'otpKey' => $otpKey,
            'otp' => $otp,
            'expiryTime' => $expiryTime->toDateTimeString(),
            'cached_value_check' => Cache::get($otpKey)
        ]);
    }

    /**
     * Send OTP message via appropriate channel
     */
    private function sendOtpMessage(string $to, string $type, string $otp): void
    {
        try {
            if ($type === self::TYPE_PHONE) {
                $this->sendSmsOtp($to, $otp);
            } else {
                $this->sendEmailOtpMessage($to, $otp);
            }
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Send SMS OTP via Twilio
     */
    private function sendSmsOtp(string $phoneNumber, string $otp): void
    { 
        if (!$this->twilio || !config('services.twilio.from')) {
            throw new Exception("Twilio is not properly configured");
        }

        $this->twilio->messages->create($phoneNumber, [
            'from' => config('services.twilio.from'),
            'body' => "Your OTP is {$otp}"
        ]);
    }

    /**
     * Send email OTP message
     */
    private function sendEmailOtpMessage(string $email, string $otp): void
    {
        // Mail::raw("Your OTP is {$otp}", function ($message) use ($email) {
        //     $message->to($email)->subject('Your Verification OTP');
        // });
        Mail::to($email)->send(new \App\Mail\OtpMail($otp, self::OTP_EXPIRY_MINUTES));
    }

    /**
     * Get OTP cache key
     */
    private function getOtpKey(string $to, string $type): string
    {
        return self::CACHE_PREFIX_OTP . "{$type}_{$to}";
    }

    /**
     * Get rate limit cache key
     */
    private function getRateLimitKey(string $to, string $type): string
    {
        return self::CACHE_PREFIX_RATE_LIMIT . "{$type}_{$to}";
    }

    /**
     * Verify OTP against cached value
     *
     * @param string $to phone or email
     * @param string $enteredOtp
     * @param string $type 'phone' or 'email'
     * @return string 'success'|'expired'|'mismatch'
     */
    public function verifyOtp(string $to, string $enteredOtp, string $type): string
    {
        $otpKey = $this->getOtpKey($to, $type);
        $cachedOtp = Cache::get($otpKey);

        // Debug logging
        \Log::info('OtpService: verifyOtp called', [
            'to' => $to,
            'enteredOtp' => $enteredOtp,
            'type' => $type,
            'otpKey' => $otpKey,
            'cachedOtp' => $cachedOtp ? 'exists' : 'null'
        ]);

        if (!$cachedOtp) {
            \Log::warning('OtpService: OTP expired or not found', ['otpKey' => $otpKey]);
            return 'expired';
        }

        if ($cachedOtp === $enteredOtp) {
            Cache::forget($otpKey);
            \Log::info('OtpService: OTP verification successful', ['otpKey' => $otpKey]);
            return 'success';
        }

        \Log::warning('OtpService: OTP mismatch', [
            'otpKey' => $otpKey,
            'cachedOtp' => $cachedOtp,
            'enteredOtp' => $enteredOtp
        ]);
        return 'mismatch';
    }

    /**
     * Send email OTP with user ID context
     * @deprecated Use sendOtp() directly instead
     */
    public function sendEmailOtp(string $email, int $userId): bool
    {
        return $this->sendOtp($email, self::TYPE_EMAIL);
    }

    /**
     * Send phone OTP with user ID context
     * @deprecated Use sendOtp() directly instead
     */
    public function sendPhoneOtp(string $phone, int $userId): bool
    {
        return $this->sendOtp($phone, self::TYPE_PHONE);
    }

    /**
     * Verify email OTP with user ID context
     * @deprecated Use verifyOtp() directly instead
     */
    public function verifyEmailOtp(string $email, string $otp, int $userId): bool
    {
        $result = $this->verifyOtp($email, $otp, self::TYPE_EMAIL);
        return $result === 'success';
    }

    /**
     * Verify phone OTP with user ID context
     * @deprecated Use verifyOtp() directly instead
     */
    public function verifyPhoneOtp(string $phone, string $otp, int $userId): bool
    {
        $result = $this->verifyOtp($phone, $otp, self::TYPE_PHONE);
        return $result === 'success';
    }

    /**
     * Check if OTP can be sent (not rate limited)
     */
    public function canSendOtp(string $to, string $type): bool
    {
        $rateLimitKey = $this->getRateLimitKey($to, $type);
        return !Cache::has($rateLimitKey);
    }
}