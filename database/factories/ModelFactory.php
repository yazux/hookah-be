<?php
$factory->defineAs(
    App\Modules\User\Model\User::class,
    'user',
    function (Faker\Generator $faker) {
        $UserController = new \App\Modules\User\Controllers\UserController();
        return [
            'login' => 'unitTestUser',
            'email' => 'user@unit.ru',
            'password' => $UserController->encryptPassword('Bb103ecc'),
        ];
    }
);
