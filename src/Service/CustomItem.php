<?php

namespace App\Service;

use EDI\Generator\EdiFactNumber;
use EDI\Generator\Invoic\Item;

/**
 * Class Item
 * @package EDI\Generator\Invoic
 */
class CustomItem extends Item
{

    protected $netPrice2;

    /**
     * @param string $grossPrice
     * @return Item
     */
    public function setGrossPrice($grossPrice)
    {
        $this->grossPrice = self::addPRISegment('AAB', $grossPrice);
        $this->addKeyToCompose('grossPrice');
        return $this;
    }


    /**
     * @param string $netPrice
     * @return Item
     */
    public function setNetPrice($netPrice)
    {
        $this->netPrice = self::addPRISegment('AAA', $netPrice);
        $this->addKeyToCompose('netPrice');
        return $this;
    }

    /**
     * @param string $netPrice
     * @return Item
     */
    public function setMOANetPrice($netPrice)
    {
        $this->netPrice2 = self::addMOASegment('203', $netPrice);
        $this->addKeyToCompose('netPrice2');
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

    public function setDeliveryNoteNumber($deliveryNoteNumber)
    {
        $this->deliveryNoteNumber = $this->addRFFSegment('LI', $deliveryNoteNumber);

        return $this;
    }

}
