<?php

namespace App\service;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;


class helpers

{
    private $langue;
    public function __construct(private LoggerInterface $logger,private Security $security) {
    }
    public function sayCc(): string {
        $this->logger->info('Je dis cc');
        return 'cc';
    }

    public function getUser():User
    {

        if($this->security->isGranted('ROLE_ADMIN')){

            $user=$this->security->getUser();
            if($user instanceof User){
                return $user;
            }


        }

    }
}