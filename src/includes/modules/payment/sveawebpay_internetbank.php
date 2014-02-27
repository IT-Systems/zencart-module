<?php
/*
HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
-----------------------------------------------
Kristian Grossman-Madsen, Shaho Ghobadi
*/

// Include Svea php integration package files
require_once(DIR_FS_CATALOG . 'svea/Includes.php');  // use new php integration package for v4
require_once(DIR_FS_CATALOG . 'sveawebpay_config.php');                  // sveaConfig implementation

require_once(DIR_FS_CATALOG . 'sveawebpay_common.php');     // zencart module common functions

class sveawebpay_internetbank extends SveaZencart {

  function sveawebpay_internetbank() {
    global $order;

    $this->code = 'sveawebpay_internetbank';
    $this->version = "4.3.1";

    // used by card, directbank when posting form in checkout_confirmation.php
    $this->form_action_url = (MODULE_PAYMENT_SWPINTERNETBANK_MODE == 'Test') ? Svea\SveaConfig::SWP_TEST_URL : Svea\SveaConfig::SWP_PROD_URL;

    $this->title = MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_SWPINTERNETBANK_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_SWPINTERNETBANK_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_SWPINTERNETBANK_SORT_ORDER;
    $this->ignore_list = explode(',', MODULE_PAYMENT_SWPINTERNETBANK_IGNORE);
    if ((int)MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID > 0)
      $this->order_status = MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID;
    if (is_object($order)) $this->update_status();
  }

  function update_status() {
    global $db, $order;

    // do not use this module if the geograhical zone is set and we are not in it
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_SWPCREDITCARD_ZONE > 0) ) {
      $check_flag = false;
      $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SWPCREDITCARD_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

      while (!$check_query->EOF) {
        if ($check_query->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check_query->MoveNext();
      }

      if ($check_flag == false)
        $this->enabled = false;
    }
  }

  function javascript_validation() {
    return false;
  }

  // sets information displayed when choosing between payment options
  function selection() {
    global $order, $currencies;

    // get & store country code
    if( isset($order) ) {
        $_SESSION['sveaAjaxOrderTotal'] = $order->info['total'];
        $_SESSION['sveaAjaxCountryCode'] = $order->customer['country']['iso_code_2'];
    }

    $fields = array();

    // show bank logo
    if($order->customer['country']['iso_code_2'] == "SE"){
         $fields[] = array('title' => '<img src=images/Svea/SVEADIRECTBANK_SE.png />', 'field' => '');
    }
    else {
        $fields[] = array('title' => '<img src=images/Svea/SVEADIRECTBANK.png />', 'field' => '');
    }

    if (isset($_REQUEST['payment_error']) && $_REQUEST['payment_error'] == 'sveawebpay_internetbank') { // is set in before_process() on failed payment
        $fields[] = array('title' => '<span style="color:red">' . $_SESSION['SWP_ERROR'] . '</span>', 'field' => '');
    }

    // insert svea js
    $sveaJs =   '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>' .
                '<script type="text/javascript" src="' . $this->web_root . 'includes/modules/payment/svea.js"></script>';
    $fields[] = array('title' => '', 'field' => $sveaJs);

    // customer country is taken from customer settings
    $customer_country = $order->customer['country']['iso_code_2'];

    // fill in all fields as required to show available bank payment methods for selection
    $sveaBankPaymentOptions = '<div name="sveaBankPaymentOptions" id="sveaBankPaymentOptions"></div>';

    // create and add the field to be shown by our js when we select SveaInvoice payment method
    $sveaField =    '<div id="sveaInternetbankField" >' . //style="display:none">' .
                        $sveaBankPaymentOptions .
                    '</div>';

    $fields[] = array('title' => '', 'field' => '<br />' . $sveaField);

    // store order info needed to reconstruct amount pre coupon later
    $_SESSION["swp_order_info_pre_coupon"]  = serialize($order->info);

    return array( 'id'      => $this->code,
                  'module'  => $this->title,
                  'fields'  => $fields);
  }

  function pre_confirmation_check() {
    return false;
  }

  function confirmation() {
    return false;
  }

  function process_button() {

    global $db, $order, $order_totals, $language;

    // calculate the order number
    $new_order_rs = $db->Execute("select orders_id from " . TABLE_ORDERS . " order by orders_id desc limit 1");
    $new_order_field = $new_order_rs->fields;
    $client_order_number = ($new_order_field['orders_id'] + 1);

    // localization parameters
    if( isset( $order->billing['country']['iso_code_2'] ) ) {
        $user_country = $order->billing['country']['iso_code_2'];
    }
    // no billing address set, fallback to session country_id
    else {
        $country = zen_get_countries_with_iso_codes( $_SESSION['customer_country_id'] );
        $user_country =  $country['countries_iso_code_2'];
    }

    $user_language = $db->Execute("select code from " . TABLE_LANGUAGES . " where directory = '" . $language . "'");
    $user_language = $user_language->fields['code'];

    $currency = $order->info['currency'];

    // Create and initialize order object, using either test or production configuration
    $sveaConfig = (MODULE_PAYMENT_SWPINTERNETBANK_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

    $swp_order = WebPay::createOrder( $sveaConfig )
        ->setCountryCode( $user_country )
        ->setCurrency($currency)
        ->setClientOrderNumber($client_order_number)
        ->setOrderDate(date('c'))
    ;

        // create product order rows from each item in cart
    $swp_order = $this->parseOrderProducts( $order->products, $swp_order );

    // creates non-item order rows from Order Total entries
    $swp_order = $this->parseOrderTotals( $order_totals, $swp_order );


    // localization parameters
    if( isset( $order->billing['country']['iso_code_2'] ) ) {
        $user_country = $order->billing['country']['iso_code_2'];
    }
    // no billing address set, fallback to session country_id
    else {
        $country = zen_get_countries_with_iso_codes( $_SESSION['customer_country_id'] );
        $user_country =  $country['countries_iso_code_2'];
    }

    $payPageLanguage = "";
    switch ($user_country) {
    case "DE":
        $payPageLanguage = "de";
        break;
    case "NL":
        $payPageLanguage = "nl";
        break;
    case "SE":
        $payPageLanguage = "sv";
        break;
    case "NO":
        $payPageLanguage = "no";
        break;
    case "DK":
        $payPageLanguage = "da";
        break;
    case "FI":
        $payPageLanguage = "fi";
        break;
    default:
        $payPageLanguage = "en";
        break;
    }

    // go directly to selected bank
    $swp_form = $swp_order->usePaymentMethod( $_REQUEST['BankPaymentOptions'] )
        ->setCancelUrl( zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true) )
        ->setReturnUrl( zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') )
        ->getPaymentForm();

    //return $process_button_string;
    return  $swp_form->htmlFormFieldsAsArray['input_merchantId'] .
            $swp_form->htmlFormFieldsAsArray['input_message'] .
            $swp_form->htmlFormFieldsAsArray['input_mac'];

}

  function before_process() {
    global $order;

    if ($_REQUEST['response']){

        // localization parameters
        if (isset($order->billing['country']['iso_code_2'])) {
            $user_country = $order->billing['country']['iso_code_2'];
        }
        // no billing address set, fallback to session country_id
        else {
            $country = tep_get_countries_with_iso_codes($_SESSION['customer_country_id']);
            $user_country = $country['countries_iso_code_2'];
        }

        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINTERNETBANK_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_respObj = new SveaResponse( $_REQUEST, $user_country, $sveaConfig ); // returns HostedPaymentResponse
        $swp_response = $swp_respObj->response;

        // check for bad response
        if( $swp_response->resultcode === 0 ) {
            die('Response failed authorization. AC not valid or Response is not recognized');
        }

        // response ok, check if payment accepted
        else {

             // handle failed payments
            if ( $swp_response->accepted === 0 ){

                switch( $swp_response->resultcode ) { // will autoconvert from string, matching initial numeric part
                case 100:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_100);
                    break;
                case 105:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_105);
                    break;
                case 106:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_106);
                    break;
                case 107:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_107);
                    break;
                case 108:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_108);
                    break;
                case 109:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_109);
                    break;
                case 110:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_110);
                    break;
                case 113:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_113);
                    break;
                case 114:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_114);
                    break;
                case 121:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_121);
                    break;
                case 124:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_124);
                    break;
                case 143:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_143);
                    break;
                default:
                    $_SESSION['SWP_ERROR'] = sprintf("Svea error %s: %s", $swp_response->resultcode, ERROR_CODE_DEFAULT . $swp_response->resultcode);
                    break;
                }

                if (isset($_SESSION['payment_attempt'])) {  // prevents repeated payment attempts interpreted by zencart as slam attack
                    unset($_SESSION['payment_attempt']);
                }

                $payment_error_return = 'payment_error=sveawebpay_internetbank'; // used in conjunction w/SWP_ERROR to avoid reason showing up in url
                zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return) );
            }

            // handle successful payments
            else{

                // payment request succeded, store response in session
                if( $swp_response->accepted === 1 ) {

                    if (isset($_SESSION['SWP_ERROR'])) {
                        unset($_SESSION['SWP_ERROR']);
                    }

                    // (with direct bank payments, shipping and billing addresses are unchanged from customer entries)

                    // save the response object
                    $_SESSION["swp_response"] = serialize($swp_response);
                }
            }
        }
    }
  }

  // if payment accepted, insert order into database
  function after_process() {
       global $insert_id, $order;

       // retrieve response object from before_process()
       $swp_response = unserialize($_SESSION["swp_response"]);

       // insert zencart order into database
        $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
        $sql_data_array = array(
            'orders_id' => $insert_id,
            'orders_status_id' => $order->info['order_status'],
            'date_added' => 'now()',
            'customer_notified' => $customer_notification,
            'comments' =>
                'Accepted by Svea ' . date("Y-m-d G:i:s") . ' Security Number #: ' . $swp_response->transactionId .
                " ". $order->info['comments']
        );
       zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

       // clean up our session variables set during checkout   //$SESSION[swp_*
       unset($_SESSION['swp_order']);
       unset($_SESSION['swp_response']);

       return false;
  }

  // sets error message to the GET error value
  function get_error() {
    return array(
        'title' => ERROR_MESSAGE_PAYMENT_FAILED,
        'error' => stripslashes(urldecode($_GET['error'])));
  }

  // standard check if installed function
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SWPINTERNETBANK_STATUS'");
      $this->_check = !$check_rs->EOF;
    }
    return $this->_check;
  }

  // insert configuration keys here
  function install() {
    global $db;
    $common = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
    $db->Execute($common . ", set_function) values ('Enable Svea Direct Bank Payment Module', 'MODULE_PAYMENT_SWPINTERNETBANK_STATUS', 'True', '', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('Svea Direct Bank Merchant ID', 'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID', '', 'The Merchant ID', '6', '0', now())");
    $db->Execute($common . ") values ('Svea Direct Bank Secret Word', 'MODULE_PAYMENT_SWPINTERNETBANK_SW', '', 'The Secret word', '6', '0', now())");
    $db->Execute($common . ") values ('Svea Direct Bank Test Merchant ID', 'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID_TEST', '', 'The Merchant ID', '6', '0', now())");
    $db->Execute($common . ") values ('Svea Direct Bank Test Secret Word', 'MODULE_PAYMENT_SWPINTERNETBANK_SW_TEST', '', 'The Secret word', '6', '0', now())");
    $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPINTERNETBANK_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
    $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
    $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPINTERNETBANK_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
    $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPINTERNETBANK_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
    $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPINTERNETBANK_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
  }

  // standard uninstall function
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  // must perfectly match keys inserted in install function
  function keys() {
    return array(
        'MODULE_PAYMENT_SWPINTERNETBANK_STATUS',
        'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID',
        'MODULE_PAYMENT_SWPINTERNETBANK_SW',
        'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID_TEST',
        'MODULE_PAYMENT_SWPINTERNETBANK_SW_TEST',
        'MODULE_PAYMENT_SWPINTERNETBANK_MODE',
        'MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID',
        'MODULE_PAYMENT_SWPINTERNETBANK_IGNORE',
        'MODULE_PAYMENT_SWPINTERNETBANK_ZONE',
        'MODULE_PAYMENT_SWPINTERNETBANK_SORT_ORDER'
    );
  }

 /**
   *
   * @global type $currencies
   * @param float $value amount to convert
   * @param string $currency as three-letter $iso3166 country code
   * @param boolean $no_number_format if true, don't convert the to i.e. Swedish decimal indicator (",")
   *    Having a non-standard decimal may cause i.e. number conversion with floatval() to truncate fractions.
   * @return type
   */
    function convert_to_currency($value, $currency, $no_number_format = true) {
        global $currencies;

        // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
        $rounded_value = zen_round($value * $currencies->currencies[$currency]['value'], $currencies->currencies[$currency]['decimal_places']);

        return $no_number_format ? $rounded_value : number_format(  $rounded_value,
                                                                    $currencies->currencies[$currency]['decimal_places'],
                                                                    $currencies->currencies[$currency]['decimal_point'],
                                                                    $currencies->currencies[$currency]['thousands_point']);
    }

    function getCountryName( $iso3166 ) {

        // countrynames from https://github.com/johannesl/Internationalization, thanks!
        $countrynames = array(
            "AF"=>"Afghanistan",
            "AX"=>"\xc3\x85land Islands",
            "AL"=>"Albania",
            "DZ"=>"Algeria",
            "AS"=>"American Samoa",
            "AD"=>"Andorra",
            "AO"=>"Angola",
            "AI"=>"Anguilla",
            "AQ"=>"Antarctica",
            "AG"=>"Antigua and Barbuda",
            "AR"=>"Argentina",
            "AM"=>"Armenia",
            "AW"=>"Aruba",
            "AU"=>"Australia",
            "AT"=>"Austria",
            "AZ"=>"Azerbaijan",
            "BS"=>"Bahamas",
            "BH"=>"Bahrain",
            "BD"=>"Bangladesh",
            "BB"=>"Barbados",
            "BY"=>"Belarus",
            "BE"=>"Belgium",
            "BZ"=>"Belize",
            "BJ"=>"Benin",
            "BM"=>"Bermuda",
            "BT"=>"Bhutan",
            "BO"=>"Bolivia, Plurinational State of",
            "BQ"=>"Bonaire, Sint Eustatius and Saba",
            "BA"=>"Bosnia and Herzegovina",
            "BW"=>"Botswana",
            "BV"=>"Bouvet Island",
            "BR"=>"Brazil",
            "IO"=>"British Indian Ocean Territory",
            "BN"=>"Brunei Darussalam",
            "BG"=>"Bulgaria",
            "BF"=>"Burkina Faso",
            "BI"=>"Burundi",
            "KH"=>"Cambodia",
            "CM"=>"Cameroon",
            "CA"=>"Canada",
            "CV"=>"Cape Verde",
            "KY"=>"Cayman Islands",
            "CF"=>"Central African Republic",
            "TD"=>"Chad",
            "CL"=>"Chile",
            "CN"=>"China",
            "CX"=>"Christmas Island",
            "CC"=>"Cocos (Keeling) Islands",
            "CO"=>"Colombia",
            "KM"=>"Comoros",
            "CG"=>"Congo",
            "CD"=>"Congo, The Democratic Republic of the",
            "CK"=>"Cook Islands",
            "CR"=>"Costa Rica",
            "CI"=>"C\xc3\xb4te d'Ivoire",
            "HR"=>"Croatia",
            "CU"=>"Cuba",
            "CW"=>"Cura\xc3\xa7ao",
            "CY"=>"Cyprus",
            "CZ"=>"Czech Republic",
            "DK"=>"Denmark",
            "DJ"=>"Djibouti",
            "DM"=>"Dominica",
            "DO"=>"Dominican Republic",
            "EC"=>"Ecuador",
            "EG"=>"Egypt",
            "SV"=>"El Salvador",
            "GQ"=>"Equatorial Guinea",
            "ER"=>"Eritrea",
            "EE"=>"Estonia",
            "ET"=>"Ethiopia",
            "FK"=>"Falkland Islands (Malvinas)",
            "FO"=>"Faroe Islands",
            "FJ"=>"Fiji",
            "FI"=>"Finland",
            "FR"=>"France",
            "GF"=>"French Guiana",
            "PF"=>"French Polynesia",
            "TF"=>"French Southern Territories",
            "GA"=>"Gabon",
            "GM"=>"Gambia",
            "GE"=>"Georgia",
            "DE"=>"Germany",
            "GH"=>"Ghana",
            "GI"=>"Gibraltar",
            "GR"=>"Greece",
            "GL"=>"Greenland",
            "GD"=>"Grenada",
            "GP"=>"Guadeloupe",
            "GU"=>"Guam",
            "GT"=>"Guatemala",
            "GG"=>"Guernsey",
            "GN"=>"Guinea",
            "GW"=>"Guinea-Bissau",
            "GY"=>"Guyana",
            "HT"=>"Haiti",
            "HM"=>"Heard Island and McDonald Islands",
            "VA"=>"Holy See (Vatican City State)",
            "HN"=>"Honduras",
            "HK"=>"Hong Kong",
            "HU"=>"Hungary",
            "IS"=>"Iceland",
            "IN"=>"India",
            "ID"=>"Indonesia",
            "IR"=>"Iran, Islamic Republic of",
            "IQ"=>"Iraq",
            "IE"=>"Ireland",
            "IM"=>"Isle of Man",
            "IL"=>"Israel",
            "IT"=>"Italy",
            "JM"=>"Jamaica",
            "JP"=>"Japan",
            "JE"=>"Jersey",
            "JO"=>"Jordan",
            "KZ"=>"Kazakhstan",
            "KE"=>"Kenya",
            "KI"=>"Kiribati",
            "KP"=>"Korea, Democratic People's Republic of",
            "KR"=>"Korea, Republic of",
            "KW"=>"Kuwait",
            "KG"=>"Kyrgyzstan",
            "LA"=>"Lao People's Democratic Republic",
            "LV"=>"Latvia",
            "LB"=>"Lebanon",
            "LS"=>"Lesotho",
            "LR"=>"Liberia",
            "LY"=>"Libya",
            "LI"=>"Liechtenstein",
            "LT"=>"Lithuania",
            "LU"=>"Luxembourg",
            "MO"=>"Macao",
            "MK"=>"Macedonia, The Former Yugoslav Republic of",
            "MG"=>"Madagascar",
            "MW"=>"Malawi",
            "MY"=>"Malaysia",
            "MV"=>"Maldives",
            "ML"=>"Mali",
            "MT"=>"Malta",
            "MH"=>"Marshall Islands",
            "MQ"=>"Martinique",
            "MR"=>"Mauritania",
            "MU"=>"Mauritius",
            "YT"=>"Mayotte",
            "MX"=>"Mexico",
            "FM"=>"Micronesia, Federated States of",
            "MD"=>"Moldova, Republic of",
            "MC"=>"Monaco",
            "MN"=>"Mongolia",
            "ME"=>"Montenegro",
            "MS"=>"Montserrat",
            "MA"=>"Morocco",
            "MZ"=>"Mozambique",
            "MM"=>"Myanmar",
            "NA"=>"Namibia",
            "NR"=>"Nauru",
            "NP"=>"Nepal",
            "NL"=>"Netherlands",
            "NC"=>"New Caledonia",
            "NZ"=>"New Zealand",
            "NI"=>"Nicaragua",
            "NE"=>"Niger",
            "NG"=>"Nigeria",
            "NU"=>"Niue",
            "NF"=>"Norfolk Island",
            "MP"=>"Northern Mariana Islands",
            "NO"=>"Norway",
            "OM"=>"Oman",
            "PK"=>"Pakistan",
            "PW"=>"Palau",
            "PS"=>"Palestine, State of",
            "PA"=>"Panama",
            "PG"=>"Papua New Guinea",
            "PY"=>"Paraguay",
            "PE"=>"Peru",
            "PH"=>"Philippines",
            "PN"=>"Pitcairn",
            "PL"=>"Poland",
            "PT"=>"Portugal",
            "PR"=>"Puerto Rico",
            "QA"=>"Qatar",
            "RE"=>"R\xc3\xa9union",
            "RO"=>"Romania",
            "RU"=>"Russian Federation",
            "RW"=>"Rwanda",
            "BL"=>"Saint Barth\xc3\xa9lemy",
            "SH"=>"Saint Helena, Ascension and Tristan Da Cunha",
            "KN"=>"Saint Kitts and Nevis",
            "LC"=>"Saint Lucia",
            "MF"=>"Saint Martin (French part)",
            "PM"=>"Saint Pierre and Miquelon",
            "VC"=>"Saint Vincent and the Grenadines",
            "WS"=>"Samoa",
            "SM"=>"San Marino",
            "ST"=>"Sao Tome and Principe",
            "SA"=>"Saudi Arabia",
            "SN"=>"Senegal",
            "RS"=>"Serbia",
            "SC"=>"Seychelles",
            "SL"=>"Sierra Leone",
            "SG"=>"Singapore",
            "SX"=>"Sint Maarten (Dutch part)",
            "SK"=>"Slovakia",
            "SI"=>"Slovenia",
            "SB"=>"Solomon Islands",
            "SO"=>"Somalia",
            "ZA"=>"South Africa",
            "GS"=>"South Georgia and the South Sandwich Islands",
            "SS"=>"South Sudan",
            "ES"=>"Spain",
            "LK"=>"Sri Lanka",
            "SD"=>"Sudan",
            "SR"=>"Suriname",
            "SJ"=>"Svalbard and Jan Mayen",
            "SZ"=>"Swaziland",
            "SE"=>"Sweden",
            "CH"=>"Switzerland",
            "SY"=>"Syrian Arab Republic",
            "TW"=>"Taiwan, Province of China",
            "TJ"=>"Tajikistan",
            "TZ"=>"Tanzania, United Republic of",
            "TH"=>"Thailand",
            "TL"=>"Timor-Leste",
            "TG"=>"Togo",
            "TK"=>"Tokelau",
            "TO"=>"Tonga",
            "TT"=>"Trinidad and Tobago",
            "TN"=>"Tunisia",
            "TR"=>"Turkey",
            "TM"=>"Turkmenistan",
            "TC"=>"Turks and Caicos Islands",
            "TV"=>"Tuvalu",
            "UG"=>"Uganda",
            "UA"=>"Ukraine",
            "AE"=>"United Arab Emirates",
            "GB"=>"United Kingdom",
            "US"=>"United States",
            "UM"=>"United States Minor Outlying Islands",
            "UY"=>"Uruguay",
            "UZ"=>"Uzbekistan",
            "VU"=>"Vanuatu",
            "VE"=>"Venezuela, Bolivarian Republic of",
            "VN"=>"Viet Nam",
            "VG"=>"Virgin Islands, British",
            "VI"=>"Virgin Islands, U.S.",
            "WF"=>"Wallis and Futuna",
            "EH"=>"Western Sahara",
            "YE"=>"Yemen",
            "ZM"=>"Zambia",
            "ZW"=>"Zimbabwe"
        );

        return( array_key_exists( $iso3166, $countrynames) ? $countrynames[$iso3166] : $iso3166 );
    }
}
?>