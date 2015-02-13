<?php

// if we're not uninstalling..
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// remove options from database
delete_option('cobia_token');
delete_option('cobia_connect_secret');