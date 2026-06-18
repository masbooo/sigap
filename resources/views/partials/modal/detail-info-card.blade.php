@php
    $detailInfoColClass = trim((string) ($detailInfoColClass ?? 'col-md-6'));
    $detailInfoLabel = trim((string) ($detailInfoLabel ?? ''));
    $detailInfoValue = $detailInfoValue ?? '-';
    $detailInfoValueId = trim((string) ($detailInfoValueId ?? ''));
    $detailInfoIcon = trim((string) ($detailInfoIcon ?? 'ti ti-circle'));
    $detailInfoTone = trim((string) ($detailInfoTone ?? 'bg-secondary-subtle text-secondary'));
    $detailInfoValueClass = trim((string) ($detailInfoValueClass ?? 'fw-semibold'));
    $detailInfoWrapperId = trim((string) ($detailInfoWrapperId ?? ''));
    $detailInfoWrapperClass = trim((string) ($detailInfoWrapperClass ?? ''));
@endphp

<div
    class="{{ trim($detailInfoColClass . ' ' . $detailInfoWrapperClass) }}"
    @if ($detailInfoWrapperId !== '') id="{{ $detailInfoWrapperId }}" @endif
>
    <div class="detail-info-card">
        <div class="detail-info-icon {{ $detailInfoTone }}">
            <i class="{{ $detailInfoIcon }}"></i>
        </div>
        <div class="detail-info-text">
            <small>{{ $detailInfoLabel }}</small>
            <div @if ($detailInfoValueId !== '') id="{{ $detailInfoValueId }}" @endif class="{{ $detailInfoValueClass }}">
                {{ $detailInfoValue }}
            </div>
        </div>
    </div>
</div>
