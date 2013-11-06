<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','Svea Payment Plan');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Payment Plan Solution - version 4.0');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','One or more of the allowed currencies are not defined. This must be enabled in order to use the SweaWebPay Hosted Solution. Log in to your admin panel, and ensure that all currencies listed as allowed in the payment module exists, and that the correct exchange rates are set.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','The default currency is not among those listed as allowed. Log in to your admin panel, and ensure that the default currency is in the allowed list in the payment module.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Payment Failed.');  

//Nordic Error codes
define('ERROR_CODE_1','Cannot get credit rating information');
define('ERROR_CODE_2','Store or Sveas credit limit overused');
define('ERROR_CODE_3','This customer is blocked or has shown strange/unusual behavior');
define('ERROR_CODE_4','The order is too old and can no longer be invoiced against');
define('ERROR_CODE_5','The order would cause the client to exceed Sveas credit limit');
define('ERROR_CODE_6','The order exceeds the highest order amount permitted at Svea');
define('ERROR_CODE_7','The order exceeds your highest order amount permitted');
define('ERROR_CODE_8','The customer has a poor credit history at Svea');
define('ERROR_CODE_9','The customer is not listed with the credit limit supplier');
define('ERROR_CODE_DEFAULT', 'Error processing payment. Internal error');

//Eu error codes
define('ERROR_CODE_20001','Order is denied ');
define('ERROR_CODE_20002','Something is wrong with the order  ');
define('ERROR_CODE_20003','Order has expired  ');
define('ERROR_CODE_20004','Order does not exist  ');
define('ERROR_CODE_20005','Wrong Order Type  ');
define('ERROR_CODE_20006','InvalidAmount ');
define('ERROR_CODE_20007','Amount over SVEA limit ');
define('ERROR_CODE_20008','Amount over client limit ');
define('ERROR_CODE_20000','Order is already closed ');

define('ERROR_CODE_30000','The credit report was rejected');
define('ERROR_CODE_30001','This customer is blocked or has shown strange/unusual behavior');
define('ERROR_CODE_30002','The order would cause the client to exceed Sveas credit limit');

define('DD_PARTPAY_IN','Partpay in ');
define('DD_PAY_IN_THREE','Pay within 3 months');
define('DD_MONTHS',' months');
define('DD_CURRENY_PER_MONTH',' kr/month');

// used in payment credentials form
define('FORM_TEXT_COMPANY_OR_PRIVATE','Are you a private individual, or do you represent a company or organisation:');
define('FORM_TEXT_COMPANY','Company');
define('FORM_TEXT_PRIVATE','Private');
define('FORM_TEXT_SS_NO','Enter your Social Security Number (SSN):');
define('FORM_TEXT_GET_ADDRESS','Show and select invoice address.');
define('FORM_TEXT_INITIALS','Initials');                                // TODO translate/add to other language files
define('FORM_TEXT_BIRTHDATE','Date of Birth (YYYYMMDD)');               // TODO translate/add to other language files
define('FORM_TEXT_VATNO','Vat Number (NL2345234)');                     // TODO translate/add to other language files
define('FORM_TEXT_PARTPAY_ADDRESS','Select billing address to be used.');
define('FORM_TEXT_PARTPAY_FEE','Payment Plan Fee:');
define('FORM_TEXT_GET_PAYPLAN','Payment options:');
define('DD_NO_CAMPAIGN_ON_AMOUNT','Can not find a suitable CampaignCode for the given amount');
?>