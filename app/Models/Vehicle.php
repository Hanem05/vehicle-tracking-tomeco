<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['plate_number', 'qr_token', 'owner_name', 'owner_contact', 'vehicle_type', 'make_model', 'color', 'registered_by'];

    public function violations(): HasMany { return $this->hasMany(Violation::class); }
    public function registrar(): BelongsTo { return $this->belongsTo(User::class, 'registered_by'); }
}
