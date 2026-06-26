<?php

namespace App\Services\Jimi;

/**
 * Manages GeoFence operations via Jimi API.
 */
class JimiGeoFenceService
{
    public function __construct(
        private JimiAuthService $auth,
    ) {}

    /**
     * Create a geofence on the Jimi platform.
     */
    public function createGeoFence(string $imei, array $params): array
    {
        return $this->auth->call('jimi.open.geo.fence.add', array_merge([
            'imei' => $imei,
        ], $params));
    }

    /**
     * Update geo fence.
     */
    public function updateGeoFence(string $imei, array $params): array
    {
        return $this->auth->call('jimi.open.geo.fence.update', array_merge([
            'imei' => $imei,
        ], $params));
    }

    /**
     * Delete geo fence.
     */
    public function deleteGeoFence(string $imei, string $instructNo): array
    {
        return $this->auth->call('jimi.open.geo.fence.del', [
            'imei' => $imei,
            'instruct_no' => $instructNo,
        ]);
    }

    /**
     * Get alarms for a device.
     */
    public function getAlarmList(string $imei, string $beginTime, string $endTime): array
    {
        $response = $this->auth->call('jimi.open.alarm.list', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Send a command to a device.
     */
    public function sendCommand(string $imei, string $command): array
    {
        return $this->auth->call('jimi.open.instruction.send', [
            'imei' => $imei,
            'command' => $command,
        ]);
    }

    /**
     * Get command list for a device.
     */
    public function getCommandList(string $imei): array
    {
        $response = $this->auth->call('jimi.open.instruction.list', [
            'imei' => $imei,
        ]);

        return $response['result'] ?? [];
    }
}
