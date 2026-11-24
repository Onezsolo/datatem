@php
    $storeTitle = isset($store_name) && $store_name ? $store_name : 'Store';
@endphp

<x-layouts.public :title="$storeTitle.' – Data bundles'" :brand="$store_name ?? null" description="Buy mobile data bundles from this store. No account needed; pay with Mobile Money.">
    @yield('content')
</x-layouts.public>
