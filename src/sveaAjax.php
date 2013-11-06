<?php
//
require('includes/application_top.php');

require_once(DIR_FS_CATALOG . 'svea/Includes.php');
require_once(DIR_FS_CATALOG . 'sveawebpay_config.php');                  // sveaConfig implementation

/**
 *  get iso 3166 customerCountry from zencart customer settings
 */
if( isset($_POST['SveaAjaxGetCustomerCountry']) ) {

    $country = zen_get_countries_with_iso_codes( $_SESSION['customer_country_id'] );
    echo $country['countries_iso_code_2'];
}

/**
 * perform getPaymentPlanParams, paymentPlanPricePerMonth, return dropdown widget
 */
if( isset($_POST['SveaAjaxGetPartPaymentOptions']) ) {

    $price = isset( $_SESSION['sveaAjaxOrderTotal'] ) ? $_SESSION['sveaAjaxOrderTotal'] : "swp_not_set";
    $country = isset( $_SESSION['sveaAjaxCountryCode'] ) ? $_SESSION['sveaAjaxCountryCode'] : "swp_not_set";

    sveaAjaxGetPartPaymentOptions( $price, $country );
    exit();
}

function sveaAjaxGetPartPaymentOptions( $price, $country ) {

    $sveaConfig = (MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test' ) ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

    $plansResponse = WebPay::getPaymentPlanParams( $sveaConfig )->setCountryCode($country)->doRequest();

    // TODO change to use zencart error message stack instead, or svea errors?
    // error?
    if( $plansResponse->accepted == false) {
        echo( sprintf('<div><input type="radio" id="address_0" value="swp_not_set">%s</div>', $plansResponse->errormessage) );
    }
    // if not, show addresses and store response in session
    else {
       $priceResponse = WebPay::paymentPlanPricePerMonth( $price, $plansResponse );
            foreach( $priceResponse->values as $cc) {
                echo sprintf( '<div><input type="radio" name="sveaPaymentOptionsPP" value="%s">%s (%.2f)</div>', $cc['campaignCode'], $cc['description'], $cc['pricePerMonth'] );

        }
    }
}

/**
 * Present the banks for the user in a friendly fashion, so that we can go directly there instead of landing on paypage
 */
if( isset($_POST['SveaAjaxGetBankPaymentOptions']) ) {
    $country = isset( $_SESSION['sveaAjaxCountryCode'] ) ? $_SESSION['sveaAjaxCountryCode'] : "swp_not_set";

    sveaAjaxGetBankPaymentOptions( $country );
    exit();
}

function sveaAjaxGetBankPaymentOptions( $country ) {

    $sveaConfig = (MODULE_PAYMENT_SWPINTERNETBANK_MODE === 'Test') ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

    $banksResponse = WebPay::getPaymentMethods( $sveaConfig )->setContryCode( $country )->doRequest();

    if( sizeof( $banksResponse ) == 0 ) {
        return "NO APPLICABLE BANKS FOR THIS PAYMENT METHOD"; //TODO fail gracefully
    }
    else {
        $logosPath = "images/logos/";
        $counter = 0;
        foreach( $banksResponse as $bank) {
            if( preg_match( "/^DB/", substr( $bank,0,2 ) ) === 1 ) { // bank payment methods all start with "DB"
                echo sprintf( '<input type="radio" name="BankPaymentOptions" id="%s" value="%s" %s/>', $bank, $bank, $counter++==0 ? "checked=true" : "" ); //selects 1st bank
                echo sprintf( '<label for="%s"> <img src="%s%s.png" alt="bank %s" /> </label>', $bank, $logosPath, $bank, $bank);
            }
        }
    }
}

/**
 *  perform getAddresses() via php integration package, return dropdown html widget
 */
if( isset($_POST['SveaAjaxGetAddresses']) ) {

    $ssn = isset( $_POST['sveaSSN'] ) ? $_POST['sveaSSN'] : "swp_not_set";
    $country = isset( $_POST['sveaCountryCode'] ) ? $_POST['sveaCountryCode'] : "swp_not_set";
    $isCompany = isset( $_POST['sveaIsCompany'] ) ? $_POST['sveaIsCompany'] : "swp_not_set";
    $paymentType = isset( $_POST['paymentType'] ) ? $_POST['paymentType'] : "swp_not_set";

    sveaAjaxGetAddresses($ssn, $country, $isCompany, $paymentType );
    exit();
}

function sveaAjaxGetAddresses( $ssn, $country, $isCompany, $paymentType ) {

    $sveaConfig = (MODULE_PAYMENT_SWPINVOICE_MODE === 'Test' ||
                   MODULE_PAYMENT_SWPPARTPAY_MODE === 'Test' ) ? new ZenCartSveaConfigTest() : new ZenCartSveaConfigProd();

    $response = WebPay::getAddresses( $sveaConfig );
    // private individual
    if(  $isCompany === 'true' ) {
        $response = $response->setCompany( $ssn );
    }
    if( $isCompany === 'false' ) {
        $response = $response->setIndividual( $ssn );
    }
    // paymenttype
    switch( strtoupper($paymentType) ) {
        case "INVOICE":
            $response = $response->setOrderTypeInvoice();
            break;
        case "PAYMENTPLAN":
            $response = $response->setOrderTypePaymentPlan();
            break;
    }
    $response = $response->setCountryCode( $country )
                    ->setIndividual( $ssn )
                    ->doRequest();

    // TODO change to use zencart error message stack instead, or svea errors?
    // error?
    if( $response->accepted == false) {
        echo( sprintf('<option id="address_0" value="swp_not_set">%s</option>', $response->errormessage) );
    }
    // if not, show addresses and store response in session
    else {
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
}

/**
 * store invoice address in customer address book, return zen address book id used for billing
 */
if( isset($_POST['SveaAjaxSetCustomerInvoiceAddress']) ) {
    sveaAjaxSetCustomerInvoiceAddress();
    exit;
}

function sveaAjaxSetCustomerInvoiceAddress() {
    global $db;

    // have we got an address selector (i.e. a getAddresses country)?
    if( isset($_POST['SveaAjaxAddressSelectorValue']) ) {

        $addressSelector = $_POST['SveaAjaxAddressSelectorValue'];

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

?>