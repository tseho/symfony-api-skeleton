<?php

namespace App\Infrastructure\Security\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTEventSubscriber implements EventSubscriberInterface
{
    private int $ttl;

    public function __construct(int $ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data['token'])) {
            throw new \LogicException('Expected a token in AuthenticationSuccessEvent data');
        }

        $event->setData([
            'access_token' => $data['token'],
            'expires_in' => $this->ttl,
            'token_type' => 'Bearer',
        ]);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();
        $previousResponse = $event->getResponse();

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $event->setResponse(
            new JsonResponse([
                'error' => 'authentication',
                'error_description' => $message,
            ], $previousResponse->getStatusCode())
        );
    }
}
