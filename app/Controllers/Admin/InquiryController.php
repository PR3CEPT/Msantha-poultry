<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PDO;
use RuntimeException;
use Throwable;

class InquiryController
{
    private function checkAccess(): void
    {
        Auth::requireLogin();
        $role = Auth::role();
        // Allow owner (MD), manager, and any staff with user management
        if ($role !== 'owner' && $role !== 'manager' && !Auth::canManageUsers()) {
            $resp = View::render('errors/403', [
                'pageTitle' => '403 Forbidden',
                'message' => 'Access Denied: Only Farm Managers and the Managing Director can access public web inquiries.',
            ]);
            $resp->setStatusCode(403)->send();
            exit(0);
        }
    }

    /**
     * List all public inquiries.
     * Route: GET /admin/inquiries
     */
    public function index(Request $request): Response
    {
        $this->checkAccess();
        Audit::logView('admin_inquiries');

        $pdo = DB::pdo();
        $statusFilter = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $where = [];
        $params = [];

        if ($statusFilter !== '') {
            $where[] = "ci.status = :status";
            $params['status'] = $statusFilter;
        }

        if ($search !== '') {
            $where[] = "(ci.name LIKE :s OR ci.phone LIKE :s OR ci.subject LIKE :s OR ci.message LIKE :s)";
            $params['s'] = "%{$search}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Overall stats
        $counts = [
            'total' => 0,
            'new' => 0,
            'attended' => 0,
        ];
        try {
            $statStmt = $pdo->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) AS attended_count
                FROM contact_inquiries
            ")->fetch(PDO::FETCH_ASSOC);

            if ($statStmt) {
                $counts['total'] = (int) $statStmt['total'];
                $counts['new'] = (int) $statStmt['new_count'];
                $counts['attended'] = (int) $statStmt['attended_count'];
            }
        } catch (Throwable) {}

        $sql = "
            SELECT 
                ci.*,
                u.full_name AS attended_by_name,
                u.username AS attended_by_username
            FROM contact_inquiries ci
            LEFT JOIN users u ON u.id = ci.attended_by
            {$whereClause}
            ORDER BY 
                CASE WHEN ci.status = 'new' THEN 0 ELSE 1 END,
                ci.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('admin/inquiries/index', [
            'pageTitle' => 'Web Inquiries & Customer Messages',
            'activeNav' => 'admin_inquiries',
            'inquiries' => $inquiries,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'currentUserId' => Auth::id(),
            'currentUserName' => Auth::user()['full_name'] ?? 'Manager',
        ]);
    }

    /**
     * Mark an inquiry as attended by the current manager.
     * Route: POST /admin/inquiries/{id}/attend
     */
    public function attend(Request $request, int|string $id): Response
    {
        $this->checkAccess();

        $inquiryId = (int) $id;
        $notes = trim((string) $request->input('resolution_notes', ''));
        $user = Auth::user();
        $userId = (int) $user['id'];
        $userName = (string) $user['full_name'];

        $pdo = DB::pdo();

        $checkStmt = $pdo->prepare("SELECT * FROM contact_inquiries WHERE id = ? LIMIT 1");
        $checkStmt->execute([$inquiryId]);
        $inquiry = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$inquiry) {
            $_SESSION['flash_error'] = 'Inquiry record not found.';
            return Response::redirect(url('/admin/inquiries'));
        }

        $updateStmt = $pdo->prepare("
            UPDATE contact_inquiries
            SET status = 'attended',
                attended_by = ?,
                attended_at = NOW(),
                resolution_notes = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $userId,
            $notes !== '' ? $notes : 'Attended by ' . $userName,
            $inquiryId,
        ]);

        Audit::log('attend_inquiry', 'contact_inquiries', $inquiryId, null, [
            'inquiry_id' => $inquiryId,
            'customer_name' => $inquiry['name'],
            'customer_phone' => $inquiry['phone'],
            'attended_by' => $userName,
            'notes' => $notes,
        ]);

        $_SESSION['flash_success'] = "Inquiry from {$inquiry['name']} marked as attended by you. All other managers can now see that this has been addressed.";
        return Response::redirect(url('/admin/inquiries'));
    }
}
