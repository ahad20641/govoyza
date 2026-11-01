<?php

namespace App\Filament\Partner\Resources\ServiceJobs;

use App\Enums\PartnerStatus;
use App\Enums\UserType;
use App\Filament\Partner\Resources\ServiceJobs\Pages\ListServiceJobs;
use App\Filament\Partner\Resources\ServiceJobs\Pages\ViewServiceJob;
use App\Filament\Partner\Resources\ServiceJobs\Schemas\ServiceJobInfolist;
use App\Filament\Partner\Resources\ServiceJobs\Tables\ServiceJobsTable;
use App\Models\ServiceJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Jobs assigned to the signed-in partner: list and job detail. Partner portal, partner domain.
 * Tasks 1.20 — Routing & jobs, 1.21 — Partner job updates (S1: read-only shell).
 *
 * Partners see the order number and applicant names only: no customer email, phone or price
 * (CLAUDE.md rule 2). Every query is scoped to the partner's own jobs.
 */
class ServiceJobResource extends Resource
{
    protected static ?string $model = ServiceJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Jobs';

    protected static ?string $navigationLabel = 'Assigned jobs';

    protected static ?string $modelLabel = 'job';

    public static function table(Table $table): Table
    {
        return ServiceJobsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceJobInfolist::configure($schema);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->type === UserType::PartnerUser
            && $user->partner?->status === PartnerStatus::Active;
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess() && $record->partner_id === auth()->user()->partner_id;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forPartner(auth()->user()?->partner_id)
            ->with(['order:id,number', 'order.applicants:id,order_id,given_names,surname']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceJobs::route('/'),
            'view' => ViewServiceJob::route('/{record}'),
        ];
    }
}
