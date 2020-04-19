<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles stripe checkout payment method.
 *
 * @extends WC_Payment_Gateway
 *
 */
class Eh_Stripe_Checkout extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		$this->id                 = 'eh_stripe_checkout';
		$this->method_title       = __( 'Stripe-Checkout', 'payment-gateway-stripe-and-woocommerce-integration' );
		$this->method_description = sprintf( __( 'Pay with Stripe Checkout', 'payment-gateway-stripe-and-woocommerce-integration' ) );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
        
        $this->eh_stripe_option        = get_option("woocommerce_eh_stripe_pay_settings");
		$this->title                   = $this->get_option( 'eh_stripe_checkout_title' );
        $this->description             = $this->get_option( 'eh_stripe_checkout_description' );
        $this->method_description      = sprintf( __( 'You will have to specify an account name in Stripe <a href="%s" target="_blank">Dashboard</a> prior to configuring the settings. Stripe account credentials should be entered <a href="%s">here</a>. ', 'payment-gateway-stripe-and-woocommerce-integration' ),'https://dashboard.stripe.com/account', admin_url( 'admin.php?page=wt_stripe_menu' ));
        
        $this->enabled                 = $this->get_option( 'enabled' );
		$this->eh_order_button         = $this->get_option( 'eh_stripe_checkout_order_button') ? $this->get_option( 'eh_stripe_checkout_order_button') : __('Place Order','payment-gateway-stripe-and-woocommerce-integration');

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        add_action( 'wc_ajax_eh_spg_stripe_checkout_session', array( $this, 'stripe_checkout_session' ) );
        add_action( 'wc_ajax_eh_spg_stripe_checkout_order', array( $this, 'eh_spg_stripe_checkout_order_callback' ) );
        add_action( 'wc_ajax_eh_spg_stripe_cancel_order', array( $this, 'eh_spg_stripe_cancel_order' ) );
        add_action('woocommerce_cart_emptied', array($this,'unset_cart_empty'));
        add_action( 'woocommerce_available_payment_gateways',array($this, 'eh_disable_gateway_for_order_pay' ));
 
        // Set stripe API key.
        \Stripe\Stripe::setApiKey(EH_Stripe_Payment::get_stripe_api_key());
        
	}
    
	/**
	 * Initialize form fields in stripe checkout payment settings page.
     * @since 3.3.4
	 */
	public function init_form_fields() {


		$this->form_fields = array(
			'enabled'      => array(
				'title'       => __('Enable/Disable','payment-gateway-stripe-and-woocommerce-integration'),
				'label'       => __('Enable Stripe Checkout Payment','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'checkbox',
                'default'     => 'no',
                'desc_tip'    => __('Enable this option to have a stripe checkout payment option in your checkout.','payment-gateway-stripe-and-woocommerce-integration')
			),
			'eh_stripe_checkout_title'         => array(
				'title'       => __('Title','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'text',
				'description' =>  __('The texts entered in this field will be displayed as the title for the stripe checkout payment at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
				'default'     => __('Stripe Checkout', 'payment-gateway-stripe-and-woocommerce-integration'),
				'desc_tip'    => true,
			),
			'eh_stripe_checkout_description'     => array(
				'title'       => __('Description','payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'textarea',
				'css'         => 'width:25em',
				'description' => __('The texts entered in this field will be displayed as a short description for the stripe checkout payment at the checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
			 	'default'     => __('Secure payment via Stripe checkout.', 'payment-gateway-stripe-and-woocommerce-integration'),
			 	'desc_tip'    => true
			),

			'eh_stripe_checkout_order_button'    => array(
				'title'       => __('Order Button Text', 'payment-gateway-stripe-and-woocommerce-integration'),
				'type'        => 'text',
				'description' => __('You can key in the text of your choice that will appear at the checkout page as the order button text.', 'payment-gateway-stripe-and-woocommerce-integration'),
				'default'     => __('Pay via Stripe Checkout', 'payment-gateway-stripe-and-woocommerce-integration'),
				'desc_tip'    => true
			)
		);   
    }
    
	/**
     * Checks if gateway should be available to use.
     * @since 3.3.4
     */
	public function is_available() {

        if ('yes' === $this->enabled) {
           
            if ('test' === $this->eh_stripe_option['eh_stripe_mode']) {
                if (! $this->eh_stripe_option['eh_stripe_test_publishable_key'] || ! $this->eh_stripe_option['eh_stripe_test_secret_key']) {
                    return false;
                }
            } else {
                if (!$this->eh_stripe_option['eh_stripe_live_secret_key'] || !$this->eh_stripe_option['eh_stripe_live_publishable_key']) {
                    return false;
                }
            }

            return true;
        }
        return false; 
    }
	

	/**
	 * Payment form on checkout page
     * @since 3.3.4
	 */
	public function payment_fields() {
		$user        = wp_get_current_user();
		$total       = WC()->cart->total;
		$description = $this->get_description();
		echo '<div class="status-box">';
        
        if ($this->description) {
            echo apply_filters('eh_stripe_desc', wpautop(wp_kses_post("<span>" . $this->description . "</span>")));
        }
        echo "</div>";
	}
    
    /**
     * loads stripe checkout scripts.
     * @since 3.3.4
     */
    public function payment_scripts(){
        
        if(is_checkout()){ 
            wp_enqueue_script('eh_checkout_script', EH_STRIPE_MAIN_URL_PATH . 'assets/js/eh-checkout.js',array('stripe_v3_js','jquery'),EH_STRIPE_VERSION ,true);

            if ('test' == $this->eh_stripe_option['eh_stripe_mode']) {
                $public_key = $this->eh_stripe_option['eh_stripe_test_publishable_key'];
                $secret_key = $this->eh_stripe_option['eh_stripe_test_secret_key'];
            } else {
                $public_key = $this->eh_stripe_option['eh_stripe_live_publishable_key'];
                $secret_key = $this->eh_stripe_option['eh_stripe_live_secret_key'];
            }

            $eh_stripe_checkout_params = array(
                'key'                                           => $public_key,
                'wp_ajaxurl'                                    => admin_url("admin-ajax.php"),
                'wc_ajaxurl'                                    => WC_AJAX::get_endpoint( '%%change_end%%' ),
                'eh_checkout_session_nonce'                     => wp_create_nonce( '_eh_checkout_session_nonce' ),
                'button'                                        => $this->eh_order_button,
                'error_notice'                                  => __('Please fill in all the required fields','payment-gateway-stripe-and-woocommerce-integration'),
                
            );
            wp_localize_script( 'eh_checkout_script', 'eh_stripe_checkout_params', $eh_stripe_checkout_params);
        }
    }

    /**
     * creates checkout section for redirection.
     * @since 3.3.4
     */
    public function stripe_checkout_session(){

        // //stripe does not support coupon discount to be added along with the line items as of now
        // $items    = array();
        // foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            
        //     $quantity_label =  0 < $cart_item['quantity'] ?  $cart_item['quantity']  : '';
        //     $_product       =  wc_get_product( $cart_item['data']->get_id()); 

        //     $item = array(
        //         'name' => $_product->get_title(),
        //         'amount' => EH_Stripe_Payment::get_stripe_amount($_product->get_price()),
        //         'currency' => strtolower(get_woocommerce_currency()),
        //         'quantity' => $quantity_label,

        //     );

        //     $items[] = $item;
        // }
        // $discounts   = EH_Stripe_Payment::get_stripe_amount(  WC()->cart->get_cart_discount_total());
        // $tax         = EH_Stripe_Payment::get_stripe_amount( WC()->cart->tax_total + WC()->cart->shipping_tax_total);
        // $shipping    = EH_Stripe_Payment::get_stripe_amount( WC()->cart->shipping_total);
        // if ( wc_tax_enabled() ) {
        //     $items[] = array(
        //         'name' =>  esc_html( __( 'Tax', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
        //         'amount' => $tax,
        //         'currency' => strtolower(get_woocommerce_currency()),
        //         'quantity' => 1,
        //     );
        // }
        // if ( WC()->cart->needs_shipping() ) {
        
        //     $items[] = array(
        //         'name' =>   esc_html( __( 'Shipping', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
        //         'amount' => $shipping,
        //         'currency' => strtolower(get_woocommerce_currency()),
        //         'quantity' => 1,
        //     );
        // }
        // if ( WC()->cart->has_discount() ) {

        //     $items[] = array(
        //         'name' =>   esc_html( __( 'Discount', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
        //         'amount' => $discounts,
        //         'currency' => strtolower(get_woocommerce_currency()),
        //         'quantity' => 1,
        //     );
        // }
        
        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, '_eh_checkout_session_nonce'))
        {
            die(_e('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }

        $data = WC()->session->data = wp_unslash( wc_clean( $_POST ));
        $order_id = WC()->checkout()->create_order($data);
        $account = $this->eh_create_account($data, $order_id);
     
        if($account == 'error'){

            wp_send_json(array('error' => 'An account is already registered with your email address. Please log in.'));
           
        }
       
        $user  = wp_get_current_user();
        $email = isset(WC()->session->data['billing_email']) ? WC()->session->data['billing_email'] : $user->user_email;

        $total = EH_Stripe_Payment::get_stripe_amount( WC()->cart->total, get_woocommerce_currency());
        
        
        $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'customer_email' => $email ,
        
        'line_items' => [
        // $items
            [
                'name'     => esc_html( __( 'Total', 'payment-gateway-stripe-and-woocommerce-integration' ) ),
                'amount'   => $total,
                'currency' => strtolower(get_woocommerce_currency()),
                'quantity' => 1,
            ]
            
        ],
        'success_url'=> home_url().'/?wc-ajax=eh_spg_stripe_checkout_order'.'&session_id={CHECKOUT_SESSION_ID}'.'&order_id='.$order_id.'&_wpnonce='.wp_create_nonce('eh_checkout_nonce'),
        'cancel_url' => home_url().'/?wc-ajax=eh_spg_stripe_cancel_order'.'&order_id='.$order_id.'&_wpnonce='.wp_create_nonce('eh_checkout_nonce'),
        ]);
       
        wp_send_json( array('session_id' => $session->id ) );
    }

	/**
     * creates order after checkout session is completed.
     * @since 3.3.4
     */
    public function eh_spg_stripe_checkout_order_callback() {
        
        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, 'eh_checkout_nonce'))
        {
            die(_e('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
      
        if(isset(WC()->session->data)){
            $session_data  = WC()->session->data;
        }else{
            $session_data  = WC()->session->data1;
        }
        $session_id = sanitize_text_field( $_GET['session_id'] );
        $order_id = intval( $_GET['order_id'] );
        $order = wc_get_order($order_id);

        /* sign in the user */
        if(isset($session_data['createaccount']) && $session_data['createaccount']) 
        {
            $userID = (WC()->version < '2.7.0') ? $order->user_id : $order->get_user_id();
            wc_set_customer_auth_cookie( $userID );
        }


        $obj  = new EH_Stripe_Payment();
       
        
        if (is_wp_error($order)) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'payment-gateway-stripe-and-woocommerce-integration'), 520));
        } elseif (false === $order) {
            throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'payment-gateway-stripe-and-woocommerce-integration'), 521));
        } else {
            do_action('woocommerce_new_order', $order_id);
        }

        // Store fees
        foreach (WC()->cart->get_fees() as $fee_key => $fee) {
            if (version_compare(WC_VERSION, '3.0', '<')) {
                $item_id = $order->add_fee($fee);
            } else {
                $item = new WC_Order_Item_Fee();
                $item->set_props(array(
                    'name' => $fee->name,
                    'tax_class' => $fee->taxable ? $fee->tax_class : 0,
                    'total' => $fee->amount,
                    'total_tax' => $fee->tax,
                    'taxes' => array(
                        'total' => $fee->tax_data,
                    ),
                    'order_id' => $order->get_id(),
                ));
                $item_id = $item->save();
                $order->add_item($item);
            }

            if (!$item_id) {
                throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'payment-gateway-stripe-and-woocommerce-integration'), 526));
            }

            // Allow plugins to add order item meta to fees
            if (version_compare(WC_VERSION, '3.0', '<')) {
                do_action('woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key);
            } else {
                do_action('woocommerce_new_order_item', $item_id, $fee, $order->get_id());
            }
        }

        // Store tax rows
        foreach (array_keys(WC()->cart->get_cart_contents_taxes() + WC()->cart->get_shipping_taxes()) as $tax_rate_id) {
            $tax_amount = WC()->cart->get_tax_amount($tax_rate_id);
            $shipping_tax_amount = WC()->cart->get_shipping_tax_amount($tax_rate_id);

            if (version_compare(WC_VERSION, '3.0', '<')) {
                $item_id = $order->add_tax($tax_rate_id, $tax_amount, $shipping_tax_amount);
            } else {
                $item = new WC_Order_Item_Tax();
                $item->set_props(array(
                    'rate_id' => $tax_rate_id,
                    'tax_total' => $tax_amount,
                    'shipping_tax_total' => $shipping_tax_amount,
                ));
                $item->set_rate($tax_rate_id);
                $item->set_order_id($order->get_id());
                $item_id = $item->save();
                $order->add_item($item);
            }

            if ($tax_rate_id && !$item_id && apply_filters('woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated') !== $tax_rate_id) {
                throw new Exception(sprintf(__('Error %d: Unable to create order. Please try again.', 'payment-gateway-stripe-and-woocommerce-integration'), 528));
            }
        }
        
        $this->eh_set_address_to_order($order,$session_data);

        WC()->shipping->calculate_shipping(WC()->cart->get_shipping_packages());

        // Get the rate object selected by user.
        foreach (WC()->shipping->get_packages() as $package_key => $package) {
            foreach ($package['rates'] as $key => $rate) {
                // Loop through user chosen shipping methods.
                foreach (WC()->session->get('chosen_shipping_methods') as $method) {
                    if ($method === $key) {
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $order->add_shipping($rate);
                        } else {
                            $item = new WC_Order_Item_Shipping();
                            $item->set_props(array(
                                'method_title' => $rate->label,
                                'method_id' => $rate->id,
                                'total' => wc_format_decimal($rate->cost),
                                'taxes' => $rate->taxes,
                                'order_id' => $order->get_id(),
                            ));
                            foreach ($rate->get_meta_data() as $key => $value) {
                                $item->add_meta_data($key, $value, true);
                            }
                            $item->save();
                            $order->add_item($item);
                        }
                    }
                }
            }
        }

        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $order->set_payment_method($available_gateways['eh_stripe_pay']);
        $order->set_total(WC()->cart->shipping_total, 'shipping');
        $order->set_total(WC()->cart->get_cart_discount_total(), 'cart_discount');
        $order->set_total(WC()->cart->get_cart_discount_tax_total(), 'cart_discount_tax');
        $order->set_total(WC()->cart->tax_total, 'tax');
        $order->set_total(WC()->cart->shipping_tax_total, 'shipping_tax');
        $order->set_total(WC()->cart->total);
        $current_user = get_current_user_id();
        if( $current_user ) {
            $order->set_customer_id($current_user);
        }
        $order->calculate_totals();
        try {
            
            $order_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600);
            
            $session = \Stripe\Checkout\Session::retrieve($session_id);
            $payment_intent_id = $session->payment_intent;
    
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $charge_details = $payment_intent->charges['data'];
     
            foreach($charge_details as $charge){

                $charge_response = $charge;  
            }

            $data = $obj->make_charge_params($charge_response, $order_id);
            
           if ($charge_response->paid == true) {

                if($charge_response->captured == true){
                    $order->payment_complete($data['id']);
                }

                if (!$charge_response->captured) {
                    $order->update_status('on-hold');
                }

                $order->set_transaction_id( $data['transaction_id'] );

                $order->add_order_note(__('Payment Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . ucfirst($data['status']) . ' [ ' . $order_time . ' ] . ' . __('Source : ', 'payment-gateway-stripe-and-woocommerce-integration') . $data['source_type'] . '. ' . __('Charge Status :', 'payment-gateway-stripe-and-woocommerce-integration') . $data['captured'] . (is_null($data['transaction_id']) ? '' : '. Transaction ID : ' . $data['transaction_id']), 'woocommerce');
                WC()->cart->empty_cart();
                add_post_meta($order_id, '_eh_stripe_payment_charge', $data);
                EH_Stripe_Log::log_update('live', $data, get_bloginfo('blogname') . ' - Charge - Order #' . $order_id);
               
                // Return thank you page redirect.
                $result =  array(
                    'result'   => 'success',
                    'redirect'     => $obj->get_return_url($order),
                );
          
                wp_safe_redirect($result['redirect']);
                exit;
               
            } else {
                wc_add_notice($data['status'], $notice_type = 'error');
                EH_Stripe_Log::log_update('dead', $charge_response, get_bloginfo('blogname') . ' - Charge - Order #' . $order_id);
            }
        } catch (Exception $error) {
            $user = wp_get_current_user();
            $user_detail = array(
                'name' => get_user_meta($user->ID, 'first_name', true),
                'email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'billing_phone', true),
            );
            $oops = $error->getJsonBody();
            wc_add_notice(__('Payment Failed ', 'payment-gateway-stripe-and-woocommerce-integration') .  __('Refresh and try again', 'payment-gateway-stripe-and-woocommerce-integration'), $notice_type = 'error');
            EH_Stripe_Log::log_update('dead', array_merge($user_detail, $oops), get_bloginfo('blogname') . ' - Charge - Order #' . $order_id);
        }
    }
    
    /**
     * Disable stripe checkout gateway for order-pay page 
     * @since 3.3.6
     */ 
    function eh_disable_gateway_for_order_pay( $available_gateways ) {
        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            
           unset( $available_gateways['eh_stripe_checkout'] );
        }
        return $available_gateways;
    }

    /**
     * creates user if create an account is checked while guest users proceed to payment
     * @since 3.3.4
     */ 
    public function eh_create_account($session_data,$order_id){
        

        if ( $this->eh_is_registration_needed($session_data) ) {
        
            if (!empty($session_data['billing_email'])) {


                $maybe_username = sanitize_user($session_data['billing_first_name']);
                $counter = 1;
                $user_name = $maybe_username;

                while (username_exists($user_name)) {
                    $user_name = $maybe_username . $counter;
                    $counter++;
                }
                $data = array(
                    'user_login' => $user_name,
                    'user_email' => $session_data['billing_email'],
                    'user_pass'  => (isset($session_data['account_password']) ? $session_data['account_password'] :''),
                );
                $userID = wc_create_new_customer($data['user_email'],$data['user_login'],$data['user_pass']);
                if ( is_wp_error( $userID ) ) {
                    // throw new Exception( $userID->get_error_message() );
                    $error = 'error';
                    return $error;
                }
 
              
                if (!is_wp_error($userID)) {

                    update_user_meta($userID, 'billing_first_name', $session_data['billing_first_name']);
                    update_user_meta($userID, 'billing_last_name', $session_data['billing_last_name']);
                    update_user_meta($userID, 'billing_address_1', $session_data['billing_address_1']);
                    update_user_meta($userID, 'billing_state', $session_data['billing_state']);
                    update_user_meta($userID, 'billing_email', $session_data['billing_email']);
                    update_user_meta($userID, 'billing_postcode', $session_data['billing_postcode']);
                    update_user_meta($userID, 'billing_phone', $session_data['billing_phone']);
                    update_user_meta($userID, 'billing_country', $session_data['billing_country']);
                    update_user_meta($userID, 'billing_company', $session_data['billing_company']);

                    $user_data = wp_update_user(array('ID' => $userID, 'first_name' => $session_data['billing_first_name'], 'last_name' => $session_data['billing_last_name']));
                    $order = wc_get_order($order_id);
                    update_post_meta($order_id, '_customer_user', $userID);
                    
                    WC()->session->data1 = $session_data;

                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function eh_is_registration_needed($session_data) {
        
        if(isset($session_data['createaccount']) && ($session_data['createaccount'] == 1)){
            return true;            
        }
        if(isset($session_data['account_password']) && !empty($session_data['account_password'])){
            return true;            
        }
        if(! is_user_logged_in()){ 
            if('no' == get_option( 'woocommerce_enable_guest_checkout' )){ 
                return true;            
            }
        }
        return false;
    }
    
    /*
    * sets address to order details 
    * @since 3.3.4
    */
    public function eh_set_address_to_order($order,$session_data){
 
          $billing_address = array(
       
            'first_name'      => isset($session_data['billing_first_name']) ? $session_data['billing_first_name'] : '',
            'last_name'       => isset($session_data['billing_last_name']) ? $session_data['billing_last_name'] : '' ,
            'billing_company' => isset($session_data['billing_company']) ? $session_data['billing_company'] : '',
            'email'           => isset($session_data['billing_email']) ? $session_data['billing_email'] : '',
            'phone'           => isset($session_data['billing_phone']) ? $session_data['billing_phone'] : '',
            'country'         => isset($session_data['billing_country']) ? $session_data['billing_country'] : '' ,
            'address_1'       => isset($session_data['billing_address_1']) ? $session_data['billing_address_1'] : '',
            'address_2'       => isset($session_data['billing_address_2']) ? $session_data['billing_address_2'] : '',
            'city'            => isset($session_data['billing_city']) ? $session_data['billing_city'] : '' ,
            'state'           => isset($session_data['billing_state']) ? $session_data['billing_state'] : '' ,
            'postcode'        => isset($session_data['billing_postcode']) ? $session_data['billing_postcode'] : '',
        );

        $shipping_address = array(
            
            'first_name'      => isset($session_data['shipping_first_name']) ? $session_data['shipping_first_name'] : '',
            'last_name'       => isset($session_data['shipping_last_name']) ? $session_data['shipping_last_name'] : '' ,
            'billing_company' => isset($session_data['shipping_company']) ? $session_data['shipping_company'] : '',
            'country'         => isset($session_data['shipping_country']) ? $session_data['shipping_country'] : '' ,
            'address_1'       => isset($session_data['shipping_address_1']) ? $session_data['shipping_address_1'] : '',
            'address_2'       => isset($session_data['shipping_address_2']) ? $session_data['shipping_address_2'] : '',
            'city'            => isset($session_data['shipping_city']) ? $session_data['shipping_city'] : '' ,
            'state'           => isset($session_data['shipping_state']) ? $session_data['shipping_state'] : '' ,
            'postcode'        => isset($session_data['shipping_postcode']) ? $session_data['shipping_postcode'] : '',
        );

        $order->set_address($billing_address, 'billing');
        if(isset($session_data['ship_to_different_address'])){ 
            $order->set_address($shipping_address, 'shipping');
        }else{
            $order->set_address($billing_address, 'shipping');
        }
    }
    
    /**
     * Creates pending order when checkout session is cancelled.
     * @since 3.3.4
     */
    public function eh_spg_stripe_cancel_order(){
        
        if(!EH_Helper_Class::verify_nonce(EH_STRIPE_PLUGIN_NAME, 'eh_checkout_nonce'))
        {
            die(_e('Access Denied', 'payment-gateway-stripe-and-woocommerce-integration'));
        }
        $session_data  = WC()->session->data;
        $order_id = intval( $_GET['order_id'] );
        
        $order = wc_get_order($order_id);
        $this->eh_set_address_to_order($order,$session_data);

        /* sign in the user */
        if(isset($session_data['createaccount']) && $session_data['createaccount']) 
        {
            $userID = (WC()->version < '2.7.0') ? $order->user_id : $order->get_user_id();
            wc_set_customer_auth_cookie( $userID );
        }
        
        WC()->shipping->calculate_shipping(WC()->cart->get_shipping_packages());
        // Get the rate object selected by user.
        foreach (WC()->shipping->get_packages() as $package_key => $package) {
            foreach ($package['rates'] as $key => $rate) {
                // Loop through user chosen shipping methods.
                foreach (WC()->session->get('chosen_shipping_methods') as $method) {
                    if ($method === $key) {
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $order->add_shipping($rate);
                        } else {
                            $item = new WC_Order_Item_Shipping();
                            $item->set_props(array(
                                'method_title' => $rate->label,
                                'method_id' => $rate->id,
                                'total' => wc_format_decimal($rate->cost),
                                'taxes' => $rate->taxes,
                                'order_id' => $order->get_id(),
                            ));
                            foreach ($rate->get_meta_data() as $key => $value) {
                                $item->add_meta_data($key, $value, true);
                            }
                            $item->save();
                            $order->add_item($item);
                        }
                    }
                }
            }
        }
      
        wc_add_notice(__('You have cancelled Stripe Checkout Session. Please try to process your order again.', 'payment-gateway-stripe-and-woocommerce-integration'), 'notice');
        wp_redirect(wc_get_checkout_url());
        exit;
    }

    /**
     * Unsets session data when cart is emptied.
     * @since 3.3.4
     */
    public function unset_cart_empty(){

        if(isset(WC()->session->data)){

            unset(WC()->session->data);
        }
        if(isset(WC()->session->data1)){

            unset(WC()->session->data1);
        }
    }

    /**
     * process order refund
     * @since 3.3.4
     */
    public function process_refund($order_id, $amount = NULL, $reason = '') {
        
        $obj = new EH_Stripe_Payment();
		$client = $obj->get_clients_details();
		if ($amount > 0) {
			
			$data = get_post_meta($order_id, '_eh_stripe_payment_charge', true);
            $status = $data['captured'];

			if ('Captured' === $status) {
				$charge_id = $data['id'];
				$currency = $data['currency'];
				$total_amount = $data['amount'];
						
				$wc_order = new WC_Order($order_id);
				$div = $amount * ($total_amount / ((WC()->version < '2.7.0') ? $wc_order->order_total : $wc_order->get_total()));
				$refund_params = array(
					'amount' => EH_Stripe_Payment::get_stripe_amount($div, $currency),
					'reason' => 'requested_by_customer',
					'metadata' => array(
						'order_id' => $wc_order->get_order_number(),
						'Total Tax' => $wc_order->get_total_tax(),
						'Total Shipping' => (WC()->version < '2.7.0') ? $wc_order->get_total_shipping() : $wc_order->get_shipping_total(),
						'Customer IP' => $client['IP'],
						'Agent' => $client['Agent'],
						'Referer' => $client['Referer'],
						'Reaon for Refund' => $reason
					)
				);
						
				try {
					$charge_response = \Stripe\Charge::retrieve($charge_id);
					$refund_response = $charge_response->refunds->create($refund_params);
					if ($refund_response) {
										
						$refund_time = date('Y-m-d H:i:s', time() + get_option('gmt_offset') * 3600);
						
						$data = $obj->make_refund_params($refund_response, $amount, ((WC()->version < '2.7.0') ? $wc_order->order_currency : $wc_order->get_currency()), $order_id);
						add_post_meta($order_id, '_eh_stripe_payment_refund', $data);
						$wc_order->add_order_note(__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . __('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . __('Status : refunded ', 'payment-gateway-stripe-and-woocommerce-integration') . ' [ ' . $refund_time . ' ] ' . (is_null($data['transaction_id']) ? '' : '<br>' . __('Transaction ID : ', 'payment-gateway-stripe-and-woocommerce-integration') . $data['transaction_id']));
						EH_Stripe_Log::log_update('live', $data, get_bloginfo('blogname') . ' - Refund - Order #' . $wc_order->get_order_number());
						return true;
					} else {
						EH_Stripe_Log::log_update('dead', $data, get_bloginfo('blogname') . ' - Refund Error - Order #' . $wc_order->get_order_number());
						$wc_order->add_order_note(__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . __('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . __(' Status : Failed ', 'payment-gateway-stripe-and-woocommerce-integration'));
						return new WP_Error('error', $data->message);
					}
				} catch (Exception $error) {
					$oops = $error->getJsonBody();
					EH_Stripe_Log::log_update('dead', $oops['error'], get_bloginfo('blogname') . ' - Refund Error - Order #' . $wc_order->get_order_number());
					$wc_order->add_order_note(__('Reason : ', 'payment-gateway-stripe-and-woocommerce-integration') . $reason . '.<br>' . __('Amount : ', 'payment-gateway-stripe-and-woocommerce-integration') . get_woocommerce_currency_symbol() . $amount . '.<br>' . __('Status : ', 'payment-gateway-stripe-and-woocommerce-integration') . $oops['error']['message']);
					return new WP_Error('error', $oops['error']['message']);
				}
			} else {
				return new WP_Error('error', __('Uncaptured Amount cannot be refunded', 'payment-gateway-stripe-and-woocommerce-integration'));
			}
		} else {
			return false;
	    }
    }

}