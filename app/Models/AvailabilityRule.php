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
 * @property-read int $doctor_id
 * @property-read int $facility_id
 * @property-read int|null $service_offering_id
 * @property-read int $day_of_week
 * @property-read string $start_time
 * @property-read string $end_time
 * @property-read int $slot_duration_minutes
 * @property-read int|null $slot_interval_minutes
 * @property-read bool $active
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AvailabilityRule extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'doctor_id',
        'facility_id',
        'service_offering_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'slot_interval_minutes',
        'active',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'doctor_id' => 'integer',
        'facility_id' => 'integer',
        'service_offering_id' => 'integer',
        'day_of_week' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
        'slot_duration_minutes' => 'integer',
        'slot_interval_minutes' => 'integer',
        'active' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'created_from_rule_id');
    }

    /**
     * Scope a query to only include active availability rules.
     * This is used when generating availability slots to only process rules that are currently active.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

