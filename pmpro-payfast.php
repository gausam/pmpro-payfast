<?php
/*
Plugin Name: Paid Memberships Pro - PayFast Gateway
Plugin URI: https://www.paidmembershipspro.com/add-ons/payfast-payment-gateway/
Description: Adds PayFast as a gateway option for Paid Memberships Pro.
Version: 0.8.4
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-payfast
Domain Path: /languages
*/

define( 'PMPRO_PAYFAST_DIR', plugin_dir_path( __FILE__ ) );

// load payment gateway class after all plugins are loaded to make sure PMPro stuff is available
function pmpro_payfast_plugins_loaded() {

	load_plugin_textdomain( 'pmpro-payfast', false, basename( __DIR__ ) . '/languages' );

	// make sure PMPro is loaded
	if ( ! defined( 'PMPRO_DIR' ) ) {
		return;
	}

	require_once( PMPRO_PAYFAST_DIR . '/classes/class.pmprogateway_payfast.php' );
}
add_action( 'plugins_loaded', 'pmpro_payfast_plugins_loaded' );

// Register activation hook.
register_activation_hook( __FILE__, 'pmpro_payfast_admin_notice_activation_hook' );
/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function pmpro_payfast_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmpro-payfast-admin-notice', true, 5 );
}

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmpro_payfast_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmpro-payfast-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the payment settings page</a> to configure the Payfast Gateway.', 'pmpro-payfast' ), esc_url( get_admin_url( null, 'admin.php?page=pmpro-paymentsettings' ) ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmpro-payfast-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmpro_payfast_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmpro_payfast_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'admin.php?page=pmpro-paymentsettings' ) . '">' . __( 'Configure Payfast', 'pmpro-payfast' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmpro_payfast_plugin_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmpro_payfast_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-payfast.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/payfast-payment-gateway/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-payfast' ) ) . '">' . __( 'Docs', 'pmpro-payfast' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-payfast' ) ) . '">' . __( 'Support', 'pmpro-payfast' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_payfast_plugin_row_meta', 10, 2 );

/**
 * Filter the results of the discount code check to make sure
 * fixed-term discounts codes are not applied to recurring subscriptions.
 *
 * @param bool   $okay     True if discount code check is okay. False is there was an error.
 * @param Object $dbcode   Object containing code data from database row.
 * @param int    $level_id Level the user is checking out for.
 * @param string $code     Discount code string.
 */
function pmpro_payfast_pmpro_check_discount_code($okay, $dbcode, $level_id, $code) {
	$discount = new PMPro_Discount_Code( $code );
	$membership_level = new PMPro_Membership_Level( $level_id );
	$is_recurring_membership = (int) $membership_level->expiration_number === 0;
	$is_fixed_term_discount = ! empty( $discount->levels[$level_id] ) &&
		(int) $discount->levels[$level_id]['expiration_number'] <> 0;

	if ( $is_recurring_membership && $is_fixed_term_discount ) {
		return  __( 'The discount code could not be applied. Please contact the administrators.', 'pmpro-payfast' );
	}

	return $okay;
}
add_filter( 'pmpro_check_discount_code', 'pmpro_payfast_pmpro_check_discount_code', 10, 4);
