<?php



if ( IN_PRODUCTION ) {
	echo 'shut off in production.';
	return;
}

echo 'shut off in dev.';
return;

//$s   = array();
//$s[] = '*';
// $s[] = 'max(best_fitting_rims.rim_id)';
//$s[] = 'COUNT(part_number)';
//$s[] = 'MIN(rim_id)';

//$db = get_database_instance();
//$p  = [];
//$q  = '';
//$q  .= 'SELECT ' . implode_comma( $s ) . ' ';
// $q .= 'FROM tires WHERE tires.tire_id IN ( SELECT tire_id FROM tires WHERE width = 265 ORDER BY tire_id DESC LIMIT 0, 1 ) ';
//$q .= 'FROM rim_finishes ';
//$q .= 'INNER JOIN ( ';
//$s2   = [];
//$s2[] = 'rim_id';
//$s2[] = 'finish_id';
// $s2[] = 'MAX(rim_id) AS best_score';
// $s2[] = 'COUNT(rim_id)';
// $s2[] = 'MIN(rim_id)';
//$s2[] = 'MAX(rim_id)';
// $s2[] = 'GROUP_CONCAT(rim_id)';
//$q .= 'SELECT ' . implode_comma( $s2 ) . ' ';
//$q .= 'FROM rims AS _rims ';
//$q .= 'WHERE 2 = 2 ';
//$q .= 'GROUP BY rim_id ';
// $q .= 'WHERE _rims.rim_id IN ( SELECT rim_id FROM rims WHERE width > 5 ) ';

// must group due to aggregate select
// $q .= 'GROUP BY _rims.finish_id ';

//$q .= 'SELECT * ';
//$q .= 'FROM rims ';
//$q .= 'WHERE 2 = 2 ';
//$q .= 'AND rims.width < 9 ';
//$q .= 'ORDER BY rim_id DESC ';
//$q .= 'LIMIT 1 ';
//$q .= ') AS best_fitting_rims ON best_fitting_rims.finish_id = rim_finishes.rim_finish_id ';
//$q .= 'WHERE 1 = 1 ';
//$q .= 'AND rim_finish_id = 854 ';
// $q .= 'AND best_fitting_rims.brand_slug = "braelin" ';
// $q .= 'GROUP BY rim_finishes.rim_finish_id ';
// $q .= 'ORDER BY best_fitting_rims.rim_id DESC ';
// $q .= 'ORDER BY MAX(rim_id), rim_finishes.rim_finish_id ';
// $q .= 'GROUP BY rim_finishes.rim_finish_id ';
// $q .= 'ORDER BY rim_id DESC ';
// $q .= 'ORDER BY rim_id ASC ';

// $q .= ';';


// .............................
$db = get_database_instance();
$p = [];

$q = '';

$q .= $db->builder->select( array(
		'*',
	)) . ' ';

$q .= 'FROM rim_finishes ';

// join all rims, giving each one a fitment score, and also
// select the minimum fitment score into each row
$q .= 'INNER JOIN ( ';

$q .= $db->builder->select( array(
	'rim_id',
	'finish_id',
	'MIN(ABS(diameter - 19)) AS min_fitment_score',
	'COUNT(rim_id) AS count_rims_that_fit',
	'GROUP_CONCAT(diameter)'
)) . ' ';

$q .= 'FROM rims ';
$q .= 'WHERE 2 = 2 ';
// $q .= 'AND ';

$q .= 'GROUP BY finish_id ';
// $q .= 'HAVING max(rim_id) ';

$q .= ' ) AS all_best_rims ON all_best_rims.finish_id = rim_finishes.rim_finish_id ';

// join rims again and calculate the fitment score, but this time only join
// if the fitment score is the same as the lowest fitment score
// to avoid making the sql calculate all fitment scores of all rims.. hoping
// that joining on rim_id will do the trick...
$q .= 'INNER JOIN ( ';

$q .= $db->builder->select( array(
		'rim_id',
		'finish_id',
		'(ABS(rims.diameter - 19)) AS fitment_score',
		'COUNT(rim_id)',
		'MIN(rim_id)',
		'MAX(rim_id)',
	)) . ' ';

// $q .= 'FROM rims INNER JOIN tires ON tires.price_ca > rims.price_ca ';
$q .= 'FROM rims ';
$q .= 'WHERE 2 = 2 ';
// $q .= 'AND ';

// $q .= 'GROUP BY finish_id ';
// $q .= 'HAVING max(rim_id) ';

$q .= ' ) AS best_rim ON ' . implode( ' AND ', array(
	'best_rim.finish_id = rim_finishes.rim_finish_id',
	'best_rim.rim_id = all_best_rims.rim_id',
	'best_rim.fitment_score = min_fitment_score',
) ) . ' ';

$q .= '';
$q .= 'WHERE 1 = 1 ';
$q .= '';
$q .= 'ORDER BY rim_finishes.rim_finish_id ';
$q .= ';';


//$q = '';
//$q .= 'SELECT * ';
//$q .= 'FROM rim_brands ';
//$q .= 'INNER JOIN rims WHERE rims.rim_id = ( SELECT MAX(rim_id) FROM rims WHERE brand_id = rim_brands.rim_brand_id GROUP BY brand_id ) ';
// $q .= 'GROUP BY rims.brand_id ';


$q = '';
//$q .= 'SELECT * ';
//$q .= 'FROM rim_finishes AS ff ';
//$q .= 'LEFT JOIN ( SELECT * FROM rims ';
//$q .= 'WHERE brand_slug = "vision" ';
//$q .= 'ORDER BY rim_id ASC ';
//$q .= ') r1 ON ff.rim_finish_id = r1.finish_id ';
//
//$q .= 'WHERE 1 = 1 ';
//$q .= 'AND r1.rim_id > 0 ';

//$q = '';
//$q .= 'SELECT * ';
//$q .= 'FROM ( ';
//$q .= 'SELECT * ';
//$q .= 'FROM rims ';
//$q .= 'ORDER BY rim_id DESC ';
//$q .= 'LIMIT 18446744073709551615 ';
//$q .= ') AS rims ';
//$q .= 'GROUP BY finish_id ';
//$q .= 'ORDER BY finish_id ';

//$q = '';
//$q .= 'SELECT * ';
//$q .= 'FROM rim_finishes AS ff ';
//$q .= 'LEFT JOIN ( SELECT * FROM rims ORDER BY rim_id ASC LIMIT 0, 10000000 ) AS rims ON rims.finish_id = ff.rim_finish_id ';
//$q .= 'WHERE rims.rim_id <> "NULL" ';
//$q .= '';

//$q = '';
//$q .= 'SELECT *, GROUP_CONCAT( speed_rating ), GROUP_CONCAT( load_index ), GROUP_CONCAT( load_index_2 ), COUNT(tire_id) ';
//$q .= 'FROM tires  ';
//$q .= '';
//$q .= 'GROUP BY brand_id, model_id, tire_sizing_system, diameter, profile, width ';
//$q .= 'HAVING COUNT(tire_id) > 1 ';
//$q .= 'ORDER BY brand_id, model_id, tire_id ';


$q = '';
$q .= 'SELECT * ';
$q .= 'FROM rims ORDER BY color_1 LIMIT 0, 5';


$p = array();

echo wrap_tag( debug_pdo_statement( $q, $p ) );
echo '<br>';

$results = $db->get_results( $q, $p );
start_time_tracking( 'test_query' );
$results = $db->get_results( $q, $p );
$count   = $results ? count( $results ) : 0;

$rim_ids = array_column( gp_convert_object_to_array_recursive( $results ), 'rim_id' );
echo '<pre>' . print_r( $rim_ids, true ) . '</pre>';

//$sub_queries = array();
//foreach ( $rim_ids as $rim_id ) {
//	$sub_queries[] = 'SELECT * FROM rims WHERE rim_id = ' . $rim_id;
//}

//$q = '';
//$q .= 'SELECT * ';
//$q .= 'FROM ( ( ' . implode( ' ) UNION ALL ( ', $sub_queries  ) . ' ) ) ';
//$q .= '';
//$q .= '';
//echo $q;

//if ( $results ) {
//	$results = array_map( function ( $r ) {
//		$r = gp_make_array( $r );
//		$tire = DB_Tire::create_instance_or_null( $r );
//		$ret = array_merge( $r, $tire->to_array_for_admin_tables() );
//		return $ret;
//	}, $results );
//}

echo '<div class="general-content">';
echo wrap_tag( $count . ' results' );
echo wrap_tag( 'Time: ' . end_time_tracking( 'test_query' ) );
echo '</div>';

echo render_html_table( null, $results );