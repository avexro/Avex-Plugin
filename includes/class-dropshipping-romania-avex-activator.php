<?php
namespace DropshippingRomaniaAvex;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$upload_dir = wp_upload_dir();
if(!empty($upload_dir['basedir']))
{
    if(!defined('DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH')){define( 'DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH', $upload_dir['basedir'].'/dropshipping-romania-avex/' );};
}
else
{
    return;
}
/**
 * Fired during plugin activation
 *
 * @link       https://avex.ro
 * @since      1.0.0
 *
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/includes
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
class Dropshipping_Romania_Avex_Activator {

    public $avexTables = array();
    public $sql=array();

    public function __construct()
    {
        global $wpdb;

        $this->avexTables[] = 'dropshipping_romania_avex_config';
        $this->avexTables[] = 'dropshipping_romania_avex_logs';
        $this->avexTables[] = 'dropshipping_romania_avex_products';
        $this->avexTables[] = 'dropshipping_romania_avex_invoices';

        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;
        $this->sql[]=$wpdb->prepare("INSERT INTO `".$wpdb->prefix."dropshipping_romania_avex_config` (`config_name`, `config_value`, `show_front`, `mdate`) VALUES
        ('api_user', '', '1', %d),
        ('api_password', '', '1', %d),
        ('max_products', '20', '1', %d),
        ('notifications_enabled', 'yes', '1', %d),
        ('notifications_email', '%s', '1', %d),
        ('price_add_percent', '40', '1', %d),
        ('price_reduced_percent', '25', '1', %d),
        ('feed_sync_interval', '1', '1', %d),
        ('feed_sync_price_override', 'no', '1', %d),
        ('feed_sync_add_new_products', 'yes', '1', %d),
        ('feed_sync_publish_new_products', 'no', '1', %d),
        ('api_sync_interval', '1', '1', %d),
        ('sync_orders_interval', '15', '1', %d),
        ('sync_orders_newer_than', '7', '1', %d),
        ('sync_invoices_interval', '1', '1', %d),
        ('completed_wc_status', 'wc-completed', '1', %d),
        ('processing_wc_status', 'wc-processing', '1', %d),
        ('cancelled_wc_status', 'wc-cancelled', '1', %d),
        ('delete_logs_older_than', '1', '1', %d),
        ('delete_invoices_upon_uninstall', 'no', '1', %d),
        ('setup_step', '0', '0', %d),
        ('admin_feed_running', '0', '0', %d),
        ('cron_feed_running', '0', '0', %d),
        ('deleting_products', '0', '0', %d)
        ON DUPLICATE KEY UPDATE mdate=%d;
        ",array(time(),time(),time(),time(),$user_email,time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time(),time()));
    }

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function activaeaza() {
        global $wpdb;
        
        $this->runFunctionsForMultiOrSingleBlog("createTables");

        //import existing products by sku
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/avex.php';
        $avex=new avex;
        $avex->checkIfImportExistingProductsBySkuAvailable();
        $avex->checkForExistingProducts();
    }

    public function createTables(){
        global $wpdb;

        require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
        if(get_option('DROPSHIPPING_ROMANIA_AVEX_VERSION')==false)
            update_option('DROPSHIPPING_ROMANIA_AVEX_VERSION',DROPSHIPPING_ROMANIA_AVEX_VERSION);

        $table_name=$wpdb->prefix.'dropshipping_romania_avex_config';
        if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
            $sql=$wpdb->prepare("create TABLE ".$wpdb->prefix."dropshipping_romania_avex_config (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `config_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                `config_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                `show_front` tinyint(1) NOT NULL,
                `mdate` int(11) NOT NULL, 
                PRIMARY KEY (`id`),
                UNIQUE KEY config_name (`config_name`)
            );
            ");
            dbDelta( $sql );
        }

        $table_name=$wpdb->prefix.'dropshipping_romania_avex_logs';
        if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
            $sql=$wpdb->prepare("create TABLE ".$wpdb->prefix."dropshipping_romania_avex_logs (
                `user_id` int(11) NOT NULL,
                `log` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
                `mdate` int(11) NOT NULL,
                KEY `idx_usr_id` (`user_id`),
                KEY `idx_log` (`log`)
            );
            ");
            dbDelta( $sql );
        }

        $table_name=$wpdb->prefix.'dropshipping_romania_avex_products';
        if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
            $sql=$wpdb->prepare("create TABLE ".$wpdb->prefix."dropshipping_romania_avex_products (
                `post_id` INT(11) NOT NULL DEFAULT '0' , 
                `imported` TINYINT(1) NOT NULL DEFAULT '0',
                `sku` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `category` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `sales_price` VARCHAR(255) NOT NULL DEFAULT '0' , 
                `avex_price` VARCHAR(255) NOT NULL DEFAULT '0' , 
                `stock` INT(11) NOT NULL DEFAULT '0' , 
                `image` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `images` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `brand` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `weight` VARCHAR(255) NOT NULL DEFAULT '' , 
                `ean` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `mdate` INT(11) NOT NULL DEFAULT '0', 
                KEY `idx_post_id` (`post_id`),
                KEY `idx_imported` (`imported`),
                UNIQUE KEY `idx_sku` (`sku`)
            );
            ");
            dbDelta( $sql );
        }

        $table_name=$wpdb->prefix.'dropshipping_romania_avex_invoices';
        if( $wpdb->get_var( $wpdb->prepare("show tables like %s" ),$table_name) != $table_name ) {
            $sql=$wpdb->prepare("create TABLE ".$wpdb->prefix."dropshipping_romania_avex_invoices (
                `post_id` INT(11) NOT NULL DEFAULT '0' , 
                `invoice_id` INT(11) NOT NULL DEFAULT '0' , 
                `order_id` INT(11) NOT NULL DEFAULT '0' , 
                `order_total` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `link` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `invoice` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' , 
                `mdate` INT(11) NOT NULL DEFAULT '0' , 
                UNIQUE KEY `idx_unique` (`post_id`, `invoice_id`, `order_id`))
            ");
            dbDelta( $sql );
        }

        dbDelta( $this->sql );

        if(!file_exists(DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH))
            wp_mkdir_p(DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH);
    }
    public function runFunctionsForMultiOrSingleBlog($the_function=""){
        global $wpdb;
        if($the_function!=""){
            if ( is_multisite() ) {
                    // Get all blogs in the network and activate plugin on each one
                    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                    foreach ( $blog_ids as $blog_id ) {
                        switch_to_blog( $blog_id );
                        $this->$the_function();
                        restore_current_blog();
                    }
                } else {
                    $this->$the_function();
            }
        }
    }
    // Creating table whenever a new blog is created
    function on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
        if ( is_plugin_active_for_network( 'dropshipping-romania-avex/dropshipping-romania-avex.php' ) ) {
            switch_to_blog( $blog_id );
            $this->createTables();
            restore_current_blog();
        }
    }
    // Deleting the table whenever a blog is deleted
    function on_delete_blog( $tables ) {
        global $wpdb;
        $current_blog_tables=array();
        foreach ($this->honeybadgerTables as $table) {
            $current_blog_tables[]=$wpdb->prefix.$table;
        }
        $tables=array_merge($tables,$current_blog_tables);
        return $tables;
    }
    function deleteDropshippingRomaniaAvexTables(){
        global $wpdb;
        $sql="select config_value from ".$wpdb->prefix."dropshipping_romania_avex_config where config_name='delete_invoices_upon_uninstall'";
        $result=$wpdb->get_row($sql);
        if(isset($result->config_value) && $result->config_value=='yes')
            $this->dropshipping_romania_avex_remove_uploads_folder(DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH.get_current_blog_id());
        $sql=$wpdb->prepare("drop TABLE IF EXISTS ".$wpdb->prefix."dropshipping_romania_avex_config");
        $wpdb->query($sql);
        $sql=$wpdb->prepare("drop TABLE IF EXISTS ".$wpdb->prefix."dropshipping_romania_avex_logs");
        $wpdb->query($sql);
        $sql=$wpdb->prepare("drop TABLE IF EXISTS ".$wpdb->prefix."dropshipping_romania_avex_products");
        $wpdb->query($sql);
        $sql=$wpdb->prepare("drop TABLE IF EXISTS ".$wpdb->prefix."dropshipping_romania_avex_invoices");
        $wpdb->query($sql);
        delete_option('DROPSHIPPING_ROMANIA_AVEX_VERSION');
    }
    function dropshipping_romania_avex_remove_uploads_folder($dir)
    {
        if (!file_exists($dir))
            return true;
        if (!is_dir($dir))
            return unlink($dir);
        foreach (scandir($dir) as $item)
        {
            if ($item == '.' || $item == '..')
                continue;
            if (!$this->dropshipping_romania_avex_remove_uploads_folder($dir . "/" . $item))
              return false;
        }
        return rmdir($dir);
    }
    function deleteTables(){
        delete_option("avex_the_dropshipping_romania_avex_activation_is_done");
        $this->runFunctionsForMultiOrSingleBlog("deleteDropshippingRomaniaAvexTables");
    }
    function versionChanges(){
        $this->runFunctionsForMultiOrSingleBlog("doVersionChanges");
    }
    function doVersionChanges(){
        global $wpdb;
        $current_version=get_option('DROPSHIPPING_ROMANIA_AVEX_VERSION');
        if (DROPSHIPPING_ROMANIA_AVEX_VERSION !== $current_version){
            if(DROPSHIPPING_ROMANIA_AVEX_VERSION=="1.0.1" && $current_version=="1.0.0"){
                //do something here
                update_option('DROPSHIPPING_ROMANIA_AVEX_VERSION','1.0.1');
            }
        }
    }
}
