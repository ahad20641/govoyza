<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Who')->placeholder('System'),
                TextColumn::make('action')->searchable(),
                TextColumn::make('auditable_type')->label('Record')
                    ->formatStateUsing(fn (?string $state, $record) => $state ? class_basename($state).' #'.$record->auditable_id : null),
                // Secrets are already masked by AuditLogger before they are stored.
                TextColumn::make('new_values')->label('Changes')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null)
                    ->limit(80)->wrap(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn () => DB::table('audit_logs')->distinct()->orderBy('action')->pluck('action', 'action')->all()),
            ]);
    }
}
