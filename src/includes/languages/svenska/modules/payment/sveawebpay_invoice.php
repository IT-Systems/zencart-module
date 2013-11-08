<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE','Svea Faktura');
define('MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION','Svea Faktura - version 4.0');
define('MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES','En expeditionsavgift p� %s tillkommer p� ordern.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flera av de till�tna valutorna �r ej definierade. Dessa m�ste vara definierade f�r att kunna anv�nda SweaWebPay Hosted Solution. Logga in till din admin-panel, och s�kerst�ll att alla de till�tna valutorna i payment-modulen existerar, och att de korrekta v�xelkursera �r satta.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutan �r ej med i listan av de till�tna. Logga in till your admin-panel, och s�kerst�ll att standardvalutan finns med bland de till�tna i payment-modulen.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betalningen misslyckades.');  

//Eu error codes
define('ERROR_CODE_20000','Ordern �r st�ngd');
define('ERROR_CODE_20001','Ordern �r nekad');
define('ERROR_CODE_20002','N�got gick fel med ordern');
define('ERROR_CODE_20003','Ordern har g�tt ut');
define('ERROR_CODE_20004','Ordern existerar ej');
define('ERROR_CODE_20005','Ordertyperna matchar ej');
define('ERROR_CODE_20006','Summan av orderraderna kan ej vara noll eller negativ');
define('ERROR_CODE_20013','Ordern v�ntar');

define('ERROR_CODE_30000','Krediteringen nekades');
define('ERROR_CODE_30001','Kunden �r blockerad eller har uppvisat udda k�pbeteende.');
define('ERROR_CODE_30002','Ordern nekades baserat p� kreditupplysningen.');
define('ERROR_CODE_30003','Det g�r ej att hitta kunden i kreditupplysningen.');

define('ERROR_CODE_40000','Det g�r ej att hitta kunden');
define('ERROR_CODE_40001','Landskoden st�ds ej');
define('ERROR_CODE_40002','Ogiltiga kunduppgifter');
define('ERROR_CODE_40004','Det gick ej att hitta n�gra adresser f�r den h�r kunden');

define('ERROR_CODE_50000','Kunden �r ej godk�nd f�r denna metod');

//invoice specific
define('ERROR_CODE_24000','Fakturabeloppet �verstiger till�tet orderbelopp.');

// used in payment credentials form
define('FORM_TEXT_INVOICE_ADDRESS','V�lj fakturaadress');
define('FORM_TEXT_INVOICE_FEE','Fakturaavgift:');
define('FORM_TEXT_COMPANY','F�retag');
define('FORM_TEXT_PRIVATE','Privat');
define('FORM_TEXT_GET_ADDRESS','H�mta adress');

define('FORM_TEXT_SS_NO','Personnummer (YYYYMMDD-XXXX):');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','F�delsedatum');              
define('FORM_TEXT_VATNO','Organisationsnummer');

define('ERROR_CODE_DEFAULT','Svea Error: ');

?>