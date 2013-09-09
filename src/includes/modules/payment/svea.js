jQuery(document).ready(function (){
    
    //Function to get which payment method selected
    jQuery("input[type=radio][name='payment']").click( function() {
    
        var checked_payment = jQuery("input:radio[name=payment]:checked").val();

        //If Svea invoice is selected
        if (checked_payment === 'sveawebpay_invoice'){
            jQuery('#sveaPartPaymentField').hide();
            jQuery('#sveaInvoiceField').show();
            //jQuery('button[type=submit]').attr('disabled','true');
            
            jQuery("#sveaSSN").change( function(){
                getAddresses();
            });
       }
        //If Svea Part payment
        else if (checked_payment === 'sveawebpay_partpay'){    
            jQuery('#sveaInvoiceField').hide();
            jQuery('#sveaPartPaymentField').show();
            //jQuery('button[type=submit]').attr('disabled','true');


        //If other payment methods are selected, hide all svea related    
        }else{
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
        }
    });
}

// ------------------------------------------------------------------------------------

//Get adress, PartPay
function getAdressPP(fin){
    
    var sveaSSN = jQuery('#sveaSSN_partpayment').val();
    
    if (sveaSSN == ''){
        jQuery('#sveaSSN_error_partpayment').html('Please enter social security number.');
    }else{
        
        //Show loader
        $('#sveaSSN_partpayment').after('<img src="images/svea_indicator.gif" id="SveaPPLoader" />');
        
        jQuery.ajax({
    	  type: "POST",
    	  url: "sveaAjax.php",
    	  data: {sveapnr: sveaSSN, paymentOptions: '1', f: fin},
    	  success: function(msg){
    	      jQuery('#sveaSSN_error_partpayment').empty();
              eval(msg);
              jQuery('#SveaPPLoader').remove();
    		}
    	});
    }
}