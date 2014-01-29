<?php

class sveawebpay_handling_fee {

    function sveawebpay_handling_fee() {
        $this->code = 'sveawebpay_handling_fee';
        $this->title = MODULE_ORDER_TOTAL_SWPHANDLING_NAME;
        $this->description = MODULE_ORDER_TOTAL_SWPHANDLING_DESCRIPTION;
        $this->enabled = MODULE_ORDER_TOTAL_SWPHANDLING_STATUS == 'true' ? true : false;
        $this->sort_order = MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER;
        $this->output = array();
        $this->tax_class = MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS;
    }

    function process() {
        global $order, $currencies;
        if (isset($_SESSION['SWP_CODE']))
            $payment_module = $_SESSION['SWP_CODE'];

        // calculate handling fee
        if (isset($payment_module) && isset($GLOBALS[$payment_module])) {
            if (isset($GLOBALS[$payment_module]->handling_fee) && zen_not_null($GLOBALS[$payment_module]->handling_fee)) {
                $paymentfee_cost = $GLOBALS[$payment_module]->handling_fee;
                // if percentage, calculate from order total
                if (substr($paymentfee_cost, -1) == '%')
                    $paymentfee_cost = (float) ((substr($paymentfee_cost, 0, -1) / 100) * $order->info['subtotal']);
            }
        }

        // calculate tax and add cost to order total and tax
        if ($paymentfee_cost) {
            $paymentfee_tax = 0;
            if ($this->tax_class > 0) {
                $paymentfee_tax = $paymentfee_cost * zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']) / 100;
                if ($paymentfee_tax > 0) {
                    $order->info['tax'] += $paymentfee_tax;
                    $paymentfee_taxgroup = zen_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                    $order->info['tax_groups'][$paymentfee_taxgroup] += $paymentfee_tax;
                }
            }
            $order->info['total'] += $paymentfee_cost + $paymentfee_tax;
            if (DISPLAY_PRICE_WITH_TAX == 'true') {
                $this->output[] = array('title' => sprintf(MODULE_ORDER_TOTAL_SWPHANDLING_LABEL, $GLOBALS[$payment_module]->title),
                    'text' => $currencies->format($paymentfee_cost + $paymentfee_tax, true, $order->info['currency'], $order->info['currency_value']),
                    'tax' => $paymentfee_tax,
                    'value' => $paymentfee_cost + $paymentfee_tax);
            } else {
                $this->output[] = array('title' => sprintf(MODULE_ORDER_TOTAL_SWPHANDLING_LABEL, $GLOBALS[$payment_module]->title),
                    'text' => $currencies->format($paymentfee_cost, true, $order->info['currency'], $order->info['currency_value']),
                    'tax' => $paymentfee_tax,
                    'value' => $paymentfee_cost);
            }
        }
    }

    // standard functions below
    function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_rs = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS'");
            $this->_check = !$check_rs->EOF;
        }
        return $this->_check;
    }

    function install() {
        global $db;
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER', '299', 'Sort order of display.', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fee', 'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE', '29', 'This handling fee will be applied to all orders using the invoice payment method. The figure can either be set to a specific amount, eg. <b>5.00</b>, or set to a percentage of the order total, by ensuring the last character is a \'%\' eg <b>5.00%</b>.', '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS', '0', 'Use the following tax class on the payment handling fee.', '6', '0', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");


        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fee"." (NO)"."', 'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE"."_NO"."', '29', '', '6', '0', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class"." (NO)"."', 'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS"."_NO"."', '0', '', '6', '0', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");

    }

    function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array(
            'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS',    
            'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER',

            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE',  // no suffix = SE
            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS',
            
            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE_NO',
            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS_NO'
            
//            'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS_DK',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS_DK',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER_DK',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE_DK',
//            
//            'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS_FI',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS_FI',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER_FI',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE_FI',
//            
//            'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS_NL',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS_NL',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER_NL',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE_NL',
//            
//            'MODULE_ORDER_TOTAL_SWPHANDLING_STATUS_DE',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_TAX_CLASS_DE',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_SORT_ORDER_DE',
//            'MODULE_ORDER_TOTAL_SWPHANDLING_HANDLING_FEE_DE'
        );
    }

}

?>