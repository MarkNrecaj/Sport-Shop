jQuery(function ($) {
    'use strict';

   
    var stripe = Stripe(eh_stripe_checkout_params.key);
    
    //replace place order button in checkout page with stripe checkout payment button
    $(document.body).ready( function () {

        get_stripe_checkout_button();

    });
    
    $(document.body).on('updated_cart_totals updated_checkout', function () {

        get_stripe_checkout_button();

    });

    $(document.body).on('change', 'input[type=radio][name=payment_method]', function () {

        get_stripe_checkout_button();
       
    });

    function get_stripe_checkout_button(){

        if ( $( '#payment_method_eh_stripe_checkout' ).is( ':checked'))
        {
            if ( $(".eh-stripe-checkout-button").length === 0){  

                $(".place-order").append( '<button  class=" button alt eh-stripe-checkout-button"> '+ eh_stripe_checkout_params.button + '</button>');
            }
            $('#place_order').hide();
        } 
        else 
        {
            $(".eh-stripe-checkout-button").remove();
            $('#place_order').show();
        }

    }
            
    $(document.body).on('click', '.eh-stripe-checkout-button', function (e) {
       e.preventDefault();
        
        //check to see if required fields are filled
        if ( $( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
            var $required_inputs = $( '.woocommerce-billing-fields .validate-required, .woocommerce-shipping-fields .validate-required' );
        } else {
            var $required_inputs = $( '.woocommerce-billing-fields .validate-required' );
        }

        if ( $required_inputs.length ) {
            var required_error = false;

            $required_inputs.each( function() {
                if ( $( this ).find( 'input.input-text, select' ).not( $( '#account_password, #account_username' ) ).val() === '' ) {
                    required_error = true;
                }
            });
           
            if ( required_error ) {
                //show error notice if any required field is not filled
                show_notice(eh_stripe_checkout_params.error_notice);
                return false;
            }
        }
      
        var post_data = $( 'form.checkout' ).serialize();
        var data=post_data+'&_wpnonce='+eh_stripe_checkout_params.eh_checkout_session_nonce;

        $.ajax({
            type: 'POST',
            data: data,
            url:  eh_stripe_checkout_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_stripe_checkout_session"),
            success: function (response) {
                console.log(response);
                if(response.error){
                    show_notice(response.error,'');
                }
                eh_stripe_checkout_params.session_id = response.session_id;
                
                stripe.redirectToCheckout({
                    sessionId: eh_stripe_checkout_params.session_id

                }).then(function (result) {

                    console.log(result.error.message);
                });
              
            }
            
        });
       

    });
    var show_notice = function( html_element, $target ) {
        if ( ! $target ) {
        $target = $( '.woocommerce-notices-wrapper:first' ) || $( '.cart-empty' ).closest( '.woocommerce' ) || $( '.woocommerce-cart-form' );
        }
        if ( $("div.woocommerce-error").length ) {
            $("div.woocommerce-error").remove();
           
        }
        $target.append( '<div class="woocommerce-notices-wrapper"> <div class="woocommerce-error">'+ html_element +'</div></div>' );
        $(window).scrollTop(0);
    };

});