<?php

namespace App\Filament\Partner\Pages;

use App\Domain\Partners\PartnerOnboarding;
use App\Enums\PartnerDocumentType;
use App\Filament\Partner\Pages\Concerns\PartnerFormPage;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** KYC uploads. Files go to the private disk under a random name; nothing is ever on a public URL. */
class KycDocuments extends Page
{
    use PartnerFormPage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'KYC documents';

    protected static ?string $title = 'KYC documents';

    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Document')
                    ->options(collect(PartnerDocumentType::cases())->mapWithKeys(fn (PartnerDocumentType $t) => [$t->value => $t->label()])->all())
                    ->required(),
                FileUpload::make('file')
                    ->label('File (PDF, JPG or PNG, max 5 MB)')
                    ->disk(PartnerOnboarding::DISK)
                    ->directory(fn () => 'partners/'.$this->partner()->id)
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->storeFileNamesIn('original_name')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $documents = $this->partner()->documents()->latest()->get();

        return $schema->components([
            Section::make('Uploaded')
                ->description('Required: '.implode(', ', array_map(fn ($t) => $t->label(), PartnerDocumentType::required())).'.')
                ->schema($documents->isEmpty()
                    ? [Text::make('Nothing uploaded yet.')]
                    : $documents->map(fn ($d) => Text::make("{$d->document_type->label()}: {$d->original_name} ({$d->created_at->format('j M Y')})"))->all()),
            Section::make('Upload a document')->schema([$this->formComponent('Upload')]),
        ]);
    }

    public function save(PartnerOnboarding $onboarding): void
    {
        $data = $this->form->getState();

        $onboarding->addDocument(
            $this->partner(),
            PartnerDocumentType::from($data['document_type']),
            $data['file'],
            $data['original_name'] ?? null,
            auth()->user(),
        );

        $this->form->fill();
        Notification::make()->title('Document uploaded')->success()->send();
    }
}
