<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 250 runs before Symfony's router (priority 32), so we can
            // answer the OPTIONS preflight before routing rejects it as a 405.
            KernelEvents::REQUEST => ['onRequest', 250],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    // Answer the browser's preflight OPTIONS request before it reaches a controller.
    public function onRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->getMethod() === 'OPTIONS') {
            $event->setResponse(new Response());
        }
    }

    // Add the CORS headers to every response.
    public function onResponse(ResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }
}
