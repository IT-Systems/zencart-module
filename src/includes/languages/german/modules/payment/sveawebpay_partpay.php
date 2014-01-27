<?php
/*
SVEA PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE','Svea Ratenkauf');
define('MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION','Svea Ratenkauf - version 4.0');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','One or more of the allowed currencies are not defined. This must be enabled in order to use the SweaWebPay Hosted Solution. Log in to your admin panel, and ensure that all currencies listed as allowed in the payment module exists, and that the correct exchange rates are set.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','The default currency is not among those listed as allowed. Log in to your admin panel, and ensure that the default currency is in the allowed list in the payment module.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Payment Failed.');  

//Eu error codes
define('ERROR_CODE_20000','Auftrag geschlossen');
define('ERROR_CODE_20001','Auftrag abgelehnt');
define('ERROR_CODE_20002','Fehler im Auftrag');
define('ERROR_CODE_20003','Auftrag abgelaufen');
define('ERROR_CODE_20004','Auftrag existiert nicht');
define('ERROR_CODE_20005','Auftragart diskrepanz');
define('ERROR_CODE_20006','Die Summe geltene alle Auftragzeilen können nicht null oder negativ sein');
define('ERROR_CODE_20013','Auftrag ist anhängig');

define('ERROR_CODE_27000','Die bereitsgestellte Kampagnekode-Betrag Kombination entspricht keiner Kampagnekode befestigt dieser Kunde');
define('ERROR_CODE_27001','Auftrag kann nicht geliefert werden seit angegebene Pdf Vorlage fehlt.Kontaktieren Sie Sveawebpay Support');
define('ERROR_CODE_27002','Die Zahlungsmethode kann nicht teilweise geliefert werden');
define('ERROR_CODE_27003','Kampagnekode kann nicht mit fixierte monatlichen Betrag gemischt werden.');
define('ERROR_CODE_27004','Keiner geeignete Kampagnekode für den monatlichen Betrag gefunden');

define('ERROR_CODE_30000','Die Bonitätsprüfung ist abgelehnt');
define('ERROR_CODE_30001','Die Kunde ist blockiert oder zeigt ungewöhnliche Verhalten');
define('ERROR_CODE_30002','Basiert der Bonitätsprüfung ist der Verlangen abgelehnt');
define('ERROR_CODE_30003','Die Kunde könnte nicht durch Bonitätsprüfung gefunden werden');

define('ERROR_CODE_40000','Die Kunde ist nicht gefunden');
define('ERROR_CODE_40001','Die bereitgestellte Landskode ist nicht unterstütz');
define('ERROR_CODE_40002','Ungültige Kundeninformationen');
define('ERROR_CODE_40004','Keiner Adresse geltene dieser Kunde gefunden');

define('ERROR_CODE_50000','Die Kunde ist nicht für dieser Verfahren zugelassen');

define('DD_NO_CAMPAIGN_ON_AMOUNT','Kein passende Kampagnen-Code für den Betrag ');

// used in payment credentials form
define('FORM_TEXT_PARTPAY_ADDRESS','Rechnungsadresse:');
define('FORM_TEXT_PAYMENT_OPTIONS','Zahlungsmöglichkeiten:');

define('FORM_TEXT_GET_PAY_OPTIONS','Zahlungsmöglichkeiten holen');
define('FORM_TEXT_SS_NO','Sozialversicherungsnummer:');
define('FORM_TEXT_INITIALS','Initialen');                                
define('FORM_TEXT_BIRTHDATE','Geburtsdatum');              
define('FORM_TEXT_VATNO','MwSt'); 
define('FORM_TEXT_PARTPAY_FEE','Ausgangsgebühr hinzukommen');
define('FORM_TEXT_GET_PAYPLAN','Adresse bekommen');

define('ERROR_CODE_DEFAULT','Svea Fehler: ');

?>