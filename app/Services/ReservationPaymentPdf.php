<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationPaymentPdf
{
    private const PAGE_WIDTH = 612.00;
    private const PAGE_HEIGHT = 792.00;
    private const MARGIN_LEFT = 79.00;
    private const MARGIN_RIGHT = 79.00;
    private const MARGIN_TOP = 26.00;
    private const LINE_HEIGHT = 15.00;

    private string $content = '';
    private string $documentTitle = 'va-pembayaran';
    /** @var array<string, array{data: string, width: int, height: int, bits: int, colorSpace: string}> */
    private array $images = [];

    public function outputInline(array $data, string $filename = 'va-pembayaran.pdf'): void
    {
        $safeFilename = $this->sanitizeFilename($filename);
        $this->documentTitle = preg_replace('/\.pdf$/i', '', $safeFilename) ?: 'va-pembayaran';
        $pdf = $this->render($data);

        $this->throwPdfResponse($pdf, 'inline', $safeFilename);
    }

    public function outputDownload(array $data, string $filename = 'va-pembayaran.pdf'): void
    {
        $safeFilename = $this->sanitizeFilename($filename);
        $this->documentTitle = preg_replace('/\.pdf$/i', '', $safeFilename) ?: 'va-pembayaran';
        $pdf = $this->render($data);

        $this->throwPdfResponse($pdf, 'attachment', $safeFilename);
    }

    private function throwPdfResponse(string $pdf, string $disposition, string $filename): never
    {
        throw new HttpResponseException(
            response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $this->buildContentDispositionValue($disposition, $filename))
                ->header('X-File-Name', $filename)
                ->header('Content-Length', (string) strlen($pdf))
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public')
        );
    }

    public function render(array $data): string
    {
        $this->content = '';
        $this->images = [];

        $pageLeft = 56.00;
        $pageRight = self::PAGE_WIDTH - $pageLeft;
        $contentWidth = $pageRight - $pageLeft;
        $sectionHeaderHeight = 28.00;
        $sectionGap = 18.00;
        $sectionPaddingX = 14.00;
        $cardRadius = 6.00;
        $cardBorderWidth = 0.85;
        $gridBorderWidth = 0.65;
        $cardBorderGray = 0.26;
        $gridBorderGray = 0.42;
        $headerFillGray = 0.94;
        $logoPath = function_exists('legacy_first_existing_asset_path')
            ? (legacy_first_existing_asset_path('custom/images/logos/pemkot-va.jpg') ?? '')
            : '';
        $logoName = null;
        $headerLogoAspectRatio = 150 / 193;

        if ($logoPath !== '' && is_file($logoPath)) {
            $logoName = $this->registerJpegImage($logoPath, 'Im1');
            if ($logoName !== null) {
                $headerLogoAspectRatio = $this->images[$logoName]['width'] / max(1, $this->images[$logoName]['height']);
            }
        }

        $headerGridLeft = $pageLeft;
        $headerGridRight = $pageRight;
        $headerGridTop = 16.00;
        $headerDividerY = 94.00;
        $headerGridHeight = $headerDividerY - $headerGridTop;
        $headerGridUnitWidth = ($headerGridRight - $headerGridLeft) / 12;
        $headerContentTopPadding = 6.00;
        $headerContentBottomPadding = 6.00;
        $headerContentTop = $headerGridTop + $headerContentTopPadding;
        $headerContentHeight = $headerGridHeight - $headerContentTopPadding - $headerContentBottomPadding;
        $headerLogoPaddingX = 8.00;
        $headerLogoGridUnits = (($headerContentHeight * $headerLogoAspectRatio) + ($headerLogoPaddingX * 2)) <= ($headerGridUnitWidth * 2)
            ? 2.00
            : 3.00;
        $headerLogoGridLeft = $headerGridLeft;
        $headerLogoGridWidth = $headerGridUnitWidth * $headerLogoGridUnits;
        $headerTextGridLeft = $headerLogoGridLeft + $headerLogoGridWidth;
        $headerTextGridWidth = $headerGridRight - $headerTextGridLeft;
        $headerLogoMaxWidth = $headerLogoGridWidth - ($headerLogoPaddingX * 2);
        $headerLogoMaxHeight = $headerContentHeight;
        $headerTextCenterX = $headerTextGridLeft + ($headerTextGridWidth / 2);
        $headerTextPaddingX = 12.00;
        $headerTextMaxWidth = $headerTextGridWidth - ($headerTextPaddingX * 2);
        $headerTextLines = [
            ['text' => 'PEMERINTAH KOTA SURABAYA', 'font' => 'F2', 'size' => 14.26, 'gapAfter' => 2.80],
            ['text' => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH', 'font' => 'F2', 'size' => 14.26, 'gapAfter' => 2.40],
            ['text' => 'INFORMASI RETRIBUSI SEWA GEDUNG SERBA GUNA', 'font' => 'F2', 'size' => 12.18, 'gapAfter' => 1.80],
            ['text' => 'Melalui Virtual Account (VA)', 'font' => 'F1', 'size' => 10.34, 'gapAfter' => 0.00],
        ];
        $headerTextScale = 1.00;
        $headerTextBlockHeight = 0.00;

        foreach ($headerTextLines as $headerTextLine) {
            $headerTextWidth = $this->measureTextWidth($headerTextLine['text'], $headerTextLine['size'], $headerTextLine['font']);
            if ($headerTextWidth > $headerTextMaxWidth && $headerTextWidth > 0.0) {
                $headerTextScale = min($headerTextScale, $headerTextMaxWidth / $headerTextWidth);
            }
        }

        foreach ($headerTextLines as &$headerTextLine) {
            $headerTextLine['size'] *= $headerTextScale;
            $headerTextLine['gapAfter'] *= $headerTextScale;
            $headerTextBlockHeight += $headerTextLine['size'] + $headerTextLine['gapAfter'];
        }
        unset($headerTextLine);

        $headerTextTop = $headerContentTop + (($headerContentHeight - $headerTextBlockHeight) / 2);
        $headerTextBaselineFactor = 0.82;

        if ($logoName !== null) {
            $headerLogoWidth = min($headerLogoMaxWidth, $headerLogoMaxHeight * $headerLogoAspectRatio);
            $headerLogoHeight = $headerLogoWidth / max(0.01, $headerLogoAspectRatio);

            if ($headerLogoHeight > $headerLogoMaxHeight) {
                $headerLogoHeight = $headerLogoMaxHeight;
                $headerLogoWidth = $headerLogoHeight * $headerLogoAspectRatio;
            }

            $headerLogoX = $headerLogoGridLeft + (($headerLogoGridWidth - $headerLogoWidth) / 2);
            $headerLogoY = $headerContentTop + (($headerContentHeight - $headerLogoHeight) / 2);
            $this->drawImage(
                $logoName,
                $headerLogoX,
                $headerLogoY,
                $headerLogoWidth,
                $headerLogoHeight
            );
        }

        $headerTextY = $headerTextTop;
        foreach ($headerTextLines as $headerTextLine) {
            $this->drawCentered(
                $headerTextCenterX,
                $headerTextY + ($headerTextLine['size'] * $headerTextBaselineFactor),
                $headerTextLine['text'],
                $headerTextLine['font'],
                $headerTextLine['size']
            );

            $headerTextY += $headerTextLine['size'] + $headerTextLine['gapAfter'];
        }

        $this->drawLine($pageLeft, $headerDividerY, $pageRight, $headerDividerY, 1.10, 0.18);

        // ===== START BLOCK 1: ROUNDED CARD INFORMASI PEMBAYARAN =====
        // Batas utama block ini:
        // - Top    : `$summaryTop`
        // - Height : `$summaryBoxHeight`
        // - Bottom : `$summaryBottom`
        // Geser block ini dengan mengubah `$summaryTop`.
        // Ubah tinggi rounded box ini dengan mengubah `$summaryBoxHeight`.
        $summaryRows = [
            ['label' => 'Bank', 'value' => (string) ($data['va_bank_name'] ?? '-')],
            ['label' => 'Nomor VA', 'value' => (string) ($data['virtual_account_number'] ?? '-')],
            ['label' => 'Waktu Kedaluwarsa VA', 'value' => (string) ($data['va_expiry_label'] ?? '-')],
        ];
        $summaryTop = 112.00;
        $summaryLabelX = $pageLeft + $sectionPaddingX;
        $summaryColonX = $pageLeft + 138.00;
        $summaryValueX = $pageLeft + 150.00;
        $summaryFontSize = 10.40;
        $summaryLineHeight = 14.80;
        $summaryRowGap = 4.00;
        $summaryContentTopPadding = 16.00;
        $summaryContentBottomPadding = 0.00;
        $summaryValueWidth = $pageRight - $sectionPaddingX - $summaryValueX;
        $summaryContentHeight = 0.00;

        foreach ($summaryRows as $index => $row) {
            $summaryContentHeight += $this->measureParagraphHeight(
                (string) $row['value'],
                $summaryValueWidth,
                $summaryFontSize,
                'F1',
                $summaryLineHeight
            );
            if ($index < count($summaryRows) - 1) {
                $summaryContentHeight += $summaryRowGap;
            }
        }

        $summaryBoxHeight = $sectionHeaderHeight + $summaryContentTopPadding + $summaryContentHeight + $summaryContentBottomPadding;
        $summaryBottom = $summaryTop + $summaryBoxHeight;

        // Rounded border block 1 digambar dari `$summaryTop` sampai `$summaryBottom`.
        $this->drawRoundedRect($pageLeft, $summaryTop, $contentWidth, $summaryBoxHeight, $cardRadius, $cardBorderWidth, $cardBorderGray);
        $this->drawLine($pageLeft, $summaryTop + $sectionHeaderHeight, $pageRight, $summaryTop + $sectionHeaderHeight, $gridBorderWidth, $gridBorderGray);
        $this->drawText($pageLeft + 14.00, $summaryTop + 18.00, 'Informasi Pembayaran', 'F2', 12.50);

        $summaryY = $summaryTop + $sectionHeaderHeight + $summaryContentTopPadding;
        foreach ($summaryRows as $row) {
            $rowBottomY = $this->drawField(
                $summaryLabelX,
                $summaryColonX,
                $summaryValueX,
                $summaryY,
                (string) $row['label'],
                (string) $row['value'],
                $summaryValueWidth,
                $summaryFontSize,
                'F2',
                'F1',
                $summaryLineHeight
            );

            $summaryY = $rowBottomY + $summaryRowGap;
        }

        // ===== END BLOCK 1: ROUNDED CARD INFORMASI PEMBAYARAN =====

        // ===== START BLOCK 2: ROUNDED CARD RINCIAN PEMBAYARAN =====
        // Batas utama block ini:
        // - Top    : `$tableTop`
        // - Height : `$tableSectionHeight`
        // - Bottom : `$tableBottom`
        // Geser block ini dengan mengubah `$tableTop`.
        // Ubah tinggi rounded box ini dengan mengubah `$tableSectionHeight` atau `$tableRowHeight`.
        // Posisi block ini sekarang mengikuti block 1 lewat `$summaryBottom + $sectionGap`.
        $tableTop = $summaryBottom + $sectionGap;
        $tableRowHeight = 38.00;
        $tableGridLeft = $pageLeft;
        $tableGridRight = $pageRight;
        $tableHeaderTop = $tableTop + $sectionHeaderHeight;
        $tableGridMid = $tableHeaderTop + $tableRowHeight;
        $tableGridBottom = $tableGridMid + $tableRowHeight;
        $tableSectionHeight = $tableGridBottom - $tableTop;
        $tableBottom = $tableTop + $tableSectionHeight;
        $tableGridWidth = $tableGridRight - $tableGridLeft;
        $tableColumnWidth = $tableGridWidth / 4;
        $tableSideBorderOffset = max(0.0, ($cardBorderWidth - $gridBorderWidth) / 2);
        $tableSideBorderBottom = $tableGridBottom - $cardRadius + ($gridBorderWidth / 2);
        $tableColumns = [
            $tableGridLeft,
            $tableGridLeft + $tableColumnWidth,
            $tableGridLeft + ($tableColumnWidth * 2),
            $tableGridLeft + ($tableColumnWidth * 3),
            $tableGridRight,
        ];

        // Rounded border block 2 digambar dari `$tableTop` sampai `$tableBottom`.
        $this->drawRoundedRect($pageLeft, $tableTop, $contentWidth, $tableSectionHeight, $cardRadius, $cardBorderWidth, $cardBorderGray);
        $this->drawFilledRect($tableGridLeft, $tableHeaderTop, $tableGridWidth, $tableGridMid - $tableHeaderTop, $headerFillGray);
        $this->drawLine($pageLeft, $tableHeaderTop, $pageRight, $tableHeaderTop, $gridBorderWidth, $gridBorderGray);
        $this->drawLine($tableGridLeft, $tableGridMid, $tableGridRight, $tableGridMid, $gridBorderWidth, $gridBorderGray);
        $this->drawLine($tableGridLeft + $tableSideBorderOffset, $tableHeaderTop, $tableGridLeft + $tableSideBorderOffset, $tableSideBorderBottom, $gridBorderWidth, $gridBorderGray);
        $this->drawLine($tableGridRight - $tableSideBorderOffset, $tableHeaderTop, $tableGridRight - $tableSideBorderOffset, $tableSideBorderBottom, $gridBorderWidth, $gridBorderGray);
        $this->drawText($pageLeft + 14.00, $tableTop + 18.00, 'Rincian Pembayaran', 'F2', 12.50);

        for ($index = 1; $index < count($tableColumns) - 1; $index += 1) {
            $this->drawLine($tableColumns[$index], $tableHeaderTop, $tableColumns[$index], $tableGridBottom, $gridBorderWidth, $gridBorderGray);
        }

        $tableHeaders = [
            'Kode Booking',
            'Tarif Sewa (Rp)',
            'Tarif Overtime (Rp)',
            'Jumlah Total (Rp)',
        ];
        $tableHeaderFontSize = 10.10;
        $tableValueFontSize = 9.60;
        $tableTotalFontSize = 12.20;
        $tableHeaderY = $tableHeaderTop + (($tableRowHeight + $tableHeaderFontSize) / 2) - 1.60;
        for ($index = 0; $index < count($tableHeaders); $index += 1) {
            $this->drawCentered(
                ($tableColumns[$index] + $tableColumns[$index + 1]) / 2,
                $tableHeaderY,
                $tableHeaders[$index],
                'F2',
                $tableHeaderFontSize
            );
        }

        $tableValueY = $tableGridMid + (($tableRowHeight + $tableValueFontSize) / 2) - 1.40;
        $tableTotalValueY = $tableGridMid + (($tableRowHeight + $tableTotalFontSize) / 2) - 1.40;
        $tableValues = [
            (string) ($data['reservation_code_label'] ?? '-'),
            (string) ($data['tarif_sewa_label'] ?? '-'),
            (string) ($data['tarif_overtime_label'] ?? '-'),
            (string) ($data['total_price_plain_label'] ?? '-'),
        ];
        for ($index = 0; $index < count($tableValues); $index += 1) {
            $this->drawCentered(
                ($tableColumns[$index] + $tableColumns[$index + 1]) / 2,
                $index === 3 ? $tableTotalValueY : $tableValueY,
                $tableValues[$index],
                $index === 3 ? 'F2' : 'F1',
                $index === 3 ? $tableTotalFontSize : $tableValueFontSize
            );
        }

        // ===== END BLOCK 2: ROUNDED CARD RINCIAN PEMBAYARAN =====

        // ===== START BLOCK 3: ROUNDED CARD INFORMASI RESERVASI =====
        // Batas utama block ini:
        // - Top    : `$detailTop`
        // - Height : `$detailBoxHeight`
        // - Bottom : `$detailBottom`
        // Geser block ini dengan mengubah `$detailTop`.
        // Ubah tinggi rounded box ini dengan mengubah `$detailBoxHeight`.
        // Posisi block ini sekarang mengikuti block 2 lewat `$tableBottom + $sectionGap`.
        $detailRows = [
            ['label' => 'Nama Pemohon', 'value' => (string) ($data['requester_name'] ?? '-')],
            ['label' => 'Gedung', 'value' => (string) ($data['building_name'] ?? '-')],
            ['label' => 'Tanggal Acara', 'value' => (string) ($data['event_date_label'] ?? '-')],
            ['label' => 'Acara', 'value' => (string) ($data['event_name'] ?? '-')],
            ['label' => 'Sesi', 'value' => (string) ($data['session_label'] ?? '-')],
        ];
        $detailTop = $tableBottom + $sectionGap;
        $detailLabelX = $pageLeft + $sectionPaddingX;
        $detailColonX = $summaryColonX;
        $detailValueX = $summaryValueX;
        $detailValueWidth = $pageRight - $sectionPaddingX - $detailValueX;
        $detailFontSize = 10.30;
        $detailLineHeight = 14.60;
        $detailRowGap = 5.00;
        $detailContentTopPadding = 16.00;
        $detailContentBottomPadding = 0.00;
        $detailContentHeight = 0.00;

        foreach ($detailRows as $index => $row) {
            $detailContentHeight += $this->measureParagraphHeight(
                (string) $row['value'],
                $detailValueWidth,
                $detailFontSize,
                'F1',
                $detailLineHeight
            );
            if ($index < count($detailRows) - 1) {
                $detailContentHeight += $detailRowGap;
            }
        }

        $detailBoxHeight = $sectionHeaderHeight + $detailContentTopPadding + $detailContentHeight + $detailContentBottomPadding;
        $detailBottom = $detailTop + $detailBoxHeight;

        // Rounded border block 3 digambar dari `$detailTop` sampai `$detailBottom`.
        $this->drawRoundedRect($pageLeft, $detailTop, $contentWidth, $detailBoxHeight, $cardRadius, $cardBorderWidth, $cardBorderGray);
        $this->drawLine($pageLeft, $detailTop + $sectionHeaderHeight, $pageRight, $detailTop + $sectionHeaderHeight, $gridBorderWidth, $gridBorderGray);
        $this->drawText($pageLeft + 14.00, $detailTop + 18.00, 'Informasi Reservasi', 'F2', 12.50);

        $detailY = $detailTop + $sectionHeaderHeight + $detailContentTopPadding;
        foreach ($detailRows as $row) {
            $rowBottomY = $this->drawField(
                $detailLabelX,
                $detailColonX,
                $detailValueX,
                $detailY,
                (string) $row['label'],
                (string) $row['value'],
                $detailValueWidth,
                $detailFontSize,
                'F2',
                'F1',
                $detailLineHeight
            );

            $detailY = $rowBottomY + $detailRowGap;
        }

        // ===== END BLOCK 3: ROUNDED CARD INFORMASI RESERVASI =====

        $notes = array_values(array_filter(
            array_map(
                static fn($note): string => trim((string) $note),
                (array) ($data['notes'] ?? [])
            ),
            static fn(string $note): bool => $note !== ''
        ));

        if ($notes === []) {
            $notes = ['-'];
        }

        // ===== START BLOCK 4: ROUNDED CARD CATATAN PENTING =====
        // Batas utama block ini:
        // - Top    : `$notesTop`
        // - Height : `$notesBoxHeight`
        // - Bottom : `$notesBottom`
        // Geser block ini dengan mengubah `$notesTop`.
        // Ubah tinggi rounded box ini dengan mengubah `$notesBoxHeight`.
        // Posisi block ini sekarang mengikuti block 3 lewat `$detailBottom + $sectionGap`.
        $notesTop = $detailBottom + $sectionGap;
        $noteBulletCenterX = $pageLeft + 15.00;
        $noteTextX = $pageLeft + 26.00;
        $noteRightPadding = 10.00;
        $noteFontSize = 10.00;
        $noteLineHeight = 12.20;
        $noteGap = 5.00;
        $noteContentTopPadding = 14.00;
        $noteContentBottomPadding = 0.00;
        $noteWidth = $pageRight - $noteRightPadding - $noteTextX;
        $notesContentHeight = 0.00;

        foreach ($notes as $index => $note) {
            $notesContentHeight += $this->measureParagraphHeight($note, $noteWidth, $noteFontSize, 'F1', $noteLineHeight);
            if ($index < count($notes) - 1) {
                $notesContentHeight += $noteGap;
            }
        }

        $notesBoxHeight = $sectionHeaderHeight + $noteContentTopPadding + $notesContentHeight + $noteContentBottomPadding;
        $notesBottom = $notesTop + $notesBoxHeight;

        // Rounded border block 4 digambar dari `$notesTop` sampai `$notesBottom`.
        $this->drawRoundedRect($pageLeft, $notesTop, $contentWidth, $notesBoxHeight, $cardRadius, $cardBorderWidth, $cardBorderGray);
        $this->drawLine($pageLeft, $notesTop + $sectionHeaderHeight, $pageRight, $notesTop + $sectionHeaderHeight, $gridBorderWidth, $gridBorderGray);
        $this->drawText($pageLeft + 14.00, $notesTop + 18.00, 'Catatan Penting', 'F2', 12.50);

        $noteY = $notesTop + $sectionHeaderHeight + $noteContentTopPadding;
        foreach ($notes as $note) {
            if ($note !== '-') {
                $this->drawFilledCircle($noteBulletCenterX, $noteY - 3.00, 2.00);
            }

            $noteY = $this->drawParagraph($noteTextX, $noteY, $note, $noteWidth, 'F1', $noteFontSize, $noteLineHeight) + $noteGap;
        }

        // ===== END BLOCK 4: ROUNDED CARD CATATAN PENTING =====

        $footerTop = $notesBottom + 20.00;
        $this->drawLine($pageLeft, $footerTop, $pageRight, $footerTop, 0.90, $gridBorderGray);

        $footerText = trim((string) ($data['footer_note'] ?? ''));
        $footerCenterX = $pageLeft + ($contentWidth / 2);
        $footerTextWidth = $contentWidth - 44.00;
        $footerFontSize = 9.10;
        $footerLineHeight = 14.00;
        $footerY = $footerTop + 18.00;
        if ($footerText !== '') {
            $footerLines = $this->buildFooterTextLines($footerText, $footerFontSize);
            $footerY = $this->drawCenteredStyledParagraph($footerCenterX, $footerY, $footerLines, $footerTextWidth, $footerLineHeight);
        }

        $createdLabel = trim((string) ($data['document_created_label'] ?? ''));
        $metaY = max($footerY + 16.00, 734.00);
        if ($createdLabel !== '') {
            $this->drawText($pageLeft, $metaY, 'Tanggal Cetak: ' . $createdLabel, 'F1', 9.00);
            $metaY += 14.00;
        }
        $this->drawParagraph(
            $pageLeft,
            $metaY,
            'Terima kasih karena tidak memberikan imbalan dalam bentuk apa pun atas layanan yang kami berikan',
            $contentWidth,
            'F1',
            8.90,
            11.50
        );

        return $this->buildPdfDocument();
    }

    private function drawField(
        float $labelX,
        float $colonX,
        float $valueX,
        float $y,
        string $label,
        string $value,
        float $valueWidth,
        float $size = 11,
        string $labelFont = 'F2',
        string $valueFont = 'F1',
        float $lineHeight = self::LINE_HEIGHT
    ): float {
        $this->drawText($labelX, $y, $label, $labelFont, $size);
        $this->drawText($colonX, $y, ':', $valueFont, $size);

        $lines = $this->wrapText($this->normalizeValue($value), $valueWidth, $size, $valueFont);
        $currentY = $y;

        foreach ($lines as $line) {
            $this->drawText($valueX, $currentY, $line, $valueFont, $size);
            $currentY += $lineHeight;
        }

        return $currentY;
    }

    private function drawParagraph(
        float $x,
        float $y,
        string $text,
        float $width,
        string $font,
        float $size,
        float $lineHeight = self::LINE_HEIGHT
    ): float {
        $lines = $this->wrapText($text, $width, $size, $font);

        foreach ($lines as $line) {
            $this->drawText($x, $y, $line, $font, $size);
            $y += $lineHeight;
        }

        return $y;
    }

    private function measureParagraphHeight(
        string $text,
        float $width,
        float $size,
        string $font,
        float $lineHeight = self::LINE_HEIGHT
    ): float {
        return count($this->wrapText($text, $width, $size, $font)) * $lineHeight;
    }

    /**
     * @param array<int, array{text: string, font: string, size: float}> $fragments
     */
    private function drawStyledParagraph(
        float $x,
        float $y,
        array $fragments,
        float $width,
        float $lineHeight = self::LINE_HEIGHT,
        bool $justify = false
    ): float {
        $lines = $this->wrapStyledFragments($fragments, $width);
        $lastLineIndex = count($lines) - 1;

        foreach ($lines as $index => $lineTokens) {
            $this->drawStyledLine($x, $y, $lineTokens, $width, $justify && $index < $lastLineIndex);
            $y += $lineHeight;
        }

        return $y;
    }

    /**
     * @param array<int, array<int, array{text: string, font: string, size: float}>> $lines
     */
    private function drawCenteredStyledParagraph(
        float $centerX,
        float $y,
        array $lines,
        float $maxWidth,
        float $lineHeight = self::LINE_HEIGHT
    ): float {
        foreach ($lines as $lineFragments) {
            $lineWidth = $this->measureStyledFragmentsWidth($lineFragments);
            $scale = ($lineWidth > $maxWidth && $lineWidth > 0.0) ? ($maxWidth / $lineWidth) : 1.0;

            if ($scale !== 1.0) {
                foreach ($lineFragments as &$lineFragment) {
                    $lineFragment['size'] *= $scale;
                }
                unset($lineFragment);
                $lineWidth = $this->measureStyledFragmentsWidth($lineFragments);
            }

            $lineStartX = $centerX - ($lineWidth / 2);
            $this->drawTextRuns($lineStartX, $y, $lineFragments);
            $y += $lineHeight;
        }

        return $y;
    }

    /**
     * @param array<int, array{text: string, font: string, size: float}> $fragments
     * @return array<int, array<int, array{text: string, font: string, size: float, width: float, isSpace: bool}>>
     */
    private function wrapStyledFragments(array $fragments, float $maxWidth): array
    {
        $tokens = $this->tokenizeStyledFragments($fragments);
        $lines = [];
        $currentLine = [];
        $currentWidth = 0.0;

        foreach ($tokens as $token) {
            if ($token['isSpace']) {
                if ($currentLine === []) {
                    continue;
                }

                $lastToken = end($currentLine);
                if ($lastToken !== false && $lastToken['isSpace']) {
                    continue;
                }

                $currentLine[] = $token;
                $currentWidth += $token['width'];
                continue;
            }

            if ($currentLine !== [] && ($currentWidth + $token['width']) > $maxWidth) {
                $lines[] = $this->trimStyledLineTokens($currentLine);
                $currentLine = [];
                $currentWidth = 0.0;
            }

            $currentLine[] = $token;
            $currentWidth += $token['width'];
        }

        if ($currentLine !== []) {
            $lines[] = $this->trimStyledLineTokens($currentLine);
        }

        return $lines;
    }

    /**
     * @param array<int, array{text: string, font: string, size: float}> $fragments
     * @return array<int, array{text: string, font: string, size: float, width: float, isSpace: bool}>
     */
    private function tokenizeStyledFragments(array $fragments): array
    {
        $tokens = [];

        foreach ($fragments as $fragment) {
            $parts = preg_split('/(\s+)/u', $fragment['text'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($parts as $part) {
                $isSpace = preg_match('/^\s+$/u', $part) === 1;
                $tokenText = $isSpace ? ' ' : $part;

                if (
                    !$isSpace &&
                    preg_match('/^[\.,;:!\?]+$/u', $tokenText) === 1 &&
                    $tokens !== []
                ) {
                    $lastIndex = array_key_last($tokens);
                    if ($lastIndex !== null && !$tokens[$lastIndex]['isSpace']) {
                        $tokens[$lastIndex]['text'] .= $tokenText;
                        $tokens[$lastIndex]['width'] = $this->measureVisualTextWidth(
                            $tokens[$lastIndex]['text'],
                            $tokens[$lastIndex]['size'],
                            $tokens[$lastIndex]['font']
                        );
                        continue;
                    }
                }

                $tokens[] = [
                    'text' => $tokenText,
                    'font' => $fragment['font'],
                    'size' => $fragment['size'],
                    'width' => $this->measureVisualTextWidth($tokenText, $fragment['size'], $fragment['font']),
                    'isSpace' => $isSpace,
                ];
            }
        }

        return $tokens;
    }

    /**
     * @param array<int, array{text: string, font: string, size: float, width: float, isSpace: bool}> $tokens
     * @return array<int, array{text: string, font: string, size: float, width: float, isSpace: bool}>
     */
    private function trimStyledLineTokens(array $tokens): array
    {
        while ($tokens !== [] && $tokens[0]['isSpace']) {
            array_shift($tokens);
        }

        while ($tokens !== [] && $tokens[array_key_last($tokens)]['isSpace']) {
            array_pop($tokens);
        }

        return array_values($tokens);
    }

    /**
     * @param array<int, array{text: string, font: string, size: float, width: float, isSpace: bool}> $tokens
     */
    private function drawStyledLine(float $x, float $y, array $tokens, float $width, bool $justify): void
    {
        $tokens = $this->trimStyledLineTokens($tokens);
        if ($tokens === []) {
            return;
        }

        $lineWidth = 0.0;
        $spaceCount = 0;
        foreach ($tokens as $token) {
            $lineWidth += $token['width'];
            if ($token['isSpace']) {
                $spaceCount += 1;
            }
        }

        $extraSpace = ($justify && $spaceCount > 0)
            ? max(0.0, ($width - $lineWidth) / $spaceCount)
            : 0.0;

        $cursorX = $x;
        foreach ($tokens as $token) {
            if ($token['isSpace']) {
                $cursorX += $token['width'] + $extraSpace;
                continue;
            }

            $this->drawText($cursorX, $y, $token['text'], $token['font'], $token['size']);
            $cursorX += $token['width'];
        }
    }

    /**
     * @param array<int, array{text: string, font: string, size: float}> $fragments
     */
    private function measureStyledFragmentsWidth(array $fragments): float
    {
        $width = 0.0;

        foreach ($fragments as $fragment) {
            $width += $this->measureVisualTextWidth($fragment['text'], $fragment['size'], $fragment['font']);
        }

        return $width;
    }

    /**
     * @return array<int, array{text: string, font: string, size: float}>
     */
    private function buildFooterTextFragments(string $text, float $size): array
    {
        $highlights = [
            'bpkad.surabaya.go.id/cetak-ssrd' => 'F4',
            '0852-5750-5734 (WhatsApp)' => 'F2',
        ];
        $fragments = [];
        $offset = 0;
        $textLength = strlen($text);

        while ($offset < $textLength) {
            $nextPosition = null;
            $nextMatch = null;
            $nextFont = 'F1';

            foreach ($highlights as $needle => $font) {
                $position = strpos($text, $needle, $offset);
                if ($position === false) {
                    continue;
                }

                if ($nextPosition === null || $position < $nextPosition) {
                    $nextPosition = $position;
                    $nextMatch = $needle;
                    $nextFont = $font;
                }
            }

            if ($nextPosition === null || $nextMatch === null) {
                $remainingText = substr($text, $offset);
                if ($remainingText !== '') {
                    $fragments[] = ['text' => $remainingText, 'font' => 'F1', 'size' => $size];
                }
                break;
            }

            if ($nextPosition > $offset) {
                $fragments[] = [
                    'text' => substr($text, $offset, $nextPosition - $offset),
                    'font' => 'F1',
                    'size' => $size,
                ];
            }

            $fragments[] = ['text' => $nextMatch, 'font' => $nextFont, 'size' => $size];
            $offset = $nextPosition + strlen($nextMatch);
        }

        return $fragments;
    }

    /**
     * @return array<int, array<int, array{text: string, font: string, size: float}>>
     */
    private function buildFooterTextLines(string $text, float $size): array
    {
        $url = 'bpkad.surabaya.go.id/cetak-ssrd';
        $phone = '0852-5750-5734 (WhatsApp)';

        if (str_contains($text, $url) && str_contains($text, $phone)) {
            return [
                [
                    ['text' => 'Jika pembayaran telah berhasil dilakukan, Anda dapat mencetak bukti', 'font' => 'F1', 'size' => $size],
                ],
                [
                    ['text' => 'Surat Setoran Retribusi Daerah (SSRD) pada laman', 'font' => 'F1', 'size' => $size],
                    ['text' => ' ', 'font' => 'F1', 'size' => $size],
                    ['text' => $url, 'font' => 'F4', 'size' => $size],
                    ['text' => '.', 'font' => 'F1', 'size' => $size],
                ],
                [
                    ['text' => 'Untuk informasi lebih lanjut, silakan hubungi Call Center BPKAD di nomor', 'font' => 'F1', 'size' => $size],
                    ['text' => ' ', 'font' => 'F1', 'size' => $size],
                    ['text' => $phone, 'font' => 'F2', 'size' => $size],
                ],
            ];
        }

        return [$this->buildFooterTextFragments($text, $size)];
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2, float $width = 1.0, float $gray = 0.0): void
    {
        $pdfY1 = self::PAGE_HEIGHT - $y1;
        $pdfY2 = self::PAGE_HEIGHT - $y2;
        $this->content .= sprintf("q %.4f G %.2f w %.2f %.2f m %.2f %.2f l S Q\n", $gray, $width, $x1, $pdfY1, $x2, $pdfY2);
    }

    private function drawRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $strokeWidth = 1.0,
        float $gray = 0.0
    ): void
    {
        $pdfY = self::PAGE_HEIGHT - $y - $height;
        $this->content .= sprintf("q %.4f G %.2f w %.2f %.2f %.2f %.2f re S Q\n", $gray, $strokeWidth, $x, $pdfY, $width, $height);
    }

    private function drawRoundedRect(
        float $x,
        float $y,
        float $width,
        float $height,
        float $radius,
        float $strokeWidth = 1.0,
        float $gray = 0.0
    ): void {
        $radius = max(0.0, min($radius, $width / 2, $height / 2));
        if ($radius <= 0.0) {
            $this->drawRect($x, $y, $width, $height, $strokeWidth, $gray);
            return;
        }

        $kappa = 0.5522847498;
        $control = $radius * $kappa;
        $left = $x;
        $right = $x + $width;
        $bottom = self::PAGE_HEIGHT - $y - $height;
        $top = $bottom + $height;

        $this->content .= sprintf(
            "q %.4f G %.2f w %.2f %.2f m %.2f %.2f l %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f l %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f l %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f l %.2f %.2f %.2f %.2f %.2f %.2f c h S Q\n",
            $gray,
            $strokeWidth,
            $left + $radius,
            $bottom,
            $right - $radius,
            $bottom,
            $right - $radius + $control,
            $bottom,
            $right,
            $bottom + $radius - $control,
            $right,
            $bottom + $radius,
            $right,
            $top - $radius,
            $right,
            $top - $radius + $control,
            $right - $radius + $control,
            $top,
            $right - $radius,
            $top,
            $left + $radius,
            $top,
            $left + $radius - $control,
            $top,
            $left,
            $top - $radius + $control,
            $left,
            $top - $radius,
            $left,
            $bottom + $radius,
            $left,
            $bottom + $radius - $control,
            $left + $radius - $control,
            $bottom,
            $left + $radius,
            $bottom
        );
    }

    private function drawFilledRect(float $x, float $y, float $width, float $height, float $gray = 0.95): void
    {
        $pdfY = self::PAGE_HEIGHT - $y - $height;
        $this->content .= sprintf("q %.4f g %.2f %.2f %.2f %.2f re f Q\n", $gray, $x, $pdfY, $width, $height);
    }

    private function drawText(float $x, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $encoded = $this->escapePdfText($text);
        $pdfY = self::PAGE_HEIGHT - $y;
        $this->content .= "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$pdfY} Tm ({$encoded}) Tj ET\n";
    }

    /**
     * @param array<int, array{text: string, font: string, size: float}> $fragments
     */
    private function drawTextRuns(float $x, float $y, array $fragments): void
    {
        $pdfY = self::PAGE_HEIGHT - $y;
        $content = "BT 1 0 0 1 {$x} {$pdfY} Tm ";

        foreach ($fragments as $fragment) {
            $encoded = $this->escapePdfText($fragment['text']);
            $content .= "/{$fragment['font']} {$fragment['size']} Tf ({$encoded}) Tj ";
        }

        $this->content .= $content . "ET\n";
    }

    private function drawCentered(float $centerX, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $width = $this->measureVisualTextWidth($text, $size, $font);
        $this->drawText($centerX - ($width / 2), $y, $text, $font, $size);
    }

    private function drawRight(float $rightX, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $width = $this->measureTextWidth($text, $size, $font);
        $this->drawText($rightX - $width, $y, $text, $font, $size);
    }

    private function measureVisualTextWidth(string $text, float $size, string $font = 'F1'): float
    {
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $width = 0.0;
        $isBold = in_array($font, ['F2', 'F4'], true);
        $spaceFactor = $isBold ? 0.30 : 0.28;
        $thinFactor = $isBold ? 0.28 : 0.25;
        $digitFactor = 0.50;
        $dashFactor = $isBold ? 0.33 : 0.31;
        $wideFactor = $isBold ? 0.86 : 0.82;
        $upperFactor = $isBold ? 0.68 : 0.60;
        $defaultFactor = $isBold ? 0.54 : 0.48;

        foreach ($characters as $character) {
            if ($character === ' ') {
                $width += $size * $spaceFactor;
                continue;
            }

            if (preg_match('/[ilI1\.,:;!\|\(\)\[\]\'"`]/u', $character) === 1) {
                $width += $size * $thinFactor;
                continue;
            }

            if (preg_match('/(?:-|\\x{2013}|\\x{2014})/u', $character) === 1) {
                $width += $size * $dashFactor;
                continue;
            }

            if (preg_match('/[mwMW@#%&]/u', $character) === 1) {
                $width += $size * $wideFactor;
                continue;
            }

            if (preg_match('/[0-9]/u', $character) === 1) {
                $width += $size * $digitFactor;
                continue;
            }

            if (preg_match('/[A-Z]/u', $character) === 1) {
                $width += $size * $upperFactor;
                continue;
            }

            $width += $size * $defaultFactor;
        }

        return $width;
    }

    private function drawImage(string $resourceName, float $x, float $y, float $width, float $height): void
    {
        if (!isset($this->images[$resourceName])) {
            return;
        }

        $pdfY = self::PAGE_HEIGHT - $y - $height;
        $this->content .= sprintf(
            "q %.2f 0 0 %.2f %.2f %.2f cm /%s Do Q\n",
            $width,
            $height,
            $x,
            $pdfY,
            $resourceName
        );
    }

    private function drawFilledCircle(float $centerX, float $centerY, float $radius): void
    {
        $kappa = 0.5522847498;
        $control = $radius * $kappa;
        $pdfY = self::PAGE_HEIGHT - $centerY;

        $this->content .= sprintf(
            "q 0 g %.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c %.2f %.2f %.2f %.2f %.2f %.2f c f Q\n",
            $centerX + $radius,
            $pdfY,
            $centerX + $radius,
            $pdfY + $control,
            $centerX + $control,
            $pdfY + $radius,
            $centerX,
            $pdfY + $radius,
            $centerX - $control,
            $pdfY + $radius,
            $centerX - $radius,
            $pdfY + $control,
            $centerX - $radius,
            $pdfY,
            $centerX - $radius,
            $pdfY - $control,
            $centerX - $control,
            $pdfY - $radius,
            $centerX,
            $pdfY - $radius,
            $centerX + $control,
            $pdfY - $radius,
            $centerX + $radius,
            $pdfY - $control,
            $centerX + $radius,
            $pdfY
        );
    }

    private function registerJpegImage(string $path, string $resourceName): ?string
    {
        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        $info = $this->extractJpegInfo($binary);
        if ($info === null) {
            return null;
        }

        $this->images[$resourceName] = [
            'data' => $binary,
            'width' => $info['width'],
            'height' => $info['height'],
            'bits' => $info['bits'],
            'colorSpace' => $info['colorSpace'],
        ];

        return $resourceName;
    }

    /**
     * @return array{width: int, height: int, bits: int, colorSpace: string}|null
     */
    private function extractJpegInfo(string $binary): ?array
    {
        if (!str_starts_with($binary, "\xFF\xD8")) {
            return null;
        }

        $length = strlen($binary);
        $offset = 2;
        $sofMarkers = [0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF];

        while ($offset + 8 < $length) {
            if (ord($binary[$offset]) !== 0xFF) {
                $offset += 1;
                continue;
            }

            while ($offset < $length && ord($binary[$offset]) === 0xFF) {
                $offset += 1;
            }

            if ($offset >= $length) {
                break;
            }

            $marker = ord($binary[$offset]);
            $offset += 1;

            if (in_array($marker, [0xD8, 0xD9], true)) {
                continue;
            }

            if ($marker === 0xDA) {
                break;
            }

            if ($offset + 1 >= $length) {
                break;
            }

            $segmentLength = (ord($binary[$offset]) << 8) + ord($binary[$offset + 1]);
            $offset += 2;

            if ($segmentLength < 2 || ($offset + $segmentLength - 2) > $length) {
                break;
            }

            if (in_array($marker, $sofMarkers, true)) {
                $bits = ord($binary[$offset]);
                $height = (ord($binary[$offset + 1]) << 8) + ord($binary[$offset + 2]);
                $width = (ord($binary[$offset + 3]) << 8) + ord($binary[$offset + 4]);
                $components = ord($binary[$offset + 5]);

                $colorSpace = '/DeviceRGB';
                if ($components === 1) {
                    $colorSpace = '/DeviceGray';
                } elseif ($components === 4) {
                    $colorSpace = '/DeviceCMYK';
                }

                return [
                    'width' => $width,
                    'height' => $height,
                    'bits' => $bits,
                    'colorSpace' => $colorSpace,
                ];
            }

            $offset += $segmentLength - 2;
        }

        return null;
    }

    private function wrapText(string $text, float $maxWidth, float $size, string $font = 'F1'): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($normalized === '') {
            return ['-'];
        }

        $words = preg_split('/\s+/u', $normalized) ?: [];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $candidate = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            if ($this->measureTextWidth($candidate, $size, $font) <= $maxWidth) {
                $currentLine = $candidate;
                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }

            if ($this->measureTextWidth($word, $size, $font) <= $maxWidth) {
                $currentLine = $word;
                continue;
            }

            $chunks = $this->splitLongWord($word, $maxWidth, $size, $font);
            $currentLine = array_pop($chunks) ?: '';

            foreach ($chunks as $chunk) {
                $lines[] = $chunk;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines !== [] ? $lines : ['-'];
    }

    private function splitLongWord(string $word, float $maxWidth, float $size, string $font): array
    {
        $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($characters as $character) {
            $candidate = $current . $character;
            if ($current !== '' && $this->measureTextWidth($candidate, $size, $font) > $maxWidth) {
                $chunks[] = $current;
                $current = $character;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks !== [] ? $chunks : [$word];
    }

    private function measureTextWidth(string $text, float $size, string $font = 'F1'): float
    {
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $width = 0.0;
        $isBold = in_array($font, ['F2', 'F4'], true);
        $fontScale = $isBold ? 1.0 : 1.0;
        $spaceFactor = $isBold ? 0.30 : 0.28;
        $thinFactor = $isBold ? 0.28 : 0.25;
        $digitFactor = 0.50;
        $dashFactor = $isBold ? 0.33 : 0.31;
        $wideFactor = $isBold ? 0.86 : 0.82;
        $upperFactor = $isBold ? 0.68 : 0.60;
        $defaultFactor = $isBold ? 0.54 : 0.48;

        foreach ($characters as $character) {
            if ($character === ' ') {
                $width += $size * $spaceFactor * $fontScale;
                continue;
            }

            if (preg_match('/[ilI1\.,:;!\|\(\)\[\]\'"`]/u', $character) === 1) {
                $width += $size * $thinFactor * $fontScale;
                continue;
            }

            if (preg_match('/[-–—]/u', $character) === 1) {
                $width += $size * $dashFactor * $fontScale;
                continue;
            }

            if (preg_match('/[mwMW@#%&]/u', $character) === 1) {
                $width += $size * $wideFactor * $fontScale;
                continue;
            }

            if (preg_match('/[0-9]/u', $character) === 1) {
                $width += $size * $digitFactor * $fontScale;
                continue;
            }

            if (preg_match('/[A-Z]/u', $character) === 1) {
                $width += $size * $upperFactor * $fontScale;
                continue;
            }

            $width += $size * $defaultFactor * $fontScale;
        }

        return $width;
    }

    private function escapePdfText(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($encoded === false) {
            $encoded = $text;
        }

        $encoded = str_replace(["\r\n", "\r", "\n"], ' ', $encoded);
        $encoded = str_replace('\\', '\\\\', $encoded);
        $encoded = str_replace('(', '\(', $encoded);
        $encoded = str_replace(')', '\)', $encoded);

        return $encoded;
    }

    private function normalizeValue(string $value): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return $normalized !== '' ? $normalized : '-';
    }

    private function buildPdfDocument(): string
    {
        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold >>",
            6 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic >>",
            7 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-BoldItalic >>",
        ];

        $nextObjectNumber = 8;
        $imageResources = [];

        foreach ($this->images as $resourceName => $image) {
            $objects[$nextObjectNumber] = "<< /Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace {$image['colorSpace']} /BitsPerComponent {$image['bits']} /Filter /DCTDecode /Length " . strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream";
            $imageResources[] = '/' . $resourceName . ' ' . $nextObjectNumber . ' 0 R';
            $nextObjectNumber += 1;
        }

        $contentObjectNumber = $nextObjectNumber;
        $infoObjectNumber = $contentObjectNumber + 1;
        $resourceDictionary = '<< /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >>';
        if ($imageResources !== []) {
            $resourceDictionary .= ' /XObject << ' . implode(' ', $imageResources) . ' >>';
        }
        $resourceDictionary .= ' >>';

        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . "] /Resources " . $resourceDictionary . " /Contents {$contentObjectNumber} 0 R >>";
        $objects[$contentObjectNumber] = "<< /Length " . strlen($this->content) . " >>\nstream\n" . $this->content . "endstream";
        $objects[$infoObjectNumber] = "<< /Title (" . $this->escapePdfText($this->documentTitle) . ") /Producer (SIGAP Reservation Payment PDF) >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i += 1) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R /Info {$infoObjectNumber} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function buildContentDispositionValue(string $dispositionType, string $filename): string
    {
        $safeDispositionType = strtolower(trim($dispositionType)) === 'attachment' ? 'attachment' : 'inline';

        return $safeDispositionType
            . '; filename="' . $filename . '"'
            . "; filename*=UTF-8''" . rawurlencode($filename);
    }

    private function sanitizeFilename(string $filename): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?? 'va-pembayaran.pdf';

        return $normalized !== '' ? $normalized : 'va-pembayaran.pdf';
    }
}
