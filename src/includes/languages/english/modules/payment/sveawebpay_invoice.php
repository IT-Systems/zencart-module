<?php
/*
SVEAWEBPAY PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE','SVEA Invoice');
define('MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION','SveaWebPay Webservice Invoice - ver 4.0');
define('MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES','A handling fee of %s will be applied to this order on checkout.');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','One or more of the allowed currencies are not defined. This must be enabled in order to use the SweaWebPay Hosted Solution. Log in to your admin panel, and ensure that all currencies listed as allowed in the paymend module exists, and that the correct exchange rates are set.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','The default currency is not among those listed as allowed. Log in to your admin panel, and ensure that the default currency is in the allowed list in the payment module.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Payment Failed.');  

/**
 *  EU error codes
 *  See http://www.sveawebpay.se/PageFiles/229/webpay_eu_webservice.pdf "ResultCodes Table"
 * 
 *  Received from checkout_confirmation, via createOrder->doRequest response
 */
define('ERROR_CODE_20000','Order closed. ');
define('ERROR_CODE_20001','Order is denied. ');
define('ERROR_CODE_20002','Something is wrong with the order.  ');
define('ERROR_CODE_20003','Order has expired.  ');
define('ERROR_CODE_20004','Order does not exist.  ');
define('ERROR_CODE_20005','OrderType mismatch.  ');
define('ERROR_CODE_20006','The sum of all order rows cannot be zero or negative. ');
define('ERROR_CODE_20013','Order is pending ');

define('ERROR_CODE_24000', "Invoice amount exceeds the authorized amount");

define('ERROR_CODE_30000','The credit report was rejected');
define('ERROR_CODE_30001','The customer is blocked or has shown strange or unusual behavior');
define('ERROR_CODE_30002','Based upon the performed credit check the request was rejected');
define('ERROR_CODE_30003','Customer cannot be found by credit check');

define('ERROR_CODE_40000','No customer found');
define('ERROR_CODE_40001','The provided CountryCode is not supported');
define('ERROR_CODE_40002','Invalid Customer information');
define('ERROR_CODE_40004','Could not find any addresses for this customer');

define('ERROR_CODE_40000','Client is not authorized for this method');
define('ERROR_CODE_DEFAULT','Error: ');


// used in payment credentials form
define('FORM_TEXT_COMPANY_OR_PRIVATE','Are you a private individual, or do you represent a company or organisation:');
define('FORM_TEXT_COMPANY','Company');
define('FORM_TEXT_PRIVATE','Private');
define('FORM_TEXT_SS_NO','Enter your Social Security Number (SSN):');
define('FORM_TEXT_GET_ADDRESS','Show and select invoice address.');
define('FORM_TEXT_INITIALS','Initials');                                // TODO translate/add to other language files
define('FORM_TEXT_BIRTHDATE','Date of Birth (YYYYMMDD)');               // TODO translate/add to other language files
define('FORM_TEXT_VATNO','Vat Number (NL2345234)');                     // TODO translate/add to other language files
define('FORM_TEXT_INVOICE_ADDRESS','Select billing address to be used.');
define('FORM_TEXT_INVOICE_FEE','Invoice Fee:');
?>