<?php

namespace App\Controller;

use App\Service\BastaXMLCleaner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class EdiConverterController extends AbstractController
{
    #[Route('/', name: 'edi_converter')]
    public function index(KernelInterface $appKernel, BastaXMLCleaner $cleaner): Response
    {

        $get = file_get_contents($appKernel->getProjectDir() . '/public/example.xml');

        $arr = $cleaner->cleanXML($get);
        dump($arr);


        //https://github.com/php-edifact/edifact-generator


        $interchange = new \EDI\Generator\Interchange('UNB-Identifier-Sender', 'UNB-Identifier-Receiver');
        $interchange->setCharset('UNOC', '3');

        $orders = new \EDI\Generator\Orders();
        $orders
            ->setOrderNumber('AB76104')
            ->setContactPerson('John Doe')
            ->setMailAddress('john.doe@company.com')
            ->setPhoneNumber('+49123456789')
            ->setDeliveryDate(new \DateTime())
            ->setDeliveryAddress(
                'Name 1',
                'Name 2',
                'Name 3',
                'Street',
                '99999',
                'city',
                'DE'
            )
            ->setDeliveryTerms('CAF');

// adding order items
        $item = new \EDI\Generator\Orders\Item();
        $item->setPosition('1', '8290123', 'EN')->setQuantity(3)->setSpecificationText('aaaaaaa');
        $orders->addItem($item);

        $item = new \EDI\Generator\Orders\Item();
        $item->setPosition('2', 'AB992233', 'EN')->setQuantity(1);
        $orders->addItem($item);

        $orders->compose();

        $encoder = new \EDI\Encoder($interchange->addMessage($orders)->getComposed(), true);
        $encoder->setUNA(":+,? '");
        print_r(nl2br($encoder->get()));
        die();
        return $this->render('edi_converter/index.html.twig', [
            'controller_name' => 'EdiConverterController',
        ]);
    }
}
