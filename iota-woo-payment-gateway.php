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


add_action( 'plugins_loaded', 'payiota_init', 0 );

function payiota_init() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'iota-gateway-class.php' );
	// class add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'payiota_add_gateway' );
	function payiota_add_gateway( $methods ) {
		$methods[] = 'PayIOTA';
		return $methods;
	}
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'payiota_action_links' );
function payiota_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'payiota' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

add_action( 'woocommerce_thankyou', 'payiota_add_content_thankyou' );
 
function payiota_add_content_thankyou($order_id) {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	
	 //don't show payment box if a different gateway is selected
	 global $woocommerce;
	 $chosen_gateway = $woocommerce->session->chosen_payment_method;
	 if ($chosen_gateway !== "payiota") {
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
				
		echo '<div id="payment" class="woocommerce-checkout-payment wooiota_main">';
		
		if( $order_status != "cancelled" && $order_status != "completed" ){
			echo $code = '<form id="payiotaform" action="https://payiota.me/external.php" method="GET">
		<input type="hidden" name="address" value="'.$result[0].'">
		<input type="hidden" name="price" value="'.$result[1].'">
		<input type="hidden" name="success_url" value="'.$iota_obj->get_return_url( $customer_order ).'/clientarea.php?action=invoices">
		<input type="hidden" name="cancel_url" value="'.esc_url_raw($customer_order->get_cancel_order_url_raw()).'">
		</form>';
		echo "<script>document.getElementById('payiotaform').submit();</script>";

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
	$order = new WC_Order( $_POST['order_id'] );
	echo $order->get_status();
	exit;
}

function iota_cancel_order(){
	$order = new WC_Order( $_POST['order_id'] );
	$order->update_status('cancelled');
	update_option("woo_iota_order_status_".$_POST['order_id'], "cancelled");
	$order->add_order_note( __( 'IOTA payment Timed Out. Order status changed from Pending payment to Cancelled.', 'payiota' ) );
	echo "Order Cancelled.";
	exit;
}

add_filter( 'woocommerce_currencies', 'payiota_add_iota_currency' );

function payiota_add_iota_currency( $currencies ) {
     $currencies['MIOTA'] = __( 'Mega IOTA', 'woocommerce' );
	$currencies['IOTA'] = __( 'IOTA', 'woocommerce' );
     return $currencies;
}

add_filter('woocommerce_currency_symbol', 'payiota_add_iota_currency_symbol', 10, 2);

function payiota_add_iota_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'MIOTA': $currency_symbol = 'Mi'; break;
		  case 'IOTA': $currency_symbol = 'IOTA'; break;
     }
     return $currency_symbol;
}

?>