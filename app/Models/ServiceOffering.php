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
 * @property-read int $service_id
 * @property-read int $doctor_id
 * @property-read int $facility_id
 * @property-read bool $active
 * @property-read int|null $default_duration_minutes
 * @property-read string $visibility
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ServiceOffering extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'doctor_id',
        'facility_id',
        'active',
        'default_duration_minutes',
        'visibility',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'service_id' => 'integer',
        'doctor_id' => 'integer',
        'facility_id' => 'integer',
        'active' => 'boolean',
        'default_duration_minutes' => 'integer',
        'visibility' => 'string',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceWorkflows(): HasMany
    {
        return $this->hasMany(ServiceWorkflow::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

