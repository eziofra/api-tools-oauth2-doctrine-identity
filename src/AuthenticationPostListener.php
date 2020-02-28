<?php

namespace Laminas\ApiTools\OAuth2\Doctrine\Identity;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity as MvcAuthAuthenticatedIdentity;
use Laminas\ApiTools\OAuth2\Doctrine\Identity\AuthenticatedIdentity as DoctrineAuthenticatedIdentity;
use Laminas\ApiTools\OAuth2\Doctrine\Identity\AccessTokenException;
use GianArb\Angry\Unclonable;
use GianArb\Angry\Unserializable;

class AuthenticationPostListener
{
    use Unclonable;
    use Unserializable;

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // Replace an Authenticated Identity with a Doctrine enabled identity
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $identity = $mvcAuthEvent->getAuthenticationService()->getIdentity();
        if (! $identity instanceof MvcAuthAuthenticatedIdentity) {
            return;
        }

        $accessToken = $this->findAccessToken($identity->getAuthenticationIdentity());
        if (! $accessToken) {
            throw new AccessTokenException('Access Token expected for authenticated identity not found');
        }

        $doctrineAuthenticatedIdentity = new DoctrineAuthenticatedIdentity(
            $accessToken,
            $mvcAuthEvent->getAuthorizationService()
        );
        $mvcAuthEvent->getMvcEvent()->setParam('Laminas\ApiTools\MvcAuth\Identity', $doctrineAuthenticatedIdentity);
        $mvcAuthEvent->setIdentity($doctrineAuthenticatedIdentity);
    }

    // Search each OAuth2 configuration for a matching clientId and access_token
    private function findAccessToken(array $identity)
    {
        $config = $this->container->get('config');

        foreach ($config['api-tools-oauth2-doctrine'] as $oauth2Config) {
            if (array_key_exists('object_manager', $oauth2Config)) {
                $objectManager = $this->container->get($oauth2Config['object_manager']);
                $accessTokenRepository = $objectManager
                    ->getRepository($oauth2Config['mapping']['AccessToken']['entity']);

                $accessToken = $accessTokenRepository->findOneBy([
                    $oauth2Config['mapping']['AccessToken']['mapping']['access_token']['name']
                    => array_key_exists('access_token', $identity) ? $identity['access_token'] : $identity['id'],
                ]);

                if ($accessToken) {
                    if ($accessToken->getClient()->getClientId() == $identity['client_id']) {
                        // Match found
                        return $accessToken;
                    }
                }
            }
        }
    }
}
