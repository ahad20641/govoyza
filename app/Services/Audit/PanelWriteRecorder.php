<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit record for every Eloquent create/update/delete made inside
 * the admin or partner panel (security.md: "audit log for every admin and
 * partner write"), as `admin.created`, `partner.updated` and so on. Hooked to
 * the wildcard Eloquent events in AppServiceProvider.
 *
 * "Inside a panel" = Filament's current panel. Filament's SetUpPanel middleware
 * is persistent, so this also holds for Livewire update requests, which is where
 * the saves actually happen.
 */
class PanelWriteRecorder
{
    private const PANELS = ['admin', 'partner'];

    /** Models never recorded: the audit table itself (no recursion). */
    private const IGNORED = [AuditLog::class];

    public function __construct(private readonly AuditLogger $audit) {}

    public function record(string $event, Model $model): void
    {
        $panel = Filament::getCurrentPanel()?->getId();

        if (! in_array($panel, self::PANELS, true) || in_array($model::class, self::IGNORED, true)) {
            return;
        }

        match ($event) {
            'created' => $this->audit->log("{$panel}.created", $model, new: $this->visible($model, $model->getAttributes())),
            'updated' => $this->audit->log(
                "{$panel}.updated",
                $model,
                old: $this->visible($model, array_intersect_key($model->getOriginal(), $this->changes($model))),
                new: $this->visible($model, $this->changes($model)),
            ),
            'deleted' => $this->audit->log("{$panel}.deleted", $model, old: $this->visible($model, $model->getOriginal())),
        };
    }

    /** @return array<string, mixed> */
    private function changes(Model $model): array
    {
        return array_diff_key($model->getChanges(), ['updated_at' => true]);
    }

    /**
     * Hidden attributes (password, secrets, code hashes) are masked rather than
     * stored; AuditLogger masks the well-known secret keys on top of this.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function visible(Model $model, array $values): array
    {
        foreach ($model->getHidden() as $hidden) {
            if (array_key_exists($hidden, $values)) {
                $values[$hidden] = '***';
            }
        }

        return $values;
    }
}
