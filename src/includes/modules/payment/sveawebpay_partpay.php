<?php

/*
  HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
  -----------------------------------------------
  Kristian Grossman-Madsen, Shaho Ghobadi
 */

// Include Svea php integration package files
require_once(DIR_FS_CATALOG . 'svea/Includes.php');         // use new php integration package for v4
require_once(DIR_FS_CATALOG . 'sveawebpay_config.php');     // sveaConfig inplementation

require_once(DIR_FS_CATALOG . 'sveawebpay_common.php');     // zencart module common functions

class sveawebpay_partpay extends SveaZencart{

    function sveawebpay_partpay() {
        global $order;

        $this->code = 'sveawebpay_partpay';
        $this->version = "4.3.1";

        $this->title = MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_SWPPARTPAY_STATUS == 'True') ? true : false);
        $this->sort_order = MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER;
        $this->sveawebpay_url = MODULE_PAYMENT_SWPPARTPAY_URL;
        $this->display_images = ((MODULE_PAYMENT_SWPPARTPAY_IMAGES == 'True') ? true : false);
        $this->ignore_list = explode(',', MODULE_PAYMENT_SWPPARTPAY_IGNORE);
        if ((int)MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID > 0)
            $this->order_status = MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID;
        if (is_object($order))
            $this->update_status();
        //when user click "edit" paymentplan payment we update params table
        if(isset($_GET['action']) && $_GET['action'] == "edit" &&  $_GET['set'] == "payment" &&  $_GET['module'] == "sveawebpay_partpay"){
            $this->svea_update_params();

        }
        
        // Tupas API related 
        $this->tupasapiurl = 'https://tupas.svea.fi/shops';
        $this->usetupas = ((MODULE_PAYMENT_SWPPARTPAY_USETUPAS_FI == 'True') ? true : false);
        $this->tupas_mode = MODULE_PAYMENT_SWPPARTPAY_TUPAS_MODE;
        $this->tupas_shop_token = MODULE_PAYMENT_SWPPARTPAY_TUPAS_SHOP_TOKEN;

        if ($this->tupas_settings_changed()) {
            $this->editShopInstance();
        }
    }


    function svea_update_params(){
        $countryCode = array("SE","NO","FI","DK","NL","DE");

          for($i=0;$i<sizeof($countryCode);$i++){
            if(constant('MODULE_PAYMENT_SWPPARTPAY_USERNAME_'.$countryCode[$i]) != "" && constant('MODULE_PAYMENT_SWPPARTPAY_PASSWORD_'.$countryCode[$i]) != "" && constant('MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_'.$countryCode[$i]) != ""){
                 $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();
                $svea_params = WebPay::getPaymentPlanParams($sveaConfig);
              try {
                   $svea_params = $svea_params
                          ->setCountryCode($countryCode[$i])
                              ->doRequest();

              } catch (Exception $e) {
                  //TODO: handle exception. Also refactor the rest of the module for try and catch
                      return NULL;
              }
                if(isset($svea_params->errormessage) == FALSE){
                   $formatted_params = $this->sveaFormatParams($svea_params);
                   if($formatted_params !=NULL){
                        $this->insertPaymentPlanParams($formatted_params,$countryCode[$i]);
                   }
                }

            }

        }



    }

        protected function insertPaymentPlanParams($params,$countryCode) {
            global $db;
            foreach ($params as $param) {
                //$query = $db->getQuery(true);
                $q = "INSERT INTO `svea_params_table`
                        (   `campaignCode` ,
                            `description`,
                            `paymentPlanType`,
                            `contractLengthInMonths`,
                            `monthlyAnnuityFactor`,
                            `initialFee`,
                            `notificationFee`,
                            `interestRatePercent`,
                            `numberOfInterestFreeMonths`,
                            `numberOfPaymentFreeMonths`,
                            `fromAmount`,
                            `toAmount`,
                            `timestamp`,
                            `countryCode`)
                            VALUES(";
                foreach ($param as $key => $value)
                    $q .= "'".$value."',";

                $q .= time().",";
                $q .= "'".$countryCode."'";
                $q .= ")";
                try{
                   $db->Execute($q);
                }catch (Exception $e) {

                }

            }


    }

          protected function sveaFormatParams($response){
            $result = array();
            if ($response == null) {
                return $result;
            } else {
                foreach ($response->campaignCodes as $responseResultItem) {
                    try {
                        $campaignCode = (isset($responseResultItem->campaignCode)) ? $responseResultItem->campaignCode : "";
                        $description = (isset($responseResultItem->description)) ? $responseResultItem->description : "";
                        $paymentplantype = (isset($responseResultItem->paymentPlanType)) ? $responseResultItem->paymentPlanType : "";
                        $contractlength = (isset($responseResultItem->contractLengthInMonths)) ? $responseResultItem->contractLengthInMonths : "";
                        $monthlyannuityfactor = (isset($responseResultItem->monthlyAnnuityFactor)) ? $responseResultItem->monthlyAnnuityFactor : "";
                        $initialfee = (isset($responseResultItem->initialFee)) ? $responseResultItem->initialFee : "";
                        $notificationfee = (isset($responseResultItem->notificationFee)) ? $responseResultItem->notificationFee : "";
                        $interestratepercentage = (isset($responseResultItem->interestRatePercent)) ? $responseResultItem->interestRatePercent : "";
                        $interestfreemonths = (isset($responseResultItem->numberOfInterestFreeMonths)) ? $responseResultItem->numberOfInterestFreeMonths : "";
                        $paymentfreemonths = (isset($responseResultItem->numberOfPaymentFreeMonths)) ? $responseResultItem->numberOfPaymentFreeMonths : "";
                        $fromamount = (isset($responseResultItem->fromAmount)) ? $responseResultItem->fromAmount : "";
                        $toamount = (isset($responseResultItem->toAmount)) ? $responseResultItem->toAmount : "";

                        $result[] = Array(
                            "campaignCode" => $campaignCode,
                            "description" => $description,
                            "paymentPlanType" => $paymentplantype,
                            "contractLengthInMonths" => $contractlength,
                            "monthlyAnnuityFactor" => $monthlyannuityfactor,
                            "initialFee" => $initialfee,
                            "notificationFee" => $notificationfee,
                            "interestRatePercent" => $interestratepercentage,
                            "numberOfInterestFreeMonths" => $interestfreemonths,
                            "numberOfPaymentFreeMonths" => $paymentfreemonths,
                            "fromAmount" => $fromamount,
                            "toAmount" => $toamount
                        );
                    } catch (Exception $e) {
                       $this->log->write($e->getMessage());
                    }
                }
            }
            return $result;
        }

    function update_status() {
        global $db, $order, $currencies, $messageStack;

        /* Tupas modification [BEGINS] */
        if (isset($_GET['succ']) && isset($_GET['stoken']) && isset($_GET['tapihash'])) { // Coming from Tupas API ?
            $return = $this->checkTapiReturn($_GET['succ'], $_GET['cart_id'], $_GET['stoken'], $_GET['tapihash'], (isset($_GET['ssn'])) ? $_GET['ssn'] : null, (isset($_GET['name'])) ? $_GET['name'] : null);
            if ($return) {
                if ($return['ok'] === true && $return['ssn']) {
                    $_SESSION['TUPAS_PP_CARTID'] = $return['cartid'];
                    $_SESSION['TUPAS_PP_SSN'] = $return['ssn'];
                    $_SESSION['TUPAS_PP_NAME'] = $return['name'];
                    $_SESSION['TUPAS_PP_HASH'] = $return['hash'];
                    $order->info['payment_method'] = $this->code;
                    // Strip the get tupas-api related parameters from the url
                    $url = $_SERVER['PHP_SELF'];
                    $taps = array('succ', 'ssn', 'name', 'cart_id', 'stoken', 'tapihash');
                    foreach ($_GET as $key => $val) {
                        if (in_array($key, $taps)) unset($_GET[$key]);
                    }
                    if($_GET) {
                        $url.= '?';
                        $pairs = array();
                        foreach ($_GET as $key => $val) $pairs[] = $key . '=' . $val;
                        $url.= implode("&", $pairs);
                    }
                    header('location:'.$url); // ... and reload page
                    die();
                } elseif ($return['ok'] === false) { // Tampered get params
                    $messageStack->add('header', ERROR_TAMPERED_PARAMETERS, 'error');
                }
            }
        }
        /* Tupas modification [ENDS] */
        // do not use this module if any of the allowed currencies are not set in osCommerce
        foreach ($this->getPartpayCurrencies() as $currency) {
            if (!is_array($currencies->currencies[strtoupper($currency)])) {
                $this->enabled = false;
                $messageStack->add('header', ERROR_ALLOWED_CURRENCIES_NOT_DEFINED, 'error');
            }
        }

        // do not use this module if the geograhical zone is set and we are not in it
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_SWPPARTPAY_ZONE > 0)) {
            $check_flag = false;
            $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SWPPARTPAY_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

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

    /**
     * Method called when building the index.php?main_page=checkout_payment page.
     * Builds the input fields that pick up ssn, vatno et al used by the various Svea Payment Methods.
     *
     * @return array containing module id, name & input field array
     *
     */
    function selection() {
        global $order, $currencies;

        // We need the order total and customer country in ajax functions. As
        // the shop order object is unavailable in sveaAjax.php, store these in
        // session when we enter checkout_payment page (where $order is set).
        if( isset($order) ) {
            $_SESSION['sveaAjaxOrderTotal'] = $order->info['total'];
            $_SESSION['sveaAjaxCountryCode'] = $order->customer['country']['iso_code_2'];
        }

        $fields = array();

        // image
            $fields[] = array('title' => '<img src=images/Svea/SVEASPLITEU_'.$order->customer['country']['iso_code_2'].'.png />', 'field' => '');

        // catch and display error messages raised when i.e. payment request from before_process() below turns out not accepted
        if (isset($_REQUEST['payment_error']) && $_REQUEST['payment_error'] == 'sveawebpay_partpay') {
            $fields[] = array('title' => '<span style="color:red">' . $_SESSION['SWP_ERROR'] . '</span>', 'field' => '');
        }

       // insert svea js
        $sveaJs = '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
                <script type="text/javascript" src="' . $this->web_root . 'includes/modules/payment/svea.js"></script>';
        $fields[] = array('title' => '', 'field' => $sveaJs);

        //
        // get required fields depending on customer country and payment method

        // customer country is taken from customer settings
        $customer_country = $order->customer['country']['iso_code_2'];

        // fill in all fields as required by customer country and payment method
        $sveaAddressDDPP = $sveaInitialsDivPP = $sveaBirthDateDivPP  = '';

        // get ssn & selects private/company for SE, NO, DK, FI
        if( ($customer_country == 'SE') ||     // e.g. == 'SE'
            ($customer_country == 'NO') ||
            ($customer_country == 'DK') )
        {
            // input text field for individual/company SSN
            $sveaSSNPP =          FORM_TEXT_SS_NO . '<br /><input type="text" name="sveaSSNPP" id="sveaSSNPP" maxlength="11" /><br />';
        }

        if( ($customer_country == 'FI') )
        {
           // input text field for individual/company SSN, without getAddresses hook
            $sveaSSNFIPP =        FORM_TEXT_SS_NO . '<br /><input type="text" name="sveaSSNFIPP" id="sveaSSNFIPP" maxlength="11" ';
            if ($this->usetupas === true) $sveaSSNFIPP.= 'value="' . $this->getSsn() . '" readonly="readonly" ';
            $sveaSSNFIPP.= '/>';

            /* Tupas mod [BEGINS] */
            if ($this->usetupas === true && !$_SESSION['TUPAS_PP_SSN']) {
                $_SESSION['TUPAS_PP_SSN'] = null; // Init ssn and other params
                $_SESSION['TUPAS_PP_NAME'] = null;
                $_SESSION['TUPAS_PP_HASH'] = null;
                $sveaSSNFIPP.= '<button type="button" id="getTupasAuthenticationPP">'.FORM_TEXT_TUPAS_AUTHENTICATE.'</button><br/>'; // Button
                $params = $this->getAuthenticationParams(); // Add a few params for better security before going to authentication api
                foreach ($params as $key => $value) $sveaSSNFIPP.= '<input type="hidden" id="'.$key.'_pp-tapi" name="'.$key.'" value="'.$value.'">';
            }
            /* Tupas mod [ENDS] */

            $sveaSSNFIPP.= '<br />';
        }

        //
        // these are the countries we support getAddress in (getAddress also depends on sveaSSN being present)
        if( ($customer_country == 'SE') ||
            ($customer_country == 'NO') ||
            ($customer_country == 'DK') )
        {
            $sveaAddressDDPP =  '<br /><label for ="sveaAddressSelectorPP" style="display:none">' . FORM_TEXT_PARTPAY_ADDRESS . '</label><br />' .
                                '<select name="sveaAddressSelectorPP" id="sveaAddressSelectorPP" style="display:none"></select><br />';
        }

        //
        // if customer is located in Netherlands, get initials
        if( $customer_country == 'NL') {

            $sveaInitialsDivPP =  '<div id="sveaInitials_divPP" >' .
                                    '<label for="sveaInitialsPP">' . FORM_TEXT_INITIALS . '</label><br />' .
                                    '<input type="text" name="sveaInitialsPP" id="sveaInitialsPP" maxlength="5" />' .
                                '</div><br />';
        }

        //
        // if customer is located in Netherlands or DE, get birth date
        if( ($customer_country == 'NL') ||
            ($customer_country == 'DE') )
        {
            //Days, to 31
            $days = "";
            for($d = 1; $d <= 31; $d++){

                $val = $d;
                if($d < 10)
                    $val = "$d";

                $days .= "<option value='$val'>$d</option>";
            }
            $birthDay = "<select name='sveaBirthDayPP' id='sveaBirthDayPP'>$days</select>";

            //Months to 12
            $months = "";
            for($m = 1; $m <= 12; $m++){
                $val = $m;
                if($m < 10)
                    $val = "$m";

                $months .= "<option value='$val'>$m</option>";
            }
            $birthMonth = "<select name='sveaBirthMonthPP' id='sveaBirthMonthPP'>$months</select>";

            //Years from 1913 to date('Y')
            $years = '';
            for($y = 1913; $y <= date('Y'); $y++){
                $selected = "";
                if( $y == (date('Y')-30) )      // selected is backdated 30 years
                    $selected = "selected";

                $years .= "<option value='$y' $selected>$y</option>";
            }
            $birthYear = "<select name='sveaBirthYearPP' id='sveaBirthYearPP'>$years</select>";

            $sveaBirthDateDivPP = '<div id="sveaBirthDate_divPP" >' .
                                    '<label for="sveaBirthYearPP">' . FORM_TEXT_BIRTHDATE . '</label><br />' .
                                    $birthYear . $birthMonth . $birthDay .
                                '</div><br />';

            $sveaVatNoDivPP = '<div id="sveaVatNo_divPP" hidden="true">' .
                                    '<label for="sveaVatNoPP" >' . FORM_TEXT_VATNO . '</label><br />' .
                                    '<input type="text" name="sveaVatNoPP" id="sveaVatNoPP" maxlength="14" />' .
                                '</div><br />';
        }

        $sveaPaymentOptionsPP =
            FORM_TEXT_PAYMENT_OPTIONS . '<br /><div id="sveaPaymentOptionsPP" style="display:none">';

        $sveaError = '<br /><span id="sveaSSN_error_invoicePP" style="color:red"></span>';

        //no campaigns on amount
        $minValue = 0;
        $maxValue = 0;
        switch ($order->billing['country']['iso_code_2']) {
            case 'SE':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_SE;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_SE;
                break;
             case 'NO':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_NO;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_NO;
                break;
             case 'FI':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_FI;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_FI;
                break;
             case 'DK':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_DK;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_DK;
                break;
             case 'NL':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_NL;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_NL;
                break;
             case 'DE':
            $minValue = MODULE_PAYMENT_SWPPARTPAY_MIN_DE;
            $maxValue = MODULE_PAYMENT_SWPPARTPAY_MAX_DE;
                break;

            default:
            $minValue = 1000;
            $maxValue = 50000;
                break;
        }
        if(($minValue != '' && $order->info['total'] < $minValue) || ($maxValue != '' && $order->info['total'] > $maxValue)){
            $fields[] = array('title' => '<div id="sveaPartPayField" style="display:none">'.DD_NO_CAMPAIGN_ON_AMOUNT.'</div>', 'field' => '');
        }  else {
         $sveaInitialFee =
                '<br /><div>' . sprintf( FORM_TEXT_PARTPAY_FEE).'</div>';
         if($order->billing['country']['iso_code_2'] == "SE" || $order->billing['country']['iso_code_2'] == "DK"){
               $sveaSubmitPaymentOptions = '<button id="sveaSubmitPaymentOptions" type="button">'.FORM_TEXT_GET_PAYPLAN.'</button><br />';
         }
             // create and add the field to be shown by our js when we select Payment Plan payment method
        $sveaField =    '<div id="sveaPartPayField" style="display:none">' .
                            $sveaSSNPP .              //  SE, DK, NO
                            $sveaSSNFIPP .            //  FI, no getAddresses
                            $sveaSubmitPaymentOptions.
                            $sveaAddressDDPP .        //  SE, Dk, NO
                            $sveaInitialsDivPP .      //  NL
                            $sveaBirthDateDivPP .     //  NL, DE
                            $sveaVatNoDivPP .         //  NL, DE
                            $sveaPaymentOptionsPP .
                            // FI, NL, DE also uses customer address data from zencart
                        '</div>'.
                        $sveaInitialFee;
            $fields[] = array('title' => '', 'field' => '<br />' . $sveaField . $sveaError);
        }

        $_SESSION["swp_order_info_pre_coupon"]  = serialize($order->info);  // store order info needed to reconstruct amount pre coupon later

        // return module fields to zencart
        return array(   'id' => $this->code,
                        'module' => $this->title,
                        'fields' => $fields );
    }

    /**
     * we've selected payment method, so we can set currency to payment method
     * currency
     *
     */
    function pre_confirmation_check() {
        global $order, $currency;

        // TODO make sure to update billing address here?

        $customer_country = $order->customer['country']['iso_code_2'];

        // did the customer have a different currency selected than the invoice country currency?
        if( $_SESSION['currency'] != $this->getPartpayCurrency( $customer_country ) )
        {
            // set shop currency to the selected payment method currency
            $order->info['currency'] = $this->getPartpayCurrency( $customer_country );
            $_SESSION['currency'] = $order->info['currency'];

            // redirect to update order_totals to new currency, making sure to preserve post data
            $_SESSION['sveapostdata'] = $_POST;
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_CONFIRMATION));    // redirect to update order_totals to new currency
        }

        if( isset($_SESSION['sveapostdata']) )
        {
            $_POST = array_merge( $_POST, $_SESSION['sveapostdata'] );
            unset( $_SESSION['sveapostdata'] );
        }

        return false;
    }

    function confirmation() {
        return false;
    }

        /** process_button() is called from tpl_checkout_confirmation.php in
     *  includes/templates/template_default/templates when we press the
     *  continue checkout button after having selected payment method and
     *  entered required payment method input.
     *
     *  Here we prepare to populate the order object by creating the
     *  WebPayItem::orderRow objects that make up the order.
     */

    function process_button() {

        global $db, $order, $order_totals, $language;

        //
        // handle postback of payment method info fields, if present
        $post_sveaSSN = isset($_POST['sveaSSNPP']) ? $_POST['sveaSSNPP'] : "swp_not_set" ;
        $post_sveaSSNFI = isset($_POST['sveaSSNFIPP']) ? $_POST['sveaSSNFIPP'] : "swp_not_set" ;
        $post_sveaAddressSelector = isset($_POST['sveaAddressSelectorPP']) ? $_POST['sveaAddressSelectorPP'] : "swp_not_set";
        $post_sveaBirthDay = isset($_POST['sveaBirthDayPP']) ? $_POST['sveaBirthDayPP'] : "swp_not_set";
        $post_sveaBirthMonth = isset($_POST['sveaBirthMonthPP']) ? $_POST['sveaBirthMonthPP'] : "swp_not_set";
        $post_sveaBirthYear = isset($_POST['sveaBirthYearPP']) ? $_POST['sveaBirthYearPP'] : "swp_not_set";
        $post_sveaInitials = isset($_POST['sveaInitialsPP']) ? $_POST['sveaInitialsPP'] : "swp_not_set" ;

        $_SESSION['sveaPaymentOptionsPP'] = isset($_POST['sveaPaymentOptionsPP']) ? $_POST['sveaPaymentOptionsPP'] : "swp_not_set" ;

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

         // switch to default currency if the customers currency is not supported
        $currency = $order->info['currency'];

        $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        // Create and initialize order object, using either test or production configuration
        $swp_order = WebPay::createOrder( $sveaConfig )
            ->setCountryCode( $user_country )
            ->setCurrency($currency)                       //Required for card & direct payment and PayPage payment.
            ->setClientOrderNumber($client_order_number)   //Required for card & direct payment, PaymentMethod payment and PayPage payments
            ->setOrderDate(date('c'))                      //Required for synchronous payments
        ;

        // create product order rows from each item in cart
        $swp_order = $this->parseOrderProducts( $order->products, $swp_order );

        // creates non-item order rows from Order Total entries
        $swp_order = $this->parseOrderTotals( $order_totals, $swp_order );

        // customer is always private individual with partpay

        // create individual customer object
        $swp_customer = WebPayItem::individualCustomer();

        // set individual customer name
        $swp_customer->setName( $order->billing['firstname'], $order->billing['lastname'] );

        // set individual customer SSN
        if( ($user_country == 'SE') ||
            ($user_country == 'NO') ||
            ($user_country == 'DK') )
        {
            $swp_customer->setNationalIdNumber( $post_sveaSSN );
        }
        if( ($user_country == 'FI') )
        {
            $swp_customer->setNationalIdNumber( $post_sveaSSNFI );
        }

        // set BirthDate if required
        if( ($user_country == 'NL') ||
            ($user_country == 'DE') )
        {
            $swp_customer->setBirthDate(intval($post_sveaBirthYear), intval($post_sveaBirthMonth), intval($post_sveaBirthDay));
        }

        // set initials if required
        if( ($user_country == 'NL') )
        {
            $swp_customer->setInitials($post_sveaInitials);
        }

        // set housenumber
        if( ($user_country == 'NL') ||
            ($user_country == 'DE') )
        {
            $myStreetAddress = Svea\Helper::splitStreetAddress( $order->billing['street_address'] ); // Split street address and house no
        }
        else // other countries disregard housenumber field, so put entire address in streetname field
        {
            $myStreetAddress[0] = $order->billing['street_address'];
            $myStreetAddress[1] = $order->billing['street_address'];
            $myStreetAddress[2] = "";
        }

        // set common fields
        $swp_customer
            ->setStreetAddress( $myStreetAddress[1], $myStreetAddress[2] )  // street, housenumber
            ->setZipCode($order->billing['postcode'])
            ->setLocality($order->billing['city'])
            ->setEmail($order->customer['email_address'])
            ->setIpAddress($_SERVER['REMOTE_ADDR'])
            ->setCoAddress($order->billing['suburb'])                       // c/o address
            ->setPhoneNumber($order->customer['telephone'])
        ;

        // add customer to order
        $swp_order->addCustomerDetails($swp_customer);

        //
        // store our order object in session, to be retrieved in before_process()
        $_SESSION["swp_order"] = serialize($swp_order);

        //
        // we're done here
        return false;
    }

    /**
     * before_process is called from modules/checkout_process.
     * It instantiates and populates a WebPay::createOrder object
     * as well as sends the actual payment request
     */
    function before_process() {
        global $order, $order_totals, $language, $billto, $sendto;

        // retrieve order object set in process_button()
        $swp_order = unserialize($_SESSION["swp_order"]);
        
        /* Tupas modification [BEGINS] */
        // Just in case, check that we are sending the same ssn as we got from tupasAPI (if at all).
        if ($this->usetupas === true) {
            if (!$_SESSION['TUPAS_PP_SSN']) {
                $_SESSION['SWP_ERROR'] = ERROR_TUPAS_NOT_SET;
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error='.$this->code));
            }
            if ($swp_order->customerIdentity->ssn != $this->getSsn()) {
                $_SESSION['SWP_ERROR'] = ERROR_TUPAS_MISMATCH;
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error='.$this->code));
            }
        }
        /* Tupas modification [ENDS] */   

        $swp_order->usePaymentPlanPayment($_SESSION['sveaPaymentOptionsPP'])->prepareRequest();

        //
        // send payment request to svea, receive response
        $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();
        try {
            $swp_response = $swp_order->usePaymentPlanPayment($_SESSION['sveaPaymentOptionsPP'])->doRequest();
        }
        catch (Exception $e){
            // hack together a fake response object containing the error & errormessage
            $swp_response = (object) array( "accepted" => false, "resultcode" => 1000, "errormessage" => $e->getMessage() ); //new "error" 1000
        }

        // payment request failed; handle this by redirecting w/result code as error message
        if ($swp_response->accepted === false) {
            $_SESSION['SWP_ERROR'] = $this->responseCodes($swp_response->resultcode,$swp_response->errormessage);
            $payment_error_return = 'payment_error=sveawebpay_partpay';
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return)); // error handled in selection() above
        }

        //
        // payment request succeded, store response in session
        if ($swp_response->accepted == true) {

            if (isset($_SESSION['SWP_ERROR'])) {
                unset($_SESSION['SWP_ERROR']);
            }

            // set zencart billing address to invoice address from payment request response

            // is private individual?
            if( $swp_response->customerIdentity->customerType == "Individual") {
                $order->billing['firstname'] = $swp_response->customerIdentity->fullName; // workaround for zen_address_format not showing 'name' in order information view/
                $order->billing['lastname'] = "";
                $order->billing['company'] = "";
            }

            $order->billing['street_address'] =
                    $swp_response->customerIdentity->street . " " . $swp_response->customerIdentity->houseNumber;
            $order->billing['suburb'] =  $swp_response->customerIdentity->coAddress;
            $order->billing['city'] = $swp_response->customerIdentity->locality;
            $order->billing['postcode'] = $swp_response->customerIdentity->zipCode;
            $order->billing['state'] = '';  // "state" is not applicable in SWP countries

            $order->billing['country']['title'] =                                           // country name only needed for address
                    $this->getCountryName( $swp_response->customerIdentity->countryCode );

            // save the response object
            $_SESSION["swp_response"] = serialize($swp_response);
        }
    }

    // if payment accepted, insert order into database
     function after_process() {
        global $insert_id, $order, $db;

        $new_order_id = $insert_id;  // $insert_id contains the new order orders_id

        // retrieve response object from before_process()
        $createOrderResponse = unserialize($_SESSION["swp_response"]);

        // store create order object along with response sveaOrderId in db
        $sql_data_array = array(
            'orders_id' => $new_order_id,
            'sveaorderid' => $createOrderResponse->sveaOrderId,
            'createorder_object' => $_SESSION["swp_order"]      // session data is already serialized
        );
        zen_db_perform("svea_order", $sql_data_array);

        // if autodeliver order status matches the new order status, deliver the order
        if( $this->getCurrentOrderStatus( $new_order_id ) == MODULE_PAYMENT_SWPPARTPAY_AUTODELIVER )
        {
            $deliverResponse = $this->doDeliverOrderPartPay($new_order_id);
            if( $deliverResponse->accepted == true )
            {
                $comment = 'Order AutoDelivered. (SveaOrderId: ' .$this->getSveaOrderId( $new_order_id ). ')';

                // insert autodeliver order status update in database
                $sql_data_array = array(
                    'orders_id' => $new_order_id,
                    'orders_status_id' => SVEA_ORDERSTATUS_DELIVERED_ID,
                    'date_added' => 'now()',
                    'customer_notified' => 0,  // 0 for "no email" (open lock symbol) in order status history
                    'comments' => $comment
                );
                zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

                $db->Execute(   "update " . TABLE_ORDERS . " " .
                                "set orders_status = " . SVEA_ORDERSTATUS_DELIVERED_ID . " " .
                                "where orders_id = " . $new_order_id
                );
            }
            else
            {
                $comment =  'WARNING: AutoDeliver failed, status not changed. ' .
                            'Error: ' . $deliverResponse->errormessage . ' (SveaOrderId: ' .$this->getSveaOrderId( $new_order_id ). ')';
                $this->insertOrdersStatus( $new_order_id, $this->getCurrentOrderStatus( $new_order_id ), $comment );
            }
        }

        // clean up our session variables set during checkout   //$SESSION[swp_*
        unset($_SESSION['swp_order']);
        unset($_SESSION['swp_response']);

        return false;
    }

    // sets error message to the GET error value
    function get_error() {
        return array('title' => ERROR_MESSAGE_PAYMENT_FAILED,
            'error' => stripslashes(urldecode($_GET['swperror'])));
    }

    // standard check if installed function
    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SWPPARTPAY_STATUS'");
            $this->_check = !$check_rs->EOF;
        }
        return $this->_check;
    }

    // insert configuration keys here
    function install() {
        global $db;
        $common = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
        
        /* Tupas API modification [BEGINS] */
        // Create tokens for this shop to be used in Tupas authentication process
        $token = uniqid();
        $response = $this->createShopInstance($token);
        if (!$response) {
            die("Could not create a shop instance to Tupas API."); // @todo :: Improve error handling (but how?)
        }
        $db->Execute($common . ", set_function) values ('Tupas mode', 'MODULE_PAYMENT_SWPPARTPAY_TUPAS_MODE', 'test', 'Tupas mode (test or production)', '6', '0', now(), 'zen_cfg_select_option(array(\'test\', \'production\'), ')");
        $db->Execute($common . ") values ('Tupas Shop Token', 'MODULE_PAYMENT_SWPPARTPAY_TUPAS_SHOP_TOKEN', '{$token}', 'Shop token (can be occasionally changed for better security)', '6', '0', now())");
        $db->Execute($common . ", set_function) values ('SveaWebPay Use Tupas (FI)', 'MODULE_PAYMENT_SWPPARTPAY_USETUPAS_FI', 'True', 'Check customers social security number using TUPAS -authentication (only for finnish customers)', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
        /* Tupas mod [ENDS] */
        
        $db->Execute($common . ", set_function) values ('Enable Svea Payment Plan Module', 'MODULE_PAYMENT_SWPPARTPAY_STATUS', 'True', '', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");

        $db->Execute($common . ") values ('Svea Username SE', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_SE', '', 'Username for Svea Payment Plan Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password SE', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_SE', '', 'Password for Svea Payment Plan Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client No SE', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SE', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for SE in SEK', 'MODULE_PAYMENT_SWPPARTPAY_MIN_SE', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for SE in SEK', 'MODULE_PAYMENT_SWPPARTPAY_MAX_SE', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ") values ('Svea Username NO', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NO', '', 'Username for Svea Payment Plan Norway', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password NO', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NO', '', 'Password for Svea Payment Plan Norway', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no NO', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for NO in NOK', 'MODULE_PAYMENT_SWPPARTPAY_MIN_NO', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for NO in NOK', 'MODULE_PAYMENT_SWPPARTPAY_MAX_NO', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ") values ('Svea Username FI', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_FI', '', 'Username for Svea Payment Plan Finland', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password FI', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_FI', '', 'Password for Svea Payment Plan Finland', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no FI', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for FI in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MIN_FI', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for FI in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MAX_FI', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ") values ('Svea Username DK', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DK', '', 'Username for Svea Payment Plan Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password DK', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DK', '', 'Password for Svea Payment Plan Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no DK', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for DK in DKK', 'MODULE_PAYMENT_SWPPARTPAY_MIN_DK', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for DK in DKK', 'MODULE_PAYMENT_SWPPARTPAY_MAX_DK', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ") values ('Svea Username NL', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NL', '', 'Username for Svea Payment Plan Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password NL', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NL', '', 'Password for Svea Payment Plan Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no NL', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for NL in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MIN_NL', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for NL in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MAX_NL', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ") values ('Svea Username DE', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DE', '', 'Username for Svea Payment Plan Germany', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password DE', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DE', '', 'Password for Svea Payment Plan Germany', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no DE', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DE', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Min amount for DE in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MIN_DE', '', 'The minimum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");
        $db->Execute($common . ") values ('Max amount for DE in EUR', 'MODULE_PAYMENT_SWPPARTPAY_MAX_DE', '', 'The maximum amount for use of this payment. Check with your Svea campaign rules. Ask your Svea integration manager if unsure.', '6', '0', now())");

        $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPPARTPAY_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
        $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value (but see AutoDeliver option below).', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
        $db->Execute($common . ", set_function) values ('Auto Deliver Order', 'MODULE_PAYMENT_SWPPARTPAY_AUTODELIVER', '3', 'AutoDeliver: When the order status of an order is set to this value, it will be delivered to Svea. Use in conjunction with Set Order Status above to autodeliver orders.', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(')");
        $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPPARTPAY_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
        $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPPARTPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
        $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

        $db->Execute($common . ", set_function) values ('Show Product Price Widget', 'MODULE_PAYMENT_SWPPARTPAY_PRODUCT', 'False', 'Show lowest price, and monthly costs for every part payment campaign on product pages.', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");

        // insert svea order table if not exists already
        $res = $db->Execute("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '". DB_DATABASE ."' AND table_name = 'svea_order';");
        if( $res->fields["COUNT(*)"] != 1 ) {
            $sql = "CREATE TABLE svea_order (orders_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, sveaorderid INT NOT NULL, createorder_object BLOB, invoice_id INT )";
            $db->Execute( $sql );
        }


        //create params table
         $sql = ' CREATE TABLE IF NOT EXISTS `svea_params_table`
                (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `campaignCode` VARCHAR( 100 ) NOT NULL,
                `description` VARCHAR( 100 ) NOT NULL ,
                `paymentPlanType` VARCHAR( 100 ) NOT NULL ,
                `contractLengthInMonths` INT NOT NULL ,
                `monthlyAnnuityFactor` DOUBLE NOT NULL ,
                `initialFee` DOUBLE NOT NULL ,
                `notificationFee` DOUBLE NOT NULL ,
                `interestRatePercent` INT NOT NULL ,
                `numberOfInterestFreeMonths` INT NOT NULL ,
                `numberOfPaymentFreeMonths` INT NOT NULL ,
                `fromAmount` DOUBLE NOT NULL ,
                `toAmount` DOUBLE NOT NULL ,
                `timestamp` INT UNSIGNED NOT NULL,
                `countryCode` VARCHAR( 100 ) NOT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
                ';
            $db->Execute( $sql );
        // insert svea order statuses into table order_status, if not exists already
        $res = $db->Execute('SELECT COUNT(*) FROM ' . TABLE_ORDERS_STATUS . ' WHERE orders_status_name = "'. SVEA_ORDERSTATUS_CLOSED .'"');
        if( $res->fields["COUNT(*)"] == 0 ) {
            $sql =  'INSERT INTO ' . TABLE_ORDERS_STATUS . ' (`orders_status_id`, `language_id`, `orders_status_name`) VALUES ' .
                    '(' . SVEA_ORDERSTATUS_CLOSED_ID . ', 1, "' . SVEA_ORDERSTATUS_CLOSED . '"), ' .
                    '(' . SVEA_ORDERSTATUS_CREDITED_ID . ', 1, "' . SVEA_ORDERSTATUS_CREDITED . '"), ' .
                    '(' . SVEA_ORDERSTATUS_DELIVERED_ID . ', 1, "' . SVEA_ORDERSTATUS_DELIVERED . '")'
            ;
            $db->Execute( $sql );
        }
        
        // insert svea tupas table
        $res = $db->Execute("SELECT COUNT(*) as rows FROM information_schema.tables WHERE table_schema = '". DB_DATABASE ."' AND table_name = 'svea_tupas';");
        if ($res->fields['rows'] == '0') {
            $db->Execute("CREATE TABLE svea_tupas (id INT NOT NULL AUTO_INCREMENT, shop_id INT NOT NULL, api_token VARCHAR(45) NOT NULL, payment_module VARCHAR(45) NOT NULL,
                previous_mode VARCHAR(10) NOT NULL, previous_shop_token VARCHAR(45) NOT NULL, PRIMARY KEY (`id`, `payment_module`), UNIQUE INDEX `pm_uniq` (`payment_module` ASC) )");
        }
        $db->Execute("INSERT INTO svea_tupas (shop_id, api_token, payment_module, previous_mode, previous_shop_token) VALUES ('{$response->id}', '{$response->api_token}', 'PARTPAY', 'test', '{$token}') 
            ON DUPLICATE KEY UPDATE shop_id = '{$response->id}', api_token = '{$response->api_token}', previous_mode = 'test', previous_shop_token = '{$token}';");

    }

    // standard uninstall function
    function remove() {
        global $db;
        
        /* Tupas modification [BEGINS] */
        // Try to remove tupas instance from api
        if (!$this->removeShopInstance()) {
            die("Could not delete a shop instance from Tupas API."); // @todo :: Improve error handling (but how?)
        }
        
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        // we don't delete svea_order tables, as data may be needed by other payment modules and to admin orders etc.
    }

    // must perfectly match keys inserted in install function
    function keys() {
        return array('MODULE_PAYMENT_SWPPARTPAY_STATUS',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_SE',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_SE',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SE',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_SE',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_SE',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NO',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NO',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_NO',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_NO',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_FI',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_FI',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_FI',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_FI',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DK',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_DK',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_DK',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NL',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NL',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_NL',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_NL',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DE',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DE',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DE',
            'MODULE_PAYMENT_SWPPARTPAY_MIN_DE',
            'MODULE_PAYMENT_SWPPARTPAY_MAX_DE',
            'MODULE_PAYMENT_SWPPARTPAY_MODE',
            'MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID',
            'MODULE_PAYMENT_SWPPARTPAY_AUTODELIVER',
            'MODULE_PAYMENT_SWPPARTPAY_IGNORE',
            'MODULE_PAYMENT_SWPPARTPAY_ZONE',
            'MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER',
            'MODULE_PAYMENT_SWPPARTPAY_PRODUCT',
            
            // Tupas API related fields
            'MODULE_PAYMENT_SWPPARTPAY_USETUPAS_FI',
            'MODULE_PAYMENT_SWPPARTPAY_TUPAS_MODE',
            'MODULE_PAYMENT_SWPPARTPAY_TUPAS_SHOP_TOKEN'
            );
    }

    //Error Responses
    function responseCodes($err,$msg = NULL) {
        switch ($err) {

            // EU error codes
            case "20000" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20000);
                break;
            case "20001" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20001);
                break;
            case "20002" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20002);
                break;
            case "20003" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20003);
                break;
            case "20004" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20004);
                break;
            case "20005" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20005);
                break;
            case "20006" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20006);
                break;
            case "20013" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_20013);
                break;
            case "27000" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_27000);
                break;
            case "27001" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_27001);
                break;
            case "27002" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_27002);
                break;
            case "27003" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_27003);
                break;
            case "27004" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_27004);
                break;
            case "30000" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_30000);
                break;
            case "30001" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_30001);
                break;
            case "30002" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_30002);
                break;
            case "30003" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_30003);
                break;

            case "40000" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_40000);
                break;
            case "40001" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_40001);
                break;
            case "40002" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_40002);
                break;
            case "40004" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_40004);
                break;

            case "50000" :
                return sprintf("Svea error %s: %s", $err, ERROR_CODE_50000);
                break;

            default :
                 return sprintf("Svea error %s: %s", $err, ERROR_CODE_DEFAULT . " " . $err . " - " . $msg);     // $err here is the response->resultcode
                break;
        }
    }

    /**
     * Given an orderID, reconstruct the svea order object and send deliver order request, return response
     *
     * @param int $oID -- $oID is the order id
     * @return Svea\DeliverOrderResult
     */
    function doDeliverOrderPartPay($oID) {
        global $db;

        // get zencart order from db
        $order = new order($oID);

        // get svea order id reference returned in createOrder request result
        $sveaOrderId = $this->getSveaOrderId( $oID );
        $swp_order = $this->getSveaCreateOrderObject( $oID );

        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_deliverOrder = WebPay::deliverOrder( $sveaConfig )
            ->setOrderId($sveaOrderId)
        ;

        // TODO create helper functions in integration package that transforms createOrder -> deliverOrder -> closeOrder etc. (see INTG-324)

        // this really exploits CreateOrderRow objects having public properties...
        // ~hack
        $swp_deliverOrder->orderRows = $swp_order->orderRows;
        $swp_deliverOrder->shippingFeeRows = $swp_order->shippingFeeRows;
        $swp_deliverOrder->invoiceFeeRows = $swp_order->invoiceFeeRows;
        $swp_deliverOrder->fixedDiscountRows = $swp_order->fixedDiscountRows;
        $swp_deliverOrder->relativeDiscountRows = $swp_order->relativeDiscountRows;
        $swp_deliverOrder->countryCode = $swp_order->countryCode;
        // /hack

        $swp_deliverResponse = $swp_deliverOrder->deliverPaymentPlanOrder()->doRequest();

        // if deliverorder accepted, update svea_order table with Svea contractNumber
        if( $swp_deliverResponse->accepted == true )
        {
            $db->Execute(
                "update svea_order " .
                "set invoice_id = " . $swp_deliverResponse->contractNumber. " " .
                "where orders_id = " . (int)$oID )
            ;
        }
        // return deliver order response
        return $swp_deliverResponse;
    }

    /**
     * Called from admin/orders.php when admin chooses to edit an order and updates its order status
     *
     * @param int $oID
     * @param type $status
     * @param type $comments
     * @param type $customer_notified
     * @param type $old_orders_status
     */
    function _doStatusUpdate($oID, $status, $comments, $customer_notified, $old_orders_status) {
        global $db;

        //if this is a legacy order (i.e. it isn't in table svea_order), fail gracefully: reset status to old status if needed
        if( ! $this->getSveaOrderId( $oID ) )
        {
            $comment =  'WARNING: Unable to administrate orders created with Svea payment module < version 4.1.';
            if( $status == SVEA_ORDERSTATUS_CLOSED_ID || $status == SVEA_ORDERSTATUS_CREDITED_ID || $status == SVEA_ORDERSTATUS_DELIVERED_ID )
            {
                $comment = $comment . " Status not changed.";
                $status = $old_orders_status;
            }
            $this->updateOrdersStatus( $oID, $status, $comment );

            return; //exit _doStatusUpdate
        }

        switch( $status )
        {
        case $old_orders_status:
            // do nothing
            break;

        case MODULE_PAYMENT_SWPPARTPAY_AUTODELIVER:
            // deliver if new status == autodeliver status setting
        case SVEA_ORDERSTATUS_DELIVERED_ID:
            $deliverResult = $this->doDeliverOrderPartPay($oID);
            if( $deliverResult->accepted == true )
            {
                $comment = 'Delivered by status update. (SveaOrderId: ' . $this->getSveaOrderId( $oID ) . ')';
                $status = SVEA_ORDERSTATUS_DELIVERED_ID;  // override set status
            }
            else // deliverOrder failed, so reset status to old status & state order closed in comment
            {
                $comment =  'WARNING: Deliver order failed, status not changed. ' .
                            'Error: ' . $deliverResult->errormessage . ' (SveaOrderId: ' .  $this->getSveaOrderId( $oID ) . ')';
                $status = $old_orders_status;
            }
            $this->updateOrdersStatus( $oID, $status, $comment );
            break;

        case SVEA_ORDERSTATUS_CLOSED_ID:
            $this->_doVoid( $oID, $old_orders_status );
            break;

        case SVEA_ORDERSTATUS_CREDITED_ID:
            $this->_doRefund( $oID, $old_orders_status );
            break;

        default:
            break;
        }
    }

    // called when we want to refund a delivered order (i.e. invoice)
    function _doRefund($oID, $from_doStatusUpdate = false ) {
        global $db;

        // get svea invoice id reference returned with deliverOrder request result
        $sveaOrderId = $this->getSveaOrderId( $oID );
        $contractNumber = $this->getSveaInvoiceId( $oID );   // contains contractNumber

        $comment =  'WARNING: Credit invoice is not applicable to Payment plan orders, status not changed. ' . "(ContractNumber: " . $contractNumber  . ')';
        $status = ($from_doStatusUpdate == false) ? $this->getCurrentOrderStatus($oID) : $from_doStatusUpdate;     // use current/old order status

        if( $from_doStatusUpdate == true )  // update status inserted before _doStatusUpdate
        {
            $this->updateOrdersStatus( $oID, $status, $comment );
        }
        else    // insert status
        {
            $this->insertOrdersStatus( $oID, $status, $comment );
        }
    }

    // called when we want to cancel (close) an undelivered order
    function _doVoid($oID, $from_doStatusUpdate = false ) {
        global $db;

        $sveaOrderId = $this->getSveaOrderId( $oID );
        $swp_order = $this->getSveaCreateOrderObject( $oID );

        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_closeOrder = WebPay::closeOrder($sveaConfig)
            ->setOrderId($sveaOrderId)
        ;

        // this really exploits CreateOrderRow objects having public properties...
        // ~hack
        $swp_closeOrder->orderRows = $swp_order->orderRows;
        $swp_closeOrder->shippingFeeRows = $swp_order->shippingFeeRows;
        $swp_closeOrder->invoiceFeeRows = $swp_order->invoiceFeeRows;
        $swp_closeOrder->fixedDiscountRows = $swp_order->fixedDiscountRows;
        $swp_closeOrder->relativeDiscountRows = $swp_order->relativeDiscountRows;
        $swp_closeOrder->countryCode = $swp_order->countryCode;
        // /hack

        $swp_closeResponse = $swp_closeOrder->closePaymentPlanOrder()->doRequest();

        if( $swp_closeResponse->accepted == true )
        {
            $comment = 'Svea order closed. ' . '(SveaOrderId: ' . $sveaOrderId . ')';
            $status = SVEA_ORDERSTATUS_CLOSED_ID;
        }
        else    // close order failed, insert error in history
        {
            $comment =  'WARNING: Close order request failed, status not changed. ' .
                        'Error: ' . $swp_closeResponse->errormessage . ' (SveaOrderId: ' . $sveaOrderId . ')';
            $status = ($from_doStatusUpdate == false) ? $this->getCurrentOrderStatus($oID) : $from_doStatusUpdate;     // use current/old order status
        }

        if( $from_doStatusUpdate == true )  // update status inserted before _doStatusUpdate
        {
            $this->updateOrdersStatus( $oID, $status, $comment );
        }
        else    // insert status
        {
            $this->insertOrdersStatus( $oID, $status, $comment );
        }
    }

    /**
     * Returns the currency used for an partpay country.
     */
    function getPartpayCurrency( $country )
    {
        $country_currencies = array(
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SE' => 'SEK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO' => 'NOK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI' => 'EUR',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK' => 'DKK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL' => 'EUR',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DE' => 'EUR'
        );

        $method = "MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_" . $country;

        return $country_currencies[$method];
    }

    /**
     * Returns the currencies used in all countries where an partpay payment
     * method has been configured (i.e. clientno is set for country in config).
     * Used in partpay to determine currencies which must be set.
     *
     * @return array - currencies for countries with ug clientno set in config
     */
    function getPartpayCurrencies()
    {
        $country_currencies = array(
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SE' => 'SEK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO' => 'NOK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI' => 'EUR',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK' => 'DKK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL' => 'EUR',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DE' => 'EUR'
        );

        $currencies = array();
        foreach( $country_currencies as $country => $currency )
        {
            if( constant($country)!=NULL ) $currencies[] = $currency;
        }

        return array_unique( $currencies );
    }
    
    /* Tupas modification [START] */
    /*
     * Installing a svea invoice payment module with tupas authentication, make POST request to api
     * @return object
     */
    function createShopInstance($shop_token){ 
        $url = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
        $guess = $url . 'index.php?main_page=checkout_payment'; // Best guess for payment selection page
        $file_headers = @get_headers($guess);
        $url = ($file_headers[0] == 'HTTP/1.1 404 Not Found') ? $url : $guess; // Use catalog url, if the guessed url doesn't exist.
        
        $shop_info = array(
            'name' => STORE_NAME,
            'shop_token' => $shop_token,
            'mode' => 'test',
            'url' => $url
            );

        $data = array('json' => json_encode($shop_info));
        // We can't be sure that cUrl is installed so use php's native methods
        $params = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data)));

        $context = stream_context_create($params);
        $fp = @fopen($this->tupasapiurl, 'rb', false, $context);
        if (!$fp) {
            return false;
        }
        $response = json_decode(stream_get_contents($fp));
        if ($response->status->code !== 200) {
            return false;
        }
        return $response;
    }
    
    // admin uninstalls invoice module; sets shop at Tupas API inactive
    function removeShopInstance() { 
        $shop_id = $this->getShopId();
        $shop_token = $this->tupas_shop_token;
        $api_token = $this->getApiToken();
        
        $data = array(
            'json' => json_encode(array(
                'shop_token' => $shop_token, 
                'hash' => hash('sha256', $shop_token.$api_token)
                ))
            );
        $params = array(
            'http' => array(
                'method' => 'DELETE',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data)));
        $context = stream_context_create($params);
        $fp = @fopen($this->tupasapiurl."/".$shop_id, 'rb', false, $context);
        if (!$fp) {
            return false;
        }
        $response = json_decode(stream_get_contents($fp));
        if ($response->status->code !== 200) {
            return false;
        }
        return $response;
    }
    
    function editShopInstance() {
        global $db;
        $previous_shop_token = $this->get_previous('shop_token');
        $shop_id = $this->getShopId();
        
        // Perform a request
        $shop_info = array(
             'shop_token' => $this->tupas_shop_token,
             'mode' => $this->tupas_mode,
             'hash' => hash('sha256', $previous_shop_token . $this->getApiToken()));

        $data = array('json' => json_encode($shop_info));
        $params = array(
             'http' => array(
                 'method' => 'POST',
                 'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                 'content' => http_build_query($data)));
        $context = stream_context_create($params);
        $fp = fopen($this->tupasapiurl."/".$shop_id, 'rb', false, $context);
        $response = json_decode(stream_get_contents($fp));
        
        // And update previous values to current ones saved in tupas table
        if ($response->status->code === 200) {
            var_dump($db->Execute("UPDATE svea_tupas SET previous_shop_token = '{$this->tupas_shop_token}', previous_mode = '{$this->tupas_mode}' WHERE payment_module = 'PARTPAY'"));
        }
    }
    
    /*
     * Check if the settings have been changed by the user. Returns boolean.
     */
    function tupas_settings_changed() {
        global $db;
        $pvrow = $db->Execute("SELECT previous_mode, previous_shop_token FROM svea_tupas WHERE payment_module = 'PARTPAY'");
        return ($pvrow->fields['previous_shop_token'] != $this->tupas_shop_token || $pvrow->fields['previous_mode'] != $this->tupas_mode) ? true : false;
    }
    
    function getShopToken() {
        global $db;        
        $shop_token_row = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_SWPPARTPAY_TUPAS_SHOP_TOKEN'");
        return $shop_token_row->fields['configuration_value'];
    }
    
    function getApiToken() {
        global $db;        
        $api_token_row = $db->Execute("SELECT api_token FROM svea_tupas WHERE payment_module = 'PARTPAY'");
        return $api_token_row->fields['api_token'];
    }
    
    function getShopId() {
        global $db;        
        $shop_id_row = $db->Execute("SELECT shop_id FROM svea_tupas WHERE payment_module = 'PARTPAY'");
        return $shop_id_row->fields['shop_id'];
    }
    
    function get_previous($col='shop_token'){
        global $db;
        $pvrow = $db->Execute("SELECT previous_{$col} AS prev FROM svea_tupas WHERE payment_module = 'PARTPAY'");
        return $pvrow->fields['prev'];
    }
    
    function getAuthenticationParams() {
        $stoken = $this->getShopToken();
        $atoken = $this->getApiToken();
        $params = array(
            'shop_token' => $stoken, 
            'cart_id' => $_SESSION['cartID'],
            'return_url' => zen_href_link(FILENAME_CHECKOUT_PAYMENT),
            'hash' => strtoupper(hash('sha256', $stoken.'|'.$_SESSION['cartID'].'|'.$atoken))
            );
        return $params;
    }
    
    function checkTapiReturn($success, $cart_id, $stoken, $hash, $ssn=null, $name=null) {
        // First check that it was partpayment instance
        if ($this->getShopToken() == $stoken) {
            if ($success == '1') {
                $mac_base = $this->getShopToken() . '|' .
                            '1' . '|' .
                            $cart_id . '|' .
                            $ssn . '|' .
                            $name . '|' .
                            $this->getApiToken();
                $calculated_hash = strtoupper(hash('sha256', $mac_base));
                if ($calculated_hash == $hash) { // OK
                    return array('ok' => true, 'ssn' => $ssn, 'name' => $name, 'hash' => $hash, 'cartid' => $cart_id);
                } else {
                    return array('ok' => false);
                }
            } else {
                return array('ok' => true, 'ssn' => null);
            }
        } else {
            // Stokens didn't match
            return false;
        }
    }
    
    function getSsn() {
        $ssn = '';
        if ($_SESSION['TUPAS_PP_SSN']) {
            $mac_base = $this->getShopToken() . '|' .
                        '1' . '|' .
                        $_SESSION['TUPAS_PP_CARTID'] . '|' .
                        $_SESSION['TUPAS_PP_SSN'] . '|' .
                        $_SESSION['TUPAS_PP_NAME'] . '|' .
                        $this->getApiToken();
            $calculated_hash = strtoupper(hash('sha256', $mac_base));
            if ($_SESSION['TUPAS_PP_HASH'] == $calculated_hash) $ssn = $_SESSION['TUPAS_PP_SSN'];
        }
        return $ssn;
    }
    
}
?>
