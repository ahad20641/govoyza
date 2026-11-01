<?php

namespace App\Filament\Partner\Widgets;

use App\Models\Partner;
use Filament\Widgets\Widget;

/** Dashboard card: account status and what is still missing before GoVoyza can approve. */
class OnboardingStatus extends Widget
{
    protected string $view = 'filament.partner.widgets.onboarding-status';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    // The first thing a partner sees; no need for a second request.
    protected static bool $isLazy = false;

    /** @return array{partner: Partner, gaps: list<string>} */
    protected function getViewData(): array
    {
        $partner = auth()->user()->partner;

        return ['partner' => $partner, 'gaps' => $partner->onboardingGaps()];
    }
}
