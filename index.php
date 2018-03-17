<?php

if( $_REQUEST['data'] == 'buses' ) {
	//por el CORS
	header( 'Content-Type: application/json;' );
	print file_get_contents( 'https://api.scltrans.it/v1/buses/?limit=10000' );
	exit;
}

$animaciones = (int)$_REQUEST['animaciones'];

?>
<!DOCTYPE html>
<html>
<head>

<style>
	html, body {
		height: 100%;
		margin: 0;
	}
	a.boton {
		display: block;
		padding: 5px 6px;
		background-color: #e42327;
		border: 1px solid #e42327;
		color: white;
		margin-top: 10px;
		text-decoration: none;
	}
	a.boton:hover {
		background-color: white;
		color: #e42327;
	}

	#map {
		height: 100%;
		width: 100%;
	}
	#legend {
		background-color: white;
		padding: 10px;
		margin: 10px;
	}
	#legend img { vertical-align: middle; }
</style>

</head>
<body>

<div id="map"></div>
<div id="legend">
	<h4>Colores:</h4>
	<div><img src="imagen.php?rec=%20&tp=2"/> Subus</div>
	<div><img src="imagen.php?rec=%20&tp=4"/> Alsacia Express</div>
	<div><img src="imagen.php?rec=%20&tp=5"/> Metbus</div>
	<div><img src="imagen.php?rec=%20&tp=9"/> STP</div>
	<div><img src="imagen.php?rec=%20&tp=13"/> Redbus Urbano</div>
	<div><img src="imagen.php?rec=%20&tp=15"/> Alsacia</div>
	<div><img src="imagen.php?rec=%20&tp=16"/> Buses Vule</div>

	<h4>Nº Micros en Recorrido:</h4>
	<div id="micros-efectivas">0</div>

<?php if( $animaciones ) { ?>
	<a href="?animaciones=0" class="boton">Desactivar Animaciones</a>
<?php } else { ?>
	<a href="?animaciones=1" class="boton">Activar Animaciones</a>
<?php } ?>
</div>

<script>
	var markers = {};
	var map = null;
	var animaciones = <?php echo $animaciones ? 'true':'false'; ?>;
	var ua = navigator.userAgent;
	var mobile = /IEMobile|Windows Phone|Lumia/i.test(ua) ? 'w' : /iPhone|iP[oa]d/.test(ua) ? 'i' : /Android/.test(ua) ? 'a' : /BlackBerry|PlayBook|BB10/.test(ua) ? 'b' : /Mobile Safari/.test(ua) ? 's' : /webOS|Mobile|Tablet|Opera Mini|\bCrMo\/|Opera Mobi/i.test(ua) ? true : false;
	var url = 'https://apps.maikowski.cl/mapa_transantiago/';
	var polys = {};
	var colors = {
		2: '#263b8e',
		4: '#ed8530',
		5: '#01aab5',
		9: '#ffd400',
		13: '#e42327',
		15: '#199dd9',
		16: '#00a67d'
	};


	function calcOffset( poly, el ) {
		var pos = { lat: parseFloat( el.bus_lat ), lng: parseFloat( el.bus_lon ) };
		var gpos = new google.maps.LatLng( pos.lat, pos.lng );
		var o; var dist_total = 0; var i = 0; var j = 0; var d = {}; var min = 999999999999999;

		var dist = 0;
		poly.getPath().forEach( function( p ) {
			if( ! o ) {
				o = p;
				return;
			}

			d[i] = google.maps.geometry.spherical.computeDistanceBetween( o, p );
			var suma = google.maps.geometry.spherical.computeDistanceBetween( o, gpos );
			suma += google.maps.geometry.spherical.computeDistanceBetween( gpos, p );

			dist_total += d[i];
			if( suma <= min ) {
				 min = suma;
				 dist = google.maps.geometry.spherical.computeDistanceBetween( o, gpos );
				 j = i;
			}

			o = p;
			i += 1;
		} );

		for( var i in d ) {
			if( i == j ) break;
			dist += d[i];
		}

		return i > 0 && i < Object.keys( d ).length ? (dist*100.0/dist_total)+'%' : '0%';
	}

	function refreshPoints() {
		ajax( url+'?data=buses', function() {
			if( this.status != 200 ) {
				errorFn();
				return;
			}

			var microsEfectivas = 0;
			data = JSON.parse( this.response );
			data.results.forEach( function( el ) {
				var path = '';
				for( var i = 0; i < el.route_id.length && i < 5; i++ ) {
					var C = el.route_id.charAt(i);
					var c = C.toLowerCase();
					if( ! svg_el[C] && ! svg_el[c] ) continue;

					path += svg_pos[i] + ( svg_el[C] ? svg_el[C] : svg_el[c] );
				}

				var symbol = {
					offset: '0%',
					patente: el.bus_plate_number,
					velocidad: el.bus_speed,
					icon: {
						path: path+' M0,0 H30 V12 H0 V0',
						fillColor: colors[el.operator_number],
						fillOpacity: 1,
						strokeOpacity: 1,
						strokeWeight: 1.2,
						strokeColor: el.operator_number !== 9 ? '#ffffff' : '#000000',
						rotation: 90,
						scale: mobile ? 2 : 1
					}
				};
				var id = el.route_id+'.'+el.direction_id;
				if( ! polys[id] ) return;

				symbol.offset = calcOffset( polys[id], el );
				if( symbol.offset == '0%' ) return;

				microsEfectivas++;
				var existe = false;
				var iarr = polys[id].get( 'icons' );
				for( var i = 0; i < iarr.length; i++ ) {
					if( iarr[i].patente != el.bus_plate_number ) continue;
					existe = true;
					iarr[i].offset = symbol.offset;
					iarr[i].velocidad = symbol.velocidad;
				}
				if( ! existe ) iarr.push( symbol );
				polys[id].set( 'icons', iarr );
				polys[id].set( 'strokeOpacity', 0.2 );
			} );

			document.getElementById( 'micros-efectivas' ).innerHTML = microsEfectivas;
			window.setTimeout( refreshPoints, 120000 );
		}, errorFn );
	}

	function initMap() {
		var stgo = { lat: -33.458518, lng:  -70.642716 };
		map = new google.maps.Map( document.getElementById( 'map' ), {
			zoom: mobile ? 17 : 15,
			center: stgo
		} );
		map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push( document.getElementById( 'legend' ) );

		genRoutes();
	}

	function genRoutes() {
		routes.forEach( function( el ) {
			if( ! el.directions || el.directions.length <= 0 ) return;

			el.directions.forEach( function( dir ) {
				var recorrido = [];
				dir.shape.forEach( function( shp ) {
					recorrido.push( { lat: parseFloat( shp[0] ), lng: parseFloat( shp[1] ) } );
				} );

				var id = el.route_id + '.' + dir.direction_id;
				polys[id] = new google.maps.Polyline( {
					path: recorrido,
					geodesic: true,
					icons: [],
					map: map,
					strokeColor: '#FFFFFF',
					strokeOpacity: 0,
					strokeWeight: 0
				} );
			} );
		} );

		window.requestAnimationFrame( updateBuses );
		refreshPoints();
	}

	function updateBuses() {
		if( ! animaciones ) return;

		for( var id in polys ) {
			var icons = polys[id].get( 'icons' );
			if( icons.length <= 0 ) continue;

			for( var i = 0; i < icons.length; i++ ) {
				icons[i].offset = (parseFloat( icons[i].offset )+(0.00003*icons[i].velocidad))+'%';
			}
			polys[id].set( 'icons', icons );
		}

		window.requestAnimationFrame( updateBuses );
	}

	function errorFn() {
		console.log( this );
	}

	function ajax( url, fn, fn_error ) {
		var xhr = new XMLHttpRequest();
		xhr.addEventListener( 'load', fn );
		xhr.addEventListener( 'error', fn_error );
		xhr.open( 'GET', url );
		xhr.send();
	}
</script>
<script src="routes.js"></script>
<script src="svgs.js"></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=GOOGLE_KEY&callback=initMap"></script>

</body>
</html>
