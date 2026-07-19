# Fixing CORS between the React frontend and Symfony API

## Model
GPT 5.6 Sol in ChatGPT

## Prompt
> I have a CORS issue between a React frontend and a Symfony API backend. GET requests work, but PUT and DELETE requests are blocked by the browser with a message saying that the preflight request "does not have HTTP ok status". The API responses already include `Access-Control-Allow-Origin: *`. What is causing this issue, and how can I fix it?

## Response

The problem is most likely the **preflight request**. For "non-simple" requests (`PUT`, `DELETE`, or a `POST` that sends a JSON `Content-Type`), the browser first sends a separate `OPTIONS` request to ask the server whether the real request is allowed. Only if that preflight returns a `2xx` status **and** the correct CORS headers does the browser send the actual request.

There are two common reasons for this error:

1. If the Symfony routes only allow methods such as `PUT` or `DELETE`, the router may reject the `OPTIONS` request with **405 Method Not Allowed** before a controller runs. In that case, CORS headers added by the controller are never included in the preflight response.
2. `Access-Control-Allow-Origin` alone is not enough for a preflight response. The server must also return `Access-Control-Allow-Methods` and `Access-Control-Allow-Headers` so the browser knows that the requested method and headers are permitted.

### Solution: a single event subscriber

Instead of repeating CORS logic in every controller, it can be handled in one place with a Symfony event subscriber:

```php
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
```

### Why the priority matters

The `kernel.request` listener needs a higher priority than Symfony's router so it can handle the `OPTIONS` request before routing rejects it. A priority of `250` makes `onRequest` run early enough to return an empty HTTP `200` response. The `onResponse` listener then adds the CORS headers, allowing the browser to send the real `PUT`, `DELETE`, or `POST` request.

If service autoconfiguration is enabled in `config/services.yaml`, Symfony should register the subscriber automatically. Otherwise, it must be registered as a service manually.

### Testing

After adding the file, clear the Symfony cache so the new listener priority is picked up:

```bash
php bin/console cache:clear
```

Then the `PUT` and `DELETE` requests from the frontend succeed instead of being blocked by the browser.
