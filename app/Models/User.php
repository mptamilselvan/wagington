<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use App\Services\ImageService;
use Illuminate\Support\Str;


/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     required={"id","country_code","phone"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="country_code", type="string", example="+65", description="Country code with + prefix"),
 *     @OA\Property(property="phone", type="string", example="87654321", description="Phone number without country code"),
 *     @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="passport_nric_fin_number", type="string", example="S1234567A"),
 *     @OA\Property(property="secondary_first_name", type="string", example="Jane"),
 *     @OA\Property(property="secondary_last_name", type="string", example="Smith"),
 *     @OA\Property(property="secondary_email", type="string", example="jane@example.com"),
 *     @OA\Property(property="secondary_phone", type="string", example="87654321"),
 *     @OA\Property(property="secondary_country_code", type="string", example="+65"),
 *     @OA\Property(property="image", type="string", example="profile.jpg"),
 *     @OA\Property(property="referal_code", type="string", example="REF123"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="phone_verified_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(property="secondary_email_verified_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(property="secondary_phone_verified_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T10:30:00Z"),
 *     @OA\Property(
 *         property="addresses",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="address_type_id", type="integer", example=1),
 *             @OA\Property(property="label", type="string", example="Home"),
 *             @OA\Property(property="country", type="string", example="SG"),
 *             @OA\Property(property="postal_code", type="string", example="123456"),
 *             @OA\Property(property="address_line_1", type="string", example="123 Main Street"),
 *             @OA\Property(property="address_line_2", type="string", example="Apt 4B"),
 *             @OA\Property(property="is_billing_address", type="boolean", example=true),
 *             @OA\Property(property="is_shipping_address", type="boolean", example=false),
 *             @OA\Property(property="full_address", type="string", example="123 Main Street, Apt 4B, SG 123456")
 *         )
 *     )
 * )
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($user) {
            // When soft-deleting a user, also soft-delete related addresses
            if (method_exists($user, 'isForceDeleting') && $user->isForceDeleting()) {
                // If force deleting, hard delete addresses as well
                $user->addresses()->withTrashed()->get()->each->forceDelete();
            } else {
                // Soft-delete related addresses
                $user->addresses()->get()->each->delete();
            }
        });

        static::restoring(function ($user) {
            // Restore related addresses when restoring user
            $user->addresses()->withTrashed()->get()->each->restore();
        });

        static::creating(function ($user) {
            if (empty($user->referal_code)) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (User::where('referal_code', $code)->exists());

                $user->referal_code = $code;
            }
        });
    }

    protected $fillable = [
        'name',
        'phone', 'phone_verified_at', 'country_code',
        'email', 'email_verified_at',
        'first_name', 'last_name',
        'dob', 'passport_nric_fin_number',
        'image',
        'secondary_first_name', 'secondary_last_name',
        'secondary_email', 'secondary_phone', 'secondary_country_code',
        'secondary_email_verified_at', 'secondary_phone_verified_at',
        'is_active','referal_code',
        'created_by', 'updated_by', 'stripe_customer_id','referred_by_id'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'secondary_email_verified_at' => 'datetime',
        'secondary_phone_verified_at' => 'datetime',
        'dob' => 'date',
        'is_active' => 'boolean',
    ];

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_id')->where('phone_verified_at','!=',null);
    }

    public function pet()
    {
        return $this->hasMany(Pet::class, 'user_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'roles' => $this->getRoleNames()->toArray(), // Get user roles
            // 'permissions' => $this->getAllPermissions()->pluck('name')->toArray(), // Get user permissions
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function getBillingAddressAttribute()
    {
        return $this->addresses()->where('is_billing_address', true)->first();
    }

    public function getShippingAddressAttribute()
    {
        return $this->addresses()->where('is_shipping_address', true)->first();
    }

    public function getFullPhoneAttribute()
    {
        return $this->country_code . $this->phone;
    }

    public function getFullSecondaryPhoneAttribute()
    {
        if ($this->secondary_phone && $this->secondary_country_code) {
            return $this->secondary_country_code . $this->secondary_phone;
        }
        return null;
    }

    /**
     * Check if email is verified (accessor for convenience)
     *
     * @return bool
     */
    public function getIsEmailVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if phone is verified (accessor for convenience)
     *
     * @return bool
     */
    public function getIsPhoneVerifiedAttribute(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Check if customer is truly active (combines is_active with verification status)
     * A customer is considered active when:
     * - is_active is true
     * - email is verified
     * - phone is verified
     *
     * @return bool
     */
    public function getIsCustomerActiveAttribute(): bool
    {
        return $this->is_active && $this->is_email_verified && $this->is_phone_verified;
    }

    /**
     * Mark email as verified
     *
     * @return void
     */
    public function markEmailAsVerified(): void
    {
        $this->email_verified_at = now();
        $this->save();
    }

    /**
     * Mark phone as verified
     *
     * @return void
     */
    public function markPhoneAsVerified(): void
    {
        $this->phone_verified_at = now();
        $this->save();
    }

    /**
     * Get the profile image URL or initials data
     *
     * @return string|array
     */
    public function getProfileImageUrlAttribute()
    {
        return ImageService::getImageUrl($this->image, null, $this->first_name, $this->last_name);
    }

    /**
     * Get the profile picture URL (alias for profile_image_url)
     *
     * @return string|array
     */
    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_image_url;
    }

    /**
     * Get user initials
     *
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        return ImageService::generateInitials($this->first_name, $this->last_name);
    }

    /**
     * Get user avatar color
     *
     * @return string
     */
    public function getAvatarColorAttribute(): string
    {
        return ImageService::generateColorFromName($this->first_name, $this->last_name);
    }

    /**
     * Get user initials as a method (for compatibility)
     *
     * @return string
     */
    public function initials(): string
    {
        return $this->initials;
    }

    /**
     * Get user's full name
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
       if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        // Fallback to the DB column 'name'
        if (!empty($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        // Final fallback: use email
        return $this->email ?? '';
    }

    /**
     * Check if user profile is complete
     *
     * @return bool
     */
    public function isProfileComplete(): bool
    {
        // Define required fields for a complete profile
        $requiredFields = [
            'name',
            'first_name',
            'last_name', 
            'email',
            'phone',
            'country_code'
        ];

        // Check if all required fields are filled
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // Check if email and phone are verified
        if (is_null($this->email_verified_at) || is_null($this->phone_verified_at)) {
            return false;
        }

        return true;
    }
}