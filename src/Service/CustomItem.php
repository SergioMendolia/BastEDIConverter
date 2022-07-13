<?php

namespace App\Service;

use EDI\Generator\Invoic\Item;

/**
 * Class Item
 * @package EDI\Generator\Invoic
 */
class CustomItem extends Item
{
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

    public function setDeliveryNoteNumber($deliveryNoteNumber)
    {
        $this->deliveryNoteNumber = $this->addRFFSegment('LI', $deliveryNoteNumber);

        return $this;
    }

}
