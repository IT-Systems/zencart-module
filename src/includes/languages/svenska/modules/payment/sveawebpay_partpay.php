<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','SVEA Delbetalning');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Delbetalning - version 4.0');
define('MODULE_PAYMENT_SWPPARTPAY_HANDLING_APPLIES','En expeditionsavgift på %s tillkommer på ordern.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flera av de tillåtna valutorna är ej definierade. Dessa måste vara definierade för att kunna använda SweaWebPay Hosted Solution. Logga in till din admin-panel, och säkerställ att alla de tillåtna valutorna i payment-modulen existerar, och att de korrekta växelkursera är satta.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutan är ej med i listan av de tillåtna. Logga in till your admin-panel, och säkerställ att standardvalutan finns med bland de tillåtna i payment-modulen.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betalningen misslyckades.');  

define('ERROR_CODE_1','Kreditinformation kan inte hämtas');
define('ERROR_CODE_2','Butikens eller Sveas kreditgräns överskriden');
define('ERROR_CODE_3','Kund blockerad eller har uppvisat ovanligt beteende hos kreditupplysare');
define('ERROR_CODE_4','Delbetalning avbruten');
define('ERROR_CODE_5','Denna order skulle orsaka att kreditgränsen överskrids');
define('ERROR_CODE_6','Kreditgränsen för lån har överskridits');
define('ERROR_CODE_7','Kampanjkod och summa matchar ej');
define('ERROR_CODE_8','Kunden har dålig kredithistoria hos SVEA');
define('ERROR_CODE_9','Kund ej listad');
define('ERROR_CODE_DEFAULT', 'Fel vid betalning, intern error');

//Form on checkout

define('FORM_TEXT_PARTPAY_ADDRESS','Faktureringsadress:');
define('FORM_TEXT_PAYMENT_OPTIONS','Delbetalningsalternativ:');

// used in payment credentials form
define('FORM_TEXT_GET_PAY_OPTIONS','Hämta betalningsalternativ');
define('FORM_TEXT_SS_NO','Personnr:');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','Födelsedatum (YYYYMMDD)');              
define('FORM_TEXT_VATNO','Organisationsnummer'); 
define('FORM_TEXT_PARTPAY_FEE','Uppläggningsavgift tillkommer');
define('FORM_TEXT_GET_PAYPLAN','Hämta betalningsalternativ:');

define('ERROR_CODE_DEFAULT','Svea Error: ');
?>