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
$msg="";
$action=isset($_POST['action'])?sanitize_text_field($_POST['action']):"";
if($action=="save_settings")
{
    check_admin_referer( 'dropshipping_romania_avex_settings_page_form' );
    $msg=$avex->saveSettings();
    $avex->loadConfig();
}
?>
<h2><?php esc_html_e('Config','dropshipping-romania-avex');?></h2>
<?php
if($msg!="")
{
    ?>
    <div class="<?php echo esc_attr($msg['status']);?> notice is-dismissible inline">
        <p><?php echo esc_html($msg['cnt'])." ".esc_html($msg['msg']);?></p>
    </div>
    <?php
}
$nonce = wp_create_nonce( 'dropshipping_romania_avex_settings_page_form' );
?>
<div class="avex-tab-desc">
    <?php esc_html_e("Here you can edit the plugin configuration, each value has an explanation in the right side","dropshipping-romania-avex"); ?>.
</div>
<form action="" method="post" autocomplete="off">
<input type="hidden" name="action" value="save_settings" />
<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
<table class="widefat" id="avex-status-table" cellspacing="0">
    <tbody>
        <?php
        $explanations=array();
        $explanations['notifications_enabled'] = esc_html__("Notifications are sent when order statuses are changed by the API synchronization, or if any issues appear","dropshipping-romania-avex");
        $explanations['notifications_email'] = esc_html__("API synchronization emails will be sent to this email address, can be multiple separated by comma (,)","dropshipping-romania-avex");
        $explanations['feed_sync_enabled'] = esc_html__("Enable or disable the Feed synchronization","dropshipping-romania-avex");
        $explanations['feed_sync_interval'] = esc_html__("The interval in days when the Feed synchronization runs, one day is recommended","dropshipping-romania-avex");
        $explanations['feed_sync_price_override'] = esc_html__("If checked the Feed synchronization will override the product prices with the Avex price and the selected VAT value","dropshipping-romania-avex");
        $explanations['feed_sync_add_new_products'] = esc_html__("If checked the Feed synchronization will create new products and categories","dropshipping-romania-avex");
        $explanations['feed_sync_publish_new_products'] = esc_html__("If checked the Feed synchronization will publish the new products","dropshipping-romania-avex");
        $explanations['api_sync_enabled'] = esc_html__("Enable or disable the API synchronization, this is used for stock and prices only","dropshipping-romania-avex");
        $explanations['api_sync_interval'] = esc_html__("The interval in hours when the API synchronization runs, one hour is recommended","dropshipping-romania-avex");
        $explanations['api_sync_price_override'] = esc_html__("If checked the API synchronization  will override the product prices with the Avex recommended price","dropshipping-romania-avex");
        $explanations['sync_orders_enabled'] = esc_html__("Enable or disable the Orders status change from Avex, if an order has the status of completed, processing or cancelled the WooCommerce actions will be triggered","dropshipping-romania-avex");
        $explanations['sync_orders_interval'] = esc_html__("The interval in minutes or hours when the API synchronization checks for changes in order statuses, 15 minutes is recommended","dropshipping-romania-avex");
        $explanations['sync_orders_newer_than'] = esc_html__("Sync order statuses that are newer than the selected value, 7 days is recommended","dropshipping-romania-avex");
        $explanations['sync_invoices_interval'] = esc_html__("Sync Invoices from Avex to your local copy interval, 1 day is recommended","dropshipping-romania-avex");
        $explanations['completed_wc_status'] = esc_html__("The WooCommerce Completed order status, when an order is set to Completed by Avex and the order has a different status in WooCommerce it will be changed to the selected status and trigger the events","dropshipping-romania-avex");
        $explanations['processing_wc_status'] = esc_html__("The WooCommerce Processing order status, when an order has this status the button to send the order to Avex appears in the order page","dropshipping-romania-avex");
        $explanations['cancelled_wc_status'] = esc_html__("The WooCommerce Cancelled order status, when an order has this status the button to Cancel the order on Avex appears","dropshipping-romania-avex");
        $explanations['delete_logs_older_than'] = esc_html__("Delete logs older than the selected value in months","dropshipping-romania-avex");
        $explanations['delete_invoices_upon_uninstall'] = esc_html__("If checked and the Avex plugin is uninstalled it will delete the saved invoices","dropshipping-romania-avex");
        $explanations['api_user'] = esc_html__("Your Avex API User","dropshipping-romania-avex");
        $explanations['api_password'] = esc_html__("Your Avex API Password","dropshipping-romania-avex");
        $explanations['max_products'] = esc_html__("For testing purposes set here a low value like 20, all the system will use this value, when everything is as expected set it to 0 for no limit","dropshipping-romania-avex");
        $explanations['price_add_percent'] = esc_html__("Add X% to your desired selling price from the Avex price","dropshipping-romania-avex");
        $explanations['price_reduced_percent'] = esc_html__("Add X% of your Sale price for the regular price","dropshipping-romania-avex");

        $setting_names=array();
        $setting_names['notifications_enabled'] = esc_html__("Notifications Enabled","dropshipping-romania-avex");
        $setting_names['notifications_email'] = esc_html__("Notifications Email","dropshipping-romania-avex");
        $setting_names['feed_sync_enabled'] = esc_html__("Feed Sync Enabled","dropshipping-romania-avex");
        $setting_names['feed_sync_interval'] = esc_html__("Feed Sync Interval","dropshipping-romania-avex");
        $setting_names['feed_sync_price_override'] = esc_html__("Feed Sync Price Override","dropshipping-romania-avex");
        $setting_names['feed_sync_add_new_products'] = esc_html__("Feed Sync add New products","dropshipping-romania-avex");
        $setting_names['feed_sync_publish_new_products'] = esc_html__("Feed Sync Publish New products","dropshipping-romania-avex");
        $setting_names['api_sync_enabled'] = esc_html__("API Sync Enabled","dropshipping-romania-avex");
        $setting_names['api_sync_interval'] = esc_html__("API Sync Interval","dropshipping-romania-avex");
        $setting_names['api_sync_price_override'] = esc_html__("API Sync Price Override","dropshipping-romania-avex");
        $setting_names['sync_orders_enabled'] = esc_html__("Sync Orders Enabled","dropshipping-romania-avex");
        $setting_names['sync_orders_interval'] = esc_html__("Sync Orders Interval","dropshipping-romania-avex");
        $setting_names['sync_orders_newer_than'] = esc_html__("Sync Orders Newer than","dropshipping-romania-avex");
        $setting_names['sync_invoices_interval'] = esc_html__("Sync Invoices Interval","dropshipping-romania-avex");
        $setting_names['completed_wc_status'] = esc_html__("Completed WC Status","dropshipping-romania-avex");
        $setting_names['processing_wc_status'] = esc_html__("Processing WC status","dropshipping-romania-avex");
        $setting_names['cancelled_wc_status'] = esc_html__("Cancelled WC Status","dropshipping-romania-avex");
        $setting_names['delete_logs_older_than'] = esc_html__("Delete Logs Older than","dropshipping-romania-avex");
        $setting_names['delete_invoices_upon_uninstall'] = esc_html__("Delete Invoices upon Uninstall","dropshipping-romania-avex");
        $setting_names['api_user'] = esc_html__("Avex API User","dropshipping-romania-avex");
        $setting_names['api_password'] = esc_html__("Avex API Password","dropshipping-romania-avex");
        $setting_names['max_products'] = esc_html__("Max Products","dropshipping-romania-avex");
        $setting_names['price_add_percent'] = esc_html__("WC Sales Price","dropshipping-romania-avex");
        $setting_names['price_reduced_percent'] = esc_html__("WC Regular Price","dropshipping-romania-avex");
        foreach($avex->config_front as $config_name => $config_value)
        {
            if(!$avex->is_woocommerce_activated() && in_array($config_name,array('completed_wc_status','processing_wc_status','cancelled_wc_status')))
                continue;
            ?>
            <tr>
                <td><label for="<?php echo esc_attr($config_name);?>"><?php echo esc_html($setting_names[$config_name]);?></label></td>
                <td>
                    <?php
                    if(in_array($config_value,array("yes","no")))
                    {
                        ?>
                        <input type="checkbox" class="wp-generate-pw hide-if-no-js" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>"<?php echo (($config_value=="yes")?' checked="checked"':'');?> />
                        <?php
                    }
                    else if(is_numeric($config_value) && $config_name!="sync_orders_interval" && $config_name!="api_password" && $config_name!="api_user" && $config_name!="max_products" && $config_name!="price_add_percent" && $config_name!="price_reduced_percent")
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="<?php echo esc_attr($config_name);?>" id="<?php echo esc_attr($config_name);?>">
                            <?php
                            $interval=array();
                            $interval_str="";
                            if($config_name=='feed_sync_interval' || $config_name=='sync_invoices_interval')
                                $interval=range(1, 31);
                            if($config_name=='api_sync_interval')
                                $interval=range(1, 12);
                            if($config_name=='sync_orders_newer_than')
                                $interval=range(7, 60);
                            if($config_name=='delete_logs_older_than')
                                $interval=range(1, 12);
                            if(count($interval)>0)
                            {
                                foreach($interval as $value)
                                {
                                    $interval_str="";
                                    if($config_name=='feed_sync_interval')
                                    {
                                        $interval_str=esc_html__("days","dropshipping-romania-avex");
                                        if($value==1)
                                            $interval_str=esc_html__("day","dropshipping-romania-avex");
                                    }
                                    if($config_name=='api_sync_interval')
                                    {
                                        $interval_str=esc_html__("hours","dropshipping-romania-avex");
                                        if($value==1)
                                            $interval_str=esc_html__("hour","dropshipping-romania-avex");
                                    }
                                    if($config_name=='sync_orders_newer_than')
                                    {
                                        $interval_str=esc_html__("days","dropshipping-romania-avex");
                                        if($value==1)
                                            $interval_str=esc_html__("day","dropshipping-romania-avex");
                                    }
                                    if($config_name=='sync_invoices_interval')
                                    {
                                        $interval_str=esc_html__("days","dropshipping-romania-avex");
                                        if($value==1)
                                            $interval_str=esc_html__("day","dropshipping-romania-avex");
                                    }
                                    if($config_name=='delete_logs_older_than')
                                    {
                                        $interval_str=esc_html__("months","dropshipping-romania-avex");
                                        if($value==1)
                                            $interval_str=esc_html__("month","dropshipping-romania-avex");
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr($value);?>"<?php echo ($config_value==$value)?' selected="selected"':'';?>><?php echo esc_html($value);?> <?php echo esc_html($interval_str);?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                        <?php
                    }
                    else if($config_name=='notifications_email')
                    {
                        ?>
                        <input type="text" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='api_user')
                    {
                        ?>
                        <input autocomplete="new-password" autocomplete="off" type="text" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='api_password')
                    {
                        ?>
                        <input autocomplete="new-password" autocomplete="off" type="password" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='max_products')
                    {
                        ?>
                        <input type="text" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='price_add_percent')
                    {
                        ?>
                        <input type="text" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='price_reduced_percent')
                    {
                        ?>
                        <input type="text" value="<?php echo esc_attr($config_value);?>" id="<?php echo esc_attr($config_name);?>" name="<?php echo esc_attr($config_name);?>" class="button wp-generate-pw hide-if-no-js" />
                        <?php
                    }
                    else if($config_name=='sync_orders_interval')
                    {
                        ?>
                        <select class="button wp-generate-pw hide-if-no-js" name="<?php echo esc_attr($config_name);?>" id="<?php echo esc_attr($config_name);?>">
                        <?php
                        $interval=array(10,15,30);
                        $interval=array_merge($interval,range(1,6));
                        if(count($interval)>0)
                        {
                            foreach($interval as $value)
                            {
                                ?>
                                <option value="<?php echo esc_attr($value);?>"<?php echo ($config_value==$value)?' selected="selected"':'';?>>
                                    <?php
                                    if(in_array($value,range(1,6)))
                                    {
                                        if($value==1)
                                            echo esc_html($value)." ".esc_html__("hour","dropshipping-romania-avex");
                                        else
                                            echo esc_html($value)." ".esc_html__("hours","dropshipping-romania-avex");
                                    }
                                   else
                                        echo esc_html($value)." ".esc_html__("minutes","dropshipping-romania-avex");
                                    ?>
                                    </option>
                                <?php
                            }
                        }
                        ?>
                        </select>
                        <?php
                    }
                    else if(in_array($config_name,array('completed_wc_status','processing_wc_status','cancelled_wc_status')))
                    {
                        $wc_statuses=wc_get_order_statuses();
                        if(count($wc_statuses)>0)
                        {
                            ?>
                            <select class="button wp-generate-pw hide-if-no-js" name="<?php echo esc_attr($config_name);?>" id="<?php echo esc_attr($config_name);?>">
                            <?php
                            foreach($wc_statuses as $wc_status => $status_text)
                            {
                                ?>
                                <option value="<?php echo esc_attr($wc_status);?>"<?php echo ($config_value==$wc_status)?' selected="selected"':'';?>>
                                    <?php echo esc_html($status_text);?>
                                </option>
                                <?php
                            }
                            ?>
                            </select>
                            <?php
                        }
                    }
                    ?>
                </td>
                <td><?php echo esc_html($explanations[$config_name]);?></td>
            </tr>
            <?php
        }
    ?>
    <tr>
        <td colspan="3">
            <input class="button-primary" type="submit" value="<?php echo esc_attr(__('Save Configuration','dropshipping-romania-avex'));?>" /><br /><br />
        </td>
    </tr>
    </tbody>
</table>
</form>
