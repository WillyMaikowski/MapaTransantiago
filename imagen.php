<?php
//version incial para generar una imagen png de un cuadro con colores

$colores = [
	2 => [ 38, 59, 142 ], 		// Subus
	4 => [ 237, 133, 48 ], 		// Alsacia Express
	5 => [ 1, 170, 181 ], 		// Metbus
	9 => [ 255, 212, 0 ], 		// STP
	13 => [ 228, 35, 39 ], 		// Redbus
	15 => [ 25, 157, 217 ], 	// Alsacia
	16 => [ 0, 166, 125 ], 		// Vule
];

$recorrido = $_REQUEST['rec'];
$color = $colores[ $_REQUEST['tp'] ];
if( ! $recorrido || ! $color ) die( '-1' );

header( 'Content-Type: image/png' );
header( 'Cache-Control: max-age=36000' );
header( 'ETag: "'.md5( $recorrido.implode( '.', $color ) ).'"' );
$im     = @imagecreate( 30, 13 ) or die( '-2' );
$bgcolor = imagecolorallocate( $im, $color[0], $color[1], $color[2] );
$txtcolor = imagecolorallocate( $im, 255, 255, 255 );
$px     = (32 - 7.5 * strlen( $recorrido )) / 2;
imagestring( $im, 3, $px, 0.5, $recorrido, $txtcolor );
imagepng( $im );
imagedestroy( $im );
