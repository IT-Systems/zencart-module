<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','Svea Delbetalning');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Delbetalning - version 4.0');
define('MODULE_PAYMENT_SWPPARTPAY_HANDLING_APPLIES','En expeditionsavgift p� %s tillkommer p� ordern.');
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

define('ERROR_CODE_27000','Delbetalningssumman matchar ej n�gon kampanj som �r knuten till kontot.');
define('ERROR_CODE_27001','Det g�r ej att leverera order f�r en pdf saknas. Kontakta SveaWebPay�s support');
define('ERROR_CODE_27002','Det g�r ej att delleverera en delbetalning');
define('ERROR_CODE_27003','Det g�r ej att kombinera CampaignCode med en fast summa.');
define('ERROR_CODE_27004','Det g�r ej att hitta en passande kampanjkod f�r den angivna summan');

define('ERROR_CODE_30000','Krediteringen nekades');
define('ERROR_CODE_30001','Kunden �r blockerad eller har uppvisat udda k�pbeteende.');
define('ERROR_CODE_30002','Ordern nekades baserat p� kreditupplysningen.');
define('ERROR_CODE_30003','Det g�r ej att hitta kunden i kreditupplysningen.');

define('ERROR_CODE_40000','Det g�r ej att hitta kunden');
define('ERROR_CODE_40001','Landskoden st�ds ej');
define('ERROR_CODE_40002','Ogiltiga kunduppgifter');
define('ERROR_CODE_40004','Det gick ej att hitta n�gra adresser f�r den h�r kunden');

define('ERROR_CODE_50000','Kunden �r ej godk�nd f�r denna metod');

define('DD_NO_CAMPAIGN_ON_AMOUNT','Det g�r ej att hitta en passande kampanjkod f�r den angivna summan');


// used in payment credentials form
define('FORM_TEXT_PARTPAY_ADDRESS','Faktureringsadress:');
define('FORM_TEXT_PAYMENT_OPTIONS','Delbetalningsalternativ:');

define('FORM_TEXT_GET_PAY_OPTIONS','H�mta betalningsalternativ');
define('FORM_TEXT_SS_NO','Personnummer:');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','F�delsedatum (YYYYMMDD)');              
define('FORM_TEXT_VATNO','Organisationsnummer'); 
define('FORM_TEXT_PARTPAY_FEE','Uppl�ggningsavgift tillkommer');
define('FORM_TEXT_GET_PAYPLAN','H�mta betalningsalternativ:');

define('ERROR_CODE_DEFAULT','Svea Error: ');

?>