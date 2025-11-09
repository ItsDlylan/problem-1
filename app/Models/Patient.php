<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read int $id
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string|null $phone
 * @property-read string|null $dob
 * @property-read int|null $default_insurance_plan_id
 * @property-read string|null $insurance_card_number
 * @property-read string|null $preferred_language
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class Patient extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['name'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'remember_token',
        'phone',
        'dob',
        'default_insurance_plan_id',
        'insurance_card_number',
        'preferred_language',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'password' => 'hashed',
        'remember_token' => 'string',
        'email_verified_at' => 'datetime',
        'phone' => 'string',
        'dob' => 'date',
        'default_insurance_plan_id' => 'integer',
        'insurance_card_number' => 'string',
        'preferred_language' => 'string',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function defaultInsurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'default_insurance_plan_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the patient's full name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

