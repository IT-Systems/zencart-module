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

        //
        // we need the order total and customer country in ajax functions. as
        // the shop order object is unavailable, store these in session when
        // we enter checkout_payment page (i.e. $order is set
        if( isset($order) ) {
            $_SESSION['sveaAjaxOrderTotal'] = $order->info['total'];
            $_SESSION['sveaAjaxCountryCode'] = $order->customer['country']['iso_code_2'];
        }
        
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
                                    $birthYear . $birthMonth . $birthDay .  // TODO better default, date order conforms w/DE,NL standard? 
                                '</div><br />';

            $sveaVatNoDivPP = '<div id="sveaVatNo_divPP" hidden="true">' . 
                                    '<label for="sveaVatNoPP" >' . FORM_TEXT_VATNO . '</label><br />' .
                                    '<input type="text" name="sveaVatNoPP" id="sveaVatNoPP" maxlength="14" />' . 
                                '</div><br />';
        }
        
        $sveaPaymentOptionsPP = 
            FORM_TEXT_GET_PAYPLAN . '<br /><select name="sveaPaymentOptionsPP" id="sveaPaymentOptionsPP" style="display:none"></select><br />'; 
        
        $sveaError = '<br /><span id="sveaSSN_error_invoicePP" style="color:red"></span>';
   
        // create and add the field to be shown by our js when we select SveaInvoice payment method
        $sveaField =    '<div id="sveaPartPayField" style="display:none">' . 
                            $sveaSSNPP .              //  SE, DK, NO        
                            $sveaSSNFIPP .            //  FI, no getAddresses     
                            $sveaAddressDDPP .        //  SE, Dk, NO   
                            $sveaInitialsDivPP .      //  NL
                            $sveaBirthDateDivPP .     //  NL, DE
                            $sveaVatNoDivPP .         //  NL, DE
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

        require('includes/modules/payment/svea/svea.php');

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
        $client_order_number = ($new_order_field['orders_id'] + 1) . '-' . time();

        // localization parameters
        $user_country = $order->billing['country']['iso_code_2'];
        
        $user_language = $db->Execute("select code from " . TABLE_LANGUAGES . " where directory = '" . $language . "'");
        $user_language = $user_language->fields['code'];
    
         // switch to default currency if the customers currency is not supported
        $currency = $order->info['currency'];
        if (!in_array($currency, $this->allowed_currencies)) {
            $currency = $this->default_currency;
        }
        
        // Include Svea php integration package files    
        require(DIR_FS_CATALOG . 'includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 

        // Create and initialize order object, using either test or production configuration
        $swp_order = WebPay::createOrder() // TODO uses default testmode config for now
            ->setCountryCode( $user_country )
            ->setCurrency($currency)                       //Required for card & direct payment and PayPage payment.
            ->setClientOrderNumber($client_order_number)   //Required for card & direct payment, PaymentMethod payment and PayPage payments
            ->setOrderDate(date('c'))                      //Required for synchronous payments -- TODO check format "2012-12-12"
        ;

        //
        // for each item in cart, create WebPayItem::orderRow objects and add to order
        foreach ($order->products as $productId => $product) {

            $amount_ex_vat = $this->convert_to_currency(round($product['final_price'], 2), $currency);

            $swp_order->addOrderRow(
                    WebPayItem::orderRow()
                            ->setQuantity($product['qty'])          //Required
                            ->setAmountExVat($amount_ex_vat)          //Optional, see info above
                            //->setAmountIncVat(125.00)               //Optional, see info above
                            ->setVatPercent(intval($product['tax']))  //Optional, see info above
                            //->setArticleNumber()                    //Optional
                            ->setDescription($product['name'])        //Optional
                            //->setName($product['model'])             //Optional
                            //->setUnit("st")                           //Optional  //TODO hardcoded?
                            //->setDiscountPercent(0)                   //Optional  //TODO hardcoded
            );
        }
       
        //        
        // handle order total modules 
        // i.e shipping fee, handling fee items
        foreach ($order_totals as $ot_id => $order_total) {
          
            switch ($order_total['code']) {
                case in_array(  $order_total['code'], 
                                $this->ignore_list):
                case 'ot_subtotal':
                case 'ot_total':
                case 'ot_tax':
                    // do nothing
                    break;

                //
                // if shipping fee, create WebPayItem::shippingFee object and add to order
                case 'ot_shipping':
                    
                    //makes use of zencart $order-info[] shipping information to populate object
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
                    $swp_order->addFee(
                            WebPayItem::shippingFee()
                                    ->setDescription($order->info['shipping_method'])
                                    ->setAmountExVat( $amountExVat )
                                    ->setAmountIncVat( $amountIncVat )
                    );
                break;
                
                //
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
                            $hf_price = $this->convert_to_currency(floatval($this->handling_fee), $currency);
                        }
                        $hf_taxrate =   zen_get_tax_rate(MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS, 
                                        $order->delivery['country']['id'], $order->delivery['zone_id']);

                        // add WebPayItem::invoiceFee to swp_order object 
                        $swp_order->addFee(
                                WebPayItem::invoiceFee()
                                        ->setDescription()
                                        ->setAmountExVat($hf_price)
                                        ->setVatPercent($hf_taxrate)
                        );
                    }
                    break;

                case 'ot_coupon':
                    // as the ot_coupon module doesn't seem to honor "show prices with/without tax" setting in zencart, we assume that
                    // coupons of a fixed amount are meant to be made out in an amount _including_ tax iff the shop displays prices incl. tax
                    if (DISPLAY_PRICE_WITH_TAX == 'false') {
                        $amountExVat = $order_total['value'];
                        //calculate price incl. tax
                        $amountIncVat = $amountExVat * ( (100 + $order->products[0]['tax']) / 100);     //Shao's magic way to get shop tax  
                    }
                    else {
                        $amountIncVat = $order_total['value'];                   
                    }
                    
                    // add WebPayItem::fixedDiscount to swp_order object 
                    $swp_order->addDiscount(
                            WebPayItem::fixedDiscount()
//                                        ->setAmountIncVat(100.00)               //Required
//                                        ->setDiscountId("1")                    //Optional
//                                        ->setUnit("st")                         //Optional
//                                        ->setDescription("FixedDiscount")       //Optional
//                                        ->setName("Fixed")                      //Optional
                                    ->setAmountIncVat( $amountIncVat )
                                    ->setDescription( $order_total['title'] )
                    );                
                break;

                // TODO
                // default case handles 'unknown' items from other plugins. Might cause problems.
                default:
                    $order_total_obj = $GLOBALS[$order_total['code']];
                    $tax_rate = zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                    // if displayed WITH tax, REDUCE the value since it includes tax
                    if (DISPLAY_PRICE_WITH_TAX == 'true')
                        $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));

                    $clientInvoiceRows[] = Array(
                        "Description" => strip_tags($order_total['title']),
                        "PricePerUnit" => $this->convert_to_currency(strip_tags($order_total['value']), $currency),
                        "NumberOfUnits" => 1,
                        "Unit" => "",
                        "VatPercent" => $tax_rate,
                        "DiscountPercent" => 0
                    );

                    break;
            }
        }
        
        // customer is private individual with partpay

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
            $swp_customer->setInitials($post_sveaInitials);  //TODO calculate from string
        }

        //Split street address and house no
        $pattern ="/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/"; 
        $myStreetAddress = Array();
        preg_match( $pattern, $order->billing['street_address'], $myStreetAddress  );
        if( !array_key_exists( 2, $myStreetAddress ) ) { $myStreetAddress[2] = ""; }  // TODO handle case Street w/o number in package?!

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

        // Include Svea php integration package files
        require('includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 

        // retrieve order object set in process_button()
        $swp_order = unserialize($_SESSION["swp_order"]);
        //print_r("swp_order:" . serialize($swp_order) );
        //debug tip: use serialized object to test in less complex (no shop) test environment

        // throws an exception if the payment request can't be done with current order content
        try {
            // set the chosen payment plan
            $swp_order->usePaymentPlanPayment($_SESSION['sveaPaymentOptionsPP'])->prepareRequest();  // TODO debug, remove in production
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        //
        // send payment request to svea, receive response
        $swp_response = $swp_order->usePaymentPlanPayment($_SESSION['sveaPaymentOptionsPP'])->doRequest();
        
        //
        // payment request failed; handle this by redirecting w/result code as error message
        if ($swp_response->accepted === false) {
//            $_SESSION['SWP_ERROR'] = $this->responseCodes($swp_response->CreateOrderEuResult->ResultCode);

            // TODO no errno for certain errors gives strange error message
            $payment_error_return = 'payment_error=sveawebpay_invoice&payment_errno=' .
                                    $swp_response->resultcode;

            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return)); // error handled in selection() above
        }

        //
        // payment request succeded, store response in session
        if ($swp_response->accepted == true) {
            
            //
            // set zencart billing address to invoice address from payment request response

            // is private individual?
            if( $swp_response->customerIdentity->customerType == "Individual") {
                $order->billing['firstname'] = $swp_response->customerIdentity->fullName; // workaround for zen_address_format not showing 'name' in order information view/
                $order->billing['lastname'] = "";
                $order->billing['company'] = "";
            }

            // TODO check default zencart CHARSET define (should equal used database collation, i.e. utf-8). 
            // if not utf-8, must handle that when parsing swp_response (in utf-8) -- use utf8_decode(response-> ?)
            // also, check that php 5.3 and 5.4+ behaves the same in zen_output_string ( htmlspecialchars() defaults to utf-8 from 5.4)
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

        //
        // retrieve response object from before_process()
        require('includes/modules/payment/svea_v4/Includes.php');
        $swp_response = unserialize($_SESSION["swp_response"]);

        // set zencart order info using data from response object
        $order->info['SveaOrderId'] = $swp_response->sveaOrderId;
        $order->info['type'] = $swp_response->customerIdentity->customerType;

        // set zencart order securityNumber -- if request to webservice, use sveaOrderId, if hosted use transactionId
        $order->info['securityNumber'] = isset( $swp_response->sveaOrderId ) ? $swp_response->sveaOrderId : $swp_response->transactionId; 
           
        // insert zencart order into database
        $sql_data_array = array('orders_id' => $insert_id,
            'orders_status_id' => $order->info['order_status'],
            'date_added' => 'now()',
            'customer_notified' => 0,
            // TODO take comments below to language files?
            'comments' => 'Accepted by SveaWebPay ' . date("Y-m-d G:i:s") . ' Security Number #: ' . $order->info['securityNumber']);
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        //
        // clean up our session variables set during checkout   //$SESSION[swp_*
        unset($_SESSION['swp_order']);
        unset($_SESSION['swp_response']);
        
        // TODO: why false?
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
            
            // TODO can these be removed?
            /*
            case "CustomerCreditRejected" :
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
            */
            
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
                return ERROR_CODE_DEFAULT;
                break;
        }
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
