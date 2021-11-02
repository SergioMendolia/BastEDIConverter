<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EdiConverterController extends AbstractController
{
    #[Route('/', name: 'edi_converter')]
    public function index(): Response
    {
        return $this->render('edi_converter/index.html.twig', [
            'controller_name' => 'EdiConverterController',
        ]);
    }
}
