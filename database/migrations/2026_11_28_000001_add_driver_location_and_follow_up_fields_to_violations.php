<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->string('driver_name')->nullable();
            $table->text('driver_license_number')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('notice_served_on')->nullable()->index();
            $table->date('appeared_on')->nullable();
            $table->date('subpoena_action_on')->nullable();
            $table->string('subpoena_reference', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropIndex(['notice_served_on']);
            $table->dropColumn([
                'driver_name',
                'driver_license_number',
                'latitude',
                'longitude',
                'notice_served_on',
                'appeared_on',
                'subpoena_action_on',
                'subpoena_reference',
            ]);
        });
    }
};
