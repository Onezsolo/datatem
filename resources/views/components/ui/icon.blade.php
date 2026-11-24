@props(['name' => 'search', 'size' => 20])

<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes }}>
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7"></circle>
            <line x1="16.5" y1="16.5" x2="21" y2="21"></line>
            @break
        @case('cart')
            <circle cx="9" cy="20" r="1.4"></circle>
            <circle cx="18" cy="20" r="1.4"></circle>
            <path d="M2 3h3l2.4 12h11.2l2.4-9H6"></path>
            @break
        @case('chevron-left')
            <path d="M15 18l-6-6 6-6"></path>
            @break
        @case('chevron-right')
            <path d="M9 18l6-6-6-6"></path>
            @break
        @case('check')
            <path d="M20 6L9 17l-5-5"></path>
            @break
        @case('home')
            <path d="M3 10.5 12 3l9 7.5"></path>
            <path d="M5 9.5V21h14V9.5"></path>
            @break
        @case('grid')
            <rect x="4" y="4" width="6" height="6" rx="1"></rect>
            <rect x="14" y="4" width="6" height="6" rx="1"></rect>
            <rect x="4" y="14" width="6" height="6" rx="1"></rect>
            <rect x="14" y="14" width="6" height="6" rx="1"></rect>
            @break
        @case('phone')
            <rect x="7" y="2" width="10" height="20" rx="2"></rect>
            <line x1="11" y1="18" x2="13" y2="18"></line>
            @break
        @case('laptop')
            <rect x="4" y="4" width="16" height="12" rx="1"></rect>
            <path d="M2 19h20"></path>
            @break
        @case('headphones')
            <path d="M4 14v-2a8 8 0 0 1 16 0v2"></path>
            <rect x="3" y="14" width="4" height="6" rx="1"></rect>
            <rect x="17" y="14" width="4" height="6" rx="1"></rect>
            @break
        @case('shirt')
            <path d="M8 4 4 6l2 4 2-1v11h8V9l2 1 2-4-4-2-2 2h-2L8 4z"></path>
            @break
        @case('shoe')
            <path d="M3 16h18v3H3z"></path>
            <path d="M3 16c4-1 6-4 8-8 2 4 5 6 10 7"></path>
            @break
        @case('bag')
            <path d="M5 8h14l-1 13H6L5 8z"></path>
            <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
            @break
        @case('watch')
            <rect x="7" y="6" width="10" height="12" rx="2"></rect>
            <path d="M9 6V3h6v3M9 18v3h6v-3"></path>
            @break
        @case('appliance')
            <path d="M8 3h8l-1 8H9L8 3z"></path>
            <path d="M9 11h6l1 10H8l1-10z"></path>
            <path d="M10 15h4"></path>
            @break
        @case('beauty')
            <path d="M9 3h6v3H9z"></path>
            <rect x="8" y="6" width="8" height="15" rx="1"></rect>
            @break
        @case('ball')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="m12 5 3.2 2.3-1.2 3.7h-4L8.8 7.3 12 5z"></path>
            <path d="M4.5 10.5 8.8 7.3M19.5 10.5 15.2 7.3M7 19l3-4M17 19l-3-4"></path>
            @break
        @case('teddy')
            <circle cx="12" cy="9" r="5"></circle>
            <circle cx="5" cy="6" r="2"></circle>
            <circle cx="19" cy="6" r="2"></circle>
            <path d="M7 14c1 3 3 5 5 5s4-2 5-5"></path>
            @break
        @case('chair')
            <path d="M6 11V6h12v5"></path>
            <path d="M5 11h14v6H5z"></path>
            <path d="M7 17v3M17 17v3"></path>
            @break
        @case('help')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M9.5 9.5a2.5 2.5 0 1 1 4.2 1.9c-.8.7-1.7 1.2-1.7 2.6"></path>
            <path d="M12 17h.01"></path>
            @break
        @case('more')
            <circle cx="5" cy="12" r="1.5"></circle>
            <circle cx="12" cy="12" r="1.5"></circle>
            <circle cx="19" cy="12" r="1.5"></circle>
            @break
        @case('truck')
            <path d="M3 6h11v10H3z"></path>
            <path d="M14 9h4l3 3v4h-7z"></path>
            <circle cx="7" cy="18" r="2"></circle>
            <circle cx="17" cy="18" r="2"></circle>
            @break
        @default
            <circle cx="12" cy="12" r="9"></circle>
    @endswitch
</svg>
