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
if($action=="avex_setup_step_0")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_0' );
    $msg=$avex->doSetupStep0();
}
if($action=="avex_setup_step_1")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_1' );
    $msg=$avex->doSetupStep1();
}
if($action=="set_not_imported")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_3_1' );
    $msg=$avex->setAllProductsNotImported();
}
if($action=="delete_products")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_3_2' );
    $msg=$avex->scheduleDeleteAllProducts();
}
if($action=="refresh_products")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_3_3' );
    $msg=$avex->refreshProductsStockPrices();
}
if($action=="setup_cron_feed")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_1' );
    $msg=$avex->startFeedCron();
}
if($action=="cancel_cron_feed_import")
{
    check_admin_referer( 'dropshipping_romania_avex_cancel_cron_feed_import' );
    $msg=$avex->stopFeedCronManual();
}
if($action=="stop_cron_feed")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_2' );
    $msg=$avex->stopFeedCron();
}
if($action=="setup_cron_api")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_3' );
    $msg=$avex->startApiCron();
}
if($action=="stop_cron_api")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_4' );
    $msg=$avex->stopApiCron();
}
if($action=="setup_cron_orders")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_5' );
    $msg=$avex->startOrdersCron();
}
if($action=="stop_cron_orders")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_6' );
    $msg=$avex->stopOrdersCron();
}
if($action=="setup_cron_invoices")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_7' );
    $msg=$avex->startInvoicesCron();
}
if($action=="stop_cron_invoices")
{
    check_admin_referer( 'dropshipping_romania_avex_setup_step_cron_8' );
    $msg=$avex->stopInvoicesCron();
}
if($action=="cancel_admin_feed_import")
{
    check_admin_referer( 'dropshipping_romania_avex_cancel_admin_feed_import' );
    $msg=$avex->cancelAdminFeedImport();
}
?>
<h2><?php esc_html_e('Dashboard','dropshipping-romania-avex');?></h2>
<?php
if($msg!="")
{
    ?>
    <div class="<?php echo esc_attr($msg['status']);?> notice is-dismissible inline">
        <p><?php echo esc_html($msg['msg']);?></p>
    </div>
    <?php
}
$setup_step=$avex->config->setup_step;
if($setup_step!=-1)
{
?>
<div class="avex-tab-desc">
    <?php esc_html_e("Here you can manage the Avex product import system","dropshipping-romania-avex"); ?>.
</div>
<?php
}
$php_version_good=false;
if(version_compare( PHP_VERSION, '7.2' ) >= 0)
    $php_version_good=true;

if(!$avex->is_woocommerce_activated())
{
    ?>
    <div class="avex-error-div">
        <?php esc_html_e("WooCommerce is not installed or activated, please install or activate it in order to continue","dropshipping-romania-avex");?>.
    </div>
    <?php
}
else if(!$php_version_good)
{
    ?>
    <div class="avex-error-div">
        <?php esc_html_e("PHP version must be at least 7.2","dropshipping-romania-avex");?>.
    </div>
    <?php
}
else if(!is_file(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php"))
{
    ?>
    <div class="avex-error-div">
        <?php esc_html_e("Action Scheduler doesn't seam to be installed, this is required for the cron jobs","dropshipping-romania-avex");?>.
    </div>
    <?php
}
else
{
    if($setup_step==-1)
    {
        ?>
        <div class="avex-tab-desc">
            <?php esc_html_e("We have found that you already have Avex products set in your WooCommerce store, please wait until the products are syncronized","dropshipping-romania-avex"); ?>.
        </div>
        <?php
        $nonce=wp_create_nonce( 'dropshipping_romania_avex_setup_step_prepare_products' );
        $data_js='
        jQuery(document).ready(function() {
            setInterval(function(){
                jQuery.ajax({
                    method: "POST",
                    data: { 
                        "action": "dropshipping_romania_avex_get_prepare_products_status", 
                        "security": \''.esc_js($nonce).'\',
                    },
                    url: "'.esc_url(admin_url('admin-ajax.php')).'",
                    async: true
                })
                .done(function( msg ) {
                    console.log(msg);
                    if(msg==1)
                    {
                        location.reload();
                    }
                    else
                    {
                        jQuery(\'#avex_task_status\').html(msg);
                    }
                });
            }, 5000);
        });
        ';
        wp_register_script( 'dropshipping-romania-avex_js_setup_step_prepare_products', '' );
        wp_enqueue_script( 'dropshipping-romania-avex_js_setup_step_prepare_products' );
        wp_add_inline_script("dropshipping-romania-avex_js_setup_step_prepare_products",$data_js);
        $status_pending=$avex->getActionSchedulerTaskStatus("dropshipping_romania_avex_prepare_products_for_import_hook",ActionScheduler_Store::STATUS_PENDING);
        $status_running=$avex->getActionSchedulerTaskStatus("dropshipping_romania_avex_prepare_products_for_import_hook",ActionScheduler_Store::STATUS_RUNNING);
        if(count($status_pending)>0)
        {
        ?>
        <table class="widefat avex-widefat" id="avex-status-table" cellspacing="0">
            <tbody>
                <tr id="avex_task_status">
                    <td>
                        <strong><?php esc_html_e("The Prepare Avex Products task is starting soon","dropshipping-romania-avex");?></strong>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        }
        if(count($status_running)>0)
        {
        ?>
        <table class="widefat avex-widefat" id="avex-status-table" cellspacing="0">
            <tbody>
                <tr id="avex_task_status">
                    <td>
                        <strong><?php esc_html_e("The Prepare Avex Products task is running, please wait","dropshipping-romania-avex");?></strong>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        }
    }
    if($setup_step==0)
    {
        $nonce=wp_create_nonce( 'dropshipping_romania_avex_setup_step_0' );
        ?>
        <div class="avex-tab-desc">
            <?php esc_html_e("Add your Avex API username and password in the form below to start the setup process","dropshipping-romania-avex"); ?>.
        </div>
        <form method="post" action="">
            <input type="hidden" name="action" value="avex_setup_step_0" />
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
            <table style="border: 0px;" class="widefat avex-widefat" id="avex-status-table" cellspacing="0">
                <tbody>
                    <tr>
                        <td style="border-top: 1px solid #c3c4c7;">
                            <label for="avex_username"><?php esc_html_e("Avex API User","dropshipping-romania-avex");?></label>
                        </td>
                        <td style="border-top: 1px solid #c3c4c7;">
                            <input<?php echo (($avex->config->api_user!="")?"":' autocomplete="new-password"');?> type="text" name="avex_username" id="avex_username" class="button wp-generate-pw hide-if-no-js" value="<?php echo (($avex->config->api_user!="")?esc_attr($avex->config->api_user):"");?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="avex_password"><?php esc_html_e("Avex API Password","dropshipping-romania-avex");?></label>
                        </td>
                        <td>
                            <input<?php echo (($avex->config->api_password!="")?"":' autocomplete="new-password"');?> type="password" name="avex_password" id="avex_password" class="button wp-generate-pw hide-if-no-js" value="<?php echo (($avex->config->api_password!="")?esc_attr($avex->config->api_password):"");?>" />
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Start the Setup",'dropshipping-romania-avex'));?>" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <?php
    }
    if($setup_step>0)
    {
        $result=$avex->getAccountStatus();
        if(isset($result["status"]) && $result["status"]=="ok")
        {
            $status=$result["msg"];
            ?>
            <table style="border: 0px;" class="widefat avex-widefat" id="avex-status-table" cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 0px;border-left:0px;border-right:0px;">
                            <strong><?php esc_html_e("Account State","dropshipping-romania-avex");?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php esc_html_e("Company Name","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($status->data->company_name);?></strong>
                        </td>
                        <td>
                            <?php esc_html_e("Wallet Balance","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong><?php echo esc_html(number_format(round((float)$status->data->balance,2),2,".",""));?></strong>
                        </td>
                        <td>
                            <?php esc_html_e("Status access API","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <?php
                            $status_color="red";
                            if(strtolower($status->data->status)=="active")
                                $status_color="green";
                            ?>
                            <strong style="font-size:larger;color:<?php echo esc_attr($status_color);?>;"><?php echo esc_html(strtoupper($status->data->status));?></strong>
                        </td>
                        <td></td>
                    </tr>
                    <?php
                    if($setup_step==1)
                    {
                    $nonce=wp_create_nonce( 'dropshipping_romania_avex_setup_step_1' );
                    ?>
                    <form method="post" action="">
                    <input type="hidden" name="action" value="avex_setup_step_1" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                    <tr>
                        <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                            <strong><?php esc_html_e("Product Import","dropshipping-romania-avex");?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong><?php esc_html_e("Import Avex Products","dropshipping-romania-avex");?></strong>
                        </td>
                        <td>
                           <label for="avex_publish_products_setup_step_1"><strong><?php esc_html_e("Publish Products","dropshipping-romania-avex");?>?</strong></label>
                        </td>
                        <td>
                            <input type="checkbox" name="avex_publish_products_setup_step_1" id="avex_publish_products_setup_step_1" class="wp-generate-pw hide-if-no-js" />
                        </td>
                        <td>
                            <label for="avex_override_products_setup_step_1"><strong><?php esc_html_e("Override Products","dropshipping-romania-avex");?>?</strong></label>
                        </td>
                        <td>
                            <input type="checkbox" name="avex_override_products_setup_step_1" id="avex_override_products_setup_step_1" class="wp-generate-pw hide-if-no-js" />
                        </td>
                        <td>
                            <input id="avex_submit_step_1" type="submit" class="button-secondary" value="<?php echo esc_attr(__("Start Products Import",'dropshipping-romania-avex'));?>" />
                        </td>
                        <td></td>
                    </tr>
                    </form>
                    <?php
                    $data_js='
                    jQuery(document).ready(function() {
                        jQuery("#avex_submit_step_1").on("click",function(){
                            let width=jQuery("#avex_submit_step_1").outerWidth();
                            jQuery("#avex_submit_step_1").val(\''.esc_js(__("loading","dropshipping-romania-avex")).'...\');
                            jQuery("#avex_submit_step_1").css("width",width);
                        });
                    });
                    ';
                    wp_register_script( 'dropshipping-romania-avex_js_setup_step_1_inline_script_handler', '' );
                    wp_enqueue_script( 'dropshipping-romania-avex_js_setup_step_1_inline_script_handler' );
                    wp_add_inline_script("dropshipping-romania-avex_js_setup_step_1_inline_script_handler",$data_js);
                    }

                    if($setup_step==2)
                    {
                        $admin_feed_status=$avex->getAdminFeedStatus();
                        $have_admin_task=false;
                        if($admin_feed_status=="running")//running
                        {
                            $have_admin_task=true;
                            $status=$avex->getFeedProcessedProducts();
                            $nonce_cancel_admin_feed=wp_create_nonce( 'dropshipping_romania_avex_cancel_admin_feed_import' );
                            ?>
                            <tr>
                                <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                                    <strong><?php esc_html_e("Product Import","dropshipping-romania-avex");?></strong>
                                </td>
                            </tr>
                            <tr id="avex_task_status">
                                <td colspan="3">
                                    <strong><?php esc_html_e("The Import Avex Products task is running","dropshipping-romania-avex");?></strong>
                                </td>
                                <td colspan="3">
                                    <strong><?php echo esc_html($status['done']);?>/<?php echo esc_html($status['total']);?></strong> <?php esc_html_e("products have been imported","dropshipping-romania-avex");?>
                                </td>
                                <td>
                                    <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to cancel the import","dropshipping-romania-avex"));?>?')">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_cancel_admin_feed);?>" />
                                    <input type="hidden" name="action" value="cancel_admin_feed_import" />
                                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Cancel","dropshipping-romania-avex"));?>" />
                                </td>
                            </tr>
                            <?php
                        }
                        else if($admin_feed_status=="pending")//pending
                        {
                            $have_admin_task=true;
                            ?>
                            <tr>
                                <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                                    <strong><?php esc_html_e("Product Import","dropshipping-romania-avex");?></strong>
                                </td>
                            </tr>
                            <tr id="avex_task_status">
                                <td colspan="7">
                                    <strong><?php esc_html_e("The Import Avex Products task is pending in queue","dropshipping-romania-avex");?></strong>
                                </td>
                            </tr>
                            <?php
                        }
                        if(!$have_admin_task)
                        {
                            $cron_feed_status=$avex->getCronFeedStatus();
                            if($cron_feed_status=="running")//running
                            {
                                $status=$avex->getFeedProcessedProducts();
                                $nonce_cancel_cron_feed=wp_create_nonce( 'dropshipping_romania_avex_cancel_cron_feed_import' );
                                ?>
                                <tr>
                                    <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                                        <strong><?php esc_html_e("Product Import","dropshipping-romania-avex");?></strong>
                                    </td>
                                </tr>
                                <tr id="avex_task_status">
                                    <td colspan="3">
                                        <strong><?php esc_html_e("The Import Avex Products cron is running","dropshipping-romania-avex");?></strong>
                                    </td>
                                    <td colspan="3">
                                        <strong><?php echo esc_html($status['done']);?>/<?php echo esc_html($status['total']);?></strong> <?php esc_html_e("products have been imported","dropshipping-romania-avex");?>
                                    </td>
                                    <td>
                                        <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to cancel the cron import (you would need to set it up again)","dropshipping-romania-avex"));?>?')">
                                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_cancel_cron_feed);?>" />
                                        <input type="hidden" name="action" value="cancel_cron_feed_import" />
                                        <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Cancel","dropshipping-romania-avex"));?>" />
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        
                        $nonce=wp_create_nonce( 'dropshipping_romania_avex_setup_step_2_feed_status' );
                        $data_js='
                        jQuery(document).ready(function() {
                            setInterval(function(){
                                jQuery.ajax({
                                    method: "POST",
                                    data: { 
                                        "action": "dropshipping_romania_avex_get_import_feed_status", 
                                        "security": \''.esc_js($nonce).'\',
                                    },
                                    url: "'.esc_url(admin_url('admin-ajax.php')).'",
                                    async: true
                                })
                                .done(function( msg ) {
                                    if(msg==1)
                                    {
                                        location.reload();
                                    }
                                    else
                                    {
                                        jQuery(\'#avex_task_status\').html(msg);
                                    }
                                });
                            }, 5000);
                        });
                        ';
                        wp_register_script( 'dropshipping-romania-avex_js_setup_step_2_inline_script_handler', '' );
                        wp_enqueue_script( 'dropshipping-romania-avex_js_setup_step_2_inline_script_handler' );
                        wp_add_inline_script("dropshipping-romania-avex_js_setup_step_2_inline_script_handler",$data_js);
                    }
                    if($setup_step==3)
                    {
                        $nonce_1=wp_create_nonce( 'dropshipping_romania_avex_setup_step_3_1' );
                        $nonce_2=wp_create_nonce( 'dropshipping_romania_avex_setup_step_3_2' );
                        $nonce_3=wp_create_nonce( 'dropshipping_romania_avex_setup_step_3_3' );
                        $total_prods_det=$avex->getTotalImportedProducts();
                        $total_products=$total_prods_det[1];
                        ?>
                        <tr>
                            <tr>
                                <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                                    <strong><?php esc_html_e("Product Import","dropshipping-romania-avex");?></strong>
                                </td>
                            </tr>
                            <td>
                                <?php esc_html_e("Products Imported","dropshipping-romania-avex");?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($total_prods_det[0]);?>/<?php echo esc_html($total_prods_det[1]);?></strong>
                            </td>
                            <td><?php esc_html_e("Reimport products","dropshipping-romania-avex");?></td>
                            <td>
                                <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want set all products for reimport","dropshipping-romania-avex"));?>?')">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_1);?>" />
                                    <input type="hidden" name="action" value="set_not_imported" />
                                    <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Reimport","dropshipping-romania-avex"));?>" />
                                </form>
                            </td>
                            <td><?php esc_html_e("Delete all Avex products (including images)","dropshipping-romania-avex");?></td>
                            <td>
                                <?php
                                if($avex->config->deleting_products==1)
                                {
                                    ?>
                                    <strong><?php esc_html_e("products are being deleted","dropshipping-romania-avex");?></strong>
                                    <?php
                                }
                                else
                                {
                                ?>
                                <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to delete all imported Avex Products","dropshipping-romania-avex"));?>?')">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_2);?>" />
                                    <input type="hidden" name="action" value="delete_products" />
                                    <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Delete","dropshipping-romania-avex"));?>" />
                                </form>
                                <?php
                                }
                                ?>
                            </td>
                            <td></td>
                        </tr>
                        <?php
                        if($total_products>0)//have products we can use the API
                        {
                            ?>
                        <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to refresh the products stock and prices if applicable","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_3);?>" />
                                <input type="hidden" name="action" value="refresh_products" />
                        <tr id="avex_task_status_api">
                            <td colspan="3">
                                <?php esc_html_e("Refresh Stock from Avex for all imported Avex Products","dropshipping-romania-avex");?>
                            </td>
                            <td>
                                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Refresh","dropshipping-romania-avex"));?>" />
                            <td>
                                
                            </td>
                            <td colspan="3">
                                
                            </td>
                        </tr>
                        </form>
                            <?php
                        }
                    }
                    $feed_cron_status=$avex->getCronStatus("dropshipping_romania_avex_import_feed_cron_hook");
                    ?>
                    <tr>
                        <td colspan="7" style="padding-bottom: 0px;padding-left: 0px;font-size: larger;background-color: #f0f0f1;border-bottom: 1px solid #c3c4c7;border-top: 1px solid #c3c4c7;border-left:0px;border-right:0px;">
                            <strong><?php esc_html_e("Automatic Sync","dropshipping-romania-avex");?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php esc_html_e("Feed Cron Status","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                {
                                    esc_html_e("Feed cron is not started","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="running")
                                {
                                    esc_html_e("Running","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="pending")
                                {
                                    esc_html_e("Pending","dropshipping-romania-avex");
                                }
                                ?>
                            </strong>
                        </td>
                        <td><?php esc_html_e("Next run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                    esc_html_e("Never","dropshipping-romania-avex");
                                else
                                {
                                    $next_run=$avex->getCronNextRun("dropshipping_romania_avex_import_feed_cron_hook");
                                    echo esc_html(get_date_from_gmt($next_run));
                                }
                                ?>
                            </strong>
                        </td>
                        <?php
                        $nonce_4=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_1' );
                        $nonce_5=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_2' );
                        ?>
                        <td>
                            <?php
                            if(!$feed_cron_status)
                            {
                            ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to start the Feed Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_4);?>" />
                                <input type="hidden" name="action" value="setup_cron_feed" />
                                <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Start Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            else
                            {
                                ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to stop the Feed Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_5);?>" />
                                <input type="hidden" name="action" value="stop_cron_feed" />
                                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Stop Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            ?>
                        </td>
                        <td><?php esc_html_e("Last run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                            <?php
                            $last_run=$avex->getCronLastRun("dropshipping_romania_avex_import_feed_cron_hook");
                            if($last_run!="")
                                echo esc_html(date("d/m/Y H:i:s",strtotime($last_run)));
                            else
                                esc_html_e("Never","dropshipping-romania-avex");
                            ?>
                            </strong>
                        </td>
                    </tr>
                    <?php
                    $feed_cron_status=$avex->getCronStatus("dropshipping_romania_avex_import_api_cron_hook");
                    ?>
                    <tr>
                        <td>
                            <?php esc_html_e("API Cron Status","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                {
                                    esc_html_e("API cron is not started","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="running")
                                {
                                    esc_html_e("Running","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="pending")
                                {
                                    esc_html_e("Pending","dropshipping-romania-avex");
                                }
                                ?>
                            </strong>
                        </td>
                        <td><?php esc_html_e("Next run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                    esc_html_e("Never","dropshipping-romania-avex");
                                else
                                {
                                    $next_run=$avex->getCronNextRun("dropshipping_romania_avex_import_api_cron_hook");
                                    echo esc_html(get_date_from_gmt($next_run));                                }
                                ?>
                            </strong>
                        </td>
                        <?php
                        $nonce_5=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_3' );
                        $nonce_6=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_4' );
                        ?>
                        <td>
                            <?php
                            if(!$feed_cron_status)
                            {
                            ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to start the API Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_5);?>" />
                                <input type="hidden" name="action" value="setup_cron_api" />
                                <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Start Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            else
                            {
                                ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to stop the API Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_6);?>" />
                                <input type="hidden" name="action" value="stop_cron_api" />
                                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Stop Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            ?>
                        </td>
                        <td><?php esc_html_e("Last run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                            <?php
                            $last_run=$avex->getCronLastRun("dropshipping_romania_avex_import_api_cron_hook");
                            if($last_run!="")
                                echo esc_html(date("d/m/Y H:i:s",strtotime($last_run)));
                            else
                                esc_html_e("Never","dropshipping-romania-avex");
                            ?>
                            </strong>
                        </td>
                    </tr>
                    <?php
                    $feed_cron_status=$avex->getCronStatus("dropshipping_romania_avex_import_orders_cron_hook");
                    ?>
                    <tr>
                        <td>
                            <?php esc_html_e("Orders Cron Status","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                {
                                    esc_html_e("Orders cron is not started","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="running")
                                {
                                    esc_html_e("Running","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="pending")
                                {
                                    esc_html_e("Pending","dropshipping-romania-avex");
                                }
                                ?>
                            </strong>
                        </td>
                        <td><?php esc_html_e("Next run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                    esc_html_e("Never","dropshipping-romania-avex");
                                else
                                {
                                    $next_run=$avex->getCronNextRun("dropshipping_romania_avex_import_orders_cron_hook");
                                    echo esc_html(get_date_from_gmt($next_run));
                                }
                                ?>
                            </strong>
                        </td>
                        <?php
                        $nonce_7=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_5' );
                        $nonce_8=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_6' );
                        ?>
                        <td>
                            <?php
                            if(!$feed_cron_status)
                            {
                            ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to start the Orders Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_7);?>" />
                                <input type="hidden" name="action" value="setup_cron_orders" />
                                <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Start Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            else
                            {
                                ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to stop the Orders Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_8);?>" />
                                <input type="hidden" name="action" value="stop_cron_orders" />
                                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Stop Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            ?>
                        </td>
                        <td><?php esc_html_e("Last run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                            <?php
                            $last_run=$avex->getCronLastRun("dropshipping_romania_avex_import_orders_cron_hook");
                            if($last_run!="")
                                echo esc_html(date("d/m/Y H:i:s",strtotime($last_run)));
                            else
                                esc_html_e("Never","dropshipping-romania-avex");
                            ?>
                            </strong>
                        </td>
                    </tr>
                    <?php
                    $feed_cron_status=$avex->getCronStatus("dropshipping_romania_avex_import_invoices_cron_hook");
                    ?>
                    <tr>
                        <td>
                            <?php esc_html_e("Invoices Cron Status","dropshipping-romania-avex");?>
                        </td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                {
                                    esc_html_e("Invoices cron is not started","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="running")
                                {
                                    esc_html_e("Running","dropshipping-romania-avex");
                                }
                                else if($feed_cron_status=="pending")
                                {
                                    esc_html_e("Pending","dropshipping-romania-avex");
                                }
                                ?>
                            </strong>
                        </td>
                        <td><?php esc_html_e("Next run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                                <?php
                                if(!$feed_cron_status)
                                    esc_html_e("Never","dropshipping-romania-avex");
                                else
                                {
                                    $next_run=$avex->getCronNextRun("dropshipping_romania_avex_import_invoices_cron_hook");
                                    echo esc_html(get_date_from_gmt($next_run));
                                }
                                ?>
                            </strong>
                        </td>
                        <?php
                        $nonce_9=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_7' );
                        $nonce_10=wp_create_nonce( 'dropshipping_romania_avex_setup_step_cron_8' );
                        ?>
                        <td>
                            <?php
                            if(!$feed_cron_status)
                            {
                            ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to start the Invoices Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_9);?>" />
                                <input type="hidden" name="action" value="setup_cron_invoices" />
                                <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Start Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            else
                            {
                                ?>
                            <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to stop the Invoices Cron","dropshipping-romania-avex"));?>?')">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_10);?>" />
                                <input type="hidden" name="action" value="stop_cron_invoices" />
                                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Stop Cron","dropshipping-romania-avex"));?>" />
                            </form>
                            <?php
                            }
                            ?>
                        </td>
                        <td><?php esc_html_e("Last run","dropshipping-romania-avex");?></td>
                        <td>
                            <strong>
                            <?php
                            $last_run=$avex->getCronLastRun("dropshipping_romania_avex_import_invoices_cron_hook");
                            if($last_run!="")
                                echo esc_html(date("d/m/Y H:i:s",strtotime($last_run)));
                            else
                                esc_html_e("Never","dropshipping-romania-avex");
                            ?>
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
                <strong><?php esc_html_e("Notes","dropshipping-romania-avex");?>:</strong>
            </p>
            <p>
                <?php esc_html_e("Number of max products to import in configuration is set to","dropshipping-romania-avex");?>: <?php
                if($avex->config->max_products>0)
                    echo esc_html($avex->config->max_products);
                else
                    esc_html_e("Unlimited","dropshipping-romania-avex");
                ?>
            </p>
            <p>
                <?php esc_html_e("You can always stop a cron and start it again to start as soon as possible, exception is the Feed Cron which already has the re-import button","dropshipping-romania-avex");?>.
            </p>
            <?php
        }
        else
        {
            $msg=$result;
            ?>
            <div class="<?php echo esc_attr($msg['status']);?> notice is-dismissible inline">
                <p><?php echo esc_html($msg['msg']);?></p>
            </div>
            <?php
        }
    }
}
?>