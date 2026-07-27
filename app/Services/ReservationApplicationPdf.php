<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationApplicationPdf
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN_LEFT = 56;
    private const MARGIN_RIGHT = 56;
    private const MARGIN_TOP = 64.00;
    private const LINE_HEIGHT = 18.00;

    private string $content = '';

    public function outputInline(array $data, string $filename = 'permohonan-reservasi.pdf'): void
    {
        $pdf = $this->render($data);

        $safeFilename = $this->sanitizeFilename($filename);

        throw new HttpResponseException(
            response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $safeFilename . '"')
                ->header('Content-Length', (string) strlen($pdf))
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public')
        );
    }

    public function render(array $data): string
    {
        $this->content = '';
        $pageRight = self::PAGE_WIDTH - self::MARGIN_RIGHT;
        $contentWidth = $pageRight - self::MARGIN_LEFT;
        $labelX = self::MARGIN_LEFT;
        $colonX = self::MARGIN_LEFT + 110;
        $valueX = self::MARGIN_LEFT + 124;
        $valueWidth = $pageRight - $valueX;
        $signatureCenterX = self::PAGE_WIDTH - self::MARGIN_RIGHT - 85;
        $y = self::MARGIN_TOP;

        $this->drawCentered(self::PAGE_WIDTH / 2, $y, 'PERMOHONAN PEMAKAIAN GEDUNG SERBAGUNA', 'F2', 14);
        $y += 34;

        $this->drawRight($pageRight, $y, 'Surabaya, ' . ($data['letter_date'] ?? '-'), 'F1', 12);
        $y += 34;

        $this->drawText(self::MARGIN_LEFT, $y, 'Kepada :', 'F1', 12);
        $y += self::LINE_HEIGHT;
        $this->drawText(
            self::MARGIN_LEFT,
            $y,
            'Yth. Bapak / Ibu Camat ' . $this->normalizeValue($data['recipient_district'] ?? '-'),
            'F1',
            12
        );
        $y += self::LINE_HEIGHT;
        $this->drawText(self::MARGIN_LEFT, $y, 'Perihal : Permohonan Pemakaian', 'F1', 12);
        $y += 34;

        $y = $this->drawParagraph(
            self::MARGIN_LEFT,
            $y,
            'Yang bertanda tangan dibawah ini:',
            $contentWidth,
            'F1',
            12
        );
        // $y += 4;

        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'NIK', $data['applicant_nik'] ?? '-', $valueWidth);
        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'Nama', $data['applicant_name'] ?? '-', $valueWidth);
        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'Alamat', $data['applicant_address'] ?? '-', $valueWidth);
        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'Telp / HP', $data['applicant_phone'] ?? '-', $valueWidth);
        $y += 16;

        $y = $this->drawStyledParagraph(
            self::MARGIN_LEFT,
            $y,
            [
                ['text' => 'Dengan ini saya mengajukan permohonan pemakaian ', 'font' => 'F1'],
                ['text' => $this->normalizeValue($data['building_name'] ?? '-'), 'font' => 'F2'],
                [
                    'text' => ' yang berada di lokasi '
                        . $this->normalizeValue($data['building_address'] ?? '-')
                        . ' Kelurahan '
                        . $this->normalizeValue($data['building_subdistrict'] ?? '-')
                        . ' Kecamatan '
                        . $this->normalizeValue($data['building_district'] ?? '-')
                        . ' yang akan kami gunakan pada:',
                    'font' => 'F1',
                ],
            ],
            $contentWidth,
            12,
            'left'
        );
        // $y += 4;

        $y = $this->drawField(
            $labelX,
            $colonX,
            $valueX,
            $y,
            'Hari / Tanggal',
            ($data['event_day'] ?? '-') . ' / ' . ($data['event_date'] ?? '-'),
            $valueWidth
        );
        $y = $this->drawField(
            $labelX,
            $colonX,
            $valueX,
            $y,
            'Waktu',
            ($data['event_start_time'] ?? '-') . ' WIB s/d '
                . ($data['event_end_time'] ?? '-') . ' WIB'
                . (trim((string) ($data['event_duration'] ?? '')) !== '' ? ' (' . $data['event_duration'] . ')' : ''),
            $valueWidth
        );
        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'Acara', $data['event_name'] ?? '-', $valueWidth);
        $y = $this->drawField(
            $labelX,
            $colonX,
            $valueX,
            $y,
            'Jumlah Peserta',
            $this->normalizeValue($data['est_person_label'] ?? '-'),
            $valueWidth
        );
        $y = $this->drawField($labelX, $colonX, $valueX, $y, 'UMKM', $data['umkm_label'] ?? '-', $valueWidth);
        $y += 16;

        $y = $this->drawParagraph(
            self::MARGIN_LEFT,
            $y,
            'Bersama ini saya bersedia mematuhi persyaratan yang tercantum di bawah ini:',
            $contentWidth,
            'F1',
            12,
            'left'
        );
        // $y += 4;

        $requirements = [
            'Melakukan pembayaran retribusi sewa gedung sesuai dengan ketentuan Peraturan Daerah Kota Surabaya Nomor 7 Tahun 2023 tentang Pajak Daerah dan Retribusi Daerah.',
            'Menjaga keamanan, ketertiban dan kebersihan selama acara berlangsung.',
            'Siap bertanggung jawab atas segala macam bentuk kerusakan dan kehilangan barang saat pemakaian Gedung Serbaguna.',
        ];

        foreach ($requirements as $index => $requirement) {
            $y = $this->drawNumberedItem(
                self::MARGIN_LEFT,
                $y,
                (string) ($index + 1),
                $requirement,
                $contentWidth,
                'F1',
                12,
                'left'
            );
        }

        $y += 16;
        $y = $this->drawParagraph(
            self::MARGIN_LEFT,
            $y,
            'Demikian permohonan ini disampaikan.',
            $contentWidth,
            'F1',
            12
        );
        $y += 28;

        $this->drawCentered($signatureCenterX, $y, 'Hormat Saya,', 'F1', 12);
        $y += self::LINE_HEIGHT;
        $this->drawCentered($signatureCenterX, $y, 'Pemohon', 'F1', 12);
        $y += 84;
        $this->drawCentered($signatureCenterX, $y, '(' . $this->normalizeValue($data['applicant_name'] ?? '-') . ')', 'F2', 12);

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
        string $labelFont = 'F1',
        string $valueFont = 'F2'
    ): float {
        $this->drawText($labelX, $y, $label, $labelFont, 12);
        $this->drawText($colonX, $y, ':', $labelFont, 12);

        $lines = $this->wrapText($this->normalizeValue($value), $valueWidth, 12, $valueFont);
        $currentY = $y;

        foreach ($lines as $line) {
            $this->drawText($valueX, $currentY, $line, $valueFont, 12);
            $currentY += self::LINE_HEIGHT;
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
        string $align = 'left'
    ): float {
        return $this->drawStyledParagraph(
            $x,
            $y,
            [
                ['text' => $text, 'font' => $font],
            ],
            $width,
            $size,
            $align
        );
    }

    private function drawStyledParagraph(
        float $x,
        float $y,
        array $segments,
        float $width,
        float $size,
        string $align = 'left'
    ): float {
        $lines = $this->wrapStyledText($segments, $width, $size);
        $lastLineIndex = count($lines) - 1;

        foreach ($lines as $index => $line) {
            $lineAlign = $align === 'justify' && $index < $lastLineIndex ? 'justify' : $align;
            $this->drawStyledLine($x, $y, $line, $width, $size, $lineAlign);
            $y += self::LINE_HEIGHT;
        }

        return $y;
    }

    private function drawNumberedItem(
        float $x,
        float $y,
        string $number,
        string $text,
        float $width,
        string $font,
        float $size,
        string $align = 'left'
    ): float {
        $numberPrefix = $number . '.';
        $numberWidth = 18.0;
        $textX = $x + $numberWidth;
        $lines = $this->wrapStyledText(
            [
                ['text' => $text, 'font' => $font],
            ],
            $width - $numberWidth,
            $size
        );
        $lastLineIndex = count($lines) - 1;

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                $this->drawText($x, $y, $numberPrefix, $font, $size);
            }

            $lineAlign = $align === 'justify' && $index < $lastLineIndex ? 'justify' : $align;
            $this->drawStyledLine($textX, $y, $line, $width - $numberWidth, $size, $lineAlign);
            $y += self::LINE_HEIGHT;
        }

        return $y;
    }

    private function drawStyledLine(
        float $x,
        float $y,
        array $line,
        float $width,
        float $size,
        string $align = 'left'
    ): void {
        $lineWidth = (float) ($line['width'] ?? 0.0);
        $spaceCount = (int) ($line['space_count'] ?? 0);
        $runs = $this->collapseLineRuns($line['tokens'] ?? []);
        $startX = $x;
        $extraSpace = 0.0;

        if ($align === 'center') {
            $startX = $x + max(0.0, ($width - $lineWidth) / 2);
        } elseif ($align === 'right') {
            $startX = $x + max(0.0, $width - $lineWidth);
        } elseif ($align === 'justify' && $spaceCount > 0) {
            $candidateExtraSpace = max(0.0, ($width - $lineWidth) / $spaceCount);

            if ($spaceCount < 4 || $candidateExtraSpace > ($size * 0.12)) {
                $align = 'left';
            } else {
                $extraSpace = $candidateExtraSpace;
            }
        }

        if ($runs === []) {
            return;
        }

        $pdfY = self::PAGE_HEIGHT - $y;
        $wordSpacing = $align === 'justify' && $extraSpace > 0.0 ? $extraSpace : 0.0;
        $currentFont = '';

        $this->content .= "BT {$wordSpacing} Tw 1 0 0 1 {$startX} {$pdfY} Tm ";

        foreach ($runs as $run) {
            $runText = (string) ($run['text'] ?? '');
            $runFont = (string) ($run['font'] ?? 'F1');

            if ($runText === '') {
                continue;
            }

            if ($currentFont !== $runFont) {
                $this->content .= "/{$runFont} {$size} Tf ";
                $currentFont = $runFont;
            }

            $this->content .= '(' . $this->escapePdfText($runText) . ") Tj ";
        }

        $this->content .= "ET\n";
    }

    private function drawText(float $x, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $encoded = $this->escapePdfText($text);
        $pdfY = self::PAGE_HEIGHT - $y;
        $this->content .= "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$pdfY} Tm ({$encoded}) Tj ET\n";
    }

    private function drawTextWithWordSpacing(
        float $x,
        float $y,
        string $text,
        string $font = 'F1',
        float $size = 12,
        float $wordSpacing = 0.0
    ): void {
        $encoded = $this->escapePdfText($text);
        $pdfY = self::PAGE_HEIGHT - $y;
        $wordSpacing = max(0.0, $wordSpacing);
        $this->content .= "BT /{$font} {$size} Tf {$wordSpacing} Tw 1 0 0 1 {$x} {$pdfY} Tm ({$encoded}) Tj ET\n";
    }

    private function drawCentered(float $centerX, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $width = $this->measureTextWidth($text, $size, $font);
        $this->drawText($centerX - ($width / 2), $y, $text, $font, $size);
    }

    private function drawRight(float $rightX, float $y, string $text, string $font = 'F1', float $size = 12): void
    {
        $width = $this->measureTextWidth($text, $size, $font);
        $this->drawText($rightX - $width, $y, $text, $font, $size);
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

    private function wrapStyledText(array $segments, float $maxWidth, float $size): array
    {
        $tokens = $this->tokenizeStyledSegments($segments);
        if ($tokens === []) {
            return [
                [
                    'tokens' => [
                        ['text' => '-', 'font' => 'F1'],
                    ],
                    'width' => $this->measureTextWidth('-', $size, 'F1'),
                    'space_count' => 0,
                ],
            ];
        }

        $lines = [];
        $currentTokens = [];
        $currentWidth = 0.0;

        foreach ($tokens as $token) {
            $tokenText = (string) ($token['text'] ?? '');
            $tokenFont = (string) ($token['font'] ?? 'F1');
            $isSpace = $tokenText === ' ';

            if ($isSpace && $currentTokens === []) {
                continue;
            }

            $tokenWidth = $this->measureTextWidth($tokenText, $size, $tokenFont);

            if (!$isSpace && $tokenWidth > $maxWidth) {
                $chunks = $this->splitLongWord($tokenText, $maxWidth, $size, $tokenFont);
                foreach ($chunks as $chunkIndex => $chunk) {
                    $chunkToken = ['text' => $chunk, 'font' => $tokenFont];
                    $chunkWidth = $this->measureTextWidth($chunk, $size, $tokenFont);

                    if ($currentTokens !== [] && $currentWidth + $chunkWidth > $maxWidth) {
                        $line = $this->buildStyledLine($currentTokens, $size);
                        if ($line !== null) {
                            $lines[] = $line;
                        }
                        $currentTokens = [];
                        $currentWidth = 0.0;
                    }

                    $currentTokens[] = $chunkToken;
                    $currentWidth += $chunkWidth;

                    if ($chunkIndex < count($chunks) - 1) {
                        $line = $this->buildStyledLine($currentTokens, $size);
                        if ($line !== null) {
                            $lines[] = $line;
                        }
                        $currentTokens = [];
                        $currentWidth = 0.0;
                    }
                }

                continue;
            }

            if ($currentTokens !== [] && ($currentWidth + $tokenWidth) > $maxWidth) {
                $line = $this->buildStyledLine($currentTokens, $size);
                if ($line !== null) {
                    $lines[] = $line;
                }

                $currentTokens = [];
                $currentWidth = 0.0;

                if ($isSpace) {
                    continue;
                }
            }

            if ($isSpace && $currentTokens === []) {
                continue;
            }

            $currentTokens[] = [
                'text' => $tokenText,
                'font' => $tokenFont,
            ];
            $currentWidth += $tokenWidth;
        }

        $line = $this->buildStyledLine($currentTokens, $size);
        if ($line !== null) {
            $lines[] = $line;
        }

        return $lines;
    }

    private function tokenizeStyledSegments(array $segments): array
    {
        $tokens = [];

        foreach ($segments as $segment) {
            $text = (string) ($segment['text'] ?? '');
            $font = (string) ($segment['font'] ?? 'F1');
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

            if (trim($text) === '') {
                continue;
            }

            $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $part) {
                if (preg_match('/^\s+$/u', $part) === 1) {
                    $tokens[] = ['text' => ' ', 'font' => $font];
                    continue;
                }

                $tokens[] = ['text' => $part, 'font' => $font];
            }
        }

        return $tokens;
    }

    private function buildStyledLine(array $tokens, float $size): ?array
    {
        while ($tokens !== [] && (string) ($tokens[count($tokens) - 1]['text'] ?? '') === ' ') {
            array_pop($tokens);
        }

        if ($tokens === []) {
            return null;
        }

        $width = 0.0;
        $spaceCount = 0;

        foreach ($tokens as $token) {
            $tokenText = (string) ($token['text'] ?? '');
            $tokenFont = (string) ($token['font'] ?? 'F1');
            $width += $this->measureTextWidth($tokenText, $size, $tokenFont);

            if ($tokenText === ' ') {
                $spaceCount += 1;
            }
        }

        return [
            'tokens' => array_values($tokens),
            'width' => $width,
            'space_count' => $spaceCount,
        ];
    }

    private function collapseLineRuns(array $tokens): array
    {
        $runs = [];

        foreach ($tokens as $token) {
            $tokenText = (string) ($token['text'] ?? '');
            $tokenFont = (string) ($token['font'] ?? 'F1');

            if ($tokenText === '') {
                continue;
            }

            $lastIndex = count($runs) - 1;
            if ($lastIndex >= 0 && $runs[$lastIndex]['font'] === $tokenFont) {
                $runs[$lastIndex]['text'] .= $tokenText;
                continue;
            }

            $runs[] = [
                'text' => $tokenText,
                'font' => $tokenFont,
            ];
        }

        return $runs;
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
        $fontScale = $font === 'F2' ? 1.05 : 1.0;

        foreach ($characters as $character) {
            if ($character === ' ') {
                $width += $size * 0.28 * $fontScale;
                continue;
            }

            if (preg_match('/[ilI1\.,:;!\|\(\)\[\]\'"`]/u', $character) === 1) {
                $width += $size * 0.25 * $fontScale;
                continue;
            }

            if (preg_match('/[mwMW@#%&]/u', $character) === 1) {
                $width += $size * 0.82 * $fontScale;
                continue;
            }

            if (preg_match('/[A-Z0-9]/u', $character) === 1) {
                $width += $size * 0.60 * $fontScale;
                continue;
            }

            $width += $size * 0.52 * $fontScale;
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
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . "] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>",
            4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold >>",
            6 => "<< /Length " . strlen($this->content) . " >>\nstream\n" . $this->content . "endstream",
        ];

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

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function sanitizeFilename(string $filename): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?? 'permohonan-reservasi.pdf';

        return $normalized !== '' ? $normalized : 'permohonan-reservasi.pdf';
    }
}
