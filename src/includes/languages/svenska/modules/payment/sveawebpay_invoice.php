<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE','Svea Faktura');
define('MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION','Svea Faktura - version 4.0');
define('MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES','En expeditionsavgift på %s tillkommer på ordern.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flera av de tillåtna valutorna är ej definierade. Dessa måste vara definierade för att kunna använda SweaWebPay Hosted Solution. Logga in till din admin-panel, och säkerställ att alla de tillåtna valutorna i payment-modulen existerar, och att de korrekta växelkursera är satta.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutan är ej med i listan av de tillåtna. Logga in till your admin-panel, och säkerställ att standardvalutan finns med bland de tillåtna i payment-modulen.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betalningen misslyckades.');  

//Eu error codes
define('ERROR_CODE_20000','Ordern är stängd');
define('ERROR_CODE_20001','Ordern är nekad');
define('ERROR_CODE_20002','Något gick fel med ordern');
define('ERROR_CODE_20003','Ordern har gått ut');
define('ERROR_CODE_20004','Ordern existerar ej');
define('ERROR_CODE_20005','Ordertyperna matchar ej');
define('ERROR_CODE_20006','Summan av orderraderna kan ej vara noll eller negativ');
define('ERROR_CODE_20013','Ordern väntar');

define('ERROR_CODE_30000','Kreditupplysningen avslogs');
define('ERROR_CODE_30001','Kunden är blockerad eller har uppvisat udda köpbeteende.');
define('ERROR_CODE_30002','Ordern nekades baserat på kreditupplysningen.');
define('ERROR_CODE_30003','Det går ej att hitta kunden i kreditupplysningen.');

define('ERROR_CODE_40000','Det går ej att hitta kunden');
define('ERROR_CODE_40001','Landskoden stöds ej');
define('ERROR_CODE_40002','Ogiltiga kunduppgifter');
define('ERROR_CODE_40004','Det gick ej att hitta några adresser för den här kunden');

define('ERROR_CODE_50000','Kunden är ej godkänd för denna metod');

//invoice specific
define('ERROR_CODE_24000','Fakturabeloppet överstiger tillåtet orderbelopp.');

// used in payment credentials form
define('FORM_TEXT_INVOICE_ADDRESS','Välj fakturaadress');
define('FORM_TEXT_INVOICE_FEE','Fakturaavgift:');
define('FORM_TEXT_COMPANY','Företag');
define('FORM_TEXT_PRIVATE','Privat');
define('FORM_TEXT_GET_ADDRESS','Hämta adress');

define('FORM_TEXT_SS_NO','Personnummer (YYYYMMDD-XXXX):');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','Födelsedatum');              
define('FORM_TEXT_VATNO','Organisationsnummer');

define('ERROR_CODE_DEFAULT','Svea Error: ');

// Tupas-Api -related definitions
define('FORM_TEXT_TUPAS_AUTHENTICATE','Authenticate on online bank');
define('ERROR_TAMPERED_PARAMETERS', 'Unexpected error occurred during authentication. Please, try again.');
define('ERROR_TUPAS_NOT_SET', 'You have to authenticate yourself in online bank.');
define('ERROR_TUPAS_MISMATCH', 'The SSN doesn\'t match with the one that Tupas authentication sent. Please, try again.');

?>