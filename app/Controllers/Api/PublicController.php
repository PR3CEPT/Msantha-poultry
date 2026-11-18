<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\DB;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

class PublicController
{
    /**
     * Public incubation batch tracker.
     * Route: POST /api/public/track-incubation
     */
    public function trackIncubation(Request $request): Response
    {
        $rawPhone = trim((string) $request->input('phone', ''));
        $rawJobRef = trim((string) $request->input('job_ref', ''));

        if ($rawPhone === '' && $rawJobRef === '') {
            return Response::json([
                'success' => false,
                'message' => 'Please provide your mobile phone number or batch/job reference number.',
                'jobs' => [],
            ], 422);
        }

        $pdo = DB::pdo();
        $where = [];
        $params = [];

        // Phone search matching (last 8-9 digits to handle +265, 099, 99 prefixes)
        if ($rawPhone !== '') {
            $digits = preg_replace('/\D/', '', $rawPhone);
            $shortDigits = strlen($digits) >= 8 ? substr($digits, -8) : $digits;

            $where[] = "(REPLACE(REPLACE(REPLACE(c.phone, '+', ''), ' ', ''), '-', '') LIKE :phone_digits OR c.phone LIKE :phone_raw)";
            $params['phone_digits'] = "%{$shortDigits}%";
            $params['phone_raw'] = "%{$rawPhone}%";
        }

        // Job Ref or Batch code search matching
        if ($rawJobRef !== '') {
            $jobId = preg_replace('/\D/', '', $rawJobRef);
            if ($jobId !== '') {
                $where[] = "(j.id = :job_id OR j.batch_code LIKE :job_code OR j.legacy_ref LIKE :job_code)";
                $params['job_id'] = (int) $jobId;
                $params['job_code'] = "%{$rawJobRef}%";
            } else {
                $where[] = "(j.batch_code LIKE :job_code OR j.legacy_ref LIKE :job_code)";
                $params['job_code'] = "%{$rawJobRef}%";
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $sql = "
                SELECT 
                    j.id, j.batch_code, j.legacy_ref, j.eggs_incubated, j.date_incubated, j.date_transfer,
                    j.expected_hatch_date, j.status, j.cost, j.amount_paid,
                    c.name AS customer_name, c.phone AS customer_phone,
                    b.name AS breed_name
                FROM incubation_jobs j
                JOIN customers c ON c.id = j.customer_id
                LEFT JOIN breeds b ON b.id = j.breed_id
                {$whereClause}
                ORDER BY j.id DESC
                LIMIT 5
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rawJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rawJobs)) {
                return Response::json([
                    'success' => true,
                    'count' => 0,
                    'message' => 'No active incubation batches found for the provided details. Please verify your number or contact farm administration directly at +265 9998 996 20.',
                    'jobs' => [],
                ]);
            }

            $today = date('Y-m-d');
            $jobs = [];

            foreach ($rawJobs as $job) {
                $settingDate = $job['date_incubated'] ?? null;
                $expectedHatch = $job['expected_hatch_date'] ?? null;
                $dateTransfer = $job['date_transfer'] ?? null;

                $totalCycleDays = 21;
                if ($settingDate && $expectedHatch) {
                    $diff = (int) round((strtotime($expectedHatch) - strtotime($settingDate)) / 86400);
                    if ($diff > 0) {
                        $totalCycleDays = $diff;
                    }
                }
                $transferDayNumber = max(1, $totalCycleDays - 3);

                $daysLeft = null;
                $dayNumber = 1;
                $progressPercent = 10;

                if ($settingDate) {
                    $elapsedDays = (int) round((strtotime($today) - strtotime($settingDate)) / 86400);
                    $dayNumber = max(1, min($totalCycleDays, $elapsedDays + 1));
                    $progressPercent = (int) round(($dayNumber / $totalCycleDays) * 100);
                }

                if ($expectedHatch) {
                    $daysLeft = (int) round((strtotime($expectedHatch) - strtotime($today)) / 86400);
                }

                // Friendly stage name
                $status = strtolower((string) ($job['status'] ?? 'incubating'));
                $stageText = 'Incubating (Setter)';
                $badgeClass = 'bg-primary';

                if ($status === 'completed' || $status === 'collected') {
                    $stageText = 'Hatched & Completed';
                    $badgeClass = 'bg-success';
                    $progressPercent = 100;
                    $countdownText = 'Batch Completed';
                } elseif ($status === 'hatching') {
                    $stageText = 'In Hatcher (Hatching Now)';
                    $badgeClass = 'bg-warning text-dark';
                    $countdownText = 'Hatching in Progress';
                } elseif ($status === 'cancelled') {
                    $stageText = 'Cancelled';
                    $badgeClass = 'bg-danger';
                    $countdownText = 'Cancelled';
                } elseif ($daysLeft !== null && $daysLeft <= 0) {
                    $stageText = 'Hatch Day Today!';
                    $badgeClass = 'bg-success';
                    $progressPercent = 100;
                    $countdownText = 'Hatch Day Today!';
                } elseif ($dayNumber >= $transferDayNumber) {
                    $stageText = 'In Hatcher (Final Stage)';
                    $badgeClass = 'bg-warning text-dark';
                    $countdownText = "Hatch in {$daysLeft} day" . ($daysLeft === 1 ? '' : 's');
                } else {
                    $countdownText = $daysLeft !== null ? "Hatch in {$daysLeft} days" : "Day {$dayNumber} of {$totalCycleDays}";
                }

                // Obfuscate customer name for privacy (e.g. "Gilbert M***")
                $nameParts = explode(' ', trim($job['customer_name']));
                $first = $nameParts[0] ?? '';
                $lastInitial = isset($nameParts[1]) && strlen($nameParts[1]) > 0 ? strtoupper(substr($nameParts[1], 0, 1)) . '***' : '';
                $displayCustomer = trim($first . ' ' . $lastInitial);

                $jobs[] = [
                    'id' => $job['id'],
                    'batch_code' => (function() use ($job): string {
                        if (!empty($job['batch_code'])) {
                            return (string)$job['batch_code'];
                        }
                        $ref = (string)($job['legacy_ref'] ?? '');
                        // Strip import artifact prefixes like "incubation_01:row_860"
                        if (preg_match('/^incubation_\d+:row_(\d+)$/i', $ref, $m)) {
                            return 'JOB-' . str_pad($m[1], 3, '0', STR_PAD_LEFT);
                        }
                        return $ref ?: 'JOB-' . str_pad((string)$job['id'], 3, '0', STR_PAD_LEFT);
                    })(),
                    'customer_name' => $displayCustomer,
                    'breed_name' => $job['breed_name'] ?: 'Dual-purpose Poultry',
                    'eggs_incubated' => (int) $job['eggs_incubated'],
                    'setting_date' => $settingDate ? date('d-M-Y', strtotime($settingDate)) : '—',
                    'date_transfer' => $dateTransfer ? date('d-M-Y', strtotime($dateTransfer)) : '—',
                    'expected_hatch_date' => $expectedHatch ? date('d-M-Y', strtotime($expectedHatch)) : '—',
                    'days_left' => $daysLeft,
                    'day_number' => $dayNumber,
                    'total_days' => $totalCycleDays,
                    'transfer_day' => $transferDayNumber,
                    'progress_percent' => $progressPercent,
                    'stage_text' => $stageText,
                    'badge_class' => $badgeClass,
                    'countdown_text' => $countdownText,
                ];
            }

            return Response::json([
                'success' => true,
                'count' => count($jobs),
                'message' => 'Found ' . count($jobs) . ' matching batch(es).',
                'jobs' => $jobs,
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lookup error: ' . $e->getMessage(),
                'jobs' => [],
            ], 500);
        }
    }

    /**
     * Public contact inquiry submission.
     * Route: POST /api/public/contact-inquiry
     */
    public function submitInquiry(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $subject = trim((string) $request->input('subject', 'General Agribusiness Inquiry'));
        $message = trim((string) $request->input('message', ''));

        if (strlen($name) < 2) {
            return Response::json(['success' => false, 'message' => 'Please provide your full name.'], 422);
        }
        if (strlen($phone) < 7) {
            return Response::json(['success' => false, 'message' => 'Please provide a valid Malawi phone number.'], 422);
        }
        if (strlen($message) < 5) {
            return Response::json(['success' => false, 'message' => 'Please enter a message or description of your inquiry.'], 422);
        }

        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO contact_inquiries (name, phone, subject, message, status, ip, created_at)
                VALUES (?, ?, ?, ?, 'new', ?, NOW())
            ");
            $stmt->execute([
                $name,
                $phone,
                $subject,
                $message,
                $request->ip(),
            ]);

            return Response::json([
                'success' => true,
                'message' => "Thank you, {$name}! Your message regarding '{$subject}' has been securely delivered to Msantha Investments (MIGS) management. Our Liwonde team will reach out to {$phone} promptly.",
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Failed to record inquiry: ' . $e->getMessage(),
            ], 500);
        }
    }
}
