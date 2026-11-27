<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'details', 'ip_address'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
