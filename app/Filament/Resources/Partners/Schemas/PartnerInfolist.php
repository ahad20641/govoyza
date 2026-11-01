<?php

namespace App\Filament\Resources\Partners\Schemas;

use App\Models\Partner;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner')->columns(2)->schema([
                    TextEntry::make('legal_name'),
                    TextEntry::make('contact_phone')->placeholder('—'),
                    TextEntry::make('status')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('kyc_status')->label('KYC')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('status_reason')->label('Suspension reason')->placeholder('—'),
                    TextEntry::make('approved_at')->dateTime()->placeholder('Not approved yet'),
                    TextEntry::make('users.email')->label('Logins')->listWithLineBreaks(),
                    TextEntry::make('gaps')->label('Still missing')
                        ->state(fn (Partner $record) => $record->onboardingGaps() ?: ['Nothing, ready to approve'])
                        ->listWithLineBreaks(),
                ]),
                Section::make('KYC documents')
                    ->description('Download from the "Download document" button above. Files are never on a public URL.')
                    ->schema([
                        TextEntry::make('documents_list')->hiddenLabel()
                            ->state(fn (Partner $record) => $record->documents->map(
                                fn ($d) => "{$d->document_type->label()}: {$d->original_name} (".number_format($d->size / 1024).' KB, '.$d->created_at->format('j M Y').')'
                            )->all() ?: ['None uploaded'])
                            ->listWithLineBreaks(),
                    ]),
                Section::make('Bank details')->columns(2)->schema([
                    TextEntry::make('bank_name')->state(fn (Partner $record) => $record->bank_details['bank_name'] ?? null)->placeholder('—'),
                    TextEntry::make('account_name')->state(fn (Partner $record) => $record->bank_details['account_name'] ?? null)->placeholder('—'),
                    // Masked here; payouts (Phase 2) use the full number.
                    TextEntry::make('account_number')->state(fn (Partner $record) => isset($record->bank_details['account_number'])
                        ? '•••• '.substr($record->bank_details['account_number'], -4) : null)->placeholder('—'),
                    TextEntry::make('branch')->state(fn (Partner $record) => $record->bank_details['branch'] ?? null)->placeholder('—'),
                ]),
                Section::make('Agreement')->columns(2)->schema([
                    TextEntry::make('agreement_version')->label('Version')->placeholder('Not signed'),
                    TextEntry::make('agreement_signed_name')->label('Signed by (typed name)')->placeholder('—'),
                    TextEntry::make('agreement_signed_at')->label('Signed at')->dateTime()->placeholder('—'),
                    TextEntry::make('agreement_ip_shown')->label('From IP')->state(fn (Partner $record) => $record->agreement_ip)->placeholder('—'),
                    TextEntry::make('agreement_text_sha256')->label('Text fingerprint (sha256)')->placeholder('—')->columnSpanFull(),
                ]),
            ]);
    }
}
