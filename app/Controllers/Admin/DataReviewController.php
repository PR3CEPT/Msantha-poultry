<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\DataReviewService;
use Throwable;

class DataReviewController
{
    private DataReviewService $reviewService;

    public function __construct(?DataReviewService $reviewService = null)
    {
        $this->reviewService = $reviewService ?? new DataReviewService();
    }

    /**
     * Data Quality Review Dashboard.
     * Route: GET /admin/data-review
     */
    public function index(Request $request): Response
    {
        Auth::requireRole(['owner', 'manager']);

        $tab = (string) $request->query('tab', 'suspect');
        $validTabs = ['suspect', 'missing', 'projected', 'skipped'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'suspect';
        }

        $data = [
            'pageTitle' => 'Data Quality Review',
            'activeNav' => 'admin_data_review',
            'activeTab' => $tab,
            'suspectDates' => [],
            'missingDates' => [],
            'projectedRows' => [],
            'skippedSummary' => [],
        ];

        if ($tab === 'suspect') {
            $data['suspectDates'] = $this->reviewService->getSuspectDates(100);
        } elseif ($tab === 'missing') {
            $data['missingDates'] = $this->reviewService->getMissingDates(100);
        } elseif ($tab === 'projected') {
            $data['projectedRows'] = $this->reviewService->getProjectedRows(100);
        } elseif ($tab === 'skipped') {
            $data['skippedSummary'] = $this->reviewService->getSkippedRowsSummary();
        }

        return View::render('admin/data_review/index', $data);
    }

    /**
     * Resolve a flagged date.
     * Route: POST /admin/data-review/resolve
     */
    public function resolveDate(Request $request): Response
    {
        Auth::requireRole(['owner', 'manager']);

        $table = trim((string) $request->input('table', ''));
        $recordId = (int) $request->input('record_id', 0);
        $correctedDate = trim((string) $request->input('corrected_date', ''));
        $tab = trim((string) $request->input('return_tab', 'suspect'));

        if ($table === '' || $recordId <= 0 || $correctedDate === '') {
            $_SESSION['flash_error'] = 'Please provide a valid corrected date (YYYY-MM-DD).';
            return Response::redirect(url('/admin/data-review?tab=' . urlencode($tab)));
        }

        try {
            $this->reviewService->resolveDate($table, $recordId, $correctedDate, Auth::id());
            $_SESSION['flash_success'] = "Row #{$recordId} in {$table} resolved successfully to {$correctedDate}.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Resolution failed: " . $e->getMessage();
        }

        return Response::redirect(url('/admin/data-review?tab=' . urlencode($tab)));
    }
}
