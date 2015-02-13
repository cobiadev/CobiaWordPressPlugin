<?php

/*
Plugin Name: Cobia Systems
Plugin URI: http://cobiasystems.com
Description: Helps the end-user migrate and manage their online management system including their web properties, social networking, branding and PR.
Version: 0.1
Author: jimcobia
Author URI: http://cobiasystems.com
License: GNU GPL 2.0
*/

class Cobia_System_Api {
	
	protected $main_url = "https://cobiasystems.com";
	protected $login_url = 'public/account/login_basic';
	protected $retail_url = 'admin/retail/list/';
	protected $register_url = 'admin/wp/register/';
	protected $secret_path = '/cobia/deploy';
	protected $zip_url;
	protected $zip_file;
	protected $plugin_url;
	protected $dir;
	protected $url;
	
	public $has_secret_key;
	public $retail_list;
	public $cobia_token;
	public $cobia_status;
	
	private static $_instance = NULL;

	public static function getInstance() {
		
		if(self::$_instance === NULL) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}
	
	public function __construct() {
		
		$protocol           = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
		
		$this->url         = $protocol . '://' . $_SERVER['SERVER_NAME'];
		
		$this->zip_url     = $this->main_url . "/rest/plugin/test/";
		$this->plugin_url  = $this->main_url . "/rest/";
		$this->zip_file    = $this->zip_url . "example.zip";
		$this->dir         = plugin_dir_path(__FILE__);
		
		// set cobia token, or failed to login message
		$this->cobia_token = get_option('cobia_token');
		
		// set has_secret_key
		$this->has_secret_key = (!get_option('cobia_connect_secret')) ? FALSE : TRUE;
	
	}
	
	public function post($post) {
		
		//Check if log in request (post)	
		if(isset($post['login_form'])) {
			$resp = $this->loginForm($post);		

			if($resp && $resp->Status == 0){
				$this->cobia_status = "Failed to log in";
			} else if($resp->Status == 1) {

				// set cobia token and get retails
				if(isset($resp->CobiaToken) && !empty($resp->CobiaToken)) {
					$this->cobia_token = $resp->CobiaToken;
					update_option('cobia_token', $resp->CobiaToken);

				}
			}	
		}

		//Check if need a retail
		if (!empty($this->cobia_token) && !$this->has_secret_key) {

			// get retail list
			$retail_list = $this->retailForm();
			if($retail_list && $retail_list->Status == 1 && sizeof($retail_list->Data > 0)) {
				$this->retail_list = (array) $retail_list->Data;	
			}
		}
		
		//Finalize registration!
		if(isset($post['submit_list'])) {
			
			$rest = $this->submitList($post);
			if($rest->Status == 1 && isset($rest->Hash) && !empty($rest->Hash)) {
				$this->has_secret_key = TRUE;
				update_option('cobia_connect_secret', $rest->Hash);
			}
			
		}
		
		return $this;
	}
	
	public function get_json() {
		
		$jsonData = file_get_contents($this->plugin_url); 
		$json = json_decode($jsonData);
		
		var_dump($json);
	}
	
	public function parse_zip($url) {
		
		$dir = plugin_dir_path(__FILE__);
		system("rm -rf ".escapeshellarg($dir."cache/*")); //I KNOW, SCARY RIGHT?
		$this->zip_file = $url;
		if($this->get_zip_file()) {
			return $this->unzip();
			
		}
		return FALSE;
	}
	
	public function get_zip_file() {
		
		// if $resp <= 150 bites, that means that the zip file failed
		return (!file_put_contents($this->dir . "temp/example.zip", @fopen($this->zip_file, 'r'))) ? FALSE : TRUE;
	}
	
	public function unzip() {
		
		$zip = new ZipArchive();
		$res = $zip->open($this->dir . "temp/example.zip");
		if ($res === TRUE){
			if($zip->extractTo($this->dir."/cache/")) {
				return $zip->close();
			}
		}
		return FALSE;
	}
	
	public function loginForm($post) {
		
		$props = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => array( 
				'email' => $post['email'],
				'password' => $post['password'],
			),
			'cookies' => array()
		);
		
		return $this->_getResponse(wp_remote_retrieve_body(wp_remote_post($this->plugin_url . $this->login_url, $props)));
		
	}

	//Get a list of retails (after being logged in)
	public function retailForm() {

		$props = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => array( 
				'cobia_token' => $this->cobia_token,
			),
			'cookies' => array()
		);
		
		return $this->_getResponse(wp_remote_retrieve_body(wp_remote_post($this->plugin_url . $this->retail_url, $props)));

	}
	
	public function submitList($post) {
		
		$type = 1;
		$props = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => array( 
				'cobia_token' => $this->cobia_token,
				'retail_id' => $post['retail_id'],
				'url' => $this->url,
				'remote_path' => $this->secret_path,
			),
			'cookies' => array()
		);
		
		return $this->_getResponse(wp_remote_retrieve_body(wp_remote_post($this->plugin_url . $this->register_url, $props)));
	
	}
	
	private function _getResponse($resp) {
		
		if ( empty($resp) ) {
		   $error_message = "Request failed!";
		   echo "Something went wrong: $error_message";
		   return null;	
		} else if ( is_wp_error( $resp ) ) {
		   $error_message = $resp->get_error_message();
		   echo "Something went wrong: $error_message";
		   return null;
		} else {
		   return json_decode($resp);
		}
		
	}


}