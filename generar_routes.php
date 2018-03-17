<?php
exit( -1 );
//pseudo-cache para las rutas de los buses

$retorno = [];
$rutas = json_decode( file_get_contents( 'https://api.scltrans.it/v1/routes/?limit=10000' ), TRUE );
foreach( $rutas['results'] as $k => $r ) {
	if( $r['agency_id'] != 'TS' ) {
		unset( $rutas['results'][$k] );
		continue;
	}
	$file = '/tmp/'.$r['route_id'].'.json';
	$dirs = file_get_contents( file_exists( $file ) ? $file : 'https://api.scltrans.it/v2/routes/'.$r['route_id'].'/directions' );
	if( ! file_exists( $file ) ) file_put_contents( $file, $dirs );

	$retorno[$k]['route_id'] = $r['route_id'];
	$ddirs = json_decode( $dirs, TRUE )['results'];
	foreach( $ddirs as $dir_id => $d ) {
		foreach( $d['shape'] as $kk => $sh ) $d['shape'][$kk] = [ $sh['shape_pt_lat'], $sh['shape_pt_lon'] ];
		$retorno[$k]['directions'][] = [
			'direction_id' => $dir_id,
			'shape' => $d['shape']
		];
	}
}

file_put_contents( 'routes.js', 'var routes = '.json_encode( array_values( $retorno ) ).';' );
