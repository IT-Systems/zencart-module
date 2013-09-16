/**
 * Svea WebPay Zencart module svea.js
 * javascript used to pass Svea WebPay payment method credentials et al
 * 
 * Version 4.0 - Zen Cart
 * Kristian Grossman-Madsen, Shaho Ghobadi
 */

jQuery(document).ready(function (){

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
        
        // Svea invoice payment method selected
        var checked_payment = jQuery("input:radio[name=payment]:checked").val();
        switch( checked_payment ) {
                
            case 'sveawebpay_invoice':
              
            // get customerCountry
            var customerCountry = jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry");
            
            // hide billing, invoice fields in getAddress countries
            if( (customerCountry === 'SE') ||
                (customerCountry === 'NO') || 
                (customerCountry === 'DK') )
            {
                jQuery('#checkoutPaymentHeadingAddress').hide();
                jQuery('#checkoutBillto').hide();
                jQuery('#checkoutPayment .floatingBox').hide();
            }

            // show input fields
            jQuery('#sveaPartPayField').hide();
            jQuery('#sveaInvoiceField').show();
 
            // force getAddresses on ssn input
            jQuery("#sveaSSN").change( function(){
                getAddresses();
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
        
            // Svea invoice payment method selected
            case 'sveawebpay_partpay':

            // get customerCountry
            var customerCountry = jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry");
 console.log("cp:cc: " + customerCountry); //TODO remove
            // hide billing, invoice fields in getAddress countries
            if( (customerCountry === 'SE') ||
                (customerCountry === 'NO') || 
                (customerCountry === 'DK') )
            {
                jQuery('#checkoutPaymentHeadingAddress').hide();
                jQuery('#checkoutBillto').hide();
                jQuery('#checkoutPayment .floatingBox').hide();
            }

            // show input fields
            jQuery('#sveaPartPayFieldPP').show();
            jQuery('#sveaInvoiceField').hide();
 
            // force getAddresses on ssn input
            jQuery("#sveaSSN").change( function(){
                getAddresses();
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
            jQuery('#checkoutPaymentHeadingAddress').show();
            jQuery('#checkoutBillto').show();
            jQuery('#checkoutPayment .floatingBox').show();
            
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
    

    //
    // new getAddresses() that uses the integration package
    function getAddresses(){

        // Show loader
        jQuery('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');

        // Do getAddresses call 
        jQuery.ajax({
            type: "POST",
            url: "sveaAjax.php",
            data: { SveaAjaxGetAddresses: true, 
                    sveaSSN: jQuery('#sveaSSN').val(),
                    sveaIsCompany: jQuery('#sveaInvoiceField input[type="radio"]:checked').val(),
                    sveaCountryCode: jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry") // stored countryCode
            },
            success: function(msg){
                jQuery('#SveaInvoiceLoader').remove();
                // TODO remove additional addresses from selector on multiple submit?
                jQuery("#sveaAddressSelector").append(msg);
                jQuery('label[for="sveaAddressSelector"]').show();
                jQuery("#sveaAddressSelector").show();

                // update billing/shipping addresses in db for display on checkout_confirmation page
                jQuery.ajax({
                    type: "POST",
                    url: "sveaAjax.php",
                    data: { 
                        SveaAjaxSetCustomerInvoiceAddress: true, 
                        SveaAjaxAddressSelectorValue: jQuery('#sveaAddressSelector').val()
                    }, 
                    success: function(msg) { msg; }
               });
            }
        });
    }

});