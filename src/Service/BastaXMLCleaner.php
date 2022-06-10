<?php

namespace App\Service;

class BastaXMLCleaner
{

    public function cleanXML($xmlstring)
    {
        $xmlstring = str_replace('REMISE_%', 'REMISE_PC', $xmlstring);
        $xmlstring = str_replace("n°_de_bon", 'no_de_bon', $xmlstring);
        $xmlstring = str_replace("V.REF>", 'VREF>', $xmlstring);
        $xmlstring = str_replace("prénom>", 'prenom>', $xmlstring);
        $xmlstring = str_replace("remarques,_réponses>", 'remarques_réponses>', $xmlstring);
        $xmlstring = str_replace("frais_d'envoi>", 'frais_denvoi>', $xmlstring);
        $arr = simplexml_load_string($xmlstring);
        $arr = json_encode($arr);
        $arr = json_decode($arr, true);
        $arr = self::removeDataKeys($arr);
        $arr = self::explodeAttributes($arr);


        return $arr;
    }

    public static function removeDataKeys($parentArray)
    {
        foreach ($parentArray as $key => $array) {
            if (is_array($array)) {
                if (count($array) === 1 && array_key_exists('DATA', $array)) {
                    $parentArray[$key] = $array['DATA'];
                } else {
                    $parentArray[$key] = self::removeDataKeys($array);
                }
            }
        }
        return $parentArray;
    }

    public static function explodeAttributes($parentArray)
    {
        foreach ($parentArray as $key => $array) {
            if ($key === "@attributes") {
                foreach ($array as $attrKey => $item) {
                    $parentArray[$attrKey] = $item;
                }
                unset($parentArray[$key]);
            } elseif (is_array($array)) {
                $parentArray[$key] = self::explodeAttributes($array);
            }
        }
        return $parentArray;
    }
}

