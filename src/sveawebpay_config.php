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
        case "SE":
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

    public function getClientNumber($type, $country) {

        $country = $this->validateCountry( $country );
        if( !$country ) throw new Svea\InvalidCountryException('Invalid country. Accepted countries: SE, NO, DK, FI, NL, DE');

        switch( strtoupper($type) ) {
        case "INVOICE":
            $key = "MODULE_PAYMENT_SWPINVOICE_CLIENTNO_" . strtoupper($country);
            break;
        case "PAYMENTPLAN":
            $key = "MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_"  . strtoupper($country);
            break;
        default:
            throw new Svea\InvalidTypeException('Invalid type. Accepted values: INVOICE, PAYMENTPLAN');
        }

        $myClientNumber = $this->getZenCartConfigValue( $key );
        return $myClientNumber;
    }

   /**
    * get the return value from your database or likewise
    * @param $type eg. HOSTED, INVOICE or PAYMENTPLAN
    * $param $country CountryCode eg. SE, NO, DK, FI, NL, DE
    */
    public function getPassword($type, $country) {
        $country = $this->validateCountry( $country );
         if( !$country ) throw new Svea\InvalidCountryException('Invalid country. Accepted countries: SE, NO, DK, FI, NL, DE');

        switch( strtoupper($type) ) {
        case "INVOICE":
            $key = "MODULE_PAYMENT_SWPINVOICE_PASSWORD_" . strtoupper($country);
            break;
        case "PAYMENTPLAN":
            $key = "MODULE_PAYMENT_SWPPARTPAY_PASSWORD_"  . strtoupper($country);
            break;
        default:
            throw new Svea\InvalidTypeException('Invalid type. Accepted values: INVOICE, PAYMENTPLAN');
        }

        $myPassword = $this->getZenCartConfigValue( $key );
        return $myPassword;
    }

    /**
    * get the return value from your database or likewise
    * @param $type eg. HOSTED, INVOICE or PAYMENTPLAN
    * $param $country CountryCode eg. SE, NO, DK, FI, NL, DE
    */
    public function getUsername($type, $country) {
        $country = $this->validateCountry( $country );
      if( !$country ) throw new Svea\InvalidCountryException('Invalid country. Accepted countries: SE, NO, DK, FI, NL, DE');

        switch( strtoupper($type) ) {
        case "INVOICE":
            $key = "MODULE_PAYMENT_SWPINVOICE_USERNAME_" . strtoupper($country);
            break;
        case "PAYMENTPLAN":
            $key = "MODULE_PAYMENT_SWPPARTPAY_USERNAME_"  . strtoupper($country);
            break;
        default:
            throw new Svea\InvalidTypeException('Invalid type. Accepted values: INVOICE, PAYMENTPLAN');
        }
        $myUsername = $this->getZenCartConfigValue( $key );
        return $myUsername;
    }
}

class ZenCartSveaConfigProd extends ZenCartSveaConfigBase implements ConfigurationProvider {

    public function getEndPoint($type) {
        switch( strtoupper($type) ) {
        case "HOSTED":
            return Svea\SveaConfig::SWP_PROD_URL;
            break;

        case "INVOICE":
        case "PAYMENTPLAN":
            return Svea\SveaConfig::SWP_PROD_WS_URL;
            break;

        case "HOSTED_ADMIN":
        return Svea\SveaConfig::SWP_PROD_HOSTED_ADMIN_URL;
            break;

        default:
            throw new Exception('Invalid type. Accepted values: INVOICE, PAYMENTPLAN, HOSTED, HOSTED_ADMIN');
            break;
        }
    }

    public function getSecret($type, $country) {
        $type = strtoupper($type);
        if($type == "HOSTED"){
            return MODULE_PAYMENT_SWPCREDITCARD_SW;
        }
        else {
            throw new Exception('Invalid type. Accepted values: HOSTED');
        }
    }

    public function getMerchantId($type, $country) {
        $type = strtoupper($type);
        if($type == "HOSTED"){
            return MODULE_PAYMENT_SWPCREDITCARD_MERCHANT_ID;
        }
        else {
            throw new Exception('Invalid type. Accepted values: HOSTED');
        }
    }

}

class ZenCartSveaConfigTest extends ZenCartSveaConfigBase implements ConfigurationProvider {

    public function getEndPoint($type) {
        switch( strtoupper($type) ) {
        case "HOSTED":
            return Svea\SveaConfig::SWP_TEST_URL;
            break;

        case "INVOICE":
        case "PAYMENTPLAN":
            return Svea\SveaConfig::SWP_TEST_WS_URL;
            break;

        case "HOSTED_ADMIN":
        return Svea\SveaConfig::SWP_TEST_HOSTED_ADMIN_URL;
            break;

        default:
            throw new Exception('Invalid type. Accepted values: INVOICE, PAYMENTPLAN, HOSTED, HOSTED_ADMIN');
            break;
        }
    }

    public function getSecret($type, $country) {
        $type = strtoupper($type);
        if($type == "HOSTED"){
            return MODULE_PAYMENT_SWPCREDITCARD_SW_TEST;
        }
        else {
            throw new Exception('Invalid type. Accepted values: HOSTED');
       }
    }

    public function getMerchantId($type, $country) {
        $type = strtoupper($type);
        if($type == "HOSTED"){
            return MODULE_PAYMENT_SWPCREDITCARD_MERCHANT_ID_TEST;
        }
        else {
            throw new Exception('Invalid type. Accepted values: HOSTED');
        }
    }
}
?>