<?php
/**
 * Generates placeholder images for the seed content (needs PHP with GD).
 * Usage: php scripts/generate-images.php
 */

$out = __DIR__ . '/seed/images';
if ( ! is_dir( $out ) ) {
	mkdir( $out, 0755, true );
}

/** Two-colour diagonal gradient with a soft grid and a few abstract "tech" shapes. */
function sammav_placeholder( string $file, int $w, int $h, array $from, array $to, int $seed, string $label = '' ): void {
	mt_srand( $seed );
	$img = imagecreatetruecolor( $w, $h );
	for ( $y = 0; $y < $h; $y++ ) {
		for ( $x = 0; $x < $w; $x += 4 ) {
			$t = ( $x / $w + $y / $h ) / 2;
			$c = imagecolorallocate(
				$img,
				(int) ( $from[0] + ( $to[0] - $from[0] ) * $t ),
				(int) ( $from[1] + ( $to[1] - $from[1] ) * $t ),
				(int) ( $from[2] + ( $to[2] - $from[2] ) * $t )
			);
			imagefilledrectangle( $img, $x, $y, $x + 3, $y, $c );
		}
	}
	imagealphablending( $img, true );
	$grid = imagecolorallocatealpha( $img, 255, 255, 255, 116 );
	for ( $x = 0; $x < $w; $x += 48 ) {
		imageline( $img, $x, 0, $x, $h, $grid );
	}
	for ( $y = 0; $y < $h; $y += 48 ) {
		imageline( $img, 0, $y, $w, $y, $grid );
	}
	// Network of nodes and links.
	$nodes = array();
	for ( $i = 0; $i < 9; $i++ ) {
		$nodes[] = array( mt_rand( (int) ( $w * 0.1 ), (int) ( $w * 0.9 ) ), mt_rand( (int) ( $h * 0.15 ), (int) ( $h * 0.85 ) ) );
	}
	imagesetthickness( $img, 3 );
	$link = imagecolorallocatealpha( $img, 255, 255, 255, 90 );
	foreach ( $nodes as $i => $n ) {
		$m = $nodes[ ( $i + 1 ) % count( $nodes ) ];
		imageline( $img, $n[0], $n[1], $m[0], $m[1], $link );
	}
	$dot  = imagecolorallocatealpha( $img, 34, 211, 238, 20 );
	$ring = imagecolorallocatealpha( $img, 255, 255, 255, 70 );
	foreach ( $nodes as $n ) {
		$r = mt_rand( 14, 30 );
		imagefilledellipse( $img, $n[0], $n[1], $r, $r, $dot );
		imageellipse( $img, $n[0], $n[1], $r + 18, $r + 18, $ring );
	}
	// Large translucent panels.
	$panel = imagecolorallocatealpha( $img, 255, 255, 255, 108 );
	for ( $i = 0; $i < 3; $i++ ) {
		$px = mt_rand( 0, (int) ( $w * 0.7 ) );
		$py = mt_rand( 0, (int) ( $h * 0.6 ) );
		imagefilledrectangle( $img, $px, $py, $px + mt_rand( 120, 320 ), $py + mt_rand( 80, 200 ), $panel );
	}
	if ( $label ) {
		$white = imagecolorallocate( $img, 255, 255, 255 );
		$scale = 5;
		$tw    = imagefontwidth( 5 ) * strlen( $label );
		$th    = imagefontheight( 5 );
		$txt   = imagecreatetruecolor( $tw, $th );
		imagesavealpha( $txt, true );
		imagefill( $txt, 0, 0, imagecolorallocatealpha( $txt, 0, 0, 0, 127 ) );
		imagestring( $txt, 5, 0, 0, $label, $white );
		imagecopyresampled( $img, $txt, 40, $h - 40 - $th * $scale, 0, 0, $tw * $scale, $th * $scale, $tw, $th );
	}
	imagejpeg( $img, $file, 82 );
}

/** Avatar: soft gradient circle with initials. */
function sammav_avatar( string $file, string $initials, array $from, array $to ): void {
	$s   = 480;
	$img = imagecreatetruecolor( $s, $s );
	for ( $y = 0; $y < $s; $y++ ) {
		$t = $y / $s;
		$c = imagecolorallocate( $img, (int) ( $from[0] + ( $to[0] - $from[0] ) * $t ), (int) ( $from[1] + ( $to[1] - $from[1] ) * $t ), (int) ( $from[2] + ( $to[2] - $from[2] ) * $t ) );
		imageline( $img, 0, $y, $s, $y, $c );
	}
	$white = imagecolorallocate( $img, 255, 255, 255 );
	$tw    = imagefontwidth( 5 ) * strlen( $initials );
	$th    = imagefontheight( 5 );
	$txt   = imagecreatetruecolor( $tw, $th );
	imagesavealpha( $txt, true );
	imagefill( $txt, 0, 0, imagecolorallocatealpha( $txt, 0, 0, 0, 127 ) );
	imagestring( $txt, 5, 0, 0, $initials, $white );
	$scale = 12;
	imagecopyresampled( $img, $txt, (int) ( ( $s - $tw * $scale ) / 2 ), (int) ( ( $s - $th * $scale ) / 2 ), 0, 0, $tw * $scale, $th * $scale, $tw, $th );
	imagejpeg( $img, $file, 85 );
}

$palettes = array(
	array( array( 7, 31, 74 ), array( 11, 61, 145 ) ),
	array( array( 11, 61, 145 ), array( 14, 116, 144 ) ),
	array( array( 14, 116, 144 ), array( 7, 31, 74 ) ),
	array( array( 30, 41, 59 ), array( 11, 61, 145 ) ),
	array( array( 8, 47, 73 ), array( 14, 116, 144 ) ),
	array( array( 49, 46, 129 ), array( 11, 61, 145 ) ),
);

for ( $p = 1; $p <= 6; $p++ ) {
	[ $from, $to ] = $palettes[ $p - 1 ];
	sammav_placeholder( "$out/project-$p.jpg", 1600, 1000, $from, $to, $p * 11 );
	for ( $g = 1; $g <= 3; $g++ ) {
		sammav_placeholder( "$out/project-$p-gallery-$g.jpg", 1200, 900, $to, $from, $p * 100 + $g );
	}
}

$people = array( 'SD' => 0, 'AM' => 1, 'KO' => 2, 'EA' => 4 );
$i      = 1;
foreach ( $people as $initials => $pal ) {
	sammav_avatar( "$out/team-$i.jpg", $initials, $palettes[ $pal ][0], $palettes[ $pal ][1] );
	$i++;
}

echo 'Generated ' . count( glob( "$out/*.jpg" ) ) . " images in $out\n";
