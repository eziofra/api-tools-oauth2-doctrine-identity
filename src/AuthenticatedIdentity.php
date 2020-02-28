<?php

namespace Laminas\ApiTools\OAuth2\Doctrine\Identity;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\Permissions\Rbac\Role as RbacRole;
use Laminas\Permissions\Acl\Acl;
use Laminas\ApiTools\OAuth2\Doctrine\Identity\Exception;
use GianArb\Angry\Uninvokable;

class AuthenticatedIdentity extends RbacRole implements
    IdentityInterface
{
    use Uninvokable;

    protected $accessToken;
    protected $authorizationService;
    protected $name;

    public function __construct($accessToken, AuthorizationInterface $authorizationService, $name = 'doctrine')
    {
        $this->accessToken = $accessToken;
        $this->authorizationService = $authorizationService;
        $this->name = $name;
    }

    public function getAuthenticationIdentity()
    {
        return [
            'user' => $this->getUser(),
            'client' => $this->getClient(),
            'accessToken' => $this->getAccessToken(),
        ];
    }

    public function getAuthorizationService()
    {
        return $this->authorizationService;
    }

    public function isAuthorized($resource, $privilege)
    {
        if ($this->authorizationService instanceof Acl) {
            return $this->authorizationService->isAuthorized($this, $resource, $privilege);
        } else {
            throw new Exception('isAuthorized is for ACL only.');
        }
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getRoleId()
    {
        return $this->getName();
    }

    // For Laminas\ApiTools\OAuth2\Provider\UserId\AuthenticationService
    public function getId()
    {
        return $this->getUser()->getId();
    }

    public function getUser()
    {
        return $this->accessToken->getUser();
    }

    public function getClient()
    {
        return $this->accessToken->getClient();
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
