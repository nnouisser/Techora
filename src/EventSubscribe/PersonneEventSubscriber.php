<?php

namespace App\EventSubscribe;

use App\Event\AddPersonEvent;
use App\service\MailerService;
use Doctrine\Common\EventSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonneEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerService $mailer,
        private  LoggerInterface $logger

    ){

    }


    public static function getSubscribedEvents():array
    {
        return [AddPersonEvent::ADD_PERSONNE_EVENT => ["onAddPersonneEvent" ,3000]
        ];
    }
    public function onAddPersonneEvent(AddPersonEvent $event) {
        $personne = $event->getPersonne();
        $mailMessage = $personne->getFirstname().' '.$personne->getName()." a été ajouté avec succès";
        $this->logger->info("Envoi d'email pour ".$personne->getFirstname().' '.$personne->getName());
        $this->mailer->sendEmail(content: $mailMessage, subject: 'Mail sent from EventSubscriber');
    }



}