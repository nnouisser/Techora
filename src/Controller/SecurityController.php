<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirection si déjà connecté
        if ($this->getUser()) {
            // Vérification email confirmé
            if ($this->getUser()->isVerified()) {
                return $this->redirectToRoute('app_personne_list');
            }
            return $this->redirectToRoute('app_register');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Laisser vide - géré par le firewall
    }

    #[Route('/personne/list', name: 'app_personne_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $this->addFlash('warning', 'Vous devez confirmer votre email avant d\'accéder à cette page.');
            return $this->redirectToRoute('app_register');
        }

        // Exemple de logique pour récupérer les personnes
        $personnes = $em->getRepository(YourPersonneEntity::class)->findAll();

        return $this->render('personne/list.html.twig', [
            'personnes' => $personnes
        ]);
    }

    #[Route('/verify/resend', name: 'app_verify_resend')]
    public function resendVerificationEmail(EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            return $this->redirectToRoute('app_personne_list');
        }

        // Renvoyer l'email de confirmation
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@techora.com', 'Techora'))
                ->to($user->getEmail())
                ->subject('Confirmez votre email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Un nouvel email de confirmation a été envoyé.');
        return $this->redirectToRoute('app_register');
    }
}