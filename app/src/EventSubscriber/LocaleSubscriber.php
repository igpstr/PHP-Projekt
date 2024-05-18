<?php

// src/EventSubscriber/LocaleSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
private $defaultLocale;

public function __construct(string $defaultLocale = 'pl')
{
$this->defaultLocale = $defaultLocale;
}

public function onKernelRequest(RequestEvent $event)
{
$request = $event->getRequest();
$session = $request->getSession();

if (!$session->has('_locale')) {
$session->set('_locale', $this->defaultLocale);
}

$request->setLocale($session->get('_locale', $this->defaultLocale));
}

public static function getSubscribedEvents()
{
return [
KernelEvents::REQUEST => [['onKernelRequest', 20]],
];
}
}
