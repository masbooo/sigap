@php
    $summaryCards = $summaryCards ?? [];
    $carouselId = trim((string) ($carouselId ?? ''));
    $carouselOptions = array_merge([
        'loop' => count($summaryCards) > 5,
        'margin' => 20,
        'nav' => false,
        'dots' => count($summaryCards) > 1,
        'autoplay' => false,
        'items' => 2,
        'smItems' => 2,
        'mdItems' => 3,
        'lgItems' => 4,
        'xlItems' => 5,
        'xxlItems' => 6,
    ], $carouselOptions ?? []);
@endphp

@if (!empty($summaryCards))
    <div class="mb-4">
        <div
            @if ($carouselId !== '') id="{{ $carouselId }}" @endif
            class="owl-carousel counter-carousel owl-theme admin-summary-card-carousel"
            data-owl-loop="{{ $carouselOptions['loop'] ? 'true' : 'false' }}"
            data-owl-margin="{{ $carouselOptions['margin'] }}"
            data-owl-nav="{{ $carouselOptions['nav'] ? 'true' : 'false' }}"
            data-owl-dots="{{ $carouselOptions['dots'] ? 'true' : 'false' }}"
            data-owl-autoplay="{{ $carouselOptions['autoplay'] ? 'true' : 'false' }}"
            data-owl-items="{{ $carouselOptions['items'] }}"
            data-owl-sm-items="{{ $carouselOptions['smItems'] }}"
            data-owl-md-items="{{ $carouselOptions['mdItems'] }}"
            data-owl-lg-items="{{ $carouselOptions['lgItems'] }}"
            data-owl-xl-items="{{ $carouselOptions['xlItems'] }}"
            data-owl-xxl-items="{{ $carouselOptions['xxlItems'] }}"
        >
            @foreach ($summaryCards as $card)
                @php
                    $tone = $card['tone'] ?? 'primary';
                    $textTone = match ($tone) {
                        'dark' => 'light',
                        'light' => 'dark',
                        default => $tone,
                    };
                    $value = $card['value'] ?? 0;
                    $rawIcon = trim((string) ($card['icon'] ?? ''));
                    $isAbsoluteIconUrl = preg_match('~^(?:https?:)?//|^data:|^/~i', $rawIcon) === 1;
                    $isImagePath = preg_match('~\.(svg|png|jpe?g|gif|webp)(\?.*)?$~i', $rawIcon) === 1;
                    $iconSrc = base_url('assets/custom/images/svgs/icon-connect.svg');

                    if ($rawIcon !== '') {
                        if ($isAbsoluteIconUrl) {
                            $iconSrc = $rawIcon;
                        } elseif ($isImagePath) {
                            $iconSrc = base_url(ltrim(preg_replace('~^\./~', '', $rawIcon), '/'));
                        }
                    }

                    $displayValue = is_numeric($value)
                        ? number_format((float) $value, 0, ',', '.')
                        : (string) $value;
                @endphp
                <div class="item">
                    <div class="card border-0 zoom-in bg-{{ $tone }}-subtle shadow-none">
                        <div class="card-body">
                            <div class="text-center">
                                <img src="{{ $iconSrc }}" class="mb-3" alt="{{ $card['label'] ?? 'Data' }}"/>
                                <p class="fw-medium fs-3 text-{{ $textTone }} mb-1">{{ $card['label'] ?? 'Data' }}</p>
                                <h4 class="fw-semibold text-dark fs-8 mb-0"><b>{{ $displayValue }}</b></h4>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
