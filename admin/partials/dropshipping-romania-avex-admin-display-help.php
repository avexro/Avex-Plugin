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
?>
<h2><?php esc_html_e('Help','dropshipping-romania-avex');?></h2>
<div class="avex-tab-desc">
    <?php esc_html_e("Here you can get help on various issues with your Avex integration","dropshipping-romania-avex"); ?>.
</div>
<table id="avex-status-table" class="widefat" cellspacing="0">
    <tr>
        <td><strong><?php esc_html_e("How to setup my Avex account","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("On the Dashboard tab you need to add your username and password used when you created your Avex Account, if everything is ok you will se a message to continue with your first Avex products import","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("What happens if I changed my Avex API details","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("You would need to go in configuration tab and set the correct Avex API username and password","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("How do I import products from Avex and how the system works","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("It is recommended that you set in Configuration tab a low value like 20 for the import limit, also make sure you have the correct values for WC Sales Price (what percentage should the system add to the Avex price, this will be what your customers would pay, keep in mind VAT and your desired profit) and WC Regular Price (this is the price shown to the customers as the reduced from price and it will be calculated by adding a percent to the price your customers actually pay for the products)","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("After you set up the details from above you can do your first import from the Dashboard, all imports will run in background and at minimal hardware resource consumption","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("At this moment you can start up the other cron jobs found in Dashboard, the Feed cron is the main one which grabs new products with all details including images and is meant to run once a day, or if you like once a couple of days, keep in mind that all crons use the maximum products value to limit the products import, when testing is done you should set this value to 0 for no limit","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("The API cron is meant to be ran recursively at a time interval like 30 minutes, this cron only updates the products stock value from Avex","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("The Orders cron is meant to be ran more recursively like every 15 minutes, this cron will update the order status from Avex in your WooCommerce shop, when you receive a new order you need to create your AWB file with your favorite courier and upload the AWB on your admin order page and send it to Avex, when Avex sends or cancels the order the Orders cron will update the order status correspondingly and notify the customer if applicable and the notifications email from Configurations page","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("The Invoices cron is meant to be ran at a higher interval like once per day, this cron will import all your Avex invoices so you can find them in the Invoices tab","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><?php esc_html_e("Now that all the crons are setup you can import and re-import products from Dashboard, test the system and when you are happy set the products limit to 0 in Configuration","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("What is the Invoices tab","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("When an order is completed by Avex an Invoice gets generated for your use, it is your accounting Invoice, they get listed here and can be downloaded for future use","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("What is the Logs tab","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("In the Logs tab you can see system logs like a cron started and finished, what actions did the users do, like Admin sent the order to Avex for processing, or Admin cancelled an order, or Orders cron changed the order status to Completed etc., Logs are away to monitor the system for any malfunction and know what actions were made either by users of system","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("What happens if I accidentally delete the Avex plugin","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("Upon deletion the plugin will not delete the WooCommerce products and it will re-link them upon reinstall, this is not all lost, the only thing the plugin will delete are the invoices and uploaded AWBs if set so in Configuration, the files are stored in the WordPress Uploads folder under dropshipping-romania-avex folder","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("Is the Avex plugin WordPress Multisite ready","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("Yes, the plugin was created to work with WordPress Multisite, for uploads under the dropshipping-romania-avex folder there will be sub-folders with the blog ID","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("How can I bulk publish or unpublish all products","dropshipping-romania-avex");?>?</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("In the Dashboard tab just click on the re-import button and check the publish checkbox if you want them all to be published, or leave unchecked to be unpublished, also leave the override products unchecked for it to run fast","dropshipping-romania-avex");?>.</td>
    </tr>
    <tr>
        <td><strong><?php esc_html_e("Need more Help","dropshipping-romania-avex");?>.</strong></td>
    </tr>
    <tr>
        <td><?php esc_html_e("Please use the support system on our website for further assistance by clicking","dropshipping-romania-avex");?> <a href="https://avex.ro/support.html" target="_blank"><?php esc_html_e("here","dropshipping-romania-avex");?></a>.</td>
    </tr>
</div>