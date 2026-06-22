<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness/readiness endpoint reporting DB, cache and queue health.
 * See docs/tasks/001-foundation.md and docs/deployment.md §12.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $ok = ! in_array(false, array_column($checks, 'ok'), true);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    /** @return array{ok: bool, message: string} */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return ['ok' => true, 'message' => 'connected'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'unavailable'];
        }
    }

    /** @return array{ok: bool, message: string} */
    private function checkCache(): array
    {
        try {
            Cache::put('health:ping', '1', 5);

            return ['ok' => Cache::get('health:ping') === '1', 'message' => 'reachable'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'unavailable'];
        }
    }

    /** @return array{ok: bool, message: string} */
    private function checkQueue(): array
    {
        try {
            // Confirm the queue connection resolves; a deeper check (worker liveness)
            // is handled by infrastructure monitoring (docs/deployment.md §12).
            app('queue')->connection();

            return ['ok' => true, 'message' => 'configured'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'unavailable'];
        }
    }
}
