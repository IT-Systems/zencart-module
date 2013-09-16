<?php
//
require('includes/application_top.php');

/**
 *  get iso 3166 customerCountry from zencart customer settings
 */
if( isset($_POST['SveaAjaxGetCustomerCountry']) ) {
    
    $country = zen_get_countries_with_iso_codes( $_SESSION['customer_country_id'] );    
    echo $country['countries_iso_code_2'];
}

/**
 *  perform getAddresses() via php integration package, return dropdown html widget
 */
if( isset($_POST['SveaAjaxGetAddresses']) ) {

    // Include Svea php integration package files    
    require('includes/modules/payment/svea_v4/Includes.php'); 

    $ssn = isset( $_POST['sveaSSN'] ) ? $_POST['sveaSSN'] : "swp_not_set";
    $country = isset( $_POST['sveaCountryCode'] ) ? $_POST['sveaCountryCode'] : "swp_not_set";
    $isCompany = isset( $_POST['sveaIsCompany'] ) ? $_POST['sveaIsCompany'] : "swp_not_set";

    // private individual
    if( $isCompany === 'false' ) {
        $response = WebPay::getAddresses()
            ->setOrderTypeInvoice()
            ->setCountryCode( $country )              
            ->setIndividual( $ssn )
            ->doRequest();
    }

    // company/organisation
    if(  $isCompany === 'true' ) {
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
    
    $_SESSION['sveaGetAddressesResponse'] = serialize( $response );
}

/**
 * store invoice address in customer address book, return zen address book id used for billing
 */
if( isset($_POST['SveaAjaxSetCustomerInvoiceAddress']) ) {
    global $db;

    // have we got an address selector (i.e. a getAddresses country)?
    if( isset($_POST['SveaAjaxAddressSelectorValue']) ) {
    
        $addressSelector = $_POST['SveaAjaxAddressSelectorValue'];

        // Include Svea php integration package files
        require('includes/modules/payment/svea_v4/Includes.php');  // use new php integration package for v4 
        $response = unserialize( $_SESSION['sveaGetAddressesResponse'] );

        // find the address corresponding to chosen addressSelector
        foreach( $response->customerIdentity as $getAddressIdentity ) {
            if( $getAddressIdentity->addressSelector === $addressSelector ) {

                // does the customer already have the invoice address in her address book?
                $sqlGetInvoiceAddressBookId =
                     "SELECT address_book_id FROM " . TABLE_ADDRESS_BOOK . " " .
                     "WHERE customers_id = '" . intval($_SESSION['customer_id']) . "' " . 
                     "AND entry_firstname = '" . $getAddressIdentity->firstName . "' " .
                     "AND entry_lastname = '" . $getAddressIdentity->lastName . "' " .
                     "AND entry_company = '" . $getAddressIdentity->fullName . "' " .
                     "AND entry_street_address = '" . $getAddressIdentity->street . "' " .
                     "AND entry_postcode = '" . strval($getAddressIdentity->zipCode) . "' " .
                     "AND entry_city = '" . $getAddressIdentity->locality . "' " .
                     "AND entry_country_id ='" . intval($_SESSION['customer_country_id']) . "'" // TODO get zen country # = ->countryCode
                ;
                $queryFactoryResult = $db->execute($sqlGetInvoiceAddressBookId);

                // invoice address not present, add it to address book for this customer
                $foundInvoiceAddress = $queryFactoryResult->recordCount();

                if( $foundInvoiceAddress == 0) {    
                    $sqlAddInvoiceAddress = array();
                    $sqlAddInvoiceAddress['customers_id'] = intval($_SESSION['customer_id']);
                    $sqlAddInvoiceAddress['entry_firstname'] = $getAddressIdentity->firstName;
                    $sqlAddInvoiceAddress['entry_lastname'] = $getAddressIdentity->lastName;
                    $sqlAddInvoiceAddress['entry_company'] = $getAddressIdentity->fullName;         // check if company first, else empty string?
                    $sqlAddInvoiceAddress['entry_street_address'] = $getAddressIdentity->street;
                    $sqlAddInvoiceAddress['entry_postcode'] = strval($getAddressIdentity->zipCode);
                    $sqlAddInvoiceAddress['entry_city'] = $getAddressIdentity->locality;
                    $sqlAddInvoiceAddress['entry_country_id'] = intval($_SESSION['customer_country_id']); // TODO get zen country # = ->countryCode

                    $sqlInsertInvoiceAddressResult = zen_db_perform(TABLE_ADDRESS_BOOK, $sqlAddInvoiceAddress); // returns true if insert succeeded

                    // needed as zen_db_perform doesn't provide insert_id
                    if( $sqlInsertInvoiceAddressResult ) {
                        $queryFactoryResult = $db->execute($sqlGetInvoiceAddressBookId);
                    }   
                }

                // get latest address book entry for this customer, use as billing address
                $invoiceAddressBookId = $queryFactoryResult->fields['address_book_id'];
                $_SESSION['billto'] = $invoiceAddressBookId;

                echo $invoiceAddressBookId;
            }
        }
    }
    
    // no addressSelector, so we respect the shipping/billing addresses for now.
    else {
        echo $_SESSION['billto'];
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