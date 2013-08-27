<?php
/*
HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
-----------------------------------------------
Version 4.0 - Zen Cart

 Kristian Grossman-Madsen, Shaho Ghobadi
*/

class sveawebpay_invoice {

  function sveawebpay_invoice() {
    global $order;

    $this->code = 'sveawebpay_invoice';
    $this->version = 2;                         // TODO version of what?

    $_SESSION['SWP_CODE'] = $this->code;

    $this->title = MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_SWPINVOICE_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_SWPINVOICE_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_SWPINVOICE_SORT_ORDER;
    $this->sveawebpay_url = MODULE_PAYMENT_SWPINVOICE_URL;
    $this->handling_fee = MODULE_PAYMENT_SWPINVOICE_HANDLING_FEE;
    $this->default_currency = MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES);
    $this->display_images = ((MODULE_PAYMENT_SWPINVOICE_IMAGES == 'True') ? true : false);
    $this->ignore_list = explode(',', MODULE_PAYMENT_SWPINVOICE_IGNORE);
    if ((int)MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID > 0)
      $this->order_status = MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID;
    if (is_object($order)) $this->update_status();
  }

  function update_status() {
    global $db, $order, $currencies, $messageStack;

    // update internal currency
    $this->default_currency = MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES);

    // do not use this module if any of the allowed currencies are not set in osCommerce
    foreach($this->allowed_currencies as $currency) {
      if(!is_array($currencies->currencies[strtoupper($currency)])) {
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
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_SWPINVOICE_ZONE > 0) ) {
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

  // sets information displayed when choosing between payment options
  function selection() {
    global $order, $currencies;

    $fields = array();
    
    // image
    if ($this->display_images)
    
    $fields[] = array('title' => '<img src=images/SveaWebPay-Faktura-100px.png />', 'field' => '');
    
    //Return error
    if (isset($_REQUEST['payment_error']) && $_REQUEST['payment_error'] == 'sveawebpay_invoice'){
        $fields[] = array('title' => '', 'field' => '<span style="color:red">'.$this->responseCodes($_REQUEST['payment_error']).'</span>');
    }

    //Fields to insert/show when SWP is chosen

    $sveaJs =  '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
                <script type="text/javascript" src="'.$this->web_root . 'includes/modules/payment/svea.js"></script>';
    $fields[] = array('title' => '', 'field' => $sveaJs);
    
    $sveaIsCompany    = FORM_TEXT_COMPANY_OR_PRIVATE.' <br /><select name="sveaIsCompany" id="sveaIsCompany">
                        <option value="" selected="selected">'.FORM_TEXT_PRIVATE.'</option>
                        <option value="true">'.FORM_TEXT_COMPANY.'</option>
                        </select><br />'; 
    $sveaPnr          = '<br />'.FORM_TEXT_SS_NO.'<br /><input type="text" name="sveaPnr" id="sveaPnr" maxlength="11" /><br />';

    //For finland and Europe there is no getAdress
    if ($order->info['currency'] == 'EUR'){
        $sveaGetAdressBtn = '';
        $sveaAdressDD     = '';
    }else{
        $sveaGetAdressBtn = '<button type="button" id="getSveaAdressInvoice" onclick="getAdress()">'.FORM_TEXT_GET_ADDRESS.'</button><br />'; 
        $sveaAdressDD     = FORM_TEXT_INVOICE_ADDRESS.'<br /><select name="adressSelector_fakt" id="adressSelector_fakt" style="display:none"></select><br />';
    }
           
    
    
    $sveaField        = '<div id="sveaFaktField" style="display:none">'.$sveaPnr.$sveaIsCompany.$sveaAdressDD.$sveaGetAdressBtn.'</div>';
             
    $fields[] = array('title' => '', 'field' => $sveaField.'<br /><span id="pers_nr_error_fakt" style="color:red"></span>');

    // handling fee
    if (isset($this->handling_fee) && $this->handling_fee > 0) {
      $paymentfee_cost = $this->handling_fee;
      if (substr($paymentfee_cost, -1) == '%')
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES, $paymentfee_cost), 'field' => '');
      else
      {
        $tax_class = MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS;
        if (DISPLAY_PRICE_WITH_TAX == "true" && $tax_class > 0)
          $paymentfee_tax = $paymentfee_cost * zen_get_tax_rate($tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) / 100;
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPINVOICE_HANDLING_APPLIES, $currencies->format($paymentfee_cost+$paymentfee_tax)), 'field' => '');
      }
    }
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

  /** process_button() is called from tpl_checkout_confirmation.php in
   *  includes/templates/template_default/templates.
   *  Here we prepare to populate the order object by creating the 
   *  Item::orderRow objects that make up the order.
   */
  
  function process_button() {
        
    global $db, $order, $order_totals, $language;
       
    // Include Svea php integration package files    
    require('includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 

    // Create order object using either test or production configuration
    $swp_order = swp_\WebPay::createOrder(); // TODO uses default testmode config for now
    
    // calculate the order number
    $new_order_rs = $db->Execute("select orders_id from ".TABLE_ORDERS." order by orders_id desc limit 1");
    $new_order_field = $new_order_rs->fields;
    $client_order_number = ($new_order_field['orders_id'] + 1).'-'.time();

    // localization parameters
    $user_country = $order->billing['country']['iso_code_2'];
    $user_language = $db->Execute("select code from " . TABLE_LANGUAGES . " where directory = '" . $language . "'");
    $user_language = $user_language->fields['code'];
        
    // switch to default currency if the customers currency is not supported
    $currency = $order->info['currency'];
    if(!in_array($currency, $this->allowed_currencies)) { $currency = $this->default_currency; }

    // set other values
    $swp_order
        ->setCountryCode($order->customer['country']['iso_code_2'])                      //Required   TODO kolla = user_country??
        ->setCurrency($currency)                     //Required for card & direct payment and PayPage payment.
        ->setClientOrderNumber($client_order_number) //Required for card & direct payment, PaymentMethod payment and PayPage payments.
        ->setOrderDate(date('c'))                      //Required for synchronous payments -- TODO check format "2012-12-12"
        //->setCustomerReference("33")                 //Optional
    ;
    
    /*
    // we'll store the generated orderid in a session variable so we can check
    // it when returning from payment gateway for security reasons:
    // Set up SSN and company
    $_SESSION['swp_orderid'] = $hosted_params['OrderId'];       // TODO -- is this used? where?
    */  
    
    // create Item::orderRow object and ensure we can read it in before_process()

    // Order rows for Nordic -- TODO should be eu/nordic/generic?
    foreach($order->products as $productId => $product) {

        $amount_ex_vat = $this->convert_to_currency(round($product['final_price'],2),$currency);
  
        //$swp_items[] =
        $swp_order->addOrderRow( 
          swp_\Item::orderRow()
              ->setQuantity($product['qty'])                        //Required
              ->setAmountExVat($amount_ex_vat)    //Optional, see info above
              //->setAmountIncVat(125.00)               //Optional, see info above
              ->setVatPercent(intval($product['tax']))                     //Optional, see info above
              //->setArticleNumber()                   //Optional
              ->setDescription($product['name'])       //Optional
              //->setName('Prod')                       //Optional
              ->setUnit("st")                         //Optional  //TODO hardcoded?
              ->setDiscountPercent(0)                 //Optional  //TODO hardcoded
        );
    }
    //$swp_order->addOrderRow( $swp_items );    TODO funkar inte?!
    
    //Handling fee
    if (isset($this->handling_fee) && $this->handling_fee > 0) {
    
        $hf_price = $this->convert_to_currency( ($this->handling_fee * 0.8),$currency );    // TODO magic number 0.8? Always one unit?!

        $swp_order->addFee( 
            swp_\Item::shippingFee()
            //->setShippingId('33')                     //Optional
            //->setName('shipping')                     //Optional
            ->setDescription("Faktureringsavgift")      //Optional     TODO hardcoded?
            ->setAmountExVat($hf_price)                 //Optional, see info above
            //->setAmountIncVat(62.50)                  //Optional, see info above
            ->setVatPercent(25)                         //Optional, see info above  TODO hardcoded
            //->setUnit("st")                           //Optional
            ->setDiscountPercent(0)                     //Optional      TODO hardcoded
        );
    }
      
    //Split street address and house no
    $streetAddress = preg_split('/ /', $order->customer['street_address'],-1,PREG_SPLIT_NO_EMPTY);
    
    //Get initials
    $initials = substr($order->customer['firstname'],0,1).substr($order->customer['lastname'],0,1);     // TODO replace this
    
    // create individual customer object
    $swp_customer = swp_\Item::individualCustomer()
         ->setNationalIdNumber($_POST['sveaPnr']) //Required for individual customers in SE, NO, DK, FI -- TODO get pnr from customer
         ->setInitials($initials)   //Required for individual customers in NL    -- TODO get w/pnr from customer
        //->setBirthDate(1923, 12, 20)   //Required for individual customers in NL and DE -- TODO calculate from pnr/get from customer
         ->setName($order->customer['firstname'], $order->customer['lastname'])        //Required for individual customers in NL and DE
         ->setStreetAddress($streetAddress[0], $streetAddress[1])     //Required in NL and DE
         ->setZipCode($order->customer['postcode'])                  //Required in NL and DE
         ->setLocality($order->customer['city'])               //Required in NL and DE
         ->setEmail($order->customer['email_address'])         //Optional but desirable
         ->setIpAddress($_SERVER['REMOTE_ADDR'])       //Optional but desirable
        //->setCoAddress("c/o Eriksson")      //Optional
         ->setPhoneNumber($order->customer['telephone'])            //Optional
    ;
    $swp_order->addCustomerDetails($swp_customer);    
       
    // next: store orderRow objects in session, are retrieved by before_process()
    $_SESSION["swp_customer"] = serialize($swp_customer);
    $_SESSION["swp_order"] = serialize($swp_order);
    
 
/// ------------------------------------------------------------- old code below        
    require('includes/modules/payment/svea/svea.php');

    //Get the order
    $new_order_rs = $db->Execute("select orders_id from ".TABLE_ORDERS." order by orders_id desc limit 1");
    $new_order_field = $new_order_rs->fields;

    // localization parameters
    $user_country = $order->billing['country']['iso_code_2'];
    $user_language = $db->Execute("select code from " . TABLE_LANGUAGES . " where directory = '" . $language . "'");
    $user_language = $user_language->fields['code'];

        
    // switch to default currency if the customers currency is not supported
    $currency = $order->info['currency'];
    if(!in_array($currency, $this->allowed_currencies))
      $currency = $this->default_currency;
  
    
    // we'll store the generated orderid in a session variable so we can check
    // it when returning from payment gateway for security reasons:
    // Set up SSN and company
    $_SESSION['swp_orderid'] = $hosted_params['OrderId'];
    
    
    /*** Set up The request Array ***/
   
    //Setting the NumberOfUnits field between Euro and nordic
    $nrOfUnits = (($order->customer['country']['iso_code_2'] == 'NL' || $order->customer['country']['iso_code_2'] == 'DE') && $order->info['currency'] == 'EUR') ? 'NumberOfUnits' : 'NrOfUnits';
  
    $i = 0;
    // Order rows for Nordic
    foreach($order->products as $productId => $product) {
        
        $orderRows = Array(
              "Description" => $product['name'],
              "PricePerUnit" => $this->convert_to_currency(round($product['final_price'],2),$currency),
              $nrOfUnits => $product['qty'],
              "Unit" => "st",
              "VatPercent" => $product['tax'],
              "DiscountPercent" => 0
            );
            
        if (isset($clientInvoiceRows)){
    
            $clientInvoiceRows[$productId] = $orderRows;
        }else{
            $clientInvoiceRows[] = $orderRows;
        }
    }



    // handle order totals
      foreach($order_totals as $ot_id => $order_total) {
        $current_row++;
        switch($order_total['code']) {
          case 'ot_subtotal':
          case 'ot_total':
          case 'ot_tax':
          case in_array($order_total['code'],$this->ignore_list):
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
              "Description" => $shipping_description,
              "PricePerUnit" => $this->convert_to_currency($_SESSION['shipping']['cost'],$currency),
              $nrOfUnits => 1,
              "VatPercent" => (string) zen_get_tax_rate($shipping->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']),
              "DiscountPercent" => 0
              );
            break;
          case 'ot_coupon':
          
            $clientInvoiceRows[] = Array(
              "Description" => strip_tags($order_total['title']),
              "PricePerUnit" => -$this->convert_to_currency(strip_tags($order_total['value']),$currency),
              $nrOfUnits => 1,
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
              "Description" => strip_tags($order_total['title']),
              "PricePerUnit" => $this->convert_to_currency(strip_tags($order_total['value']),$currency),
              $nrOfUnits => 1,
              "VatPercent" => $tax_rate,
              "DiscountPercent" => 0
              );

            break;
        }
      }
    
    //Handling fee
    if (isset($this->handling_fee) && $this->handling_fee > 0) {
      $paymentfee_cost = $this->handling_fee;
      $invoiceCost     = $this->handling_fee * 0.8;
      
      $clientInvoiceRows[] = Array(
              "Description" => 'Faktureringsavgift',
              "PricePerUnit" => $this->convert_to_currency($invoiceCost,$currency),
              $nrOfUnits => 1,
              "VatPercent" => 25,
              "DiscountPercent" => 0
            );   
    }
    
    
    //Get svea configuration for each country based on currency
    $sveaConf = getCountryConfigInvoice($order->info['currency'],$order->customer['country']['iso_code_2']);
  
    //Split street address and house no
    $streetAddress = preg_split('/ /', $order->customer['street_address'],-1,PREG_SPLIT_NO_EMPTY);
    
    //Get initials
    $initials = substr($order->customer['firstname'],0,1).substr($order->customer['lastname'],0,1);

    //IsCompany
    $company = ($_POST['sveaIsCompany'] == 'True') ? 'Company' : 'Individual';
    if ($company == 'Individual'){
        $identity    = 'IndividualIdentity';
        $identityArr = array(
                        "FirstName" => $order->customer['firstname'],
                        "LastName" => $order->customer['lastname'],
                        "Initials" => $initials,
                        "BirthDate" => $_POST['sveaPnr']
                        );
    }else{
        $identity    = 'CompanyIdentity';
        $identityArr = array(
                        "CompanyIdentification" => '',
                        "CompanyVatNumber" => $_POST['sveaPnr']
                        );
    }

    /************ CREATE ORDER FOR EU *******************/
    
    

    //The createOrder Data for Euro
    $request = Array(
          "Auth" => Array(
            "Username" => $sveaConf['username'],
            "Password" => $sveaConf['password'],
            "ClientNumber" => $sveaConf['clientno']
           ),
          "CreateOrderInformation" => Array(
            "ClientOrderNumber" => ($new_order_field['orders_id'] + 1).'-'.time(),
            "OrderRows" => array('OrderRow' => $clientInvoiceRows),
            "CustomerIdentity" => array (
                //"NationalIdNumber" => '',//$_POST['sveaPnr'],
                "Email" => $order->customer['email_address'],
                "PhoneNumber" => $order->customer['telephone'],
                "IpAddress" => $_SERVER['REMOTE_ADDR'],
                "FullName" => $order->customer['firstname']. ' ' .$order->customer['lastname'],
                "Street" => $streetAddress[0],
                "ZipCode" => $order->customer['postcode'],
                "HouseNumber" => $streetAddress[1],
                "Locality" => $order->customer['city'],
                "CountryCode" => $order->customer['country']['iso_code_2'],
                "CustomerType" => $company,
                $identity => $identityArr

            ),
            "OrderDate" => date(c),
            "AddressSelector" => $_POST['adressSelector_fakt'],
            "CustomerReference" => "",
            "OrderType" => "Invoice"
          )
          
          
        );
    
    
    // TODO flow for customers outside of NL, DE
    if( false) {    // TODO fix
    
    //Get svea configuration for each country based on currency
    $sveaConf = getCountryConfigInvoice($order->info['currency']);
    
    //IsCompany
    $company = ($_POST['sveaIsCompany'] == 'True') ? True: false;
    
    /************ CREATE ORDER FOR NORDIC COUNTRIES *******************/
    $request = Array(
          "Auth" => Array(
            "Username" => $sveaConf['username'],
            "Password" => $sveaConf['password'],
            "ClientNumber" => $sveaConf['clientno']
           ),
          "Order" => Array(
    		"ClientOrderNr" => ($new_order_field['orders_id'] + 1).'-'.time(),
            "CountryCode" => $sveaConf['countryCode'],
            "SecurityNumber" => $_POST['sveaPnr'],
            "IsCompany" => $company,
            "OrderDate" => date(c),
    		"AddressSelector" => $_POST['adressSelector_fakt'],
            "PreApprovedCustomerId" => 0
          ),
          "InvoiceRows" => array('ClientInvoiceRowInfo' => $clientInvoiceRows)
        );
    
    }
    
 
     $_SESSION['swp_fakt_request'] = $request;
    return false;   
  }


  /**
   * before_process is called from modules/checkout_process
   * instantiates and populates a WebPay::createOrder object
   * as well as sends the actual payment request
   */
  
  function before_process() {
    global $order, $order_totals, $language, $billto, $sendto, $db;
     
    // Include Svea php integration package files
    require('includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 
    
    // retrieve order object set in process_button()
    $swp_order = unserialize($_SESSION["swp_order"]);
    print_r("swp_order:" . serialize($swp_order) );
     
    // retrieve orderRow objects previously set in process_button()
    //print_r(unserialize($_SESSION["testitem"])); die();
    //$testitem = unserialize($_SESSION["testitem"]);
    $swp_customer = unserialize($_SESSION["swp_customer"]);
    //print_r("swp_customer:" . serialize($swp_customer) );
 
    //debugging tip: use serialization string to test in less complex (i.e. outside shop) test environment
    
    try {
        $myobject = $swp_order->useInvoicePayment()->prepareRequest();
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    
    print_r("past prepare");
    print_r(serialize($myobject));

    $response = $swp_order->useInvoicePayment()->doRequest();
    print_r(serialize($response));
    die();
        
    
    /** old request below ================================================================================== */
    
    //Put all the data in request tag
    $data['request'] = $_SESSION['swp_fakt_request'];
   
   	$svea_server = (MODULE_PAYMENT_SWPINVOICE_MODE == 'Test') ? 'https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL' : 'https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL';
    //Call Soap
    $client = new SoapClient( $svea_server );


    /************ RESPONSE HANDLING EUROPE ****************/ 
    if (($order->customer['country']['iso_code_2'] == 'NL' || $order->customer['country']['iso_code_2'] == 'DE') && $order->info['currency'] == 'EUR'){

    //Make soap call to below method using above data
    $svea_req = $client->CreateOrderEU( $data );
     
    $response = $svea_req->CreateOrderEuResult->Accepted;
    
     
    // handle failed payments
    if ($response != '1') {
      $_SESSION['SWP_ERROR'] = $this->responseCodes($svea_req->CreateOrderEuResult->CreateOrderResult->ResultCode);
      
      $payment_error_return = 'payment_error=' .$svea_req->CreateOrderEuResult->CreateOrderResult->ResultCode;
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
    }
    
    
    // handle successful payments
    if($response == '1'){
        unset($_SESSION['swp_fakt_request']);
        if (isset($svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity))
            $order->info['securityNumber']     = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->BirthDate;
        else
            $order->info['securityNumber']     = $svea_req->CreateOrderEuResult->CustomerIdentity->CompanyIdentity->CompanyVatNumber;
 
    }
      
    if (isset($svea_req->CreateOrderEuResult->FullName)) {

      $order->billing['firstname']       = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->FirstName;
      $order->billing['lastname']        = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->LastName;
      $order->billing['street_address']  = $svea_req->CreateOrderEuResult->CustomerIdentity->Street. ' ' . $svea_req->CreateOrderEuResult->HouseNumber;
      //$order->billing['suburb']          = $svea_req->CreateOrderEuResult->AddressLine2;
      $order->billing['city']            = $svea_req->CreateOrderEuResult->CustomerIdentity->Locality;
      $order->billing['state']           = '';                    // "state" is not applicable in SWP countries
      $order->billing['postcode']        = $svea_req->CreateOrderEuResult->CustomerIdentity->ZipCode;
    
      $order->delivery['firstname']      = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->FirstName;
      $order->delivery['lastname']       = $svea_req->CreateOrderEuResult->CustomerIdentity->IndividualIdentity->LastName;
      $order->delivery['street_address'] = $svea_req->CreateOrderEuResult->CustomerIdentity->Street. ' ' . $svea_req->CreateOrderEuResult->HouseNumber;
      //$order->delivery['suburb']         = $svea_req->CreateOrderEuResult->AddressLine2;
      $order->delivery['city']           = $svea_req->CreateOrderEuResult->CustomerIdentity->Locality;
      $order->delivery['state']          = '';                    // "state" is not applicable in SWP countries
      $order->delivery['postcode']       = $svea_req->CreateOrderEuResult->CustomerIdentity->ZipCode;
    }
    
    
    /************ RESPONSE HANDLING NORDIC ****************/     
    }else{
         
    //Make soap call to below method using above data
    $svea_req = $client->CreateOrder( $data );
    
     
    $response = $svea_req->CreateOrderResult->RejectionCode;

    // handle failed payments
    if ($response != 'Accepted') {
      $_SESSION['SWP_ERROR'] = $this->responseCodes($response);
      
      $payment_error_return = 'payment_error=' . $this->code;
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
    }
    
    
    // handle successful payments
    if($response == 'Accepted'){
        unset($_SESSION['swp_fakt_request']);
        $order->info['securityNumber']     = $svea_req->CreateOrderResult->SecurityNumber;
 
    }
      
    if (isset($svea_req->CreateOrderResult->LegalName)) {
      $name = explode(',',$svea_req->CreateOrderResult->LegalName); 
        
      $order->billing['firstname']       = $name[1];
      $order->billing['lastname']        = $name[0];
      $order->billing['street_address']  = $svea_req->CreateOrderResult->AddressLine1;
      $order->billing['suburb']          = $svea_req->CreateOrderResult->AddressLine2;
      $order->billing['city']            = $svea_req->CreateOrderResult->Postarea;
      $order->billing['state']           = '';                    // "state" is not applicable in SWP countries
      $order->billing['postcode']        = $svea_req->CreateOrderResult->Postcode;
    
      $order->delivery['firstname']      = $name[1];
      $order->delivery['lastname']       = $name[0];
      $order->delivery['street_address'] = $svea_req->CreateOrderResult->AddressLine1;
      $order->delivery['suburb']         = $svea_req->CreateOrderResult->AddressLine2;
      $order->delivery['city']           = $svea_req->CreateOrderResult->Postarea;
      $order->delivery['state']          = '';                    // "state" is not applicable in SWP countries
      $order->delivery['postcode']       = $svea_req->CreateOrderResult->Postcode;
    }
    
    }
    $table = array (
                'INVOICE'       => MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE,
                'INVOICESE'     => MODULE_PAYMENT_SWPINVOICE_TEXT_TITLE);

  }

  // if payment accepted, insert order into database
  function after_process() {
    global $insert_id, $order, $db;

    $sql_data_array = array(  'orders_id'         => $insert_id,
                              'orders_status_id'  => $order->info['order_status'],
                              'date_added'        => 'now()',
                              'customer_notified' => 0,
                              'comments'          => 'Accepted by SveaWebPay '.date("Y-m-d G:i:s") .' Security Number #: '.$order->info['securityNumber']);
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    
    if ($this->handling_fee > 0){
    
    switch (MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY){
            case 'SEK':
                $currency = 'kr';
            break;
            case 'EUR':
                $currency = '€';
            break;

    }

    $invoiceFeeVAT = $this->handling_fee * 0.2;
    $invoicePrice  = $this->handling_fee * 0.8;

    $db->Execute("UPDATE ".TABLE_ORDERS_TOTAL." set value = value+".$this->handling_fee.", text = CONCAT(FORMAT(value,0), '".$currency."') WHERE orders_id = ".$insert_id." AND class = 'ot_total'")or die('första');
    $db->Execute("UPDATE ".TABLE_ORDERS_TOTAL." set value = value+".$invoiceFeeVAT.", text = CONCAT(FORMAT(value,0), '".$currency."') WHERE orders_id = ".$insert_id." AND class = 'ot_tax'")or die('Andra');
    $db->Execute("UPDATE ".TABLE_ORDERS_TOTAL." set value = value+".$this->handling_fee.", text = CONCAT(FORMAT(value,0), '".$currency."') WHERE orders_id = ".$insert_id." AND class = 'ot_subtotal'")or die('tredje');
    $db->Execute("INSERT INTO ".TABLE_ORDERS_PRODUCTS." (orders_products_id, orders_id, products_id, products_model, products_name, products_price, final_price, products_tax, products_quantity)
          VALUES ('','".$insert_id."','','','Faktureringsavgift','".$invoicePrice."','".$invoicePrice."','25.00','1')")or die('fjärde');
    }

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
      $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SWPINVOICE_STATUS'");
      $this->_check = !$check_rs->EOF;
    }
    return $this->_check;
  }

  // insert configuration keys here
  function install() {
    global $db;
    $common = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
    $db->Execute($common . ", set_function) values ('Enable SveaWebPay Invoice Module', 'MODULE_PAYMENT_SWPINVOICE_STATUS', 'True', 'Do you want to accept SveaWebPay payments?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('SveaWebPay Username SV', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_SV', 'Testinstallation', 'Username for SveaWebPay Invoice Sweden', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Password SV', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_SV', 'Testinstallation', 'Password for SveaWebPay Invoice Sweden', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Username NO', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_NO', 'webpay_test_no', 'Username for SveaWebPay Invoice Norway', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Password NO', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NO', 'dvn349hvs9+29hvs', 'Password for SveaWebPay Invoice Norway', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Username FI', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_FI', 'finlandtest', 'Username for SveaWebPay Invoice Finland', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Password FI', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_FI', 'finlandtest', 'Password for SveaWebPay Invoice Finland', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Username DK', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_DK', 'danmarktest', 'Username for SveaWebPay Invoice Denmark', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Password DK', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_DK', 'danmarktest', 'Password for SveaWebPay Invoice Denmark', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Username NL', 'MODULE_PAYMENT_SWPINVOICE_USERNAME_NL', 'hollandtest', 'Username for SveaWebPay Invoice Netherlands', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Password NL', 'MODULE_PAYMENT_SWPINVOICE_PASSWORD_NL', 'hollandtest', 'Password for SveaWebPay Invoice Netherlands', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Client no SV', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_SV', '75021', '', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Client no NO', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NO', '32666', '', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Client no FI', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_FI', '29995', '', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Client no DK', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_DK', '60006', '', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Client no NL', 'MODULE_PAYMENT_SWPINVOICE_CLIENTNO_NL', '85997', '', '6', '0', now())");
    $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPINVOICE_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
    $db->Execute($common . ") values ('Handling Fee', 'MODULE_PAYMENT_SWPINVOICE_HANDLING_FEE', '', 'This handling fee will be applied to all orders using this payment method.  The figure can either be set to a specific amount eg <b>5.00</b>, or set to a percentage of the order total, by ensuring the last character is a \'%\' eg <b>5.00%</b>.', '6', '0', now())");
    $db->Execute($common . ") values ('Accepted Currencies', 'MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES','SEK,NOK,DKK,EUR', 'The accepted currencies, separated by commas.  These <b>MUST</b> exist within your currencies table, along with the correct exchange rates.','6','0',now())");
    $db->Execute($common . ", set_function) values ('Default Currency', 'MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY', 'SEK', 'Default currency used, if the customer uses an unsupported currency it will be converted to this. This should also be in the supported currencies list.', '6', '0', now(), 'zen_cfg_select_option(array(\'SEK\',\'NOK\',\'DKK\',\'EUR\'), ')");
    $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
    $db->Execute($common . ", set_function) values ('Display SveaWebPay Images', 'MODULE_PAYMENT_SWPINVOICE_IMAGES', 'True', 'Do you want to display SveaWebPay images when choosing between payment options?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPINVOICE_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
    $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPINVOICE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
    $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPINVOICE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
  }

  // standard uninstall function
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  // must perfectly match keys inserted in install function
  function keys() {
    return array( 'MODULE_PAYMENT_SWPINVOICE_STATUS',
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
                  'MODULE_PAYMENT_SWPINVOICE_MODE',
                  'MODULE_PAYMENT_SWPINVOICE_HANDLING_FEE',
                  'MODULE_PAYMENT_SWPINVOICE_ALLOWED_CURRENCIES',
                  'MODULE_PAYMENT_SWPINVOICE_DEFAULT_CURRENCY',
                  'MODULE_PAYMENT_SWPINVOICE_ORDER_STATUS_ID',
                  'MODULE_PAYMENT_SWPINVOICE_IMAGES',
                  'MODULE_PAYMENT_SWPINVOICE_IGNORE',
                  'MODULE_PAYMENT_SWPINVOICE_ZONE',
                  'MODULE_PAYMENT_SWPINVOICE_SORT_ORDER');
  }

  function convert_to_currency($value, $currency) {
    global $currencies;
    // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
    return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $decimal_places), 2, $decimal_symbol, '');
  }
  

    
  //Error Responses
  function responseCodes($err){      
        switch ($err){
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
