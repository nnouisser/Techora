<?php

namespace App\EventListener;

use App\Event\AddPersonEvent;
use App\Event\ListAllPersonnesEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class PersonneListener
{
    public function __construct(private LoggerInterface $logger) {
    }
    public function onPersonneAdd(AddPersonEvent $event) {
        $this->logger->debug("cc je suis entrain d'écouter l'evenement personne.add et une personne vient d'être ajoutée et c'est " . $event->getPersonne()->getName());
    }

    public function onListAllPersonnes(ListAllPersonnesEvent   $event){
        $this->logger->debug("nombre de personnes dans bbd est " . $event->getNbPersonne());
    }public function onListAllPersonnes2(ListAllPersonnesEvent   $event){
        $this->logger->debug("le second listener avec le nombre "  . $event->getNbPersonne());
    }
    public function logKernelRequest(KernelEvent $event){
        dd($event->getRequest());
    }



}