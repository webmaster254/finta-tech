<x-filament-panels::page>
    <x-filament::section>
        @if(method_exists($this, 'filtersForm'))
            {{ $this->filtersForm }}
        @endif
    </x-filament::section>

    <x-branch.tables.container :report-loaded="$this->reportLoaded">
        @if($this->report && ! $this->tableHasEmptyState())
            <x-branch.tables.reports.account-transactions :report="$this->report"/>
        @else
            <x-filament-tables::empty-state
                :actions="$this->getEmptyStateActions()"
                :description="$this->getEmptyStateDescription()"
                :heading="$this->getEmptyStateHeading()"
                :icon="$this->getEmptyStateIcon()"
            />
        @endif
    </x-branch.tables.container>
</x-filament-panels::page>
