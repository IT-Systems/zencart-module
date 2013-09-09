jQuery(document).ready(function (){
    
    jQuery("input[type=radio][name='payment']").prop('checked', false);
    
    //Function to get which payment method selected
    jQuery("input[type=radio][name='payment']").click( function() {
        
        var checked_payment = jQuery("input:radio[name=payment]:checked").val();

        //If Svea invoice is selected
        if (checked_payment === 'sveawebpay_invoice'){
            // hide billing address from default view
            jQuery('#checkoutPaymentHeadingAddress').hide();
            jQuery('#checkoutBillto').hide();
            jQuery('#checkoutPayment .floatingBox').hide();
            
            // show input fields
            jQuery('#sveaPartPaymentField').hide();
            jQuery('#sveaInvoiceField').show();
 
            // force getAddresses on ssn input
            jQuery("#sveaSSN").change( function(){
                getAddresses();
            });
       }
        //If Svea Part payment
        else if (checked_payment === 'sveawebpay_partpay'){    
            jQuery('#sveaInvoiceField').hide();
            jQuery('#sveaPartPaymentField').show();

        //If other payment methods are selected, hide all svea related    
        }else{
            jQuery('#checkoutPaymentHeadingAddress').show();
            jQuery('#checkoutBillto').show();
            jQuery('#checkoutPayment .floatingBox').show();
            
            
            jQuery('#sveaInvoiceField').hide();
            jQuery('#sveaPartPaymentField').hide();
        }
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
                sveaCountryCode: "SE" },    // TODO pass country code correctly
        success: function(msg){
            jQuery('#SveaInvoiceLoader').remove();
            // TODO remove additional addresses from selector on multiple submit?
            jQuery("#sveaAddressSelector").append(msg);
            jQuery('label[for="sveaAddressSelector"]').show();
            jQuery("#sveaAddressSelector").show();
            
            // update addresses in db
            jQuery.ajax({
                type: "POST",
                url: "sveaAjax.php",
                data: { SveaAjaxSetCustomerInvoiceAddress: true }, 
                success: function(msg) { msg; }
           });
        }
    });
}