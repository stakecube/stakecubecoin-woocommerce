<?php
/*
    Plugin Name: WooCommerce StakeCubeCoin Payment Gateway
    Description: Payment Gateway with StakeCubeCoin
    Author: StakeCube
    Author URI: https://stakecube.net
    Plugin URI: https://stakecube.net
*/    
define('TRANSACTION_URL',"http://95.179.165.19/api/getrawtransaction");

function create_table()
{      
    global $wpdb; 
    $db_table_name = $wpdb->prefix . 'stakecubecoin_transaction_ids';  // table name
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $db_table_name (
                id int(11) NOT NULL auto_increment,
                transaction_id text ,
                order_id varchar(191),
                order_status varchar(10) DEFAULT NULL,
                order_time varchar(32) DEFAULT NULL,
                confirmation_no varchar(32) DEFAULT NULL,
                PRIMARY KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    add_option( 'test_db_version', $test_db_version );
} 


$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(in_array('woocommerce/woocommerce.php', $active_plugins)) {     
    register_activation_hook( __FILE__, 'create_table' );

    add_filter('woocommerce_payment_gateways', 'add_stakecubecoin_gateway');


    function add_stakecubecoin_gateway( $gateways ){
        $gateways[] = 'WC_SCC';
        return $gateways; 
    }

    add_action('plugins_loaded', 'init_other_payment_gateway');
    function init_other_payment_gateway(){
        require 'WC_SCC.php';
    }  
}
else
{
    echo "<script>alert('Install WooCommerce First')</script>";
    exit;
}



function add_jscript() {
    $rate = SCCConversion('1',get_woocommerce_currency());

?>

<input type="hidden" name="currency_code" value="">
<script type="text/javascript">    
    jQuery( document ).ajaxComplete(function() {
        var total_amount = jQuery(".order-total span").text();
        var currency_code = "<?php echo get_woocommerce_currency();?>";
        var lower_currency_code = currency_code.toLowerCase();
        var exchange_rate = "<?php echo $rate; ?>";
        //console.log(total_amount);
        jQuery("p#stakecubecoin-rate").html("SCC Rate: "+exchange_rate+"/"+currency_code);
    });
</script>
  
<?php 
}
 
add_action( 'woocommerce_after_checkout_form', 'add_jscript');
add_action("woocommerce_view-order", "xlwcty_add_custom_action_view-order", 20);
add_action("woocommerce_thankyou", "xlwcty_add_custom_action_thankyou", 20);

if(!function_exists('xlwcty_add_custom_action_thankyou')) {
    function xlwcty_add_custom_action_thankyou($order_id) {
        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            if ($order instanceof WC_Order) {
                $order_id = $order->get_id(); // order id
                $order_key = $order->get_order_key(); // order key
                $order_total = $order->get_total(); // order total
                $order_currency = $order->get_currency(); // order currency
                $order_payment_method = $order->get_payment_method(); // order payment method
                $order_shipping_country = $order->get_shipping_country(); // order shipping country
                $order_billing_country = $order->get_billing_country(); // order billing country
                $order_status = $order->get_status(); // order status
                $order_total = SCCConversion($order_total,get_woocommerce_currency());
                $wc_scc = new WC_SCC;
     
?>
                <script type="text/javascript">
                    var config_text = "<?php echo $wc_scc->description; ?>";
                    var order_id = "<?php echo $order_id; ?>";   
                    var server_address = "<?php echo $wc_scc->server_address; ?>";
                    var max_time_limit = "<?php echo $wc_scc->max_time_limit; ?>";
                    var confirmation_no = "<?php echo $wc_scc->confirmation_no; ?>";
               
                    var site_url = "<?php echo get_site_url(); ?>";
                    var marginal_error = "<?php echo $wc_scc->marginal_error; ?>";
                    var order_status = "<?php echo $order_status ?>";
                    if(order_status == 'pending') {
                        jQuery('h1.entry-title').html("Checkout");                        
                        jQuery("section.woocommerce-customer-details,section.woocommerce-order-details").remove();
                    }
 
                    var total_amount = <?php echo $order_total; ?>;
                    var total_coins = <?php echo $order_total; ?>;

                    //refresh page add trigger
                    setTimeout(function() {
                        jQuery('.refresh_transaction').trigger('click');
                    },10);          
     
                    jQuery(function() {

                        jQuery("label.get_scc").html(total_amount);
                        jQuery("ul.woocommerce-order-overview span.woocommerce-Price-amount").html(total_amount+" SCC");

                        jQuery("input.get_address").val(server_address);
                        jQuery("p#get_config_text").html(config_text);
                        jQuery("div#qr_code").html('<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='+server_address+'&choe=UTF-8" style="margin:auto;" />');
                        jQuery("button.cpText").click(function(){             
                            var value = jQuery("input#copyTarget").select(); 
                            console.log(value);
                            document.execCommand("Copy");
                        });

                        //refresh action
                        jQuery('.refresh_transaction').on('click',function(e){
                            e.preventDefault();

                            jQuery.ajax({
                                url:site_url+'/wp-admin/admin-ajax.php',
                                type:"POST",
                                data:{'order_id':order_id,'scc':total_coins,'server_address':server_address,'max_time_limit':max_time_limit,'confirmation_no':confirmation_no,'action':'getTransactionID'},
                                dataType:"json",
                                beforeSend: function() {
                                    jQuery("img#ajax-loader").show();
                                },
                                success: function(response){
                                    //console.log(response);
                                    jQuery("img#ajax-loader").hide();
                                    if(response.status == 'pending')
                                    {
                                        console.log(response.status);
                                        jQuery("input[name=transaction_id]").val(response.transaction_id);
                                        jQuery("button.verify_transaction").remove();
                                        jQuery(".refresh_transaction").show();
                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("input[name=confimation_no]").val(response.confirmations);
                                        jQuery("label.order_status").addClass('orange');
                                        jQuery("label.order_status").html('Pending...');

                                        return false;
                                    }

                                    if(response.status == 'reject')
                                    {
                                        console.log(response.status);

                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("button.verify_transaction").remove();
                                        jQuery("label.order_status").addClass('red');
                                        jQuery("label.order_status").html('Rejected');

                                        return false;
                                    }

                                    if(response.status == 'success')
                                    {
                                        console.log(response.status);
                                        jQuery('div#success').show();
                                        jQuery('header.entry-header').html("<h1 class='green'>Thank you for your order!<h1><p>We have received your SCC payment. You will receive an email from us with your tracking information as soon as your order is shipped.</p>");
                                        jQuery("p.order_confirm_msg").hide();
                                        jQuery("input[name=transaction_id]").val(response.transaction_id);
                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("button.verify_transaction").remove();
                                        jQuery("input[name=confimation_no]").val(response.confirmations);
                                        jQuery("label.order_status").addClass('green');
                                        jQuery("label.order_status").html('Success');
                                        window.location.reload();

                                        return false;
                                    }
                                }
                            });
                        });

                        jQuery('.verify_transaction').on('click',function(e){
                            e.preventDefault();
                            if(jQuery("input[name=transaction_id]").val().length == 0)
                            {
                                jQuery("input[name=transaction_id]").addClass('border-red');
                                return false;
                            }

                            jQuery("input[name=transaction_id]").removeClass('border-red');
                            var transaction_id = jQuery("input[name=transaction_id]").val();
                            var data = {'order_id':order_id,'scc':total_coins,'transaction_id':transaction_id,'server_address':server_address,'max_time_limit':max_time_limit,'confirmation_no':confirmation_no,'marginal_error':marginal_error}; 
                            data.action = 'getTransaction';

                            jQuery.ajax({
                                url:site_url+'/wp-admin/admin-ajax.php',
                                type:"POST",
                                data:data,           
                                dataType:"json",
                                beforeSend:function(){
                                    jQuery("img#ajax-loader").show();
                                    jQuery('.verify_transaction').addClass('not-allowed').attr('disabled','disabled');
                                },
                                success: function(response){
                                    //console.log(response);
                                    jQuery("img#ajax-loader").hide();
                                    jQuery('.verify_transaction').removeClass('not-allowed').removeAttr('disabled');

                                    if(response.status == 'fail')
                                    {
                                        jQuery("span.error_msg").html("Invalid Transaction ID or still not confirmed. Please wait 1-2 minutes click verify again");
                                        return false;
                                    }
                                    if(response.status == 'invalid')
                                    {
                                        jQuery("span.error_msg").html("Invalid Transaction ID or still not confirmed. Please wait 1-2 minutes click verify again");
                                        return false;
                                    }
                                    if(response.status == 'already_exist')
                                    {
                                        jQuery("span.error_msg").html("Sorry... Transaction ID is already exist.");
                                        return false;
                                    }

                                    if(response.status == 'pending')
                                    {
                                        console.log(response.status);
                                        jQuery("button.verify_transaction").remove();
                                        jQuery(".refresh_transaction").show();
                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("input[name=confimation_no]").val(response.confirmations);
                                        jQuery("label.order_status").addClass('orange');
                                        jQuery("label.order_status").html('Pending...');

                                        return false;
                                    }

                                    if(response.status == 'reject')
                                    {
                                        console.log(response.status);
                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("button.verify_transaction").remove();
                                        jQuery("label.order_status").addClass('red');
                                        jQuery("label.order_status").html('Rejected');

                                        return false;
                                    }

                                    if(response.status == 'success')
                                    {
                                        console.log(response.status);
                                        jQuery('header.entry-header').html("<h1 class='green'>Thank you for your order!<h1><p>We have received your SCC payment. You will receive an email from us with your tracking information as    soon as your order is shipped.</p>");
                                        jQuery("p.order_confirm_msg").hide();
                                        jQuery("input[name=transaction_id]").attr('disabled','disabled');
                                        jQuery("button.verify_transaction").remove();
                                        jQuery("input[name=confimation_no]").val(response.confirmations);
                                        jQuery("label.order_status").addClass('green');
                                        jQuery("label.order_status").html('Success');
                                        window.location.reload();

                                        return false;
                                     }
                                }
                            });
                        });          
                    });
    
                </script>
                <?php
            }
        }
    }
}

function isa_order_received_text( $text, $order ) {
    if($order->has_status('completed') )
    {
        $new = 'Thank you. Your order has been received'; 
    }
    else
    {
        $new = '';
    }
    return $new;
}
add_filter('woocommerce_thankyou_order_received_text', 'isa_order_received_text', 10, 2 );

function get_scc_transaction() {
    global $wpdb; 
    $db_table_name = $wpdb->prefix . 'stakecubecoin_transaction_ids';

    $action = $_POST['action']; 
    $order_id = $_POST['order_id'];
    $trans_id = $_POST['transaction_id'];
    $marginal_error = $_POST['marginal_error'];

    $scc_coins = $_POST['scc'];
    $server_address = $_POST['server_address'];
    $max_time_limit = $_POST['max_time_limit'];
    $confirmation_no = $_POST['confirmation_no'];
    $url = file_get_contents(TRANSACTION_URL."?txid=".$trans_id."&decrypt=1");
    $result_array = json_decode($url,true);
    // print_r($url);exit;

    if(!empty($result_array))
    {
        foreach ($result_array['vout'] as $vout) 
        {
            $current_value = round($vout['value'],2);
            $current_address = $vout['scriptPubKey']['addresses'][0];
            if($server_address == $current_address)
            {
                if(abs($scc_coins-$current_value) <= $marginal_error)
                {
                    $status = 1;
                }
            }   
        }

        if($status == 1)
        {
            //check if transaction is exists or not
            if($action == 'getTransaction')
            {
                $result = $wpdb->get_results ( "SELECT transaction_id FROM  $db_table_name" );
                $already_exist = 0;
                if(count($result) > 0)
                {
                    foreach($result as $rs)
                    {
                        if($rs->transaction_id == $trans_id)
                        {
                            $already_exist = 1;
                        }
                    }
                }
                if($already_exist == 1)
                {
                    echo json_encode(['status'=>'already_exist']);exit;
                }
                else
                {
                    //insert transaction id in db
                    $wpdb->insert( $db_table_name, array( 'transaction_id' => $trans_id, 'order_status' => 'pending', 'order_time'=>time(),'order_id'=>$order_id ) );
                }
            }

            $order_time = $wpdb->get_results ( "SELECT order_time FROM  $db_table_name WHERE transaction_id='$trans_id'" );
            if(count($order_time)>0)
            {
                if(((time()-($order_time[0]->order_time))/60) > $max_time_limit)
                {
                    echo json_encode(['status'=>'reject']);
                    exit;
                }
            }

            if($result_array['confirmations'] >= $confirmation_no)
            {
                $wpdb->update( $db_table_name, array('order_status' => 'success','confirmation_no'=>$result_array['confirmations'],'order_time'=>time(),'order_id'=>$order_id ), array('transaction_id'=>$trans_id) );

                $order = new WC_Order($order_id);
                $order->update_status('completed', 'SCC Transaction Done!!!'); 
                $status = 'success';
            }
            else
            {
                $wpdb->update( $db_table_name, array('order_status' => 'pending','confirmation_no'=>$result_array['confirmations'],'order_time'=>time(),'order_id'=>$order_id ), array('transaction_id'=>$trans_id) );
                $status = 'pending';                
            }
            echo json_encode(['status'=>$status,'blockhash'=>$result_array['blockhash'],'confirmations'=>$result_array['confirmations'],'blocktime'=>$result_array['blocktime']]);exit;
        }
        else
        {
            echo json_encode(['status'=>'invalid']);exit;
        }
    }
    else
    {          
        echo json_encode(["status"=>"fail"]);exit;
    }
}


add_action("wp_ajax_getTransaction", "get_scc_transaction");
add_action("wp_ajax_nopriv_getTransaction", "get_scc_transaction");

add_action("wp_ajax_getTransactionID", "get_trans_id");
add_action("wp_ajax_nopriv_getTransactionID", "get_trans_id");


function get_trans_id()
{
    global $wpdb; 
    $order_id = $_POST['order_id'];
    $db_table_name = $wpdb->prefix . 'stakecubecoin_transaction_ids';
   
    $transaction = $wpdb->get_results ( "SELECT * FROM  $db_table_name WHERE order_id='$order_id'" );

    if(count($transaction) > 0)
    {
        $action = $_POST['action']; 
    
        $trans_id = $transaction[0]->transaction_id;
        if($transaction[0]->order_status == 'pending')
        {

            $scc_coins = $_POST['scc'];
            $server_address = $_POST['server_address'];
            $max_time_limit = $_POST['max_time_limit'];
            $confirmation_no = $_POST['confirmation_no'];
            $url = file_get_contents(TRANSACTION_URL."?txid=".$trans_id."&decrypt=1");
            $result_array = json_decode($url,true);

            $order_time = $wpdb->get_results ( "SELECT order_time FROM  $db_table_name WHERE transaction_id='$trans_id'" );
            if(count($order_time)>0)
            {
                if(((time()-($order_time[0]->order_time))/60) > $max_time_limit)
                {
                    echo json_encode(['status'=>'reject']);
                    $wpdb->update($db_table_name, array('order_status' => 'reject', 'order_time'=>time(),'order_id'=>$order_id ), array('transaction_id'=>$trans_id) );
                    exit;
                }
            }

            if($result_array['confirmations'] >= $confirmation_no)
            {
                $wpdb->update($db_table_name, array('order_status' => 'success','confirmation_no'=>$result_array['confirmations'],'order_time'=>time(),'order_id'=>$order_id ), array('transaction_id'=>$trans_id) );
                $order = new WC_Order($order_id);
                $order->update_status('completed', 'SCC Transaction Done!!!');

                $status = 'success';
            }
            else
            {
                $wpdb->update( $db_table_name, array('order_status' => 'pending','confirmation_no'=>$result_array['confirmations'],'order_time'=>time(),'order_id'=>$order_id ), array('transaction_id'=>$trans_id) );

                $status = 'pending';
            }
            echo json_encode(['status'=>$status,'blockhash'=>$result_array['blockhash'],'confirmations'=>$result_array['confirmations'],'blocktime'=>$result_array['blocktime'],'transaction_id'=>$trans_id]);exit;
        } 
        else
        {
            echo json_encode(['status'=>$transaction[0]->order_status,'transaction_id'=>$trans_id,'confirmations'=>$transaction[0]->confirmation_no]);exit;
        }
    }
    else
    {
        echo json_encode(['status'=>0]);exit;
    }
}

function SCCConversion($price,$currency_code = 'USD')
{
    $url = file_get_contents("https://api.coingecko.com/api/v3/coins/stakecube");
    $result_array = json_decode($url,true);
    if(!empty($result_array))
    {
        $per_scc_price = $result_array['market_data']['current_price'][strtolower($currency_code)];          
        $price_scc = number_format($price/$per_scc_price,2);
        return $price_scc;            
    }
}

add_filter( 'wc_price', 'my_custom_price_format', 10, 3 );

function my_custom_price_format( $formatted_price, $price, $args ) {
    $model =new WC_SCC;
    if($model->scc_shop == 'yes')
    {
        $currency_code = get_woocommerce_currency();
  
        $price_scc = SCCConversion($price,$currency_code);
        $currency_symbol = ' <span>SCC</span> ';
        $price_scc = $price_scc.$currency_symbol; 
    
        $formatted_price_scc = "<span> $price_scc</span>";

        return $formatted_price_scc ;
    }
    else
    {
        $currency_symbol = get_woocommerce_currency_symbol();
        return $price.$currency_symbol;
    }

}


function myplugin_plugin_path() {

    // gets the absolute path to this plugin directory
    return untrailingslashit( plugin_dir_path( __FILE__ ) );
}
add_filter( 'woocommerce_locate_template', 'myplugin_woocommerce_locate_template', 10, 3 );


function myplugin_woocommerce_locate_template( $template, $template_name, $template_path ) {
    global $woocommerce;

    $_template = $template;

    if ( ! $template_path ) $template_path = $woocommerce->template_url;

    $plugin_path  = myplugin_plugin_path() . '/woocommerce/';

    // Look within passed path within the theme - this is priority
    $template = locate_template(

        array(
            $template_path . $template_name,
            $template_name
        )
    );

    // Modification: Get the template from this plugin, if it exists
    if ( ! $template && file_exists( $plugin_path . $template_name ) )
        $template = $plugin_path . $template_name;

    // Use default template
    if ( ! $template )
        $template = $_template;

    // Return what we found
    return $template;
}
?>
