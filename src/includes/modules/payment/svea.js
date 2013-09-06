jQuery(document).ready(function (){
    
    //Function to get which payment method selected
    jQuery("input[type=radio][name='payment']").click( function() {
    
    var checked_payment = jQuery("input:radio[name=payment]:checked").val();
    
    //If Svea invoice is selected
    if (checked_payment == 'sveawebpay_invoice'){
        jQuery('#sveaPartPaymentField').hide();
        jQuery('#sveaInvoiceField').show();
        //jQuery('button[type=submit]').attr('disabled','true');

    //If Svea Part payment
    }else if (checked_payment == 'sveawebpay_partpay'){    
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
    $('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');

    // Do getAddresses call 
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: {getAddresses: true, sveapnr: jQuery('#sveaSSN').val(), is_company: jQuery('#sveaIsCompany').val(), country: "SE" },
        success: function(msg){
            jQuery('#sveaSSN_error_invoice').empty();
            jQuery("#addressSelector_invoice").show();
            jQuery("#addressSelector_invoice").append(msg);
            $('#SveaInvoiceLoader').remove();
        }
    });
}


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
              $('#SveaPPLoader').remove();
    		}
    	});
    }
}