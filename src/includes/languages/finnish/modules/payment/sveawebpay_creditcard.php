<?php
/*
SVEA PAYMENT MODULE FOR Zencart
-----------------------------------------------
Version 3.0
*/
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE','Svea Korttimaksu');
define('MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION','Svea korttimaksu - ver 4.0');
define('MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES','tilaukseen lisätään toimitusmaksu');
define('ERROR_ALLOWED_CURRENCIES_NOT_DEFINED','Yksi tai useampi sallituista valuutoista ei ole määritelty. Nämä on määriteltävä käyttämään SveaWebPay hosted solutionia. Kirjaudu admin paneeliin ja varmista että.');
define('ERROR_DEFAULT_CURRENCY_NOT_ALLOWED','Oletusvaluuta ei ole sallitussa luettelossa. Kirjaudu admin paneeliin ja varmista että oletusvaluutta kuuluu niihin sallituihin maksu-moduulissa.');  
define('ERROR_MESSAGE_PAYMENT_FAILED','Maksu epäonnistui');  

define('ERROR_CODE_100','Sisäinen  järjestelmävirhe');
define('ERROR_CODE_105','Virheellinen tila tapahtuma');
define('ERROR_CODE_106','Kolmannen puolen epäonnistuminen, esimerkiksi pankissa');
define('ERROR_CODE_107','Pankki hylkäsi liiketoiminnan');
define('ERROR_CODE_108','Liiketoiminta keskeytetty');
define('ERROR_CODE_109','Liiketoimintaa ei löydy pankista');
define('ERROR_CODE_110','Virheellinen asiointitunnus');
define('ERROR_CODE_113','Tämän liiketoiminnan maskuvaihtoehto ei ole määritetty');
define('ERROR_CODE_114','Timeout pankissa');
define('ERROR_CODE_121','Kortin voimassaolo on päätynyt');
define('ERROR_CODE_124','Hankinnan määrä on ylitetty');
define('ERROR_CODE_143','Pankki hylkäsi luoton');

define('ERROR_CODE_DEFAULT', 'Tuntematon virhe on tapahtunut tilauksen käsittelyssä. Ilmoittakaa tämä koodi kun otatte yhteytä asiakaspalveluun. Virhenumero: ');
?>