<?php
/*
SVEAWEBPAY PAYMENT MODULE FOR ZenCart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE','SVEA Kortbetalning');
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION','SveaWebPay Kortbetalning Hostad - ver 3.0');
define('MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES','En expeditionsavgift p� %s tillkommer p� ordern.');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','En eller flera av de till�tna valutorna �r ej definierade. Dessa m�ste vara definierade f�r att kunna anv�nda SweaWebPay Hosted Solution. Logga in till din admin-panel, och s�kerst�ll att alla de till�tna valutorna i payment-modulen existerar, och att de korrekta v�xelkursera �r satta.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Standardvalutan �r ej med i listan av de till�tna. Logga in till your admin-panel, och s�kerst�ll att standardvalutan finns med bland de till�tna i payment-modulen.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Betalningen misslyckades.');  

define('ERROR_CODE_100','Internt systemfel');
define('ERROR_CODE_105','Felaktig transaktionsstatus');
define('ERROR_CODE_106','Fel hos tredjepart ex hos bank');
define('ERROR_CODE_107','Transaktion avvisad hos bank');
define('ERROR_CODE_108','Transaktion avbruten');
define('ERROR_CODE_109','Transaktion ej hittad hos bank');
define('ERROR_CODE_110','Ogiltigt transaktionsid');
define('ERROR_CODE_113','Betalmetod ej konfigurerad f�r denna butik');
define('ERROR_CODE_114','Timeout hos bank');
define('ERROR_CODE_121','Utg�ngsdatum f�r kort passerat');
define('ERROR_CODE_124','Belppet �verstiger maxgr�nsen f�r k�p');
define('ERROR_CODE_143','Kredit avvisad av bank');

define('ERROR_CODE_DEFAULT', 'Ett ok�nt fel har uppst�tt vid behandling av din betalning. Var v�nlig uppge denna kod n�r du kontaktar supporten. Felkod: ');
?>