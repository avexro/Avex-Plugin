<?php
/**
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/includes
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

require_once DROPSHIPPING_ROMANIA_AVEX_PLUGIN_PATH  . 'includes/avex.php';
$avex=new DropshippingRomaniaAvex\avex;


$default_tab = "dropshipping-romania-avex";
$tab = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : $default_tab;
?>
<div class="wrap dropshipping-romania-avex-wrap">
<!-- Print the page title -->
<h1 id="dropshipping-romania-avex_top_row"><a href="<?php echo esc_url("https://avex.ro/");?>" target="_blank">
    <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/avex.png");?>" />
    <br />
    Dropshipping Romania
    </a>
</h1>
<div id="avex-description">
    
    <?php esc_html_e("We ship the packages on your behalf. Place the order and load the AWB alongside it","dropshipping-romania-avex");?>.

</div>
<!-- Here are our tabs -->
<nav class="nav-tab-wrapper" id="avex-plugin-menu">
  <a href="<?php echo esc_url(admin_url()."admin.php?page=dropshipping-romania-avex");?>" class="nav-tab <?php if($tab==='dropshipping-romania-avex'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Dashboard','dropshipping-romania-avex');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=dropshipping-romania-avex-config");?>" class="nav-tab <?php if($tab==='dropshipping-romania-avex-config'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Config','dropshipping-romania-avex');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=dropshipping-romania-avex-invoices");?>" class="nav-tab <?php if($tab==='dropshipping-romania-avex-invoices'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Invoices','dropshipping-romania-avex');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=dropshipping-romania-avex-logs");?>" class="nav-tab <?php if($tab==='dropshipping-romania-avex-logs'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Logs','dropshipping-romania-avex');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=dropshipping-romania-avex-help");?>" class="nav-tab <?php if($tab==='dropshipping-romania-avex-help'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Help','dropshipping-romania-avex');?></a>
</nav>

<div class="tab-content">
<?php switch($tab) :
case 'dropshipping-romania-avex-config':
    require_once plugin_dir_path(__FILE__)."dropshipping-romania-avex-admin-display-config.php";
    break;
case 'dropshipping-romania-avex-invoices':
    require_once plugin_dir_path(__FILE__)."dropshipping-romania-avex-admin-display-invoices.php";
    break;
case 'dropshipping-romania-avex-logs':
    require_once plugin_dir_path(__FILE__)."dropshipping-romania-avex-admin-display-logs.php";
    break;
case 'dropshipping-romania-avex-help':
    require_once plugin_dir_path(__FILE__)."dropshipping-romania-avex-admin-display-help.php";
    break;
default:
    require_once plugin_dir_path(__FILE__)."dropshipping-romania-avex-admin-display-dashboard.php";
    break;
endswitch; ?>
</div>
</div>
