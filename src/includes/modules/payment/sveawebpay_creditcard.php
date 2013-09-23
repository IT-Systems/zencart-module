<?php
/*
HOSTED SVEAWEBPAY PAYMENT MODULE FOR ZEN CART
-----------------------------------------------
Version 4.0 - Zen Cart
Kristian Grossman-Madsen, Shaho Ghobadi
*/

class sveawebpay_creditcard {

  function sveawebpay_creditcard() {
    global $order;

    $this->code = 'sveawebpay_creditcard';
    $this->version = 4;

    $_SESSION['SWP_CODE'] = $this->code;
    
    $this->form_action_url = (MODULE_PAYMENT_SWPCREDITCARD_STATUS == 'True') ? 'https://test.sveaekonomi.se/webpay/payment' : 'https://webpay.sveaekonomi.se/webpay/payment';
    $this->title = MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_SWPCREDITCARD_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_SWPCREDITCARD_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_SWPCREDITCARD_SORT_ORDER;
    /*
    $this->sveawebpay_url = MODULE_PAYMENT_SWPCREDITCARD_URL;
    $this->handling_fee = MODULE_PAYMENT_SWPCREDITCARD_HANDLING_FEE;
    */
    $this->default_currency = MODULE_PAYMENT_SWPCREDITCARD_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPCREDITCARD_ALLOWED_CURRENCIES);
    $this->display_images = ((MODULE_PAYMENT_SWPCREDITCARD_IMAGES == 'True') ? true : false);
    $this->ignore_list = explode(',', MODULE_PAYMENT_SWPCREDITCARD_IGNORE);
    if ((int)MODULE_PAYMENT_SWPCREDITCARD_ORDER_STATUS_ID > 0)
      $this->order_status = MODULE_PAYMENT_SWPCREDITCARD_ORDER_STATUS_ID;
    if (is_object($order)) $this->update_status();
  }

  function update_status() {
    global $db, $order, $currencies, $messageStack;

    // update internal currency
    $this->default_currency = MODULE_PAYMENT_SWPCREDITCARD_DEFAULT_CURRENCY;
    $this->allowed_currencies = explode(',', MODULE_PAYMENT_SWPCREDITCARD_ALLOWED_CURRENCIES);

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
      $fields[] = array('title' => '<img src=images/SveaWebPay-Kort-100px.png />', 'field' => '');

    // handling fee
    if (isset($this->handling_fee) && $this->handling_fee > 0) {
      $paymentfee_cost = $this->handling_fee;
      if (substr($paymentfee_cost, -1) == '%')
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES, $paymentfee_cost), 'field' => '');
      else
      {
        $tax_class = MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS;
        if (DISPLAY_PRICE_WITH_TAX == "true" && $tax_class > 0)
          $paymentfee_tax = $paymentfee_cost * zen_get_tax_rate($tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) / 100;
        $fields[] = array('title' => sprintf(MODULE_PAYMENT_SWPCREDITCARD_HANDLING_APPLIES, $currencies->format($paymentfee_cost+$paymentfee_tax)), 'field' => '');
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

    //
    // Include Svea php integration package files    
    require(DIR_FS_CATALOG . 'includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 
 
    // Create and initialize order object, using either test or production configuration
    $swp_order = WebPay::createOrder() // TODO uses default testmode config for now
        ->setCountryCode( $user_country )
        ->setCurrency($currency)                       //Required for card & direct payment and PayPage payment.
        ->setClientOrderNumber($client_order_number)   //Required for card & direct payment, PaymentMethod payment and PayPage payments
        ->setOrderDate(date('c'))                      //Required for synchronous payments
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
                    
                    // TODO for now, we only support fixed amount coupons. 
                    // Investigate how zencart calculates %-rebates if shop set to display prices inc.tax i.e. 69.99*1.25 => 8.12 if 10% off?!
                    
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

                // TODO default case not tested, lack of test case/data. ported from 3.0 zencart module
                default:
                // default case handles 'unknown' items from other plugins. Might cause problems.   
                    $order_total_obj = $GLOBALS[$order_total['code']];
                    $tax_rate = zen_get_tax_rate($order_total_obj->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                    // if displayed WITH tax, REDUCE the value since it includes tax
                    if (DISPLAY_PRICE_WITH_TAX == 'true') {
                        $order_total['value'] = (strip_tags($order_total['value']) / ((100 + $tax_rate) / 100));
                    }
                    
                    $swp_order->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1)          //Required
                            ->setAmountExVat($this->convert_to_currency(strip_tags($order_total['value']), $currency))
                            ->setVatPercent($tax_rate)  //Optional, see info above
                            ->setDescription($order_total['title'])        //Optional
                    );
                break;
            }
        }
    
        $swp_form =  $swp_order->usePaymentMethod(PaymentMethod::KORTCERT)
           ->setCancelUrl( zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true) )      // todo test this
           ->setReturnUrl( zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL') )
           ->getPaymentForm();

        //return $process_button_string;
        return  $swp_form->htmlFormFieldsAsArray['input_merchantId'] .
                $swp_form->htmlFormFieldsAsArray['input_message'] .
                $swp_form->htmlFormFieldsAsArray['input_mac'];

    }
  
 function before_process() {
    global $db, $order, $order_totals, $language;    
    
    if ($_REQUEST['response']){
        
        //
        // Include Svea php integration package files    
        require(DIR_FS_CATALOG . 'includes/modules/payment/svea_v4/Includes.php');
    
        // localization parameters
        $user_country = $order->billing['country']['iso_code_2'];
        
        // TODO use config in this
        $resp = new SveaResponse($_REQUEST, $user_country); //HostedPaymentResponse 

        // check for bad response
        if( $resp->response->resultcode == '0' ) {     
            die('Response failed authorization. AC not valid or 
                Response is not recognized');  // TODO don't die()            
        }
        
        // response ok, check if payment accepted
        else {            
            // handle successful payments
            if ($resp->response->accepted == '1'){           
                $table = array (
                        'KORTABSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'KORTINDK'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'KORTINFI'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'KORTINNO'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'KORTINSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'NETELLER'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
                        'PAYSON'        => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE);

                if(array_key_exists($_GET['PaymentMethod'], $table)) {
                    $order->info['payment_method'] = 
                        $table[$_GET['PaymentMethod']] . ' - ' . $_GET['PaymentMethod'];
                }

            }
            // handle failed payments
            else{
                
                $payment_error_return = 'payment_error=' . $resp->response->resultcode;
    
                switch ($resp->response->resultcode) {
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
                      $_SESSION['SWP_ERROR'] = 
                            ERROR_CODE_DEFAULT . $resp->response->resultcode;
                      break;
                }
                
                if (isset($_SESSION['payment_attempt'])) {
                    unset($_SESSION['payment_attempt']);
                }
                
                zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return) );
            }
        } 
    }
    
//    // second round, when we return from hosted solution, whether sucessful or not
//
//    // check MD5 verification, although it should really not fail
//    $no_page_query = explode(FILENAME_CHECKOUT_PROCESS.'&',$_SERVER['QUERY_STRING']);
//    $raw_query    = explode('&MD5=', $no_page_query[1]);
//    $page_url     = (ENABLE_SSL == 'true' ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG).'swphosted/response.php';
//    $md5_string   = $page_url.'?'.$raw_query[0].MODULE_PAYMENT_SWPCREDITCARD_PASSWORD;
//    if (md5($md5_string) != $raw_query[1]) {
//      $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_MESSAGE_PAYMENT_MD5_FAILED);
//      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
//    }
//
//    // handle failed payments
//    if (strtolower($_GET['Success']) == 'false') {
//      switch ($_GET['ErrorCode']) {
//          case 1:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_1);
//            break;
//          case 2:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_2);
//            break;
//          case 3:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_3);
//            break;
//          case 4:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_4);
//            break;
//          case 5:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_5);
//            break;
//          default:
//            $payment_error_return = 'payment_error=' . $this->code . '&swperror=' . urlencode(ERROR_CODE_DEFAULT . $_GET['ErrorCode']);
//            break;
//      }
//      // unset this since otherwise shop thinks we are slamming if we try to process again
//      if (isset($_SESSION['payment_attempt'])) unset($_SESSION['payment_attempt']);
//      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return));
//    }
//
//    // handle successful payments
//    if (isset($_GET['SecurityNumber']))
//      $order->info['securityNumber']    = $_GET['SecurityNumber'];
//    if (isset($_GET['Firstname'])) {
//      $order->billing['firstname']      = $_GET['Firstname'];
//      $order->billing['lastname']       = $_GET['Lastname'];
//      $order->billing['street_address'] = $_GET['AddressLine1'];
//      $order->billing['suburb']         = $_GET['AddressLine2'];
//      $order->billing['state']          = $_GET['PostArea'];
//      $order->billing['postcode']       = $_GET['PostCode'];
//    }
//
//    $table = array (
//            'KORTABSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'KORTINDK'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'KORTINFI'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'KORTINNO'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'KORTINSE'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'NETELLER'      => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE,
//            'PAYSON'        => MODULE_PAYMENT_SWPCREDITCARD_TEXT_TITLE);
//
//    if(array_key_exists($_GET['PaymentMethod'], $table))
//      $order->info['payment_method'] = $table[$_GET['PaymentMethod']] . ' - ' . $_GET['PaymentMethod'];*/
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
    $db->Execute($common . ", set_function) values ('Enable SveaWebPay Card Payment Module', 'MODULE_PAYMENT_SWPCREDITCARD_STATUS', 'True', 'Do you want to accept SveaWebPay payments?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('SveaWebPay Merchant ID', 'MODULE_PAYMENT_SWPCREDITCARD_MERCHANT_ID', '', 'The Merchant ID', '6', '0', now())");
    $db->Execute($common . ") values ('SveaWebPay Secret Word', 'MODULE_PAYMENT_SWPCREDITCARD_SW', '', 'The Secret word', '6', '0', now())");
    $db->Execute($common . ", set_function) values ('Transaction Mode', 'MODULE_PAYMENT_SWPCREDITCARD_MODE', 'Test', 'Transaction mode used for processing orders. Production should be used for a live working cart. Test for testing.', '6', '0', now(), 'zen_cfg_select_option(array(\'Production\', \'Test\'), ')");
    $db->Execute($common . ") values ('Accepted Currencies', 'MODULE_PAYMENT_SWPCREDITCARD_ALLOWED_CURRENCIES','SEK,NOK,DKK,EUR', 'The accepted currencies, separated by commas.  These <b>MUST</b> exist within your currencies table, along with the correct exchange rates.','6','0',now())");
    $db->Execute($common . ", set_function) values ('Default Currency', 'MODULE_PAYMENT_SWPCREDITCARD_DEFAULT_CURRENCY', 'SEK', 'Default currency used, if the customer uses an unsupported currency it will be converted to this. This should also be in the supported currencies list.', '6', '0', now(), 'zen_cfg_select_option(array(\'SEK\',\'NOK\',\'DKK\',\'EUR\'), ')");
    $db->Execute($common . ", set_function, use_function) values ('Set Order Status', 'MODULE_PAYMENT_SWPCREDITCARD_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', now(), 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name')");
    $db->Execute($common . ", set_function) values ('Display SveaWebPay Images', 'MODULE_PAYMENT_SWPCREDITCARD_IMAGES', 'True', 'Do you want to display SveaWebPay images when choosing between payment options?', '6', '0', now(), 'zen_cfg_select_option(array(\'True\', \'False\'), ')");
    $db->Execute($common . ") values ('Ignore OT list', 'MODULE_PAYMENT_SWPCREDITCARD_IGNORE','ot_pretotal', 'Ignore the following order total codes, separated by commas.','6','0',now())");
    $db->Execute($common . ", set_function, use_function) values ('Payment Zone', 'MODULE_PAYMENT_SWPCREDITCARD_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', now(), 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title')");
    $db->Execute($common . ") values ('Sort order of display.', 'MODULE_PAYMENT_SWPCREDITCARD_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
  }

  // standard uninstall function
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  // must perfectly match keys inserted in install function
  function keys() {
    return array( 'MODULE_PAYMENT_SWPCREDITCARD_STATUS',
                  'MODULE_PAYMENT_SWPCREDITCARD_MERCHANT_ID',
                  'MODULE_PAYMENT_SWPCREDITCARD_SW',
                  'MODULE_PAYMENT_SWPCREDITCARD_MODE',
                  'MODULE_PAYMENT_SWPCREDITCARD_ALLOWED_CURRENCIES',
                  'MODULE_PAYMENT_SWPCREDITCARD_DEFAULT_CURRENCY',
                  'MODULE_PAYMENT_SWPCREDITCARD_ORDER_STATUS_ID',
                  'MODULE_PAYMENT_SWPCREDITCARD_IMAGES',
                  'MODULE_PAYMENT_SWPCREDITCARD_IGNORE',
                  'MODULE_PAYMENT_SWPCREDITCARD_ZONE',
                  'MODULE_PAYMENT_SWPCREDITCARD_SORT_ORDER');
  }

  function convert_to_currency($value, $currency) {
    global $currencies;
    // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
    return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $currencies->currencies[$currency]['decimal_places']), $currencies->currencies[$currency]['decimal_places'], ',', '');
  }
}
?>
