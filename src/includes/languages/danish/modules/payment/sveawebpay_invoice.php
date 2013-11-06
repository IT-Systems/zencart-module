<?php
/*
Svea PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE','Svea Faktura');
define('MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION','Svea Faktura - version 4.0');
define('MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES','Et admininistrationsgebyr p� %s vil blive f�jet til ordren ved kassen.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flere af de tilladte valutaer er ikke pr�ciserede. Dette skal ske, f�rend SweaWebPay Hosted Solution kan benyttes. Login p� dit admin panel og tjek, at alle valutaer er listede som tilladte i betalingsmodulet, og at de rigtige vekselkurser er indstillede korrekt.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutaen er ikke blandt de listede som tilladte. Login i dit admin panel og tjek, at standardvalutaen er p� listen over tilladte valutaer i betalingsmodulet.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betaling mislykkedes.');  

define('ERROR_CODE_1','Kreditoplysninger kan ikke findes');
define('ERROR_CODE_2','Butikkens eller Sveas kreditlimit er overskredet');
define('ERROR_CODE_3','Denne kunde er blokeret eller har udvist m�rkv�rdig adf�rd');
define('ERROR_CODE_4','Ordren er for�ldet, og kan derfor ikke faktureres');
define('ERROR_CODE_5','Ordren vil overstige kundens kreditlimit hos Svea');
define('ERROR_CODE_6','Ordren overstiger Sveas maksimale ordresum');
define('ERROR_CODE_7','Ordren overstiger din tilladte ordresum.');
define('ERROR_CODE_8','Kunden har en d�rlig kredithistorik hos Svea');
define('ERROR_CODE_9','Kunden findes ikke hos kreditoplysningsleverand�ren.');
define('ERROR_CODE_DEFAULT', 'Fejl i behandling af betaling. Indre fejl');

//Form on checkout
define('FORM_TEXT_COMPANY_OR_PRIVATE','V�lg virksomhed/privat:');
define('FORM_TEXT_COMPANY','Virksomhed');
define('FORM_TEXT_PRIVATE','Privat');
define('FORM_TEXT_SS_NO','SSN:');
define('FORM_TEXT_GET_ADDRESS','F� adresse');
define('FORM_TEXT_INVOICE_ADDRESS','Faktureringsadresse:');
define('FORM_TEXT_INVOICE_FEE','Faktureringsgebyr:');
?>