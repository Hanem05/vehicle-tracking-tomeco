<?php

namespace Tests\Unit;

use App\Models\Violation;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ViolationComplianceTimelineTest extends TestCase
{
    public function test_missing_service_date_leaves_the_follow_up_clock_pending(): void
    {
        $timeline = (new Violation(['status' => 'issued']))
            ->complianceTimeline(CarbonImmutable::parse('2026-02-01'));

        $this->assertSame('service_pending', $timeline['stage']);
        $this->assertNull($timeline['appearance_due_on']);
        $this->assertNull($timeline['payment_due_on']);
        $this->assertFalse($timeline['subpoena_review_due']);
    }

    public function test_appearance_payment_and_review_deadline_boundaries(): void
    {
        $violation = new Violation([
            'status' => 'issued',
            'notice_served_on' => '2026-01-01',
        ]);

        $day30 = $violation->complianceTimeline(CarbonImmutable::parse('2026-01-31'));
        $this->assertSame('appearance_window', $day30['stage']);
        $this->assertSame('2026-01-31', $day30['appearance_due_on']->toDateString());
        $this->assertSame('2026-02-15', $day30['payment_due_on']->toDateString());
        $this->assertFalse($day30['missed_appearance']);

        $day31 = $violation->complianceTimeline(CarbonImmutable::parse('2026-02-01'));
        $this->assertSame('payment_window', $day31['stage']);
        $this->assertTrue($day31['missed_appearance']);

        $day45 = $violation->complianceTimeline(CarbonImmutable::parse('2026-02-15'));
        $this->assertSame('payment_window', $day45['stage']);
        $this->assertFalse($day45['subpoena_review_due']);

        $day46 = $violation->complianceTimeline(CarbonImmutable::parse('2026-02-16'));
        $this->assertSame('subpoena_review_due', $day46['stage']);
        $this->assertTrue($day46['subpoena_review_due']);
    }

    public function test_timely_appearance_and_resolved_statuses_are_not_escalated(): void
    {
        $asOf = CarbonImmutable::parse('2026-02-16');
        $timelyAppearance = new Violation([
            'status' => 'issued',
            'notice_served_on' => '2026-01-01',
            'appeared_on' => '2026-01-31',
        ]);
        $this->assertSame('appeared', $timelyAppearance->complianceTimeline($asOf)['stage']);

        $appealed = new Violation([
            'status' => 'appealed',
            'notice_served_on' => '2026-01-01',
        ]);
        $this->assertSame('appeal_pending', $appealed->complianceTimeline($asOf)['stage']);

        $paid = new Violation([
            'status' => 'paid',
            'notice_served_on' => '2026-01-01',
        ]);
        $this->assertSame('settled', $paid->complianceTimeline($asOf)['stage']);
    }
}
