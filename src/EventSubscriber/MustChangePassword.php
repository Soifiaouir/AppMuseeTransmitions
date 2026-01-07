<?php
// src/EventSubscriber/MustChangePassword.php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MustChangePassword implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        dump('🔵 MustChangePassword appelé');

        if (!$event->isMainRequest()) {
            dump('🔴 Pas une requête principale');
            return;
        }

        $user = $this->security->getUser();
        dump('👤 User récupéré:', $user);
        dump('👤 Classe du user:', $user ? get_class($user) : 'null');

        // ✅ CHANGEMENT ICI : Vérifier différemment
        if (!$user || !method_exists($user, 'isPasswordChange')) {
            dump('🔴 User null ou pas de méthode isPasswordChange');
            return;
        }

        dump('✅ User a bien la méthode isPasswordChange');
        dump('🔑 passwordChange =', $user->isPasswordChange());

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        dump('🛣️ Route actuelle:', $currentRoute);

        $allowedRoutes = [
            'app_change_password',
            'app_logout',
        ];

        if ($user->isPasswordChange() && !in_array($currentRoute, $allowedRoutes)) {
            dump('🚀 REDIRECTION vers change password');
            $event->setResponse(
                new RedirectResponse($this->urlGenerator->generate('app_change_password'))
            );
        } else {
            dump('❌ PAS DE REDIRECTION', [
                'passwordChange' => $user->isPasswordChange(),
                'route autorisée' => in_array($currentRoute, $allowedRoutes)
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }
}