<?php
namespace Svea;

/**
 *
 */
class HostedRowFormatter {

    private $totalAmount;       // order item rows, rounded to 2 decimals, multiplied by 100 to integer
    private $totalVat;          // order item rows, rounded to 2 decimals, multiplied by 100 to integer
    private $newRows;           // all order rows, as above
    private $rawAmount;         // unrounded, multiplied by 100, avoids cumulative rounding error (when summing up over rows)
    private $rawVat;            // unrounded, multiplied by 100, avoids cumulative rounding error (when summing up over rows)

    private $shippingAmount;
    private $shippingVat;

    private $discountAmount;
    private $discountVat;
    /**
     *
     */
    public function __construct() {
        $this->totalAmount = 0;
        $this->totalVat = 0;
        $this->newRows = array();
    }

    /**
     * Format rows and calculate vat
     * @param type $rows
     * @return int
     */
    public function formatRows($order) {
        $this->formatOrderRows($order);
        $this->formatShippingFeeRows($order);
        $this->formatFixedDiscountRows($order);
        $this->formatRelativeDiscountRows($order);

        return $this->newRows;  // TODO return self instead => chain functions instead of passing rows to formatTotalX() below
    }

    /**
     * formatOrderRows goes through the orderBuilder object order-, shipping & discount rows
     * and translates them to a format suitable for use by the HostedXmlBuilder.
     *
     * This includes translating all prices to integer, multiplying by 100 to remove fractions.
     * Svea employs Bankers rounding, also known as "half-to-even rounding".
     *
     * We also calculate a total amount including taxes, and the total tax amount, for the order.
     * When calculating the amounts, all rounding takes place last, in order to avoid cumulative
     * rounding errors. (See HostedPaymentTest for an example.)
     *
     * TODO implement bankers rounding
     */
    private function formatOrderRows($order) {
        foreach ($order->orderRows as $row ) {
            $tempRow = new HostedOrderRowBuilder();     // new empty object

            if (isset($row->name)) {
                $tempRow->setName($row->name);
            }

            if (isset($row->description)) {
                $tempRow->setDescription($row->description);
            }

            $rawAmount = 0.0;
            $rawVat = 0.0;
            // calculate amount, vat from two out of three given by customer, see unit tests HostedRowFormater
            if (isset($row->amountExVat) && isset($row->vatPercent)) {
                $rawAmount = floatval($row->amountExVat) *($row->vatPercent/100+1);
                $rawVat = floatval($row->amountExVat) *($row->vatPercent/100);

                $tempRow->setAmount( Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100 );
                
            } elseif (isset($row->amountIncVat) && isset($row->vatPercent)) {
                $rawAmount = $row->amountIncVat;
                $rawVat = $row->amountIncVat - ($row->amountIncVat/($row->vatPercent/100+1));
                $tempRow->setAmount( Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100 );
             
            } else {
                $rawAmount = $row->amountIncVat;
                $rawVat = ($row->amountIncVat - $row->amountExVat);
                $tempRow->setAmount( Helper::bround($rawAmount,2)*100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100);
            }

            if (isset($row->unit)) {
                $tempRow->setUnit($row->unit);
            }

            if (isset($row->articleNumber)) {
                $tempRow->setSku($row->articleNumber);
            }

            if (isset($row->quantity)) {
                $tempRow->setQuantity($row->quantity);
            }

            $this->newRows[] = $tempRow;
            $this->totalAmount += ($tempRow->amount * $row->quantity);
            $this->totalVat +=  ($tempRow->vat * $row->quantity);            
            $this->rawAmount += Helper::bround( ($rawAmount * $row->quantity) ,2) *100;
            $this->rawVat +=  Helper::bround( ($rawVat * $row->quantity) ,2) *100;           
        }
    }

    private function formatShippingFeeRows($order) {
        if (!isset($order->shippingFeeRows)) {
            return;
        }

        foreach ($order->shippingFeeRows as $row) {
            $tempRow = new HostedOrderRowBuilder();

            if (isset($row->articleNumber)) {
                $tempRow->setSku($row->articleNumber);
            }

            if (isset($row->name)) {
                $tempRow->setName($row->name);
            }

            if (isset($row->description)) {
                $tempRow->setDescription($row->description);
            }

            $rawAmount = 0.0;
            $rawVat = 0.0;
            // calculate amount, vat from two out of three given by customer, see unit tests in HostedRowFormater
            if (isset($row->amountExVat) && isset($row->vatPercent)) {
                $rawAmount = floatval($row->amountExVat) *($row->vatPercent/100+1);
                $rawVat = floatval($row->amountExVat) *($row->vatPercent/100);
                $tempRow->setAmount( Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100 );
                
            } elseif (isset($row->amountIncVat) && isset($row->vatPercent)) {
                $rawAmount = $row->amountIncVat;
                $rawVat = $row->amountIncVat - ($row->amountIncVat/($row->vatPercent/100+1));
                $tempRow->setAmount( Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100 );
             
            } else {
                $rawAmount = $row->amountIncVat;
                $rawVat = ($row->amountIncVat - $row->amountExVat);
                $tempRow->setAmount( Helper::bround($rawAmount,2)*100 );
                $tempRow->setVat( Helper::bround($rawVat,2) *100);
            }

            if (isset($row->unit)) {
                $tempRow->setUnit($row->unit);
            }

            if (isset($row->shippingId)) {
                $tempRow->setSku($row->shippingId);
            }

            $tempRow->setQuantity(1);
            $this->newRows[] = $tempRow;
            $this->shippingAmount += ($tempRow->amount );
            $this->shippingVat +=  ($tempRow->vat );

        }
    }

    public function formatFixedDiscountRows($order) {
        if (!isset($order->fixedDiscountRows)) {
            return;
        }

        foreach ($order->fixedDiscountRows as $row) {
            $tempRow = new HostedOrderRowBuilder();

            if (isset($row->name)) {
                $tempRow->setName($row->name);
            }

            if (isset($row->description)) {
                $tempRow->setDescription($row->description);
            }

            // switch on which were used of setAmountIncVat ($this->amount), setAmountExVat (->amountExVat), setVatPercent (->vatPercent)
            $rawAmount = 0.0;
            $rawVat = 0.0;
            // use old method of calculating discounts from single amount inc. vat
            if (isset($row->amount) && !isset($row->amountExVat) && !isset($row->vatPercent)) {
                $discountInPercent = ($row->amount * 100) / $this->totalAmount;   // discount as fraction of total order sum

                $rawAmount = $row->amount;
                $rawVat = $this->totalVat/100 * $discountInPercent;     // divide by 100 so that our "round and multiply" works in setVat below
                $tempRow->setAmount( - Helper::bround($rawAmount,2)*100 );
                $tempRow->setVat( - Helper::bround($rawVat,2)*100 );
            }
            // calculate amount, vat from two out of three given by customer, see unit tests in HostedPaymentTest
            elseif (isset($row->amountExVat) && isset($row->vatPercent)) {
                $rawAmount = $row->amountExVat *($row->vatPercent/100+1);
                $rawVat = $row->amountExVat *($row->vatPercent/100);
                $tempRow->setAmount( - Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( - Helper::bround($rawVat,2) *100 );
                
            } elseif (isset($row->amount) && isset($row->vatPercent)) {
                $rawAmount = $row->amount;
                $rawVat = $row->amount - ($row->amount/($row->vatPercent/100+1));
                $tempRow->setAmount( - Helper::bround($rawAmount,2) *100 );
                $tempRow->setVat( - Helper::bround($rawVat,2) *100 );
             
            } else {
                $rawAmount = $row->amount;
                $rawVat = ( $row->amount - $row->amountExVat);
                $tempRow->setAmount( - Helper::bround($rawAmount,2)*100 );
                $tempRow->setVat( - Helper::bround($rawVat,2) *100);
            }

            if (isset($row->unit)) {
                $tempRow->setUnit($row->unit);
            }

            if (isset($row->discountId)) {
                $tempRow->setSku($row->discountId);
            }

            $tempRow->setQuantity(1);


            $this->totalAmount += $tempRow->amount;
            $this->totalVat += $tempRow->vat;
            $this->newRows[] = $tempRow;

            $this->discountAmount += $tempRow->amount;
            $this->discountVat +=  $tempRow->vat;
        }
    }

    public function formatRelativeDiscountRows($order) {
        if (!isset($order->relativeDiscountRows)) {
            return;
        }

        foreach ($order->relativeDiscountRows as $row) {
            $tempRow = new HostedOrderRowBuilder();

            if (isset($row->name)) {
                $tempRow->setName($row->name);
            }

            if (isset($row->description)) {
                $tempRow->setDescription($row->description);
            }

            if (isset($row->discountId)) {
                $tempRow->setSku($row->discountId);
            }

            if (isset($row->unit)) {
                $tempRow->setUnit($row->unit);
            }

            $rawAmount = $this->rawAmount/100 * $row->discountPercent/100;    
            $rawVat = $this->rawVat/100 * $row->discountPercent/100;                
            
            $tempRow->setAmount( - Helper::bround($rawAmount,2)*100 );    
            $tempRow->setVat( - Helper::bround($rawVat,2)*100 );

            $tempRow->setQuantity(1);
            
            $this->totalAmount += $tempRow->amount;
            $this->totalVat += $tempRow->vat;
            $this->newRows[] = $tempRow;

            $this->discountAmount += $tempRow->amount;
            $this->discountVat +=  $tempRow->vat;
        }
    }

    /**
     * formatTotalAmount() is used by i.e. HostedPayment calculateRequestValues to 
     * get the total vat sum of the order.
     * 
     * @deprecated @param array $rows $rows is no longer used, instead we return 
     * the object rawAmount value, modified by shippinga and discounts
     * @return integer total order amount, including vat
     */
    public function formatTotalAmount($rows) {
        return $this->rawAmount + $this->shippingAmount + $this->discountAmount;
    }

    /**
     * formatTotalVat() is used by i.e. HostedPayment calculateRequestValues to 
     * get the total vat sum of the order.
     * 
     * @deprecated @param array $rows $rows is no longer used, instead we return 
     * the object rawAmount value, modified by shippinga and discounts
     * @return integer total amount of vat due in order 
     */
    public function formatTotalVat($rows) {
        return $this->rawVat + $this->shippingVat + $this->discountVat;
    }
}