<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Pet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'profile_image',
        'gender',
        'species_id',
        'breed_id',
        'color',
        'date_of_birth',
        'sterilisation_status',
        'microchip_number',
        'length_cm',
        'height_cm',
        'weight_kg',
        'avs_license_number',
        'date_expiry',
        'document',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['evaluation_status'];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_expiry' => 'date',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst(strtolower($value));
    }

    public function getNameAttribute()
    {
        return ucfirst(strtolower($this->attributes['name']));
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class, 'breed_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vaccination_record()
    {
        return $this->hasMany(VaccinationRecord::class, 'pet_id','id');
    }

    public function blood_record()
    {
        return $this->hasMany(BloodTestRecord::class, 'pet_id','id');
    }

    public function deworming_record()
    {
        return $this->hasMany(DewormingRecord::class, 'pet_id','id');
    }

    public function medical_history_record()
    {
        return $this->hasMany(MedicalHistoryRecord::class, 'pet_id','id');
    }

    public function dietary_preferences()
    {
        return $this->hasMany(DietaryPreferences::class, 'pet_id','id');
    }

    public function medication_supplement()
    {
        return $this->hasMany(MedicationSupplement::class, 'pet_id','id');
    }

    public function temperamentHealthEvaluations()
    {
        return $this->hasMany(TemperamentHealthEvaluation::class, 'pet_id');
    }

    public function size_management()
    {
        return $this->hasMany(SizeManagement::class, 'pet_id');
    }

    // Accessor for age in years
    public function getAgeYearAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }

        $year = (int) Carbon::parse($this->date_of_birth)->diffInYears(Carbon::now());

        $yearLabel = (int) $year === 1 ? ' year' : ' years';

        return $year.$yearLabel;
    }

    // Accessor for remaining months
    public function getAgeMonthsAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }

        $dob = Carbon::parse($this->date_of_birth);
        $years = $dob->diffInYears(Carbon::now());

        $month = (int) $dob->copy()->addYears($years)->diffInMonths(Carbon::now());
        $monthLabel = (int) $month === 1 ? ' month' : ' months';
        return $month.$monthLabel;
    }

    public function getAvsLicenseExpiryAttribute()
    {
        $dateExpiry = Carbon::parse($this->date_expiry);
        if ($dateExpiry->isPast()) {
            return "false";
        } else {
            return "true";
        }
    }

    public function evaluations()
    {
        return $this->hasMany(TemperamentHealthEvaluation::class, 'pet_id');
    }

    public function getEvaluationStatusAttribute()
    {
        $latestEvaluation = $this->evaluations()
            ->orderBy('date', 'desc')->orderBy('created_at', 'desc')
            ->first();

        return $latestEvaluation ? $latestEvaluation->status : null;
    }
}
