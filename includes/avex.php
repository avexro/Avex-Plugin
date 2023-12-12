<?php
/**
 * @package    Dropshipping_Romania_Avex
 * @subpackage Dropshipping_Romania_Avex/includes
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace DropshippingRomaniaAvex;
use \stdClass;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

class avex{
    
    public $config;
    public $config_front;
    public $avex_api_status_url='https://api.avex.ro/account/get_status';
    public $avex_products_feed_url='https://export.avex.ro/wp-load.php?security_token=016d0da95f890dba&export_id=3&action=get_data';
    public $avex_api_products_url='https://api.avex.ro/products/list_products';
    public $avex_api_new_order_url="https://api.avex.ro/orders/add_order";
    public $avex_api_cancel_order_url="https://api.avex.ro/orders/cancel_order";
    public $avex_api_order_status_url="https://api.avex.ro/orders/get_order_status/";//?order_id=37479
    public $avex_api_invoices_url="https://api.avex.ro/account/list_invoices";
    public $avex_api_invoice_url="https://api.avex.ro/account/get_invoice/";//?invoice_id=39767
    public $avex_completed_status=array("finalizată","finalizata");
    public $avex_cancelled_status=array("anulată","anulata");
    public $avex_account_order_page="https://avex.ro/index.php?dispatch=orders.details&order_id=";

    public function __construct(){
        $this->loadConfig();
    }
    public function loadConfig()
    {
        global $wpdb;
        $this->config=new stdClass;
        $this->config_front=new stdClass;
        $sql=$wpdb->prepare("select * from ".$wpdb->prefix."dropshipping_romania_avex_config where 1");
        $results=$wpdb->get_results($sql);
        if($results){
            foreach($results as $r){
                if(!isset($this->config->{$r->config_name}))
                    $this->config->{$r->config_name}=$r->config_value;
                if(!isset($this->config_front->{$r->config_name}) && $r->show_front==1)
                    $this->config_front->{$r->config_name}=$r->config_value;
            }
        }
    }
    public function is_woocommerce_activated(){
        if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
    }
    public function saveSettings()
    {
        global $wpdb;
        $cnt=0;
        $config_values=array(
            'api_user' => '',
            'api_password' => '',
            'max_products' => '',
            'notifications_enabled' => '',
            'notifications_email' => '',
            'price_add_percent' => '',
            'price_reduced_percent' => '',
            'feed_sync_interval' => '',
            'feed_sync_price_override' => '',
            'feed_sync_add_new_products' => '',
            'feed_sync_publish_new_products' => '',
            'api_sync_interval' => '',
            'api_sync_price_override' => '',
            'sync_orders_interval' => '',
            'sync_orders_newer_than' => '',
            'completed_wc_status' => '',
            'processing_wc_status' => '',
            'cancelled_wc_status' => '',
            'delete_logs_older_than' => '',
            'delete_invoices_upon_uninstall' => ''
        );
        $checkboxes=array(
            'notifications_enabled',
            'feed_sync_enabled',
            'feed_sync_price_override',
            'feed_sync_add_new_products',
            'feed_sync_publish_new_products',
            'api_sync_enabled',
            'api_sync_price_override',
            'sync_orders_enabled',
            'delete_invoices_upon_uninstall'
        );
        foreach($config_values as $config_name => $config_value)
        {
            if(isset($_POST[$config_name]))
            {
                $the_config_value=sanitize_text_field($_POST[$config_name]);
                if($config_name=='api_password')
                    $the_config_value=$_POST[$config_name];//passwords cannot be sanitized
            }
            else if(in_array($config_name, $checkboxes))
                $the_config_value="off";//unset checkboxes
            else
                continue;//WC not enabled, skip these values
            if(isset($this->config_front->$config_name))
            {
                if(in_array($config_name, $checkboxes))
                {
                    if($the_config_value=='on')
                        $the_config_value="yes";
                    else
                        $the_config_value="no";
                }
                $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_config set config_value=%s where config_name=%s",array($the_config_value,$config_name));
                if(!$wpdb->query($sql) && $wpdb->last_error !== '')
                {
                    return array('status'=>'error','msg'=>__("Error in saving configuration","dropshipping-romania-avex"));
                }
                else
                    $cnt++;
            }
        }
        $this->saveLog(__("Saved configuration","dropshipping-romania-avex"));
        return array('status'=>'updated','cnt'=>$cnt,'msg'=>__("Configuration values updated","dropshipping-romania-avex"));
    }
    public function saveLog($log="")
    {
        global $wpdb;
        if($log!="")
        {
            $user_id=get_current_user_id();
            $log=sanitize_text_field($log);
            $sql=$wpdb->prepare("insert into ".$wpdb->prefix."dropshipping_romania_avex_logs set user_id=%d, log=%s, mdate=%d",array($user_id,$log,current_time('U')));
            $wpdb->query($sql);
        }
    }
    public function getLogs()
    {
        global $wpdb;

        $draw=isset($_POST['draw'])?(int)$_POST['draw']:0;
        $start=isset($_POST['start'])?(int)$_POST['start']:0;
        $length=isset($_POST['length'])?(int)$_POST['length']:10;
        $search_arr=isset($_POST['search'])?$_POST['search']:array();
        $search=isset($search_arr['value'])?sanitize_text_field($search_arr['value']):"";
        $start_date=isset($_POST['start_date'])?sanitize_text_field($_POST['start_date']):"";
        $end_date=isset($_POST['end_date'])?sanitize_text_field($_POST['end_date']):"";
        $order_arr=isset($_POST['order'])?$_POST['order']:array();
        $logs=new stdClass;
        $logs->logs=array();
        $logs->total_logs=0;
        $logs->total_filtered_logs=0;
        $logs->draw=$draw;

        if($start_date!="")
            $start_date=strtotime(date("Y-m-d 00:00:00",strtotime($start_date)));
        else
            $start_date=0;
        if($end_date!="")
            $end_date=strtotime(date("Y-m-d 23:59:59",strtotime($end_date)));
        else
            $end_date=strtotime(date("Y-m-d")." +1 day");

        $order_by=array();
        $order_by[]="l.mdate desc";
        if(count($order_arr)>0)
        {
            $sortables=array();
            foreach($order_arr as $order)
                $sortables[$order['column']]=$order['dir'];
            $order_by=array();
            foreach($sortables as $col => $order)
            {
                if($col==0 && $order!="")
                    $order_by[]="l.mdate ".(($order=='asc')?'asc':'desc');
                if($col==1 && $order!="")
                    $order_by[]="l.`log` ".(($order=='asc')?'asc':'desc');
                if($col==2 && $order!="")
                    $order_by[]="u.display_name ".(($order=='asc')?'asc':'desc');
            }
        }
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_logs 
        where 1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total))
        {
            $logs->total_logs=$result->total;
            $logs->total_filtered_logs=$result->total;
        }

        if($search!="")
        {
            $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_logs l
            left join ".$wpdb->prefix."users u on u.ID=l.user_id
            where
            l.mdate>=%s and
            l.mdate<=%s and
            (
                l.`log` like %s or
                u.display_name like %s or
                u.user_email like %s or
                u.user_nicename like %s or
                u.display_name like %s or
                u.user_login like %s
            )",array($start_date,$end_date,"%".$search."%","%".$search."%","%".$search."%","%".$search."%","%".$search."%","%".$search."%"));
            $result=$wpdb->get_row($sql);
            if(isset($result->total))
                $logs->total_filtered_logs=$result->total;

            $sql=$wpdb->prepare("select l.*, u.display_name from ".$wpdb->prefix."dropshipping_romania_avex_logs l
            left join ".$wpdb->prefix."users u on u.ID=l.user_id
            where
            l.mdate>=%s and
            l.mdate<=%s and
            (
                l.`log` like %s or
                u.display_name like %s or
                u.user_email like %s or
                u.user_nicename like %s or
                u.display_name like %s or
                u.user_login like %s
            )
            order by ".implode(",",array_map('esc_sql',$order_by))."
            limit %d,%d",array($start_date,$end_date,"%".$search."%","%".$search."%","%".$search."%","%".$search."%","%".$search."%","%".$search."%",$start,$length));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
                $logs->logs=$results;
        }
        else
        {
            if($start_date!="" || $end_date!="")
            {
                $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_logs l
                where
                l.mdate>=%s and
                l.mdate<=%s",array($start_date,$end_date));
                $result=$wpdb->get_row($sql);
                if(isset($result->total))
                    $logs->total_filtered_logs=$result->total;
            }
            $sql=$wpdb->prepare("select l.*, u.display_name from ".$wpdb->prefix."dropshipping_romania_avex_logs l
            left join ".$wpdb->prefix."users u on u.ID=l.user_id
            where
            l.mdate>=%s and
            l.mdate<=%s
            order by ".implode(",",array_map('esc_sql',$order_by))."
            limit %d,%d",array($start_date,$end_date,$start,$length));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
                $logs->logs=$results;
        }
        return $logs;
    }
    public function getLogsAjax()
    {
        $data=array('data'=>array());
        $logs=$this->getLogs();

        if(is_array($logs->logs))
        {
            foreach($logs->logs as $log)
            {
                $result=array();
                $result[]=esc_html(date("d/m/Y H:i",$log->mdate));
                $result[]=esc_html($log->log);
                $result[]=(($log->display_name!="")?esc_html($log->display_name):esc_html(__("system","dropshipping-romania-avex")));
                $data['data'][]=$result;
            }
        }
        if(count($data))
        {
            $data['draw']=esc_html($logs->draw);
            $data['recordsTotal']=esc_html($logs->total_logs);
            $data['recordsFiltered']=esc_html($logs->total_filtered_logs);
            echo json_encode($data);
        }
    }
    public function setSetupStep($step=0)
    {
        global $wpdb;
        $step=(int)$step;
        $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_config set config_value=%d where config_name='setup_step'",array($step));
        $wpdb->query($sql);
    }
    public function getSetupStep()
    {
        global $wpdb;
        $sql=$wpdb->prepare("select config_value from ".$wpdb->prefix."dropshipping_romania_avex_config where config_name='setup_step'");
        $result=$wpdb->get_row($sql);
        if(isset($result->config_value))
            return (int)$result->config_value;
        return false;
    }
    public function setSetupValue($config_name="",$config_value="")
    {
        global $wpdb;
        $config_name=sanitize_text_field($config_name);
        $config_value=sanitize_text_field($config_value);
        if($config_name!="" && $config_value!="")
        {
            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_config set config_value=%s where config_name=%s",array($config_value,$config_name));
            $wpdb->query($sql);
        }
    }
    public function getSetupValue($config_name="")
    {
        global $wpdb;
        $config_name=sanitize_text_field($config_name);
        if($config_name!="")
        {
            $sql=$wpdb->prepare("select config_value from ".$wpdb->prefix."dropshipping_romania_avex_config where config_name=%s",array($config_name));
            $result=$wpdb->get_row($sql);
            if(isset($result->config_value))
                return esc_html($result->config_value);
        }
    }
    public function doSetupStep0()
    {
        global $wpdb;
        $api_user=isset($_POST['avex_username'])?sanitize_text_field($_POST['avex_username']):"";
        $api_password=isset($_POST['avex_password'])?$_POST['avex_password']:"";//password cannot be sanitized
        if($api_user!="" && $api_password!="")
        {
            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_config set config_value=%s where config_name='api_user'",array($api_user));
            $wpdb->query($sql);
            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_config set config_value=%s where config_name='api_password'",array($api_password));
            $wpdb->query($sql);
            $this->loadConfig();
            $result=$this->doAvexRequest($this->avex_api_status_url);
            if(is_array($result) && isset($result['status']) && $result['status']=='ok')
            {
                if(isset($result['msg']->data))
                {
                    $api=$result['msg']->data;
                    if(isset($api->status) && $api->status=='active')
                    {
                        if(get_option("avex_the_dropshipping_romania_avex_activation_is_done")=="have_products")
                        {
                            update_option("avex_the_dropshipping_romania_avex_activation_is_done","done");
                            $this->setSetupStep(3);
                        }
                        else
                            $this->setSetupStep(1);
                        $this->loadConfig();
                        $this->saveLog(__("Started Setup process with success","dropshipping-romania-avex"));
                        return array('status'=>'updated','msg'=>__("Avex API authentication Success","dropshipping-romania-avex"));
                    }
                    else
                    {
                        $this->saveLog(__("Error in starting the Setup process, API access not active","dropshipping-romania-avex"));
                        return array('status'=>'error','msg'=>__("Your Avex API access is not active","dropshipping-romania-avex"));
                    }
                }
            }
            else
            {
                $this->saveLog(__("Error in starting the Setup process","dropshipping-romania-avex").": ".$result['msg']);
                return array('status'=>'error','msg'=>$result['msg']);
            }
        }
        else
            return array('status'=>'error','msg'=>__("Avex API user and password are required","dropshipping-romania-avex"));
    }
    public function returnApiAuthProblem()
    {
        return array('status'=>'error','msg'=>__("Your Avex API access does not seam to work, if you changed your password in your Avex account make sure that you change the details in Config too","dropshipping-romania-avex"));
    }
    public function getAccountStatus()
    {
        $result=$this->doAvexRequest($this->avex_api_status_url);
        if(is_array($result) && isset($result['status']) && $result['status']=='ok')
            return array("status"=>"ok", "msg"=>$result['msg']);
        else
            return $this->returnApiAuthProblem();
    }
    public function doAvexRequest($url="",$params="",$args_arr=array(), $request_type="POST")
    {
        $result=array("status"=>"error", "msg"=>__("Something went wrong, please try again","dropshipping-romania-avex"));
        $request_type=sanitize_text_field($request_type);
        $url=sanitize_url($url);
        $verify_ssl=true;
        if(isset($this->config->curl_ssl_verify) && strtolower($this->config->curl_ssl_verify)=="no")//not used
            $verify_ssl=false;
        $args=array();
        $token=base64_encode($this->config->api_user.":".$this->config->api_password);
        $body="";
        if($request_type=="POST")
        {
            parse_str($params,$args);
            if(is_array($args_arr) && count($args_arr)>0)
                $args=array_merge($args,$args_arr);
            $response = wp_remote_post($url, array(
                'headers'     => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json; charset=utf-8'),
                'body'        => json_encode($args),
                'method'      => 'POST',
                'data_format' => 'body',
            ));
        }
        else
        {
            $args=array('method' => $request_type,'body' => $args, 'headers'=>array('Authorization' => 'Bearer ' . $token), 'sslverify' => $verify_ssl);
            $response = wp_remote_post( $url, $args );
        }
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $result=array("status"=>"error", "msg"=>$error_message);
        }
        else
        {
            $body = wp_remote_retrieve_body( $response );
            $response=json_decode($body);
            if(is_object($response) && isset($response->success) && $response->success==true)
                $result=array("status"=>"ok", "msg"=>$response);
            else if(is_object($response) && isset($response->message) && $response->message!="")
                $result=array("status"=>"error", "msg"=>__("Error Avex API","dropshipping-romania-avex").": ".$response->message);
        }
        return $result;
    }
    public function getProductsFeed()
    {
        $result=array("status"=>"error", "msg"=>__("Something went wrong, please try again","dropshipping-romania-avex"));
        $response=wp_remote_get($this->avex_products_feed_url);
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $result=array("status"=>"error", "msg"=>$error_message);
        }
        else
        {
            $body = wp_remote_retrieve_body( $response );
            $result=array("status"=>"ok", "msg"=>$body);
        }
        return $result;
    }
    public function doSetupStep1()
    {
        global $wpdb;
        $return=array("status"=>"error", "msg"=>__("Something went wrong, please try again","dropshipping-romania-avex"));
        $publish_products=isset($_POST['avex_publish_products_setup_step_1'])?sanitize_text_field($_POST['avex_publish_products_setup_step_1']):false;
        $override_products=isset($_POST['avex_override_products_setup_step_1'])?sanitize_text_field($_POST['avex_override_products_setup_step_1']):false;
        if($publish_products=="on")
            $publish_products=true;
        if($override_products=="on")
            $override_products=true;
        $request=$this->getProductsFeed();
        if(isset($request['status']) && $request['status']=='ok')
        {
            $result=$request['msg'];
            if($result!="")
            {
                $products=array_map("str_getcsv", explode("\n", $result));
                if(is_array($products) && count($products)>1)
                {
                    $max_products=(int)$this->config->max_products;
                    $cnt=0;
                    $first=0;
                    foreach($products as $prod)
                    {
                        if($max_products>0 && $max_products==$cnt)//we have a limit of products
                            break;
                        if($first==0)
                        {
                            $first=1;
                            continue;
                        }
                        if($prod[0]=='')
                            continue;
                        $sql=$wpdb->prepare("insert into ".$wpdb->prefix."dropshipping_romania_avex_products set
                            sku=%s,
                            title=%s,
                            description=%s,
                            category=%s,
                            sales_price=%s,
                            avex_price=%s,
                            stock=%d,
                            image=%s,
                            images=%s,
                            brand=%s,
                            weight=%s,
                            ean=%s,
                            mdate=%d
                            on duplicate key update
                            title=%s,
                            description=%s,
                            category=%s,
                            sales_price=%s,
                            avex_price=%s,
                            stock=%d,
                            image=%s,
                            images=%s,
                            brand=%s,
                            weight=%s,
                            ean=%s,
                            mdate=%d
                        ",array(
                            sanitize_text_field($prod[0]),//sku
                            sanitize_text_field($prod[1]),//title
                            wp_kses_post($prod[2]),//description
                            sanitize_text_field($prod[3]),//category
                            sanitize_text_field($prod[4]),//sales_price
                            sanitize_text_field($prod[5]),//avex_price
                            sanitize_text_field($prod[6]),//stock
                            sanitize_url($prod[7]),//image
                            wp_kses_post($prod[8]),//images
                            sanitize_text_field($prod[9]),//brand
                            sanitize_text_field($prod[10]),//weight
                            sanitize_text_field($prod[11]),//ean
                            time(),
                            sanitize_text_field($prod[1]),//title
                            wp_kses_post($prod[2]),//description
                            sanitize_text_field($prod[3]),//category
                            sanitize_text_field($prod[4]),//sales_price
                            sanitize_text_field($prod[5]),//avex_price
                            sanitize_text_field($prod[6]),//stock
                            sanitize_url($prod[7]),//image
                            wp_kses_post($prod[8]),//images
                            sanitize_text_field($prod[9]),//brand
                            sanitize_text_field($prod[10]),//weight
                            sanitize_text_field($prod[11]),//ean
                            time()
                        ));
                        $wpdb->query($sql);
                        if(!$wpdb->query($sql) && $wpdb->last_error !== '')
                        {
                            return array("status"=>"error", "msg"=>__("Database Error","dropshipping-romania-avex").": ".$wpdb->last_error);
                        }
                        $cnt++;
                    }
                    if(is_file(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php"))
                    {
                        $action_added=$this->addActionSchedulerTask("dropshipping_romania_avex_import_feed_from_admin_hook",array($publish_products,$override_products),"dropshipping_romania_avex");
                        if($action_added)
                        {
                            $this->setSetupStep(2);
                            $this->loadConfig();
                            $return=array("status"=>"updated", "msg"=>__("Saved product details into database, the import procedure will start soon","dropshipping-romania-avex"));
                        }
                        else
                            $return=array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
                    }
                }
                else
                    $return=array("status"=>"error", "msg"=>__("Did not receive a valid products array","dropshipping-romania-avex"));
            }
            else
                $return=array("status"=>"error", "msg"=>__("Did not receive a valid products array","dropshipping-romania-avex"));       
        }
        else
            $return=array("status"=>"error", "msg"=>$request['msg']);    
        return $return;
    }
    public function addActionSchedulerTask($task="", $args=array(), $group="")
    {
        if($task!="")
        {
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            if(!as_has_scheduled_action($task,$args,$group))
            {
                $action_added=true;
                $action_id=as_enqueue_async_action( $task, $args, $group, true, 0);
                if(!$action_id)
                    $action_added=false;
                return $action_added;
            }
        }
        return false;
    }
    public function getActionSchedulerTaskStatus($hook="", $status="")
    {
        $hook=sanitize_text_field($hook);
        $status=sanitize_text_field($status);

        if($hook!="" && $status!="")
        {
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            return as_get_scheduled_actions(
                array(
                    "hook"=>$hook,
                    "status"=>$status
                )
            );
        }
    }
    public function getActionSchedulerTaskStatusPending($hook="")
    {
        if($hook!="")
        {
            $hook=sanitize_text_field($hook);
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            return as_get_scheduled_actions(
                array(
                    "hook"=>$hook,
                    "status"=>\ActionScheduler_Store::STATUS_PENDING
                )
            );
        }
        return array();
    }
    public function getActionSchedulerTaskStatusRunning($hook="")
    {
        if($hook!="")
        {
            $hook=sanitize_text_field($hook);
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            return as_get_scheduled_actions(
                array(
                    "hook"=>$hook,
                    "status"=>\ActionScheduler_Store::STATUS_RUNNING
                )
            );
        }
        return array();
    }
    public function getCronStatus($hook="")
    {
        if($hook!="")
        {
            $hook=sanitize_text_field($hook);
            $pending=$this->getActionSchedulerTaskStatusPending($hook);
            $running=$this->getActionSchedulerTaskStatusRunning($hook);
            if(count($pending)>0)
                return "pending";
            if(count($running)>0)
                return "running";
        }
        return false;
    }
    public function getCronNextRun($hook="")
    {
        global $wpdb;
        if($hook!="")
        {
            $pending="";
            $running="";
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            $sql=$wpdb->prepare("select scheduled_date_local, status from ".$wpdb->prefix."actionscheduler_actions where hook=%s and (status=%s or status=%s)",array(sanitize_text_field($hook),sanitize_text_field(\ActionScheduler_Store::STATUS_RUNNING),sanitize_text_field(\ActionScheduler_Store::STATUS_PENDING)));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
            {
                foreach($results as $result)
                {
                    if($result->status==\ActionScheduler_Store::STATUS_PENDING)
                        $pending=$result->scheduled_date_local;
                    if($result->status==\ActionScheduler_Store::STATUS_RUNNING)
                        $running=$result->scheduled_date_local;
                }
            }
            if($pending!="")
                return $pending;
            if($running!="")
                return $running;
        }
        return "";
    }
    public function getCronLastRun($hook="")
    {
        global $wpdb;
        if($hook!="")
        {
            $completed="";
            if ( ! class_exists( 'ActionScheduler_Versions', false ) )
                require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
            $sql=$wpdb->prepare("select last_attempt_local, status from ".$wpdb->prefix."actionscheduler_actions where hook=%s and status=%s order by action_id desc limit 1",array(sanitize_text_field($hook),sanitize_text_field(\ActionScheduler_Store::STATUS_COMPLETE)));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
            {
                foreach($results as $result)
                {
                    if($result->status==\ActionScheduler_Store::STATUS_COMPLETE)
                        $completed=$result->last_attempt_local;
                }
            }
            if($completed!="")
                return $completed;
        }
        return "";
    }
    public function importFeedFromAdmin($publish_products="", $override_products="")
    {
        $publish_products=sanitize_text_field($publish_products);
        $override_products=sanitize_text_field($override_products);
        set_time_limit(0);
        proc_nice(20);
        $this->saveLog(__("Started Setup Feed products import","dropshipping-romania-avex"));
        $this->importFeedFromDb($publish_products, $override_products);
    }
    public function getImportFeedStatusAjax()
    {
        $config_step=$this->getSetupStep();
        if($config_step==3)
        {
            echo "1";
            return;
        }
        $have_something_todo=false;
        $admin_feed_status=$this->getAdminFeedStatus();
        $have_admin_task=false;
        if($admin_feed_status=="running")//running
        {
            $have_something_todo=true;
            $have_admin_task=true;
            $status=$this->getFeedProcessedProducts();
            $nonce=wp_create_nonce( 'dropshipping_romania_avex_cancel_admin_feed_import' );
            ?>
            <td colspan="3">
                <strong><?php echo esc_html_e("The Import Avex Products task is running","dropshipping-romania-avex");?></strong>
            </td>
            <td colspan="3">
                <strong><?php echo esc_html($status['done']);?>/<?php echo esc_html($status['total']);?></strong> <?php echo esc_html_e("products have been imported","dropshipping-romania-avex");?>
            </td>
            <td>
                <form method="post" action="" onSubmit="return confirm('<?php echo esc_js(__("Are you sure you want to cancel the import","dropshipping-romania-avex"));?>?')">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce);?>" />
                <input type="hidden" name="action" value="cancel_admin_feed_import" />
                <input type="submit" class="button-secondary" value="<?php echo esc_attr(__("Cancel","dropshipping-romania-avex"));?>" />
            </td>
            <?php
        }
        else if($admin_feed_status=="pending")//pending
        {
            $have_something_todo=true;
            $have_admin_task=true;
            ?>
            <td colspan="7">
                <strong><?php echo esc_html_e("The Import Avex Products task is pending in queue","dropshipping-romania-avex");?></strong>
            </td>
            <?php
        }

        if(!$have_admin_task)
        {
            $cron_feed_status=$this->getCronFeedStatus();
            if($cron_feed_status=="running")//running
            {
                $have_something_todo=true;
                $status=$this->getFeedProcessedProducts();
                $nonce_cancel_cron_feed=wp_create_nonce( 'dropshipping_romania_avex_cancel_cron_feed_import' );
                ?>
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
                <?php
            }
        }
        if(!$have_something_todo)//something went wrong, setting setup status to 3
        {
            $this->setSetupStep(3);
            echo "1";
            return;
        }
    }
    public function getFeedProcessedProducts()
    {
        global $wpdb;
        
        $total=0;
        $done=0;
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_products where 1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total))
            $total=$result->total;
        $sql=$wpdb->prepare("select count(*) as done from ".$wpdb->prefix."dropshipping_romania_avex_products where post_id>0 and imported=1");
        $result=$wpdb->get_row($sql);
        if(isset($result->done))
            $done=$result->done;
        return array("total"=>$total, "done"=>$done);
    }
    public function checkForPostIdForAlreadyExistingProduct($sku="")
    {
        global $wpdb;
        $sku=sanitize_text_field($sku);
        if($sku!="")
        {
            $sql=$wpdb->prepare("select post_id from ".$wpdb->prefix."postmeta where meta_key='_sku' and meta_value=%s",array($sku));
            $result=$wpdb->get_row($sql);
            if(isset($result->post_id))
            {
                $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set post_id=%d where sku=%s",array($result->post_id,$sku));
                $wpdb->query($sql);
                return $result->post_id;
            }
        }
        return 0;
    }
    public function exportTempCsvForImport($publish_products="", $override_products="", $publish_new_poducts="")
    {
        global $wpdb;
        $publish_products=sanitize_text_field($publish_products);
        $publish_new_poducts=sanitize_text_field($publish_new_poducts);
        $override_products=sanitize_text_field($override_products);
        $sql=$wpdb->prepare("select * from ".$wpdb->prefix."dropshipping_romania_avex_products where imported=%d order by sku limit %d",array(0,1));

        $results=$wpdb->get_results($sql);
        if(count($results)>0)
        {
            $csv=array();
            $csv[]=array("ID","Type","SKU","Name","Published","Is featured?","Visibility in catalog","Short description","Description","Date sale price starts","Date sale price ends","Tax status","Tax class","In stock?","Stock","Backorders allowed?","Sold individually?","Weight (lbs)","Length (in)","Width (in)","Height (in)","Allow customer reviews?","Purchase note","Sale price","Regular price","Categories","Tags","Shipping class","Images","Download limit","Download expiry days","Parent","Grouped products","Upsells","Cross-sells","External URL","Button text","Position");
            $published=0;
            if($publish_products)
                  $published=1;  
            $the_post_id=0;
            foreach($results as $prod)
            {
                $the_post_id=(($prod->post_id>0)?$prod->post_id:0);
                if($the_post_id==0)
                    $the_post_id=(int)$this->checkForPostIdForAlreadyExistingProduct($prod->sku);
                if($the_post_id==0 && $publish_new_poducts==false)
                    $published=0;
                else
                    $published=(($published)?1:0);
                $categories=$prod->category;
                $categories=str_replace(">"," > ",$categories);
                $images=array();
                $images[]=$prod->image;
                $tmp=explode(";",$prod->images);
                if(count($tmp)>0)
                {
                    foreach($tmp as $t)
                    {
                        if($t!="" && !in_array($t,$images))
                            $images[]=$t;
                    }
                }
                $images_str=implode(", ",$images);
                $csv[]=array(
                    $the_post_id,
                    "simple",
                    sanitize_text_field($prod->sku),
                    sanitize_text_field($prod->title),
                    sanitize_text_field($published),
                    0,
                    "visible",
                    "",
                    wp_kses_post($prod->description),
                    "",
                    "",
                    "taxable",
                    "",
                    1,
                    sanitize_text_field($prod->stock),
                    0,
                    0,
                    sanitize_text_field($prod->weight),
                    "",
                    "",
                    "",
                    1,
                    "",
                    sanitize_text_field($prod->avex_price),
                    sanitize_text_field($prod->sales_price),
                    sanitize_text_field($categories),
                    "",
                    "",
                    sanitize_text_field($images_str),
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    0
                );
            }
            $file_name=sanitize_file_name("tmp.csv");
            $tmp_folder=sanitize_file_name("tmp");

            $main_folder=DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH.get_current_blog_id();
            
            $this->checkUploadFolders();

            if(is_file($main_folder."/".$tmp_folder."/".$file_name))
                unlink($main_folder."/".$tmp_folder."/".$file_name);
            touch($main_folder."/".$tmp_folder."/".$file_name);
            $fp=fopen($main_folder."/".$tmp_folder."/".$file_name, 'w');
            foreach ($csv as $fields)
                fputcsv($fp, $fields);
            fclose($fp);

            return array("file"=>$main_folder."/".$tmp_folder."/".$file_name, "post_id"=>$the_post_id);
        }
        return false;
    }
    public function checkUploadFolders()
    {
        $tmp_folder=sanitize_file_name("tmp");

        $main_folder=DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH.get_current_blog_id();
        if(!is_dir($main_folder))
            mkdir($main_folder);

        if(!is_dir($main_folder."/".$tmp_folder))
            mkdir($main_folder."/".$tmp_folder);
        if(!is_file($main_folder."/".$tmp_folder."/index.php"))
            file_put_contents($main_folder."/".$tmp_folder."/index.php",'<?php // Silence is golden');

        $tmp_folder=sanitize_file_name("awb");

        if(!is_dir($main_folder."/".$tmp_folder))
            mkdir($main_folder."/".$tmp_folder);
        if(!is_file($main_folder."/".$tmp_folder."/index.php"))
            file_put_contents($main_folder."/".$tmp_folder."/index.php",'<?php // Silence is golden');

        $tmp_folder=sanitize_file_name("invoices");

        if(!is_dir($main_folder."/".$tmp_folder))
            mkdir($main_folder."/".$tmp_folder);
        if(!is_file($main_folder."/".$tmp_folder."/index.php"))
            file_put_contents($main_folder."/".$tmp_folder."/index.php",'<?php // Silence is golden');
    }
    public function getProductPublishStatus($post_id=0)
    {
        global $wpdb;
        $post_id=(int)$post_id;
        if($post_id>0)
        {
            $sql=$wpdb->prepare("select post_status from ".$wpdb->prefix."posts where ID=%d",array($post_id));
            $result=$wpdb->get_row($sql);
            if(isset($result->post_status))
                return $result->post_status;
        }
        return "draft";
    }
    public function setProductPublishStatus($post_id=0, $status="")
    {
        global $wpdb;
        $post_id=(int)$post_id;
        $status=sanitize_text_field($status);
        if($post_id>0 && $status!="")
        {
            $sql=$wpdb->prepare("update ".$wpdb->prefix."posts set post_status=%s where ID=%d",array($status,$post_id));
            $wpdb->query($sql);
        }
    }
    public function importFeedFromDb($publish_products="", $override_products="", $override_prices=true, $is_cron=false)
    {
        global $wpdb;
        $args = array(
            'role'    => 'administrator',
            'number' => 1
        );
        $users = get_users( $args );
        if(is_array($users) && count($users)==1)
            wp_set_current_user($users[0]->ID);//we need capabilities in cron for manage_product_terms in WC product import from CSV as an example
        
        if ( ! class_exists( 'WC_Product_CSV_Importer', false ) )
            require_once(WC()->plugin_path()."/includes/import/class-wc-product-csv-importer.php");
        require_once (WC()->plugin_path(). '/includes/admin/importers/mappings/mappings.php');
        $publish_products=sanitize_text_field($publish_products);
        $override_products=sanitize_text_field($override_products);
        $override_prices=sanitize_text_field($override_prices);
        $is_cron=sanitize_text_field($is_cron);
        if(!$is_cron)
        {
            $cron_running=$this->getSetupValue("admin_feed_running");
            if($cron_running==0)//set the cron as running
                $this->setSetupValue("admin_feed_running","1");
        }
        $import_products=(int)$this->config->max_products;
        if($import_products<=0)
        {
            $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_products where 1");
            $result=$wpdb->get_row($sql);
            if(isset($result->total))
                $import_products=$result->total;
        }
        for($j=0;$j<$import_products;$j++)
        {
            if(!$is_cron)
            {
                $cron_running=$this->getSetupValue("admin_feed_running");
                if($cron_running==0)
                {
                    $this->setSetupStep(3);
                    $this->setSetupValue("admin_feed_running","0");
                    $this->saveLog(__("Setup Feed products import manually stopped","dropshipping-romania-avex"));
                    return true;
                }
            }
            if($is_cron)
            {
                $cron_running=$this->getSetupValue("cron_feed_running");
                if($cron_running==0)
                {
                    $this->setSetupStep(3);
                    $this->setSetupValue("cron_feed_running","0");
                    $this->saveLog(__("Stopped the Feed Cron manually","dropshipping-romania-avex"));
                    return true;
                }
                $csv_result=$this->exportTempCsvForImport($publish_products, $override_products,$publish_products);
            }
            else
                $csv_result=$this->exportTempCsvForImport($publish_products, $override_products);
            $csv_file="";
            $csv_post_id=0;
            if(isset($csv_result['file']))
                $csv_file=$csv_result['file'];
            if(isset($csv_result['post_id']))
                $csv_post_id=(int)$csv_result['post_id'];
            if($csv_post_id==0)
                $override_products=false;//cannot override what doesn't exist
            if(is_file($csv_file))
            {
                $old_price=0;
                $old_sales_price=0;
                $old_post_status="";
                if($csv_post_id>0)
                {
                    $old_price=(float)get_post_meta($csv_post_id,"_regular_price",true);
                    $old_sales_price=(float)get_post_meta($csv_post_id,"_sale_price",true);
                    $old_post_status=$this->getProductPublishStatus($csv_post_id);
                }
                $importer=new \WC_Product_CSV_Importer($csv_file,
                    array(
                        "update_existing"=>$override_products,
                        "prevent_timeouts"=>false,
                        "mapping"=>wc_importer_default_english_mappings(array()),
                        "parse"=>true
                    )
                );
                $result=$importer->import();
                $product_ids=array();
                if(isset($result['imported']))
                    $product_ids=$result['imported'];
                if(isset($result['updated']))
                    $product_ids=array_merge($product_ids,$result['updated']);
                if(isset($result['skipped']))//not updated because override product not set
                {
                    //we'll treath them as updated
                    foreach($result['skipped'] as $skip)
                    {
                        if(isset($skip->error_data) && isset($skip->error_data['woocommerce_product_importer_error']) && isset($skip->error_data['woocommerce_product_importer_error']['id']))
                            $product_ids[]=$skip->error_data['woocommerce_product_importer_error']['id'];
                    }
                }

                if(count($product_ids)>0)
                {
                    foreach($product_ids as $post_id)
                    {
                        $sku=get_post_meta($post_id,"_sku",true);
                        $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set post_id=%d, imported=%d where sku=%s",array($post_id,1,$sku));
                        $wpdb->query($sql);
                        $sql=$wpdb->prepare("select category, image, images, sales_price, avex_price, brand, ean, weight from ".$wpdb->prefix."dropshipping_romania_avex_products where post_id=%d",$post_id);
                        $result=$wpdb->get_row($sql);
                        if(isset($result->ean))
                        {
                            update_post_meta($post_id,"_weight",sanitize_text_field($result->weight));
                            update_post_meta($post_id,"_avex_sales_price",sanitize_text_field($result->sales_price));
                            update_post_meta($post_id,"_avex_avex_price",sanitize_text_field($result->avex_price));
                            update_post_meta($post_id,"_avex_brand",sanitize_text_field($result->brand));
                            update_post_meta($post_id,"_avex_ean",sanitize_text_field($result->ean));
                            update_post_meta($post_id,"_avex_image",sanitize_text_field($result->image));
                            update_post_meta($post_id,"_avex_images",sanitize_text_field($result->images));
                            update_post_meta($post_id,"_avex_category",sanitize_text_field($result->category));
                            update_post_meta($post_id,"_avex_imported_product","1");
                            if($old_post_status!="")
                                $this->setProductPublishStatus($post_id,$old_post_status);
                            $sales_price=0;
                            $price_add_percent=(int)$this->config->price_add_percent;
                            if($price_add_percent>0)//sales price
                            {
                                $sales_price=(float)$result->avex_price;
                                $percent=$sales_price*$price_add_percent/100;
                                $percent=round($percent,2);
                                $sales_price=$sales_price+$percent;
                                $sales_price=number_format(round($sales_price,2),2,".","");
                                update_post_meta($post_id,"_sale_price",sanitize_text_field((float)$sales_price));
                                update_post_meta($post_id,"_price",sanitize_text_field((float)$sales_price));
                            }
                            else
                            {
                                $sales_price=(float)$result->avex_price;
                                update_post_meta($post_id,"_sale_price",sanitize_text_field((float)$sales_price));
                                update_post_meta($post_id,"_price",sanitize_text_field((float)$sales_price));
                            }
                            $price_reduced_percent=(int)$this->config->price_reduced_percent;
                            if($price_reduced_percent>0)//regular price
                            {
                                if($sales_price==0)
                                    $sales_price=$old_sales_price;
                                $percent=$sales_price*$price_reduced_percent/100;
                                $percent=round($percent,2);
                                $regular_price=$sales_price+$percent;
                                $regular_price=number_format(round($regular_price,2),2,".","");
                                update_post_meta($post_id,"_regular_price",sanitize_text_field((float)$regular_price));
                            }
                            else
                            {
                                if($sales_price==0)
                                    $regular_price=$old_sales_price;
                                if($sales_price==0)
                                    $regular_price=(float)$result->sales_price;;
                                 update_post_meta($post_id,"_regular_price",sanitize_text_field((float)$regular_price));
                            }
                        }
                        if(!$override_prices && $csv_post_id>0)
                        {
                            if($old_sales_price!="")
                                update_post_meta($post_id,"_sale_price",sanitize_text_field((float)$old_sales_price));
                            if($old_price!="")
                                update_post_meta($post_id,"_regular_price",sanitize_text_field((float)$old_price));
                        }
                        //update the status
                        $status="draft";
                        if($publish_products && $csv_post_id==0)//only new products
                        {
                            $status="publish";
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."posts set post_status=%s where ID=%d",array(sanitize_text_field($status),(int)$post_id));
                            $wpdb->query($sql);
                        }
                    }
                }
            }
        }
        $this->setSetupValue("admin_feed_running","0");
        $this->setSetupStep(3);
        $this->saveLog(__("Finished Setup Feed products import","dropshipping-romania-avex"));
    }
    public function cancelAdminFeedImport()
    {
        $this->setSetupValue("admin_feed_running","0");
        return array("status"=>"updated", "msg"=>__("Setup Feed products import manually stopped","dropshipping-romania-avex"));
    }
    public function getCronFeedStatus()
    {
        $status_running=$this->getActionSchedulerTaskStatus("dropshipping_romania_avex_import_feed_cron_hook",\ActionScheduler_Store::STATUS_RUNNING);
        if(count($status_running)>0)
            return "running";
        $running=$this->getSetupValue("cron_feed_running");
        if($running==1)
            return "running";
    }
    public function getAdminFeedStatus()
    {
        $status_running=$this->getActionSchedulerTaskStatus("dropshipping_romania_avex_import_feed_from_admin_hook",\ActionScheduler_Store::STATUS_RUNNING);
        if(count($status_running)>0)
            return "running";
        $running=$this->getSetupValue("admin_feed_running");
        if($running==1)
            return "running";
        $status_pending=$this->getActionSchedulerTaskStatus("dropshipping_romania_avex_import_feed_from_admin_hook",\ActionScheduler_Store::STATUS_PENDING);
        if(count($status_pending)>0)
            return "pending";
    }
    public function setAllProductsNotImported()
    {
        global $wpdb;
        $max_products=(int)$this->config->max_products;
        if($max_products>0)
            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set imported=0 where 1 limit %d",array($max_products));
        else
            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set imported=0 where 1");
        $wpdb->query($sql);
        $this->setSetupStep(1);
        $this->loadConfig();
        $this->saveLog(__("Set all existing products to be reimported","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("All products are ready for reimport","dropshipping-romania-avex"));
    }
    function deleteAllProducts()
    {
        global $wpdb;

        set_time_limit(0);
        proc_nice(20);

        $this->setSetupValue("deleting_products","1");

        $sql=$wpdb->prepare("truncate table ".$wpdb->prefix."dropshipping_romania_avex_products");
        $wpdb->query($sql);
        $sql=$wpdb->prepare("select post_id as ID from ".$wpdb->prefix."postmeta where meta_key='_avex_imported_product'");
        $results=$wpdb->get_results($sql);
        foreach($results as $result)
            $this->deleteProduct($result->ID,true);
        $this->setSetupStep(1);
        $this->loadConfig();
        $this->saveLog(__("Deleted all Avex products (including images)","dropshipping-romania-avex"));
        $this->setSetupValue("deleting_products","0");
    }
    public function scheduleDeleteAllProducts()
    {
        $action_added=$this->addActionSchedulerTask("dropshipping_romania_avex_import_delete_products_hook",array(),"dropshipping_romania_avex");
        if($action_added)
            $action_added=true;

        if($action_added)
        {
            $this->setSetupValue("deleting_products","1");
            $this->loadConfig();
            $this->saveLog(__("Scheduled the Products delete task","dropshipping-romania-avex"));
            return array("status"=>"updated", "msg"=>__("Scheduled the Products delete task, it will start soon","dropshipping-romania-avex"));
        }
        else
            return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
    }
    function deleteProduct($id, $force = FALSE)
    {
        $product = wc_get_product($id);
        if(!$product)
            return false;

        $img_ids=$product->get_gallery_image_ids();
        foreach($img_ids as $img_id)
            wp_delete_attachment($img_id,true);
        if ($force)
        {
            if ($product->is_type('variable'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->delete(true);
                }
            }
            elseif ($product->is_type('grouped'))
            {
                foreach ($product->get_children() as $child_id)
                {
                    $child = wc_get_product($child_id);
                    $child->set_parent_id(0);
                    $child->save();
                }
            }

            $product->delete(true);
            $result = $product->get_id() > 0 ? false : true;
        }
        else
        {
            $product->delete();
            $result = 'trash' === $product->get_status();
        }

        if (!$result)
            return false;
        if ($parent_id = wp_get_post_parent_id($id))
            wc_delete_product_transients($parent_id);
        return true;
    }
    public function getTotalImportedProducts()
    {
        global $wpdb;
        $total_imported=0;
        $total_products=0;
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_products where imported=1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total))
            $total_imported=$result->total;
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_products where 1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total))
            $total_products=$result->total;
        return array($total_imported,$total_products);
    }
    public function refreshProductsStockPrices()
    {
        global $wpdb;
        $update_prices=isset($_POST['avex_refresh_prices_setup_step_3'])?$_POST['avex_refresh_prices_setup_step_3']:false;
        if($update_prices=='on')
            $update_prices=true;
        $update_prices=false;//we do not use this anymore, prices can be updated only from feed
        set_time_limit(0);
        proc_nice(20);
        $this->saveLog(__("Started API product stock refresh","dropshipping-romania-avex"));
        $result=$this->doAvexRequest($this->avex_api_products_url);
        $cnt=0;
        if(is_array($result) && isset($result['status']) && $result['status']=='ok')
        {
            if(isset($result['msg']->data))
            {
                $products=$result['msg']->data;
                if(is_array($products) && count($products)>0)
                {
                    foreach($products as $prod)
                    {
                        if($update_prices)
                        {
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set stock=%s, sales_price=%s, avex_price=%s where sku=%s"
                                ,array(sanitize_text_field($prod->amount),
                                sanitize_text_field($prod->list_price),
                                sanitize_text_field($prod->price),
                                sanitize_text_field($prod->product_code)
                            ));
                            $count=$wpdb->query($sql);
                            if((int)$count>0)//we have it in db and was updated
                            {
                                $sql=$wpdb->prepare("select post_id, stock, sales_price from ".$wpdb->prefix."dropshipping_romania_avex_products where sku=%s",array($prod->product_code));
                                $result=$wpdb->get_row($sql);
                                if(isset($result->post_id))
                                {
                                    update_post_meta($result->post_id, '_stock', sanitize_text_field($result->stock));
                                    update_post_meta($result->post_id, '_regular_price', sanitize_text_field($result->sales_price));
                                    $cnt++;
                                }
                            }
                        }
                        else
                        {
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set stock=%s where sku=%s"
                                ,array(sanitize_text_field($prod->amount),
                                    sanitize_text_field($prod->product_code)
                                ));
                            $count=$wpdb->query($sql);
                            if((int)$count>0)//we have it in db and was updated
                            {
                                $sql=$wpdb->prepare("select post_id, stock from ".$wpdb->prefix."dropshipping_romania_avex_products where sku=%s",array(sanitize_text_field($prod->product_code)));
                                $result=$wpdb->get_row($sql);
                                if(isset($result->post_id))
                                {
                                    update_post_meta($result->post_id, '_stock', sanitize_text_field($result->stock));
                                    $cnt++;
                                }
                            }
                        }
                    }
                }
            }
        }
        else
        {
            return array('status'=>'error','msg'=>__("Refresh stock action did not received a valid response from Avex server","dropshipping-romania-avex"));
        }
        $this->saveLog(__("Finished API product stock refresh","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>$cnt." ".__("Products have the stock refreshed","dropshipping-romania-avex"));
    }
    public function startFeedCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_feed_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_feed_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=(int)$this->config->feed_sync_interval;
            $interval=$interval*24*60*60;
            $start_run=time();
            if((int)$this->config->max_products==0)
                $start_run=strtotime(date("Y-m-d 00:00")." + ".(int)$this->config->feed_sync_interval." day");
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_feed_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Set up the Feed Cron","dropshipping-romania-avex"));
                return array("status"=>"updated", "msg"=>__("Set up the Feed Cron","dropshipping-romania-avex"));
            }
            else
                return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
        }
        else
            return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
    }
    public function stopFeedCron()
    {
        as_unschedule_all_actions( "dropshipping_romania_avex_import_feed_cron_hook", array(), "dropshipping_romania_avex" );
        $this->saveLog(__("Stopped the Feed Cron","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("Stopped the Feed Cron","dropshipping-romania-avex"));
    }
    public function stopFeedCronManual()
    {
        $this->setSetupValue("cron_feed_running","0");
        $this->setSetupStep(3);
        $this->saveLog(__("Stopped the Feed Cron manually","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("Sent stop signal to the Feed cron","dropshipping-romania-avex"));
    }
    public function cleanLogs()
    {
        global $wpdb;
        $delete_logs_older_than=(int)$this->config->delete_logs_older_than;
        if($delete_logs_older_than<=0)
            $delete_logs_older_than=1;
        $stamp=strtotime(date("Y-m-d 00:00:00")." -".$delete_logs_older_than." months");
        $sql=$wpdb->prepare("delete from ".$wpdb->prefix."dropshipping_romania_avex_logs where mdate<%s",array($stamp));
        $wpdb->query($sql);
    }
    public function importFeedFromCron()
    {
        global $wpdb;

        set_time_limit(0);
        proc_nice(20);

        $this->cleanLogs();

        $price_override=((sanitize_text_field($this->config->feed_sync_price_override)=="yes")?true:false);
        $add_new_products=((sanitize_text_field($this->config->feed_sync_add_new_products)=="yes")?true:false);
        $publish_new_products=((sanitize_text_field($this->config->feed_sync_publish_new_products)=="yes")?true:false);

        $cron_running=$this->getSetupValue("cron_feed_running");
        if($cron_running==0)//set the cron as running
            $this->setSetupValue("cron_feed_running","1");

        $this->saveLog(__("Started the Feed Cron","dropshipping-romania-avex"));
        $this->setSetupStep(2);
        $request=$this->getProductsFeed();
        if(isset($request['status']) && $request['status']=='ok')
        {
            $result=$request['msg'];
            if($result!="")
            {
                $products=array_map("str_getcsv", explode("\n", $result));
                if(is_array($products) && count($products)>1)
                {
                    $max_products=(int)$this->config->max_products;
                    $cnt=0;
                    $first=0;
                    foreach($products as $prod)
                    {
                        if($max_products>0 && $max_products==$cnt)//we have a limit of products
                            break;
                        if($first==0)
                        {
                            $first=1;
                            continue;
                        }
                        if($prod[0]=='')
                            continue;
                        if($add_new_products)
                        {
                            $sql=$wpdb->prepare("insert into ".$wpdb->prefix."dropshipping_romania_avex_products set
                                sku=%s,
                                title=%s,
                                description=%s,
                                category=%s,
                                sales_price=%s,
                                avex_price=%s,
                                stock=%d,
                                image=%s,
                                images=%s,
                                brand=%s,
                                weight=%s,
                                ean=%s,
                                mdate=%d
                                on duplicate key update
                                title=%s,
                                description=%s,
                                category=%s,
                                sales_price=%s,
                                avex_price=%s,
                                stock=%d,
                                image=%s,
                                images=%s,
                                brand=%s,
                                weight=%s,
                                ean=%s,
                                mdate=%d
                            ",array(
                                sanitize_text_field($prod[0]),//sku
                                sanitize_text_field($prod[1]),//title
                                wp_kses_post($prod[2]),//description
                                sanitize_text_field($prod[3]),//category
                                sanitize_text_field($prod[4]),//sales_price
                                sanitize_text_field($prod[5]),//avex_price
                                sanitize_text_field($prod[6]),//stock
                                sanitize_url($prod[7]),//image
                                wp_kses_post($prod[8]),//images
                                sanitize_text_field($prod[9]),//brand
                                sanitize_text_field($prod[10]),//weight
                                sanitize_text_field($prod[11]),//ean
                                time(),
                                sanitize_text_field($prod[1]),//title
                                wp_kses_post($prod[2]),//description
                                sanitize_text_field($prod[3]),//category
                                sanitize_text_field($prod[4]),//sales_price
                                sanitize_text_field($prod[5]),//avex_price
                                sanitize_text_field($prod[6]),//stock
                                sanitize_url($prod[7]),//image
                                wp_kses_post($prod[8]),//images
                                sanitize_text_field($prod[9]),//brand
                                sanitize_text_field($prod[10]),//weight
                                sanitize_text_field($prod[11]),//ean
                                time()
                            ));
                            $wpdb->query($sql);
                            if(!$wpdb->query($sql) && $wpdb->last_error !== '')
                                return false;
                        }
                        else//only update
                        {
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set
                                title=%s,
                                description=%s,
                                category=%s,
                                sales_price=%s,
                                avex_price=%s,
                                stock=%d,
                                image=%s,
                                images=%s,
                                brand=%s,
                                weight=%s,
                                ean=%s,
                                mdate=%d
                                where
                                sku=%s
                            ",array(
                                sanitize_text_field($prod[1]),//title
                                wp_kses_post($prod[2]),//description
                                sanitize_text_field($prod[3]),//category
                                sanitize_text_field($prod[4]),//sales_price
                                sanitize_text_field($prod[5]),//avex_price
                                sanitize_text_field($prod[6]),//stock
                                sanitize_url($prod[7]),//image
                                wp_kses_post($prod[8]),//images
                                sanitize_text_field($prod[9]),//brand
                                sanitize_text_field($prod[10]),//weight
                                sanitize_text_field($prod[11]),//ean
                                time(),
                                sanitize_text_field($prod[0]),//sku
                            ));
                            $wpdb->query($sql);
                            if(!$wpdb->query($sql) && $wpdb->last_error !== '')
                                return false;
                        }
                        $cnt++;
                    }
                    $max_products=(int)$this->config->max_products;
                    if($max_products>0)
                        $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set imported=0 where 1 limit %d",array($max_products));
                    else
                        $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set imported=0 where 1");
                    $wpdb->query($sql);
                    $this->importFeedFromDb($publish_new_products, true, $price_override, true);
                }
            }
        }
        $this->setSetupValue("cron_feed_running","0");
        $this->setSetupStep(3);
        $this->saveLog(__("Ran the Feed Cron","dropshipping-romania-avex"));
        //add action to reset the cron start hour
        if(is_file(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php"))
        {
            $action_added=$this->addActionSchedulerTask("dropshipping_romania_avex_import_feed_from_cron_reschedule_hook",array(),"dropshipping_romania_avex");
            if($action_added)
                $this->saveLog(__("Added rescheduler Feed Cron task","dropshipping-romania-avex"));
            else
                $this->saveLog(__("Error in adding rescheduler Feed Cron task","dropshipping-romania-avex"));
        }
        return true;
    }
    public function rescheduleImportFeedFromCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_feed_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_feed_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=(int)$this->config->feed_sync_interval;
            $interval=$interval*24*60*60;
            $start_run=strtotime(date("Y-m-d 00:00")." + ".(int)$this->config->feed_sync_interval." day");
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_feed_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Rescheduled Feed Cron","dropshipping-romania-avex"));
            }
            else
                $this->saveLog(__("Error in rescheduling Feed Cron, please contact support","dropshipping-romania-avex"));
        }
        else
            $this->saveLog(__("Error in rescheduling Feed Cron","dropshipping-romania-avex"));
        return true;
    }
    public function rescheduleImportInvoicesFromCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_invoices_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_invoices_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=(int)$this->config->sync_invoices_interval;
            $interval=$interval*24*60*60;
            $start_run=strtotime(date("Y-m-d 02:00")." + ".(int)$this->config->sync_invoices_interval." day");
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_invoices_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Rescheduled Invoices Cron","dropshipping-romania-avex"));
            }
            else
                $this->saveLog(__("Error in rescheduling Invoices Cron, please contact support","dropshipping-romania-avex"));
        }
        else
            $this->saveLog(__("Error in rescheduling Invoices Cron","dropshipping-romania-avex"));
        return true;
    }
    public function startApiCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_api_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_api_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=(int)$this->config->api_sync_interval;
            $interval=$interval*60*60;
            $start_run=time();//+$interval;
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_api_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Set up the API Cron","dropshipping-romania-avex"));
                return array("status"=>"updated", "msg"=>__("Set up the API Cron","dropshipping-romania-avex"));
            }
            else
                return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
        }
        else
            return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
    }
    public function stopApiCron()
    {
        as_unschedule_all_actions( "dropshipping_romania_avex_import_api_cron_hook", array(), "dropshipping_romania_avex" );
        $this->saveLog(__("Stopped the API Cron","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("Stopped the API Cron","dropshipping-romania-avex"));
    }
    public function importApiFromCron()
    {
        global $wpdb;
        //$update_prices=false;
        //if($this->config->api_sync_price_override=='yes')//not used anymore
        //    $update_prices=true;
        $update_prices=false;//we do not use this anymore, prices can be updated only from feed
        set_time_limit(0);
        proc_nice(20);
        $this->saveLog(__("Started API product stock refresh from cron","dropshipping-romania-avex"));
        $result=$this->doAvexRequest($this->avex_api_products_url);
        $cnt=0;
        if(is_array($result) && isset($result['status']) && $result['status']=='ok')
        {
            if(isset($result['msg']->data))
            {
                $products=$result['msg']->data;
                if(is_array($products) && count($products)>0)
                {
                    foreach($products as $prod)
                    {
                        if($update_prices)
                        {
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set stock=%s, sales_price=%s, avex_price=%s where sku=%s"
                                ,array(sanitize_text_field($prod->amount),
                                sanitize_text_field($prod->list_price),
                                sanitize_text_field($prod->price),
                                sanitize_text_field($prod->product_code)
                            ));
                            $count=$wpdb->query($sql);
                            if((int)$count>0)//we have it in db and was updated
                            {
                                $sql=$wpdb->prepare("select post_id, stock, sales_price from ".$wpdb->prefix."dropshipping_romania_avex_products where sku=%s",array($prod->product_code));
                                $result=$wpdb->get_row($sql);
                                if(isset($result->post_id))
                                {
                                    update_post_meta($result->post_id, '_stock', sanitize_text_field($result->stock));
                                    update_post_meta($result->post_id, '_regular_price', sanitize_text_field($result->sales_price));
                                    $cnt++;
                                }
                            }
                        }
                        else
                        {
                            $sql=$wpdb->prepare("update ".$wpdb->prefix."dropshipping_romania_avex_products set stock=%s where sku=%s"
                                ,array(sanitize_text_field($prod->amount),
                                    sanitize_text_field($prod->product_code)
                                ));
                            $count=$wpdb->query($sql);
                            if((int)$count>0)//we have it in db and was updated
                            {
                                $sql=$wpdb->prepare("select post_id, stock from ".$wpdb->prefix."dropshipping_romania_avex_products where sku=%s",array(sanitize_text_field($prod->product_code)));
                                $result=$wpdb->get_row($sql);
                                if(isset($result->post_id))
                                {
                                    update_post_meta($result->post_id, '_stock', sanitize_text_field($result->stock));
                                    $cnt++;
                                }
                            }
                        }
                    }
                }
            }
        }
        else
        {
            $this->saveLog(__("Error in API product stock refresh from cron task, no valid product list received","dropshipping-romania-avex"));
            return false;
        }
        $this->saveLog(__("Finished API product stock refresh from cron, updated","dropshipping-romania-avex").": ".$cnt." ".__("products","dropshipping-romania-avex"));
        return true;
    }
    public function startOrdersCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_orders_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_orders_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=(int)$this->config->sync_orders_interval;
            if($interval==10 || $interval==15 || $interval==30)
                $interval=$interval*60;
            else
                $interval=$interval*60*60;
            $start_run=time();//+$interval;
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_orders_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Set up the Orders Cron","dropshipping-romania-avex"));
                return array("status"=>"updated", "msg"=>__("Set up the Orders Cron","dropshipping-romania-avex"));
            }
            else
                return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
        }
        else
            return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
    }
    public function uploadAvexAWB()
    {
        global $wpdb;
        $order_id=isset($_POST['order_id'])?(int)$_POST['order_id']:0;
        $file=isset($_FILES['file'])?$_FILES['file']:false;
        if($order_id>0)
        {
            if($file)
            {
                if($file['type']!="application/pdf")
                {
                    ?>
                    <strong style="color:red;">
                    <?php
                    esc_html_e("Please upload a PDF file");
                    ?>
                    </strong>
                    <?php
                    return;
                }
                $this->checkUploadFolders();
                $file_name=sanitize_file_name("awb_".$order_id.".pdf");
                $awb_folder=sanitize_file_name("awb");
                $main_folder=DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH.get_current_blog_id();
                
                $target_file=$main_folder."/".$awb_folder."/".$file_name;

                if (!function_exists('wp_handle_upload'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploadedfile = array(
                    'name'     => $file['name'],
                    'type'     => $file['type'],
                    'tmp_name' => $file['tmp_name'],
                    'error'    => $file['error'],
                    'size'     => $file['size']
                );
                $upload_overrides = array( 'test_form' => false );
                $movefile=wp_handle_upload($uploadedfile, $upload_overrides);
                if ( $movefile && !isset( $movefile['error'] ) && isset($movefile['file']) && file_exists($movefile['file']))
                {
                    copy($movefile['file'],$target_file);
                    unlink($movefile['file']);
                }
                else
                {
                    ?>
                    <strong style="color:red;">
                    <?php
                    esc_html_e("Error in uploading file");
                    ?>
                    </strong>
                    <?php
                    return;
                }

                $order = new \WC_Order( $order_id );
                if($order)
                {
                    $avex_items=array();
                    $items=$order->get_items();
                    if(is_array($items))
                    {
                        foreach($items as $item)
                        {
                            $product = wc_get_product($item->get_product_id());
                            $tmp=array("product_code"=>$product->get_sku(),"amount"=>$item->get_quantity());
                            $avex_items[]=$tmp;
                        }
                    }
                    if(count($items)==0)
                    {
                        ?>
                        <strong style="color:red;">
                        <?php
                        esc_html_e("Missing order items");
                        ?>
                        </strong>
                        <?php
                        return;
                    }
                    else
                    {
                        $fname=sanitize_text_field(trim($order->get_billing_first_name()));
                        $lnamename=sanitize_text_field(trim($order->get_billing_last_name()));
                        $customer_name=$fname." ".$lnamename;
                        if($fname=="" || $lnamename=="")
                        {
                            ?>
                            <strong style="color:red;">
                            <?php
                            esc_html_e("Missing full customer name");
                            ?>
                            </strong>
                            <?php
                            return;
                        }
                        $avex_order=array(
                            "order_id" => $order->get_id(),
                            "notes" => $order->get_customer_note(),
                            "recipient" => $customer_name,
                            "products" => $avex_items,
                            "awb" => array("file_content"=>base64_encode(file_get_contents($target_file)))
                        );
                        $result=$this->doAvexRequest($this->avex_api_new_order_url,"",$avex_order);
                        if(is_array($result) && isset($result['status']) && $result['status']=='error' && isset($result['msg']))
                        {
                            ?>
                            <strong style="color:red;">
                            <?php
                            echo esc_html($result['msg']);
                            ?>
                            </strong>
                            <?php
                            return;
                        }
                        if(is_array($result) && isset($result['status']) && $result['status']=="ok" && isset($result['msg']) && is_object($result['msg']) && isset($result['msg']->success) && $result['msg']->success==1 && isset($result['msg']->message) && isset($result['msg']->data) && is_object($result['msg']->data) && isset($result['msg']->data->order_id) && isset($result['msg']->data->status))
                        {
                            $avex_success_msg=$result['msg']->message;
                            $avex_order_id=$result['msg']->data->order_id;
                            $avex_order_status=$result['msg']->data->status;
                            update_post_meta($order->get_id(),"_avex_order_sent",1);
                            update_post_meta($order->get_id(),"_avex_order_id",sanitize_text_field($avex_order_id));
                            update_post_meta($order->get_id(),"_avex_order_status",sanitize_text_field($avex_order_status));
                            update_post_meta($order->get_id(),"_avex_order_awb_file",sanitize_text_field($target_file));
                            $target_file_url=str_replace(ABSPATH,get_site_url()."/",$target_file);
                            update_post_meta($order->get_id(),"_avex_order_awb_file_url",sanitize_text_field($target_file_url));
                            $this->saveLog($avex_order_id." ".__("order sent to Avex","dropshipping-romania-avex"));
                            echo "1";
                            return;
                        }
                    }
                }
                else
                {
                    ?>
                    <strong style="color:red;">
                    <?php
                    esc_html_e("Missing order id");
                    ?>
                    </strong>
                    <?php
                    return;
                }
            }
            else
            {
                ?>
                <strong style="color:red;">
                <?php
                esc_html_e("Missing AWB File");
                ?>
                </strong>
                <?php
                return;
            }
        }
        else
        {
            ?>
            <strong style="color:red;">
            <?php
            esc_html_e("Missing order id");
            ?>
            </strong>
            <?php
            return;
        }
    }
    public function getAvexOrderStatus($order_id=0)
    {
        $order_id=(int)$order_id;
        if($order_id>0)
        {
            $result=$this->doAvexRequest($this->avex_api_order_status_url."?order_id=".$order_id,"",array(),"GET");
            $avex_order_status="";
            if(is_array($result) && isset($result['status']) && $result['status']=="ok" && isset($result['msg']) && is_object($result['msg']) && isset($result['msg']->success) && $result['msg']->success==1 && isset($result['msg']->message) && isset($result['msg']->data) && is_object($result['msg']->data) && isset($result['msg']->data->order_id) && isset($result['msg']->data->status))
                return $result['msg']->data->status;
        }
        return "";
    }
    public function getOrderPageActions($order)
    {
        if($order)
        {
            $result=$this->doAvexRequest($this->avex_api_status_url);
            if(is_array($result) && isset($result['status']) && $result['status']=='ok')
            {
            $processing_status=sanitize_text_field($this->config->processing_wc_status);
            $processing_status=str_ireplace("wc-","",$processing_status);
            $cancelled_status=sanitize_text_field($this->config->cancelled_wc_status);
            $cancelled_status=str_ireplace("wc-","",$cancelled_status);
            if($order->get_status()==$processing_status)
            {
                $nonce = wp_create_nonce( 'dropshipping_romania_avex_upload_awb' );
                $data_js='
                jQuery(document).ready(function(){
                    jQuery("#avex_dropshipping_romania_send_awb_btn").attr("onClick","avexDropshippingRomaniaSendAwb()");
                });
                function avexDropshippingRomaniaSendAwb()
                {
                    jQuery("#avex_dropshipping_romania_send_awb_btn").val(\''.esc_js(__("loading","dropshipping-romania-avex")).'...\');
                    jQuery("#avex_dropshipping_romania_ajax_result").html("");
                    var fd = new FormData();
                    var file = jQuery(document).find("#avex_dropshipping_romania_awb");
                    var individual_file = file[0].files[0];
                    fd.append("file", individual_file);
                    fd.append("action", "dropshipping_romania_avex_upload_awb");
                    fd.append("order_id", \''.esc_js($order->get_id()).'\');
                    fd.append("security", \''.esc_js($nonce).'\');
                    jQuery.ajax({
                        type: "POST",
                        url: "'.esc_url(admin_url('admin-ajax.php?page=dropshipping-romania-avex')).'",
                        data: fd,
                        contentType: false,
                        processData: false,
                        success: function(response){
                            if(response!=1)
                            {
                                jQuery("#avex_dropshipping_romania_ajax_result").html(response);
                                jQuery("#avex_dropshipping_romania_send_awb_btn").val(\''.esc_js(__("Send the order to Avex","dropshipping-romania-avex")).'\');
                            }
                            else
                            {
                                jQuery("#avex_dropshipping_romania_send_awb_btn").val(\''.esc_js(__("Order sent to Avex, refreshing page","dropshipping-romania-avex")).'\');
                                jQuery("#avex_dropshipping_romania_send_awb_btn").attr("disabled","disabled");
                                jQuery("#avex_dropshipping_romania_awb").attr("disabled","disabled");
                                setTimeout(function(){
                                    location.reload();
                                }, 3000);
                            }
                        }
                    });
                }
                ';
                wp_register_script( 'dropshipping-romania-avex_js_upload_awb_inline_script_handler', '' );
                wp_enqueue_script( 'dropshipping-romania-avex_js_upload_awb_inline_script_handler' );
                wp_add_inline_script("dropshipping-romania-avex_js_upload_awb_inline_script_handler",$data_js);

                ?>
                <div style="margin-top: 20px;margin-bottom: 10px;">
                <div id="avex_dropshipping_romania_ajax_result" style="margin: 10px 10px 10px 0;"></div>
                <?php
                $avex_items=array();
                $img=DROPSHIPPING_ROMANIA_AVEX_PLUGIN_URL."admin/images/avex.png";
                $items=$order->get_items();
                if(is_array($items))
                {
                    foreach($items as $item)
                    {
                        $product = wc_get_product($item->get_product_id());
                        if($product)
                        {
                            $tmp=array("product_code"=>$product->get_sku(),"amount"=>$item->get_quantity());
                            $avex_items[]=$tmp;
                        }
                    }
                }
                if(count($items)==0)
                {
                    ?>
                    <strong style="color:red;">
                    <?php
                    esc_html_e("Missing order items");
                    ?>
                    </strong>
                    <?php
                }
                $fname=sanitize_text_field(trim($order->get_billing_first_name()));
                $lnamename=sanitize_text_field(trim($order->get_billing_last_name()));
                $customer_name=$fname." ".$lnamename;
                if($fname=="" || $lnamename=="")
                {
                    ?>
                    <strong style="color:red;">
                    <?php
                    esc_html_e("Missing full customer name");
                    ?>
                    </strong>
                    <?php
                }
                
                $order_sent=get_post_meta($order->get_id(),"_avex_order_sent",true);
                if($order_sent==1)
                {
                    $awb_file_url=get_post_meta($order->get_id(),"_avex_order_awb_file_url",true);
                    $awb_file=basename(get_post_meta($order->get_id(),"_avex_order_awb_file",true));
                    $avex_order_id=get_post_meta($order->get_id(),"_avex_order_id",true);
                    $avex_order_status=$this->getAvexOrderStatus($avex_order_id);
                    ?>
                    <div style="margin: 10px 10px 10px 0;"><img width="100" src="<?php echo esc_attr($img);?>"></div>
                    <div style="margin: 10px 10px 10px 0;">
                    <strong style="color:green;">
                    <?php
                    esc_html_e("Order was sent to Avex","dropshipping-romania-avex");
                    ?>
                    </strong>

                    <strong>
                        <?php esc_html_e("AWB file","dropshipping-romania-avex");?>
                    </strong>:
                    <a href="<?php echo esc_url($awb_file_url);?>" target="_blank"><?php echo esc_html($awb_file);?></a>
                    <strong>
                        <?php esc_html_e("Avex Order ID","dropshipping-romania-avex");?>
                    </strong>:
                    <a target="_blank" href="<?php echo esc_url($this->avex_account_order_page.$avex_order_id);?>"><?php echo esc_html($avex_order_id);?></a>
                    <strong>
                        <?php esc_html_e("Avex Order Status","dropshipping-romania-avex");?>
                    </strong>:
                    <?php
                    echo esc_html($avex_order_status);
                    ?>
                    </div>
                    <?php
                }
                else
                {
                ?>
                <div style="margin: 10px 10px 10px 0;"><img width="100" src="<?php echo esc_attr($img);?>"></div>
                <div style="float: left;margin: 10px 10px 10px 0;">
                <input style="padding-left:10px;" type="file" class="button wp-generate-pw hide-if-no-js" id="avex_dropshipping_romania_awb" />
                </div>
                <div style="float: left;margin: 10px 10px 10px 0;padding-top: 2px;">
                    <input onClick="" id="avex_dropshipping_romania_send_awb_btn" type="button" class="button-primary" value="<?php echo esc_attr(__("Send the order to Avex",'dropshipping-romania-avex'));?>"/>
                </div>
                <?php
                }
                ?>
                </div>
                <?php
                }
                else if($order->get_status()==$cancelled_status)//cancelled order page section
                {
                $avex_order_id=get_post_meta($order->get_id(),"_avex_order_id",true);
                if(!$avex_order_id)
                    return;
                $nonce = wp_create_nonce( 'dropshipping_romania_avex_cancel_order' );
                $data_js='
                jQuery(document).ready(function(){
                    jQuery("#avex_dropshipping_romania_cancel_order_btn").attr("onClick","avexDropshippingRomaniaCancelOrder()");
                });
                function avexDropshippingRomaniaCancelOrder()
                {
                    if(confirm(\''.esc_js(__("Are you sure you want to cancel the order","dropshipping-romania-avex")).'?\'))
                    {
                    jQuery("#avex_dropshipping_romania_cancel_order_btn").val(\''.esc_js(__("loading","dropshipping-romania-avex")).'...\');
                    jQuery("#avex_dropshipping_romania_ajax_result").html("");
                    var fd = new FormData();
                    fd.append("action", "dropshipping_romania_avex_cancel_order");
                    fd.append("order_id", \''.esc_js($order->get_id()).'\');
                    fd.append("security", \''.esc_js($nonce).'\');
                    jQuery.ajax({
                        type: "POST",
                        url: "'.esc_url(admin_url('admin-ajax.php?page=dropshipping-romania-avex')).'",
                        data: fd,
                        contentType: false,
                        processData: false,
                        success: function(response){
                            if(response!=1)
                            {
                                jQuery("#avex_dropshipping_romania_ajax_result").html(response);
                                jQuery("#avex_dropshipping_romania_cancel_order_btn").val(\''.esc_js(__("Cancel Avex Order","dropshipping-romania-avex")).'\');
                            }
                            else
                            {
                                jQuery("#avex_dropshipping_romania_cancel_order_btn").val(\''.esc_js(__("Cancelled Avex Order, refreshing page","dropshipping-romania-avex")).'\');
                                jQuery("#avex_dropshipping_romania_cancel_order_btn").attr("disabled","disabled");
                                setTimeout(function(){
                                    location.reload();
                                }, 3000);
                            }
                        }
                    });
                    }
                }
                ';
                wp_register_script( 'dropshipping-romania-avex_js_cancel_order_inline_script_handler', '' );
                wp_enqueue_script( 'dropshipping-romania-avex_js_cancel_order_inline_script_handler' );
                wp_add_inline_script("dropshipping-romania-avex_js_cancel_order_inline_script_handler",$data_js);
                $awb_file_url=get_post_meta($order->get_id(),"_avex_order_awb_file_url",true);
                $awb_file=basename(get_post_meta($order->get_id(),"_avex_order_awb_file",true));
                $avex_order_id=get_post_meta($order->get_id(),"_avex_order_id",true);
                $img=DROPSHIPPING_ROMANIA_AVEX_PLUGIN_URL."admin/images/avex.png";
                $avex_order_status=$this->getAvexOrderStatus($avex_order_id);
                ?>
                <div style="margin-top: 20px;margin-bottom: 10px;">
                <div id="avex_dropshipping_romania_ajax_result" style="margin: 10px 10px 10px 0;"></div>
                <div style="margin: 10px 10px 10px 0;"><img width="100" src="<?php echo esc_attr($img);?>"></div>
                <div style="margin: 10px 10px 10px 0;">
                <strong style="color:green;">
                <?php
                esc_html_e("Order was sent to Avex","dropshipping-romania-avex");
                ?>
                </strong>

                <strong>
                    <?php esc_html_e("AWB file","dropshipping-romania-avex");?>
                </strong>:
                <a href="<?php echo esc_url($awb_file_url);?>" target="_blank"><?php echo esc_html($awb_file);?></a>
                <strong>
                    <?php esc_html_e("Avex Order ID","dropshipping-romania-avex");?>
                </strong>:
                <a target="_blank" href="<?php echo esc_url($this->avex_account_order_page.$avex_order_id);?>"><?php echo esc_html($avex_order_id);?></a>
                <strong>
                    <?php esc_html_e("Avex Order Status","dropshipping-romania-avex");?>
                </strong>:
                <?php
                echo esc_html($avex_order_status);
                ?>
                </div>
                <div style="margin: 10px 10px 10px 0;padding-top: 2px;">
                    <input onClick="" id="avex_dropshipping_romania_cancel_order_btn" type="button" class="button-secondary" value="<?php echo esc_attr(__("Cancel Avex Order",'dropshipping-romania-avex'));?>"/>
                </div>
                </div>
                <?php
                }
                else
                {
                    $avex_order_id=get_post_meta($order->get_id(),"_avex_order_id",true);
                    $img=DROPSHIPPING_ROMANIA_AVEX_PLUGIN_URL."admin/images/avex.png";
                    $avex_order_status=$this->getAvexOrderStatus($avex_order_id);
                    if((int)$avex_order_id>0)
                    {
                    ?>
                    <div style="margin-top: 20px;margin-bottom: 10px;">
                    <div id="avex_dropshipping_romania_ajax_result" style="margin: 10px 10px 10px 0;"></div>
                    <div style="margin: 10px 10px 10px 0;"><img width="100" src="<?php echo esc_attr($img);?>"></div>
                    <div style="margin: 10px 10px 10px 0;">
                    <strong>
                        <?php esc_html_e("Avex Order ID","dropshipping-romania-avex");?>
                    </strong>:
                    <a target="_blank" href="<?php echo esc_url($this->avex_account_order_page.$avex_order_id);?>"><?php echo esc_html($avex_order_id);?></a>
                    <strong>
                        <?php esc_html_e("Avex Order Status","dropshipping-romania-avex");?>
                    </strong>:
                    <?php
                    echo esc_html($avex_order_status);
                    ?>
                    </div>
                    </div>
                    <?php
                    }
                }
            }
            else
            {
                ?>
                <strong style="color:red;">
                <?php
                esc_html_e("Cannot access the Avex API");
                ?>
                </strong>
                <?php
            }
        }
    }
    public function cancelAvexOrder()
    {
        $order_id=isset($_POST['order_id'])?(int)$_POST['order_id']:0;
        if($order_id>0)
        {
            $avex_order_id=get_post_meta($order_id,"_avex_order_id",true);
            $avex_order=array(
                "order_id" => $avex_order_id
            );
            $result=$this->doAvexRequest($this->avex_api_cancel_order_url,"",$avex_order);
            if(is_array($result) && isset($result['status']) && $result['status']=='error' && isset($result['msg']))
            {
                ?>
                <strong style="color:red;">
                <?php
                echo esc_html($result['msg']);
                ?>
                </strong>
                <?php
                return;
            }
            if(is_array($result) && isset($result['success']) && $result['success']==false && isset($result['message']))
            {
                ?>
                <strong style="color:red;">
                <?php
                echo esc_html($result['message']);
                ?>
                </strong>
                <?php
                return;
            }
            if(is_array($result) && isset($result['status']) && $result['status']=="ok" && isset($result['msg']) && is_object($result['msg']) && isset($result['msg']->success) && $result['msg']->success==1 && isset($result['msg']->message) && isset($result['msg']->data) && is_object($result['msg']->data) && isset($result['msg']->data->order_id) && isset($result['msg']->data->status))
            {
                $awb_file=get_post_meta($order_id,"_avex_order_awb_file",true);
                if(is_file($awb_file))
                    unlink($awb_file);
                delete_post_meta($order_id,"_avex_order_sent");
                delete_post_meta($order_id,"_avex_order_id");
                delete_post_meta($order_id,"_avex_order_status");
                delete_post_meta($order_id,"_avex_order_awb_file");
                delete_post_meta($order_id,"_avex_order_awb_file_url");
                $this->saveLog($avex_order_id." ".__(" cancelled Avex order","dropshipping-romania-avex"));
                echo "1";
                return;
            }
        }
        else
        {
            ?>
            <strong style="color:red;">
            <?php
            esc_html_e("Missing order id");
            ?>
            </strong>
            <?php
            return;
        }
    }
    public function stopOrdersCron()
    {
        as_unschedule_all_actions( "dropshipping_romania_avex_import_orders_cron_hook", array(), "dropshipping_romania_avex" );
        $this->saveLog(__("Stopped the Orders Cron","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("Stopped the Orders Cron","dropshipping-romania-avex"));
    }
    public function importOrdersFromCron()
    {
        global $wpdb;
        set_time_limit(0);
        proc_nice(20);
        $sync_orders_newer_than=(int)$this->config->sync_orders_newer_than;
        $stamp=strtotime(date("Y-m-d 00:00:00")."-".$sync_orders_newer_than." days");
        $sql=$wpdb->prepare("select p.ID, m.meta_value as avex_order_id from ".$wpdb->prefix."postmeta m
            inner join ".$wpdb->prefix."posts p on p.ID=m.post_id where
            m.meta_key='_avex_order_id' and
            m.meta_value>0 and
            p.post_modified_gmt>=%s
        ",array($stamp));
        $results=$wpdb->get_results($sql);
        if(is_array($results) && count($results)>0)
        {
            $completed_status=sanitize_text_field($this->config->completed_wc_status);
            $completed_status=str_ireplace("wc-","",$completed_status);
            $cancelled_status=sanitize_text_field($this->config->cancelled_wc_status);
            $cancelled_status=str_ireplace("wc-","",$cancelled_status);
            foreach($results as $result)
            {
                $avex_order_status=$this->getAvexOrderStatus($result->avex_order_id);
                if($avex_order_status!="")
                {
                    $order = new \WC_Order( $result->ID );
                    if($order)
                    {
                        $wc_order_status=$order->get_status();
                        if(in_array(strtolower($avex_order_status), $this->avex_completed_status) && $wc_order_status!=$completed_status)//avex completed the order
                        {
                            //update the order to completed
                            $order->update_status($completed_status);
                            $this->saveLog($result->ID." ".__("order status was updated by the orders cron to","dropshipping-romania-avex")." ".strtoupper($completed_status));
                            update_post_meta($result->ID,"_avex_order_status",sanitize_text_field($avex_order_status));
                            //send the notification email
                            $this->sendAvexNotificationEmail($result->ID,$completed_status);
                        }
                        if(in_array(strtolower($avex_order_status), $this->avex_cancelled_status) && $wc_order_status!=$cancelled_status)//avex cancelled the order
                        {
                            //update the order to cancelled
                            $order->update_status($cancelled_status);
                            $this->saveLog($result->ID." ".__("order status was updated by the orders cron to","dropshipping-romania-avex")." ".strtoupper($cancelled_status));
                            $awb_file=get_post_meta($result->ID,"_avex_order_awb_file",true);
                            if(is_file($awb_file))
                                unlink($awb_file);
                            delete_post_meta($result->ID,"_avex_order_sent");
                            delete_post_meta($result->ID,"_avex_order_id");
                            delete_post_meta($result->ID,"_avex_order_status");
                            delete_post_meta($result->ID,"_avex_order_awb_file");
                            delete_post_meta($result->ID,"_avex_order_awb_file_url");
                            //send the notification email
                            $this->sendAvexNotificationEmail($result->ID,$cancelled_status);
                        }
                    }
                }
            }
        }
        $this->saveLog(__("Ran the Orders Cron","dropshipping-romania-avex"));
        return true;
    }
    public function sendAvexNotificationEmail($order_id=0, $status="")
    {
        if($this->config->notifications_enabled=="yes" && $this->config->notifications_email!="" && is_email($this->config->notifications_email) && (int)$order_id>0 && $status!="")
        {
            $subject=sanitize_text_field($order_id)." ".__("Order status changed from cron to","dropshipping-romania-avex")." ".sanitize_text_field(strtoupper($status));
            $body=esc_html(__("Order status changed from cron to","dropshipping-romania-avex")).": ".sanitize_text_field(strtoupper($status))."\n\n";
            $body.=esc_html(__("Order ID","dropshipping-romania-avex").": ".sanitize_text_field($order_id)."\n\n");
            if(!wp_mail(sanitize_email($this->config->notifications_email),$subject,$body))
                $this->saveLog("Error in sending notification email from orders cron","dropshipping-romania-avex");
        }
    }
    public function getInvoices()
    {
        global $wpdb;

        $draw=isset($_POST['draw'])?(int)$_POST['draw']:0;
        $start=isset($_POST['start'])?(int)$_POST['start']:0;
        $length=isset($_POST['length'])?(int)$_POST['length']:10;
        $search_arr=isset($_POST['search'])?$_POST['search']:array();
        $search=isset($search_arr['value'])?sanitize_text_field($search_arr['value']):"";
        $start_date=isset($_POST['start_date'])?sanitize_text_field($_POST['start_date']):"";
        $end_date=isset($_POST['end_date'])?sanitize_text_field($_POST['end_date']):"";
        $order_arr=isset($_POST['order'])?$_POST['order']:array();
        $invoices=new stdClass;
        $invoices->invoices=array();
        $invoices->total_invoicess=0;
        $invoices->total_filtered_invoices=0;
        $invoices->draw=$draw;

        if($start_date!="")
            $start_date=strtotime(date("Y-m-d 00:00:00",strtotime($start_date)));
        else
            $start_date=0;
        if($end_date!="")
            $end_date=strtotime(date("Y-m-d 23:59:59",strtotime($end_date)));
        else
            $end_date=strtotime(date("Y-m-d")." +1 day");

        $order_by=array();
        $order_by[]="i.mdate desc";
        if(count($order_arr)>0)
        {
            $sortables=array();
            foreach($order_arr as $order)
                $sortables[$order['column']]=$order['dir'];
            $order_by=array();
            foreach($sortables as $col => $order)
            {
                if($col==0 && $order!="")
                    $order_by[]="i.mdate ".(($order=='asc')?'asc':'desc');
                if($col==1 && $order!="")
                    $order_by[]="i.`post_id` ".(($order=='asc')?'asc':'desc');
                if($col==2 && $order!="")
                    $order_by[]="i.invoice_id ".(($order=='asc')?'asc':'desc');
                if($col==3 && $order!="")
                    $order_by[]="i.order_id ".(($order=='asc')?'asc':'desc');
                if($col==4 && $order!="")
                    $order_by[]="i.order_total ".(($order=='asc')?'asc':'desc');
            }
        }
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_invoices 
        where 1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total))
        {
            $invoices->total_invoices=$result->total;
            $invoices->total_filtered_invoices=$result->total;
        }

        if($search!="")
        {
            $sql=$wpdb->prepare("select count(i.post_id) as total from ".$wpdb->prefix."dropshipping_romania_avex_invoices i
            where
            i.mdate>=%s and
            i.mdate<=%s and
            (
                i.`post_id` like %s or
                i.`invoice_id` like %s or
                i.`order_id` like %s or
                i.`order_total` like %s
            )",array($start_date,$end_date,"%".$search."%","%".$search."%","%".$search."%","%".$search."%"));
            $result=$wpdb->get_row($sql);
            if(isset($result->total))
                $invoices->total_filtered_invoices=$result->total;

            $sql=$wpdb->prepare("select i.* from ".$wpdb->prefix."dropshipping_romania_avex_invoices i
            where
            i.mdate>=%s and
            i.mdate<=%s and
            (
                i.`post_id` like %s or
                i.`invoice_id` like %s or
                i.`order_id` like %s or
                i.`order_total` like %s
            )
            order by ".implode(",",array_map('esc_sql',$order_by))."
            limit %d,%d",array($start_date,$end_date,"%".$search."%","%".$search."%","%".$search."%","%".$search."%",$start,$length));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
                $invoices->invoices=$results;
        }
        else
        {
            if($start_date!="" || $end_date!="")
            {
                $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_invoices i
                where
                i.mdate>=%s and
                i.mdate<=%s",array($start_date,$end_date));
                $result=$wpdb->get_row($sql);
                if(isset($result->total))
                    $invoices->total_filtered_invoices=$result->total;
            }
            $sql=$wpdb->prepare("select i.* from ".$wpdb->prefix."dropshipping_romania_avex_invoices i
            where
            i.mdate>=%s and
            i.mdate<=%s
            order by ".implode(",",array_map('esc_sql',$order_by))."
            limit %d,%d",array($start_date,$end_date,$start,$length));
            $results=$wpdb->get_results($sql);
            if(is_array($results))
                $invoices->invoices=$results;
        }
        return $invoices;
    }
    public function getInvoicesAjax()
    {
        $data=array('data'=>array());
        $invoices=$this->getInvoices();

        if(is_array($invoices->invoices))
        {
            $uploads_base_url=wp_get_upload_dir()['baseurl'];
            foreach($invoices->invoices as $invoice)
            {
                $invoice_url=$uploads_base_url."/dropshipping-romania-avex/".get_current_blog_id().$invoice->invoice;
                $invoice_url=$invoice->link;
                $result=array();
                $result[]=esc_html(date("d/m/Y H:i",$invoice->mdate));
                $result[]='<a target="_blank" href="'.esc_url(admin_url('admin.php?page=wc-orders&action=edit&id='.$invoice->post_id)).'">'.esc_html($invoice->post_id).'</a>';
                $result[]='<a target="_blank" href="'.esc_url($invoice_url).'">'.esc_html($invoice->invoice_id).'</a>';
                $result[]='<a target="_blank" href="'.esc_url($this->avex_account_order_page.$invoice->order_id).'">'.esc_html($invoice->order_id).'</a>';
                $result[]=esc_html($invoice->order_total);
                $data['data'][]=$result;
            }
        }
        if(count($data))
        {
            $data['draw']=esc_html($invoices->draw);
            $data['recordsTotal']=esc_html($invoices->total_invoices);
            $data['recordsFiltered']=esc_html($invoices->total_filtered_invoices);
            echo json_encode($data);
        }
    }
    public function startInvoicesCron()
    {
        if ( ! class_exists( 'ActionScheduler_Versions', false ) )
            require_once(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php");
        as_unschedule_all_actions( "dropshipping_romania_avex_import_invoices_cron_hook", array(), "dropshipping_romania_avex" );
        if(! as_has_scheduled_action("dropshipping_romania_avex_import_invoices_cron_hook",array(),"dropshipping_romania_avex"))
        {
            $interval=$this->config->sync_invoices_interval*24*60*60;
            $start_run=time();
            $action_added=true;
            $action_id=as_schedule_recurring_action( $start_run, $interval, "dropshipping_romania_avex_import_invoices_cron_hook",array(),"dropshipping_romania_avex", true);
            if(!$action_id)
                $action_added=false;

            if($action_added)
            {
                $this->saveLog(__("Set up the Invoices Cron","dropshipping-romania-avex"));
                return array("status"=>"updated", "msg"=>__("Set up the Invoices Cron","dropshipping-romania-avex"));
            }
            else
                return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
        }
        else
            return array("status"=>"error", "msg"=>__("Action Scheduler did not work, please contact support","dropshipping-romania-avex"));
    }
    public function stopInvoicesCron()
    {
        as_unschedule_all_actions( "dropshipping_romania_avex_import_invoices_cron_hook", array(), "dropshipping_romania_avex" );
        $this->saveLog(__("Stopped the Invoices Cron","dropshipping-romania-avex"));
        return array("status"=>"updated", "msg"=>__("Stopped the Invoices Cron","dropshipping-romania-avex"));
    }
    public function getPostIdForInvoiceId($invoice_id=0)
    {
        global $wpdb;
        $invoice_id=(int)$invoice_id;
        if($invoice_id>0)
        {
            $sql=$wpdb->prepare("select post_id from ".$wpdb->prefix."dropshipping_romania_avex_invoices where invoice_id=%d",array($invoice_id));
            $result=$wpdb->get_row($sql);
            if(isset($result->post_id))
                return $result->post_id;
        }
        return 0;
    }
    public function getPostIdForOrderId($order_id=0)
    {
        global $wpdb;
        $order_id=(int)$order_id;
        if($order_id>0)
        {
            $sql=$wpdb->prepare("select p.ID as post_id from ".$wpdb->prefix."postmeta m
                inner join ".$wpdb->prefix."posts p on p.ID=m.post_id
                where
                m.meta_key='_avex_order_id' and
                m.meta_value=%d
            ",array($order_id));
            $result=$wpdb->get_row($sql);
            if(isset($result->post_id))
                return $result->post_id;
        }
        return 0;
    }
    public function importInvoicesFromCron()
    {
        global $wpdb;

        set_time_limit(0);
        proc_nice(20);
        $args = array(
            'role'    => 'administrator',
            'number' => 1
        );
        $users = get_users( $args );
        if(is_array($users) && count($users)==1)
            wp_set_current_user($users[0]->ID);//we need capabilities in cron for uploading html file (this is the way the invoices company provides invoices)

        $result=$this->doAvexRequest($this->avex_api_invoices_url);
        if(is_array($result) && isset($result['status']) && $result['status']=="ok" && isset($result['msg']) && is_object($result['msg']) && isset($result['msg']->success) && $result['msg']->success==1 && isset($result['msg']->message) && isset($result['msg']->data) && is_object($result['msg']->data) && isset($result['msg']->data->invoices) && is_array($result['msg']->data->invoices) && count($result['msg']->data->invoices)>0)
        {
            $invoices=$result['msg']->data->invoices;
            $this->checkUploadFolders();
            foreach($invoices as $invoice)
            {
                if((int)$invoice->invoice_id==0)
                    continue;
                $link="";
                $invoice_url="";
                $existing_post_id=(int)$this->getPostIdForInvoiceId($invoice->invoice_id);
                if($existing_post_id>0)//not overriding invoices, no point
                    continue;
                $post_id=(int)$this->getPostIdForOrderId($invoice->order_id);
                /*
                if($post_id==0)//if orders are deleted, import them anyway
                {
                    $this->saveLog(__("Missing WC Order for Invoice id","dropshipping-romania-avex").": ".(int)$invoice->invoice_id);
                    continue;
                }
                */
                $result=$this->doAvexRequest($this->avex_api_invoice_url."?invoice_id=".(int)$invoice->invoice_id);
                if(is_array($result) && isset($result['status']) && $result['status']=="ok" && isset($result['msg']) && is_object($result['msg']) && isset($result['msg']->success) && $result['msg']->success==1 && isset($result['msg']->message) && isset($result['msg']->data) && is_object($result['msg']->data) && isset($result['msg']->data->invoice) && is_object($result['msg']->data->invoice) && isset($result['msg']->data->invoice->link))
                {
                    $link=$result['msg']->data->invoice->link;
                }
                else
                {
                    $this->saveLog(__("Missing Invoice Link for","dropshipping-romania-avex").": ".(int)$invoice->invoice_id);
                    continue;
                }
                if($link!="")
                {
                    $response=wp_remote_get(esc_url($link));
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        $this->saveLog($error_message.": ".(int)$invoice->invoice_id);
                    }
                    else
                    {
                        $file_name=sanitize_file_name("invoice_".(int)$invoice->invoice_id.".html");
                        $invoices_folder=sanitize_file_name("invoices");
                        $main_folder=DROPSHIPPING_ROMANIA_AVEX_UPLOADS_PATH.get_current_blog_id();
                        
                        $target_file=$main_folder."/".$invoices_folder."/".$file_name;
                        $body = wp_remote_retrieve_body( $response );
                        if($body!="")
                        {
                            if (!function_exists('wp_upload_bits'))
                                require_once(ABSPATH . 'wp-admin/includes/file.php');
                            $movefile=wp_upload_bits( sanitize_file_name(basename($target_file)), null, $body );

                            if ( $movefile && isset($movefile['file']) && file_exists($movefile['file']))
                            {
                                copy($movefile['file'],$target_file);
                                unlink($movefile['file']);
                                $invoice_url=str_replace($main_folder,"",$target_file);
                            }
                            else
                            {
                                $this->saveLog(__("Error in saving file for","dropshipping-romania-avex").": ".(int)$invoice->invoice_id);//.print_r($movefile,true)
                                continue;
                            }
                        }
                        else
                        {
                            $this->saveLog(__("No Invoice file content for","dropshipping-romania-avex").": ".(int)$invoice->invoice_id);
                            continue;
                        }
                    }
                }
                else
                {
                    $this->saveLog(__("Could not retreive the invoice link for","dropshipping-romania-avex").": ".(int)$invoice->invoice_id);
                    continue;
                }
                $sql=$wpdb->prepare("insert into ".$wpdb->prefix."dropshipping_romania_avex_invoices set
                    post_id=%d,
                    invoice_id=%d,
                    order_id=%d,
                    order_total=%s,
                    link=%s,
                    invoice=%s,
                    mdate=%d
                    on duplicate key update
                    link=%s
                ",array(
                    $post_id,
                    sanitize_text_field($invoice->invoice_id),
                    sanitize_text_field($invoice->order_id),
                    sanitize_text_field($invoice->order_total),
                    sanitize_text_field($link),
                    sanitize_text_field($invoice_url),
                    time(),
                    sanitize_text_field($link)
                ));
                $wpdb->query($sql);
            }
        }
        if(is_file(WC()->plugin_path()."/packages/action-scheduler/action-scheduler.php"))
        {
            $action_added=$this->addActionSchedulerTask("dropshipping_romania_avex_import_invoices_from_cron_reschedule_hook",array(),"dropshipping_romania_avex");
            if($action_added)
                $this->saveLog(__("Added rescheduler Invoices Cron task","dropshipping-romania-avex"));
            else
                $this->saveLog(__("Error in adding rescheduler Invoices Cron task","dropshipping-romania-avex"));
        }
        $this->saveLog(__("Ran the Invoices Cron","dropshipping-romania-avex"));
        return true;
    }
    public function checkForExistingProducts()
    {
        global $wpdb;
        //check for existing products
        $sql=$wpdb->prepare("select count(*) as total from ".$wpdb->prefix."dropshipping_romania_avex_products where 1");
        $result=$wpdb->get_row($sql);
        if(isset($result->total) && $result->total==0)
        {
            $sql=$wpdb->prepare("select 
                p.ID as post_id, p.post_title as title, p.post_content as description,
                m1.meta_value as sku,
                m2.meta_value as sales_price,
                m3.meta_value as avex_price,
                m4.meta_value as stock,
                m5.meta_value as brand,
                m6.meta_value as ean,
                m7.meta_value as weight,
                m8.meta_value as images,
                m9.meta_value as image,
                m10.meta_value as category
                from ".$wpdb->prefix."posts p
                inner join ".$wpdb->prefix."postmeta m on m.post_id=p.ID and meta_key='_avex_imported_product' and meta_value=1
                left join ".$wpdb->prefix."postmeta m1 on m1.post_id=p.ID and m1.meta_key='_sku'
                left join ".$wpdb->prefix."postmeta m2 on m2.post_id=p.ID and m2.meta_key='_avex_sales_price'
                left join ".$wpdb->prefix."postmeta m3 on m3.post_id=p.ID and m3.meta_key='_avex_avex_price'
                left join ".$wpdb->prefix."postmeta m4 on m4.post_id=p.ID and m4.meta_key='_stock'
                left join ".$wpdb->prefix."postmeta m5 on m5.post_id=p.ID and m5.meta_key='_avex_brand'
                left join ".$wpdb->prefix."postmeta m6 on m6.post_id=p.ID and m6.meta_key='_avex_ean'
                left join ".$wpdb->prefix."postmeta m7 on m7.post_id=p.ID and m7.meta_key='_weight'
                left join ".$wpdb->prefix."postmeta m8 on m8.post_id=p.ID and m8.meta_key='_avex_images'
                left join ".$wpdb->prefix."postmeta m9 on m9.post_id=p.ID and m9.meta_key='_avex_image'
                left join ".$wpdb->prefix."postmeta m10 on m10.post_id=p.ID and m10.meta_key='_avex_category'
                where p.post_type='product'
             ");
            $results=$wpdb->get_results($sql);
            if(is_array($results) && count($results)>0)
            {
                update_option("avex_the_dropshipping_romania_avex_activation_is_done","have_products");
                foreach($results as $row)
                {
                    $sql=$wpdb->prepare("insert into ".$wpdb->prefix."dropshipping_romania_avex_products set
                        post_id=%d,
                        imported=1,
                        sku=%s,
                        title=%s,
                        description=%s,
                        category=%s,
                        sales_price=%s,
                        avex_price=%s,
                        stock=%d,
                        image=%s,
                        images=%s,
                        brand=%s,
                        weight=%s,
                        ean=%s,
                        mdate=%d
                        on duplicate key update mdate=%s
                    ",array(
                        sanitize_text_field($row->post_id),//post_id
                        sanitize_text_field($row->sku),//sku
                        sanitize_text_field($row->title),//title
                        wp_kses_post($row->description),//description
                        sanitize_text_field($row->category),//category
                        sanitize_text_field($row->sales_price),//sales_price
                        sanitize_text_field($row->avex_price),//avex_price
                        sanitize_text_field($row->stock),//stock
                        sanitize_url($row->image),//image
                        wp_kses_post($row->images),//images
                        sanitize_text_field($row->brand),//brand
                        sanitize_text_field($row->weight),//weight
                        sanitize_text_field($row->ean),//ean
                        time(),
                        time()
                    ));
                    $wpdb->query($sql);
                }
            }
        }
    }
    public function importExistingProductsBySku()
    {
        global $wpdb;
        set_time_limit(0);
        proc_nice(20);
        $request=$this->getProductsFeed();
        if(isset($request['status']) && $request['status']=='ok')
        {
            $result=$request['msg'];
            if($result!="")
            {
                $products=array_map("str_getcsv", explode("\n", $result));
                if(is_array($products) && count($products)>1)
                {
                    $cnt=0;
                    $first=0;
                    foreach($products as $prod)
                    {
                        if($first==0)
                        {
                            $first=1;
                            continue;
                        }
                        if($prod[0]=='')
                            continue;
                        $sku=trim($prod[0]);//sku
                        if($sku=='')
                            continue;
                        $sql=$wpdb->prepare("select 
                            p.ID as post_id, 
                            if(m.meta_value IS NULL,0,m.meta_value) as avex_imported_product,
                            m1.meta_value as sku
                            from ".$wpdb->prefix."posts p
                            left join ".$wpdb->prefix."postmeta m on m.post_id=p.ID and meta_key='_avex_imported_product'
                            left join ".$wpdb->prefix."postmeta m1 on m1.post_id=p.ID and m1.meta_key='_sku'
                            where p.post_type='product' and m1.meta_value=%s
                         ",array(sanitize_text_field($sku)));
                        $result=$wpdb->get_row($sql);
                        if(isset($result->post_id) && $result->post_id>0 && isset($result->avex_imported_product) && (int)$result->avex_imported_product==0)
                        {
                            $post_id=(int)$result->post_id;
                            update_post_meta($post_id,"_avex_sales_price",sanitize_text_field($prod[4]));
                            update_post_meta($post_id,"_avex_avex_price",sanitize_text_field($prod[4]));
                            update_post_meta($post_id,"_avex_brand",sanitize_text_field($prod[9]));
                            update_post_meta($post_id,"_avex_ean",sanitize_text_field($prod[11]));
                            update_post_meta($post_id,"_avex_image",sanitize_text_field($prod[7]));
                            update_post_meta($post_id,"_avex_images",wp_kses_post($prod[8]));
                            update_post_meta($post_id,"_avex_category",sanitize_text_field($prod[3]));
                            update_post_meta($post_id,"_avex_imported_product","1");
                        }
                    }
                }
            }
        }
        $this->checkForExistingProducts();
        $this->setSetupValue("setup_step","0");
    }
    public function checkIfImportExistingProductsBySkuAvailable()
    {
        global $wpdb;
        set_time_limit(0);
        proc_nice(20);
        $request=$this->getProductsFeed();
        if(isset($request['status']) && $request['status']=='ok')
        {
            $result=$request['msg'];
            if($result!="")
            {
                $products=array_map("str_getcsv", explode("\n", $result));
                if(is_array($products) && count($products)>1)
                {
                    $sql=$wpdb->prepare("select 
                        count(*) as total
                        from ".$wpdb->prefix."postmeta 
                        where meta_key='_avex_imported_product' and meta_value=1
                     ");
                    $result=$wpdb->get_row($sql);
                    if(isset($result->total) && (int)$result->total==0)
                    {
                        $action_added=$this->addActionSchedulerTask("dropshipping_romania_avex_prepare_products_for_import_hook",array(),"dropshipping_romania_avex");
                        if($action_added)
                            $action_added=true;

                        if($action_added || 1==1)
                        {
                            $this->setSetupValue("setup_step","-1");
                            $this->saveLog(__("Scheduled WC existing Products prepare for update task","dropshipping-romania-avex"));
                        }
                        else
                            $this->saveLog(__("Error in scheduling the WC existing Products prepare for update task","dropshipping-romania-avex"));
                    }
                }
            }
        }
    }
    public function getPrepareProductsStatusAjax()
    {
        $setup_step=$this->getSetupValue("setup_step");
        if($setup_step==0)
        {
            echo "1";
            return;
        }
        ?>
        <td>
            <strong><?php esc_html_e("The Prepare Avex Products task is running, please wait","dropshipping-romania-avex");?></strong>
        </td>
        <?php
    }
}