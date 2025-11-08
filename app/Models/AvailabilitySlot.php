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
 * @property-read int $facility_id
 * @property-read int $doctor_id
 * @property-read int|null $service_offering_id
 * @property-read CarbonInterface $start_at
 * @property-read CarbonInterface $end_at
 * @property-read string $status
 * @property-read int $capacity
 * @property-read CarbonInterface|null $reserved_until
 * @property-read int|null $created_from_rule_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AvailabilitySlot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'doctor_id',
        'service_offering_id',
        'start_at',
        'end_at',
        'status',
        'capacity',
        'reserved_until',
        'created_from_rule_id',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'facility_id' => 'integer',
        'doctor_id' => 'integer',
        'service_offering_id' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'status' => 'string',
        'capacity' => 'integer',
        'reserved_until' => 'datetime',
        'created_from_rule_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function serviceOffering(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class);
    }

    public function createdFromRule(): BelongsTo
    {
        return $this->belongsTo(AvailabilityRule::class, 'created_from_rule_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

