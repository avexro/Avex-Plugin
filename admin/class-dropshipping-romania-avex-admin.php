<?php
namespace DropshippingRomaniaAvex;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://avex.ro
 * @since      1.0.0
 *
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
class Dropshipping_Romania_Avex_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Dropshipping_Romania_Avex_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dropshipping_Romania_Avex_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if($hook && in_array($hook,array('toplevel_page_dropshipping-romania-avex','avex_page_dropshipping-romania-avex-config','avex_page_dropshipping-romania-avex-invoices','avex_page_dropshipping-romania-avex-logs','avex_page_dropshipping-romania-avex-help')))
		{
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dropshipping-romania-avex-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."-bootstrap", plugin_dir_url( __FILE__ ) . 'css/grid.css', array(), $this->version, 'all' );
		}
		if($hook && ($hook=='avex_page_dropshipping-romania-avex-logs' || $hook=='avex_page_dropshipping-romania-avex-invoices'))
		{
			wp_enqueue_style( $this->plugin_name."-datatables", plugin_dir_url( __FILE__ ) . 'datatables/datatables.min.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Dropshipping_Romania_Avex_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dropshipping_Romania_Avex_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if($hook && in_array($hook,array('toplevel_page_dropshipping-romania-avex','avex_page_dropshipping-romania-avex-config','avex_page_dropshipping-romania-avex-invoices','avex_page_dropshipping-romania-avex-logs','avex_page_dropshipping-romania-avex-help')))
		{
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dropshipping-romania-avex-admin.js', array( 'jquery' ), $this->version, false );
		}
		if($hook && ($hook=='avex_page_dropshipping-romania-avex-logs' || $hook=='avex_page_dropshipping-romania-avex-invoices'))
		{
			wp_enqueue_script( $this->plugin_name."-datatables", plugin_dir_url( __FILE__ ) . 'datatables/datatables.min.js', array( 'jquery' ), $this->version, false );
		}
	}

}
