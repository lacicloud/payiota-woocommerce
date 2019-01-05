<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Iota_Class extends WC_Payment_Gateway {
	
	public $api_key, $verification_key, $currency, $title, $description, $custom_message, $timeout_after;

	function __construct() {
		
		global $woocommerce;
		
		// global ID
		$this->id = "payiota";

		$this->api_key = $this->get_option( 'api_key' );
		
		$this->verification_key = $this->get_option( 'verification_key' );
		
		$this->currency = $this->get_option( 'currency' );
		
		$this->title = $this->get_option( 'title' );
		
		$this->description = $this->get_option( 'description' );

		$this->ipn_url = $this->get_option('ipn_url');
			
	}
}