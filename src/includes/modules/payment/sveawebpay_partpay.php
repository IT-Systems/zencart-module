<?php

/*
  HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
  -----------------------------------------------
  Version 4.0 - Zen Cart

  Kristian Grossman-Madsen, Shaho Ghobadi
 */

class sveawebpay_partpay {

    function sveawebpay_partpay() {
        global $order;

        $this->code = 'sveawebpay_partpay';
        $this->version = 2;

        $_SESSION['SWP_CODE'] = $this->code;

        $this->title = MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_SWPPARTPAY_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_SWPPARTPAY_STATUS == 'True') ? true : false);
        $this->sort_order = MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER;
        $this->sveawebpay_url = MODULE_PAYMENT_SWPPARTPAY_URL;
        $this->handling_fee = MODULE_PAYMENT_SWPPARTPAY_HANDLING_FEE;
        $this->default_currency = MODULE_PAYMENT_SWPPARTPAY_DEFAULT_CURRENCY;
        $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPPARTPAY_ALLOWED_CURRENCIES);
        $this->display_images = ((MODULE_PAYMENT_SWPPARTPAY_IMAGES == 'True') ? true : false);
        $this->ignore_list = explode(',', MODULE_PAYMENT_SWPPARTPAY_IGNORE);
        if ((int) MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID > 0)
            $this->order_status = MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID;
        if (is_object($order))
            $this->update_status();
    }

    function update_status() {
        global $db, $order, $currencies, $messageStack;

        // update internal currency
        $this->default_currency = MODULE_PAYMENT_SWPPARTPAY_DEFAULT_CURRENCY;
        $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPPARTPAY_ALLOWED_CURRENCIES);

        // do not use this module if any of the allowed currencies are not set in osCommerce
        foreach ($this->allowed_currencies as $currency) {
            if (!is_array($currencies->currencies[strtoupper($currency)])) {
                $this->enabled = false;
                $messageStack->add('header', ERROR_ALLOWED_CURRENCIES_NOT_DEFINED, 'error');
            }
        }

        // do not use this module if the default currency is not among the allowed
        if (!in_array($this->default_currency, $this->allowed_currencies)) {
            $this->enabled = false;
            $messageStack->add('header', ERROR_DEFAULT_CURRENCY_NOT_ALLOWED, 'error');
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

        $fields = array();

        // image
        if ($this->display_images)
            $fields[] = array('title' => '<img src=images/SveaWebPay-Delbetala-100px.png />', 'field' => '');

        // catch and display error messages raised when i.e. payment request from before_process() below turns out not accepted 
        if (isset($_REQUEST['payment_error']) && $_REQUEST['payment_error'] == 'sveawebpay_partpay') {
            $fields[] = array(  'title' => '<span style="color:red">' . $this->responseCodes($_REQUEST['payment_errno']) . '</span>', 
                                'field' => '');
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
            $sveaSSNFIPP =        FORM_TEXT_SS_NO . '<br /><input type="text" name="sveaSSNFIPP" id="sveaSSNFIPP" maxlength="11" /><br />';             
        }
        
        // radiobutton for choosing individual or organization
        $sveaIsCompanyField = FORM_TEXT_COMPANY_OR_PRIVATE . ' <br />' .
                            '<label><input type="radio" name="sveaIsCompanyPP" value="false" checked>' . FORM_TEXT_PRIVATE . '</label>' .
                            '<label><input type="radio" name="sveaIsCompanyPP" value="true">' . FORM_TEXT_COMPANY . '</label><br />';

        
        //
        // these are the countries we support getAddress in (getAddress also depends on sveaSSN being present)
        if( ($customer_country == 'SE') ||
            ($customer_country == 'NO') || 
            ($customer_country == 'DK') ) 
        {       
            $sveaAddressDDPP =  '<br /><label for ="sveaAddressSelectorPP" style="display:none">' . FORM_TEXT_INVOICE_ADDRESS . '</label><br />' .
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

            //Years from 1913 to 1996
            $years = '';
            for($y = 1913; $y <= 1996; $y++){
                $years .= "<option value='$y'>$y</option>";
            }
            $birthYear = "<select name='sveaBirthYearPP' id='sveaBirthYearPP'>$years</select>";

            $sveaBirthDateDivPP = '<div id="sveaBirthDate_divPP" >' . 
                                    '<label for="sveaBirthYearPP">' . FORM_TEXT_BIRTHDATE . '</label><br />' .
                                    $birthYear . $birthMonth . $birthDay .  // TODO better default selected, date order conforms w/DE,NL standard? 
                                '</div><br />';

            $sveaVatNoDivPP = '<div id="sveaVatNo_divPP" hidden="true">' . 
                                    '<label for="sveaVatNoPP" >' . FORM_TEXT_VATNO . '</label><br />' .
                                    '<input type="text" name="sveaVatNoPP" id="sveaVatNoPP" maxlength="14" />' . 
                                '</div><br />';
        }
        
        $sveaPaymentOptionsPP = FORM_TEXT_GET_PAYPLAN . '<br /><select name="sveaPaymentOptionsPP" id="sveaPaymentOptionsPP" style="display:none"></select><br />'; 
        
        $sveaError = '<br /><span id="sveaSSN_error_invoicePP" style="color:red"></span>';
   
        // create and add the field to be shown by our js when we select SveaInvoice payment method
        $sveaField =    '<div id="sveaPartPayField" style="display:none">' . 
                            $sveaIsCompanyFieldPP .   //  SE, DK, NO
                            $sveaSSNPP .              //  SE, DK, NO        
                            $sveaSSNFIPP .            //  FI, no getAddresses     
                            $sveaAddressDDPP .        //  SE, Dk, NO   
                            $sveaInitialsDivPP .      //  NL
                            $sveaBirthDateDivPP .     //  NL, DE
                            $sveaVatNoDivPP .         // NL, DE
                            $sveaPaymentOptionsPP .
                        // FI, NL, DE also uses customer address data from zencart
                        '</div>';
       
        $fields[] = array('title' => '', 'field' => '<br />' . $sveaField . $sveaError);
   
        // return module fields to zencart
        return array(   'id' => $this->code,
                        'module' => $this->title,
                        'fields' => $fields );
    }
     

    function pre_confirmation_check() {
        return false;
    }

    function confirmation() {
        return false;
    }

    function process_button() {

        global $db, $order, $order_totals, $language;


        require('includes/modules/payment/svea/svea.php');

        //Get the order
        $new_order_rs = $db->Execute("select orders_id from " . TABLE_ORDERS . " order by orders_id desc limit 1");
        $new_order_field = $new_order_rs->fields;

        // localization parameters
        $user_country = $order->billing['country']['iso_code_2'];
        $user_language = $db->Execute("select code from " . TABLE_LANGUAGES . " where directory = '" . $language . "'");
        $user_language = $user_language->fields['code'];


        // switch to default currency if the customers currency is not supported
        $currency = $order->info['currency'];
        if (!in_array($currency, $this->allowed_currencies))
            $currency = $this->default_currency;


        // we'll store the generated orderid in a session variable so we can check
        // it when returning from payment gateway for security reasons:
        // Set up SSN and company
        $_SESSION['swp_orderid'] = $hosted_params['OrderId'];


        /*         * * Set up The request Array ** */

        $i = 0;
        // Order rows for Nordic
        foreach ($order->products as $productId => $product) {
            $i++;
            $orderRows = Array(
                "ClientOrderRowNr" => $i,
                "Description" => $product['name'],
                "PricePerUnit" => $this->convert_to_currency(round($product['final_price'], 2), $currency),
                "NrOfUnits" => $product['qty'],
                "Unit" => "st",
                "VatPercent" => $product['tax'],
                "DiscountPercent" => 0
            );

            if (isset($clientInvoiceRows)) {

                $clientInvoiceRows[$productId] = $orderRows;
            } else {
                $clientInvoiceRows[] = $orderRows;
            }
        }



        // handle order totals
        foreach ($order_totals as $ot_id => $order_total) {
            $current_row++;
            switch ($order_total['code']) {
                case 'ot_subtotal':
                case 'ot_total':
                case 'ot_tax':
                case in_array($order_total['code'], $this->ignore_list):
                    // do nothing for these
                    $current_row--;
                    break;
                case 'ot_shipping':
                    $shipping_code = explode('_', $_SESSION['shipping']['id']);
                    $shipping = $GLOBALS[$shipping_code[0]];
                    if (isset($shipping->description))
                        $shipping_description = $shipping->title . ' [' . $shipping->description . ']';
                    else
                        $shipping_description = $shipping->title;

                    $clientInvoiceRows[] = Array(
                        "ClientOrderRowNr" => $i + 1,
                        "Description" => $shipping_description,
                        "PricePerUnit" => $this->convert_to_currency($_SESSION['shipping']['cost'], $currency),
                        "NrOfUnits" => 1,
                        "VatPercent" => (string) zen_get_tax_rate($shipping->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']),
                        "DiscountPercent" => 0
                    );
                    break;
                case 'ot_coupon':

                    $clientInvoiceRows[] = Array(
                        "ClientOrderRowNr" => $i + 1,
                        "Description" => strip_tags($order_total['title']),
                        "PricePerUnit" => -$this->convert_to_currency(strip_tags($order_total['value']), $currency),
                        "NrOfUnits" => 1,
                        "VatPercent" => 0,
                        "DiscountPercent" => 0
                    );

                    break;
                // default case handles order totals like handling fee, but also
                // 'unknown' items from other plugins. Might cause problems.
                default:
                    $order_total_obj = $GLOBALS[$order_total['code']];
                    $tax_rate = zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                    // if displayed WITH tax, REDUCE the value since it includes tax
                    if (DISPLAY_PRICE_WITH_TAX == 'true')
                        $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));

                    $clientInvoiceRows[] = Array(
                        "ClientOrderRowNr" => $i + 1,
                        "Description" => strip_tags($order_total['title']),
                        "PricePerUnit" => $this->convert_to_currency(strip_tags($order_total['value']), $currency),
                        "NrOfUnits" => 1,
                        "VatPercent" => $tax_rate,
                        "DiscountPercent" => 0
                    );

                    break;
            }
        }



        if (($order->customer['country']['iso_code_2'] == 'NL' || $order->customer['country']['iso_code_2'] == 'DE') && $order->info['currency'] == 'EUR') {

            //Get svea configuration for each country based on currency
            $sveaConf = getCountryConfigPP($order->info['currency'], $order->customer['country']['iso_code_2']);

            //Split street address and house no
            $streetAddress = preg_split('/ /', $order->customer['street_address'], -1, PREG_SPLIT_NO_EMPTY);

            //Get initials
            $initials = substr($order->customer['firstname'], 0, 1) . substr($order->customer['lastname'], 0, 1);


            /*             * ********** CREATE ORDER FOR EU ****************** */


            //The createOrder Data for Euro
            $request = Array(
                "Auth" => Array(
                    "Username" => $sveaConf['username'],
                    "Password" => $sveaConf['password'],
                    "ClientNumber" => $sveaConf['clientno']
                ),
                "CreateOrderInformation" => Array(
                    "ClientOrderNr" => ($new_order_field['orders_id'] + 1) . '-' . time(),
                    "OrderRows" => array('OrderRow' => $clientInvoiceRows),
                    "CustomerIdentity" => array(
                        //"NationalIdNumber" => '',//$_POST['sveaSSN'],
                        "Email" => $order->customer['email_address'],
                        "PhoneNumber" => $order->customer['telephone'],
                        "FullName" => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                        "Street" => $streetAddress[0],
                        "ZipCode" => $order->customer['postcode'],
                        "HouseNumber" => $streetAddress[1],
                        "Locality" => $order->customer['city'],
                        "CountryCode" => $order->customer['country']['iso_code_2'],
                        "CustomerType" => "Individual",
                        "IndividualIdentity" => array(
                            "FirstName" => $order->customer['firstname'],
                            "LastName" => $order->customer['lastname'],
                            "Initials" => $initials,
                            "BirthDate" => $_POST['sveaSSN_partpayment']
                        )
                    ),
                    "OrderDate" => date(c),
                    "AddressSelector" => $_POST['adressSelectorPP'],
                    "CustomerReference" => "",
                    "OrderType" => "PaymentPlan",
                    "CreatePaymentPlanDetails" => array("CampaignCode" => $_POST['paymentOptions'],
                        "SendAutomaticGiroPaymentForm" => false)
                )
            );
        } else {

            //Get svea configuration for each country based on currency
            $sveaConf = getCountryConfigPP($order->info['currency']);


            /*             * ********** CREATE ORDER FOR NORDIC COUNTRIES ****************** */

            $request = Array(
                "Auth" => Array(
                    "Username" => $sveaConf['username'],
                    "Password" => $sveaConf['password'],
                    "ClientNumber" => $sveaConf['clientno']
                ),
                'Amount' => 0,
                'PayPlan' => Array(
                    'SendAutomaticGiropaymentForm' => false,
                    'ClientPaymentPlanNr' => ($new_order_field['orders_id'] + 1) . '-' . time(),
                    'CampainCode' => $_POST['paymentOptions'],
                    'CountryCode' => $sveaConf['countryCode'],
                    'SecurityNumber' => $_POST['sveaSSN_partpayment'],
                    'IsCompany' => ''
                ),
                "InvoiceRows" => array('ClientInvoiceRowInfo' => $clientInvoiceRows)
            );
        }


        $_SESSION['swp_fakt_request'] = $request;

        return false;
    }

    function before_process() {
        global $order, $order_totals, $language, $billto, $sendto, $db;


        //Put all the data in request tag
        $data['request'] = $_SESSION['swp_fakt_request'];

        $svea_server = (MODULE_PAYMENT_SWPPARTPAY_MODE == 'Test') ? 'https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL' : 'https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL';
        //Call Soap
        $client = new SoapClient($svea_server);


        /*         * ********** RESPONSE HANDLING EUROPE *************** */
        if (($order->customer['country']['iso_code_2'] == 'NL' || $order->customer['country']['iso_code_2'] == 'DE') && $order->info['currency'] == 'EUR') {

            //Make soap call to below method using above data
            $svea_req = $client->CreateOrderEU($data);


            $response = $svea_req->CreateOrderEuResult->Accepted;

            // handle failed payments
            if ($response != '1') {
                $_SESSION['SWP_ERROR'] = $this->responseCodes($svea_req->CreateOrderEuResult->CreateOrderResult->ResultCode);

                $payment_error_return = 'payment_error=' . $svea_req->CreateOrderEuResult->CreateOrderResult->ResultCode;
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
            }


            // handle successful payments
            if ($response == '1') {
                unset($_SESSION['swp_fakt_request']);
                if (isset($svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity))
                    $order->info['securityNumber'] = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->BirthDate;
                else
                    $order->info['securityNumber'] = $svea_req->CreateOrderEuResult->CustomerIdentity->CompanyIdentity->CompanyVatNumber;
            }

            if (isset($svea_req->CreateOrderEuResult->FullName)) {

                $order->billing['firstname'] = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->FirstName;
                $order->billing['lastname'] = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->LastName;
                $order->billing['street_address'] = $svea_req->CreateOrderEuResult->CustomerIdentity->Street . ' ' . $svea_req->CreateOrderEuResult->HouseNumber;
                //$order->billing['suburb']          = $svea_req->CreateOrderEuResult->AddressLine2;
                $order->billing['city'] = $svea_req->CreateOrderEuResult->CustomerIdentity->Locality;
                $order->billing['state'] = '';                    // "state" is not applicable in SWP countries
                $order->billing['postcode'] = $svea_req->CreateOrderEuResult->CustomerIdentity->ZipCode;

                $order->delivery['firstname'] = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->FirstName;
                $order->delivery['lastname'] = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->LastName;
                $order->delivery['street_address'] = $svea_req->CreateOrderEuResult->CustomerIdentity->Street . ' ' . $svea_req->CreateOrderEuResult->HouseNumber;
                //$order->delivery['suburb']         = $svea_req->CreateOrderEuResult->AddressLine2;
                $order->delivery['city'] = $svea_req->CreateOrderEuResult->CustomerIdentity->Locality;
                $order->delivery['state'] = '';                    // "state" is not applicable in SWP countries
                $order->delivery['postcode'] = $svea_req->CreateOrderEuResult->CustomerIdentity->ZipCode;
            }


            /*             * ********** RESPONSE HANDLING NORDIC *************** */
        } else {
            //print_r($data);
            //Make soap call to below method using above data
            $svea_req = $client->CreatePaymentPlan($data);


            $response = $svea_req->CreatePaymentPlanResult->RejectionCode;
            // handle failed payments
            if ($response != 'Accepted') {
                $_SESSION['SWP_ERROR'] = $this->responseCodes($response);

                $payment_error_return = 'payment_error=' . $this->code;
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
            }


            // handle successful payments
            if ($response == 'Accepted') {
                unset($_SESSION['swp_fakt_request']);
                $order->info['securityNumber'] = $svea_req->CreatePaymentPlanResult->SecurityNumber;
            }

            if (isset($svea_req->CreatePaymentPlanResult->LegalName)) {
                $name = explode(',', $svea_req->CreatePaymentPlanResult->LegalName);

                $order->billing['firstname'] = $name[1];
                $order->billing['lastname'] = $name[0];
                $order->billing['street_address'] = $svea_req->CreatePaymentPlanResult->AddressLine1;
                $order->billing['suburb'] = $svea_req->CreatePaymentPlanResult->AddressLine2;
                $order->billing['city'] = $svea_req->CreatePaymentPlanResult->Postarea;
                $order->billing['state'] = '';                    // "state" is not applicable in SWP countries
                $order->billing['postcode'] = $svea_req->CreatePaymentPlanResult->Postcode;

                $order->delivery['firstname'] = $name[1];
                $order->delivery['lastname'] = $name[0];
                $order->delivery['street_address'] = $svea_req->CreatePaymentPlanResult->AddressLine1;
                $order->delivery['suburb'] = $svea_req->CreatePaymentPlanResult->AddressLine2;
                $order->delivery['city'] = $svea_req->CreatePaymentPlanResult->Postarea;
                $order->delivery['state'] = '';                    // "state" is not applicable in SWP countries
                $order->delivery['postcode'] = $svea_req->CreatePaymentPlanResult->Postcode;
            }
        }
        $table = array(
            'PARTPAYMENTSE' => MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE,
            'SHBNP' => MODULE_PAYMENT_SWPPARTPAY_TEXT_TITLE);
    }

    // if payment accepted, insert order into database
    function after_process() {
        global $insert_id, $order;

        $sql_data_array = array('orders_id' => $insert_id,
            'orders_status_id' => $order->info['order_status'],
            'date_added' => 'now()',
            'customer_notified' => 0,
            'comments' => 'Accepted by SveaWebPay ' . date("Y-m-d G:i:s") . ' Security Number #: ' . $order->info['securityNumber']);
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


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
        $db->Execute($common . ", set_function) values ('Enable SveaWebPay PartPay Module', 'MODULE_PAYMENT_SWPPARTPAY_STATUS', 'True', 'Do you want to accept SveaWebPay payments?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
        $db->Execute($common . ") values ('SveaWebPay Username SV', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_SV', 'Testinstallation', 'Username for SveaWebPay Part Payment Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Password SV', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_SV', 'Testinstallation', 'Password for SveaWebPay Part Payment Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Username NO', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NO', 'webpay_test_no', 'Username for SveaWebPay Part Payment Norway', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Password NO', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NO', 'dvn349hvs9+29hvs', 'Password for SveaWebPay Part Payment Norway', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Username FI', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_FI', 'finlandtest', 'Username for SveaWebPay Part Payment Finland', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Password FI', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_FI', 'finlandtest', 'Password for SveaWebPay Part Payment Finland', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Username DK', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DK', 'danmarktest', 'Username for SveaWebPay Part Payment Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Password DK', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DK', 'danmarktest', 'Password for SveaWebPay Part Payment Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Username NL', 'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NL', 'hollandtest', 'Username for SveaWebPay Part Payment Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Password NL', 'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NL', 'hollandtest', 'Password for SveaWebPay Part Payment Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Client no SV', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SV', '59012', '', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Client no NO', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO', '36000', '', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Client no FI', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI', '29992', '', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Client no DK', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK', '60004', '', '6', '0', now())");
        $db->Execute($common . ") values ('SveaWebPay Client no NL', 'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL', '86997', '', '6', '0', now())");
        $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPPARTPAY_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
        $db->Execute($common . ") values ('Accepted Currencies', 'MODULE_PAYMENT_SWPPARTPAY_ALLOWED_CURRENCIES','SEK,NOK,DKK,EUR', 'The accepted currencies, separated by commas.  These <b>MUST</b> exist within your currencies table, along with the correct exchange rates.','6','0',now())");
        $db->Execute($common . ", set_function) values ('Default Currency', 'MODULE_PAYMENT_SWPPARTPAY_DEFAULT_CURRENCY', 'SEK', 'Default currency used, if the customer uses an unsupported currency it will be converted to this. This should also be in the supported currencies list.', '6', '0', now(), 'zen_cfg_select_option(array(\'SEK\',\'NOK\',\'DKK\',\'EUR\'), ')");
        $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
        $db->Execute($common . ", set_function) values ('Display SveaWebPay Images', 'MODULE_PAYMENT_SWPPARTPAY_IMAGES', 'True', 'Do you want to display SveaWebPay images when choosing between payment options?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
        $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPPARTPAY_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
        $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPPARTPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
        $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    }

    // standard uninstall function
    function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    // must perfectly match keys inserted in install function
    function keys() {
        return array('MODULE_PAYMENT_SWPPARTPAY_STATUS',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_SV',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_SV',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_SV',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NO',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NO',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NO',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_FI',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_FI',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_FI',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_DK',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_DK',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_DK',
            'MODULE_PAYMENT_SWPPARTPAY_USERNAME_NL',
            'MODULE_PAYMENT_SWPPARTPAY_PASSWORD_NL',
            'MODULE_PAYMENT_SWPPARTPAY_CLIENTNO_NL',
            'MODULE_PAYMENT_SWPPARTPAY_MODE',
            'MODULE_PAYMENT_SWPPARTPAY_ALLOWED_CURRENCIES',
            'MODULE_PAYMENT_SWPPARTPAY_DEFAULT_CURRENCY',
            'MODULE_PAYMENT_SWPPARTPAY_ORDER_STATUS_ID',
            'MODULE_PAYMENT_SWPPARTPAY_IMAGES',
            'MODULE_PAYMENT_SWPPARTPAY_IGNORE',
            'MODULE_PAYMENT_SWPPARTPAY_ZONE',
            'MODULE_PAYMENT_SWPPARTPAY_SORT_ORDER');
    }

    function convert_to_currency($value, $currency) {
        global $currencies;
        // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
        return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $decimal_places), 2, $decimal_symbol, '');
    }

    //Error Responses
    function responseCodes($err) {
        switch ($err) {
            case "CusomterCreditRejected" :
                return ERROR_CODE_1;
                break;
            case "CustomerOverCreditLimit" :
                return ERROR_CODE_2;
                break;
            case "CustomerAbuseBlock" :
                return ERROR_CODE_3;
                break;
            case "OrderExpired" :
                return ERROR_CODE_4;
                break;
            case "ClientOverCreditLimit" :
                return ERROR_CODE_5;
                break;
            case "OrderOverSveaLimit" :
                return ERROR_CODE_6;
                break;
            case "OrderOverClientLimit" :
                return ERROR_CODE_7;
                break;
            case "CustomerSveaRejected" :
                return ERROR_CODE_8;
                break;
            case "CustomerCreditNoSuchEntity" :
                return ERROR_CODE_9;
                break;
            case "20001" :
                return ERROR_CODE_20001;
                break;
            case "20002" :
                return ERROR_CODE_20002;
                break;
            case "20003" :
                return ERROR_CODE_20003;
                break;
            case "20004" :
                return ERROR_CODE_20004;
                break;
            case "20005" :
                return ERROR_CODE_20005;
                break;
            case "20006" :
                return ERROR_CODE_20006;
                break;
            case "20007" :
                return ERROR_CODE_20007;
                break;
            case "20008" :
                return ERROR_CODE_20008;
                break;
            case "20000" :
                return ERROR_CODE_20000;
                break;
            case "30000" :
                return ERROR_CODE_30000;
                break;
            case "30001" :
                return ERROR_CODE_30001;
                break;
            case "30002" :
                return ERROR_CODE_30002;
                break;
            default :
                return ERROR_CODE_DEFAULT;
                break;
        }
    }

}

?>
