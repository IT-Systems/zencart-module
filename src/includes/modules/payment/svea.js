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

//Get adress, invoice
function getAdress(){
 
    var sveaSSN = jQuery('#sveaSSN').val();
    var company = jQuery('#sveaIsCompany').val();

    if (sveaSSN == ''){
        jQuery('#sveaSSN_error_invoice').html('Please enter social security number.');
    }else{
        
        //Show loader
        $('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');
        
        jQuery.ajax({
    	  type: "POST",
    	  url: "sveaAjax.php",
    	  data: {sveapnr: sveaSSN, is_company: company},
    	  success: function(msg){
    	      jQuery('#sveaSSN_error_invoice').empty();
              eval(msg);
              $('#SveaInvoiceLoader').remove();
    		}
    	});
    }
}

//
// new getAddress() that uses the integration package
function v4_getAddress(){
    var sveaSSN = jQuery('#sveaSSN').val();
    var company = jQuery('#sveaIsCompany').val();

    //do new getAddress
    //Show loader
    $('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');

    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: {v4: true, sveapnr: sveaSSN, is_company: company },
        success: function(msg){
            jQuery('#sveaSSN_error_invoice').empty();
            eval(msg);
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