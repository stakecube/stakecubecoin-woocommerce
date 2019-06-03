<?php 

class WC_SCC extends WC_Payment_Gateway{

    public function __construct(){
        $this->id = 'stakecubecoin_payment';
        $this->method_title = __('Pay with StakeCubeCoin','woocommerce-stakecubecoin');
        $this->title = __('Pay with StakeCubeCoin','woocommerce-stakecubecoin');
        $this->has_fields = true;
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->server_address = $this->get_option('server_address');
        $this->confirmation_no = $this->get_option('confirmation_no');
        $this->max_time_limit = $this->get_option('max_time_limit');
        $this->marginal_error = $this->get_option('marginal_error');
        $this->scc_shop = $this->get_option('scc_shop');
        
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
    }
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'woocommerce-stakecubecoin' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable StakeCubeCoin Payment', 'woocommerce-stakecubecoin' ),
                'default'       => 'yes'
            ),
            'title' => array(
                'title'         => __( 'Method Title', 'woocommerce-stakecubecoin' ),
                'type'          => 'text',
                'default'       => __( 'Pay With StakeCubeCoin', 'woocommerce-stakecubecoin' ),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Customer Message', 'woocommerce-stakecubecoin' ),
                'type' => 'textarea',
                'css' => 'width:500px;',
                'default' => 'Please send SCC to the above address and enter your transaction ID below',
                'description'   => __( 'The message which you want to appear to the customer in the checkout page.', 'woocommerce-stakecubecoin' ),
            ),
            'server_address' => array(
                'title' => __( 'SCC Wallet Address', 'woocommerce-stakecubecoin' ),
                'type' => 'text',
                'description'   => __( 'Please enter the StakeCubeCoin wallet address in which you would like to receive SCC.' ),
            ),
            'confirmation_no' => array(
                'title' => __( 'Number of Confirmations', 'woocommerce-stakecubecoin' ),
                'type' => 'number',
                'default' => '2',
                'description'   => __( 'Please enter the number of confirmations upon which the order will be considered as confirmed' ),
            ),
            'max_time_limit' => array(
                'title' => __( 'Maximum Time Limit (in Minutes)', 'woocommerce-stakecubecoin' ),
                'type' => 'number',
                'default' => '30',
                'description'   => __( 'Time in which the system should interact with the blockchain to get the number of confirmations' ),
            ),
            'marginal_error' => array(
                'title' => __( 'Marginal Error', 'woocommerce-stakecubecoin' ),
                'type' => 'number',
                'default' => '0.01',

            ),
            'scc_shop' => array(
                'title' => __( 'Show SCC prices on website - Beta', 'woocommerce-stakecubecoin' ),
                'type' => 'checkbox',
                'default' => 'no',
                'label'         => __( ' ', 'woocommerce-stakecubecoin' ),
            ),
        );
    }
    
    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     * @return void
     */
    public function admin_options() {
        ?>
        <h3><?php _e( 'StakeCubeCoin Payment Settings', 'woocommerce-stakecubecoin' ); ?></h3>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <table class="form-table">
                            <?php $this->generate_settings_html();?>
                        </table><!--/.form-table-->
                    </div>                   
                </div>
            </div>                
        <?php
    }
    
    public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );
          
        $scc_coins = $this->get_scc($order->order_total,get_woocommerce_currency());

        // Reduce stock levels
        wc_reduce_stock_levels( $order_id );
        // Remove cart
        $woocommerce->cart->empty_cart();
        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' =>  $this->get_return_url( $order )
        );     
    }

    public function payment_fields(){

        ?>
        <input type="hidden" name="currency_code" value="<?php echo get_woocommerce_currency(); ?>">
        <fieldset>
            <p>
            <img src="<?php echo plugins_url('/woocommerce-stakecubecoin/stakecubecoin-main-logo.png') ?>" style="max-width: 400px; float: left;">
            <p class="form-row form-row-wide" id="scc-amount" style="font-size: 18px;font-weight: bold; float: left;"></p>
            <p class="form-row form-row-wide" id="scc-rate"></p>                        
            <div class="clear"></div>
        </fieldset>

        <?php       
    }


    public function get_scc($amount,$currency_code)
    {
        $url = file_get_contents("https://api.coingecko.com/api/v3/coins/stakecube");
        $result_array = json_decode($url,true);
        if(!empty($result_array))
        {
            $per_scc_price = $result_array['market_data']['current_price'][strtolower($currency_code)];
            $result_amount =number_format($amount/$per_scc_price,2);
            return $result_amount;
        }
    }
   
}
?>