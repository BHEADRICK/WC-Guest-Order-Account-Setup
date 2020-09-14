<?php
/**
 * WC Guest Order Account Setup Order_account_check.
 *
 * @since   0.0.0
 * @package WC_Guest_Order_Account_Setup
 */

/**
 * WC Guest Order Account Setup Order_account_check.
 *
 * @since 0.0.0
 */
class WCGOAS_Order_account_check {
	/**
	 * Parent plugin class
	 *
	 * @var   WC_Guest_Order_Account_Setup
	 *
	 * @since 0.0.0
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.0
	 *
	 * @param  WC_Guest_Order_Account_Setup $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// If we have WP CLI, add our commands.
		if ( $this->verify_wp_cli() ) {
			$this->add_commands();
		}
	}

	/**
	 * Check for WP CLI running.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if WP CLI currently running.
	 */
	public function verify_wp_cli() {
		return ( defined( 'WP_CLI' ) && WP_CLI );
	}

	/**
	 * Add our commands.
	 *
	 * @since  0.0.0
	 */
	public function add_commands() {
		WP_CLI::add_command( 'wc_guest_order_account_setup', array( $this, 'wc_guest_order_account_setup_command' ) );
	}

	/**
	 * Create a method stub for our first CLI command.
	 *
	 * @since 0.0.0
	 */
	public function wc_guest_order_account_setup_command($args, $assoc_args) {


		if(isset($assoc_args['date'])){
			$strdate = date('Y-m-d', strtotime($assoc_args['date']));

			$orders = get_posts([
				'post_type'=>'shop_order',
				'posts_per_page'=>-1,
				'post_status'=>'any',
				'date_query' => array(
					array(
						'after' => $strdate,
						'column' => 'post_date',
					),
				),
			]);

			foreach($orders as $order){
				$email = get_post_meta($order->ID, '_billing_email', true);
				$customer_id = get_post_meta($order->ID, '_customer_user', true);

				$user_id = $this->plugin->find_user($email);

				if(!$customer_id || (int) $user_id !==(int)$customer_id){

					error_log("updating order: " .$order->ID);
					update_post_meta($order->ID, '_customer_user', $user_id);
				}
			}

		}
	}
}
