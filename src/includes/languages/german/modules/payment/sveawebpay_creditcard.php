<?php
/*
SVEAWEBPAY PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 4.0
*/
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE','Svea Kartenzahlung');
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION','Svea Kartenzahlung - version 4.0');
define('MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES','Ein Behandlungsgebühr von %s wird in beim Checkout aufgebracht.');

define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','Eine oder mehrere Währungen sind nicht definiert. Diese muss aktiviert werden, um den Svea Methode zu verwenden');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Loggen Sie  in Ihrem Admin-Panel ein und um sicher zu sorgen, dass alle Währungen bei der Zahlungsmodul gelistet  und  erlaubt sind und auch dass die richtige Wechselkurse eingestellt sind');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Ausgefallende Zahlung.');   

define('ERROR_CODE_100','Zahlung erfolglos. Kontaktieren Sie der Integrator.');
define('ERROR_CODE_105','Ungültige Transaktionsstatus');
define('ERROR_CODE_106','Bankfehler');
define('ERROR_CODE_107','Transaktion bei der Bank abgebrochen');
define('ERROR_CODE_108','Transaktion abgebrochen');
define('ERROR_CODE_109','Transaktion nicht bei der Bank gefunden');
define('ERROR_CODE_110','Ungültige Transaktions-ID');
define('ERROR_CODE_113','Zahlungsmethode nicht beim Händler konfiguriert');
define('ERROR_CODE_114','Timeout bei der Bank');
define('ERROR_CODE_121','Karte gelüscht');
define('ERROR_CODE_124','Betrag überschreitet den Höchstbetrag');
define('ERROR_CODE_143','Kredit verweigert durch Bank');

define('ERROR_CODE_DEFAULT', 'Fehler bei der Zahlungsverarbeitung. Bitte geben Sie diesen Fehlercode ein, wenn Sie den Support kontaktieren,  Fehlercode: ');
?>