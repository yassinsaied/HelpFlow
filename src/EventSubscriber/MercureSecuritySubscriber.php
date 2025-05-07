<?php
namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;


#[Autoconfigure(tags: ['kernel.event_subscriber'])]
class MercureSecuritySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['validateMercureRequest', 128],
        ];
    }

    public function validateMercureRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        
        if (!str_contains($request->getPathInfo(), '.well-known/mercure')) {
            return;
        }

        if (!$request->headers->has('Authorization')) {
            throw new AccessDeniedHttpException('Token JWT requis');
        }
    }
}