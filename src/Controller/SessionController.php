<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // ✅ Bon import
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SessionController extends AbstractController
{
    #[Route('/session', name: 'app_session')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();

        if ($session->has('nbvisite')) {
            $nombrevisite = $session->get('nbvisite') + 1;
        } else {
            $nombrevisite = 1;
        }

        $session->set('nbvisite', $nombrevisite);

        return $this->render('session/index.html.twig', [
            'nbvisite' => $nombrevisite
        ]);
    }
}
