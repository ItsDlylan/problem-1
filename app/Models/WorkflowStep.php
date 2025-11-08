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
 * @property-read int $service_workflow_id
 * @property-read int $step_template_id
 * @property-read int $position
 * @property-read int|null $duration_minutes
 * @property-read string|null $location_type
 * @property-read bool $requires_preparation
 * @property-read bool $can_be_skipped
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class WorkflowStep extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_workflow_id',
        'step_template_id',
        'position',
        'duration_minutes',
        'location_type',
        'requires_preparation',
        'can_be_skipped',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'service_workflow_id' => 'integer',
        'step_template_id' => 'integer',
        'position' => 'integer',
        'duration_minutes' => 'integer',
        'location_type' => 'string',
        'requires_preparation' => 'boolean',
        'can_be_skipped' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function serviceWorkflow(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkflow::class);
    }

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(StepTemplate::class);
    }

    public function appointmentSteps(): HasMany
    {
        return $this->hasMany(AppointmentStep::class);
    }
}

