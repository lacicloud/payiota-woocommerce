<?php

class cwoa_AuthorizeNet_AIM extends WC_Payment_Gateway {

	function __construct() {
		
		global $woocommerce;
		
		// global ID
		$this->id = "cwoa_authorizenet_aim";

		// Show Title
		$this->method_title = __( "IOTA Payment Gateway", 'cwoa-authorizenet-aim' );

		// Show Description
		$this->method_description = __( "Iota Payment Gateway Plug-in for WooCommerce <br/><br/> <strong>IPN URL:</strong> ".plugins_url( 'ipn.php', __FILE__ ), 'cwoa-authorizenet-aim' );

		// vertical tab title
		$this->title = __( "IOTA Payment Gateway", 'cwoa-authorizenet-aim' );


		$this->icon = null;

		$this->has_fields = false;

		// setting defines
		$this->init_form_fields();

		// load time variable setting
		$this->init_settings();
		
		
		// Define user set variables
		$default_title = $this->get_option( 'title' );
		$default_desc = $this->get_option( 'description' );
		
		$this->title        = !empty( $default_title ) ? $default_title : "IOTA";
		$this->description  = !empty( $default_desc ) ? $default_desc : 'Pay with IOTA, a virtual currency. <a href="http://iota.org" target="_blank">What is Iota?</a>';
		
		// further check of SSL if you want
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // Here is the  End __construct()
	
	
	// administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable IOTA Plugin', 'cwoa-authorizenet-aim' ),
				'label'		=> __( 'Show IOTA as an option to customers during checkout', 'cwoa-authorizenet-aim' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'cwoa-authorizenet-aim' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This controls the title which users sees during checkout.', 'cwoa-authorizenet-aim' ),
				'default'	=> __( 'Mega IOTA (Mi)', 'cwoa-authorizenet-aim' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'cwoa-authorizenet-aim' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'This controls the description which users sees during checkout.', 'cwoa-authorizenet-aim' ),
				'default'	=> __( 'Pay with IOTA, a virtual currency. <a href="http://iota.org" target="_blank">What is Iota?</a>', 'cwoa-authorizenet-aim' ),
				'css'		=> 'max-width:450px;'
			),
			'api_key' => array(
				'title'		=> __( 'Iota API Key', 'cwoa-authorizenet-aim' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the API Key provided by payiota.me when you signed up for an account.', 'cwoa-authorizenet-aim' ),
			),
			'veri_key' => array(
				'title'		=> __( 'Iota Verification Key', 'cwoa-authorizenet-aim' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Verificatino Key provided by payiota.me when you signed up for an account.', 'cwoa-authorizenet-aim' ),
			),
			'currency_code' => array(
				'title'		=> __( '3 Letter Currency Code', 'cwoa-authorizenet-aim' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Verificatino Key provided by payiota.me when you signed up for an account.', 'cwoa-authorizenet-aim' ),
				'default'	=> __( 'USD', 'cwoa-authorizenet-aim' ),
			),
			'custom_message' => array(
				'title'		=> __( 'Custom Message', 'cwoa-authorizenet-aim' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This message will be shown beneath qr code.', 'cwoa-authorizenet-aim' ),
			),
			'timeout_after' => array(
				'title'		=> __( 'Timeout After', 'cwoa-authorizenet-aim' ),
				'type'		=> 'number',
				'desc_tip'	=> __( 'Time measured in minutes. 0 for no timeout. ', 'cwoa-authorizenet-aim' ),
			)
		);		
	}
	
	// Response handled for payment gateway
	public function process_payment( $order_id ) {
		global $woocommerce;

		$customer_order = new WC_Order( $order_id );
		
		$customer_order->add_order_note( __( 'Iota Order Processed.', 'cwoa-authorizenet-aim' ) );

		// paid order marked
		//$customer_order->payment_complete();

		// this is important part for empty cart
		$woocommerce->cart->empty_cart();

		// Redirect to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $customer_order ),
		);

	}
	
	// Validate fields
	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

}