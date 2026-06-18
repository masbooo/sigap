<?php

class CaptchaController extends Controller
{
    public function image()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $length = 5;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        $_SESSION['captcha'] = $code;

        $width = 190;
        $height = 64;

        $digitColors = [
            '#5b4636',
            '#dc2626',
            '#2563eb',
            '#9333ea',
            '#0f766e',
            '#b45309',
        ];

        $lineColors = [
            '#ec4899',
            '#8b5cf6',
            '#06b6d4',
            '#f97316',
            '#84cc16',
        ];

        $bgDots = '';
        for ($i = 0; $i < 140; $i++) {
            $cx = random_int(0, $width);
            $cy = random_int(0, $height);
            $r = random_int(1, 2);
            $alpha = random_int(8, 22) / 100;
            $bgDots .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="rgba(120,120,120,' . $alpha . ')" />';
        }

        $microLines = '';
        for ($i = 0; $i < 18; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $len = random_int(8, 28);
            $angle = deg2rad(random_int(0, 360));
            $x2 = (int) round($x1 + cos($angle) * $len);
            $y2 = (int) round($y1 + sin($angle) * $len);
            $stroke = random_int(1, 2);
            $alpha = random_int(10, 28) / 100;

            $microLines .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgba(140,140,140,' . $alpha . ')" stroke-width="' . $stroke . '" stroke-linecap="round" />';
        }

        $curves = '';
        for ($i = 0; $i < 3; $i++) {
            $startX = random_int(0, 20);
            $startY = random_int(8, $height - 8);

            $c1x = random_int(30, 70);
            $c1y = random_int(0, $height);

            $c2x = random_int(100, 150);
            $c2y = random_int(0, $height);

            $endX = random_int($width - 25, $width);
            $endY = random_int(8, $height - 8);

            $color = $lineColors[array_rand($lineColors)];
            $stroke = random_int(2, 4);
            $alpha = random_int(45, 75) / 100;

            $curves .= '<path d="M ' . $startX . ' ' . $startY . ' C ' . $c1x . ' ' . $c1y . ', ' . $c2x . ' ' . $c2y . ', ' . $endX . ' ' . $endY . '" fill="none" stroke="' . $color . '" stroke-width="' . $stroke . '" stroke-linecap="round" opacity="' . $alpha . '" />';
        }

        $randomLines = '';
        for ($i = 0; $i < 4; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);

            $len = random_int(60, 170);
            $angle = deg2rad(random_int(-25, 25) + ($i * 35));
            $x2 = (int) round($x1 + cos($angle) * $len);
            $y2 = (int) round($y1 + sin($angle) * $len);

            $stroke = random_int(2, 5);
            $color = $lineColors[array_rand($lineColors)];
            $alpha = random_int(45, 85) / 100;

            $randomLines .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="' . $stroke . '" stroke-linecap="round" opacity="' . $alpha . '" />';
        }

        $digitsSvg = '';
        $baseX = 18;

        for ($i = 0; $i < strlen($code); $i++) {
            $digit = htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8');

            $x = $baseX + ($i * 31) + random_int(-3, 3);
            $y = random_int(36, 49);
            $rotate = random_int(-22, 22);
            $size = random_int(28, 34);
            $color = $digitColors[array_rand($digitColors)];

            $scaleY = random_int(90, 112) / 100;
            $skewX = random_int(-12, 12);

            $digitsSvg .= '
                <text
                    x="' . $x . '"
                    y="' . $y . '"
                    font-family="Verdana, Arial, sans-serif"
                    font-size="' . $size . '"
                    font-weight="500"
                    fill="' . $color . '"
                    transform="rotate(' . $rotate . ' ' . $x . ' ' . $y . ') skewX(' . $skewX . ') scale(1,' . $scaleY . ')"
                    opacity="0.96"
                >' . $digit . '</text>
            ';
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect x="0" y="0" width="{$width}" height="{$height}" rx="5" ry="5" fill="#f4f4f5"/>
    {$bgDots}
    {$microLines}
    {$digitsSvg}
    {$curves}
    {$randomLines}
</svg>
SVG;

        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo $svg;
        exit;
    }
}