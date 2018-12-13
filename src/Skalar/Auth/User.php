<?php

namespace Skalar\Auth;

/**
 * Class User
 * @package Skalar\Auth
 */
class User
{
    /**
     * @var
     */
    protected $user;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * User constructor.
     */
    public function __construct()
    {
        global $USER;
        $this->user = $USER;
        $this->validator = new Validator();
    }

    /**
     * Метод добавляет нового пользователя. При успешном выполнении возвращает ID нового пользователя
     * на вход принимает массив значений полей
     * например, array("EMAIL" => "ivanov@microsoft.com")
     * @param $fields
     * @return bool|int|string
     */
    public function addUser($fields)
    {
        $result = $this->user->add($fields);

        if ($result !== false) {
            return $result;
        }

        return false;
    }

    /**
     * TODO Валидация пароля
     * Регистрирует нового пользователя, авторизует его и отсылает письмо по шаблону типа NEW_USER
     * Возвращает массив с сообщением о результате выполнения (массив может быть обработан функцией ShowMessage)
     * @param $login
     * @param $name
     * @param $lastname
     * @param $password
     * @param $confirmpassword
     * @param $email
     * @return array|bool
     */
    public function register($login, $name, $lastname, $password, $confirmpassword, $email)
    {
        $this->validator->validateLogin($login);
        $this->validator->isString($name);
        $this->validator->isString($lastname);
        $this->validator->validateEmail($email);

        $errors = $this->validator->getErrors();

        if(count($errors) > 0){
            return $errors;
        }

        return $this->user->Register($login, $name, $lastname, $password, $confirmpassword, $email, SITE_ID, "sss", 1);
    }

    /**
     * @param $id
     * @param $fields
     * @return bool
     */
    public function updateUser($id, $fields)
    {
        $result = $this->user->Update($id, $fields);
        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteUser($id)
    {
        if ($this->user->Delete($id)) {
            return true;
        }
        return false;
    }

}