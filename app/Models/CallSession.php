<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $call_sid
 * @property-read int|null $patient_id
 * @property-read string $phone_number
 * @property-read string $status
 * @property-read array|null $conversation_state
 * @property-read array|null $metadata
 * @property-read \Carbon\CarbonInterface $created_at
 * @property-read \Carbon\CarbonInterface $updated_at
 */
final class CallSession extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'call_sid',
        'patient_id',
        'phone_number',
        'status',
        'conversation_state',
        'metadata',
    ];

    /**
     * @var list<string>
     */
    protected $casts = [
        'id' => 'integer',
        'call_sid' => 'string',
        'patient_id' => 'integer',
        'phone_number' => 'string',
        'status' => 'string',
        'conversation_state' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
