=== Dropshipping Romania Avex ===
Contributors: @claudiumaftei
Donate link: https://avex.ro/
Tags: woocommerce, dropshipping, dropshipping romania, dropshiping avex, dropshipping romania avex
Requires at least: 6.2
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Importer and Supplier in the Dropshipping system. B2B platform, importer prices. Create your own WooCommerce online shop in a few minutes, without any physical stock.

== Description ==

WHAT IS THE AVEX DROPSHIPPING SERVICE?

Dropshipping is a business model that allows you to have all AVEX stocks at your disposal without having to invest a single cent.

Benefits:

* You don't need to accumulate stock or invest in stock. Thousands of AVEX products will become your stock.
* We do the preparation for orders and packaging, we become your logistics operator.
* We handle direct delivery to the end customer, and ship with your name so your customers associate their orders with your store.
* We offer options to synchronize prices and stocks with your platform in .csv format

WHAT YOU NEED TO KNOW BEFORE STARTING COLLABORATION IN A DROPSHIPPING SYSTEM WITH AVEX.ro

What will you do next:

* Although you don't need to invest in inventory, you still need to have some cash that you can use to pay your purchase invoices until you collect your refunds from the courier.
* To keep in constant contact with your customers both for delivery and if it is about return or warranty (Avex will not contact your customers in any way)
* You process the order from your website and place it on Avex so that we can prepare it. (Check stock on Avex first if it's not automatically synced with your platform)
* You generate the AWB in the courier app for your customer and upload it alongside the order placed on Avex (Avex will not generate AWBs for your customers)
* You invoice the goods to your customer and send the invoice by email (Avex will invoice you the ordered goods within a few minutes of the order and you will find it in the customer account to be able to upload it to the management. Avex does not issue invoices directly to your customers)
* You collect the money in your account from the courier for the delivered parcels (Avex will not collect the money from the courier for you)
* You pay the counter value of the invoices issued by Avex and upload the payments to the "Financial" section of your account. (When the amount loaded in the wallet reaches zero, no more orders can be placed until another payment is loaded into the platform)

What AVEX does:

* Prepare the goods for your customer in cardboard boxes or foil bags without our company logo so that your customers associate their orders with your store and not with AVEX, except for the importer labels applied individually to each product, a mandatory condition for any importer from the EU
* Apply the AWB to the parcel (the AWB generated by you and uploaded alongside the order placed on Avex)
* Generates the tax invoice and uploads it to the customer account so you can download and upload it to accounting/management. When the invoice is generated, you will be notified automatically by email
* Generates the cancellation invoice if the shipped package was not picked up by the recipient and returned to Avex. You will receive an email notification about this aspect and a cancellation invoice for the product/products in the package. The value of the canceled invoice can be used to place the following orders on Avex

In other words:

You deal entirely with your customers, and Avex packs and ships the goods to them, after which it generates the tax invoice for the ordered products that you will find in the customer account, next to the order.

This plugin is intended to use the AVEX Ddopshipping API, the used API endpoints are:

* https://api.avex.ro/account/get_status - to check your account status
* https://export.avex.ro/wp-load.php?security_token=016d0da95f890dba&export_id=3&action=get_data - product feed to import products to your shop
* https://api.avex.ro/products/list_products - API endpoint to retreive products
* https://api.avex.ro/orders/add_order - API endpoint to send an order to AVEX
* https://api.avex.ro/orders/cancel_order - API endpoint to cancel an order on AVEX
* https://api.avex.ro/orders/get_order_status/ - API endpoint to check the status of an order
* https://api.avex.ro/account/list_invoices - API endpoint to list your AVEX invoices
* https://api.avex.ro/account/get_invoice/ - API endpoint to retreive one of your invoices
* https://avex.ro/index.php?dispatch=orders.details&order_id= - web resource to easily load an Avex order on your web browser

By using this plugin you and your WC clients agree with:
* AVEX Terms and Conditions https://avex.ro/termeni-si-conditii-de-colaborare.html
* AVEX Privacy Policy https://avex.ro/date-cu-caracter-personal.html


== Installation ==
1. Upload the Dropshipping Romania Avex plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Configure the plugin
4. Log in to the API
5. Enjoy

== Frequently Asked Questions ==

= How to setup my Avex account? =

On the Dashboard tab you need to add your Avex API username and Avex API password, if everything is ok you will se a message to continue with your first Avex products import.

= What happens if I changed my Avex API details? =

You would need to go in configuration tab and set the correct Avex API username and password.

= How do I import products from Avex and how the system works? =
It is recommended that you set in Configuration tab a low value like 20 for the import limit, also make sure you have the correct values for WC Sales Price (what percentage should the system add to the Avex price, this will be what your customers would pay, keep in mind VAT and your desired profit) and WC Regular Price (this is the price shown to the customers as the reduced from price and it will be calculated by adding a percent to the price your customers actually pay for the products).

After you set up the details from above you can do your first import from the Dashboard, all imports will run in background and at minimal hardware resource consumption.

At this moment you can start up the other cron jobs found in Dashboard, the Feed cron is the main one which grabs new products with all details including images and is meant to run once a day, or if you like once a couple of days, keep in mind that all crons use the maximum products value to limit the products import, when testing is done you should set this value to 0 for no limit.

The API cron is meant to be ran recursively at a time interval like 30 minutes, this cron only updates the products stock value from Avex.

The Orders cron is meant to be ran more recursively like every 15 minutes, this cron will update the order status from Avex in your WooCommerce shop, when you receive a new order you need to create your AWB file with your favorite courier and upload the AWB on your admin order page and send it to Avex, when Avex sends or cancels the order the Orders cron will update the order status correspondingly and notify the customer if applicable and the notifications email from Configurations page.

Now that all the crons are setup you can import and re-import products from Dashboard, test the system and when you are happy set the products limit to 0 in Configuration.

= What is the Invoices tab? =

When an order is completed by Avex an Invoice gets generated for your use, it is your accounting Invoice, they get listed here and can be downloaded for future use.

= What is the Logs tab? =

In the Logs tab you can see system logs like a cron started and finished, what actions did the users do, like Admin sent the order to Avex for processing, or Admin cancelled an order, or Orders cron changed the order status to Completed etc., Logs are away to monitor the system for any malfunction and know what actions were made either by users of system.

= What happens if I accidentally delete the Avex plugin? =

Upon deletion the plugin will not delete the WooCommerce products and it will re-link them upon reinstall, this is not all lost, the only thing the plugin will delete are the invoices and uploaded AWBs if set so in Configuration, the files are stored in the WordPress Uploads folder under dropshipping-romania-avex folder.

= Is the Avex plugin WordPress Multisite ready? =

Yes, the plugin was created to work with WordPress Multisite, for uploads under the dropshipping-romania-avex folder there will be sub-folders with the blog ID.

== Screenshots ==

1. Dashboard not configured
2. Config page 1
3. Config page 2
4. Config page 3
5. Dashboard configured
6. Dashboard importing products
7. Logs tab

== Dependencies ==
1. WooCommerce
2. PHP 7.2
3. Action Scheduler
4. PHP proc_nice function to be enabled

== Changelog ==

= 1.0.0 =
* First release of Dropshipping Romania Avex plugin