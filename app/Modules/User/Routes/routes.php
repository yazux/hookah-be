<?php

Route::group(
    [
        'namespace' => 'App\Modules\User\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => ['web','cors']
    ],
    function () {

        Route::get('/group/{id}/push_actions', ['uses' => 'GroupController@pushPublicActionsToGroup']);

        /**
         * Логин (авторизация) пользователя по логину и паролю
         * либо по email и паролю
         */
        Route::post('/login', ['uses' => 'AuthController@Login']);
        /**
         * Регистрация пользователя
         */
        Route::post('/signup', ['uses' => 'UserController@createUser']);
        /**
         * Отправляет пользователю email с ссылкой для сменф пароля
         */
        Route::get(
            '/user/{login}/return',
            ['uses' => 'UserController@sendReturnEmail']
        );

        /**
         * Сбрасывает пароль пользователя и отправляем ему новый на почту
         */
        Route::get(
            '/user/{email}/break',
            ['uses' => 'UserController@breakPassword']
        );



        /**
         * Установка нового пароля пользователя по токену
         */
        Route::post(
            '/user/reset',
            ['uses' => 'UserController@resetPassword']
        );

        Route::get(
            '/user/encryptpass/{pass}',
            ['uses' => 'UserController@encryptPassword']
        );

        Route::get('/user/byid/{id}', ['uses' => 'UserController@getUserById']);
        /**
         * Получение списка пользователей
         */
        Route::get('/user', ['uses' => 'UserController@getUsers']);
        /**
         * Возвращает пользователя, являющегося разработчиком
         */
        Route::get(
            '/user/developer',
            ['uses' => 'UserController@getDeveloperUser']
        );



        /**
         * Получение списка всех групп
         */
        Route::get('/group', ['uses' => 'GroupController@getGroups']);
    }
);

Route::group(
    [
        'namespace' => 'App\Modules\User\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web','auth','cors'
        ]
    ],
    function () {


        Route::get('/user/{id}/email/getconfirm', ['uses' => 'UserController@getConfirmEmail']);
        Route::post('/user/{id}/email/confirm', ['uses' => 'UserController@confirmEmail']);



        Route::post('/user/hero_image', ['uses' => 'UserController@putHeroImage']);

        /**
         * Получение обновлённого access_token при помощи refresh_token
         */
        Route::get('/login/refresh', ['uses' => 'AuthController@RefreshToken']);
        /**
         * Вернёт текущего пользователя
         */
        Route::get(
            '/user/current',
            ['uses' => 'UserController@getCurrentUser']
        );


        /**
         * Добавление нового пользователя
         */
        Route::post('/user', ['uses' => 'UserController@CreateUser']);
        /**
         * Обновление существующего пользователя
         */
        Route::put('/user', ['uses' => 'UserController@putUser']);


        /**
         * Прикрепление пользователя к какой-то группе
         */
        Route::post(
            '/user/attachgroup',
            ['uses' => 'UserController@attachUserGroup']
        );
        /**
         * Открепление пользователя от группы
         */
        Route::post(
            '/user/detachgroup',
            ['uses' => 'UserController@detachUserGroup']
        );



        //user by email
        /**
         * Получение данных пользователя по его email
         */
        Route::get(
            '/user/email/{email}',
            ['uses' => 'UserController@GetUserByEmail']
        );
        /**
         * Удаление пользователя по его email
         */
        Route::delete(
            '/user/email/{email}',
            ['uses' => 'UserController@RemoveUserByEmail']
        );
        //user by id
        /**
         * Получение данных пользователя по его id
         */
        Route::get('/user/id/{id}', ['uses' => 'UserController@GetUserById']);
        /**
         * Удаление пользователя по его id
         */
        Route::delete(
            '/user/id/{id}',
            ['uses' => 'UserController@RemoveUserById']
        );




        //logout
        /**
         * Выход пользователя из системы
         */
        Route::post('/logout', ['uses' => 'AuthController@Logout']);

        Route::post(
            '/user/change_password',
            ['uses' => 'UserController@changePassword']
        );


        //groups
        /**
         * Добавлание новой группы
         */
        Route::post('/group', ['uses' => 'GroupController@CreateGroup']);
        /**
         * Удаление групппы
         */
        Route::delete('/group/{id}', ['uses' => 'GroupController@removeGroup']);
        /**
         * Изменение группы
         */
        Route::put('/group', ['uses' => 'GroupController@PutGroup']);
        /**
         * Получение данных группы по её коду
         */
        Route::get('/group/{code}', ['uses' => 'GroupController@GetGroupByCode']);




        //actions
        /**
         * Получение списка всех действий
         */
        Route::get('/action', ['uses' => 'ActionController@getActions']);
        /**
         * Получение данных действия по его id
         */
        Route::get('/action/id/{id}', ['uses' => 'ActionController@getActionById']);
        /**
         * Получение данных действия по его коду и коду модуля
         */
        Route::get(
            '/action/{code}/module/{module_code}',
            ['uses' => 'ActionController@getAction']
        );
        /**
         * Добавление нового действия в БД
         */
        Route::post('/action', ['uses' => 'ActionController@createAction']);
        /**
         * Обновление действия в БД
         */
        Route::put('/action', ['uses' => 'ActionController@updateAction']);
        /**
         * Удаление действия из БД
         * (не удаляет действие из конфига, это можно сделать только руками)
         */
        Route::delete('/action', ['uses' => 'ActionController@removeAction']);







        Route::get(
            '/get_user_test',
            ['uses' => 'UserController@getUsers_TEST']
        );


    }
);
