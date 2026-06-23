<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetrics;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(DashboardMetrics $metrics): View
    {
        $workspace = $this->current->get();

        return view('dashboard', [
            'workspace' => $workspace,
            'kpis' => $metrics->kpis($workspace),
            'checklist' => $metrics->checklist($workspace),
            'recentMessages' => $metrics->recentMessages($workspace),
            'upcomingSchedules' => $metrics->upcomingSchedules($workspace),
        ]);
    }
}
