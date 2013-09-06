<?php
//
require('includes/application_top.php');

//
// perform getAddresses() via php integration package
if( isset($_POST['getAddresses']) ) {
    // Include Svea php integration package files    
    require('includes/modules/payment/svea_v4/Includes.php'); 

    $ssn = $_POST['sveaSSN'];
    $country = $_POST['sveaCountryCode'];

    // private individual
    if( isset($_POST['sveaIsCompany']) && $_POST['sveaIsCompany'] === '0' ) {
        $response = WebPay::getAddresses()
            ->setOrderTypeInvoice()
            ->setCountryCode( $country )              
            ->setIndividual( $ssn )
            ->doRequest();    
    }
    
    // company/organisation
    if( isset($_POST['sveaIsCompany']) && $_POST['sveaIsCompany'] === '1' ) {
        $response = WebPay::getAddresses()
            ->setOrderTypeInvoice()
            ->setCountryCode( $country )
            ->setCompany( $ssn )
            ->doRequest();    
    }
    
    // $getAddressResponse has type Svea\getAddressIdentity 
    foreach( $response->customerIdentity as $key => $getAddressIdentity ) {
    
        $addressSelector = $getAddressIdentity->addressSelector;
        $fullName = $getAddressIdentity->fullName;  // also used for company name
        $street = $getAddressIdentity->street;
        $coAddress = $getAddressIdentity->coAddress;
        $zipCode = $getAddressIdentity->zipCode;
        $locality = $getAddressIdentity->locality;
            
        //Send back to user
        echo(   '<option id="address_' . $key .
                    '" value="' . $addressSelector . 
                    '">' . $fullName . 
                    ', ' . $street . 
                    ', ' . $coAddress .
                    ', ' . $zipCode . 
                    ' ' . $locality . 
                '</option>'
        );    
    }
}

// --------------------------------------------------------

/*
 *
 * PartPayment
 *
 */
 
if (isset($_POST['paymentOptions'])) {
    
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

} 

function convert_to_currency($value, $currency) {
    global $currencies;
    // item price is ALWAYS given in internal price from the products DB, so just multiply by currency rate from currency table
    return number_format(zen_round($value * $currencies->currencies[$currency]['value'], $decimal_places), 2, $decimal_symbol, '');
}
?>