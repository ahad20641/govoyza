<?php

namespace App\Filament\Resources\JournalEntries;

use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only journal. Entries are written by LedgerPoster and never edited (CLAUDE.md rule 4).
 * Admin console, group "Accounting". List-only shell from Sprint S1; task 1.24 — Ledger.
 * Access: ledger.manage (Owner passes every check).
 */
class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Ledger';

    protected static ?string $modelLabel = 'Journal entry';

    protected static ?string $pluralModelLabel = 'Journal entries';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('ledger.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.24
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('lines');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
        ];
    }
}
