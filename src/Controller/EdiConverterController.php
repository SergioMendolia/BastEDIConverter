<?php

namespace App\Controller;

use App\Form\BillType;
use App\Service\BastaXMLCleaner;
use EDI\Generator\Interchange;
use EDI\Generator\Orders;
use EDI\Generator\Orders\Item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class EdiConverterController extends AbstractController
{
    #[Route('/', name: 'edi_converter')]
    public function index(Request $request, KernelInterface $appKernel, BastaXMLCleaner $cleaner, ParameterBagInterface $parameterBag): Response
    {
        $form = $this->createForm(BillType::class);
        $form->handleRequest($request);
        $facturesDir = $parameterBag->get('kernel.project_dir') . '/factures/';

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $brochureFile */
            $brochureFile = $form->get('brochure')->getData();

            $get = file_get_contents($brochureFile);

            $arr = $cleaner->cleanXML($get);

            $edis = [];
            $facture = $arr['ROW'];

                $interchange = new Interchange('UNB-Identifier-Sender', 'UNB-Identifier-Receiver');
                $interchange->setCharset('UNOC', '3');

                $tz = new \DateTimeZone('Europe/Paris');

            $dt = $facture['date_facture'];

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
                        ->setAdditionalText('TVA' . $facture['TVA_TAUX'][$key] . '-No compte:' . $facture['NO_CPTE_BCU'][$key])
                        ->setGrossPrice($facture['PRIX_BRUT'][$key])
                        ->setNetPrice($facture['PRIX_NET'][$key]);

                    $orders->addItem($item);

                }

            $orders->compose();

            $encoder = new \EDI\Encoder($interchange->addMessage($orders)->getComposed(), true);
            $encoder->setUNA(":+,? '");

            $edis['fact'] = $encoder->get();

            foreach ($edis as $filename => $fileContent) {
                file_put_contents($facturesDir . date('Y-m-d_H.i.s') . '-' . $filename . '.edi', $fileContent);
            }

            $this->addFlash('success', "C'est dans la boîte!");

            return $this->redirectToRoute('edi_converter');
        }

        $existingBills = [];
        $previousBills = [];
        $finder = new Finder();
        $finder->files()->in($facturesDir);

        $finder->files()->name('*.edi');
        foreach ($finder as $file) {
            $existingBills[$file->getFilename()] = $file->getRealPath();

        }
        $finder->files()->name('*.handled');
        foreach ($finder as $file) {
            $previousBills[$file->getFilename()] = $file->getRealPath();
        }

        //https://github.com/php-edifact/edifact-generator

        return $this->renderForm('edi_converter/index.html.twig', [
            'form' => $form,
            'existing_bills' => $existingBills,
            'previous_bills' => $previousBills,
        ]);
    }

    #[Route('/supprimer/{file}', name: 'remove_file')]
    public function remove(Request $request, string $file, ParameterBagInterface $parameterBag): Response
    {
        $facturesDir = $parameterBag->get('kernel.project_dir') . '/factures/';

        $finder = new Finder();
        $finder->files()->in($facturesDir);

        $finder->files()->name($file);
        if ($finder->count() > 1) {
            return $this->redirectToRoute('edi_converter');
        }

        foreach ($finder as $found) {
            unlink($found->getPathname());
        }

        return $this->redirectToRoute('edi_converter');
    }
}
