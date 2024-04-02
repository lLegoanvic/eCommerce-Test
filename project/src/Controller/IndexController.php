<?php

namespace App\Controller;

use phpDocumentor\Reflection\Types\Void_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;


class IndexController extends AbstractController
{
    #[Route ('/', name: 'index')]
    public function indexredirect(Environment $twig): Response
    {

        $randomInt = random_int(0,500);


        // check if user is connected ( ROLE_USER = user co )
        if(!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }
        $connectedUser = $this->getUser();
        // twig render(fichier, ['nomVarDansLeTwig' =>'sa valeur']
        return new Response($twig->render('index.html.twig', ['user' => $connectedUser]));

    }
}