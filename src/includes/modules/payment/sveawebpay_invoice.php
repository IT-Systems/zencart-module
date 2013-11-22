<?php

/*
  HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
  -----------------------------------------------
  Version 4.1 - Zen Cart
  Kristian Grossman-Madsen, Shaho Ghobadi
 */

// Include Svea php integration package files
require_once(DIR_FS_CATALOG . 'svea/Includes.php');         // use new php integration package for v4
require_once(DIR_FS_CATALOG . 'sveawebpay_config.php');     // sveaConfig implementation

require_once(DIR_FS_CATALOG . 'sveawebpay_common.php');     // zencart module common functions

class sveawebpay_invoice extends SveaZencart {
    
    function sveawebpay_invoice() {
        global $order;

        $this->code = 'sveawebpay_invoice';
        $this->version = 4;

        $_SESSION['SWP_CODE'] = $this->code;

        $this->title = MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_SWPINVOICE_STATUS == 'True') ? true : false);
        $this->sort_order = MODULE_PAYMENT_SWPINVOICE_SORT_ORDER;
        $this->sveawebpay_url = MODULE_PAYMENT_SWPINVOICE_URL;
        $this->handling_fee = defined('MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE') ? MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE : 0.0; //if not set, use 0
        $this->default_currency = MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY;
        $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES);
        $this->display_images = ((MODULE_PAYMENT_SWPINVOICE_IMAGES == 'True') ? true : false);
        $this->ignore_list = explode(',', MODULE_PAYMENT_SWPINVOICE_IGNORE);
        if ((int)MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID > 0)
            $this->order_status = MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID;
        if (is_object($order))
            $this->update_status();
    }

    function update_status() {
        global $db, $order, $currencies, $messageStack;

        // update internal currency
        $this->default_currency = MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY;
        $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES);

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
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_SWPINVOICE_ZONE > 0)) {
            $check_flag = false;
            $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SWPINVOICE_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

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

        // add svea invoice image file
             $fields[] = array('title' => '<img src=images/Svea/SVEAINVOICEEU_'.$order->customer['country']['iso_code_2'].'.png />', 'field' => '');

        // catch and display error messages raised when i.e. payment request from before_process() below turns out not accepted
        if (isset($_REQUEST['payment_error']) && $_REQUEST['payment_error'] == 'sveawebpay_invoice') {
            $fields[] = array('title' => '<span style="color:red">' . $_SESSION['SWP_ERROR'] . '</span>', 'field' => '');
        }

        // insert svea js
        $sveaJs =   '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>' .
                    '<script type="text/javascript" src="' . $this->web_root . 'includes/modules/payment/svea.js"></script>';
        $fields[] = array('title' => '', 'field' => $sveaJs);

        //
        // get required fields depending on customer country and payment method

        // customer country is taken from customer settings
        $customer_country = $order->customer['country']['iso_code_2'];

        // fill in all fields as required by customer country and payment method
        $sveaAddressDD = $sveaInitialsDiv = $sveaBirthDateDiv  = '';

        // get ssn & selects private/company for SE, NO, DK, FI
        if( ($customer_country == 'SE') ||     // e.g. == 'SE'
            ($customer_country == 'NO') ||
            ($customer_country == 'DK') )
        {
            // input text field for individual/company SSN
            $sveaSSN =          FORM_TEXT_SS_NO . '<br /><input type="text" name="sveaSSN" id="sveaSSN" maxlength="11" /><br />';
        }

        if( ($customer_country == 'FI') )
        {
           // input text field for individual/company SSN, without getAddresses hook
            $sveaSSNFI =        FORM_TEXT_SS_NO . '<br /><input type="text" name="sveaSSNFI" id="sveaSSNFI" maxlength="11" /><br />';
        }

        // radiobutton for choosing individual or organization
        $sveaIsCompanyField =
                            '<label><input type="radio" name="sveaIsCompany" value="false" checked>' . FORM_TEXT_PRIVATE . '</label>' .
                            '<label><input type="radio" name="sveaIsCompany" value="true">' . FORM_TEXT_COMPANY . '</label><br />';


        //
        // these are the countries we support getAddress in (getAddress also depends on sveaSSN being present)
        if( ($customer_country == 'SE') ||
            ($customer_country == 'NO') ||
            ($customer_country == 'DK') )
        {
            $sveaAddressDD =    '<br /><label for ="sveaAddressSelector" style="display:none">' . FORM_TEXT_INVOICE_ADDRESS . '</label><br />' .
                                '<select name="sveaAddressSelector" id="sveaAddressSelector" style="display:none"></select><br />';
        }

        //
        // if customer is located in Netherlands, get initials
        if( $customer_country == 'NL') {

            $sveaInitialsDiv =  '<div id="sveaInitials_div" >' .
                                    '<label for="sveaInitials">' . FORM_TEXT_INITIALS . '</label><br />' .
                                    '<input type="text" name="sveaInitials" id="sveaInitials" maxlength="5" />' .
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
            $birthDay = "<select name='sveaBirthDay' id='sveaBirthDay'>$days</select>";

            //Months to 12
            $months = "";
            for($m = 1; $m <= 12; $m++){
                $val = $m;
                if($m < 10)
                    $val = "$m";

                $months .= "<option value='$val'>$m</option>";
            }
            $birthMonth = "<select name='sveaBirthMonth' id='sveaBirthMonth'>$months</select>";

            //Years from 1913 to 1996
            $years = '';
            for($y = 1913; $y <= 1996; $y++){
                if( $y == 1980 )
                    $years .= "<option value='$y' selected>$y</option>"; // sensible default
                else
                    $years .= "<option value='$y'>$y</option>";
                
            }
            
            $birthYear = "<select name='sveaBirthYear' id='sveaBirthYear'>$years</select>";

            $sveaBirthDateDiv = '<div id="sveaBirthDate_div" >' .
                                    //'<label for="sveaBirthDate">' . FORM_TEXT_BIRTHDATE . '</label><br />' .
                                    //'<input type="text" name="sveaBirthDate" id="sveaBirthDate" maxlength="8" />' .
                                    '<label for="sveaBirthYear">' . FORM_TEXT_BIRTHDATE . '</label><br />' .
                                    $birthYear . $birthMonth . $birthDay .
                                '</div><br />';

            $sveaVatNoDiv = '<div id="sveaVatNo_div" hidden="true">' .
                                    '<label for="sveaVatNo" >' . FORM_TEXT_VATNO . '</label><br />' .
                                    '<input type="text" name="sveaVatNo" id="sveaVatNo" maxlength="14" />' .
                                '</div><br />';
        }

        //
        // add information about handling fee for invoice payment method
        if (isset($this->handling_fee) && $this->handling_fee > 0) {
            $paymentfee_cost = $this->handling_fee;

            // is the handling fee a percentage?
            if (substr($paymentfee_cost, -1) == '%')
                $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES, $paymentfee_cost));

            // no, handling fee is a fixed amount
            else {
                $tax_class = MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS;
                if (DISPLAY_PRICE_WITH_TAX == "true" && $tax_class > 0) {
                    // calculate tax based on deliver country?
                    $paymentfee_tax =
                        $paymentfee_cost * zen_get_tax_rate($tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) / 100;
                }
            }

            $sveaHandlingFee =
                '<br />' . sprintf( MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES, $currencies->format($paymentfee_cost + $paymentfee_tax));
        }

        $sveaError = '<br /><span id="sveaSSN_error_invoice" style="color:red"></span>';
        if($order->billing['country']['iso_code_2'] == "SE" || $order->billing['country']['iso_code_2'] == "DK"){
             $sveaSubmitAddress = '<button id="sveaSubmitGetAddress" type="button">'.FORM_TEXT_GET_ADDRESS.'</button>';
        }

        // create and add the field to be shown by our js when we select SveaInvoice payment method
        $sveaField =    '<div id="sveaInvoiceField" style="display:none">' .
                            $sveaIsCompanyField .   //  SE, DK, NO
                            $sveaSSN .              //  SE, DK, NO
                            $sveaSSNFI .            //  FI, no getAddresses
                            $sveaSubmitAddress.
                            $sveaAddressDD .        //  SE, Dk, NO
                            $sveaInitialsDiv .      //  NL
                            $sveaBirthDateDiv .     //  NL, DE
                            $sveaVatNoDiv .         // NL, DE
                            $sveaHandlingFee .
                            // FI, NL, DE also uses customer address data from zencart
                        '</div>';

        $fields[] = array('title' => '', 'field' => '<br />' . $sveaField . $sveaError);

        $_SESSION["swp_order_info_pre_coupon"]  = serialize($order->info);  // store order info needed to reconstruct amount pre coupon later

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
        $post_sveaSSN = isset($_POST['sveaSSN']) ? $_POST['sveaSSN'] : "swp_not_set" ;
        $post_sveaSSNFI = isset($_POST['sveaSSNFI']) ? $_POST['sveaSSNFI'] : "swp_not_set" ;
        $post_sveaIsCompany = isset($_POST['sveaIsCompany']) ? $_POST['sveaIsCompany'] : "swp_not_set" ;
        $post_sveaAddressSelector = isset($_POST['sveaAddressSelector']) ? $_POST['sveaAddressSelector'] : "swp_not_set";
        $post_sveaVatNo = isset($_POST['sveaVatNo']) ? $_POST['sveaVatNo'] : "swp_not_set";
        $post_sveaBirthDay = isset($_POST['sveaBirthDay']) ? $_POST['sveaBirthDay'] : "swp_not_set";
        $post_sveaBirthMonth = isset($_POST['sveaBirthMonth']) ? $_POST['sveaBirthMonth'] : "swp_not_set";
        $post_sveaBirthYear = isset($_POST['sveaBirthYear']) ? $_POST['sveaBirthYear'] : "swp_not_set";
        $post_sveaInitials = isset($_POST['sveaInitials']) ? $_POST['sveaInitials'] : "swp_not_set" ;

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
                
        $currency = $this->getCurrency($order->info['currency']);

        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_order = WebPay::createOrder( $sveaConfig )
            ->setCountryCode( $user_country )
            ->setCurrency($currency)                       //Required for card & direct payment and PayPage payment.
            ->setClientOrderNumber($client_order_number)   //Required for card & direct payment, PaymentMethod payment and PayPage payments
            ->setOrderDate(date('c'))                      //Required for synchronous payments
        ;

        //
        // for each item in cart, create WebPayItem::orderRow objects and add to order
        foreach ($order->products as $productId => $product) {

            $amount_ex_vat = floatval( $this->convertToCurrency(round($product['final_price'], 2), $currency) );

            $swp_order->addOrderRow(
                    WebPayItem::orderRow()
                            ->setQuantity($product['qty'])          //Required
                            ->setAmountExVat($amount_ex_vat)          //Optional, see info above
                            ->setVatPercent(intval($product['tax']))  //Optional, see info above
                            ->setDescription($product['name'])        //Optional
           );
        }

        $swp_order = $this->parseOrderTotals( $order_totals, $swp_order );

        // Check if customer is company
        if( $post_sveaIsCompany === 'true'){

            // create company customer object
            $swp_customer = WebPayItem::companyCustomer();

            // set company name
            $swp_customer->setCompanyName( $order->billing['company'] );

            // set company SSN
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

            // set addressSelector from getAddresses
            if( ($user_country == 'SE') ||
                ($user_country == 'NO') ||
                ($user_country == 'DK') )
            {
                $swp_customer->setAddressSelector( $post_sveaAddressSelector );
            }

            // set vatNo
            if( ($user_country == 'NL') ||
                ($user_country == 'DE') )
            {
                $swp_customer->setVatNumber( $post_sveaVatNo );
            }

            //Split street address and house no
            $pattern ="/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/";
            $myStreetAddress = Array();
            preg_match( $pattern, $order->billing['street_address'], $myStreetAddress  );
            if( !array_key_exists( 2, $myStreetAddress ) ) { $myStreetAddress[2] = ""; }

            // set common fields
            $swp_customer
                ->setStreetAddress( $myStreetAddress[1], $myStreetAddress[2] )
                ->setZipCode($order->billing['postcode'])
                ->setLocality($order->billing['city'])
                ->setEmail($order->customer['email_address'])
                ->setIpAddress($_SERVER['REMOTE_ADDR'])
                ->setCoAddress($order->billing['suburb'])                       // c/o address
                ->setPhoneNumber($order->customer['telephone']);

            // add customer to order
            $swp_order->addCustomerDetails($swp_customer);
        }
        // customer is private individual
        else {

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

            //Split street address and house no
            $pattern ="/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/";
            $myStreetAddress = Array();
            preg_match( $pattern, $order->billing['street_address'], $myStreetAddress  );
            if( !array_key_exists( 2, $myStreetAddress ) ) { $myStreetAddress[2] = ""; }

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
        }

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

        // send payment request to svea, receive response
        $swp_response = $swp_order->useInvoicePayment()->doRequest();

        // payment request failed; handle this by redirecting w/result code as error message
        if ($swp_response->accepted === false) {
            $_SESSION['SWP_ERROR'] = $this->responseCodes($swp_response->resultcode,$swp_response->errormessage);
            $payment_error_return = 'payment_error=sveawebpay_invoice';
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
            else {
                $order->billing['company'] = $swp_response->customerIdentity->fullName;
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

    //
    // if payment accepted, set addresses based on response, insert order into database
    function after_process() {
        global $insert_id, $order, $db;

        $new_order_id = $insert_id;  // $insert_id contains the new order orders_id
        
        // retrieve response object from before_process()
        $swp_response = unserialize($_SESSION["swp_response"]);

        // store create order object along with response sveaOrderId in db
        $sql_data_array = array(
            'orders_id' => $new_order_id,
            'sveaorderid' => $swp_response->sveaOrderId,
            'createorder_object' => $_SESSION["swp_order"]      // session data is already serialized
        );
        zen_db_perform("svea_order", $sql_data_array);
   
        // if autodeliver option set, deliver order
        if( MODULE_PAYMENT_SWPINVOICE_AUTODELIVER == "True" ) {
 
            $sveaOrderId = $this->doDeliverOrder($insert_id);
            if( $sveaOrderId != false ) {
           
                // insert autodeliver order status update in database
                $sql_data_array = array(
                    'orders_id' => $new_order_id,
                    'orders_status_id' => 3,  // Magic number 3 from "Delivered [3]"                             
                    'date_added' => 'now()',
                    'customer_notified' => 1,
                    'comments' => 'AutoDelivered ' . date("Y-m-d G:i:s") . ' SveaOrderId: ' . $sveaOrderId 
                );
                zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                
                // make sure order status shows up as "delivered" in admin orders list
                $db->Execute(   "update " . TABLE_ORDERS . " " .
                                "set orders_status = '" . 3 . "', " .    // Magic number 3 from "Delivered [3]"
                                "last_modified = now() " .     
                                "where orders_id = '" . $new_order_id . "'")
                ;
            }
            else {
                // we do nothing, as order will show up as undelivered in admin order overview
            }          
        }
        
        // clean up our session variables set during checkout   //$SESSION[swp_*
        unset($_SESSION['swp_order']);
        unset($_SESSION['swp_response']);

        return false;
    }

    // sets error message to the GET error value
    function get_error() {
        return array('title' => ERROR_MESSAGE_PAYMENT_FAILED, 'error' => stripslashes(urldecode($_GET['swperror'])));
    }

    // standard check if installed function
    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SWPINVOICE_STATUS'");
            $this->_check = !$check_rs->EOF;
        }
        return $this->_check;
    }

    // insert configuration keys here
    function install() {
        global $db;
        $common = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
        $db->Execute($common . ", set_function) values ('Enable Svea Invoice Module', 'MODULE_PAYMENT_SWPINVOICE_STATUS', 'True', 'Do you want to accept Svea payments?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
        $db->Execute($common . ") values ('Svea Username SV', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_SV', '', 'Username for Svea Invoice Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password SV', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_SV', '', 'Password for Svea Invoice Sweden', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Username NO', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_NO', '', 'Username for Svea Invoice Norway', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password NO', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NO', '', 'Password for Svea Invoice Norway', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Username FI', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_FI', '', 'Username for Svea Invoice Finland', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password FI', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_FI', '', 'Password for Svea Invoice Finland', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Username DK', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_DK', '', 'Username for Svea Invoice Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password DK', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_DK', '', 'Password for Svea Invoice Denmark', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Username NL', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_NL', '', 'Username for Svea Invoice Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password NL', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NL', '', 'Password for Svea Invoice Netherlands', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Username DE', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_DE', '', 'Username for Svea Invoice Germany', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Password DE', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_DE', '', 'Password for Svea Invoice Germany', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no SV', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_SV', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no NO', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NO', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no FI', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_FI', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no DK', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DK', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no NL', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NL', '', '', '6', '0', now())");
        $db->Execute($common . ") values ('Svea Client no DE', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DE', '', '', '6', '0', now())");
        $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPINVOICE_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
        $db->Execute($common . ") values ('Accepted Currencies', 'MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES','SEK,NOK,DKK,EUR', 'The accepted currencies, separated by commas.  These <b>MUST</b> exist within your currencies table, along with the correct exchange rates.','6','0',now())");
        $db->Execute($common . ", set_function) values ('Default Currency', 'MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY', 'SEK', 'Default currency used, if the customer uses an unsupported currency it will be converted to this. This should also be in the supported currencies list.', '6', '0', now(), 'zen_cfg_select_option(array(\'SEK\',\'NOK\',\'DKK\',\'EUR\'), ')");
        $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value (but see AutoDeliver option below).', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
        $db->Execute($common . ", set_function) values ('Auto Deliver Order', 'MODULE_PAYMENT_SWPINVOICE_AUTODELIVER', 'False', 'Do you want to autodeliver order invoices? This will override the the Set Order Status setting above.', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
        $db->Execute($common . ", set_function) values ('Invoice Distribution type', 'MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE', 'Post', 'Deliver orders per Post or Email? NOTE: This must match your Svea admin settings or invoices may be non-delivered. Ask your Svea integration manager if unsure.', '6', '0', now(), 'zen_cfg_select_option(array(\'Post\', \'Email\'), ')");
        $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPINVOICE_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
        $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPINVOICE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
        $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPINVOICE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        
        // insert svea order id table if not exists already
        $res = $db->Execute("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '". DB_DATABASE ."' AND table_name = 'svea_sveaorderid';");
        if( $res->fields["COUNT(*)"] != 1 ) {
            $db->Execute("CREATE TABLE svea_sveaorderid (orders_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, sveaorderid INT NOT NULL)");
        }

        // insert svea order table if not exists already
        $sql = "CREATE TABLE svea_order (orders_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, sveaorderid INT NOT NULL, createorder_object BLOB )";
        $res = $db->Execute("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '". DB_DATABASE ."' AND table_name = 'svea_sveaorderid';");
        if( $res->fields["COUNT(*)"] != 1 ) {
            $db->Execute( $sql );
        }     
    }
    // standard uninstall function
    function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        // we don't delete the svea_ tables, as they may be needed by other payment modules and to admin orders etc.
    }

    // must perfectly match keys inserted in install function
    function keys() {
        return array('MODULE_PAYMENT_SWPINVOICE_STATUS',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_SV',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_SV',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_SV',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_NO',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NO',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NO',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_FI',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_FI',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_FI',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_DK',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_DK',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DK',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_NL',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NL',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NL',
            'MODULE_PAYMENT_SWPINVOICE_USERNAME_DE',
            'MODULE_PAYMENT_SWPINVOICE_PASSWORD_DE',
            'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DE',
            'MODULE_PAYMENT_SWPINVOICE_MODE',
            'MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES',
            'MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY',
            'MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID',
            'MODULE_PAYMENT_SWPINVOICE_AUTODELIVER',
            'MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE',
            'MODULE_PAYMENT_SWPINVOICE_IGNORE',
            'MODULE_PAYMENT_SWPINVOICE_ZONE',
            'MODULE_PAYMENT_SWPINVOICE_SORT_ORDER');
    }
    
    /**
     * Localize Error Responses
     */
    function responseCodes($err,$msg = NULL) {
        switch ($err) {

            // EU error codes
            case "20000" :
                return ERROR_CODE_20000;
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
            case "20013" :
                return ERROR_CODE_20013;
                break;

            case "24000" :
                return ERROR_CODE_24000;
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
            case "30003" :
                return ERROR_CODE_30003;
                break;

            case "40000" :
                return ERROR_CODE_40000;
                break;
            case "40001" :
                return ERROR_CODE_40001;
                break;
            case "40002" :
                return ERROR_CODE_40002;
                break;
            case "40004" :
                return ERROR_CODE_40004;
                break;

            case "50000" :
                return ERROR_CODE_50000;
                break;

            default :
                return ERROR_CODE_DEFAULT . " " . $err . " - " . $msg;     // $err here is the response->resultcode
                break;
        }
    } 

    
    /**
     * Given an orderID, reconstruct the svea order object and send deliver order request. 
     * returns false if the deliver order request was accepted, else returns sveaOrderId
     * 
     * @param int $oID -- $oID is the order id
     * @return int -- false (0) or sveaOrderId 
     */
    function doDeliverOrder($oID) {   
        global $db;

        // get zencart order from db
        $order = new order($oID); 
        
        // get svea order id reference returned in createOrder request result
        $result = $db->Execute("SELECT sveaorderid, createorder_object FROM svea_order WHERE orders_id = " . (int)$oID );
        $sveaOrderId = $result->fields["sveaorderid"];
        $swp_order = unserialize( $result->fields["createorder_object"] );
        
        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_deliverOrder = WebPay::deliverOrder( $sveaConfig )
            ->setInvoiceDistributionType( MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE )
            ->setOrderId($sveaOrderId)                                  
        ;
     
            // this really exploits CreateOrderRow objects having public properties...
            // ~hack
            $swp_deliverOrder->orderRows = $swp_order->orderRows;
            $swp_deliverOrder->shippingFeeRows = $swp_order->shippingFeeRows;
            $swp_deliverOrder->invoiceFeeRows = $swp_order->invoiceFeeRows;
            $swp_deliverOrder->fixedDiscountRows = $swp_order->fixedDiscountRows;
            $swp_deliverOrder->relativeDiscountRows = $swp_order->relativeDiscountRows;
            $swp_deliverOrder->countryCode = $swp_order->countryCode;
            // /hack

            $swp_deliveryResponse = $swp_deliverOrder->deliverInvoiceOrder()->doRequest();       
       
        // return true/false depending on deliver order response
        return ($swp_deliveryResponse->accepted == 1) ? $sveaOrderId : 0;
    }   

    /**
     * parseOrderTotals() goes through the zencart order order_totals for diverse non-product
     * order rows and updates the svea order object with the appropriate shipping, handling
     * and discount rows.
     * 
     * @param array $order_totals
     * @param createOrderBuilder or deliverOrderBuilder $svea_order
     * @return createOrderBuilder or deliverOrderBuilder -- the updated $svea_order object
     */
    function parseOrderTotals( $order_totals, &$svea_order ) {
        global $db, $order;
        
        $currency = $this->getCurrency($order->info['currency']);
        
        foreach ($order_totals as $ot_id => $order_total) {

            switch ($order_total['code']) {

                // ignore these order_total codes
                case in_array( $order_total['code'], $this->ignore_list):
                case 'ot_subtotal':
                case 'ot_total':
                case 'ot_tax':
                    // do nothing
                    break;

                // if shipping fee, create WebPayItem::shippingFee object and add to order
                case 'ot_shipping':

                    // makes use of zencart $order-info[] shipping information to populate object
                    // shop shows prices including tax, take this into accord when calculating tax
                    if (DISPLAY_PRICE_WITH_TAX == 'false') {
                        $amountExVat = $order->info['shipping_cost'];
                        $amountIncVat = $order->info['shipping_cost'] + $order->info['shipping_tax'];
                    }
                    else {
                        $amountExVat = $order->info['shipping_cost'] - $order->info['shipping_tax'];
                        $amountIncVat = $order->info['shipping_cost'] ;
                    }

                    // add WebPayItem::shippingFee to swp_order object
                    $svea_order->addFee(
                            WebPayItem::shippingFee()
                                    ->setDescription($order->info['shipping_method'])
                                    ->setAmountExVat( $amountExVat )
                                    ->setAmountIncVat( $amountIncVat )
                    );
                    break;

                // if handling fee applies, create WebPayItem::invoiceFee object and add to order
                case 'sveawebpay_handling_fee' :

                    // is the handling_fee module activated?
                    if (isset($this->handling_fee) && $this->handling_fee > 0) {

                        // handlingfee expressed as percentage?
                        if (substr($this->handling_fee, -1) == '%') {

                            // sum of products + shipping * handling_fee as percentage
                            $hf_percentage = floatval(substr($this->handling_fee, 0, -1));

                            $hf_price = ($order->info['subtotal'] + $order->info['shipping_cost']) * ($hf_percentage / 100.0);
                        }
                        // handlingfee expressed as absolute amount (incl. tax)
                        else {
                            $hf_price = $this->convertToCurrency(floatval($this->handling_fee), $currency);
                        }
                        $hf_taxrate =   zen_get_tax_rate(MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS,
                                        $order->delivery['country']['id'], $order->delivery['zone_id']);

                        // add WebPayItem::invoiceFee to swp_order object
                        $svea_order->addFee(
                                WebPayItem::invoiceFee()
                                        ->setName($order_total['title'])
                                        ->setDescription($order_total['text'])
                                        ->setAmountExVat($hf_price)
                                        ->setVatPercent($hf_taxrate)
                        );
                    }
                    break;

                case 'ot_coupon':
                    // zencart coupons are made out as either amount x.xx or a percentage y%.
                    // Both of these are calculated by zencart via the order total module ot_coupon.php and show up in the
                    // corresponding $order_totals[...]['value'] field.
                    //
                    // Depending on the module settings the value may differ, Svea assumes that the (zc 1.5.1) default settings
                    // are being used:
                    //
                    // admin/ot_coupon module setting -- include shipping: false, include tax: false, re-calculate tax: standard
                    //
                    // The value contains the total discount amount including tax iff configuration display prices with tax is set to true:
                    //
                    // admin/configuration setting -- display prices with tax: true => ot_coupon['value'] includes vat, if false, excludes vat
                    //
                    // Example:
                    // zc adds an ot_coupon with value of 20 for i.e. a 10% discount on an order of 100 +(25%) + 100 (+6%).
                    // This discount seems to be split in equal parts over the two order item vat rates:
                    // 90*1,25 + 90*1,06 = 112,5 + 95,4 = 207,90, to which the shipping fee of 4 (+25%) is added. The total is 212,90
                    // ot_coupon['value'] is 23,10 iff display prices incuding tax = true, else ot_coupon['value'] = 20
                    //
                    // We handle the coupons by adding a FixedDiscountRow for the amount, specified ex vat. The package
                    // handles the vat calculations.

                    // if display price with tax is not set, svea's package calculations match zencart's and we can use value right away
                    if (DISPLAY_PRICE_WITH_TAX == 'false') {
                        $svea_order->addDiscount(
                            WebPayItem::fixedDiscount()
                                ->setAmountExVat( $order_total['value'] ) // $amountExVat works iff display prices with tax = false in shop
                                ->setDescription( $order_total['title'] )
                        );
                    }
                    // we need to determine the order discount ex. vat if display prices with tax is set to true,
                    // the ot_coupon module calculate_deductions() method returns a value including tax. We try to
                    // reconstruct the amount using the stored order info and the order_totals entries
                    else {
                        $swp_order_info_pre_coupon = unserialize( $_SESSION["swp_order_info_pre_coupon"] );
                        $pre_coupon_subtotal_ex_tax = $swp_order_info_pre_coupon['subtotal'] - $swp_order_info_pre_coupon['tax'];

                        foreach( $order_totals as $key => $ot ) {
                            if( $ot['code'] === 'ot_subtotal' ) {
                                $order_totals_subtotal_ex_tax = $ot['value'];
                            }
                        }
                        foreach( $order_totals as $key => $ot ) {
                            if( $ot['code'] === 'ot_tax' ) {
                                $order_totals_subtotal_ex_tax -= $ot['value'];
                            }
                        }
                        foreach( $order_totals as $key => $ot ) {
                            if( $ot['code'] === 'ot_coupon' ) {
                                $order_totals_subtotal_ex_tax -= $ot['value'];
                            }
                        }

                        $value_from_subtotals = isset( $order_totals_subtotal_ex_tax ) ?
                                ($pre_coupon_subtotal_ex_tax - $order_totals_subtotal_ex_tax) : $order_total['value']; // 'value' fallback

                        // if display_price_with tax is set to true && the coupon was specified as a fixed amount
                        // zencart's math doesn't match svea's, so we force the discount to use the the shop's vat
                        $coupon = $db->Execute("select * from " . TABLE_COUPONS . " where coupon_id = '" . (int)$_SESSION['cc_id'] . "'");

                        // coupon_type is F for coupons specified with a fixed amount
                        if( $coupon->fields['coupon_type'] == 'F' ) {

                            // calculate the vatpercent from zencart's amount: discount vat/discount amount ex vat
                            $zencartDiscountVatPercent =
                                ($order_total['value'] - $coupon->fields['coupon_amount']) / $coupon->fields['coupon_amount'] *100;

                            // split $zencartDiscountVatPercent into allowed values
                            $taxRates = Svea\Helper::getTaxRatesInOrder($svea_order);
                            $discountRows = Svea\Helper::splitMeanToTwoTaxRates( $coupon->fields['coupon_amount'], 
                                    $zencartDiscountVatPercent, $order_total['title'], $order_total['title'], $taxRates );
                            
                            foreach($discountRows as $row) {
                                $svea_order = $svea_order->addDiscount( $row );
                            }

                        }
                        // if coupon specified as a percentage, or as a fixed amount and prices are ex vat.
                        else {
                            $svea_order->addDiscount(
                                WebPayItem::fixedDiscount()
                                    ->setAmountExVat( $value_from_subtotals )
                                    ->setDescription( $order_total['title'] )
                            );
                        }
                    }
                    break;

                // default case attempt to handle 'unknown' items from other plugins, treating negatives as discount rows, positives as fees
                default:
                    $order_total_obj = $GLOBALS[$order_total['code']];
                    $tax_rate = zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);

                    // if displayed WITH tax, REDUCE the value since it includes tax
                    if (DISPLAY_PRICE_WITH_TAX == 'true') {
                        $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));
                    }
                    
                    // write negative amounts as FixedDiscount with the given tax rate, write positive amounts as HandlingFee
                    if( $order_total['value'] < 0 ) {
                        $svea_order->addDiscount(
                            WebPayItem::fixedDiscount()
                                ->setAmountExVat( -1* $this->convertToCurrency(strip_tags($order_total['value']), $currency)) // given as positive amount
                                ->setVatPercent($tax_rate)  //Optional, see info above
                                ->setDescription($order_total['title'])        //Optional
                        );
                    }
                    else {
                        $svea_order->addFee(
                            WebPayItem::invoiceFee()
                                ->setAmountExVat($this->convertToCurrency(strip_tags($order_total['value']), $currency))
                                ->setVatPercent($tax_rate)  //Optional, see info above
                                ->setDescription($order_total['title'])        //Optional
                        );
                    }
                    break;
            }
        }
        
        return $svea_order;
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

        if( $status == 3 ) {    // TODO move magic number to admin settings, should be the same as used for autoDevlivered orders' statuses
                     
            $sveaOrderId = $this->doDeliverOrder($oID);

            // update order_status_history to include comment
            $result = $db->Execute("SELECT sveaorderid FROM svea_order WHERE orders_id = " . (int)$oID );
            $sveaOrderId = $result->fields["sveaorderid"];

            $result = $db->Execute( "select * from orders_status_history where orders_id = ". (int)$oID .
                                    " order by date_added DESC LIMIT 1");
            $oshID = $result->fields["orders_status_history_id"];

            $comment = 'Delivered by status update ' . date("Y-m-d G:i:s") . ' SveaOrderId: ' . $sveaOrderId;

            $db->Execute(   "update " . TABLE_ORDERS_STATUS_HISTORY . " " .
                            "set comments = '" . $comment . "' " .
                            "where orders_status_history_id = " . (int)$oshID)
            ;                   
        }
    }
    
    /**
     * Called from admin/orders.php at the top of the edit order view
     * 
     * @param int $oID

     */
    function admin_notification($oID) {  
        global $db;

        // display svea logo & admin text
        // TODO
        //               
        // check if status = 3 at any time in history (i.e. order has been delivered) => can't do closeOrder()
        $result = $db->Execute( "SELECT COUNT(orders_status_id) AS delivered FROM `orders_status_history` WHERE orders_status_id = 3 AND orders_id = " . $oID );
                
        if( $result->fields['delivered'] == false ) {   // 0 occurances of "delivered" in history
            echo '<div id=SveaCancelOrderDiv>' .
                '<form name=SveaAdminCancelOrder action="http://sveazencart151.se/zc_admin/orders.php" method=get>' .
                    '<input type="hidden" name="oID" value="' . $_GET['oID'] .'" />' .                 
                    '<input type="hidden" name="page" value="' . $_GET['page'] .'" />' .                 
                    '<input type="hidden" name="action" value="doVoid" />' .                 
                    '<input type="submit" value="Svea admin: Cancel order" />' .
                '</form>'.
            '</div>';        
        }
        else {
            // TODO show the deliverbutton? 
        }
    }
    
    // called when we want to cancel an undelivered order
    function _doVoid($oID) {      
        global $db;
        
        // get svea order id reference returned in createOrder request result
        $result = $db->Execute("SELECT sveaorderid, createorder_object FROM svea_order WHERE orders_id = " . (int)$oID );
        $sveaOrderId = $result->fields["sveaorderid"];
        $swp_order = unserialize( $result->fields["createorder_object"] );
        
        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_closeOrder = WebPay::closeOrder($sveaConfig)
            ->setOrderId($sveaOrderId)                                                  //Required, received when creating an order     
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

        $swp_closeResponse = $swp_closeOrder->closeInvoiceOrder()->doRequest();       
    
        if( $swp_closeResponse->accepted == true ) {
            // ta bort order från tabellen & gåt ill sammanställningen
            // TODO
            
        }
        
// DEBUG             
//        $result = $db->Execute( "select * from orders_status_history where orders_id = ". (int)$oID .
//                                " order by date_added DESC LIMIT 1");
//        $oshID = $result->fields["orders_status_history_id"];
//        
//        $db->Execute(   "update " . TABLE_ORDERS_STATUS_HISTORY . " " .
//                        "set comments = '" . time() . "' " .
//                        "where orders_status_history_id = " . (int)$oshID)
//        ;
    } 
}    
?>