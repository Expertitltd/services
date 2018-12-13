<?php

namespace Skalar\Auth;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;


/**
 * Class SocialAuth
 * @package Skalar\Auth
 */
class SocialAuth
{
    /**
     * @var \OAuth\ServiceFactory
     */
    private $serviceFactory;
    /**
     * @var Session
     */
    private $storage;
    /**
     * @var
     */
    private $currentUri;
    /**
     * @var
     */
    private $socService;
    /**
     * @var
     */
    private $seviceName;

    /**
     *
     * socialauth constructor.
     */
    public function __construct($service)
    {
        $this->storage = new Session();

        $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
        $this->currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
        $this->currentUri->setQuery('');
        $this->serviceFactory = new \OAuth\ServiceFactory();
        $this->seviceName = $service;

        $this->{$this->seviceName . "Service"}();
    }

    /**
     * указываем id клиента
     * указываем секретный ключ клиента
     */
    private function googleService()
    {
        //echo $this->currentUri->getAbsoluteUri(); exit;
        $client_id = '451263689974-3cnboagcdqlq353pb22tgdh3ng3b0fd2.apps.googleusercontent.com';
        $client_secret = 'E6-2ZaKUzuzKzhmVQ-OxWCs0';

        $credentials = new Credentials(
            "451263689974-3cnboagcdqlq353pb22tgdh3ng3b0fd2.apps.googleusercontent.com",
            "E6-2ZaKUzuzKzhmVQ-OxWCs0",
            $this->currentUri->getAbsoluteUri()
        );

        $this->socService = $this->serviceFactory->createService(
            'google',
            $credentials,
            $this->storage,
            ['userinfo_email', 'userinfo_profile']
        );
    }

    /**
     * @return mixed
     */
    public function getRedirectUri(){
        return $this->currentUri->getAbsoluteUri();
    }

    /**
     *
     */
    private function facebookService()
    {
        $credentials = new Credentials(
            286892198529901,
            "5fbfae6f2bf26948144c008ba8cd96a3",
            $this->currentUri->getAbsoluteUri()
        );

        $this->socService = $this->serviceFactory->createService(
            'facebook',
            $credentials,
            $this->storage,
            []
        );
    }

    /**
     *
     */
    private function instagramService()
    {
        $credentials = new Credentials(
            "ae5f033788d64ad9a52a43e5f804b633",
            "05dfc1fbd67e4547b2f9f522bc6eaaee",
            $this->currentUri->getAbsoluteUri()
        );

        $scopes = ['basic', 'comments', 'relationships', 'likes'];

        $this->socService = $this->serviceFactory->createService(
            'instagram',
            $credentials,
            $this->storage,
            $scopes
        );
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->socService->getAuthorizationUri();
    }

    /**
     * @param $code
     * @param null $state
     */
    public function auth($code, $state = null)
    {
        return $this->{$this->seviceName . "Auth"}($code, $state);
    }

    /**
     * @param $code
     * @param null $state
     */
    public function instagramAuth($code, $state)
    {
        $this->socService->requestAccessToken($code, $state);
        $result = json_decode($this->socService->request("users/self"), true);
        return $this->authUser("UF_INSTAGRAM", $result["data"]["id"]);
    }

    /**
     * @param $code
     * @param null $state
     */
    public function facebookAuth($code, $state)
    {
        $this->socService->requestAccessToken($code, $state);
        $result = json_decode($this->socService->request("/me"), true);
        return $this->authUser("UF_FACEBOOK", $result["id"]);
    }

    /**
     * @param $code
     * @param null $state
     */
    public function googleAuth($code, $state = "")
    {
        $this->socService->requestAccessToken($code, $state);
        $result = json_decode($this->socService->request("userinfo"), true);
        return $this->authUser("UF_GOOGLE", $result["id"]);
    }

    /**
     * @param $field
     * @param $id
     */
    private function authUser($field, $id)
    {
        global $USER;

        $arUser = \CUser::GetList(
            ($by = "id"),
            ($order = "desc"),
            [
                $field => $id
            ],
            []
        )->fetch();

        if ($USER->GetID()) {
            if (!$arUser["ID"]) {
                return $USER->Update(
                    $USER->GetID(),
                    [
                        $field => $id
                    ]
                );
            }
        } else {
            if ($arUser["ID"]) {
                return $USER->Authorize($arUser["ID"]);
            }
        }
    }
}