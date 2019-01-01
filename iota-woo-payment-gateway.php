<?php
/* 
Plugin Name: IOTA Payment Gateway Pro
Plugin URI: http://cryptostore.trade
Description: IOTA Payment Gateway Extension For WooCommerce Pro Version. Simple but flexible.
Version: 1.0.1
Author: Dan Darden & Laszlo Molnarfi
Author URI: http://cryptostore.trade and https://lacicloud.net
*/ 

/*  Copyright 2017 Dan Darden (email: satoshin@protonmail.ch)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; If not, see <http://www.gnu.org/licenses/>
*/


add_action( 'plugins_loaded', 'cwoa_authorizenet_aim_init', 0 );
function cwoa_authorizenet_aim_init() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'iota-gateway-class.php' );
	// class add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'cwoa_add_authorizenet_aim_gateway' );
	function cwoa_add_authorizenet_aim_gateway( $methods ) {
		$methods[] = 'cwoa_AuthorizeNet_AIM';
		return $methods;
	}
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cwoa_authorizenet_aim_action_links' );
function cwoa_authorizenet_aim_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'cwoa-authorizenet-aim' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}


function aj_vortex_woo_scripts() {
		wp_enqueue_style( 'aj-ioto-woo-css', plugins_url( 'css/wooiota.css', __FILE__ ) );
		wp_enqueue_script(array('jquery'));
		wp_enqueue_script( 'aj-woo-ioto-js',  plugins_url( 'js/qrcode.min.js', __FILE__ ) );
}
add_action('wp_enqueue_scripts', 'aj_vortex_woo_scripts');


add_action( 'woocommerce_thankyou', 'bbloomer_add_content_thankyou' );
 
function bbloomer_add_content_thankyou($order_id) {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	
	 //don't show payment box if a different gateway is selected
	 global $woocommerce;
	 $chosen_gateway = $woocommerce->session->chosen_payment_method;
	 if ($chosen_gateway !== "cwoa_authorizenet_aim") {
			return;
	 }
	
	include_once( 'iota_class.php' );
	
	$iota_obj = new Iota_Class();
	
	$api_key = $iota_obj->api_key;
	$verification_key = $iota_obj->verification_key;
	$currency = $iota_obj->currency;
	
	$title = $iota_obj->title;	
	$title  = !empty( $title ) ? $title : "IOTA";
	
	$custom_message = $iota_obj->custom_message;
	
	$timeout_after = $iota_obj->timeout_after;
	
	$customer_order = new WC_Order( $order_id );
	
	
	if( !empty( $api_key ) ){

		$i_address = get_option( 'invoice_address_'.$order_id );
		$i_price = get_option( 'invoice_payment_'.$order_id );
		$order_status = get_option( 'woo_iota_order_status_'.$order_id );
		$currency_code = get_option('woocommerce_currency');
		$total_bill = $customer_order->get_total();
		
		if( $currency_code == "MIOTA" ){
			$currency_code = "IOTA";
			$total_bill *= 1000000;
		}
		
		if( empty( $i_price ) && empty( $i_address ) ){

			$data = array(
				"api_key" => $api_key,
				"price"   => $total_bill,
				"currency" => $currency_code,
				"order_id" => $customer_order->get_order_number()
			);

			$result = payiota_invoice($data);

			update_option( 'invoice_address_'.$order_id , $result[0]);
			update_option( 'invoice_payment_'.$order_id , $result[1]);
		}else{
			$result[] = $i_address;
			$result[] = $i_price;
		}
		
		echo "<script>";
		echo "var ajax_request_url = '" . admin_url('admin-ajax.php') . "'";
		echo "</script>";

		echo  '<script>var timeoutHandle;
		function countdown(minutes) {
			var seconds = 60;
			var mins = minutes
			function tick() {
				var counter = document.getElementById("woo_timer");
				var current_minutes = mins-1
				seconds--;
				counter.innerHTML =
				current_minutes.toString() + ":" + (seconds < 10 ? "0" : "") + String(seconds);
				if( seconds > 0 ) {
					timeoutHandle=setTimeout(tick, 1000);
				} else {

					if(mins > 1){

					   setTimeout(function () { countdown(mins - 1); }, 1000);

					}
				}
				
				if( current_minutes <= 0 && seconds <= 0 ){
					//alert( "time is up!" );
					cancel_order();
				}
			}
			tick();
		}
		
		
		function cancel_order(){
			jQuery.ajax({
				type : "POST",
				url: ajax_request_url,
				data: "action=iota_cancel_order&order_id="+jQuery("#order_id").val(),
				success: function(resp){
					
				}
			});
		}
		

		function check_payment_status(){
			jQuery.ajax({
				type : "POST",
				url: ajax_request_url,
				data: "action=check_payment_status&order_id="+jQuery("#order_id").val(),
				success: function(resp){
					if( jQuery.trim(resp) == "pending" ){
					
					}else if( jQuery.trim(resp) == "cancelled" || jQuery.trim(resp) == "completed" ){
						window.location = window.location.href;
					}
				}
			});
		}
		</script>';
		
		//echo $currency_code;
		
		echo '<input type="hidden" name="order_id" id="order_id" value="'.$order_id.'" />';
		
		echo '<div id="payment" class="woocommerce-checkout-payment wooiota_main">';
		
		if( !empty($order_status) && $order_status == "cancelled" ){
			echo '<div style="width:100%;padding:10px;background-color:rgba(255,58,45,1);color:#FFF;">IOTA payment Timed Out. Order status changed from Pending payment to Cancelled.</div>';
		}
		
		if( !empty($order_status) && $order_status == "completed" ){
			echo '<div style="width:100%;padding:10px;background-color:green;color:#FFF;">IOTA payment Complete. Order status changed from Pending payment to Completed.</div>';
		}
		
		
		if( $order_status != "cancelled" && $order_status != "completed" ){
		echo '<ul class="wc_payment_methods payment_methods methods" style="border-bottom:none;">
			<li class="wc_payment_method payment_method_cwoa_authorizenet_aim">

			<label for="payment_method_cwoa_authorizenet_aim">'.$title.'</label>
			<div class="payment_box payment_method_cwoa_authorizenet_aim">
			Send the exact amount for your payment at the following address : <strong>'.($result[1]/1000000).' Mi</strong><br/>
		<div style="background:rgba(255,239,239,0.7);padding: 10px;color: rgba(255,58,45,1);word-wrap: break-word;">'.$result[0].'</div>
		<p style="padding-top:20px; padding-bottom:0;text-align:center;"><strong>SCAN THIS QR FOR PAYMENT ADDRESS</strong></p>
		<hr style="border-bottom:3px solid #000;margin:0;" /><br/>
		<div id="qrcode" style="width:50%;margin:0 auto;"></div><script>new QRCodePayIOTA(document.getElementById("qrcode"), JSON.stringify ( { "address" : "'.$result[0].'", "amount" : "'.$result[1].'", "tag" : "" } ) );</script>
		'.( !empty( $custom_message ) ? '<p style="margin-top:10px;">'.$custom_message.'</p>' : '' ).'
		</div>
		<hr/ style="border-top:2px solid #000;">';
		
		
			echo '<script>setInterval(check_payment_status,1000*2)</script>';
			if( !empty( $timeout_after ) && $timeout_after != 0  ){
				echo '<p style="text-align:center;"><strong>Time Remaining: <span id="woo_timer"></span></strong></p><script>countdown('.$timeout_after.');</script>';
			}
			
			echo '</li></ul>';
		}
		
	echo '</div>';
		
		

	}else{
		echo "<br/><strong>Error! No API key is set for PayIOTA.</strong><br/>";
	}
}



function payiota_invoice($data){
	$postdata = http_build_query(
	    array(
	        "api_key" => $data['api_key'],
			"price" => $data['price'],
			"custom" => $data['order_id'],
			"action" => "new",
			"ipn_url" => plugins_url( 'ipn.php', __FILE__ ),
			"currency" => $data['currency']
	    )
	);

	$opts = array('http' =>
	    array(
	        'method'  => 'POST',
	        'header'  => 'Content-type: application/x-www-form-urlencoded',
	        'content' => $postdata
	    )
	);

	$context  = stream_context_create($opts);
	$response = file_get_contents('https://payiota.me/api.php', false, $context);
	
	//cURL fallback
	if (!$response) {
		
		if(is_callable('curl_init') == false){
			echo "ERROR: file_get_contents failed and cURL is not installed";
			die(1);
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl,CURLOPT_POST, 1);
		curl_setopt($curl,CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
		$response = curl_exec($curl);
		
		if (!$response) {
			echo "ERROR: file_get_contents and cURL failed";
			die(1);
		}
	}

	$response = json_decode($response, true);
	return $response;
}

function check_payment_status(){
	//print_r($_POST);
	$order = new WC_Order( $_POST['order_id'] );
	echo $order->get_status();
	exit;
}


add_action('wp_ajax_nopriv_check_payment_status', 'check_payment_status');
add_action('wp_ajax_check_payment_status', 'check_payment_status');


function iota_cancel_order(){
	$order = new WC_Order( $_POST['order_id'] );
	$order->update_status('cancelled');
	update_option("woo_iota_order_status_".$_POST['order_id'], "cancelled");
	$order->add_order_note( __( 'IOTA payment Timed Out. Order status changed from Pending payment to Cancelled.', 'cwoa-authorizenet-aim' ) );
	echo "Order Cancelled.";
	exit;
}
add_action('wp_ajax_nopriv_iota_cancel_order', 'iota_cancel_order');
add_action('wp_ajax_iota_cancel_order', 'iota_cancel_order');



add_filter( 'woocommerce_currencies', 'aj_add_my_currency' );

function aj_add_my_currency( $currencies ) {
     $currencies['MIOTA'] = __( 'Mega IOTA', 'woocommerce' );
	$currencies['IOTA'] = __( 'IOTA', 'woocommerce' );
     return $currencies;
}

add_filter('woocommerce_currency_symbol', 'aj_add_my_currency_symbol', 10, 2);

function aj_add_my_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'MIOTA': $currency_symbol = 'Mi'; break;
		  case 'IOTA': $currency_symbol = 'IOTA'; break;
     }
     return $currency_symbol;
}

?>