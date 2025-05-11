<?php

namespace App\Controller;

use App\service\MailerService;
use http\Env\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FirstcontrillerController extends AbstractController
{
    public function __construct(private readonly MailerService $mailerService)
    {
    }

    #[Route('/firstcontriller', name: 'app_firstcontriller')]
    public function index(): Response
    {
        //chercher au bbd vos users
        return $this->render('firstcontriller/index.html.twig',[
            'name' => 'NSR',
            'FirstName'=>'Ouma',
            'path'=>'    '
        ]);
    }
    #[Route('/template', name: 'template')]
    public function template(): Response
    {
        //chercher au bbd vos users
        return $this->render('template.html.twig');
    }






    //#[Route('/sayhello/{name}/{FirstName}', name: 'say.hello')]
    public function sayhello(\Symfony\Component\HttpFoundation\Request $request,$name,$FirstName): Response
    {

       return $this->render('firstcontriller/hello.html.twig',
           ['nom'=>$name,
               'prenom'=>$FirstName]);
    }
}
