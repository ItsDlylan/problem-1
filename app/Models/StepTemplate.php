<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int $default_duration_minutes
 * @property-read bool $requires_location
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class StepTemplate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'default_duration_minutes',
        'requires_location',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'default_duration_minutes' => 'integer',
        'requires_location' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function appointmentSteps(): HasMany
    {
        return $this->hasMany(AppointmentStep::class);
    }
}

