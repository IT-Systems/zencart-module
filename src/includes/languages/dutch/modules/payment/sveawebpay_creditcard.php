<?php
/*
SVEAWEBPAY PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE','Svea Creditcard');
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION','Svea Betalen met betaalpas/creditcard - version 4.0');
define('MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES','De Factuurkosten %s zullen worden toegevoegd');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','One or more of the allowed currencies are not defined. This must be enabled in order to use the SweaWebPay Hosted Solution. Log in to your admin panel, and ensure that all currencies listed as allowed in the payment module exists, and that the correct exchange rates are set.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','The default currency is not among those listed as allowed. Log in to your admin panel, and ensure that the default currency is in the allowed list in the payment module.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Payment Failed.');  

define('ERROR_CODE_100','Ongeldig. Neem contact op met de integrator');
define('ERROR_CODE_105','Ongeldige transactiestatus');
define('ERROR_CODE_106','Fout bij derde partij');
define('ERROR_CODE_107','Transactie afgewezen door de bank');
define('ERROR_CODE_108','Transactie geannulleerd');
define('ERROR_CODE_109','Transactie niet gevonden bij de bank');
define('ERROR_CODE_110','Invalid transaction ID');
define('ERROR_CODE_113','Payment method not configured for merchant');
define('ERROR_CODE_114','Timeout bij de bank');
define('ERROR_CODE_121','De geldigheidsdatum van de pas is verlopen');
define('ERROR_CODE_124','Amount exceeds the limit');
define('ERROR_CODE_143','Credit denied by bank');

define('ERROR_CODE_DEFAULT', 'Error processing payment. Please provide this code when contacting support. Error code: ');
?>