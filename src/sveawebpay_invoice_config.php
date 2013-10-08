<?php

/**
 * functions common to both test, prod classes
 */
class ZenCartSveaConfigBase {
    /**
     * get a zencart configuration value from zencart db
     */
    protected function getZenCartConfigValue( $key ) { 
        global $db;

        // see install() below for config table schema:
        // "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
        // $db->Execute($common . ") values ('SveaWebPay Client no SV', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_SV', '75021', '', '6', '0', now())");
        
        $sql = "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = :key:";
        $sql = $db->bindVars($sql, ':key:', $key, 'string');  

        $result = $db->Execute($sql);                
        if ($result->RecordCount() > 0) {
          $value = $result->fields['configuration_value'];
        } else {
          $value = 'swp_error_record_not_found';
        }
        
        return $value;
    }
    
    /**
     * Converts "SE" to "SV" (sic!), as well as checks for unsupported countries.
     * 
     * @param string $country, iso3166 country code (two letter, i.e. SE,NO,DK et al
     * @return string $country, or false if unsupported country
     */
    protected function validateCountry( $country ) {
        $country = strtoupper($country);

        switch( $country ) {    
        case "SE": // for compatibility w/module 3.0 db entries fix
            $country = "SV";
            break;

        case "NO":
        case "DK":
        case "FI":
        case "DE":
        case "NL":
            break;

        default: // unrecognised country
            $country = false;
        }

        return $country;
    }
    
   /**
    * not implemented for invoice
    */
    public function getSecret($type, $country) {
        return null;
    }
       /**
    * not implemented for invoice
    */
    public function getMerchantId($type, $country) {
        // validate also handles SE => SV  
        return null;
    }
 
    /**
    * get the return value from your database or likewise
    * @param $type eg. HOSTED, INVOICE or PAYMENTPLAN
    * $param $country CountryCode eg. SE, NO, DK, FI, NL, DE
    */
    public function getClientNumber($type, $country) {
        // validate also handles SE => SV
        $country = $this->validateCountry( $country );     
        if( !$country ) throw new Exception('Invalid country for payment method.');
     
        $key = "MODULE_PAYMENT_SWPINVOICE_CLIENTNO_" . strtoupper ( $country );
        $myMerchantId = $this->getZenCartConfigValue( $key );       
        return $myMerchantId;
    }  
 
   /**
    * get the return value from your database or likewise
    * @param $type eg. HOSTED, INVOICE or PAYMENTPLAN
    * $param $country CountryCode eg. SE, NO, DK, FI, NL, DE
    */
    public function getPassword($type, $country) {
        // validate also handles SE => SV
        $country = $this->validateCountry( $country );     
        if( !$country ) throw new Exception('Invalid country for payment method.');
        
        $key = "MODULE_PAYMENT_SWPINVOICE_PASSWORD_" . strtoupper ( $country );
        $myPassword = $this->getZenCartConfigValue( $key );       
        return $myPassword;
    }
    
    /**
    * get the return value from your database or likewise
    * @param $type eg. HOSTED, INVOICE or PAYMENTPLAN
    * $param $country CountryCode eg. SE, NO, DK, FI, NL, DE
    */
    public function getUsername($type, $country) {
 
        // validate also handles SE => SV
        $country = $this->validateCountry( $country );     
        if( !$country ) throw new Exception('Invalid country for payment method.');
        
        $key = "MODULE_PAYMENT_SWPINVOICE_USERNAME_" . strtoupper ( $country );
        $myUsername = $this->getZenCartConfigValue( $key );       
        return $myUsername;
    }
}

class ZenCartSveaConfigProd extends ZenCartSveaConfigBase implements ConfigurationProvider {
     
    public function getEndPoint($type) {
        $type = strtoupper($type);
          if($type == "HOSTED"){
            return   Svea\SveaConfig::SWP_PROD_URL;
        }elseif($type == "INVOICE" || $type == "PAYMENTPLAN"){
             return Svea\SveaConfig::SWP_PROD_WS_URL;
        }  else {
           throw new Exception('Invalid type. Accepted values: INVOICE, PAYMENTPLAN or HOSTED');
        }
    }
}

class ZenCartSveaConfigTest extends ZenCartSveaConfigBase implements ConfigurationProvider {

    public function getEndPoint($type) {
        $type = strtoupper($type);
        
        if($type == "HOSTED"){
            return   Svea\SveaConfig::SWP_TEST_URL;;
        }elseif($type == "INVOICE" || $type == "PAYMENTPLAN"){
             return Svea\SveaConfig::SWP_TEST_WS_URL;
        }  else {
           throw new Exception('Invalid type. Accepted values: INVOICE, PAYMENTPLAN or HOSTED');
        }
    }    
}
?>