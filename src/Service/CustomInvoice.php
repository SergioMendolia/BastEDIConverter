<?php

namespace App\Service;


/**
 * Class Invoic
 * @url http://www.unece.org/trade/untdid/d96b/trmd/invoic_s.htm
 * @package EDI\Generator
 */
class CustomInvoice extends \EDI\Generator\Invoic
{

    /**
     * @param string $invoiceDate
     * @return CustomInvoice
     */
    public function setCustomDate($invoiceDate)
    {
        $this->invoiceDate = $this->addDTMSegment($invoiceDate, '3');
        return $this;
    }


}
