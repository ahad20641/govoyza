<x-filament-widgets::widget>
    <x-filament::section :heading="$partner->legal_name" :description="'Account status: '.$partner->status->label()">
        @if($partner->isActive())
            <p>Your account is active.</p>
        @elseif($gaps)
            <p style="margin-bottom:.5rem">To finish your application:</p>
            <ul style="list-style:disc;padding-left:1.25rem">
                @foreach($gaps as $gap)
                    <li>{{ $gap }}</li>
                @endforeach
            </ul>
        @else
            <p>Everything is in. GoVoyza is reviewing your application and will email you when your account is active.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
