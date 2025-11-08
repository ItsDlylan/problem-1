<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $appointment_id
 * @property-read int $workflow_step_id
 * @property-read int $step_template_id
 * @property-read int $position
 * @property-read CarbonInterface $scheduled_start_at
 * @property-read CarbonInterface $scheduled_end_at
 * @property-read string $status
 * @property-read string|null $notes
 * @property-read string|null $location
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AppointmentStep extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'appointment_id',
        'workflow_step_id',
        'step_template_id',
        'position',
        'scheduled_start_at',
        'scheduled_end_at',
        'status',
        'notes',
        'location',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'workflow_step_id' => 'integer',
        'step_template_id' => 'integer',
        'position' => 'integer',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'status' => 'string',
        'notes' => 'string',
        'location' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(StepTemplate::class);
    }
}

