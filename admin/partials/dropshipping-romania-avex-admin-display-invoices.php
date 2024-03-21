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

$request=$avex->getInvoices();
$invoices=$request->invoices;

$data_js="";
$msg="";
if(is_array($invoices) && count($invoices)>0)
{
    $action=isset($_POST['action'])?sanitize_text_field($_POST['action']):"";
    if($action=="setart_cron_invoices_now")
    {
        check_admin_referer( 'dropshipping_romania_avex_sync_cron_invoices_now' );
        $msg=$avex->startInvoicesCron();
    }
    $nonce = wp_create_nonce( 'dropshipping_romania_avex_ajax_get_invoices_nonce' );
    $data_js.='
    var dtable="";
    var controller_url="'.esc_url(admin_url('admin-ajax.php')).'";
    jQuery(document).ready(function() {
    var minDate, maxDate;
    jQuery.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var min = minDate.val();
            var max = maxDate.val();
            var date = new Date( data[1] );

            if (
                ( min === null && max === null ) ||
                ( min === null && date <= max ) ||
                ( min <= date   && max === null ) ||
                ( min <= date   && date <= max )
            ) {
                return true;
            }
            return false;
        }
    );
     minDate = new DateTime(jQuery("#start_date"), {
        format: \'YYYY-MM-DD\'
    });
    maxDate = new DateTime(jQuery("#end_date"), {
        format: \'YYYY-MM-DD\'
    });
    jQuery(".mydatatable").DataTable({
        dom: "lBfrtip",
        buttons: [
            "copy", "csv", "excel", "print"
        ],
        initComplete: function (settings, json) {
        },
        bAutoWidth: false,
        scrollY: 650,
        scrollX: true,
        autoWidth: false,
        scrollCollapse: true,
        paging: true,
        lengthMenu: [ 10, 25, 50, 75, 100 ],
        ordering: true,
        order: [[ 0, "desc" ]],
        info: true,
        orderMulti:true,
        processing: true,
        serverSide: true,
        ajax: {
                "url": controller_url,
                "type": "POST",
                "data": function ( d ) {
                    return jQuery.extend( {}, d, {
                    "start_date": jQuery(\'#start_date\').val(),
                    "end_date": jQuery(\'#end_date\').val(),
                    "security": \''.esc_js($nonce).'\',
                    "action": \''.esc_js('dropshipping_romania_avex_get_invoices').'\'
                })
            }
        },
        deferLoading: '.((isset($request->total_invoices))?esc_js($request->total_invoices):'"0"').',
        "language": {
            buttons: {
                copy: \''.esc_js(__("Copy","dropshipping-romania-avex")).'\',
                print: \''.esc_js(__("Print","dropshipping-romania-avex")).'\'
            },
            "emptyTable": \''.esc_js(__("No data available in table","dropshipping-romania-avex")).'\',
            "info":  \''.esc_js(__("Showing _START_ to _END_ of _TOTAL_ entries","dropshipping-romania-avex")).'\',
            "infoEmpty": \''.esc_js(__("No entries to show","dropshipping-romania-avex")).'\',
            "infoFiltered": \''.esc_js(__(" (filtered from _MAX_ total entries)","dropshipping-romania-avex")).'\',
            "infoPostFix": "",
            "infoThousands": ",",
            "lengthMenu": \''.esc_js(__("Display _MENU_ records","dropshipping-romania-avex")).'\',
            "lengthMenu": \''.esc_js(__("Show","dropshipping-romania-avex")).'\'+\'<select>\'+
            \'<option value="10">10</option>\'+
            \'<option value="25">25</option>\'+
            \'<option value="50">50</option>\'+
            \'<option value="75">75</option>\'+
            \'<option value="100">100</option>\'+
            \'</select> '.esc_js(__("entries","dropshipping-romania-avex")).'\',
            "loadingRecords": \''.esc_js(__("Please wait - loading","dropshipping-romania-avex")).'...\',
            "search": "<strong style=\'font-weight:bold;\'>'.esc_html(__("Search","dropshipping-romania-avex")).'<strong>",
            "zeroRecords": \''.esc_js(__("No records to display","dropshipping-romania-avex")).'\',
          "paginate": {
            "first": \''.esc_js(__("First page","dropshipping-romania-avex")).'\',
            "last": \''.esc_js(__("Last page","dropshipping-romania-avex")).'\',
            "next": \''.esc_js(__("Next","dropshipping-romania-avex")).'\',
            "previous": \''.esc_js(__("Previous","dropshipping-romania-avex")).'\'
          },
          "aria": {
            "sortAscending": \''.esc_js(__(" - click/return to sort ascending","dropshipping-romania-avex")).'\',
            "sortDescending": \''.esc_js(__(" - click/return to sort descending","dropshipping-romania-avex")).'\'
          }
        },
        columns: [
        { "name": "date" ,"targets": 0 },
        { "name": "post_id" ,"targets": 1 },
        { "name": "invoice_id" ,"targets": 2 },
        { "name": "order_id" ,"targets": 3 },
        { "name": "order_total" ,"targets": 4 }
      ]
    });
    dtable = jQuery(".mydatatable").dataTable().api();
    jQuery(".dataTables_filter input[type=search]")
    .unbind()
    .bind("input", dropshippingRomaniaAvexDelayUserInput(function (e) {
      if(this.value.length >= 3) {
            dtable.search(this.value).draw();
        }
        if(this.value == "") {
            dtable.search("").draw();
        }
        return;
    }, 500));
    jQuery(".dataTables_filter input[type=search]")
    .keydown(function( event ) {
      if ( event.which == 13 && jQuery(".dataTables_filter input[type=search]").val()!="") {
        dtable.search(jQuery(".dataTables_filter input[type=search]").val()).draw();
    }});

    
    jQuery(\'#start_date, #end_date\').on(\'change\', function () {
        dtable.draw(false);
    });
    jQuery( window ).on( "resize", function() {
      dtable.columns.adjust()
    } );
    });
    function dropshippingRomaniaAvexDelayUserInput(callback, ms)
    {
      var timer = 0;
      return function()
      {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function ()
        {
          callback.apply(context, args);
        }, ms || 0);
      };
    }
    ';
    wp_register_script( 'dropshipping-romania-avex_js_invoices_inline_script_handler', '' );
    wp_enqueue_script( 'dropshipping-romania-avex_js_invoices_inline_script_handler' );
    wp_add_inline_script("dropshipping-romania-avex_js_invoices_inline_script_handler",$data_js);
}
?>
<?php
if($msg!="")
{
    ?>
    <div class="<?php echo esc_attr($msg['status']);?> notice is-dismissible inline">
        <p><?php echo esc_html($msg['msg']);?></p>
    </div>
    <?php
}
?>
<h2><?php esc_html_e('Invoices','dropshipping-romania-avex');?></h2>
<div class="avex-tab-desc">
    <?php esc_html_e("Here you can view invoices generated by Avex for your company","dropshipping-romania-avex"); ?>.
    <?php
    if(is_array($invoices) && count($invoices)>0)
    {
        $nonce=wp_create_nonce( 'dropshipping_romania_avex_sync_cron_invoices_now' );
    ?>
    <form style="display: inline;" method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to start the Invoices Cron Now","dropshipping-romania-avex"));?>?')">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
        <input type="hidden" name="action" value="setart_cron_invoices_now" />
        <input type="submit" class="button-primary" value="<?php echo esc_attr(__("Sync Invoices Now","dropshipping-romania-avex"));?>" />
    </form>
    <?php
    }
    ?>
</div>
<div class="table-responsive table mb-0 pt-3 pe-2">
<?php
if(is_array($invoices) && count($invoices)>0)
{
?>
    <table class="table table-striped table-sm my-0 mydatatable" style="width:100%;">
        <thead>
            <tr>
                <th><?php esc_html_e("Start","dropshipping-romania-avex");?>
                <input type="search" tabindex="-1" id="start_date" name="start_date" value="" /></th>
                <th><?php esc_html_e("End","dropshipping-romania-avex");?>
                <input type="search" tabindex="-1" id="end_date" name="end_date" value="" /></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th><?php esc_html_e("Date","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("WC Order ID","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("Avex Invoice ID","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("Avex Order ID","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("Total","dropshipping-romania-avex");?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(is_array($invoices))
            {
                $uploads_base_url=wp_get_upload_dir()['baseurl'];
                foreach($invoices as $invoice)
                {
                    $invoice_url=$uploads_base_url."/dropshipping-romania-avex/".get_current_blog_id().$invoice->invoice;
                    $invoice_url=$invoice->link;
                    ?>
                    <tr>
                        <td><?php echo esc_html(date("d/m/Y H:i",$invoice->mdate));?></td>
                        <td><a target="_blank" href="<?php echo esc_url(admin_url('admin.php?page=wc-orders&action=edit&id='.$invoice->post_id));?>"><?php echo esc_html($invoice->post_id);?></a></td>
                        <td><a target="_blank" href="<?php echo esc_url($invoice_url);?>"><?php echo esc_html($invoice->invoice_id);?></a></td>
                        <td><a target="_blank" href="<?php echo esc_url($avex->avex_account_order_page.$invoice->order_id);?>"><?php echo esc_html($invoice->order_id);?></a></td>
                        <td><?php echo esc_html($invoice->order_total);?></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </tbody>
    </table>
<?php
}
else
{
    ?>
    <div id="avex-no-records">
        <?php esc_html_e("No records yet","dropshipping-romania-avex");?>
    </div>
    <?php
}
?>
</div>