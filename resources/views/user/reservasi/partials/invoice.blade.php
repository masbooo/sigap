<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dokumen Pembayaran' }}</title>
    <style>
        :root {
            color-scheme: light;
            --page-bg: #eef4ff;
            --panel-bg: #ffffff;
            --text-main: #24324a;
            --text-muted: #66758f;
            --border-soft: #d8e2ff;
            --brand: #5d87ff;
            --brand-soft: #e7efff;
            --success: #00b894;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
        }

        .payment-preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 24px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(93, 135, 255, 0.12);
        }

        .payment-preview-toolbar__title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .payment-preview-toolbar__hint {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .payment-preview-toolbar__actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .payment-preview-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid var(--brand);
            background: #fff;
            color: var(--brand);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .payment-preview-button--primary {
            background: var(--brand);
            color: #fff;
        }

        .payment-preview-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        .payment-preview-card {
            width: 100%;
            background: var(--panel-bg);
            border-radius: 28px;
            box-shadow: 0 28px 70px rgba(40, 72, 140, 0.14);
            overflow: hidden;
        }

        .va-document {
            padding: 40px 38px 34px;
            color: #111111;
            background: #ffffff;
            font-family: "Times New Roman", Times, serif;
        }

        .va-document__header {
            text-align: center;
        }

        .va-document__logo {
            margin: 0 auto 10px;
            width: 62px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .va-document__logo svg,
        .va-document__logo img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .va-document__gov,
        .va-document__agency,
        .va-document__title {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.15;
        }

        .va-document__gov,
        .va-document__agency {
            font-size: 14px;
            text-transform: uppercase;
        }

        .va-document__title {
            font-size: 15px;
            text-transform: uppercase;
        }

        .va-document__subtitle {
            margin: 4px 0 0;
            font-size: 13px;
            color: #111111;
            line-height: 1.2;
        }

        .va-document__divider {
            margin: 12px 0 18px;
            border: 0;
            border-top: 2px solid #4b4b4b;
            height: 0;
        }

        .va-document__summary-box {
            border: 1px solid #6d6d6d;
            padding: 10px 14px;
        }

        .va-document__summary-line,
        .va-document__detail-line {
            display: grid;
            grid-template-columns: 118px 14px minmax(0, 1fr);
            gap: 6px;
            align-items: start;
            font-size: 13px;
            line-height: 1.35;
        }

        .va-document__summary-line + .va-document__summary-line,
        .va-document__detail-line + .va-document__detail-line {
            margin-top: 2px;
        }

        .va-document__summary-line dt,
        .va-document__summary-line dd,
        .va-document__detail-line dt,
        .va-document__detail-line dd {
            margin: 0;
        }

        .va-document__summary-line dt,
        .va-document__detail-line dt {
            font-weight: 700;
        }

        .va-document__section {
            margin-top: 18px;
        }

        .va-document__section-heading {
            margin: 0 0 6px;
            font-size: 14px;
            font-weight: 700;
        }

        .va-document__section-divider {
            border: 0;
            border-top: 1px solid #6d6d6d;
            margin: 0 0 10px;
            height: 0;
        }

        .va-document__detail-list {
            display: grid;
            gap: 2px;
        }

        .va-document__detail-value-stack {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .va-document__footnote {
            margin-top: 26px;
            padding-top: 10px;
            border-top: 2px solid #4b4b4b;
            font-size: 12px;
            line-height: 1.55;
        }

        .qris-document {
            max-width: 680px;
            margin: 0 auto;
            padding: 40px 32px 44px;
            text-align: center;
        }

        .qris-document__icon {
            width: 118px;
            height: 118px;
            margin: 0 auto 22px;
            border-radius: 50%;
            border: 5px solid rgba(0, 184, 148, 0.24);
            color: rgba(0, 184, 148, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: 700;
            line-height: 1;
        }

        .qris-document__title {
            margin: 0 0 24px;
            font-size: 54px;
            font-weight: 300;
            line-height: 1.08;
            color: #2d2d2d;
        }

        .qris-document__meta {
            margin: 0 auto 26px;
            max-width: 540px;
            display: grid;
            gap: 10px;
        }

        .qris-document__meta-item {
            font-size: 23px;
            line-height: 1.4;
            color: #303030;
        }

        .qris-document__meta-item strong {
            font-weight: 800;
        }

        .qris-document__qr-frame {
            margin: 10px auto 24px;
            width: min(100%, 420px);
            padding: 18px;
            border-radius: 26px;
            background: #fff;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.04);
        }

        .qris-document__qr-frame img {
            width: 100%;
            display: block;
            border-radius: 12px;
        }

        .qris-document__hint {
            margin: 0;
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.7;
        }

        @media print {
            body {
                background: #fff;
            }

            .payment-preview-toolbar {
                display: none !important;
            }

            .payment-preview-shell {
                max-width: none;
                padding: 0;
            }

            .payment-preview-card {
                box-shadow: none;
                border-radius: 0;
            }
        }

        @media (max-width: 767.98px) {
            .payment-preview-toolbar {
                padding: 14px 16px;
                align-items: flex-start;
                flex-direction: column;
            }

            .payment-preview-shell {
                padding: 20px 14px 36px;
            }

            .va-document {
                padding: 26px 20px 24px;
            }

            .va-document__gov {
                font-size: 13px;
            }

            .va-document__agency {
                font-size: 13px;
            }

            .va-document__title {
                font-size: 14px;
            }

            .va-document__summary-line,
            .va-document__detail-line {
                grid-template-columns: 100px 12px minmax(0, 1fr);
                font-size: 12px;
            }

            .qris-document {
                padding: 28px 16px 32px;
            }

            .qris-document__title {
                font-size: 38px;
            }

            .qris-document__meta-item {
                font-size: 19px;
            }
        }
    </style>
</head>
<body>
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
                <button type="button" class="payment-preview-button" onclick="window.print()">Cetak Browser</button>
                <button type="button" class="payment-preview-button" onclick="window.close()">Tutup</button>
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
</body>
</html>
