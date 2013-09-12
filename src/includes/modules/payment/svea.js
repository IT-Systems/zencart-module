jQuery(document).ready(function (){
    
    
    //get country
    var customerCountry = "";
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: { 
            SveaAjaxGetCustomerCountry: true, 
        }, 
        success: function(msg) { 
            customerCountry = msg;
        }
    });
    
    // show fields depending on payment method selected
    jQuery("input[type=radio][name='payment']").prop('checked', false);
    jQuery("input[type=radio][name='payment']").click( function() {
        
        var checked_payment = jQuery("input:radio[name=payment]:checked").val();

        //If Svea invoice is selected
        if (checked_payment === 'sveawebpay_invoice'){
            
            // hide billing address in getAddresses countries
            if( (customerCountry === 'SE') ||
                (customerCountry === 'NO') || 
                (customerCountry === 'DK') )
            {
                jQuery('#checkoutPaymentHeadingAddress').hide();
                jQuery('#checkoutBillto').hide();
                jQuery('#checkoutPayment .floatingBox').hide();
            }

            // show input fields
            jQuery('#sveaPartPaymentField').hide();
            jQuery('#sveaInvoiceField').show();
 
            // force getAddresses on ssn input
            jQuery("#sveaSSN").change( function(){
                getAddresses();
            });
            
            // set zencart billing/shipping to match getAddresses selection
            jQuery('#sveaAddressSelector').change( function() {
                    ;
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
        }

        else if (checked_payment === 'sveawebpay_partpay') {    
            // TODO
        }
        //If other payment methods are selected, hide all svea related    
        else{
            // show billing address if getAddresses countries
            if( (customerCountry  === 'SE') ||
                (customerCountry === 'NO') || 
                (customerCountry === 'DK') ) 
            {
                jQuery('#checkoutPaymentHeadingAddress').show();
                jQuery('#checkoutBillto').show();
                jQuery('#checkoutPayment .floatingBox').show();
            }

            // hide svea payment methods
            jQuery('#sveaInvoiceField').hide();
            jQuery('#sveaPartPaymentField').hide();
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
                sveaCountryCode: customerCountry },
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