@php
    $color = $status->getColor();
@endphp

<div>
    <x-filament::badge :color="$color">
        {{ $status->getLabel() }}
    </x-filament::badge>
</div>
