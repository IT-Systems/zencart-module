<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class svea_product_price extends base {
    function __construct()
    {
        global $zco_notifier;
        $zco_notifier->attach( $this, array('NOTIFY_MAIN_TEMPLATE_VARS_START_PRODUCT_INFO'));//event to display product

    }

    function update(&$class, $eventID, $paramsArray = array() )
    {   global $db,$currencies;
            //logged in customer, will see campaigns for his country
            if(isset($_SESSION['customer_country_id'])){
                 $svea_countryInfo = zen_get_countries_with_iso_codes( $_SESSION['customer_country_id'] );
                 $svea_country_code = $svea_countryInfo['countries_iso_code_2'];
            //not logged in customer, will se campaigns for the store country
            }else{
                $q = "SELECT `countries_iso_code_2` FROM `countries` WHERE `countries_id` = ".STORE_COUNTRY." LIMIT 1";
                $svea_countryInfo = $db->Execute($q);
                $svea_country_code = $svea_countryInfo->fields['countries_iso_code_2'];
            }
        if(MODULE_PAYMENT_SWPPARTPAY_PRODUCT !== "True" && MODULE_PAYMENT_SWPINVOICE_PRODUCT !== "True" || $svea_countryInfo == "NL" || $svea_country_code == "DE"){
              return;
          }

               //get product price
            $svea_currencyValue = $currencies->get_value($_SESSION['currency']);
            $svea_base_price = zen_get_products_base_price((int)$_GET['products_id']);
            $svea_price = $svea_currencyValue * $svea_base_price;
            $currency_decimals = $_SESSION['currency'] == 'EUR' ? 1 : 0;
            $price_list = array();
            $prices = array();
        //payment plan
        if(MODULE_PAYMENT_SWPPARTPAY_PRODUCT === "True"){

            $query = "SELECT `campaignCode`,`description`,`paymentPlanType`,`contractLengthInMonths`,
                            `monthlyAnnuityFactor`,`initialFee`, `notificationFee`,`interestRatePercent`,
                            `numberOfInterestFreeMonths`,`numberOfPaymentFreeMonths`,`fromAmount`,`toAmount`
                        FROM `svea_params_table`
                        WHERE `timestamp`=(SELECT MAX(timestamp) FROM `svea_params_table` WHERE `countryCode` = '".$svea_country_code."' )
                        AND `countryCode` =  '".$svea_country_code."'
                        ORDER BY `monthlyAnnuityFactor` ASC";
            $svea_result = $db->Execute($query);

            if ($svea_result->RecordCount() > 0) {
                $price_list[] = '<h4 style="display:block;  list-style-position:outside; margin: 5px 10px 10px 10px">'.ENTRY_TEXT_SWPPARTPAY.'</h4>';
              while (!$svea_result->EOF) {
                if($svea_base_price >= $svea_result->fields['fromAmount'] && $svea_base_price <= $svea_result->fields['toAmount']){
                  $price = $svea_currencyValue * ($svea_base_price * $svea_result->fields['monthlyAnnuityFactor'] + $svea_result->fields['notificationFee']);

                $price_list[] = '<div class="svea_product_price_item" style="display:block;  list-style-position:outside; margin: 5px 10px 10px 10px">'.
                        "<div style='float:left;'>".
                            $svea_result->fields['description'] .
                       "</div>
                        <div style='color: #002A46;
                                    width:90%;
                                    margin-left: 80px;
                                    margin-right: auto;
                                    float:left;'>
                            <strong >".
                               round($price,$currency_decimals)." ".$_SESSION['currency'].
                                "/".ENTRY_TEXT_MONTH.
                            "</strong>
                        </div>
                    </div>";
                     $prices[] = $price;
                }
                    $svea_result->MoveNext();

              }
            }


        }
        //invoice
           if(MODULE_PAYMENT_SWPINVOICE_PRODUCT === "True" && $svea_base_price >= constant(MODULE_PAYMENT_SWPINVOICE_PRODUCT_.$svea_country_code) && $svea_country_code != "DK"){
            $lowest_to_pay = $this->svea_get_invoice_lowest($svea_country_code);
            $price_list[] = '<h4 style="display:block;  list-style-position:outside; margin: 5px 10px 10px 10px">'.ENTRY_TEXT_SWPINVOICE.'</h4>';

            //Based on condition to pay 3% of amount or 100
            $price = ($svea_currencyValue * $svea_base_price) * 0.03 < $lowest_to_pay ? $lowest_to_pay : ($svea_currencyValue * $svea_base_price) * 0.03;

            $price_list[] = '<div class="svea_product_price_item" style="display:block; margin: 5px 10px 10px 10px">'.
                        "<div style='float:left;'>".
                            ENTRY_TEXT_SWPINVOICE_DESCRIPTION.
                       "</div>
                        <div style='color: #002A46;
                                    width:90%;
                                    margin-left: 80px;
                                    margin-right: auto;
                                    float:left;'>
                            <strong >".
                               round($price,$currency_decimals)." ".$_SESSION['currency'].
                            "</strong>
                        </div>
                    </div>";
             $prices[] = $price;
        }
        //lowest price
        if(sizeof($prices) > 0){
            $lowest_price =  ENTRY_TEXT_FROM." ".round(min($prices),$currency_decimals)." ".$_SESSION['currency']. "/" . ENTRY_TEXT_MONTH;

            $this->sveaShowHtml($price_list,$lowest_price);
        }
    }

    public function sveaShowHtml($price_list, $lowest_price) {
         //jquery
        echo'<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>';
        echo
'<script language="javascript" type="text/javascript"><!--

            jQuery(document).ready(function () {

                jQuery("#productPrices").after(jQuery("#svea_price_box"));
                jQuery("#svea_price_arrow").hover(function (){
                     jQuery(this).css({"cursor" : "pointer"});
                });
                jQuery("#svea_product_price_all").click(function (){
                     jQuery("#svea_product_price_all").slideUp();
                });
                jQuery("#svea_price_arrow").toggle(
                    function (){
                         jQuery("#svea_product_price_all").slideDown();
                         jQuery(this).css({"cursor" : "pointer"});
                   },
                    function(){
                         jQuery("#svea_product_price_all").slideUp();
                   });

               });


//--></script>';

        $line = "<img width='163' height='1' src='images/Svea/grey_line.png' />";
        $arrow = "<img src='images/Svea/blue_arrow.png' />";

        echo '<div id="svea_price_box"
                style=" width: 53%;
                height:50px;
                float:left;
                margin:5px 5px;">
                    <div style="position:relative; z-index:1">';

            echo '      <div id="svea_product_price_lowest"
                            style="display:block;">';
                    echo "<img width='170' src='images/Svea/svea_background.png'
                        style=' position:absolute;
                        z-index:1;' />";

               echo     '</div>

                    <div id="svea_price_arrow">
                        <div id="svea_arrow" style="
                          width:auto;
                           position:absolute;
                            z-index:2;
                            left: -5px;
                            top:26px;
                          margin: 7px -10px 3px 17px;
                          ">'.$arrow.'
                        </div>
                      <div style="
                       position:absolute;
                        z-index:2;
                        left:29px;
                        top:26px;
                       color: #002A46;
                       width:auto;
                       padding: 3px;
                       margin-left: auto;
                       margin-right: auto;">'.$lowest_price.'
                      </div>
                   </div>';

            echo '<div id="svea_product_price_all"
                    style="
                        display:none;
                        width: 100%;
                        max-width: 206px;
                        padding: 5px;
                        box-shadow: inset 10px 10px 10px -11px #d2d2d2;
                        border-radius: 4px 4px 4px 4px;
                        -moz-border-radius: 4px 4px 4px 4px;
                        -webkit-border-radius: 4px 4px 4px 4px;
                        background-color: #ededed;
                        border: 0.5px solid #bdbdbd;
                        z-index: 10;
                        position: absolute;
                        top:50px;
                        padding: 3px 3px 0px 0px;
                    ">';
                       foreach ($price_list as $value) {
                            echo $value;
                            echo '<div style="
                                        width:90%;
                                        margin-left: auto;
                                        margin-right: auto;">'.$line.
                                    '</div>';
                       }
                echo '</div>
                </div>';
        echo '</div>';

    }

    private function svea_get_invoice_lowest($svea_country_code) {
        switch ($svea_country_code) {
            case "SE":
                return 100;
                break;
            case "NO":
                return 100;
                break;
            case "FI":
                return 10;
                break;
            case "DK":
                return 100;
                break;
            /** not yew available
            case "NL":
                return 100;
                break;
            case "DE":
                return 100;
                break;
             *
             */

            default:
                break;
        }
    }



}

?>
