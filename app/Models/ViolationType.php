<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViolationType extends Model
{
    protected $fillable = ['name', 'default_penalty', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean', 'default_penalty' => 'decimal:2']; }
}
