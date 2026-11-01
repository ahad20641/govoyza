<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Enums\PartnerStatus;
use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Partner;
use App\Models\PartnerDocument;
use App\Services\Audit\AuditLogger;
use App\Services\Partners\PartnerApproval;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property Partner $record */
class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('The partner becomes Active and is emailed.')
                ->visible(fn () => $this->record->status === PartnerStatus::Pending)
                ->authorize('approve', $this->record)
                ->action(function (PartnerApproval $approval) {
                    try {
                        $approval->approve($this->record, auth()->user());
                    } catch (LogicException $e) {
                        Notification::make()->title('Not ready to approve')->body($e->getMessage())->danger()->send();

                        return;
                    }
                    Notification::make()->title('Partner approved')->success()->send();
                }),
            Action::make('suspend')
                ->label('Suspend')
                ->color('danger')
                ->visible(fn () => $this->record->status !== PartnerStatus::Suspended)
                ->authorize('approve', $this->record)
                ->schema([Textarea::make('reason')->required()->maxLength(500)])
                ->action(function (array $data, PartnerApproval $approval) {
                    $approval->suspend($this->record, auth()->user(), $data['reason']);
                    Notification::make()->title('Partner suspended: its users can no longer sign in')->success()->send();
                }),
            Action::make('reactivate')
                ->label('Reactivate')
                ->visible(fn () => $this->record->status === PartnerStatus::Suspended)
                ->authorize('approve', $this->record)
                ->requiresConfirmation()
                ->action(function (PartnerApproval $approval) {
                    $approval->reactivate($this->record, auth()->user());
                    Notification::make()->title('Partner reactivated')->success()->send();
                }),
            Action::make('download')
                ->label('Download document')
                ->color('gray')
                ->visible(fn () => $this->record->documents()->exists())
                ->schema([
                    Select::make('document_id')->label('Document')->required()
                        ->options(fn () => $this->record->documents()->get()
                            ->mapWithKeys(fn (PartnerDocument $d) => [$d->id => "{$d->document_type->label()}: {$d->original_name}"])),
                ])
                ->action(function (array $data, AuditLogger $audit): StreamedResponse {
                    $document = $this->record->documents()->findOrFail($data['document_id']);
                    abort_unless(auth()->user()->can('view', $document), 403);
                    $audit->log('admin.partner_document_downloaded', $document);

                    return Storage::disk($document->disk)->download($document->path, $document->original_name);
                }),
        ];
    }
}
