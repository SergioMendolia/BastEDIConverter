<?php

namespace App\Service;


use EDI\Generator\EdiFactNumber;

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
        $this->invoiceDate = $this->addDTMSegment($invoiceDate, '137');
        return $this;
    }

    public static function addMOASegment($qualifier, $value)
    {
        return [
            'MOA',
            [
                (string)$qualifier,
                EdiFactNumber::convert($value),
            ],
        ];
    }

}
