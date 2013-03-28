jQuery(document).ready(function (){
    
    //Function to get which payment method selected
    jQuery("input[type=radio][name='payment']").click( function() {
    
    var checked_payment = jQuery("input:radio[name=payment]:checked").val();
    
    //If Svea invoice is selected
    if (checked_payment == 'sveawebpay_invoice'){
        jQuery('#sveaDelbetField').hide();
        jQuery('#sveaFaktField').show();
        //jQuery('button[type=submit]').attr('disabled','true');

    //If Svea Part payment
    }else if (checked_payment == 'sveawebpay_partpay'){    
        jQuery('#sveaFaktField').hide();
        jQuery('#sveaDelbetField').show();
        //jQuery('button[type=submit]').attr('disabled','true');

        
    //If other payment methods are selected, hide all svea related    
    }else{
        jQuery('#sveaFaktField').hide();
        jQuery('#sveaDelbetField').hide();
    }
    
    });
});

//Get adress, invoice
function getAdress(){
 
    var sveaPnr = jQuery('#sveaPnr').val();
    var company = jQuery('#sveaIsCompany').val();

    if (sveaPnr == ''){
        jQuery('#pers_nr_error_fakt').html('Personnr/Orgnr m�ste fyllas i');
    }else{
        
        //Show loader
        $('#sveaPnr').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');
        
        jQuery.ajax({
    	  type: "POST",
    	  url: "sveaAjax.php",
    	  data: {sveapnr: sveaPnr, is_company: company},
    	  success: function(msg){
    	      jQuery('#pers_nr_error_fakt').empty();
              eval(msg);
              $('#SveaInvoiceLoader').remove();
    		}
    	});
    }
    
}

//Get adress, PartPay
function getAdressPP(fin){
    
    var sveaPnr = jQuery('#sveaPnrPP').val();
    
    if (sveaPnr == ''){
        jQuery('#pers_nr_errorPP').html('Personnr m�ste fyllas i');
    }else{
        
        //Show loader
        $('#sveaPnrPP').after('<img src="images/svea_indicator.gif" id="SveaPPLoader" />');
        
        jQuery.ajax({
    	  type: "POST",
    	  url: "sveaAjax.php",
    	  data: {sveapnr: sveaPnr, paymentOptions: '1', f: fin},
    	  success: function(msg){
    	      jQuery('#pers_nr_errorPP').empty();
              eval(msg);
              $('#SveaPPLoader').remove();
    		}
    	});
    }
}