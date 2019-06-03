<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<style type="text/css">
	.border-red
	{
		border: 1px solid red !important;
	}
	.red
	{
		color: red;
	}
	.green
	{
		color: green;
	}
	.gray
	{
		color: gray;
	}
	.orange
	{
		color: orange;
	}
	.font-sw
	{
		font-size: 16px !important;
		font-weight: bold !important;
	}
	.inline-block
	{
		display: inline-block !important;
	}
	.not-allowed
	{
		cursor: not-allowed;
	}
	.header.entry-header
	{
		display: block !important;
	}
	
.button1:hover {background-color: #4d4d4d; color: #F1F1F1;}
</style>
<input type="hidden" name="currency_code" value="<?php echo get_woocommerce_currency(); ?>">
<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-order">

	<?php if ( $order ) : ?>
		<?php if ( $order->has_status( 'completed' ) ) : ?>
				<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php _e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php _e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php _e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php _e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php _e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		
		<?php else : ?>

			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php _e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php _e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
				</li>

				<li class="woocommerce-order-overview__total total">
					<?php _e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>

				<li class="woocommerce-order-overview__payment-method method">
					<?php _e( 'Payment method:', 'woocommerce' ); ?>
					<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
				</li>

				<?php endif; ?>

			</ul>
	
		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		<?php endif; ?>

		

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), null ); ?></p>

	<?php endif; ?>

</div>

<?php if ( $order->has_status( 'pending' ) || $order->has_status( 'cancelled' ) ) : ?>
<div style="width: 50%;margin: auto;float: left;">
<p class="font-sw order_confirm_msg">Your order will be confirmed as soon as we receive your payment.<br><br>
Thank you for shopping with us.</p>

<p><span class="font-sw">SCC Due:</span> <label class="get_scc green font-sw inline-block"></label></p>

<p><span>Address:</span><br><input type="text" class="get_address gray font-sw inline-block" readonly id="copyTarget" style="width: 90%"> &nbsp;&nbsp;<button class="cpText button1" style="background-color: #7a5eb7; color: #FFFFFF;">Copy Address</button></p>

<p class="order_confirm_msg" id="get_config_text"></p>
<p>
	<div >

	<span>Transaction ID:</span><br><input required type="text" name="transaction_id" style="width: 90%">
	</div> 
	<span class="red error_msg"></span></p>
<form method="post">
 <button style="background-color: #7a5eb7; color: #FFFFFF;" data-type='verify' class="verify_transaction button1" type="submit">Verify Transaction</button>
	<img style="width: 50px;display: none;" id="ajax-loader" src="<?php echo plugins_url('/woocommerce-stakecubecoin/loader.svg') ?>"  />

 	<a data-type='refresh' title="refresh" href="#" class="refresh_transaction" style="display: none;" ><img src="<?php echo plugins_url('/woocommerce-stakecubecoin/refresh_icon.png') ?>" /></a>
</p>

</form>

<p>No of Confirmations: &nbsp;&nbsp;
  <input type="text" name="confimation_no" value="0" disabled style="width: 90%"></p>
<p>Order Status: <label class="order_status orange">Pending...</label></p>

</div>

<div style="width: 50%;margin:auto;float: right" id="qr_code"></div>

<?php endif; ?>