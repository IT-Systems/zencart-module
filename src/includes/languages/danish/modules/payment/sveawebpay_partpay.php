<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','Svea Delbetal');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Delbetal - version 4.0');
define('MODULE_PAYMENT_SWPPARTPAY_HANDLING_APPLIES','Et admininistrationsgebyr på %s vil blive føjet til ordren ved kassen.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flere af de tilladte valutaer er ikke præciserede. Dette skal ske, førend SweaWebPay Hosted Solution kan benyttes. Login på dit admin panel og tjek, at alle valutaer er listede som tilladt i betalingsmodulet, og at de rigtige vekselkurser er indstillede korrekt.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutaen er ikke blandt de listede som tilladte. Login i dit admin panel og tjek, at standardvalutaen er på listen over tilladte valutaer i betalingsmodulet.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betaling mislykkedes.');  

//Eu error codes
define('ERROR_CODE_20000','Ordre lukket');
define('ERROR_CODE_20001','Ordre nægtet');
define('ERROR_CODE_20002','Fejl ved ordre');
define('ERROR_CODE_20003','Ordre udløbet');
define('ERROR_CODE_20004','Ordre eksisterer ikke');
define('ERROR_CODE_20005','Ordretype mismatch');
define('ERROR_CODE_20006','Summen af alle ordrerækker kan ikke være nul eller negativ');
define('ERROR_CODE_20013','Ordre afventer');

define('ERROR_CODE_27000','Kombinationen af det angivne kampagnekode-beløb matcher ikke nogen kampagnekode knyttet til denne kunde');
define('ERROR_CODE_27001','Kan ikke levere ordre, da den udspecificerede pdf-skabelon mangler. Kontakt SveaWebPays supportafdeling');
define('ERROR_CODE_27002','Kan ikke levere Betalingsplan delvist');
define('ERROR_CODE_27003','Kampagnekode kan ikke benyttes sammen med fast Månedlig Betaling.');
define('ERROR_CODE_27004','Kan ikke finde en egnet Kampagnekode for den Månedlige Betaling');

define('ERROR_CODE_30000','Kreditrapporten blev afvist');
define('ERROR_CODE_30001','Kunden er spærret eller har udvist mærkelig eller usædvanlig adfærd');
define('ERROR_CODE_30002','På basis af det gennemførte kredittjek blev forespørgslen afvist');
define('ERROR_CODE_30003','Kunden kan ikke findes af kredittjek');

define('ERROR_CODE_40000','Ingen kunde fundet');
define('ERROR_CODE_40001','Den angivne Landekode understøttes ikke');
define('ERROR_CODE_40002','Ugyldig kundeinformation');
define('ERROR_CODE_40004','Kunne ikke finde adresse på pågældende kunde');

define('ERROR_CODE_50000','Kunden er ikke godkendt til denne handling');

define('DD_NO_CAMPAIGN_ON_AMOUNT','Kan ikke finde en egnet Kampagnekode for for den angivne sum');

// used in payment credentials form
define('FORM_TEXT_PARTPAY_ADDRESS','Betalingsadresse:');
define('FORM_TEXT_PAYMENT_OPTIONS','Betalingsmuligheder:');

define('FORM_TEXT_GET_PAY_OPTIONS','Hente betalingsmuligheder');
define('FORM_TEXT_SS_NO','Cpr.nr:');
define('FORM_TEXT_INITIALS','Initialer');                                
define('FORM_TEXT_BIRTHDATE','Fødselsdato');              
define('FORM_TEXT_VATNO','CVR-nummer'); 
define('FORM_TEXT_PARTPAY_FEE','Indledende gebyr vil blive tilføjet.');
define('FORM_TEXT_GET_PAYPLAN','Hente adresse:');
define('FORM_TEXT_FROM','Fra');
define('FORM_TEXT_MONTH','m�ned');


define('ERROR_CODE_DEFAULT','Svea Error: ');
?>