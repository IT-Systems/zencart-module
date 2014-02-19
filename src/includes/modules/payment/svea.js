/**
 * Svea WebPay Zencart module svea.js
 * javascript used to pass Svea WebPay payment method credentials et al
 *
 * Version 4.2.3 - Zen Cart
 * Kristian Grossman-Madsen
 */
var isReady; //global, undefined on first file inclusion

jQuery(document).ready(function (){

    if( !isReady ) { isReady = true;  // as svea.js is included w/both payplan & invoice, internetbank, only hook functions once.

    // store customerCountry as attribute in payment method selection radiobutton
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
        jQuery('#pmt-sveawebpay_internetbank').attr("sveaCustomerCountry",msg);
    });

    // if there are no radio buttons, we have only one payment method, handle that:
    if( jQuery("input[type=radio][name='payment']").length == 0) {

        if( jQuery("#sveaInvoiceField").length == 1 ) {
            showInvoiceFields();
        }
        if( jQuery("#sveaPartPayField").length == 1 ) {
            showPartpayFields();
        }
        if( jQuery("#sveaInternetbankField").length == 1 ) {
            showInternetbankFields();
        }
    }
    else {
    // first, uncheck all payment buttons
    jQuery("input[type=radio][name='payment']").prop('checked', false);

    // show fields depending on payment method selected
    jQuery("input[type=radio][name='payment']").click( function() {

        var checked_payment = jQuery("input:radio[name=payment]:checked").val();
        switch( checked_payment ) {

            // Svea invoice payment method selected
            case 'sveawebpay_invoice':
                showInvoiceFields();           
            break;

            // Svea partpay payment method selected
            case 'sveawebpay_partpay':
                showPartpayFields();
            break;

            // Svea internetbank payment method selected
            case 'sveawebpay_internetbank':
                showInternetbankFields();                
            break;

            //If other payment methods are selected, hide all svea related
            default:
                // show billing address if hidden
                showBillingAndInvoiceAddress();

                // hide svea payment methods
                jQuery('#sveaInternetbankField').hide();
                jQuery('#sveaInvoiceField').hide();
                jQuery('#sveaPartPayField').hide();
            break; //default:
        }
    });
   } 
   // show/hide private/company input fields depending on country
    jQuery("input[type=radio][name='sveaIsCompany'][value='false']").click( function() {    // show private
        jQuery('#sveaInitials_div').show();
        jQuery('#sveaBirthDate_div').show();
        jQuery('#sveaVatNo_div').hide();
                
        if( jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry") == "NO" ) {       // hide getAddress button
            jQuery('#sveaSubmitGetAddress').hide();
        }
    });

    jQuery("input[type=radio][name='sveaIsCompany'][value='true']").click( function() {     // company
        jQuery('#sveaInitials_div').hide();
        jQuery('#sveaBirthDate_div').hide();
        jQuery('#sveaVatNo_div').show();
                
        if( jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry") == "NO" ) {       // show getAddress button
            jQuery('#sveaSubmitGetAddress').show();
        }        
    });

    } // isReady
});

function showInvoiceFields() {
    
    // get customerCountry
    var customerCountry = jQuery('#pmt-sveawebpay_invoice').attr("sveaCustomerCountry");

    // hide getAddress button
    if( customerCountry == "NO" ) {       // hide getAddress button for NO (default)
        jQuery('#sveaSubmitGetAddress').hide();
    }

    // hide addresses
    hideBillingAndInvoiceAddress( customerCountry );

    // show input fields
    jQuery('#sveaInternetbankField').hide();
    jQuery('#sveaPartPayField').hide();
    jQuery('#sveaInvoiceField').show();

    // do getAddresses on button press
    jQuery("#sveaSubmitGetAddress").click( function(){
        getAddresses(   jQuery('#sveaSSN').val(),
                        "invoice",
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
}

function showPartpayFields() {
    
    // get customerCountry
    var customerCountry = jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry");

    // hide addresses
    hideBillingAndInvoiceAddress( customerCountry );

    // show input fields
    jQuery('#sveaInternetbankField').hide();
    jQuery('#sveaPartPayField').show();
    jQuery('#sveaInvoiceField').hide();

    // force getAddresses & show part payment options on ssn input
    jQuery("#sveaSubmitPaymentOptions").click( function(){
        getAddresses(   jQuery('#sveaSSNPP').val(),
                        "paymentplan",
                        "false", // partpay not available to companies
                        jQuery('#pmt-sveawebpay_partpay').attr("sveaCustomerCountry"),
                        "sveaAddressSelectorPP"
        );
    });                

    // get & show getPaymentOptions
    getPartPaymentOptions( customerCountry );

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
}

function showInternetbankFields() {   
    
    // get customerCountry
    var customerCountry = jQuery('#pmt-sveawebpay_internetbank').attr("sveaCustomerCountry");

    // show input fields
    jQuery('#sveaInternetbankField').show();
    jQuery('#sveaPartPayField').hide();
    jQuery('#sveaInvoiceField').hide();

    // get & show getBankPaymentOptions
    getBankPaymentOptions( customerCountry );
}

// hide billing, invoice address fields in getAddress countries
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

// show billing address if currently hidden
function showBillingAndInvoiceAddress() {
    jQuery('#checkoutPaymentHeadingAddress').show();
    jQuery('#checkoutBillto').show();
    jQuery('#checkoutPayment .floatingBox').show();
}

//
// new getAddresses() that uses the integration package
function getAddresses( ssn, paymentType, isCompany, countryCode, addressSelectorName ) {

    // Show loader
    jQuery('#sveaSSN').after('<img src="images/svea_indicator.gif" id="SveaInvoiceLoader" />');

    // Do getAddresses call
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: { SveaAjaxGetAddresses: true,
                sveaSSN: ssn,
                paymentType: paymentType,
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


function getPartPaymentOptions( countryCode ) {

    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: {
            SveaAjaxGetPartPaymentOptions: true,
            sveaCountryCode: countryCode
        },
        success: function(msg){
            jQuery( "#sveaPaymentOptionsPP" ).empty();
            jQuery( "#sveaPaymentOptionsPP" ).append(msg);
            jQuery( 'label[for="sveaPaymentOptionsPP"]' ).show();
            jQuery( "#sveaPaymentOptionsPP" ).show();
        }
    });
}

function getBankPaymentOptions( countryCode ) {

    // getBankPaymentOptions to display as radio buttons, w/preselected default
    jQuery.ajax({
        type: "POST",
        url: "sveaAjax.php",
        data: {
            SveaAjaxGetBankPaymentOptions: true,
            sveaAjaxCountryCode : countryCode
        },
        success: function( msg ){
            jQuery( "#sveaBankPaymentOptions" ).empty();
            jQuery( "#sveaBankPaymentOptions" ).append(msg);
            jQuery( "#sveaBankPaymentOptions" ).show();
        }
    });
}