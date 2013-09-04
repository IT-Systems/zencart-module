<?php
use swp_\WebPay as WebPay;

//
require('includes/application_top.php');
//require('includes/modules/payment/svea/svea.php');
//require(DIR_WS_CLASSES . 'order.php');
//
//$order = new order;
//
//if (isset($_POST['paymentOptions'])){
//    $svea_server = (MODULE_PAYMENT_SWPPARTPAY_MODE == 'Test') ? 'https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL' : 'https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL';
//}else{
//    $svea_server = (MODULE_PAYMENT_SWPINVOICE_MODE == 'Test') ? 'https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL' : 'https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL';
//}


//
///**
// * Validates SSN
// */
//class Validate{
//
//    private function luhn($ssn){
//        $sum = 0;
//        for ($i = 0; $i < strlen($ssn)-1; $i++){
//            $tmp = substr($ssn, $i, 1) * (2 - ($i & 1)); //växla mellan 212121212
//            if ($tmp > 9) $tmp -= 9;
//            $sum += $tmp;
//        }
//
//        //extrahera en-talet
//        $sum = (10 - ($sum % 10)) % 10;
//        return substr($ssn, -1, 1) == $sum;
//    }
//
//    private function only_numbers($ssn){
//        if (is_numeric($ssn)){
//                return true;
//        }else{
//                return false;
//        }
//    }
//
//    public function check($ssn){
//
//        $error_msg = null;
//
//        if ($this->only_numbers($ssn) == false){
//            $error_msg = "Persnr/orgnr får endast bestå av siffror";
//        }elseif ($this->luhn($ssn) == false){
//            $error_msg = "Persnr/orgnr har felaktig kontrollsiffra, vänligen ange ett giltigt nr";
//        }
//
//        $returns = array("error_msg" => $error_msg);
//
//        return $returns;
//    }
//}

//
// perform getAddress() via php integration package
if( isset($_POST['v4']) ) {
    // Include Svea php integration package files    
    require('includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 

    $ssn = $_POST['sveapnr'];

    $response = WebPay::getAddresses()
        ->setOrderTypeInvoice()
        ->setCountryCode("SE")                                             
        ->setIndividual( $ssn )                                       
        ->doRequest();    
    
    
    // $getAddressResponse comes from type wep_:\getAddressIdentity 
    foreach( $response->customerIdentity as $key => $getAddressIdentity ) {
    
        $addressSelector = $getAddressIdentity->addressSelector;
        $fullName = $getAddressIdentity->fullName;
        $street = $getAddressIdentity->street;
        $zipCode = $getAddressIdentity->zipCode;
        $locality = $getAddressIdentity->locality;
            
        //Send back to user
        $as = "#addressSelector_invoice";
        echo(   'jQuery("' .$as. '").show();'   );
        echo(   '     jQuery("' .$as. '").append( "<option id=\"address_' . $key .
                                            '\" value=\"' . $addressSelector . 
                                            '\">' . $fullName . 
                                            ', ' . $street . 
                                            //', ' . $getAddressIdentity->coAddress .
                                            ', ' . $zipCode . 
                                            ' ' . $locality . 
                                            '</option>" );'
        );    
    }
}
die();

//
// getAddress() lookup from svea.js
if (isset($_POST['sveapnr'])  ) {
    if (!isset($_POST['f'])){ 
        $PP = (isset($_POST['paymentOptions'])) ? true : false ;

        // validate SSN
        $v = new validate();
        $validation = $v->check($_POST['sveapnr']);

        //Get svea configuration for each country based on currency
        $sveaConf = ($PP == true) ? getCountryConfigPP($order->info['currency']) : getCountryConfigInvoice($order->info['currency']) ;

        $company = ($PP == true) ? false : $_POST['is_company'] ;

        if ($order->info['currency'] == 'SEK'){
            $error_msg = $validation['error_msg'];
        }
        else{
            $error_msg = null;
        }


        if ($PP == true){
            $pnef = "#sveaSSN_error_partpayment";
            $as   = "#adressSelectorPP";
            $petf = "#persnr_error_trPP";
        }else{
            $pnef = "#sveaSSN_error_invoice";
            $as   = "#adressSelector_fakt";
            $petf = "#persnr_error_tr_fakt";
        }

        if ($error_msg == '' || $error_msg == null){


        $request = Array(
                "request" => Array(
              "Auth" => Array(
                "Username" => $sveaConf['username'],
                "Password" => $sveaConf['password'],
                "ClientNumber" => $sveaConf['clientno']
               ),
                  "IsCompany" => $company,
                  "CountryCode" => $sveaConf['countryCode'],
                  "SecurityNumber" => $_POST['sveapnr']
                )
          );

        //Call Soap and set up data
        $client = new SoapClient( $svea_server );

        //Handle response
        $response =  $client->GetAddresses( $request );


        echo '$("'.$as.'").empty();';

        if (isset($response->GetAddressesResult->ErrorMessage)){
                echo 'jQuery("'.$pnef.'").html("'.$response->GetAddressesResult->ErrorMessage.'");
                                jQuery("'.$pnef.'").show();';
        }elseif(is_array($response->GetAddressesResult->Addresses->CustomerAddress)){
                        foreach ($response->GetAddressesResult->Addresses->CustomerAddress as $key => $info){

                                $firstName = $info->FirstName;
                                $lastName = $info->LastName;
                    $LegalName = $info->LegalName;
                                $address = $info->AddressLine1." ".$info->AddressLine2;
                                $postCode = $info->Postcode;
                                $city = $info->Postarea;
                                $addressSelector = $info->AddressSelector;

                //Send back to user
                        echo '
                jQuery("'.$as.'").show();
                        jQuery("'.$as.'").append("<option id=\"adress_'.$key.'\" value=\"'.$addressSelector.'\">'.$LegalName.', '.$address.', '.$postCode.' '.$city.'</option>");
                ';

                }

                echo '
                jQuery("button[type=submit]").removeAttr("disabled");
                jQuery("'.$petf.'").hide();';
        }else{
                        $firstName = $response->GetAddressesResult->Addresses->CustomerAddress->FirstName;
                        $lastName = $response->GetAddressesResult->Addresses->CustomerAddress->LastName;
                $LegalName = $response->GetAddressesResult->Addresses->CustomerAddress->LegalName;
                        $address = $response->GetAddressesResult->Addresses->CustomerAddress->AddressLine1." ".$response->GetAddressesResult->Addresses->CustomerAddress->AddressLine2;
                        $postCode = $response->GetAddressesResult->Addresses->CustomerAddress->Postcode;
                        $city = $response->GetAddressesResult->Addresses->CustomerAddress->Postarea;
                        $addressSelector = $response->GetAddressesResult->Addresses->CustomerAddress->AddressSelector;

                //Send back to user
                        echo '
                jQuery("'.$as.'").show();
                        jQuery("'.$as.'").append("<option id=\"adress_'.$key.'\" value=\"'.$addressSelector.'\">'.$LegalName.', '.$address.', '.$postCode.' '.$city.'</option>");
                jQuery("button[type=submit]").removeAttr("disabled");
                jQuery("'.$petf.'").hide();';
        }



        }else{
                echo 'jQuery("'.$pnef.'").html("'.$error_msg.'");
                                jQuery("'.$petf.'").show();
                         ';

        }

    }
};


/*
 *
 * PartPayment
 *
 */
 
if (isset($_POST['paymentOptions'])):
    
    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';

    require $language_page_directory.'modules/payment/sveawebpay_partpay.php';

    $currencies = new currencies;
    
    $sveaConf = getCountryConfigPP($order->info['currency'],'NL') ;
     
    //Order rows
    foreach($order->products as $i => $Item) {
    
    $orderRowArr = Array(
              "ClientOrderRowNr" => $i,
              "Description" => $Item['name'],
              "PricePerUnit" => convert_to_currency(round($Item['final_price'],2),$order->info['currency']),
              "NrOfUnits" => $Item['qty'],
              "Unit" => "st",
              "VatPercent" => $Item['tax'],
              "DiscountPercent" => 0
            );
    
    if (isset($clientInvoiceRows)){
        $clientInvoiceRows[$i] = $orderRowArr;
    }else{
        $clientInvoiceRows[] = $orderRowArr;
    }
    }
    
    //The createOrder Data
    $request = Array(
    	"request" => Array(
          "Auth" => Array(
            "Username" => $sveaConf['username'],
            "Password" => $sveaConf['password'],
            "ClientNumber" => $sveaConf['clientno']
           ),
          "Amount" => 0,
          "InvoiceRows" => array('ClientInvoiceRowInfo' => $clientInvoiceRows)
        )
    );


//Call Soap
$client = new SoapClient( $svea_server );

 //Make soap call to below method using above data
$svea_req = $client->GetPaymentPlanOptions( $request);

$response = $svea_req->GetPaymentPlanOptionsResult->PaymentPlanOptions;
//print_r($response);
echo 'jQuery("#paymentOptions").empty();';

//print_r($svea_req);
foreach ($svea_req->GetPaymentPlanOptionsResult->PaymentPlanOptions->PaymentPlanOption as $key => $ss){
	
	if ($ss->ContractLengthInMonths == 3){
		$description = DD_PAY_IN_THREE;
	}else{
		$description = DD_PARTPAY_IN.$ss->ContractLengthInMonths.DD_MONTHS.', ('.$ss->MonthlyAnnuity.' '.DD_CURRENY_PER_MONTH.')';
	}
	echo '
		jQuery("#paymentOptions").append("<option id=\"paymentOption'.$key.'\" value=\"'.$ss->CampainCode.'\">'.$description.'</option>");
	';
}

echo 'jQuery("#paymentOptions").show();';    

endif; 

function convert_to_currency($value, $currency) {
    global $currencies;
    // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
    return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $decimal_places), 2, $decimal_symbol, '');
}
?>