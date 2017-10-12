<?php
/* 
Plugin Name: IOTA Payment Gateway
Plugin URI: http://ajvortex.com
Description: IOTA Payment Gateway Extension For WooCommerce. Simple but flexible.
Version: 1.0.0 
Author: Junaid Rajpoot
Author URI: http://ajvortex.com
*/ 

/*  Copyright 2017 Junaid Rajpoot (email: junaidfx@gmail.com)

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

		if( empty( $i_price ) && empty( $i_address ) ){

			$data = array(
				"api_key" => $api_key,
				"price"   => $customer_order->order_total,
				"currency" => !empty( $currency ) ? $currency : "USD",
				"order_id" => $customer_order->get_order_number()
			);

			$result = payiota_invoice($data);

			update_option( 'invoice_address_'.$order_id , $result[0]);
			update_option( 'invoice_payment_'.$order_id , $result[1]);
		}else{
			$result[] = $i_address;
			$result[] = $i_price;
		}

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
    }
    tick();
}</script>';
		
		echo '<div id="payment" class="woocommerce-checkout-payment wooiota_main">
			<ul class="wc_payment_methods payment_methods methods" style="border-bottom:none;">
			<li class="wc_payment_method payment_method_cwoa_authorizenet_aim">

			<label for="payment_method_cwoa_authorizenet_aim">'.$title.'</label>
			<div class="payment_box payment_method_cwoa_authorizenet_aim">
			Send the exact amount for your payment at the following address : <strong>'.($result[1]/1000000).' Mi</strong><br/>
		<div style="background:rgba(255,239,239,0.7);padding: 10px;color: rgba(255,58,45,1);word-wrap: break-word;">'.$result[0].'</div>
		<p style="padding-top:20px; padding-bottom:0;text-align:center;"><strong>SCAN THIS QR FOR PAYMENT ADDRESS</strong></p>
		<hr style="border-bottom:3px solid #000;margin:0;" /><br/>
		<div id="qrcode" style="width:50%;margin:0 auto;"></div><script>new QRCode(document.getElementById("qrcode"), "'.$result[0].'");</script>
		'.( !empty( $custom_message ) ? '<p style="margin-top:10px;">'.$custom_message.'</p>' : '' ).'
		</div>
		<hr/ style="border-top:2px solid #000;">
		'.( ( !empty( $timeout_after ) && $timeout_after != 0 ) ? '<p style="text-align:center;"><strong>Time Remaining: <span id="woo_timer"></span></strong></p><script>countdown('.$timeout_after.');</script>' : '' ).'
	</li>
		</ul></div>';
		

	}else{
		echo "<br/><strong>It seems the admin has not set a PayIOTA.me API key yet!</strong><br/>";
	}
}


function payiota_invoice($data){
	$request = array(
		"api_key" => $data['api_key'],
		"price" => $data['price'],
		"custom" => $data['order_id'],
		"action" => "new",
		"currency" => $data['currency']
	);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$request = http_build_query($request);

	curl_setopt($curl,CURLOPT_POST, 1);
	curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

	curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
	$response = curl_exec($curl);

	$response = json_decode($response, true);

	return $response;
}
?>