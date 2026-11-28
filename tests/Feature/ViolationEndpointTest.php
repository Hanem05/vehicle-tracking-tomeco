<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViolationEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-28 12:00:00');
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enforcer_can_submit_a_ticket_and_the_license_is_encrypted_at_rest(): void
    {
        $enforcer = $this->user('enforcer');
        $vehicle = $this->vehicle();
        $type = $this->violationType();

        $response = $this->actingAs($enforcer)->post(route('violations.store'), [
            'vehicle_id' => $vehicle->id,
            'violation_type_id' => $type->id,
            'location' => 'Downtown Tacloban',
            'latitude' => 11.2445,
            'longitude' => 125.0032,
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'driver_name' => 'Maria Cruz',
            'driver_license_number' => 'D1234567',
            'notice_served_on' => '2026-11-28',
            'penalty_amount' => 500,
        ]);

        $violation = Violation::firstOrFail();
        $response->assertRedirect(route('violations.show', $violation));
        $this->assertSame('issued', $violation->status);
        $this->assertSame('Maria Cruz', $violation->driver_name);
        $this->assertSame('D1234567', $violation->driver_license_number);
        $this->assertSame('2026-11-28', $violation->notice_served_on->toDateString());
        $this->assertEqualsWithDelta(11.2445, $violation->latitude, 0.00001);
        $this->assertEqualsWithDelta(125.0032, $violation->longitude, 0.00001);

        $storedLicense = DB::table('violations')->where('id', $violation->id)->value('driver_license_number');
        $this->assertNotSame('D1234567', $storedLicense);
        $this->assertSame('D1234567', $violation->fresh()->driver_license_number);

        $this->get(route('violations.show', $violation))->assertOk()->assertSee('D1234567');
        $this->actingAs($this->user('viewer'))
            ->get(route('violations.show', $violation))
            ->assertForbidden();
    }

    public function test_ticket_endpoint_rejects_unpaired_map_coordinates_and_invalid_license_data(): void
    {
        $enforcer = $this->user('enforcer');
        $vehicle = $this->vehicle();
        $type = $this->violationType();
        $valid = [
            'vehicle_id' => $vehicle->id,
            'violation_type_id' => $type->id,
            'location' => 'Market road',
            'occurred_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'penalty_amount' => 500,
        ];

        $this->actingAs($enforcer)->post(route('violations.store'), $valid + [
            'latitude' => 11.2,
        ])->assertSessionHasErrors('latitude');
        $this->assertDatabaseCount('violations', 0);

        $this->actingAs($enforcer)->post(route('violations.store'), $valid + [
            'driver_license_number' => 'ABC-123!?',
        ])->assertSessionHasErrors('driver_license_number');
        $this->assertDatabaseCount('violations', 0);
    }

    public function test_only_admin_can_record_a_subpoena_action_after_the_45_day_deadline(): void
    {
        $violation = $this->violation(['notice_served_on' => '2026-10-01']);
        $action = ['subpoena_action_on' => '2026-11-16', 'subpoena_reference' => 'SUB-42'];

        $this->actingAs($this->user('enforcer'))
            ->patch(route('violations.subpoena-action', $violation), $action)
            ->assertForbidden();

        Carbon::setTestNow('2026-11-15 12:00:00');
        $this->actingAs($this->user('admin'))
            ->patch(route('violations.subpoena-action', $violation), $action)
            ->assertSessionHasErrors('subpoena_action_on');
        $this->assertNull($violation->fresh()->subpoena_action_on);

        Carbon::setTestNow('2026-11-16 12:00:00');
        $this->patch(route('violations.subpoena-action', $violation), $action)->assertRedirect();
        $this->assertSame('2026-11-16', $violation->fresh()->subpoena_action_on->toDateString());
        $this->assertSame('SUB-42', $violation->fresh()->subpoena_reference);
    }

    public function test_dashboard_and_report_endpoints_include_due_and_mapped_tickets(): void
    {
        $admin = $this->user('admin');
        $due = $this->violation([
            'notice_served_on' => '2026-10-01',
            'latitude' => 11.2445,
            'longitude' => 125.0032,
            'location' => 'Downtown Tacloban',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Subpoena review due')
            ->assertSee($due->ticket_number);

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('OpenStreetMap locations')
            ->assertSee('data-leaflet-map="report"', false)
            ->assertSee('tile.openstreetmap.org')
            ->assertSee('window.tomecoReportMapPoints', false)
            ->assertSee('Downtown Tacloban');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'plate_number' => 'ABC-123',
            'qr_token' => (string) Str::uuid(),
            'owner_name' => 'Maria Cruz',
            'vehicle_type' => 'Car',
        ]);
    }

    private function violationType(): ViolationType
    {
        return ViolationType::create(['name' => 'No parking', 'default_penalty' => 500]);
    }

    private function violation(array $overrides = []): Violation
    {
        $vehicle = $this->vehicle();
        $type = $this->violationType();

        return Violation::create(array_merge([
            'ticket_number' => 'TV-2026-'.Str::upper(Str::random(10)),
            'vehicle_id' => $vehicle->id,
            'violation_type_id' => $type->id,
            'location' => 'Downtown Tacloban',
            'occurred_at' => now()->subDay(),
            'penalty_amount' => 500,
            'status' => 'issued',
        ], $overrides));
    }
}
