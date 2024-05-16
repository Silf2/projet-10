<?php 

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class RedirectListener
{
    private $security;
    private $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $user = $this->security->getUser();
        $route = $request->attributes->get('_route');

        // Si l'utilisateur est connecté et qu'il accède à la page de connexion, redirection vers la page d'accueil des utilisateurs connectés
        if ($user && $request->attributes->get('_route') === 'app_home') {
            $response = new RedirectResponse($this->urlGenerator->generate('app_allProjects'));
            $event->setResponse($response);
        }

        // Liste des routes accessibles aux utilisateurs non connectés
        $allowedRoutes = ['app_home', 'app_login', 'app_register'];

        // Si l'utilisateur essaie d'accéder à une route non autorisée, rediriger vers la page d'accueil
        if (!$user && !in_array($route, $allowedRoutes)) {
            $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            $event->setResponse($response);
        }
    }
}