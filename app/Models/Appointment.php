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
 * @property-read int $patient_id
 * @property-read int $facility_id
 * @property-read int $doctor_id
 * @property-read int $service_offering_id
 * @property-read int|null $availability_slot_id
 * @property-read int $service_workflow_id
 * @property-read CarbonInterface $start_at
 * @property-read CarbonInterface $end_at
 * @property-read string $status
 * @property-read string|null $notes
 * @property-read string|null $language
 * @property-read string|null $locale
 * @property-read array|null $meta
 * @property-read int|null $updated_by
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Appointment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'facility_id',
        'doctor_id',
        'service_offering_id',
        'availability_slot_id',
        'service_workflow_id',
        'start_at',
        'end_at',
        'status',
        'notes',
        'language',
        'locale',
        'meta',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'patient_id' => 'integer',
        'facility_id' => 'integer',
        'doctor_id' => 'integer',
        'service_offering_id' => 'integer',
        'availability_slot_id' => 'integer',
        'service_workflow_id' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'status' => 'string',
        'notes' => 'string',
        'language' => 'string',
        'locale' => 'string',
        'meta' => 'array',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

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

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class);
    }

    public function serviceWorkflow(): BelongsTo
    {
        return $this->belongsTo(ServiceWorkflow::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function appointmentSteps(): HasMany
    {
        return $this->hasMany(AppointmentStep::class);
    }
}

