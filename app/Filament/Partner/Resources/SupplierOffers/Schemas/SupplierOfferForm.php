<?php

namespace App\Filament\Partner\Resources\SupplierOffers\Schemas;

use App\Enums\Currency;
use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier terms')->columns(2)->schema([
                Select::make('product_id')
                    ->label('Visa product')
                    ->options(fn () => Product::query()
                        ->whereIn('status', [ProductStatus::Draft->value, ProductStatus::Live->value])
                        ->with(['country', 'visaType'])
                        ->orderBy('public_name')
                        ->get()
                        ->mapWithKeys(fn (Product $product) => [
                            $product->id => "{$product->country->name} · {$product->visaType->name} · {$product->public_name}",
                        ])->all())
                    ->searchable()->preload()->disabledOn('edit')->required(),
                TextInput::make('cost_amount')
                    ->label('Your cost')
                    ->helperText('A live offer saves changes as a pending revision; current terms remain active until approval.')
                    ->numeric()->minValue(0)->required(),
                Select::make('currency')
                    ->options(collect(Currency::cases())->mapWithKeys(fn (Currency $currency) => [$currency->value => $currency->value])->all())
                    ->default(config('govoyza.currency'))
                    ->required(),
                TextInput::make('processing_days')->numeric()->integer()->minValue(1)->required(),
                TextInput::make('capacity_per_week')->label('Weekly capacity')->numeric()->integer()->minValue(0),
                TagsInput::make('inclusions')->placeholder('Add an inclusion'),
                Textarea::make('refund_terms')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }
}
