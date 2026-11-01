<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\Order;
use App\Models\Partner;
use App\Models\SupplierOffer;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * What needs action today: orders waiting on staff, late jobs, offers to review, partners to approve.
 * Admin console, group "Today". Shell from Sprint S1; task 1.30 — Admin Today board. Access: all staff.
 */
class TodayBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|UnitEnum|null $navigationGroup = 'Today';

    protected static ?string $title = 'Today board';

    protected static ?int $navigationSort = -10;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Needs action')->columns(4)->schema([
                Text::make('Orders under review: '.Order::query()->where('status', OrderStatus::UnderReview)->count()),
                Text::make('Offers to review: '.SupplierOffer::query()->where('status', SupplierOfferStatus::Pending)->count()),
                Text::make('Partners to approve: '.Partner::query()->where('status', PartnerStatus::Pending)->count()),
                Text::make('Late jobs: coming in task 1.29'),
            ]),
            Section::make('Coming in task 1.30')->schema([
                Text::make('Clickable lists for each item above, SLA breaches and cost-increase flags.'),
            ]),
        ]);
    }
}
