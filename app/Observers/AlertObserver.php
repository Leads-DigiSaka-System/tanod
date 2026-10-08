<?php

namespace App\Observers;

use App\Models\Alert;
use App\Services\AlertSummaryService;

class AlertObserver
{
    public function created(Alert $alert): void
    {
        AlertSummaryService::increment($alert);
    }

    public function updated(Alert $alert): void
    {
        if (! $alert->wasChanged('is_acknowledged')) {
            return;
        }

        $wasAcknowledged = (bool) $alert->getOriginal('is_acknowledged');
        $isAcknowledged = (bool) $alert->is_acknowledged;

        if ($wasAcknowledged === $isAcknowledged) {
            return;
        }

        AlertSummaryService::handleAcknowledgedChange($alert, $isAcknowledged);
    }

    public function deleted(Alert $alert): void
    {
        AlertSummaryService::decrement($alert);
    }
}
