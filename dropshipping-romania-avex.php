<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://avex.ro
 * @since             1.0.0
 * @package           Dropshipping_Romania_Avex
 *
 * @wordpress-plugin
 * Plugin Name:       Dropshipping Romania AVEX
 * Plugin URI:        https://avex.ro
 * Description:       Importer and Supplier in the Dropshipping system. B2B platform, importer prices.
 * Version:           1.0.0
 * Author:            Stefan Nick
 * Author URI:        https://avex.ro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dropshipping-romania-avex
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DROPSHIPPING_ROMANIA_AVEX_VERSION', '1.0.0' );
define( 'DROPSHIPPING_ROMANIA_AVEX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DROPSHIPPING_ROMANIA_AVEX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$upload_dir = wp_upload_dir();
if(!empty($upload_dir['basedir']))
    define( 'DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH', $upload_dir['basedir'].'/dropshipping-romania-avex/' );
else
    return;

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dropshipping-romania-avex-activator.php
 */
function activate_dropshipping_romania_avex() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-activator.php';
	$activator = new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Activator;
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dropshipping-romania-avex-deactivator.php
 */
function deactivate_dropshipping_romania_avex() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-deactivator.php';
	$deactivator = new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Deactivator;
    $deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_dropshipping_romania_avex' );
register_deactivation_hook( __FILE__, 'deactivate_dropshipping_romania_avex' );

// Creating table whenever a new blog is created
function avex_new_blog_dropshipping_romania_avex_plugin_check($blog_id, $user_id, $domain, $path, $site_id, $meta) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-activator.php';
    $activator=new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Activator;
    $activator->on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta);
}
add_action( 'wpmu_new_blog', 'avex_new_blog_dropshipping_romania_avex_plugin_check', 10, 6 );

// Deleting the table whenever a blog is deleted
function avex_on_delete_blog_dropshipping_romania_avex_plugin_check( $tables ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-activator.php';
    $activator=new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Activator;
    return $activator->on_delete_blog($tables);
}
add_filter( 'wpmu_drop_tables', 'avex_on_delete_blog_dropshipping_romania_avex_plugin_check' );

function dropshipping_romania_avex_check_version_plugin_check() {
    if (DROPSHIPPING_ROMANIA_AVEX_VERSION !== get_option('DROPSHIPPING_ROMANIA_AVEX_VERSION')){
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex-activator.php';
        $activator=new DropshippingRomaniaAvex\Dropshipping_Romania_Avex_Activator;
        $activator->versionChanges();
    }
}
add_action('plugins_loaded', 'dropshipping_romania_avex_check_version_plugin_check');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dropshipping-romania-avex.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function avex_run_dropshipping_romania_avex_plugin_start() {

    $plugin = new DropshippingRomaniaAvex\Dropshipping_Romania_Avex();
    $plugin->run();

}
avex_run_dropshipping_romania_avex_plugin_start();

function avex_run_dropshipping_romania_avex_plugin_admin_main_page(){
    require_once dirname( __FILE__ )  . '/admin/partials/dropshipping-romania-avex-admin-display.php';
}
add_action('admin_menu', 'avex_admin_menu_dropshipping_romania_avex_plugin_menu_items');
function avex_admin_menu_dropshipping_romania_avex_plugin_menu_items()
{
    require_once dirname( __FILE__ )  . '/admin/partials/dropshipping_romania_avex_svg.php';
    add_menu_page( "AVEX", "AVEX", "administrator", "dropshipping-romania-avex", "avex_run_dropshipping_romania_avex_plugin_admin_main_page", $avex_icon, 54.9);
    add_submenu_page( "dropshipping-romania-avex", __('Dashboard','dropshipping-romania-avex'), __('Dashboard','dropshipping-romania-avex'), "administrator", "dropshipping-romania-avex","avex_run_dropshipping_romania_avex_plugin_admin_main_page",1);
    add_submenu_page( "dropshipping-romania-avex", __('Config','dropshipping-romania-avex'), __('Config','dropshipping-romania-avex'), "administrator", "dropshipping-romania-avex-config","avex_run_dropshipping_romania_avex_plugin_admin_main_page",2);
    add_submenu_page( "dropshipping-romania-avex", __('Invoices','dropshipping-romania-avex'), __('Invoices','dropshipping-romania-avex'), "administrator", "dropshipping-romania-avex-invoices","avex_run_dropshipping_romania_avex_plugin_admin_main_page",3);
    add_submenu_page( "dropshipping-romania-avex", __('Logs','dropshipping-romania-avex'), __('Logs','dropshipping-romania-avex'), "administrator", "dropshipping-romania-avex-logs","avex_run_dropshipping_romania_avex_plugin_admin_main_page",4);
    add_submenu_page( "dropshipping-romania-avex", __('Help','dropshipping-romania-avex'), __('Help','dropshipping-romania-avex'), "administrator", "dropshipping-romania-avex-help","avex_run_dropshipping_romania_avex_plugin_admin_main_page",4);
}

add_filter( 'plugin_action_links', 'dropshipping_romania_avex_show_plugin_admin_settings_link', 10, 2 );

function dropshipping_romania_avex_show_plugin_admin_settings_link( $links, $file ) 
{
    if ( $file == plugin_basename(dirname(__FILE__) . '/dropshipping-romania-avex.php') ) 
    {
        $links = array_merge(array('<a href="'.esc_url(admin_url().'admin.php?page=dropshipping-romania-avex').'">'.__('Dashboard','dropshipping-romania-avex').'</a>'),$links);
    }
    return $links;
}

add_action( 'wp_ajax_dropshipping_romania_avex_get_logs', 'dropshipping_romania_avex_get_logs' );
function dropshipping_romania_avex_get_logs() {
    check_ajax_referer( 'dropshipping_romania_avex_ajax_get_logs_nonce', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->getLogsAjax();
    wp_die();
}

add_action( 'wp_ajax_dropshipping_romania_avex_get_import_feed_status', 'dropshipping_romania_avex_get_import_feed_status' );
function dropshipping_romania_avex_get_import_feed_status() {
    check_ajax_referer( 'dropshipping_romania_avex_setup_step_2_feed_status', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->getImportFeedStatusAjax();
    wp_die();
}

//import Feed from admin
add_action( 'dropshipping_romania_avex_import_feed_from_admin_hook', 'dropshipping_romania_avex_import_feed_from_admin', 10, 2 );
function dropshipping_romania_avex_import_feed_from_admin($publish_products, $override_products) {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importFeedFromAdmin($publish_products,$override_products);
}

//import Feed Cron
add_action( 'dropshipping_romania_avex_import_feed_cron_hook', 'dropshipping_romania_avex_import_feed_cron', 10 );
function dropshipping_romania_avex_import_feed_cron() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importFeedFromCron();
}
//API Cron
add_action( 'dropshipping_romania_avex_import_api_cron_hook', 'dropshipping_romania_avex_import_api_cron', 10 );
function dropshipping_romania_avex_import_api_cron() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importApiFromCron();
}
//Orders Cron
add_action( 'dropshipping_romania_avex_import_orders_cron_hook', 'dropshipping_romania_avex_import_orders_cron', 10 );
function dropshipping_romania_avex_import_orders_cron() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importOrdersFromCron();
}
//Invoices Cron
add_action( 'dropshipping_romania_avex_import_invoices_cron_hook', 'dropshipping_romania_avex_import_invoices_cron', 10 );
function dropshipping_romania_avex_import_invoices_cron() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importInvoicesFromCron();
}
//import Feed from admincron rescheduler
add_action( 'dropshipping_romania_avex_import_feed_from_cron_reschedule_hook', 'dropshipping_romania_avex_import_feed_from_cron_reschedule', 10, );
function dropshipping_romania_avex_import_feed_from_cron_reschedule() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->rescheduleImportFeedFromCron();
}
//import Invoices from admincron rescheduler
add_action( 'dropshipping_romania_avex_import_invoices_from_cron_reschedule_hook', 'dropshipping_romania_avex_import_invoices_from_cron_reschedule', 10, );
function dropshipping_romania_avex_import_invoices_from_cron_reschedule() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->rescheduleImportInvoicesFromCron();
}
//delete products scheduler
add_action( 'dropshipping_romania_avex_import_delete_products_hook', 'dropshipping_romania_avex_import_delete_products', 10, );
function dropshipping_romania_avex_import_delete_products() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->deleteAllProducts();
}


add_filter( 'woocommerce_admin_order_data_after_payment_info', 'dropshipping_romania_avex_show_order_page_actions', 10, 1 );
function dropshipping_romania_avex_show_order_page_actions($order)
{
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->getOrderPageActions($order);
}

//ajax awb upload
add_action( 'wp_ajax_dropshipping_romania_avex_upload_awb', 'dropshipping_romania_avex_upload_awb' );
function dropshipping_romania_avex_upload_awb() {
    check_ajax_referer( 'dropshipping_romania_avex_upload_awb', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->uploadAvexAWB();
    wp_die();
}

//ajax cancel order
add_action( 'wp_ajax_dropshipping_romania_avex_cancel_order', 'dropshipping_romania_avex_cancel_order' );
function dropshipping_romania_avex_cancel_order() {
    check_ajax_referer( 'dropshipping_romania_avex_cancel_order', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->cancelAvexOrder();
    wp_die();
}

//wc admin add new columns for Avex orders
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'dropshipping_romania_avex_custom_shop_order_column', 999 );
function dropshipping_romania_avex_custom_shop_order_column($columns)
{
    $reordered_columns = array();
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            $reordered_columns['avex_order_id'] = __( 'Avex Order ID','dropshipping-romania-avex');
            $reordered_columns['avex_order_status'] = __( 'Avex Order Status','dropshipping-romania-avex');
        }
    }
    return $reordered_columns;
}
add_action( 'manage_woocommerce_page_wc-orders_custom_column' , 'dropshipping_romania_avex_custom_orders_list_column_content', 999, 2 );
function dropshipping_romania_avex_custom_orders_list_column_content( $column, $order )
{
    switch ( $column )
    {
        case 'avex_order_id' :
            $avex_order_id = get_post_meta( $order->get_id(), '_avex_order_id', true );
            if(!empty($avex_order_id))
                echo esc_html($avex_order_id);
            else
                echo '-';
            break;
        case 'avex_order_status' :
            $avex_order_status = get_post_meta( $order->get_id(), '_avex_order_status', true );
            if(!empty($avex_order_status))
                echo esc_html($avex_order_status);
            else
                echo '-';
            break;
    }
}

add_action( 'wp_ajax_dropshipping_romania_avex_get_invoices', 'dropshipping_romania_avex_get_invoices' );
function dropshipping_romania_avex_get_invoices() {
    check_ajax_referer( 'dropshipping_romania_avex_ajax_get_invoices_nonce', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->getInvoicesAjax();
    wp_die();
}

//prepare products for import
add_action( 'dropshipping_romania_avex_prepare_products_for_import_hook', 'dropshipping_romania_avex_prepare_products_for_import', 10, );
function dropshipping_romania_avex_prepare_products_for_import() {
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->importExistingProductsBySku();
}

add_action( 'wp_ajax_dropshipping_romania_avex_get_prepare_products_status', 'dropshipping_romania_avex_get_prepare_products_status' );
function dropshipping_romania_avex_get_prepare_products_status() {
    check_ajax_referer( 'dropshipping_romania_avex_setup_step_prepare_products', 'security' );
    require_once plugin_dir_path( __FILE__ )  . 'includes/avex.php';
    $avex=new DropshippingRomaniaAvex\avex;
    $avex->getPrepareProductsStatusAjax();
    wp_die();
}