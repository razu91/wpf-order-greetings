<?php
/**
 * Plugin Name: WP Funnels Order Greetings
 * Plugin URI: https://github.com/razu91/wpf-order-greetings
 * Description: Custom order greetings plugin for WP Funnels. This sends a simple greetings each time order count reaches 100.
 * Author: Razu
 * Author URI: https://github.com/razu91
 * Text Domain: wpf-order-greetings
 * Domain Path: /languages
 * Version: 1.0.0
 * License: GPL 3.0 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * 
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

 define( 'WPF_OG_VERSION', '1.0.0' );

 register_activation_hook( __FILE__, 'wpf_og_install' );

 /**
 * Creates the wpf_og_customers table if it doesn't exist and updates the plugin version.
 *
 * @global wpdb $wpdb
 */
 function wpf_og_install() {
    
    if (!get_option( 'wpf_og_version' ) ) {

        global $wpdb;

        $collate = '';

        if($wpdb->has_cap('collation')){
            $collate = $wpdb->get_charset_collate();
        }

        $table_name = $wpdb->prefix . 'wpf_og_customers';

        $table_schema = "
        CREATE TABLE {$table_name} (
            ID BIGINT UNSIGNED NOT NULL auto_increment,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            is_sent tinyint(1) NOT NULL default 0,
            PRIMARY KEY (ID),
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY email (email),
            KEY is_sent (is_sent)
        ) $collate;
        ";

        if(!function_exists('dbDelta')){
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $table_schema );

        // Check if installed.
        if($wpdb->get_row("SHOW TABLES LIKE '$table_name'")){
            update_option('wpf_og_version',WPF_OG_VERSION);
        }

    }
 }

 add_action( 'wpfunnels/funnel_order_placed', 'wpf_og_collect_user_data' );

 /**
 * Collects user data for a given order.
 *
 * @param int $order_id The ID of the order.
 * @global wpdb $wpdb
 */
 function wpf_og_collect_user_data($order_id)
 {
    global $wpdb;

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    try {

        $customer = new \WC_Customer( $order->get_customer_id() );

        $check = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}wpf_og_customers WHERE user_id = %d;",
                $customer->get_id()
            )
        );

        if ( $check ) {
            // Greetings already sent for this customer.
            return;
        }

        $checkout_count = (int) get_option('wpf_og_count',0) + 1;

        $wpdb->insert($wpdb->prefix. 'wpf_og_customers',[
            'order_id' => $order->get_id(),
            'user_id'  => $customer->get_id(),
            'name'     => $customer->get_billing_first_name() .''.$customer->get_billing_last_name(),
            'email'    => $customer->get_billing_email(),
        ]);


        // store user data.
        if ( $checkout_count === 100 ) {
            // send emails.
            wpf_og_schedule_greeting_emails();
            $checkout_count = 0; // reset count.
        }

        update_option( 'wpf_og_count', $checkout_count );
        // store customer data.

    } catch (\Throwable $th) {
        error_log('Error in wpf_og_collect_user_data: ' . $e->getMessage());
    }
 }

function wpf_og_schedule_greeting_emails(){
    WC()->queue()->schedule_single( time() + 1, 'wpf_og_send_greetings_emails' );
}


add_action( 'wpf_og_send_greetings_emails', 'wpf_og_greetings_email_sender' );

/**
 * Sends greeting emails to customers who haven't received them yet.
 *
 * @global wpdb $wpdb
 */
function wpf_og_greetings_email_sender() {

    global $wpdb;

    $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpf_og_customers WHERE is_sent = 0 LIMIT 100;" );

    if ( ! $results ) {
        return;
    }


    $subject   = "Welcome to {site_name}";
    $greetings = "Hi {customer_name}!\n.";
    $greetings .= "Thank you for your recent purchase from {site_name}!\n.";
    $greetings .= "We’re excited to have you as a customer and, we’re confident you’ll love your new {products}.";

    // Placeholder data.
    $replacements = [
        '{site_name}'     => get_bloginfo( 'name' ),
        '{customer_name}' => '',
        '{products}'      => '',

    ];

    foreach( $results as $item ) {

        $order = wc_get_order( $item->order_id );

        if ( ! $order ) {
            continue;
        }

        $products = wpf_og_get_product_names( $order);

        // Set placeholders.
        $replacements['{customer_name}'] = $item['name'];
        $replacements['{products}']      = implode( ',', $products );

        // Compile subject & email body
        $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
        $message = str_replace( array_keys( $replacements ), array_values( $replacements ), $greetings );

        // Send the greetings.
        wp_mail( $item['email'], $subject, $message );
        // Batch delete
        $processed[] = $item->ID;
    }

    // Batch delete.
    if (!empty($processed)) {
       $processed = implode( ',', $processed );
       $wpdb->query( "DELETE FROM {$wpdb->prefix}wpf_og_customers WHERE ID IN({$processed});" );
    }

}

/**
 * Retrieves the names of products from a given WooCommerce order.
 *
 * @param \WC_Order $order The WooCommerce order object.
 * @return array List of product names.
 */
function wpf_og_get_product_names( \WC_Order $order): array {

    $products = [];

    foreach ( $order->get_items() as $line_item ) {
        $products[] = $line_item->get_name();
    }

    return $products;

}