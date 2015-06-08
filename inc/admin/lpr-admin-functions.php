<?php
/**
 * Common function for admin
 */

function learn_press_admin_localize_script(){
    if( defined( 'DOING_AJAX' ) || !is_admin() ) return;
    $translate = array(
        'quizzes_is_not_available' => __( 'Quiz is not available', 'learn_press' ),
        'lessons_is_not_available'   => __( 'Lesson is not available', 'learn_press' )
    );
    LPR_Admin_Assets::add_localize( $translate );
}
add_action( 'init', 'learn_press_admin_localize_script' );

/**
 * Default admin settings pages
 *
 * @return mixed
 */
function learn_press_settings_tabs_array() {
	$tabs = array(
		'general' => __( 'General', 'learn_press' ),
		'pages'   => __( 'Pages', 'learn_press' ),
		'payment' => __( 'Payments', 'learn_press' ),
		'emails'  => __( 'Emails', 'learn_press' )
	);
	return apply_filters( 'learn_press_settings_tabs_array', $tabs );
}


function learn_press_settings_payment() {
	?>
	<h3><?php _e( 'Payment', 'learn_press' ); ?></h3>
<?php
}

function learn_press_get_order_by_time( $by, $time ) {
	global $wpdb;
	$user_id = get_current_user_id();

	$y = date( 'Y', $time );
	$m = date( 'm', $time );
	$d = date( 'd', $time );
	switch ( $by ) {
		case 'days':
			$orders = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
					WHERE p.post_author = %d
					AND p.post_type = %s
					AND p.post_status = %s
					AND m.meta_key = %s
					AND m.meta_value = %s
					AND YEAR(p.post_date) = %s AND MONTH(p.post_date) = %s AND DAY(p.post_date) = %s",
					$user_id, 'lpr_order', 'publish', '_learn_press_transaction_status', 'completed', $y, $m, $d
				)
			);
			break;
		case 'months':
			$orders = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
					WHERE p.post_author = %d
					AND p.post_type = %s
					AND p.post_status = %s
					AND m.meta_key = %s
					AND m.meta_value = %s
					AND YEAR(p.post_date) = %s AND MONTH(p.post_date) = %s",
					$user_id, 'lpr_order', 'publish', '_learn_press_transaction_status', 'completed', $y, $m
				)
			);
			break;
	}
	return $orders;
}

function learn_press_get_courses_by_status( $status ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$courses = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM $wpdb->posts
			WHERE post_author = %d
			AND post_type = %s
			AND post_status = %s",
			$user_id, 'lpr_course', $status
		)
	);
	return $courses;
}

function learn_press_get_courses_by_price( $fee ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$courses = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
			WHERE p.post_author = %d
			AND p.post_type = %s
			AND p.post_status IN (%s, %s)
			AND m.meta_key = %s
			AND m.meta_value = %s",
			$user_id, 'lpr_course', 'publish', 'pending', '_lpr_course_payment', $fee
		)
	);
	return $courses;
}

function learn_press_get_chart_students( $from = null, $by = null, $time_ago ) {
	$labels   = array();
	$datasets = array();
	if ( is_null( $from ) ) {
		$from = current_time( 'mysql' );
	}
	// $by: days, months or years
	if ( is_null( $by ) ) {
		$by = 'days';
	}
	switch ( $by ) {
		case 'days':
			$date_format = 'M d';
			break;
		case 'months':
			$date_format = 'M Y';
			break;
		case 'years':
			$date_format = 'Y';
			break;
	}

	for ( $i = - $time_ago + 1; $i <= 0; $i ++ ) {
		$labels[]              = date( $date_format, strtotime( "$i $by", strtotime( $from ) ) );
		$datasets[0]['data'][] = learn_press_get_order_by_time( $by, strtotime( "$i $by", strtotime( $from ) ) );
	}
	$colors                              = learn_press_get_admin_colors();
	$datasets[0]['fillColor']            = 'rgba(255,255,255,0.1)';
	$datasets[0]['strokeColor']          = $colors[0];
	$datasets[0]['pointColor']           = $colors[0];
	$datasets[0]['pointStrokeColor']     = $colors[2];
	$datasets[0]['pointHighlightFill']   = $colors[2];
	$datasets[0]['pointHighlightStroke'] = $colors[0];
	return array(
		'labels'   => $labels,
		'datasets' => $datasets
	);
}

function learn_press_get_chart_courses() {
	$labels              = array( __( 'Pending Courses / Publish Courses', 'learn_press' ), __( 'Free Courses / Priced Courses', 'learn_press' ) );
	$datasets            = array();
	$datasets[0]['data'] = array( learn_press_get_courses_by_status( 'pending' ), learn_press_get_courses_by_price( 'free' ) );
	$datasets[1]['data'] = array( learn_press_get_courses_by_status( 'publish' ), learn_press_get_courses_by_price( 'not_free' ) );

	$colors                     = learn_press_get_admin_colors();
	$datasets[0]['fillColor']   = $colors[1];
	$datasets[0]['strokeColor'] = $colors[1];
	$datasets[1]['fillColor']   = $colors[3];
	$datasets[1]['strokeColor'] = $colors[3];
	return array(
		'labels'   => $labels,
		'datasets' => $datasets
	);
}

function learn_press_get_admin_colors() {
	$admin_color = get_user_meta( get_current_user_id(), 'admin_color', true );
	global $_wp_admin_css_colors;
	$colors = array();
	if ( !empty( $_wp_admin_css_colors[$admin_color]->colors ) ) {
		$colors = $_wp_admin_css_colors[$admin_color]->colors;
	}
	if ( empty ( $colors[0] ) ) {
		$colors[0] = '#000000';
	}
	if ( empty ( $colors[2] ) ) {
		$colors[2] = '#00FF00';
	}
	return $colors;
}

function learn_press_process_chart( $chart = array() ) {
	$data = json_encode(
		array(
			'labels'   => $chart['labels'],
			'datasets' => $chart['datasets']
		)
	);
	echo $data;
}

function learn_press_config_chart() {
	$colors = learn_press_get_admin_colors();
	$config = array(
		'scaleShowGridLines'      => true,
		'scaleGridLineColor'      => "#777",
		'scaleGridLineWidth'      => 0.3,
		'scaleFontColor'          => "#444",
		'scaleLineColor'          => $colors[0],
		'bezierCurve'             => true,
		'bezierCurveTension'      => 0.2,
		'pointDotRadius'          => 5,
		'pointDotStrokeWidth'     => 5,
		'pointHitDetectionRadius' => 20,
		'datasetStroke'           => true,
		'responsive'              => true,
		'tooltipFillColor'        => $colors[2],
		'tooltipFontColor'        => "#eee",
		'tooltipCornerRadius'     => 0,
		'tooltipYPadding'         => 10,
		'tooltipXPadding'         => 10,
		'barDatasetSpacing'       => 10,
		'barValueSpacing'         => 200

	);
	echo json_encode( $config );
}

function set_post_order_in_admin( $wp_query ) {
	global $pagenow;
	if( isset($_GET['post_type']) ) {
		$post_type = $_GET['post_type'];
	} else $post_type = '';
  	if ( is_admin() && 'edit.php' == $pagenow && $post_type=='lpr_course' && !isset($_GET['orderby'])) {
    	$wp_query->set( 'orderby', 'date' );
    	$wp_query->set( 'order', 'DSC' );
  	}
}
add_filter('pre_get_posts', 'set_post_order_in_admin' );


