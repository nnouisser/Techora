<?php

namespace App\Controller;
use App\Event\AddPersonEvent;
use App\Event\ListAllPersonnesEvent;
use App\service\PdfService;

use App\Entity\Personne;
use App\Form\PersonneType;
use App\service\helpers;
use App\service\MailerService;
use App\service\UploaderService;
use Couchbase\RequestTracer;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('personne'),
IsGranted('ROLE_USER')]

final class PersonneController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private Helpers $helper,
        private EventDispatcherInterface $dispatcher
    ){}
    #[Route('/',name: 'personne.list')]
    public function index(ManagerRegistry $doctrine):Response{
        $repository = $doctrine->getRepository(Personne::class);
       $personnes=$repository->findAll();
       return $this->render('personne/index.html.twig', ['personnes'=>$personnes]);

    }
    #[Route('/pdf/{id}', name: 'personne.pdf')]
    public function generatePdfPersonne(Personne $personne = null, PdfService $pdf) {
        $html = $this->renderView('pdf/personne.html.twig', ['personne' => $personne]);
        return $pdf->showPdfFile($html);
    }



    #[Route('/alls/age/{ageMin}/{ageMax}',name: 'personne.list.age')]
    public function personneByAge(ManagerRegistry $doctrine,$ageMin,$ageMax):Response{
        $repository = $doctrine->getRepository(Personne::class);
        $personnes=$repository->findPersonnesByAgeInterval($ageMin,$ageMax);

        return $this->render('personne/index.html.twig', ['personnes'=>$personnes]);

    }

    #[Route('/stats/age/{ageMin}/{ageMax}', name: 'personne.list.age')]
    public function statsPersonnesByAge(ManagerRegistry $doctrine, $ageMin, $ageMax): Response {
        $repository = $doctrine->getRepository(Personne::class);
        $stats = $repository->statsPersonnesByAgeInterval($ageMin, $ageMax);
        return $this->render('personne/stats.html.twig', [
                'stats' => $stats[0],
                'ageMin'=> $ageMin,
                'ageMax' => $ageMax]
        );
    }





    #[
        Route('/alls/{page?1}/{nbre?12}', name: 'personne.list.alls'),
        IsGranted("ROLE_USER")
    ]
    public function indexAlls(ManagerRegistry $doctrine, $page, $nbre): Response {

       // echo $this->helper->sayCc();

//        echo ($this->helper->sayCc());
        $repository = $doctrine->getRepository(Personne::class);
        $nbPersonne = $repository->count([]);
        // 24
        $nbrePage = ceil($nbPersonne / $nbre) ;

        $personnes = $repository->findBy([], [],$nbre, ($page - 1 ) * $nbre);
        $listAllPersonneEvent=new ListAllPersonnesEvent(count($personnes));
        $this->dispatcher->dispatch($listAllPersonneEvent,ListAllPersonnesEvent::LIST_ALL_PERSONNE_EVENT);

        return $this->render('personne/index.html.twig', [
            'personnes' => $personnes,
            'isPaginated' => true,
            'nbrePage' => $nbrePage,
            'page' => $page,
            'nbre' => $nbre
        ]);
    }



    #[Route('/{id<\d+>}',name: 'personne.detail')]
    public function detail(personne $personne=null):Response{

        if(!$personne){
            $this->addFlash("error","Personne n'existe pas");
            return $this->redirectToRoute('personne.list');

        }

        return $this->render('personne/detail.html.twig', ['personne'=>$personne]);

    }


    #[Route('/edit/{id?0}', name: 'personne.edit')]
    public function addPersonne(
        Personne $personne=null,
        ManagerRegistry $doctrine,
        Request $request,
        UploaderService $uploaderService,
        MailerService $mailer
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $new=false;
        $manager = $doctrine->getManager();
        if(!$personne){
            $new=true;
            $personne=new Personne();

        }

        //$personne est l'image de notre formulaire
        $form=$this->createForm(PersonneType::class,$personne);
        $form->remove('createdAt');
        $form->remove('updatedAt');
        $form->handleRequest($request);

        //formullaire va ete traiter la requete
        // cas1:est ce que formulaire a ete soumis :
        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photo) {
                $directory=$this->getParameter('kernel.project_dir').'/public/upload/personnes';


                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $personne->setImage($uploaderService->uploadFile($photo, $directory));
            }
            if($new){
                $message="a été ajouté avec succés";
                $personne->setCreatedBy($this->getUser());
            }
            else{
                $message="a  été mis a jour avec succés";

            }
            $manager = $doctrine->getManager();
            $manager->persist($personne);
            $manager->flush();
            if($new){
                //j'ai creeer un event
                $addPersonEvent=new AddPersonEvent($personne);
                //je vais despatcher cette event
                $this->logger->debug("Dispatching AddPersonEvent...");
                $this->dispatcher->dispatch($addPersonEvent, AddPersonEvent::ADD_PERSONNE_EVENT);


            }

            $this->addFlash('success', $personne->getName() . $message);

            return $this->redirectToRoute('personne.list.alls');
        }else{
            //sinon : afiche le formulaire
            return $this->render('personne/add-personne.html.twig', [
                'form'=>$form->createView(),
            ]);

        }




    }
    #[Route('/delete/{id}', name: 'personne.delete'),
    IsGranted('ROLE_ADMIN')]
    public function deletePersonne(Personne $personne=null,ManagerRegistry $doctrine): RedirectResponse{
        //recuperer la personne
        //si la personne existe on va le supprimer et retourner flash message de succes
        if($personne){
            $manager=$doctrine->getManager();
            //ajoute la fonction de supprission dans la transaction
            $manager->remove($personne);
            //executer la transaction
            $manager->flush();
            $this->addFlash('success', "La personne a été supprimé avec succés");

        }            // sinon retourner flash message d'erreur
        else{
            $this->addFlash('error', "Personne innexistante");
        }
         return $this->redirectToRoute('personne.list.alls');

    }

    #[Route('/update/{id}/{name}/{firstname}/{age}', name: 'personne.update')]
    public function updatePersonne(Personne $personne=null,ManagerRegistry $doctrine,$name,$firstname,$age){
        //verifie que la personne existe
        //si  la personne existe on va mettre a jour  les parametres et afficher msg succeess
        if($personne){
            $personne->setName($name);
            $personne->setFirstname($firstname);
            $personne->setAge($age);
            $manager=$doctrine->getManager();
            $manager->persist($personne);
            $manager->flush();
            $this->addFlash('success', "La personne a mise a jour   avec succés");


        }  else {
            //Sinon  retourner un flashMessage d'erreur
            $this->addFlash('error', "Personne innexistante");
        }
        return $this->redirectToRoute('personne.list.alls');
    }
}