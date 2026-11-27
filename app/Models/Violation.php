<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    protected $fillable = ['ticket_number', 'vehicle_id', 'violation_type_id', 'location', 'latitude', 'longitude', 'occurred_at', 'description', 'driver_name', 'driver_license_number', 'notice_served_on', 'appeared_on', 'subpoena_action_on', 'subpoena_reference', 'penalty_amount', 'status', 'evidence_path', 'status_note', 'resolved_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
            'penalty_amount' => 'decimal:2',
            'driver_license_number' => 'encrypted',
            'latitude' => 'float',
            'longitude' => 'float',
            'notice_served_on' => 'date',
            'appeared_on' => 'date',
            'subpoena_action_on' => 'date',
        ];
    }

    /** @return array{stage: string, label: string, appearance_due_on: ?CarbonImmutable, payment_due_on: ?CarbonImmutable, missed_appearance: bool, subpoena_review_due: bool} */
    public function complianceTimeline(?CarbonInterface $asOf = null): array
    {
        $today = CarbonImmutable::parse($asOf ?? now())->startOfDay();
        $servedOn = $this->notice_served_on
            ? CarbonImmutable::parse($this->notice_served_on)->startOfDay()
            : null;

        if (! $servedOn) {
            return [
                'stage' => 'service_pending',
                'label' => 'Notice service date not recorded',
                'appearance_due_on' => null,
                'payment_due_on' => null,
                'missed_appearance' => false,
                'subpoena_review_due' => false,
            ];
        }

        // The supplied policy allows 30 calendar days to appear, then 15 more to pay.
        $appearanceDueOn = $servedOn->addDays(30);
        $paymentDueOn = $servedOn->addDays(45);
        $appearedOn = $this->appeared_on
            ? CarbonImmutable::parse($this->appeared_on)->startOfDay()
            : null;
        $appearedOnTime = $appearedOn && $appearedOn->lessThanOrEqualTo($appearanceDueOn);
        $missedAppearance = ! $appearedOnTime && $today->greaterThan($appearanceDueOn);

        if ($this->status === 'paid') {
            $stage = 'settled';
            $label = 'Fee settled';
        } elseif ($this->status === 'appealed') {
            $stage = 'appeal_pending';
            $label = 'Appeal pending';
        } elseif ($this->subpoena_action_on) {
            $stage = 'subpoena_action_recorded';
            $label = 'Subpoena action recorded';
        } elseif ($appearedOnTime) {
            $stage = 'appeared';
            $label = 'Appeared within 30 days';
        } elseif (! $missedAppearance) {
            $stage = 'appearance_window';
            $label = 'Appearance due by '.$appearanceDueOn->format('M j, Y');
        } elseif ($today->lessThanOrEqualTo($paymentDueOn)) {
            $stage = 'payment_window';
            $label = 'Fee due by '.$paymentDueOn->format('M j, Y');
        } else {
            $stage = 'subpoena_review_due';
            $label = 'Subpoena review due';
        }

        return [
            'stage' => $stage,
            'label' => $label,
            'appearance_due_on' => $appearanceDueOn,
            'payment_due_on' => $paymentDueOn,
            'missed_appearance' => $missedAppearance,
            'subpoena_review_due' => $stage === 'subpoena_review_due',
        ];
    }

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function type(): BelongsTo { return $this->belongsTo(ViolationType::class, 'violation_type_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
