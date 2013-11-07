<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE','Svea Direktzahlung');
define('MODULE_PAYMENT_SWPINTERNETBANK_TEXT_DESCRIPTION','Svea Direktzahlung - version 4.0');
define('MODULE_PAYMENT_SWPINTERNETBANK_HANDLING_APPLIES','%s Bearbeitungsgebühr hinzukommen');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','One or more of the allowed currencies are not defined. This must be enabled in order to use the SweaWebPay Hosted Solution. Log in to your admin panel, and ensure that all currencies listed as allowed in the payment module exists, and that the correct exchange rates are set.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','The default currency is not among those listed as allowed. Log in to your admin panel, and ensure that the default currency is in the allowed list in the payment module.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Payment Failed.');  

define('ERROR_CODE_100','Zahlung erfolglos. Kontaktieren Sie der Integrator.');
define('ERROR_CODE_105','Ungültige Transaktionsstatus');
define('ERROR_CODE_106','Bankfehler');
define('ERROR_CODE_107','Transaktion bei der Bank abgebrochen');
define('ERROR_CODE_108','Transaktion abgebrochen');
define('ERROR_CODE_109','Transaktion nicht bei der Bank gefunden');
define('ERROR_CODE_110','Invalid transaction ID');
define('ERROR_CODE_113','Payment method not configured for merchant');
define('ERROR_CODE_114','Timeout bei der Bank');
define('ERROR_CODE_121','Karte gelüscht');
define('ERROR_CODE_124','Amount exceeds the limit');
define('ERROR_CODE_143','Credit denied by bank');

define('ERROR_CODE_DEFAULT', 'Error processing payment. Please provide this code when contacting support. Error code: ');
?>