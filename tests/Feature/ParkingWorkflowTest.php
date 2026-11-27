<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Services\TrendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParkingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_guests_are_redirected_and_roles_limit_changes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $viewer = $this->user('viewer');
        $this->actingAs($viewer)->get('/dashboard')->assertOk();
        $this->actingAs($viewer)->get('/vehicles/create')->assertForbidden();
        $this->actingAs($viewer)->get('/violations/create')->assertForbidden();
        $this->actingAs($viewer)->get('/users')->assertForbidden();
        $enforcer = $this->user('enforcer');
        $this->actingAs($enforcer)->get('/violations/create')->assertOk();
        $this->actingAs($enforcer)->get('/vehicles/create')->assertForbidden();
    }

    public function test_admin_can_register_and_tag_a_vehicle_and_enforcer_can_issue_a_ticket(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->post('/vehicles', [
            'plate_number' => 'abc 1234', 'owner_name' => 'Maria Cruz',
            'vehicle_type' => 'Car', 'owner_contact' => '09123456789',
        ])->assertRedirect();
        $vehicle = Vehicle::firstOrFail();
        $this->assertSame('ABC 1234', $vehicle->plate_number);
        $this->actingAs($admin)->get('/vehicles/'.$vehicle->id.'/qr')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        $type = ViolationType::create(['name' => 'No parking', 'default_penalty' => 500]);
        $enforcer = $this->user('enforcer');
        $this->actingAs($enforcer)->get('/scan/'.$vehicle->qr_token)->assertRedirect('/violations/create?vehicle='.$vehicle->id);
        $this->actingAs($enforcer)->post('/violations', [
            'vehicle_id' => $vehicle->id, 'violation_type_id' => $type->id,
            'location' => 'Downtown', 'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'penalty_amount' => 500,
        ])->assertRedirect();
        $ticket = Violation::firstOrFail();
        $this->assertSame('issued', $ticket->status);
        $this->actingAs($enforcer)->patch('/violations/'.$ticket->id.'/status', ['status' => 'paid'])->assertForbidden();
        $this->actingAs($admin)->patch('/violations/'.$ticket->id.'/status', ['status' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $ticket->fresh()->status);
        $this->actingAs($admin)->get('/reports')->assertOk()->assertSee('Peak hours')->assertSee('Vehicle types');
        $this->actingAs($admin)->get('/reports/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_photo_evidence_is_private_and_forecast_waits_for_enough_history(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin');
        $vehicle = Vehicle::create(['plate_number' => 'ABC-222', 'qr_token' => (string) \Illuminate\Support\Str::uuid(), 'owner_name' => 'Test Owner', 'vehicle_type' => 'Car']);
        $type = ViolationType::create(['name' => 'Double parking', 'default_penalty' => 500]);
        $this->assertNull(app(TrendService::class)->weekly()['forecast']);
        $image = UploadedFile::fake()->createWithContent('evidence.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/VYcAAAAASUVORK5CYII='));
        $this->actingAs($admin)->post('/violations', [
            'vehicle_id' => $vehicle->id, 'violation_type_id' => $type->id,
            'location' => 'Market road', 'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'penalty_amount' => 500, 'evidence' => $image,
        ])->assertRedirect();
        $ticket = Violation::firstOrFail();
        $this->assertNotNull($ticket->evidence_path);
        $this->get('/violations/'.$ticket->id.'/evidence')->assertOk();
        auth()->logout();
        $this->get('/violations/'.$ticket->id.'/evidence')->assertRedirect('/login');

        for ($week = 0; $week < 8; $week++) {
            for ($j = 0; $j < 3; $j++) {
                Violation::create([
                    'ticket_number' => "T-{$week}-{$j}", 'vehicle_id' => $vehicle->id,
                    'violation_type_id' => $type->id, 'location' => 'Market road',
                    'occurred_at' => now()->startOfWeek()->subWeeks($week)->addDay(),
                    'penalty_amount' => 500, 'status' => 'issued',
                ]);
            }
        }
        $this->assertCount(4, app(TrendService::class)->weekly()['forecast']);
    }
}
