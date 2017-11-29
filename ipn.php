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
	
		$order->update_status('processing');
		update_option("woo_iota_order_status_".$order_id, "processing");
		$order->add_order_note( __( 'IOTA payment Complete. Order status changed from Pending payment to Processing.', 'cwoa-authorizenet-aim' ) );
		
		echo "Order set to Processing.";
		
	}else{
		echo "Paid Iota Amount is less than Price Amount Iota.";
	}

}


?>