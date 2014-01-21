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
        $this->handling_fee = defined('MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE') ? MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE : 0.0;
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

        // these are the countries we support getAddress in (getAddress also depends on sveaSSN being present)
        if( ($customer_country == 'SE') ||
            ($customer_country == 'NO') ||
            ($customer_country == 'DK') )
        {
            $sveaAddressDD =    '<br /><label for ="sveaAddressSelector" style="display:none">' . FORM_TEXT_INVOICE_ADDRESS . '</label><br />' .
                                '<select name="sveaAddressSelector" id="sveaAddressSelector" style="display:none"></select><br />';
        }

        // if customer is located in Netherlands, get initials
        if( $customer_country == 'NL' ) {

            $sveaInitialsDiv =  '<div id="sveaInitials_div" >' .
                                    '<label for="sveaInitials">' . FORM_TEXT_INITIALS . '</label><br />' .
                                    '<input type="text" name="sveaInitials" id="sveaInitials" maxlength="5" />' .
                                '</div><br />';
        }

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

            //Years from 1913 to date('Y')
            $years = '';
            for($y = 1913; $y <= date('Y'); $y++){
                $selected = "";
                if( $y == (date('Y')-30) )      // selected is backdated 30 years
                    $selected = "selected";

                $years .= "<option value='$y' $selected>$y</option>";
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
        if( $post_sveaIsCompany === 'true')
        {
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
            $pattern = "/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*(?:\s*[0-9]*)?[^\s])?(?:\s)*$/";         
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
        else    // customer is private individual
        {
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
            $pattern = "/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*(?:\s*[0-9]*)?[^\s])?(?:\s)*$/";
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

        // store our order object in session, to be retrieved in before_process()
        $_SESSION["swp_order"] = serialize($swp_order);

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

    // if payment accepted, set addresses based on response, insert order into database
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
        if( $this->getCurrentOrderStatus( $new_order_id ) == MODULE_PAYMENT_SWPINVOICE_AUTODELIVER ) 
        {
            $deliverResponse = $this->doDeliverOrderInvoice($new_order_id);
            if( $deliverResponse->accepted == true )
            {                    
                $comment = 'Order AutoDelivered. (SveaOrderId: ' .$this->getSveaOrderId( $new_order_id ). ')';                
                //$this->insertOrdersStatus( $new_order_id, SVEA_ORDERSTATUS_DELIVERED_ID, $comment );
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
        $db->Execute($common . ", set_function) values ('Auto Deliver Order', 'MODULE_PAYMENT_SWPINVOICE_AUTODELIVER', '3', 'AutoDeliver: When the order status of an order is set to this value, it will be delivered to Svea. Use in conjunction with Set Order Status above to autodeliver orders.', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(')");
        $db->Execute($common . ", set_function) values ('Invoice Distribution type', 'MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE', 'Post', 'Deliver orders per Post or Email? NOTE: This must match your Svea admin settings or invoices may be non-delivered. Ask your Svea integration manager if unsure.', '6', '0', now(), 'zen_cfg_select_option(array(\'Post\', \'Email\'), ')");
        $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPINVOICE_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
        $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPINVOICE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
        $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPINVOICE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        
        // insert svea order table if not exists already
        $res = $db->Execute("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '". DB_DATABASE ."' AND table_name = 'svea_order';");
        if( $res->fields["COUNT(*)"] != 1 ) {
            $sql = "CREATE TABLE svea_order (orders_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, sveaorderid INT NOT NULL, createorder_object BLOB, invoice_id INT )";
            $db->Execute( $sql );
        }     
        
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
    }
    // standard uninstall function
    function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        
        // we don't delete svea_order tables, as data may be needed by other payment modules and to admin orders etc.        
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
    
    // Localize Error Responses
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
     * Given an orderID, reconstruct the svea order object and send deliver order request, return response
     * 
     * @param int $oID -- $oID is the order id
     * @return Svea\DeliverOrderResult
     */
    function doDeliverOrderInvoice($oID) {   
        global $db;

        // get zencart order from db
        $order = new order($oID); 
        
        // get svea order id reference returned in createOrder request result
        $sveaOrderId = $this->getSveaOrderId( $oID );
        $swp_order = $this->getSveaCreateOrderObject( $oID );
        
        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_deliverOrder = WebPay::deliverOrder( $sveaConfig )
            ->setInvoiceDistributionType( MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE )
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

        $swp_deliverResponse = $swp_deliverOrder->deliverInvoiceOrder()->doRequest(); 

        // if deliverorder accepted, update svea_order table with Svea invoiceId
        if( $swp_deliverResponse->accepted == true ) 
        {
            $db->Execute(   
                "update svea_order " .
                "set invoice_id = " . $swp_deliverResponse->invoiceId . " " .
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
            
        case MODULE_PAYMENT_SWPINVOICE_AUTODELIVER:  
            // deliver if new status == autodeliver status setting
        case SVEA_ORDERSTATUS_DELIVERED_ID:
            $deliverResult = $this->doDeliverOrderInvoice($oID);
            
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
        $invoiceId = $this->getSveaInvoiceId( $oID );
        $swp_order = $this->getSveaCreateOrderObject( $oID );
        
        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

        $swp_creditInvoice = WebPay::deliverOrder($sveaConfig)
                ->setInvoiceDistributionType( MODULE_PAYMENT_SWPINVOICE_DISTRIBUTIONTYPE )
                ->setOrderId($sveaOrderId)                                                  //Required, received when creating an order     
        ;
     
        // ~hack, exploits CreateOrderRow objects having public properties...
        $swp_creditInvoice->orderRows = $swp_order->orderRows;
        $swp_creditInvoice->shippingFeeRows = $swp_order->shippingFeeRows;
        $swp_creditInvoice->invoiceFeeRows = $swp_order->invoiceFeeRows;
        $swp_creditInvoice->fixedDiscountRows = $swp_order->fixedDiscountRows;
        $swp_creditInvoice->relativeDiscountRows = $swp_order->relativeDiscountRows;
        $swp_creditInvoice->countryCode = $swp_order->countryCode;
        // /hack

        $swp_creditResponse = $swp_creditInvoice->setCreditInvoice($invoiceId)->deliverInvoiceOrder()->doRequest();       
        
        if( $swp_creditResponse->accepted == true )
        {    
            $comment = 'Svea invoice credited. ' . '(Svea invoiceId: ' . $invoiceId . ')';
            $status = SVEA_ORDERSTATUS_CREDITED_ID;

        }
        else    // creditOrder failed, insert error in history
        {
            $comment =  'WARNING: Credit invoice failed, status not changed. ' . 
                        'Error: ' . $swp_creditResponse->errormessage . ". (InvoiceId: " . $invoiceId  . ')'; 
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
    
    // called when we want to cancel (close) an undelivered order
    function _doVoid($oID, $from_doStatusUpdate = false ) {      
        global $db;
        
        $sveaOrderId = $this->getSveaOrderId( $oID );
        $swp_order = $this->getSveaCreateOrderObject( $oID );
        
        // Create and initialize order object, using either test or production configuration
        $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

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

        $swp_closeResponse = $swp_closeOrder->closeInvoiceOrder()->doRequest();       

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
}    
?>