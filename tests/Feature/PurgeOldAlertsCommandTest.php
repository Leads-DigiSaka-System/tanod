<?php

namespace Tests\Feature;

use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PurgeOldAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_deletes_only_alerts_older_than_the_retention_period_in_chunks(): void
    {
        Carbon::setTestNow('2026-07-25 12:00:00');
        $oldest = $this->createAlertAt(now()->subMonths(2));
        $old = $this->createAlertAt(now()->subMonth()->subSecond());
        $atCutoff = $this->createAlertAt(now()->subMonth());
        $recent = $this->createAlertAt(now()->subDays(10));

        $this->artisan('alerts:purge', ['--chunk' => 1])
            ->expectsOutput('Deleted 2 alert(s) older than 2026-06-25 12:00:00.')
            ->assertSuccessful();

        $this->assertModelMissing($oldest);
        $this->assertModelMissing($old);
        $this->assertModelExists($atCutoff);
        $this->assertModelExists($recent);
    }

    public function test_dry_run_reports_without_deleting_alerts(): void
    {
        Carbon::setTestNow('2026-07-25 12:00:00');
        $old = $this->createAlertAt(now()->subMonths(2));

        $this->artisan('alerts:purge', ['--dry-run' => true])
            ->expectsOutput('Dry run: 1 alert(s) older than 2026-06-25 12:00:00 would be deleted.')
            ->assertSuccessful();

        $this->assertModelExists($old);
    }

    public function test_it_rejects_invalid_retention_and_chunk_options(): void
    {
        $this->artisan('alerts:purge', ['--months' => 0])
            ->expectsOutput('The --months option must be a positive integer.')
            ->assertExitCode(2);

        $this->artisan('alerts:purge', ['--chunk' => 'invalid'])
            ->expectsOutput('The --chunk option must be a positive integer.')
            ->assertExitCode(2);
    }

    private function createAlertAt(Carbon $createdAt): Alert
    {
        $alert = new Alert([
            'type' => 'offline',
            'title' => 'Test alert',
        ]);
        $alert->created_at = $createdAt;
        $alert->updated_at = $createdAt;
        $alert->save();

        return $alert;
    }
}
