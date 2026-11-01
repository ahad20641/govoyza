<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Single entry point for writing audit records. Never logs secrets:
 * sensitive keys are masked before storage.
 */
class AuditLogger
{
    private const MASKED = ['password', 'password_confirmation', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'passport_number', 'token', 'secret', 'api_key', 'code_hash'];

    public function __construct(private readonly Request $request) {}

    public function log(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        array $meta = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? $this->request->user()?->getKey(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $this->mask($old) ?: null,
            'new_values' => $this->mask($new) ?: null,
            'meta' => $meta ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }

    /** Log the dirty attributes of a model that is about to be / was just saved. */
    public function logChanges(string $action, Model $model, array $meta = []): AuditLog
    {
        $changes = $model->getChanges() ?: $model->getDirty();
        unset($changes['updated_at']);
        $old = array_intersect_key($model->getOriginal(), $changes);

        return $this->log($action, $model, $old, $changes, $meta);
    }

    private function mask(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::MASKED, true)) {
                $values[$key] = '***';
            } elseif ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            }
        }

        return $values;
    }
}
