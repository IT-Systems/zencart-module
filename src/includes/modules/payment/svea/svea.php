<?php
//Switch user and pass for different countries, Invoice
function getCountryConfigInvoice($currency,$country = null){
    switch ($currency) {
        case 'SEK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPINVOICE_USERNAME_SV,
                                 "password" => MODULE_PAYMENT_SWPINVOICE_PASSWORD_SV,
                                 "clientno" => MODULE_PAYMENT_SWPINVOICE_CLIENTNO_SV,
                                 "countryCode" => 'SE');
        break;
        case 'NOK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPINVOICE_USERNAME_NO,
                                 "password" => MODULE_PAYMENT_SWPINVOICE_PASSWORD_NO,
                                 "clientno" => MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NO,
                                 "countryCode" => 'NO');
        break;
        case 'DKK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPINVOICE_USERNAME_DK,
                                 "password" => MODULE_PAYMENT_SWPINVOICE_PASSWORD_DK,
                                 "clientno" => MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DK,
                                 "countryCode" => 'DK');
        break;
        case 'EUR' && $country == 'FI':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPINVOICE_USERNAME_FI,
                                 "password" => MODULE_PAYMENT_SWPINVOICE_PASSWORD_FI,
                                 "clientno" => MODULE_PAYMENT_SWPINVOICE_CLIENTNO_FI,
                                 "countryCode" => 'FI');
        
        case 'EUR' && $country == 'NL':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPINVOICE_USERNAME_NL,
                                 "password" => MODULE_PAYMENT_SWPINVOICE_PASSWORD_NL,
                                 "clientno" => MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NL,
                                 "countryCode" => 'NL');
        break;
    }
    
    return $sveaConfig;
}

//Switch user and pass for different countries, PartPay
function getCountryConfigPP($currency,$country = null){
    switch ($currency) {
        case 'SEK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPPARTPAY_USERNAME_SV,
                                 "password" => MODULE_PAYMENT_SWPPARTPAY_PASSWORD_SV,
                                 "clientno" => MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SV,
                                 "countryCode" => 'SE');
        break;
        case 'NOK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPPARTPAY_USERNAME_NO,
                                 "password" => MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NO,
                                 "clientno" => MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO,
                                 "countryCode" => 'NO');
        break;
        case 'DKK':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPPARTPAY_USERNAME_DK,
                                 "password" => MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DK,
                                 "clientno" => MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK,
                                 "countryCode" => 'DK');
        break;
        case 'EUR' && $country == 'FI':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPPARTPAY_USERNAME_FI,
                                 "password" => MODULE_PAYMENT_SWPPARTPAY_PASSWORD_FI,
                                 "clientno" => MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI,
                                 "countryCode" => 'FI');
        
        case 'EUR' && $country == 'NL':
            $sveaConfig = array ("username" => MODULE_PAYMENT_SWPPARTPAY_USERNAME_NL,
                                 "password" => MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NL,
                                 "clientno" => MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL,
                                 "countryCode" => 'NL');
        break;
    }
    
    return $sveaConfig;
}
?>