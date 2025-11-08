<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $insurance_provider_id
 * @property-read string $plan_code
 * @property-read string $name
 * @property-read string|null $effective_from
 * @property-read string|null $effective_to
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class InsurancePlan extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'insurance_provider_id',
        'plan_code',
        'name',
        'effective_from',
        'effective_to',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'insurance_provider_id' => 'integer',
        'plan_code' => 'string',
        'name' => 'string',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_insurance_plans')
            ->withPivot('enabled', 'notes', 'accepted_since')
            ->withTimestamps();
    }

    public function serviceWorkflows(): HasMany
    {
        return $this->hasMany(ServiceWorkflow::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'default_insurance_plan_id');
    }
}

