<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read int|null $user_id
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $email
 * @property-read string|null $phone
 * @property-read string|null $dob
 * @property-read int|null $default_insurance_plan_id
 * @property-read string|null $preferred_language
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class Patient extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'default_insurance_plan_id',
        'preferred_language',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'dob' => 'date',
        'default_insurance_plan_id' => 'integer',
        'preferred_language' => 'string',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultInsurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'default_insurance_plan_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

