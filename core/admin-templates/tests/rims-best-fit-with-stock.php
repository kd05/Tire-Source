<?php

if ( IN_PRODUCTION ) {
	echo 'shut off in production.';
	return;
}

$size = array(
	'staggered' => false,
	'oem' => false,
	'fitment_slug' => 'testing-slug',
	'rims' => array(
		'universal' => array(
			'bolt_pattern' => '5x114.3',
			'center_bore' => '73.1',
			'diameter' => 20,
			'offset' => 50,
			'width' => 7,
			'width_plus' => 10,
			'width_minus' => 10,
			'offset_plus' => 100,
			'offset_minus' => 100,
			'loc' => 'universal',
		),
	)
);

$sizes = array( $size );

// filters
$userdata = array();

// order by, grouping stuff, paginate, etc.
$args = array();

$results = query_rims_by_sizes( $sizes, $userdata, $args );
$print = array();

echo '<h1>Count ' . count( $results ) . '</h1>';

if ( $results ) {
	foreach ( $results as $r ) {

		$p = array();
		$p['r1_part_number'] = gp_if_set( $r, 'r1_part_number' );
		$p['fitment_slug'] = gp_if_set( $r, 'fitment_slug' );
		$p['front_part_numbers'] = gp_if_set( $r, 'front_part_numbers' );

		if ( gp_if_set( $r, 'staggered' ) ) {
			$p['r2_part_number'] = gp_if_set( $r, 'r2_part_number' );
			$p['rear_part_numbers'] = gp_if_set( $r, 'rear_part_numbers' );
		}

		$print[] = $p;
	}
}

// echo '<pre>' . print_r( $print, true ) . '</pre>';
echo '<pre>' . print_r( $results, true ) . '</pre>';