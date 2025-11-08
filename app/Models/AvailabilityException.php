<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int|null $availability_rule_id
 * @property-read int $facility_id
 * @property-read int $doctor_id
 * @property-read CarbonInterface $start_at
 * @property-read CarbonInterface $end_at
 * @property-read string $type
 * @property-read array|null $meta
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AvailabilityException extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'availability_rule_id',
        'facility_id',
        'doctor_id',
        'start_at',
        'end_at',
        'type',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'availability_rule_id' => 'integer',
        'facility_id' => 'integer',
        'doctor_id' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'type' => 'string',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function availabilityRule(): BelongsTo
    {
        return $this->belongsTo(AvailabilityRule::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}

