<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        try {
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                $verifyEmailRouteName,
                (string)$user->getId(),
                $user->getEmail()
            );

            $context = $email->getContext();
            $context['signedUrl'] = $signatureComponents->getSignedUrl();
            $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
            $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

            $email->context($context);

            $this->logger->info("Envoi email vérification à {$user->getEmail()}", [
                'user_id' => $user->getId(),
                'email_subject' => $email->getSubject()
            ]);

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Échec envoi email vérification", [
                'error' => $e->getMessage(),
                'user' => $user->getEmail()
            ]);
            throw new \RuntimeException('Échec de l\'envoi de l\'email de vérification.');
        }
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string)$user->getId(),
            $user->getEmail()
        );

        $user->setIsVerified(true);
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
            $this->logger->info("Email confirmé avec succès", ['user_id' => $user->getId()]);
        } catch (\Exception $e) {
            $this->logger->error("Échec confirmation email", [
                'error' => $e->getMessage(),
                'user' => $user->getEmail()
            ]);
            throw $e;
        }
    }
}