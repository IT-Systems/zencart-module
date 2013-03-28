<?php
/*
HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
-----------------------------------------------
Version 3.0 - Zen Cart
Shaho Ghobadi
*/

class sveawebpay_internetbank {

  function sveawebpay_internetbank() {
    global $order;

    $this->code = 'sveawebpay_internetbank';
    $this->version = 2;

    $_SESSION['SWP_CODE'] = $this->code;

    $this->form_action_url = (MODULE_PAYMENT_SWPINTERNETBANK_STATUS == 'True') ? 'https://test.sveaekonomi.se/webpay/payment' : 'https://webpay.sveaekonomi.se/webpay/payment';
    $this->title = MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_SWPINTERNETBANK_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_SWPINTERNETBANK_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_SWPINTERNETBANK_SORT_ORDER;
    /*
    $this->sveawebpay_url = MODULE_PAYMENT_SWPCREDITCARD_URL;
    $this->handling_fee = MODULE_PAYMENT_SWPCREDITCARD_HANDLING_FEE;
    */
    $this->default_currency = MODULE_PAYMENT_SWPINTERNETBANK_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINTERNETBANK_ALLOWED_CURRENCIES);
    $this->display_images = ((MODULE_PAYMENT_SWPINTERNETBANK_IMAGES == 'True') ? true : false);
    $this->ignore_list = explode(',', MODULE_PAYMENT_SWPINTERNETBANK_IGNORE);
    if ((int)MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID > 0)
      $this->order_status = MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID;
    if (is_object($order)) $this->update_status();
  }

  function update_status() {
    global $db, $order, $currencies, $messageStack;

    // update internal currency
    $this->default_currency = MODULE_PAYMENT_SWPINTERNETBANK_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPINTERNETBANK_ALLOWED_CURRENCIES);

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

    $fields = array();

    // image
    if ($this->display_images)
      $fields[] = array('title' => '<img src=images/SveaWebPay-Direktbank-100px.png />', 'field' => '');

    // handling fee
    if (isset($this->handling_fee) && $this->handling_fee > 0) {
      $paymentfee_cost = $this->handling_fee;
      if (substr($paymentfee_cost, -1) == '%')
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPINTERNETBANK_HANDLING_APPLIES, $paymentfee_cost), 'field' => '');
      else
      {
        $tax_class = MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS;
        if (DISPLAY_PRICE_WITH_TAX == "true" && $tax_class > 0)
          $paymentfee_tax = $paymentfee_cost * zen_get_tax_rate($tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) / 100;
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPINTERNETBANK_HANDLING_APPLIES, $currencies->format($paymentfee_cost+$paymentfee_tax)), 'field' => '');
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

  function process_button() {
    
    global $db, $order, $order_totals, $language;
    
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
    
    //Import SVEA files
    require('includes/modules/payment/svea/SveaConfig.php');
    
    //SVEA config settings
    $configSvea = SveaConfig::getConfig();
    $configSvea->merchantId = MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID;
    $configSvea->secret = MODULE_PAYMENT_SWPINTERNETBANK_SW; 
    
    //Build Order rows
    $totalPrice = 0;
    $totalTax = 0;
    
    $paymentRequest = new SveaPaymentRequest();
    $orderSvea = new SveaOrder();
    $paymentRequest->order = $orderSvea;;  
           
    
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
            //Nya rader här
            $shippingPriceExVat   = $this->convert_to_currency($_SESSION['shipping']['cost'],$currency);
            $shippingTaxRate = zen_get_tax_rate($shipping->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            $shippingTax     = ($shippingTaxRate / 100) * $shippingPriceExVat; 
            
            $orderRow = new SveaOrderRow();
            $orderRow->amount = number_format(round($shippingPriceExVat+$shippingTax,2),2,'','');
            $orderRow->vat = number_format(round($shippingTax,2),2,'','');
            $orderRow->name = $shipping_description;
            $orderRow->quantity = 1;
            $orderRow->unit = "st";
                	
            //Add the order rows to your order
            $orderSvea->addOrderRow($orderRow);
            
            //Add to totals    
            $totalPrice = $totalPrice+$shippingPriceExVat+$shippingTax;
            $totalTax = $totalTax + $shippingTax;

          break;
        case 'ot_coupon':
          //Nya rader här
          $discountPrice = -$this->convert_to_currency(strip_tags($order_total['value']),$currency);
          
          $orderRow = new SveaOrderRow();
          $orderRow->amount = number_format(round($discountPrice,2),2,'','');
          $orderRow->vat = 0;
          $orderRow->name = strip_tags($order_total['title']);
          $orderRow->quantity = 1;
          $orderRow->unit = "st";
          
          //Add the order rows to your order
          $orderSvea->addOrderRow($orderRow);
        
          //Add to totals    
          $totalPrice = $totalPrice+$discountPrice;

        break;
        // default case handles order totals like handling fee, but also
        // 'unknown' items from other plugins. Might cause problems.
        default:
          $order_total_obj = $GLOBALS[$order_total['code']];
          $tax_rate = (string) zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
          // if displayed WITH tax, REDUCE the value since it includes tax
          if (DISPLAY_PRICE_WITH_TAX == 'true')
            $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));
            $otherTax     = ($tax_rate / 100) * $order_total['value']; 
            
            $orderRow = new SveaOrderRow();
            $orderRow->amount = number_format(round($order_total['value']+$otherTax,2),2,'','');
            $orderRow->vat = number_format(round($otherTax,2),2,'','');
            $orderRow->name = strip_tags($order_total['title']);
            $orderRow->quantity = 1;
            $orderRow->unit = "st";
                	
            //Add the order rows to your order
            $orderSvea->addOrderRow($orderRow);
            
            //Add to totals    
            $totalPrice = $totalPrice+$otherPriceExVat+$otherTax;
            $totalTax = $totalTax + $otherTax;

        break;
      }
    }
    
    
    // Ordered Products
    foreach($order->products as $i => $Item) {
         
        $tax = ($Item['tax'] / 100) * $this->convert_to_currency($Item['final_price'],$currency);
        $price = $this->convert_to_currency($Item['final_price'],$currency) + $tax;
        
        $totalPrice = $totalPrice+($price * $Item['qty']);
        $totalTax = $totalTax + ($tax * $Item['qty']);
        
        $orderRow = new SveaOrderRow();
        $orderRow->amount = number_format(round($price,2),2,'','');
        $orderRow->vat = number_format(round($tax,2),2,'','');
        $orderRow->name = $Item['name'];
        $orderRow->quantity = $Item['qty'];
        $orderRow->sku = $Item['sku'];
        $orderRow->unit = "st";
    
    	
        //Add the order rows to your order
        $orderSvea->addOrderRow($orderRow);
    }
       
    //Set base data for the order
    $orderSvea->amount = number_format(round($totalPrice,2),2,'','');
    $orderSvea->customerRefno = ($new_order_field['orders_id'] + 1).'-'.time();
    $orderSvea->returnUrl = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
    $orderSvea->vat = number_format(round($totalTax,2),2,'','');
    $orderSvea->currency = $currency;
    $orderSvea->cancelurl = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
    
    //Exclude other payments
    $paymethods = array ('CARD','SVEASPLITSE','SVEAINVOICESE');
                
    foreach($paymethods as $method){
        $orderSvea->excludePaymentMethod($method);
    }
    
    
    $paymentRequest->createPaymentMessage();

    $formString  = "<input type='hidden' name='merchantid' value='{$paymentRequest->merchantid}'/>";
    $formString .= "<input type='hidden' name='message' value='{$paymentRequest->payment}'/>";
    $formString .= "<input type='hidden' name='mac' value='{$paymentRequest->mac}'/>";
    
    //return $process_button_string;
    return $formString;
  }

  function before_process() {
    global $db, $order, $order_totals, $language;
    
    
    if ($_REQUEST['response']){
        
        //REQUESTS
        $responseSvea   = $_REQUEST['response'];
        $macSvea        = $_REQUEST['mac'];
        $merchantidSvea = $_REQUEST['merchantid'];
        
        //Import SVEA files
        require('includes/modules/payment/svea/SveaConfig.php');
        
        $resp = new SveaPaymentResponse($responseSvea);

        if($resp->validateMac($macSvea,MODULE_PAYMENT_SWPCREDITCARD_SW) == true){
            
            //SUCCESS 
            if ($resp->statuscode == '0'){
                	                
            // handle successful payments
            $table = array (
                'EKOP'          => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'AKTIA'         => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'BANKAXNO'      => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'FSPA'          => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'GIROPAY'       => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'NORDEADK'      => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'NORDEAFI'      => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'NORDEASE'      => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'OP'            => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'SAMPO'         => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'SEBFTG'        => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'SEBPRV'        => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE,
                'SHB'           => MODULE_PAYMENT_SWPINTERNETBANK_TEXT_TITLE);
            if(array_key_exists($_GET['PaymentMethod'], $table))
              $order->info['payment_method'] = $table[$_GET['PaymentMethod']] . ' - ' . $_GET['PaymentMethod'];
                  
            }else{
                    //FAIL
                    $payment_error_return = 'payment_error=' . $this->code;
                      switch ($resp->statuscode) {
                          case 100:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_100;
                            break;
                          case 105:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_105;
                            break;
                          case 106:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_106;
                            break;
                          case 107:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_107;
                            break;
                          case 108:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_108;
                            break;
                          case 109:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_109;
                            break;
                          case 110:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_110;
                            break;
                          case 113:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_113;
                            break;
                          case 114:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_114;
                            break;
                          case 121:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_121;
                            break;
                          case 124:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_124;
                            break;
                          case 143:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_143;
                            break;
                          default:
                            $_SESSION['SWP_ERROR'] = ERROR_CODE_DEFAULT . $resp->statuscode;
                            break;
                      }
                      if (isset($_SESSION['payment_attempt'])) unset($_SESSION['payment_attempt']);
                        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
                            
                      //zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
                    }
        
           
        }else{
            //MAC NOT VALID
            die('nej');
        }
    }
    
    /*
    // first round, when we redirect to hosted solution
    if (!isset($_GET['Success'])) {
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

      // setup user parameters for hosted solution
      $hosted_params = array( 'Username'      =>  MODULE_PAYMENT_SWPCREDITCARD_USERNAME,
                              'OrderId'       =>  ($new_order_field['orders_id'] + 1).'-'.time(),
                              'ResponseURL'   =>  urlencode((ENABLE_SSL == 'true' ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG).'swphosted/response.php'),
                              'Testmode'      =>  (MODULE_PAYMENT_SWPCREDITCARD_MODE == 'Test' ? 'True' : 'False'),
                              'Language'      =>  $user_language,
                              'Country'       =>  $user_country,
                              'Paymentmethod' =>  'card',
                              'Currency'      =>  $currency,
                              'Version'       =>  $this->version,
                              'Module'        =>  'ZenCart');
      $current_row = 0;

      // handle products
      foreach($order->products as $productId => $product) {
        $current_row++;
        $hosted_params['Row'.$current_row.'AmountExVAT']    = $this->convert_to_currency($product['final_price'],$currency);
        $hosted_params['Row'.$current_row.'VATPercentage']  = $product['tax'];
        $hosted_params['Row'.$current_row.'Description']    = urlencode(utf8_encode($product['name']));
        $hosted_params['Row'.$current_row.'Quantity']       = $product['qty'];
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
            $hosted_params['Row'.$current_row.'AmountExVAT']    = $this->convert_to_currency($_SESSION['shipping']['cost'],$currency);
            $hosted_params['Row'.$current_row.'VATPercentage']  = zen_get_tax_rate($shipping->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            $hosted_params['Row'.$current_row.'Description']    = urlencode( utf8_encode($shipping_description) );
            $hosted_params['Row'.$current_row.'Quantity']       = '1';
            break;
          case 'ot_coupon':
            $hosted_params['Row'.$current_row.'VATPercentage']  = '0';
            $hosted_params['Row'.$current_row.'AmountExVAT']    = -$this->convert_to_currency(strip_tags($order_total['value']),$currency);
            $hosted_params['Row'.$current_row.'Description']    = urlencode( utf8_encode(strip_tags($order_total['title'])) );
            $hosted_params['Row'.$current_row.'Quantity']       = '1';
            break;
          // default case handles order totals like handling fee, but also
          // 'unknown' items from other plugins. Might cause problems.
          default:
            $order_total_obj = $GLOBALS[$order_total['code']];
            $tax_rate = zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            // if displayed WITH tax, REDUCE the value since it includes tax
            if (DISPLAY_PRICE_WITH_TAX == 'true')
              $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));
            $hosted_params['Row'.$current_row.'VATPercentage']  = $tax_rate;
            $hosted_params['Row'.$current_row.'AmountExVAT']    = $this->convert_to_currency(strip_tags($order_total['value']),$currency);
            $hosted_params['Row'.$current_row.'Description']    = urlencode( utf8_encode(strip_tags($order_total['title'])) );
            $hosted_params['Row'.$current_row.'Quantity']       = '1';
            break;
        }
      }

      foreach($hosted_params as $key => $value) {
        $hosted_params_array[] = $key.'='.$value;
      }

      // create get data from param array and create MD5 verification
      $process_md5_check = $this->sveawebpay_url.'?'.mb_convert_encoding(implode('&', $hosted_params_array), 'utf-8');
      $md5 = md5($process_md5_check.MODULE_PAYMENT_SWPCREDITCARD_PASSWORD);
      $redirect_url = $process_md5_check.'&MD5='.$md5;

      // send to hosted solution
      zen_redirect($redirect_url);
    }
    
    
    // second round, when we return from hosted solution, whether sucessful or not

    // check MD5 verification, although it should really not fail
    $no_page_query = explode(FILENAME_CHECKOUT_PROCESS.'&',$_SERVER['QUERY_STRING']);
    $raw_query    = explode('&MD5=', $no_page_query[1]);
    $page_url     = (ENABLE_SSL == 'true' ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG).'swphosted/response.php';
    $md5_string   = $page_url.'?'.$raw_query[0].MODULE_PAYMENT_SWPCREDITCARD_PASSWORD;
    if (md5($md5_string) != $raw_query[1]) {
      $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_MESSAGE_PAYMENT_MD5_FAILED);
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
    }

    // handle failed payments
    if (strtolower($_GET['Success']) == 'false') {
      switch ($_GET['ErrorCode']) {
          case 1:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_1);
            break;
          case 2:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_2);
            break;
          case 3:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_3);
            break;
          case 4:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_4);
            break;
          case 5:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_5);
            break;
          default:
            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_DEFAULT . $_GET['ErrorCode']);
            break;
      }
      // unset this since otherwise shop thinks we are slamming if we try to process again
      if (isset($_SESSION['payment_attempt'])) unset($_SESSION['payment_attempt']);
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
    }

    // handle successful payments
    if (isset($_GET['SecurityNumber']))
      $order->info['securityNumber']    = $_GET['SecurityNumber'];
    if (isset($_GET['Firstname'])) {
      $order->billing['firstname']      = $_GET['Firstname'];
      $order->billing['lastname']       = $_GET['Lastname'];
      $order->billing['street_address'] = $_GET['AddressLine1'];
      $order->billing['suburb']         = $_GET['AddressLine2'];
      $order->billing['state']          = $_GET['PostArea'];
      $order->billing['postcode']       = $_GET['PostCode'];
    }

    $table = array (
            'KORTABSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'KORTINDK'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'KORTINFI'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'KORTINNO'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'KORTINSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'NETELLER'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
            'PAYSON'        => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE);

    if(array_key_exists($_GET['PaymentMethod'], $table))
      $order->info['payment_method'] = $table[$_GET['PaymentMethod']] . ' - ' . $_GET['PaymentMethod'];*/
  }

  // if payment accepted, insert order into database
  function after_process() {
    global $insert_id, $order;

    $sql_data_array = array(  'orders_id'         => $insert_id,
                              'orders_status_id'  => $order->info['order_status'],
                              'date_added'        => 'now()',
                              'customer_notified' => 0,
                              'comments'          => 'Accepted by SveaWebPay '.date("Y-m-d G:i:s") .' Security Number #: '.$order->info['securityNumber']);
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    return false;
  }

  // sets error message to the GET error value
  function get_error() {
    return array('title' => ERROR_MESSAGE_PAYMENT_FAILED,
                 'error' => stripslashes(urldecode($_GET['error'])));
  }

  // standard check if installed function
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SWPCREDITCARD_STATUS'");
      $this->_check = !$check_rs->EOF;
    }
    return $this->_check;
  }

  // insert configuration keys here
  function install() {
    global $db;
    $common = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added";
    $db->Execute($common . ", set_function) values ('Enable SveaWebPay Direct Bank Payment Module', 'MODULE_PAYMENT_SWPINTERNETBANK_STATUS', 'True', 'Do you want to accept SveaWebPay payments?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('SveaWebPay Merchant ID', 'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID', '', 'The Merchant ID', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Secret Word', 'MODULE_PAYMENT_SWPINTERNETBANK_SW', '', 'The Secret word', '6', '0', now())");
    $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPINTERNETBANK_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
    $db->Execute($common . ") values ('Accepted Currencies', 'MODULE_PAYMENT_SWPINTERNETBANK_ALLOWED_CURRENCIES','SEK,NOK,DKK,EUR', 'The accepted currencies, separated by commas.  These <b>MUST</b> exist within your currencies table, along with the correct exchange rates.','6','0',now())");
    $db->Execute($common . ", set_function) values ('Default Currency', 'MODULE_PAYMENT_SWPINTERNETBANK_DEFAULT_CURRENCY', 'SEK', 'Default currency used, if the customer uses an unsupported currency it will be converted to this. This should also be in the supported currencies list.', '6', '0', now(), 'zen_cfg_select_option(array(\'SEK\',\'NOK\',\'DKK\',\'EUR\'), ')");
    $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
    $db->Execute($common . ", set_function) values ('Display SveaWebPay Images', 'MODULE_PAYMENT_SWPINTERNETBANK_IMAGES', 'True', 'Do you want to display SveaWebPay images when choosing between payment options?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
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
    return array( 'MODULE_PAYMENT_SWPINTERNETBANK_STATUS',
                  'MODULE_PAYMENT_SWPINTERNETBANK_MERCHANT_ID',
                  'MODULE_PAYMENT_SWPINTERNETBANK_SW',
                  'MODULE_PAYMENT_SWPINTERNETBANK_MODE',
                  'MODULE_PAYMENT_SWPINTERNETBANK_ALLOWED_CURRENCIES',
                  'MODULE_PAYMENT_SWPINTERNETBANK_DEFAULT_CURRENCY',
                  'MODULE_PAYMENT_SWPINTERNETBANK_ORDER_STATUS_ID',
                  'MODULE_PAYMENT_SWPINTERNETBANK_IMAGES',
                  'MODULE_PAYMENT_SWPINTERNETBANK_IGNORE',
                  'MODULE_PAYMENT_SWPINTERNETBANK_ZONE',
                  'MODULE_PAYMENT_SWPINTERNETBANK_SORT_ORDER');
  }

  function convert_to_currency($value, $currency) {
    global $currencies;
    // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
    return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $currencies->currencies[$currency]['decimal_places']), $currencies->currencies[$currency]['decimal_places'], ',', '');
  }

}
?>