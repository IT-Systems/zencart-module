<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','SVEA Delbetalning');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Delbetalning - version 4.0');
define('MODULE_PAYMENT_SWPPARTPAY_HANDLING_APPLIES','En expeditionsavgift p� %s tillkommer p� ordern.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flera av de till�tna valutorna �r ej definierade. Dessa m�ste vara definierade f�r att kunna anv�nda SweaWebPay Hosted Solution. Logga in till din admin-panel, och s�kerst�ll att alla de till�tna valutorna i payment-modulen existerar, och att de korrekta v�xelkursera �r satta.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutan �r ej med i listan av de till�tna. Logga in till your admin-panel, och s�kerst�ll att standardvalutan finns med bland de till�tna i payment-modulen.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betalningen misslyckades.');  

define('ERROR_CODE_1','Kreditinformation kan inte h�mtas');
define('ERROR_CODE_2','Butikens eller Sveas kreditgr�ns �verskriden');
define('ERROR_CODE_3','Kund blockerad eller har uppvisat ovanligt beteende hos kreditupplysare');
define('ERROR_CODE_4','Delbetalning avbruten');
define('ERROR_CODE_5','Denna order skulle orsaka att kreditgr�nsen �verskrids');
define('ERROR_CODE_6','Kreditgr�nsen f�r l�n har �verskridits');
define('ERROR_CODE_7','Kampanjkod och summa matchar ej');
define('ERROR_CODE_8','Kunden har d�lig kredithistoria hos SVEA');
define('ERROR_CODE_9','Kund ej listad');
define('ERROR_CODE_DEFAULT', 'Fel vid betalning, intern error');

//Form on checkout

define('FORM_TEXT_PARTPAY_ADDRESS','Faktureringsadress:');
define('FORM_TEXT_PAYMENT_OPTIONS','Delbetalningsalternativ:');

// used in payment credentials form
define('FORM_TEXT_GET_PAY_OPTIONS','H�mta betalningsalternativ');
define('FORM_TEXT_SS_NO','Personnr:');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','F�delsedatum (YYYYMMDD)');              
define('FORM_TEXT_VATNO','Organisationsnummer'); 
define('FORM_TEXT_PARTPAY_FEE','Uppl�ggningsavgift tillkommer');
define('FORM_TEXT_GET_PAYPLAN','H�mta betalningsalternativ:');

define('ERROR_CODE_DEFAULT','Svea Error: ');
?>