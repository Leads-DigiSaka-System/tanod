<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSimHistory;

/**
 * Handles SIM number changes on a device.
 *
 * Every time the SIM number changes, the previous value is archived in
 * `device_sim_histories` so it can still be viewed after the replacement.
 */
class DeviceSimService
{
    /**
     * Change the SIM number of a device and archive the previous value.
     *
     * @param  bool  $markOverridden  When true, flag the device so the manual
     *                                value is not overwritten by Jimi sync.
     * @return bool  True when the stored SIM value actually changed.
     */
    public function change(
        Device $device,
        ?string $newSim,
        ?int $changedBy = null,
        bool $markOverridden = false,
    ): bool {
        $newSim = is_string($newSim) ? trim($newSim) : null;
        if ($newSim === '') {
            $newSim = null;
        }

        $oldSim = $device->sim;
        $changed = (string) $oldSim !== (string) $newSim;

        // Only archive when there was a previous number to preserve.
        if ($changed && ! empty($oldSim)) {
            DeviceSimHistory::create([
                'device_id' => $device->id,
                'changed_by' => $changedBy,
                'old_sim' => $oldSim,
                'new_sim' => $newSim,
            ]);
        }

        if ($changed) {
            $device->sim = $newSim;

            // Once manually changed, lock further edits and protect the value
            // from being overwritten by the Jimi sync.
            if ($markOverridden && ! $device->sim_overridden) {
                $device->sim_overridden = true;
            }
        }

        if ($device->isDirty()) {
            $device->save();
        }

        return $changed;
    }
}
