<?php
// if we're not uninstalling..
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
    
// disable connection by cobia connect secret
$props = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array( 
                'cobia_token' => get_option('cobia_token'),
                'cobia_connect_secret' => get_option('cobia_connect_secret'),
        ),
        'cookies' => array()
);

wp_remote_post("https://cobiasystems.com/admin/wp/deactivate", $props);

// remove options from database
delete_option('cobia_token');
delete_option('cobia_connect_secret');