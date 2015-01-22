<?php

/*
Plugin Name: Cobia Systems
Plugin URI: http://cobiasystems.com
Description: Cobia Systems
Author: Cobia Systems
Author URI: http://cobiasystems.com
Version: 0.1
*/

if( !class_exists( 'Cobia_System_Pages' ) && !class_exists( 'Cobia_System_Plugin' ) ) {
	
	require_once "main.php";
	
	class Cobia_System_Plugin {
		
		var $options;
		var $basename;
		var $slug;
		var $defaults;
		
		function __construct() {
			
			$this->basename = plugin_basename( __FILE__ );
			$this->slug = str_replace( array( basename( __FILE__ ), '/' ), '', $this->basename );
			$this->options = get_option( 'cobia_' . $this->slug );
			

			//Set the default text to display on error pages
			$this->defaults = array();
			$this->defaults['title_404'] = '404 Not Found ';
			
			add_action('init', array($this, 'plugin_init'));
			
			add_action('template_redirect', array($this, 'custom_redirect'));
		}

		function plugin_init() {
			
			//load_plugin_textdomain( $this->slug, FALSE, $this->slug . '/languages' );
		}
		
		function custom_redirect() {
			
			global $wp_query, $page, $paged;
			
			// initialize rest response
			$rest = new stdClass();
			$rest->Status = 0;
			$rest->Message = '';
			     
			if ( $wp_query->is_404() ) {
				
				if (strpos($_SERVER['REQUEST_URI'], '/cobia/deploy') === 0) {
					
					// handle errors, like missing or incorrect secret
					if(!isset($_POST['secret']) ||
					   isset($_POST['secret']) && empty($_POST['secret'])) {
						$rest->Message = 'Error: secret is not set.';
						die(json_encode($rest));
					} else if($_POST['secret'] != get_option('cobia_connect_secret')) {
						$rest->Message = 'Error: secret set but does not match.';
						die(json_encode($rest));
					}
					
					
					if(!Cobia_System_Api::getInstance()->parse_zip($_POST['url'])) {
						$rest->Message = 'Error deploying file!';
						die(json_encode($rest));
						
					} else {
						
						status_header(200);
						
						/**
						 * Tell google and bing about new sitemap
						 * leave this commented out for now, it takes a while or may not even work on users systems
						 * 
						$google = "http://www.google.com/webmasters/tools/ping?sitemap=";
						$bing = "http://www.bing.com/ping?sitemap=";
						
						$url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
						$url .= '://' . $_SERVER['SERVER_NAME'];
						$url .= "/sitemap.xml";
						
						system("ping " . $google . urlencode($url));
						system("ping " . $bing . urlencode($url));
						
						*/
						
						$rest->Status = 1;
						$rest->Message = 'Success!';
						
						die(json_encode($rest));
						
					}
	
				}
				
				if ($_SERVER['REQUEST_URI'] == '/custom/secret/json') {
					Cobia_System_Api::getInstance()->get_json();
				}
				
				//verify file exists
				$dir = plugin_dir_path(__FILE__);
				$home_url = get_home_url();
				$path_arr = parse_url($home_url);
				$subdir = (isset($path_arr['path'])) ? $path_arr['path'] : '';
				$path = $dir.'cache'.str_replace($subdir, '', $_SERVER['REQUEST_URI']);
				if (file_exists($path) && strpos($path, '.') > 0) {
					$ext = pathinfo($path, PATHINFO_EXTENSION);
					status_header(200);
					if (strtolower($ext) == 'html') {
						
						$wp_query->is_404 = false;
						$cobia_System_Pages = new Cobia_System_Pages(array('options' => $this->options, 'path' => $path));						
					} else {
						if ($ext == 'gz') $ext = 'x-gzip';
						header('Content-type: application/'.$ext);
						die(file_get_contents($path));
					}
				}
			}
		}
	}

	class Cobia_System_Pages {
		
		var $http_code;
		public $path;
		
		function __construct( $args ) {
			// options not needed right now
			$this->options = $args['options'];
			$this->path = $args['path'];
			$this->generate_page($this);
		}

		function get_template() {
			
			$buffer = false;
			
			if (file_exists($this->path)) {
				$buffer = file_get_contents($this->path);
			}
			
			return $buffer;
		}
		
		function generate_page() {
			
			$contents = $this->get_template();

			// maybe redirect home if there is not template file
			if ($contents == false) {	 }
			
			get_header();

			echo '
			<div id="primary">
			<div id="content" role="main">
			<article class="post type-post status-publish format-standard hentry">
			<div class="entry-content">
			'.$contents.'
			</div><!-- .entry-content -->
			</article>

				
			</div><!-- #content -->
		</div><!-- #primary -->
			';
			//echo $contents;
			
			get_sidebar();
			
			get_footer();
			
			exit;
		}
	}

	$cobia_System_Plugin = new Cobia_System_Plugin;
}

if( is_admin() ) {
	require_once dirname( __FILE__ ) . '/admin_options.php';
}