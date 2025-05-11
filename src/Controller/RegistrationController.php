<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\LoginAthenticatorAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\service\MailerService;
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{
    private MailerService $mailerService;
    private LoggerInterface $logger;
    private EmailVerifier $emailVerifier;
    private MailerInterface $mailer;

    public function __construct(
        EmailVerifier $emailVerifier,
        MailerService $mailerService,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->mailerService = $mailerService;
        $this->logger = $logger;
        $this->emailVerifier = $emailVerifier;
        $this->mailer = $mailer;
    }

    private function getFromAddress(): Address
    {
        return new Address('nouisseroumaima@gmail.com', 'Techora');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Encodage du mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                // Envoi email de confirmation
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('noreply@techora.com', 'Techora Mail Bot'))
                        ->to($user->getEmail())
                        ->subject('Confirmez votre email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                        ->context(['user' => $user])
                );

                // Reste sur la page d'inscription avec message
                $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez vérifier votre boîte mail.');
                return $this->redirectToRoute('app_register');

            } catch (\Exception $e) {
                $this->logger->error('Registration error: '.$e->getMessage());
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());

            $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès.');
            return $this->redirectToRoute('personne.list.alls');
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }
    }
}