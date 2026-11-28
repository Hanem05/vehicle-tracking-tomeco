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

        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('enforcer.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get('/users/create')->assertOk()->assertSee('Enforcer');
        $this->actingAs($admin)->post('/users', [
            'name' => 'New Enforcer',
            'email' => 'new.enforcer@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => 'enforcer',
        ])->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new.enforcer@example.test', 'role' => 'enforcer', 'is_active' => true]);

        $enforcer = $this->user('enforcer');
        $this->actingAs($enforcer)->get('/dashboard')->assertRedirect(route('enforcer.dashboard'));
        $this->actingAs($enforcer)->get(route('enforcer.dashboard'))->assertOk()->assertSee('Enforcer workspace');
        $this->actingAs($enforcer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($enforcer)->get('/reports')->assertForbidden();
        $this->actingAs($enforcer)->get('/violations')->assertForbidden();
        $this->actingAs($enforcer)->get('/users')->assertForbidden();
        $this->actingAs($enforcer)->post('/users', [
            'name' => 'Unauthorized Account',
            'email' => 'unauthorized@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => 'admin',
        ])->assertForbidden();
        $this->actingAs($enforcer)->get('/vehicles/create')->assertRedirect(route('vehicles.index'));
        $this->actingAs($enforcer)->get('/vehicles/create?plate=NEW-123')->assertOk();
        $this->actingAs($enforcer)->get('/violations/create')->assertOk();

        $viewer = $this->user('viewer');
        $this->actingAs($viewer)->get('/dashboard')->assertForbidden();
        $this->actingAs($viewer)->get('/vehicles/create')->assertForbidden();
        $this->actingAs($viewer)->get('/violations/create')->assertForbidden();
        $this->actingAs($viewer)->get('/users')->assertForbidden();

        $this->post('/logout')->assertRedirect('/login');
        $legacyViewer = $this->user('viewer');
        $this->post('/login', ['email' => $legacyViewer->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $enforcer->email, 'password' => 'password'])
            ->assertRedirect(route('enforcer.dashboard'));
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
        $this->actingAs($admin)->get('/vehicles/'.$vehicle->id.'/qr-card')
            ->assertOk()
            ->assertSee('Vehicle QR Card')
            ->assertSee('Print QR card')
            ->assertSee('ABC 1234')
            ->assertSee('Maria Cruz');

        $type = ViolationType::create(['name' => 'No parking', 'default_penalty' => 500]);
        $enforcer = $this->user('enforcer');
        $this->actingAs($enforcer)->get('/vehicles?search=ABC%201234')
            ->assertOk()
            ->assertSee('ABC 1234')
            ->assertDontSee('Register this vehicle');
        $this->actingAs($enforcer)->get('/vehicles/create?plate=ABC%201234')
            ->assertRedirect(route('vehicles.show', $vehicle));
        $this->actingAs($enforcer)->post('/vehicles', [
            'plate_number' => 'abc 1234', 'searched_plate' => 'ABC 1234',
            'owner_name' => 'Duplicate Owner', 'vehicle_type' => 'Car',
        ])->assertSessionHasErrors('plate_number');
        $this->assertDatabaseCount('vehicles', 1);

        $this->actingAs($enforcer)->get('/vehicles?search=NEW-123')
            ->assertOk()
            ->assertSee('Register this vehicle');
        $this->actingAs($enforcer)->post('/vehicles', [
            'plate_number' => 'new-123', 'searched_plate' => 'NEW-123',
            'owner_name' => 'New Owner', 'vehicle_type' => 'Motorcycle',
        ])->assertRedirect();
        $this->assertDatabaseCount('vehicles', 2);

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
        $this->assertDatabaseCount('activity_logs', 4);
    }

    public function test_driver_can_scan_a_vehicle_tag_and_only_sees_the_ticket_summary(): void
    {
        $vehicle = Vehicle::create([
            'plate_number' => 'XYZ-456',
            'qr_token' => (string) \Illuminate\Support\Str::uuid(),
            'owner_name' => 'Private Owner Name',
            'owner_contact' => '09123456789',
            'vehicle_type' => 'Car',
        ]);
        $type = ViolationType::create(['name' => 'No parking', 'default_penalty' => 450]);
        Violation::create([
            'ticket_number' => 'TOMECO-DRIVER-001',
            'vehicle_id' => $vehicle->id,
            'violation_type_id' => $type->id,
            'location' => 'Downtown',
            'occurred_at' => now()->subDay(),
            'description' => 'STAFF ONLY DESCRIPTION',
            'driver_name' => 'Private Driver Name',
            'driver_license_number' => 'PRIVATE-LICENSE-123',
            'status_note' => 'STAFF ONLY NOTE',
            'penalty_amount' => 450,
            'status' => 'issued',
        ]);
        Violation::create([
            'ticket_number' => 'TOMECO-DRIVER-002',
            'vehicle_id' => $vehicle->id,
            'violation_type_id' => $type->id,
            'location' => 'Downtown',
            'occurred_at' => now()->subDays(3),
            'penalty_amount' => 200,
            'status' => 'paid',
        ]);

        $this->get('/driver/scan')
            ->assertOk()
            ->assertSee('Check tickets and fees')
            ->assertSee('start-scanner')
            ->assertSee('driver-qr-image')
            ->assertSee('Upload a QR code image');
        $this->get('/driver/lookup?code='.$vehicle->qr_token)->assertRedirect('/scan/'.$vehicle->qr_token);
        $this->get('/scan/'.$vehicle->qr_token)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('XYZ-456')
            ->assertSee('No parking')
            ->assertSee('₱450.00')
            ->assertSee('TOMECO-DRIVER-002')
            ->assertDontSee('Private Owner Name')
            ->assertDontSee('Private Driver Name')
            ->assertDontSee('PRIVATE-LICENSE-123')
            ->assertDontSee('STAFF ONLY DESCRIPTION')
            ->assertDontSee('STAFF ONLY NOTE');
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
