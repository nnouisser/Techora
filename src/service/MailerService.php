<?php

namespace App\service;
// src/service/MailerService.php
namespace App\service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private ?string $replyTo = null
    ) {}

    public function sendEmail(
        string $to,
        string $content,
        string $subject,
        ?Address $from = null
    ): void {
        $email = (new Email())
            ->from($from ?? new Address('no-reply@techora.com', 'Techora'))
            ->to($to)
            ->subject($subject)
            ->html($content);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to send email: '.$e->getMessage());
        }
    }
}