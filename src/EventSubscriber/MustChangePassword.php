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
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        // ✅ FIX 1: Vérifier QUE les utilisateurs qui DOIVENT changer leur mot de passe
        if (!$user instanceof User || !$user->isPasswordChange()) {
            return;
        }

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route') ?? '';

        // ✅ FIX 2: NOMS DE ROUTES EXACTS (d'après votre PasswordChangeController)
        $allowedRoutes = [
            'app_logout',
            'app_login',
            'change_password',        // ← Exactement comme dans votre controller
            'password_reset_ask',
        ];

        if (!in_array($currentRoute, $allowedRoutes, true)) {
            $event->setResponse(
                new RedirectResponse($this->urlGenerator->generate('change_password'))
            );
        }
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }
}