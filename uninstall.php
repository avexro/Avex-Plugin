<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://avex.ro
 * @since      1.0.0
 *
 * @package    Dropshipping_Romania_Avex
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-activator.php';
$activator=new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Activator;
$activator->deleteTables();

$avex_hooks=array("dropshipping_romania_avex_import_feed_from_admin_hook","dropshipping_romania_avex_import_feed_cron_hook","dropshipping_romania_avex_import_api_cron_hook","dropshipping_romania_avex_import_orders_cron_hook","dropshipping_romania_avex_import_feed_from_cron_reschedule_hook","dropshipping_romania_avex_import_delete_products_hook","dropshipping_romania_avex_import_invoices_cron_hook","dropshipping_romania_avex_import_invoices_from_cron_reschedule_hook","dropshipping_romania_avex_prepare_products_for_import_hook");
if(isset($avex_hooks) && is_array($avex_hooks)  && count($avex_hooks)>0)
{
    foreach($avex_hooks as $hook)
        as_unschedule_all_actions(sanitize_text_field($hook),array(),"dropshipping_romania_avex");
}