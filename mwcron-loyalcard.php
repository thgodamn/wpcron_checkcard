<?php

/**
 * Plugin Name: 
 * Description: 
 * Author:      
 * Version:     
 */

//schedules - timetable
add_filter( 'cron_schedules', 'add_cron_times', 11);
function add_cron_times($timetable){
    $timetable['cron_30m'] = array( //prod
        'interval' => 1800,
        'display' => '30 min'
    );
    return $timetable;
}

// check loyalcard
function cron_function_check_loyalcard() {

    $number = 200;
    $users = get_users( [
        'number' => $number,
        'meta_query'  => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => 'loyalcard_activation',
                    'value'   => 0,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'loyalcard_activation',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'loyalcard_activation',
                    'value' => date('Y-m-d', strtotime("-1 day")),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                ),
            ),
            array(
                'key' => 'has_loyalty_card',
                'value' => 1,
                'compare' => '=',
                'type' => 'NUMERIC'
            ),
        ),
    ] );

    foreach ( $users as $user ) {
        $url = 'http://example.com/check-loyal-card';

        $args = http_build_query(
            array (
                'email' => $user->user_email,
                'name' => $user->first_name,
                'surname' =>  $user->last_name,
                'username' => $user->nickname,
                'password' => 'B0NDwty3LauDcNHwbaS7'
            )
        );
        $res = json_decode(wp_remote_post( $url, $args )['body']);

        if ($res->isValid) { //good request
            update_user_meta($user->ID, 'loyalcard_activation', date("Y-m-d H:i:s"));
        } elseif ($res->hasError) { //bad request
            update_user_meta($user->ID, 'loyalcard_activation', 0);
        }
    }
}

//ACTIVAION
function cronstarter_activation() {
    if( !wp_next_scheduled( 'MWCron' ) ) {
        wp_schedule_event( time(), 'cron_30m', 'MWCron' );
    }
}
register_activation_hook( __FILE__, 'cronstarter_activation' );
add_action ('MWCron', 'cron_function_check_loyalcard');

//DEACTIVATION
function cronstarter_deactivate() {
    $timestamp = wp_next_scheduled ('MWCron');
    wp_unschedule_event ($timestamp, 'MWCron');
}
register_deactivation_hook (__FILE__, 'cronstarter_deactivate');

?>