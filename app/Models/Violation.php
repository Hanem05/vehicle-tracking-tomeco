<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    protected $fillable = ['ticket_number', 'vehicle_id', 'violation_type_id', 'location', 'occurred_at', 'description', 'penalty_amount', 'status', 'evidence_path', 'status_note', 'resolved_at', 'created_by', 'updated_by'];
    protected function casts(): array { return ['occurred_at' => 'datetime', 'resolved_at' => 'datetime', 'penalty_amount' => 'decimal:2']; }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function type(): BelongsTo { return $this->belongsTo(ViolationType::class, 'violation_type_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
