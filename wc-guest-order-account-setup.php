<?php
/**
 * Plugin Name: WC Guest Order Account Setup
 * Plugin URI:  https://catmanstudios.com
 * Description: Creates accounts or maps orders to existing account from billing email
 * Version:     0.0.0
 * Author:      Bryan Headrick
 * Author URI:  https://catmanstudios.com
 * Donate link: https://catmanstudios.com
 * License:     GPLv2
 * Text Domain: wc-guest-order-account-setup
 * Domain Path: /languages
 *
 * @link    https://catmanstudios.com
 *
 * @package WC_Guest_Order_Account_Setup
 * @version 0.0.0
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2019 Bryan Headrick (email : info@catmanstudios.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Autoloads files with classes when needed.
 *
 * @since  0.0.0
 * @param  string $class_name Name of the class being requested.
 */
function wc_guest_order_account_setup_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'WCGOAS_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'WCGOAS_' ) ) ) );

	// Include our file.
	WC_Guest_Order_Account_Setup::include_file( 'includes/class-' . $filename );
}
spl_autoload_register( 'wc_guest_order_account_setup_autoload_classes' );

/**
 * Main initiation class.
 *
 * @since  0.0.0
 */
final class WC_Guest_Order_Account_Setup {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	const VERSION = '0.0.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  0.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    WC_Guest_Order_Account_Setup
	 * @since  0.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of WCGOAS_Order_account_check
	 *
	 * @sinceundefined
	 * @var WCGOAS_Order_account_check
	 */
	protected $order_account_check;

	protected $email_host;
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.0.0
	 * @return  WC_Guest_Order_Account_Setup A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  0.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.0.0
	 */
	public function plugin_classes() {

		$this->order_account_check = new WCGOAS_Order_account_check( $this );
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  0.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action('woocommerce_checkout_update_order_meta', array($this, 'save_data'), 100, 2);

        //Runs on User Registration

	}
	public function initial_match_past_orders( $user_id ) {

		//Get current users's e-mail from ID
		$current_user = get_user_by( 'ID', $user_id );
		$email = $current_user->user_email;

		//Pulls all orders made with the user e-mail as the billing e-mail
		$customer_orders = get_posts( array(
			'meta_key'    => '_billing_email',
			'meta_value'  => "$email",
			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'numberposts'=>-1
		) );

		//If matching orders are found..
		if (!empty($customer_orders)) {

			global $wpdb;
			$prefix = $wpdb->prefix;
			$table=$prefix.'woocommerce_downloadable_product_permissions';
			$data = array('user_id'=>"$user_id");
			$where = array('user_email'=>"$email",'user_id'=>'0');

			//Updates all downloads with the same e-mail but user_id=0 (guest) to the correct user ID
			$wpdb->update( $table, $data, $where, $format = null, $where_format = null );


			//Updates all WC Orders with the e-mail to map to the correct user ID
			foreach($customer_orders as $k => $v){

				$order_id = $customer_orders[ $k ]->ID;
				update_post_meta( $order_id, '_customer_user', $user_id, 0);
			}
		}
	}

	public function returning_match_past_orders($user_login, $current_user) {

		//Gets current user ID and e-mail
		$user_id = $current_user->ID;

		$email = $current_user->user_email;


		//Pulls all orders made with the user e-mail as the billing e-mail but is a guest order
		$customer_orders = get_posts( array(

			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'numberposts'=>-1,
            'meta_query'=>[
	            'relation' => 'AND',
                'email'=>[
                        'key'=>'_billing_email',
                        'value'=>"$email"
                ],
                'customer_user'=>[
                        'key'=>'_customer_user',
                    'value'=>'0'
                ]
            ]
		) );

		//If matching orders are found..

		if (!empty($customer_orders)) {

			global $wpdb;
			$prefix = $wpdb->prefix;
			$table=$prefix.'woocommerce_downloadable_product_permissions';
			$data = array('user_id'=>"$user_id");
			$where = array('user_email'=>"$email",'user_id'=>'0');


			//Updates all downloads with the same e-mail but user_id=0 (guest) to the correct user ID
			$wpdb->update( $table, $data, $where, $format = null, $where_format = null );


			//Updates all WC Orders with the e-mail to map to the correct user ID
			foreach($customer_orders as $k => $v){

				$order_id = $customer_orders[ $k ]->ID;
				update_post_meta( $order_id, '_customer_user', $user_id, 0);

			}
		}

	}
	public function save_data($order_id, $posted){
		$email = $posted['billing_email'];
		$user_id = $this->find_user($email, $posted);
		if(strpos($email, '@' . $this->email_host)){


			if($user_id){
				WC()->session->delete_session($user_id);

				$usermeta = get_user_meta($user_id);

				foreach($usermeta as $key=>$val){
					if(strpos($key, 'billing')!==false || strpos($key, 'shipping')!== false){
						update_user_meta($user_id, $key, '');
					}

				}

			}
			return;
		}
	    if(!get_post_meta($order_id, '_customer_user', true) || get_post_meta($order_id, '_customer_user', true) !== $user_id){

		    update_post_meta($order_id, '_customer_user', $user_id);

        }

    }

    public function find_user($email, $posted = null){

	    $user = get_user_by('email', $email);

	    if(!$user){
		    $user_id = $this->create_user($email);
		    if($posted !== null){
			    foreach($posted as $key=>$val){
				    if(strpos($key, 'billing')!==false || strpos($key, 'shipping')!== false){
					    update_user_meta($user_id, $key, $val);
				    }
            }

            }
            return $user_id;
        }else{
	        return $user->ID;
        }
    }

    private function create_user($email){

	    $username = wc_create_new_customer_username( $email, [] );
	    $password           = wp_generate_password();
	    $new_customer_data = apply_filters(
		    'woocommerce_new_customer_data',
			    array(
				    'user_login' => $username,
				    'user_pass'  => $password,
				    'user_email' => $email,
				    'role'       => 'customer'
		    )
	    );

	    $customer_id = wp_insert_user( $new_customer_data );

	    if ( is_wp_error( $customer_id ) ) {
		    error_log('could not register user: ' . $email);
		    return false;
	    }

	    return $customer_id;
    }

	/**
	 * Activate the plugin.
	 *
	 * @since  0.0.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  0.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	/**
	 * Init hooks
	 *
	 * @since  0.0.0
	 */
	public function init() {


	    if(defined('WP_LIVE_DOMAIN')){
	        $url = WP_LIVE_DOMAIN;
        }else{
		    $url = get_site_url();
        }
		$parsed = parse_url($url);
		$host =  $parsed['host'];

		$host = implode('.',array_slice( explode('.', $host), -2));

		error_log($host);


	    $this->email_host = $host;

		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load translated strings for plugin.
		load_plugin_textdomain( 'wc-guest-order-account-setup', false, dirname( $this->basename ) . '/languages/' );

		// Initialize plugin classes.
		$this->plugin_classes();
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  0.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {

		// Do checks for required classes / functions or similar.
		// Add detailed messages to $this->activation_errors array.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  0.0.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( 'WC Guest Order Account Setup is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wc-guest-order-account-setup' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'order_account_check':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path.
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

/**
 * Grab the WC_Guest_Order_Account_Setup object and return it.
 * Wrapper for WC_Guest_Order_Account_Setup::get_instance().
 *
 * @since  0.0.0
 * @return WC_Guest_Order_Account_Setup  Singleton instance of plugin class.
 */
function wc_guest_order_account_setup() {
	return WC_Guest_Order_Account_Setup::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( wc_guest_order_account_setup(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( wc_guest_order_account_setup(), '_activate' ) );
register_deactivation_hook( __FILE__, array( wc_guest_order_account_setup(), '_deactivate' ) );
