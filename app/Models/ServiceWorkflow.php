<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $service_offering_id
 * @property-read int|null $insurance_plan_id
 * @property-read string $name
 * @property-read int $total_estimated_minutes
 * @property-read bool $active
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ServiceWorkflow extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_offering_id',
        'insurance_plan_id',
        'name',
        'total_estimated_minutes',
        'active',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'service_offering_id' => 'integer',
        'insurance_plan_id' => 'integer',
        'name' => 'string',
        'total_estimated_minutes' => 'integer',
        'active' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

