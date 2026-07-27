@php
    $pagination = $pagination ?? [];
    $paginationItems = $paginationItems ?? [];
    $currentPage = (int) ($currentPage ?? 1);
    $buildPageUrl = $buildPageUrl ?? static function (int $page): string {
        return $page > 1 ? url('umkm/' . $page) : url('umkm');
    };
@endphp

<nav class="umkm-pagination-nav" aria-label="Navigasi halaman UMKM">
    <div class="umkm-pagination-wrap">
        <ul class="pagination align-items-center gap-1 umkm-pagination-list mb-0">
            <li class="page-item">
                @if ($pagination['hasPreviousPage'] ?? false)
                    <a
                        class="page-link umkm-pagination-link umkm-pagination-icon"
                        href="{{ $buildPageUrl((int) ($pagination['firstPage'] ?? 1)) }}"
                        data-no-pjax
                        data-umkm-pagination-link
                        aria-label="Halaman pertama"
                    >
                        <i class="ti ti-chevrons-left"></i>
                    </a>
                @else
                    <span class="page-link umkm-pagination-link is-disabled" aria-hidden="true">
                        <i class="ti ti-chevrons-left"></i>
                    </span>
                @endif
            </li>

            <li class="page-item">
                @if ($pagination['hasPreviousPage'] ?? false)
                    <a
                        class="page-link umkm-pagination-link umkm-pagination-icon"
                        href="{{ $buildPageUrl((int) ($pagination['previousPage'] ?? 1)) }}"
                        data-no-pjax
                        data-umkm-pagination-link
                        aria-label="Halaman sebelumnya"
                    >
                        <i class="ti ti-chevron-left"></i>
                    </a>
                @else
                    <span class="page-link umkm-pagination-link is-disabled" aria-hidden="true">
                        <i class="ti ti-chevron-left"></i>
                    </span>
                @endif
            </li>

            @foreach ($paginationItems as $paginationItem)
                <li class="page-item">
                    @if (($paginationItem['type'] ?? '') === 'ellipsis')
                        <span class="page-link umkm-pagination-link umkm-pagination-ellipsis" aria-hidden="true">...</span>
                    @elseif ((int) ($paginationItem['value'] ?? 0) === $currentPage)
                        <span class="page-link umkm-pagination-link is-active" aria-current="page">
                            {{ $paginationItem['value'] }}
                        </span>
                    @else
                        <a
                            class="page-link umkm-pagination-link"
                            href="{{ $buildPageUrl((int) $paginationItem['value']) }}"
                            data-no-pjax
                            data-umkm-pagination-link
                            aria-label="Halaman {{ $paginationItem['value'] }}"
                        >
                            {{ $paginationItem['value'] }}
                        </a>
                    @endif
                </li>
            @endforeach

            <li class="page-item">
                @if ($pagination['hasNextPage'] ?? false)
                    <a
                        class="page-link umkm-pagination-link umkm-pagination-icon"
                        href="{{ $buildPageUrl((int) ($pagination['nextPage'] ?? 1)) }}"
                        data-no-pjax
                        data-umkm-pagination-link
                        aria-label="Halaman berikutnya"
                    >
                        <i class="ti ti-chevron-right"></i>
                    </a>
                @else
                    <span class="page-link umkm-pagination-link is-disabled" aria-hidden="true">
                        <i class="ti ti-chevron-right"></i>
                    </span>
                @endif
            </li>

            <li class="page-item">
                @if ($pagination['hasNextPage'] ?? false)
                    <a
                        class="page-link umkm-pagination-link umkm-pagination-icon"
                        href="{{ $buildPageUrl((int) ($pagination['lastPage'] ?? 1)) }}"
                        data-no-pjax
                        data-umkm-pagination-link
                        aria-label="Halaman terakhir"
                    >
                        <i class="ti ti-chevrons-right"></i>
                    </a>
                @else
                    <span class="page-link umkm-pagination-link is-disabled" aria-hidden="true">
                        <i class="ti ti-chevrons-right"></i>
                    </span>
                @endif
            </li>
        </ul>
    </div>
</nav>
