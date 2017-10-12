<?php

include("../../../wp-config.php");

if (isset($_POST["address"])) {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'iota_class.php' );
	$iota_obj = new Iota_Class();
	
	$address = $_POST["address"];
	$order_id = $_POST["custom"];
	$verification = $_POST["verification"];
	$paid_iota = $_POST["paid_iota"];
	$price_iota = $_POST["price_iota"];
	//for more variables see documentation

	if ($verification !== $iota_obj->verification_key) {
		die(1);
	}

	global $woocommerce;

	$order = new WC_Order( $order_id );
	
	if( $paid_iota >= $price_iota ){
	
		$order->update_status('completed');
		update_option("woo_iota_order_status_".$order_id, "completed");
		
		echo "Order Completed.";
		
	}else{
		echo "Paid Iota Amount is less than Price Amount Iota.";
	}

}


?>