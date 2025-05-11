<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TabcontrollerController extends AbstractController
{
    #[Route('/tab/{nb<\d+>?5}', name: 'app_tabcontroller')]
    public function index($nb): Response
    {
        $notes=[];
        for($i=0;$i<$nb;$i++){
            $notes[]=rand(0,20);
        }
        return $this->render('tabcontroller/index.html.twig', [
            'notes' => $notes,
        ]);
    }
    #[Route('/tab/users', name: 'tab.users')]
    public function users(): Response{
        $users=[
            ['firstname'=>'oumaima','name'=>'nouisser','age'=>25],
            ['firstname'=>'aicha','name'=>'nouisser','age'=>28],
            ['firstname'=>'awatef','name'=>'brahim','age'=>60]
        ];
        return $this->render('tab/users.html.twig',[
            'users' => $users
        ]);


    }
}
