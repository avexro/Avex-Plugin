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

$request=$avex->getLogs();
$logs=$request->logs;

$data_js="";

if(is_array($logs) && count($logs)>0)
{
    $nonce = wp_create_nonce( 'dropshipping_romania_avex_ajax_get_logs_nonce' );
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
                    "action": \''.esc_js('dropshipping_romania_avex_get_logs').'\'
                })
            }
        },
        deferLoading: '.((isset($request->total_logs))?esc_js($request->total_logs):'"0"').',
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
        { "name": "log" ,"targets": 1 },
        { "name": "name" ,"targets": 2 }
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
    wp_register_script( 'dropshipping-romania-avex_js_logs_inline_script_handler', '' );
    wp_enqueue_script( 'dropshipping-romania-avex_js_logs_inline_script_handler' );
    wp_add_inline_script("dropshipping-romania-avex_js_logs_inline_script_handler",$data_js);
}
?>
<h2><?php esc_html_e('Logs','dropshipping-romania-avex');?></h2>
<div class="avex-tab-desc">
    <?php esc_html_e("Here you can view logs for various user actions and synchronization events","dropshipping-romania-avex"); ?>.
</div>
<div class="table-responsive table mb-0 pt-3 pe-2">
<?php
if(is_array($logs) && count($logs)>0)
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
            </tr>
            <tr>
                <th><?php esc_html_e("Date","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("Log","dropshipping-romania-avex");?></th>
                <th><?php esc_html_e("Name","dropshipping-romania-avex");?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(is_array($logs))
            {
                foreach($logs as $log)
                {
                    ?>
                    <tr>
                        <td><?php echo esc_html(date("d/m/Y H:i",$log->mdate));?></td>
                        <td><?php echo esc_html($log->log);?></td>
                        <td><?php echo ((($log->display_name)!="")?esc_html($log->display_name):esc_html("system","dropshipping-romania-avex"));?></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </tbody>
    </table>
    <small>
        <?php
        esc_html_e("Note: Logs get deleted after","dropshipping-romania-avex");
        echo " ".esc_html($avex->config_front->delete_logs_older_than)." ";
        if($avex->config_front->delete_logs_older_than==1)
            echo esc_html__("month","dropshipping-romania-avex");
        else
            echo esc_html__("months","dropshipping-romania-avex");
        ?>.
    </small>
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