<?php

/*
Plugin Name: WP Plugins Data
Author: 8guild
Version: 1.0.0
Text-domain: 8guild
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

add_filter( 'json_url_prefix', 'replace_wp_json');
add_filter( 'rest_url_prefix', 'replace_wp_json');
function replace_wp_json( $slug ) {
    return 'api';
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'plugins', '/list', array(
        'methods' => 'GET',
        'callback' => 'get_plugins_data'
    ) );

    register_rest_route( 'plugin', '.+', array(
        'methods' => 'GET',
        'callback' => 'get_plugins_data'
    ) );

} );

function get_plugins_data( WP_REST_Request $request ) {

    $dir = plugin_dir_path( __FILE__ ) . 'data/';
    $dir_url = plugin_dir_url( __FILE__ ) . 'data/';
    $files = scandir( $dir );
    rsort( $files );

    $files = array_reverse( $files );
    $out = array();
    foreach( $files as $file ){
        if ( $file != '.' && $file != '..' ) {
            $name = explode('.', $file)[0];
            $out[$name][] = $file;
        }
    }

    foreach ( $out as $plugin => $versions ) {
        usort( $versions, 'version_compare' );
        $out[$plugin] = array_reverse( $versions );
    }

    $route =  explode( '/', $request->get_route() );

    if ( $route[1] == 'plugins' ) {
        return array( 'type' => 'json', 'content' =>array( 'plugins' => $out ) );
    }
    else if ( $route[1] == 'plugin' ) {

        $name = explode('.', $route[2] )[0];
        if ( !isset( $route[3] ) ) {
            if ( isset( $out[$name] ) ) {
                return array( 'type' => 'html', 'content' => $dir_url . $out[$name][0] );
            }
            else {
                return array( 'type' => 'json', 'content' => 'There is no such plugin here' );
            }
        }
        else {
            if ( !preg_match( '/\.zip/', $route[3] ) )
                $file = $route[3] . '.zip';
            else
                $file = $route[3];
            if ( in_array( $file, $out[$name] ) ) {
                return array( 'type' => 'html', 'content' => $dir_url . $file );
            }
            else {
                //return new WP_Error( 'awesome_no_author', 'Invalid author', array( 'status' => 404 ) );
                return array( 'type' => 'json', 'content' => 'There is no such plugin version here' );
            }
        }

    }

}



add_filter( 'rest_pre_serve_request', 'custom_rest_pre_serve_request', 999, 4 );
function custom_rest_pre_serve_request( $served, $result, $request, $server ) {

    if ( $result->data['type'] == 'html' ) {

        header('Location: ' . $result->data['content'] );
        $served = true;

    }
    else {
        $result->data = $result->data['content'];
        http_response_code(404);
    }

    return $served;

}



?>