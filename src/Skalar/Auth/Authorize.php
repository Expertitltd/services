<?php

namespace Skalar\Auth;

/**
 * Class Authorize
 * @package Skalar\Auth
 */
class Authorize
{
    protected $user;
    protected $token;

    /**
     * Authorize constructor.
     */
    public function __construct()
    {
        global $USER;
        $this->user = $USER;
    }

    /**
     * Проверка - авторизован ли пользователь
     * @return bool
     */
    public function isAuthorize(){
        return $this->user->IsAuthorized();
    }

    /**
     * Авторизация по логину и паролю
     * @param $login
     * @param $password
     * @return array|bool
     */
    public function auth($login, $password){
        $res = $this->user->Login($login, $password);

        if($res === true) {
            $user = $this->user;
            if (empty($user['UF_RESTFUL_API_TOKEN'])) {
                $this->token = md5($user['LOGIN'] . $user['EMAIL']);
                $this->user->Update($user['ID'], ['UF_RESTFUL_API_TOKEN' => $this->token]);
            }

            return $user;
        }
        return $res;
    }

    /**
     * Авторизация по id
     * @param $id
     * @return bool
     */
    public function loginById($id){
        if($this->user->getById($id)->fetch() === false){
            return false;
        }

        $this->user->authorize($id);

        return true;
    }

    /**
     * @return bool
     */
    public function logout()
    {
        $logout = $this->user->logout();

        if($logout == NULL){
            return true;
        }

        return false;
    }

    /**
     * @param $service
     * @param $code
     * @param null $state
     * @return mixed
     */
    public function socAuth($service, $code, $state = null)
    {
        $socialAuth = new SocialAuth($service);

        return $socialAuth->auth($code, $state);
    }
}