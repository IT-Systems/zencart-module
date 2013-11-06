<?php
/*
SVEAWEBPAY PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','SVEA Delbetaling');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','SveaWebPay Webservice Delbetaling - vers. 3.0');
define('MODULE_PAYMENT_SWPPARTPAY_HANDLING_APPLIES','Et admininistrationsgebyr p� %s vil blive f�jet til ordren ved kassen.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flere af de tilladte valutaer er ikke pr�ciserede. Dette skal ske, f�rend SweaWebPay Hosted Solution kan benyttes. Login p� dit admin panel og tjek, at alle valutaer er listede som tilladt i betalingsmodulet, og at de rigtige vekselkurser er indstillede korrekt.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutaen er ikke blandt de listede som tilladte. Login i dit admin panel og tjek, at standardvalutaen er p� listen over tilladte valutaer i betalingsmodulet.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betaling mislykkedes.');  

define('ERROR_CODE_1','Kreditoplysninger kan ikke findes');
define('ERROR_CODE_2','Butikkens eller Sveas kreditlimit er overskredet');
define('ERROR_CODE_3','Denne kunde er blokeret eller har udvist m�rkv�rdig adf�rd');
define('ERROR_CODE_4','Delbetaling afbrudt');
define('ERROR_CODE_5','Med denne ordre vil kunden overskride sin kreditlimit hos Svea');
define('ERROR_CODE_6','Kreditgr�nsen for lejlighedsvise l�n er overskredet.');
define('ERROR_CODE_7','Kombinationen af kampagnekoder og bel�b er ukorrekt.');
define('ERROR_CODE_8','Kunden har en d�rlig kredithistorik hos Svea');
define('ERROR_CODE_9','Kunden er ikke listet hos kreditlimit-udbyderen');
define('ERROR_CODE_DEFAULT', 'Fejl i behandling af betaling. Indre fejl');

//Form on checkout
define('FORM_TEXT_SS_NO','SSN:');
define('FORM_TEXT_GET_ADDRESS','F� adresse og betalingsmuligheder');
define('FORM_TEXT_GET_PAY_OPTIONS','F� betalingsmuligheder');
define('FORM_TEXT_INVOICE_ADDRESS','Faktureringsadresse:');
define('FORM_TEXT_PAYMENT_OPTIONS','Betalingsmuligheder:');

define('DD_PARTPAY_IN','Delbetal i ');
define('DD_PAY_IN_THREE','Betal over 3 m�neder');
define('DD_MONTHS',' m�neder');
define('DD_CURRENY_PER_MONTH',' kr./m�ned');
define('DD_NO_CAMPAIGN_ON_AMOUNT','Kan ikke finde en egnet Kampagnekode for for den angivne sum');
?>