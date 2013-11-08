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

//Eu error codes
define('ERROR_CODE_20000','Ordre lukket');
define('ERROR_CODE_20001','Ordre n�gtet');
define('ERROR_CODE_20002','Fejl ved ordre');
define('ERROR_CODE_20003','Ordre udl�bet');
define('ERROR_CODE_20004','Ordre eksisterer ikke');
define('ERROR_CODE_20005','Ordretype mismatch');
define('ERROR_CODE_20006','Summen af alle ordrer�kker kan ikke v�re nul eller negativ');
define('ERROR_CODE_20013','Ordre afventer');

define('ERROR_CODE_30000','Kreditrapporten blev afvist');
define('ERROR_CODE_30001','Kunden er sp�rret eller har udvist m�rkelig eller us�dvanlig adf�rd');
define('ERROR_CODE_30002','P� basis af det gennemf�rte kredittjek blev foresp�rgslen afvist');
define('ERROR_CODE_30003','Kunden kan ikke findes af kredittjek');

define('ERROR_CODE_40000','Ingen kunde fundet');
define('ERROR_CODE_40001','Den angivne Landekode underst�ttes ikke');
define('ERROR_CODE_40002','Ugyldig kundeinformation');
define('ERROR_CODE_40004','Kunne ikke finde adresse p� p�g�ldende kunde');

define('ERROR_CODE_50000','Kunden er ikke godkendt til denne handling');

//invoice specific
define('ERROR_CODE_24000','Bel�bsst�rrelse overstiger det tilladte');

// used in payment credentials form
define('FORM_TEXT_INVOICE_ADDRESS','Betalingsadresse');
define('FORM_TEXT_INVOICE_FEE','Faktureringsgebyr:');
define('FORM_TEXT_COMPANY','Virksomhed');
define('FORM_TEXT_PRIVATE','Privat');
define('FORM_TEXT_GET_ADDRESS','F� adresse');

define('FORM_TEXT_SS_NO','Cpr.nr:');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','F�dselsdato');
define('FORM_TEXT_VATNO','CVR-nummer'); 

define('ERROR_CODE_DEFAULT','Svea Error: ');

?>