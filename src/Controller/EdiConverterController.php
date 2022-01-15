<?php

namespace App\Controller;

use App\Form\BillType;
use App\Service\BastaXMLCleaner;
use EDI\Generator\Interchange;
use EDI\Generator\Orders;
use EDI\Generator\Orders\Item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class EdiConverterController extends AbstractController
{
    #[Route('/', name: 'edi_converter')]
    public function index(Request $request, KernelInterface $appKernel, BastaXMLCleaner $cleaner): Response
    {
        $form = $this->createForm(BillType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('brochure')->getData();

            $get = file_get_contents($brochureFile);

            $arr = $cleaner->cleanXML($get);

            $edis = [];
            foreach ($arr['ROW'] as $factLine => $facture) {

                $interchange = new Interchange('UNB-Identifier-Sender', 'UNB-Identifier-Receiver');
                $interchange->setCharset('UNOC', '3');

                $tz = new \DateTimeZone('Europe/Paris');

                $dt = reset($facture['date_facture']);

                $dt = str_replace(
                    ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',],
                    ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12',],
                    $dt
                );

                $date = \DateTime::createFromFormat('d m Y', $dt, $tz);
                $orders = new Orders();
                $orders
                    ->setOrderNumber(reset($facture['numéro_de_facture']))
                    ->setContactPerson($facture['Expéditeur'])
                    ->setMailAddress('chauderon@librairiebasta.ch')
                    ->setPhoneNumber('+49123456789')
                    ->setDeliveryDate($date)
                    ->setDeliveryAddress(
                        $facture["Institution"],
                        $facture["Nom"] . ' ' . $facture["prenom"],
                        '',
                        $facture["rue"],
                        $facture["NPA"],
                        $facture["ville"],
                        'CH'
                    )
                    ->setDeliveryTerms('CAF');

                foreach ($facture['EAN'] as $key => $EAN) {

                    $convertArrays = ['QUANTITE', 'PRIX_UNITAIRE', 'TITRE', 'EAN', 'AUTEUR', 'TVA_TAUX', 'PRIX_BRUT', 'PRIX_NET'];
                    foreach ($convertArrays as $convertArray) {
                        if (is_array($facture[$convertArray][$key])) {
                            $facture[$convertArray][$key] = null;
                        }
                        $facture[$convertArray][$key] = str_replace([',', ':', '?', ';', 'Fr.', "'"], ' ', $facture[$convertArray][$key]);
                    }

                    $item = new Item();
                    $item->setPosition(($key + 1), $facture['EAN'][$key] ?? $key)
                        ->setQuantity($facture['QUANTITE'][$key])
                        ->setSpecificationText($facture['TITRE'][$key] . ' ' . $facture['AUTEUR'][$key] . ' (' . $facture['PRIX_UNITAIRE'][$key] . ')')
                        ->setAdditionalText('TVA' . $facture['TVA_TAUX'][$key])
                        ->setGrossPrice($facture['PRIX_BRUT'][$key])
                        ->setNetPrice($facture['PRIX_NET'][$key]);

                    $orders->addItem($item);

                }

                $orders->compose();

                $encoder = new \EDI\Encoder($interchange->addMessage($orders)->getComposed(), true);
                $encoder->setUNA(":+,? '");

                $edis['fact' . $factLine] = $encoder->get();

            }

            $zip = new \ZipArchive();
            $zipName = 'facture.zip';
            if ($zip->open($zipName, \ZipArchive::CREATE) === TRUE) {
                foreach ($edis as $filename => $fileContent) {
                    $zip->addFromString($filename . '.edi', $fileContent);
                }
                $zip->close();
            } else {
                die($zipName . ' failed');
            }
            return new BinaryFileResponse($zipName);
        }

        //https://github.com/php-edifact/edifact-generator

        return $this->renderForm('edi_converter/index.html.twig', [
            'form' => $form,
        ]);
    }
}
