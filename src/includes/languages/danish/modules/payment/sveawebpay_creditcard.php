<?php
/*
Svea PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE','Svea Kort');
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION','Svea Kort - version 4.0');
define('MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES','Et admininistrationsgebyr på %s vil blive lagt på ordren ved kassen.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flere af de tilladte valutaer er ikke præciserede. Dette skal ske, førend SweaWebPay Hosted Solution kan benyttes. Login på dit admin panel og tjek at alle valutaer er listede som tilladt i betalingsmodulet, og at de rigtige vekselkurser er indstillede korrekt.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutaen er ikke blandt de listede som tilladte. Login i dit admin panel og tjek, at standardvalutaen er på den tilladte liste i betalingsmodulet.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betaling mislykkedes.');  

define('ERROR_CODE_100','Indre systemfejl såsom at databaser er nede, ressourcer er ikke tilngængelige etc. kontakt integrator');
define('ERROR_CODE_105','Ugyldig transaktionsstatus');
define('ERROR_CODE_106','Fejl ved tredjepart f.eks. ved banken');
define('ERROR_CODE_107','Transaktion afvist af bank');
define('ERROR_CODE_108','Transaktion afbrudt');
define('ERROR_CODE_109','Transaktion ikke fundet ved banken');
define('ERROR_CODE_110','Ugyldigt transaktions-id');
define('ERROR_CODE_113','Betalingsmetode ikke konfigureret til forhandleren');
define('ERROR_CODE_114','Timeout hos banken');
define('ERROR_CODE_121','Kortet er udløbet');
define('ERROR_CODE_124','Beløbet overstiger kreditgrænsen');
define('ERROR_CODE_143','Kredit afslået af banken');

define('ERROR_CODE_DEFAULT', 'Fejl i behandling af betalingen. Oplys venligst kode ved henvendelse kundeservice. Fejlkode: ');
?>