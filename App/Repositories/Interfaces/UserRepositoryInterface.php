<?php

namespace App\Repositories\Interfaces;


interface UserRepositoryInterface
{
    public function encryptText($data);
    public function decryptText($data);

    public function userRegister(array $data);
    public function userLogin(array $data);
    public function userValidate(array $data);
    public function userData();
    public function userAll(array $data);
    public function userPasswordReset(array $data);
    public function generateOTP(array $data);
    public function verifyOTP(array $data);
}