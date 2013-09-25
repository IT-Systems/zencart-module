/**
 * Svea WebPay Zencart module svea.js
 * javascript used to pass Svea WebPay payment method credentials et al
 * 
 * Version 4.0 - Zen Cart
 * Kristian Grossman-Madsen, Shaho Ghobadi
 */
var isReady; //undefined
    
jQuery(document).ready(function (){
      
    if( !isReady ) { isReady = true;  // as svea.js is included w/both payplan & invoice, only hook functions once.

    // store customerCountry in attribute
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: 
        { 
            SveaAjaxGetCustomerCountry: true 
        }
    }).done( function( msg ) {
        jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry",msg);
        jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry",msg);       
    });

    // first, uncheck all payment buttons
    jQuery("input[type=radio][name='payment']").prop('checked', false);
    
    // show fields depending on payment method selected  
    jQuery("input[type=radio][name='payment']").click( function() {

        var checked_payment = jQuery("input:radio[name=payment]:checked").val();
        switch( checked_payment ) {
        
            // Svea invoice payment method selected
            case 'sveawebpay_invoice':

                // get customerCountry
                var customerCountry = jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry");

                // hide addresses
                hideBillingAndInvoiceAddress( customerCountry );

                // show input fields
                jQuery('#sveaPartPayField').hide();
                jQuery('#sveaInvoiceField').show();

                // force getAddresses on ssn input
                jQuery("#sveaSSN").change( function(){
                    getAddresses(   jQuery('#sveaSSN').val(), 
                                    jQuery('#sveaInvoiceField input[type="radio"]:checked').val(), 
                                    jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry"),
                                    "sveaAddressSelector"
                    );
                });
                
                // set zencart billing/shipping to match getAddresses selection
                jQuery('#sveaAddressSelector').change( function() {
                    jQuery.ajax({
                        type: "POST",
                        url: "sveaAjax.php",
                        data: { 
                            SveaAjaxSetCustomerInvoiceAddress: true, 
                            SveaAjaxAddressSelectorValue: jQuery('#sveaAddressSelector').val() 
                        }, 
                        success: function(msg) { msg; }
                    });
                });
            break; //case 'sveawebpay_invoice':
        
            //
            // Svea partpay payment method selected
            case 'sveawebpay_partpay':

                // get customerCountry
                var customerCountry = jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry");

                // hide addresses
                hideBillingAndInvoiceAddress( customerCountry );

                // show input fields
                jQuery('#sveaPartPayField').show();
                jQuery('#sveaInvoiceField').hide();

                // get & show getPaymentOptions 
                getPaymentOptions( customerCountry );

                // force getAddresses & show part payment options on ssn input 
                jQuery("#sveaSSNPP").change( function(){
                    getAddresses(   jQuery('#sveaSSNPP').val(), 
                                    "false", // partpay not available to companies
                                    jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry"),
                                    "sveaAddressSelectorPP"
                    );
                });

                // set zencart billing/shipping to match getAddresses selection
                jQuery('#sveaAddressSelectorPP').change( function() {
                    jQuery.ajax({
                        type: "POST",
                        url: "sveaAjax.php",
                        data: { 
                            SveaAjaxSetCustomerInvoiceAddress: true, 
                            SveaAjaxAddressSelectorValue: jQuery('#sveaAddressSelectorPP').val() 
                        }, 
                        success: function(msg) { msg; }
                    });
                });
            break; //case 'sveawebpay_partpay':
        
            //If other payment methods are selected, hide all svea related    
            default:
                
                // show billing address if hidden
                showBillingAndInvoiceAddress();

                // hide svea payment methods
                jQuery('#sveaInvoiceField').hide();
                jQuery('#sveaPartPayField').hide();
            break; //default:
        }
    });

    // show/hide private/company input fields depending on country
    jQuery("input[type=radio][name='sveaIsCompany'][value='false']").click( function() {    // show private
        jQuery('#sveaInitials_div').show();
        jQuery('#sveaBirthDate_div').show();
        jQuery('#sveaVatNo_div').hide();
    });
    
    jQuery("input[type=radio][name='sveaIsCompany'][value='true']").click( function() {     // company
        jQuery('#sveaInitials_div').hide();
        jQuery('#sveaBirthDate_div').hide();
        jQuery('#sveaVatNo_div').show();
    });

    } // isReady
});
    
// hide billing, invoice fields in getAddress countries
function hideBillingAndInvoiceAddress( country ) {
    if( (country === 'SE') ||
        (country === 'NO') || 
        (country === 'DK') )
    {
        jQuery('#checkoutPaymentHeadingAddress').hide();
        jQuery('#checkoutBillto').hide();
        jQuery('#checkoutPayment .floatingBox').hide();
    }
}

// show billing address if hidden
function showBillingAndInvoiceAddress() {
    jQuery('#checkoutPaymentHeadingAddress').show();
    jQuery('#checkoutBillto').show();
    jQuery('#checkoutPayment .floatingBox').show();
}

//
// new getAddresses() that uses the integration package
function getAddresses( ssn, isCompany, countryCode, addressSelectorName ) {

    // Show loader
    jQuery('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');

    // Do getAddresses call 
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: { SveaAjaxGetAddresses: true, 
                sveaSSN: ssn,
                sveaIsCompany: isCompany,
                sveaCountryCode: countryCode
        },
        
        success: function(msg){
            jQuery('#SveaInvoiceLoader').remove();
            jQuery( "#" + addressSelectorName ).empty(); 
            jQuery( "#" + addressSelectorName ).append(msg);
            jQuery( 'label[for="' + addressSelectorName + '"]' ).show();
            jQuery( "#" + addressSelectorName ).show();

            // update billing/shipping addresses in db for display on checkout_confirmation page
            jQuery.ajax({
                type: "POST",
                url: "sveaAjax.php",
                data: { 
                    SveaAjaxSetCustomerInvoiceAddress: true, 
                    SveaAjaxAddressSelectorValue: jQuery( "#" + addressSelectorName ).val()
                }, 
                success: function(msg) { msg; }
           });
        }
    });
}

function getPaymentOptions( countryCode ) {
    
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: {
            SveaAjaxGetPaymentOptions: true,
            sveaCountryCode: countryCode
        },
        success: function(msg){
            jQuery( "#sveaPaymentOptionsPP").empty();
            jQuery( "#sveaPaymentOptionsPP" ).append(msg);
            jQuery( 'label[for="sveaPaymentOptionsPP"]' ).show();
            jQuery( "#sveaPaymentOptionsPP" ).show();
        }
    });
}