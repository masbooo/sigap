<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dokumen Pembayaran' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/main/css/styles.css') }}">
</head>
<body class="payment-preview-page">
    @php
        $downloadButtonLabel = ($document['method'] ?? '') === 'qris' ? 'Unduh SVG' : 'Unduh DOC';
        $previewHint = ($document['method'] ?? '') === 'qris'
            ? 'QRIS reservasi siap ditinjau atau diunduh sebagai gambar.'
            : 'Dokumen Virtual Account siap ditinjau atau diunduh.';
        $pemkotLogoMarkup = '';
        $pemkotLogoPath = function_exists('legacy_first_existing_asset_path')
            ? (legacy_first_existing_asset_path('custom/images/logos/pemkot.svg') ?? '')
            : '';
        if ($pemkotLogoPath !== '' && is_file($pemkotLogoPath)) {
            $pemkotLogoMarkup = preg_replace('/<\?xml.*?\?>\s*/', '', (string) file_get_contents($pemkotLogoPath));
        }
        $vaFooterNote = 'Pembayaran harus dilakukan sebelum batas waktu Virtual Account berakhir. Setelah pembayaran berhasil, simpan bukti transaksi untuk proses verifikasi.';
    @endphp
    @if (empty($isDownloadMode))
        <div class="payment-preview-toolbar">
            <div>
                <h1 class="payment-preview-toolbar__title">{{ $document['preview_title'] ?? 'Dokumen Pembayaran' }}</h1>
                <p class="payment-preview-toolbar__hint">Reservasi {{ $document['reservation_code_label'] ?? '-' }}. {{ $previewHint }}</p>
            </div>
            <div class="payment-preview-toolbar__actions">
                <a href="{{ $downloadUrl }}" class="payment-preview-button payment-preview-button--primary">{{ $downloadButtonLabel }}</a>
                <button type="button" class="payment-preview-button" data-payment-preview-action="print">Cetak Browser</button>
                <button type="button" class="payment-preview-button" data-payment-preview-action="close">Tutup</button>
            </div>
        </div>
    @endif

    <main class="payment-preview-shell">
        <div class="payment-preview-card">
            @if (($document['method'] ?? '') === 'va')
                <section class="va-document">
                    <div class="va-document__header">
                        @if ($pemkotLogoMarkup !== '')
                            <div class="va-document__logo" aria-hidden="true">{!! $pemkotLogoMarkup !!}</div>
                        @endif
                        <p class="va-document__gov">PEMERINTAH KOTA SURABAYA</p>
                        <p class="va-document__agency">BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH</p>
                        <p class="va-document__title">INFORMASI RETRIBUSI SEWA GEDUNG SERBA GUNA</p>
                        <p class="va-document__subtitle">Melalui Virtual Account (VA)</p>
                    </div>

                    <hr class="va-document__divider">

                    <div class="va-document__summary-box">
                        <dl class="va-document__summary-line">
                            <dt>Bank</dt>
                            <dd>:</dd>
                            <dd>{{ $document['va_bank_name'] ?? 'Bank Jatim' }}</dd>
                        </dl>
                        <dl class="va-document__summary-line">
                            <dt>Nomor VA</dt>
                            <dd>:</dd>
                            <dd>{{ $document['virtual_account_number'] ?? '-' }}</dd>
                        </dl>
                        <dl class="va-document__summary-line">
                            <dt>Waktu Kedaluwarsa VA</dt>
                            <dd>:</dd>
                            <dd>{{ $document['va_expiry_label'] ?? '-' }}</dd>
                        </dl>
                    </div>

                    <div class="va-document__section">
                        <p class="va-document__section-heading">Informasi Reservasi</p>
                        <hr class="va-document__section-divider">
                        <div class="va-document__detail-list">
                            <dl class="va-document__detail-line">
                                <dt>Kode Booking</dt>
                                <dd>:</dd>
                                <dd>{{ $document['reservation_code_label'] ?? '-' }}</dd>
                            </dl>
                            <dl class="va-document__detail-line">
                                <dt>Nama Pemohon</dt>
                                <dd>:</dd>
                                <dd>{{ $document['requester_name'] ?? '-' }}</dd>
                            </dl>
                            <dl class="va-document__detail-line">
                                <dt>Gedung</dt>
                                <dd>:</dd>
                                <dd>{{ $document['building_name'] ?? '-' }}</dd>
                            </dl>
                            <dl class="va-document__detail-line">
                                <dt>Tanggal Acara</dt>
                                <dd>:</dd>
                                <dd>{{ $document['event_date_label'] ?? '-' }}</dd>
                            </dl>
                            <dl class="va-document__detail-line">
                                <dt>Acara</dt>
                                <dd>:</dd>
                                <dd>{{ $document['event_name'] ?? '-' }}</dd>
                            </dl>
                            <dl class="va-document__detail-line">
                                <dt>Sesi</dt>
                                <dd>:</dd>
                                <dd>{{ $document['session_label'] ?? '-' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <p class="va-document__footnote">{{ $vaFooterNote }}</p>
                </section>
            @else
                <section class="qris-document">
                    <div class="qris-document__icon">i</div>
                    <h2 class="qris-document__title">QRIS Pembayaran Berhasil Dibuat</h2>

                    <div class="qris-document__meta">
                        <div class="qris-document__meta-item"><strong>Kode Booking:</strong> {{ $document['reservation_code_label'] ?? '-' }}</div>
                        <div class="qris-document__meta-item"><strong>Total Pembayaran:</strong> {{ $document['total_price_compact_label'] ?? '-' }}</div>
                        <div class="qris-document__meta-item"><strong>Masa Berlaku:</strong> {{ $document['qris_expiry_label'] ?? '-' }}</div>
                    </div>

                    <div class="qris-document__qr-frame">
                        @if (!empty($document['qris_image_href']) || !empty($document['qris_sample_data_uri']))
                            <img src="{{ $document['qris_image_href'] ?? $document['qris_sample_data_uri'] }}" alt="QRIS Pembayaran">
                        @else
                            <div class="qris-document__hint">QRIS tidak tersedia.</div>
                        @endif
                    </div>

                    <p class="qris-document__hint">
                        Gunakan QRIS ini untuk menyelesaikan pembayaran reservasi <strong>{{ $document['reservation_code_label'] ?? '-' }}</strong>
                        sebesar <strong>{{ $document['total_price_label'] ?? '-' }}</strong> sebelum masa berlaku berakhir.
                    </p>
                </section>
            @endif
        </div>
    </main>
    <script src="{{ asset('assets/main/js/view-bayar.js') }}"></script>
</body>
</html>
