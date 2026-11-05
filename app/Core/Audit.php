<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

class Audit
{
    public static function log(
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ?int {
        try {
            $user = $userId ?? Auth::id();
            $req = Request::capture();
            $clientIp = $ip ?? $req->ip();
            $agent = $userAgent ?? ($req->header('user-agent') ?? ($_SERVER['HTTP_USER_AGENT'] ?? null));

            $sanitizedOld = $oldValues !== null ? self::redactSensitive($oldValues) : null;
            $sanitizedNew = $newValues !== null ? self::redactSensitive($newValues) : null;

            $oldJson = $sanitizedOld !== null ? json_encode($sanitizedOld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $newJson = $sanitizedNew !== null ? json_encode($sanitizedNew, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

            $pdo = DB::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip, user_agent, created_at)
                VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip, :user_agent, NOW())
            ");

            $stmt->execute([
                'user_id' => $user,
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'old_values' => $oldJson,
                'new_values' => $newJson,
                'ip' => $clientIp,
                'user_agent' => $agent ? substr($agent, 0, 250) : null,
            ]);

            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            Logger::error("Failed to write audit log: " . $e->getMessage(), [
                'action' => $action,
                'table' => $table,
                'record_id' => $recordId,
            ]);
            return null;
        }
    }

    public static function logView(string $screenName): void
    {
        self::log('view', $screenName);
    }

    private static function redactSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'password_hash', 'secret', 'token', '_csrf_token', 'api_key', 'plain_password', 'key'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactSensitive($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $needle) {
                    if (stripos($key, $needle) !== false) {
                        $data[$key] = '***REDACTED***';
                        break;
                    }
                }
            }
        }
        return $data;
    }
}
