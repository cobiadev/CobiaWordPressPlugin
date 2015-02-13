<?php

if( !class_exists( 'Cobia_System_Admin' ) ) {
	
	require_once "main.php";
	
	class Cobia_System_Admin extends Cobia_System_Plugin {
		
		public $data;
		
		function __construct( ) {
			
			parent::__construct();
			
			// handle returned post data from the cobia api
			$this->data = isset($_POST) ? Cobia_System_Api::getInstance()->post($_POST) : NULL;
			
			add_action( 'init', array( $this, 'register_session') );
			add_action( 'admin_menu' , array( $this, 'plugin_menu' ) );
			add_action( 'admin_init' , array( $this, 'plugin_settings' ) );
		
		}

		function plugin_menu() {
			
			add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'settings_link' ) );
			$plugin_page = add_options_page( __( 'Cobia System', $this->slug ), __( 'Cobia System', $this->slug ), 'manage_options', $this->slug, array( $this, 'plugin_options' ) );
			add_action( 'admin_head-' . $plugin_page, array( $this, 'plugin_panel_styles' ) );
			add_action( 'load-' . $plugin_page, array( $this, 'notice_hook' ) );
		}

		function notice_hook() {
			
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}

		function notice() {
			
			echo '<div class="updated"><p>' . sprintf( __( 'If you find this plugin useful please consider giving it a %sfive star%s rating.', $this->slug ), '<a target="_blank" href="http://google.com/' . $this->slug . '?rate=5#postform">', '</a>' ) . '</p></div>';
			if (get_option('permalink_structure', '') == "") {
				echo '<div class="error"><p>Your permalinks option is set to Default. This setting must be changed to another value in order for the Cobia System to work. <a href="/wp-admin/options-permalink.php">Click here to go to the page to change this setting.</a></p></div>';
			}
		}

		//Adds additional links under this plugin on the WordPress Plugins page
		function settings_link( $links ) {
			
			array_unshift( $links, '<a href="' . admin_url( 'options-general.php?page=' . $this->slug ) . '">' . __( 'Settings', $this->slug ) . '</a>' );
			$links[] = '<a href="#" target="_blank">' .  __( 'More Plugins', $this->slug ) . '</a>';
			return $links;
		}

		function plugin_settings() {
			
		}
		
		function register_session() {
			if(!session_id()) session_start();
		}

		//Additional CSS for the plugin options page
		function plugin_panel_styles() {
			
			echo '<style type="text/css">
			#title_404, #title_401 { margin-bottom:5px;padding:3px 8px;font-size:1.7em;line-height:100%;height:1.7em;width:100%;outline:0;margin:1px 0; }
			#icon-' . $this->slug . '{ background:transparent url(\'' . plugin_dir_url( __FILE__ ) . 'screen-icon.png\') no-repeat; }</style>';
		}

		function plugin_options() {
			
			if(isset($this->data->cobia_status) && $this->data->cobia_status == 'Failed to log in') {
				$this->showLoginForm(TRUE);
			} else if(!empty($this->data->cobia_token) && !$this->data->has_secret_key) {
				$this->loginSuccessfull();
			} else if($this->data->has_secret_key) {
				$this->showConnected();
			} else {
				$this->showLoginForm();
			}
			
			
		}
		
		public function loginSuccessfull() {
			
			?>
			<div class="wrap">
				<?php screen_icon( $this->slug ); ?>
				<h2><?php _e( 'Cobia System', $this->slug ); ?></h2>
				
				<form method="post">
					<table>
						<tr>
							<td>Select Business List</td>
							<td>
								<select name="retail_id">
									<option disabled>Select list</option>
									<?php if($this->data->retail_list){ ?>
										<?php foreach($this->data->retail_list as $key => $val) { ?>
										<option value="<?php echo $val->id; ?>"><?php echo $val->name; ?></option>
										<?php } ?>
									<?php } ?>
								</select>
							</td>
						</tr>
						<tr>
							<td></td>
							<td><input type="submit" name="submit_list" value="Submit"></td>
						</tr>
					</table>
				</form>
			</div>
		<?php
		}
		
		public function showConnected() {
			
			?>
			<div class="wrap">
				<?php screen_icon( $this->slug ); ?>
				<h2><?php _e( 'Cobia System', $this->slug ); ?></h2>				
				<table>
					<tr>
						<td>Status: Connected!</td>
					</tr>
				</table>
				
			</div>
		<?php
		}
		
		public function showLoginForm($failed = FALSE) {
			
		?>
			<div class="wrap">
				<?php screen_icon( $this->slug ); ?>
				<h2><?php _e( 'Cobia System', $this->slug ); ?></h2>

				<?php echo $failed ? '<div style="color:#ff0000;font-weight:800;">Invalid username or password</div>' : ''; ?>
				
				<form method="post">
					<table>
						<tr>
							<td>Email</td>
							<td><input type="text" name="email" placeholder="Email goes here"></td>
						</tr>
						<tr>
							<td>Password</td>
							<td><input type="password" name="password" placeholder="Password goes here"></td>
						</tr>
						<tr>
							<td></td>
							<td><input type="submit" name="login_form" value="Login"></td>
						</tr>
					</table>
				</form>
				<p>Don't have an account? <a href="http://cobiasystems.com/signup" target="_blank">Click here</a> to create one.</p>
			</div>
		<?php
		}
	}

	$cobia_System_Admin = new Cobia_System_Admin;
}