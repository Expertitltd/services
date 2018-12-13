<?php

namespace Skalar\Auth;
use Symfony\Component\Validator\Validation;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class Validator
{

    private $validator;
    private $errors;

    public function __construct(){
        $this->errors = [];
        $this->validator = Validation::createValidator();
    }

    /**
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }

    public function isInt(){
        return true;
    }

    /**
     * Длина проверяемого значения должна быть не меньше min и не больше max
     * В значении не должно быть цифр
     * Строка может быть пустой
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function isString($value){
        $errors = $this->validator->validate($value, [
            new Length(array(
                'min' => 2,
                'max' => 50,
                'minMessage' => 'Ваш логин должен быть как минимум {{ limit }} символов',
                'maxMessage' => 'Ваш логин должен быть длиннее {{ limit }} символов',
            )),
            new Regex(array(
                'pattern' => '/\d/',
                'match'   => false,
                'message' => 'Your name cannot contain a number',
            ))
        ]);
        if(count($errors) > 0) {
            $this->errors[] = $errors;
        }

        if (count($errors) > 0) {
            throw new \Exception('Не валидная строка');
        }

        return true;
    }

    /**
     * true или массив
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function validateLogin($value){
        $errors = $this->validator->validate($value, [
            new NotBlank(),
            new Length(array(
                'min' => 5,
                'max' => 50,
                'minMessage' => 'Ваш логин должен быть как минимум {{ limit }} символов',
                'maxMessage' => 'Ваш логин должен быть длиннее {{ limit }} символов',
            )),
        ]);

        if(count($errors) > 0) {
            $this->errors[] = $errors;
        }

        if (count($errors) > 0) {
            throw new \Exception('Не валидный логин');
        }

        return true;
    }

    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function validateEmail($value){
        $errors = $this->validator->validate($value, [
            new NotBlank(),
            new Email(array(
                'message' => "The email ".$value." is not a valid email.",
                'checkMX' => false,
            )),
        ]);

        if(count($errors) > 0) {
            $this->errors[] = $errors;
        }

        if (count($errors) > 0) {
            throw new \Exception('Не валидный email');
        }

        return true;
    }

}