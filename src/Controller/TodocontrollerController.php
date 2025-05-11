<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TodocontrollerController extends AbstractController
{
    #[Route('/todo', name: 'app_todocontroller')]
    public function index(Request $request): Responsesy
    {
        $session=$request->getSession();
        //afficher notre tableau de todo
        //        // sinon je l'initialise puis l'affiche
        if(!$session->has('todo')){
            $todo = [
                'achat'=>'acheter cle usb ',
            'cours'=>'finaliser mon cours',
            'correction'=>'corriger mes examens '
            ];
            $session->set('todo', $todo);
            $this->addFlash('info', 'la liste de todo viens detre initialisé');
        }
        // si j'ai mon tab dans ma session je ne fait que l'afficher
        return $this->render('todocontroller/index.html.twig');
    }

    #[Route('/todo/add/{name}/{content}',name: 'todo.add')]
    public function addtodo(Request $request,$name,$content)
    {
        $session = $request->getSession();
        //verifier si j'ai mon tab de to do dans la session
        if ($session->has('todo')) {
        //si oui
        //si on a deja un todo avec le meme name
            //afficher erreur

            $todo=$session->get('todo');
                if(isset($todo[$name])){
                    $this->addFlash('error', "le  todo  de id $name existe deja dans la liste");

                }else          //sinon on ajoute et affiche message de succée

                {
                    $todo[$name]=$content;
                    $this->addFlash('success', "le  todo de id  $name a ete ajouté avec succé");
                    $session->set('todo',$todo);



                }
        }
    else{
        $this->addFlash('error', 'la liste des todo nest pas encore initialisé');


    }



        //sinon affiicher une erreur et rediriger vers le
        // controlleur initiale qui affiche la liste initialisé

        return $this->redirectToRoute('app_todocontroller');

    }
}
