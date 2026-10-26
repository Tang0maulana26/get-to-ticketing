<?php
// Original, lightweight SVG poster illustrations for fictional demo events.
$directory = __DIR__.'/../public/images';
if (!is_dir($directory)) { mkdir($directory, 0755, true); }
$posters = [
    ['blue', '#a9d9fb', '#123f6a', 'BLUE HOUR', 'FESTIVAL', 'INDEPENDENT SOUNDS / OPEN SKIES', 'vinyl'],
    ['sore', '#f2dfbe', '#81492d', 'SORE DI', 'SELATAN', 'A LITTLE SLOWER. A LITTLE CLOSER.', 'sun'],
    ['afterglow', '#c6bae5', '#3d3063', 'AFTERGLOW', 'SESSIONS', 'NEW SOUNDS AFTER SUNDOWN', 'waves'],
    ['ruang', '#d5dfcc', '#294d42', 'RUANG', 'SUARA', 'SMALL STAGE / BIG FEELINGS', 'circles'],
    ['midnight', '#17293e', '#b3ddff', 'MIDNIGHT', 'FREQUENCY', 'TUNE IN. LET GO.', 'waves'],
    ['sunday', '#f1db8d', '#65541f', 'SUNDAY,', 'UNPLUGGED', 'SLOW DAYS / GOOD SONGS', 'sun'],
];
foreach ($posters as [$name, $bg, $fg, $line1, $line2, $tag, $shape]) {
    $art = '';
    if ($shape === 'vinyl') {
        $art = '<circle cx="705" cy="290" r="225" fill="'.$fg.'"/>';
        for ($r = 80; $r < 220; $r += 14) { $art .= '<circle cx="705" cy="290" r="'.$r.'" fill="none" stroke="'.$bg.'" stroke-opacity=".2"/>'; }
        $art .= '<circle cx="705" cy="290" r="67" fill="'.$bg.'"/><circle cx="705" cy="290" r="8" fill="'.$fg.'"/><path d="M700 250v80M665 290h80" stroke="'.$fg.'" stroke-width="3"/>';
    } elseif ($shape === 'sun') {
        $art = '<circle cx="700" cy="250" r="180" fill="'.$fg.'" opacity=".14"/>';
        for ($i = 0; $i < 7; $i++) { $y = 350 + $i * 29; $art .= '<path d="M440 '.$y.' Q660 '.($y-90).' 910 '.$y.'" fill="none" stroke="'.$fg.'" stroke-width="9" opacity=".45"/>'; }
    } elseif ($shape === 'waves') {
        for ($i = 0; $i < 15; $i++) { $x = 450 + $i * 30; $h = 70 + abs(sin($i / 2)) * 260; $art .= '<rect x="'.$x.'" y="'.(300-$h/2).'" width="15" height="'.$h.'" rx="7" fill="'.$fg.'" opacity="'.(.2+$i*.04).'"/>'; }
    } else {
        for ($i = 0; $i < 5; $i++) { $art .= '<ellipse cx="720" cy="285" rx="'.(190-$i*26).'" ry="225" fill="none" stroke="'.$fg.'" stroke-width="8" opacity=".5"/>'; }
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600"><rect width="900" height="600" fill="'.$bg.'"/><path d="M35 80H865M35 520H865" stroke="'.$fg.'" opacity=".3"/>'.$art.'<g font-family="Arial,Helvetica,sans-serif" fill="'.$fg.'"><text x="40" y="51" font-size="17" letter-spacing="3">THE GET TO PRESENTS</text><text x="40" y="270" font-size="70" font-weight="900" letter-spacing="-4">'.$line1.'</text><text x="40" y="345" font-size="70" font-weight="900" letter-spacing="-4">'.$line2.'</text><text x="43" y="393" font-size="13" font-weight="700" letter-spacing="2">'.$tag.'</text><text x="40" y="560" font-size="16" letter-spacing="2">LIVE MUSIC / GOOD COMPANY</text><text x="800" y="560" font-size="15">DEMO</text></g></svg>';
    file_put_contents($directory.'/poster-'.$name.'.svg', $svg);
}
echo "Six original poster illustrations generated.\n";
