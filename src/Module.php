<?php

namespace Laminas\ApiTools\OAuth2\Doctrine\Identity;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Mvc\MvcEvent;

class Module
{
    /**
     * Provide default configuration.
     *
     * @param return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__,
                ],
            ],
        ];
    }

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $serviceManager = $e->getApplication()->getServiceManager();

        // Attach an event to replace the Identity with a DoctrineAuthenticatedIdentity
        $authenticationPostListener = $serviceManager->get(AuthenticationPostListener::class);
        $eventManager->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $authenticationPostListener,
            100
        );
    }
}
