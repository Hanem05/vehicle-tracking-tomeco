<?php

namespace Database\Seeders;

use App\Models\ViolationType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'No parking zone', 'default_penalty' => 0],
            ['name' => 'Blocking driveway', 'default_penalty' => 0],
            ['name' => 'Double parking', 'default_penalty' => 0],
            ['name' => 'Obstructing traffic', 'default_penalty' => 0],
            ['name' => 'Parking on sidewalk', 'default_penalty' => 0],
        ] as $type) {
            ViolationType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
