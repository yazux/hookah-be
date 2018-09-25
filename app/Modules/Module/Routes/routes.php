<?php
Route::group(
    [
        'namespace' => 'App\Modules\Module\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web'
        ]
    ],
    function () {
        /**
         * Получение свойств модуля по его коду
         */
        Route::get(
            '/module/{code}/props',
            ['uses' => 'ModuleController@GetModuleProperties']
        );

        /**
         * Получение значения свойства модуля по его коду модуля и коду свойства
         */
        Route::get(
            '/module/{code}/props/{prop_code}',
            ['uses' => 'ModuleController@GetModulePropertyValue']
        );
    }
);



Route::group(
    [
        'namespace' => 'App\Modules\Module\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web','auth'
        ]
    ],
    function () {
        /**
         * Создание записи о модуле
         */
        Route::post('/module', ['uses' => 'ModuleController@CreateModule']);

        /**
         * Получение списка модулей
         */
        Route::get('/module', ['uses' => 'ModuleController@GetModules']);

        /**
         * Изменение записи о модуле
         */
        Route::put('/module', ['uses' => 'ModuleController@PutModuleByCode']);

        /**
         * Удаление записи модуля по его коду
         */
        Route::delete(
            '/module/{code}',
            ['uses' => 'ModuleController@RemoveModuleByCode']
        );

        /**
         * Получение записи модуля по его коду
         */
        Route::get('/module/{code}', ['uses' => 'ModuleController@GetModuleByCode']);

        /**
         * Получение конфигов модуля по его коду
         */
        Route::get(
            '/module/{code}/config',
            ['uses' => 'ModuleController@GetModuleConfig']
        );

        /**
         * Получение действий модуля по его коду
         */
        Route::get(
            '/module/{code}/actions',
            ['uses' => 'ModuleController@getModuleActions']
        );
        /**
         * Получение групп пользователей,
         * у которых есть доступ к конкретному действию
         */
        Route::get(
            '/module/{code}/actions/{action_code}',
            ['uses' => 'ModuleController@getModuleActionGroup']
        );


        /**
         * Получение сущностей модуля по его коду
         */
        Route::get(
            '/module/{code}/entity',
            ['uses' => 'ModuleController@getModuleEntity']
        );


        /**
         * Удаление значение свойства модуля по его коду модуля и коду свойства
         */
        Route::delete(
            '/module/{code}/props/{prop_code}',
            ['uses' => 'ModuleController@removeModulePropertyValue']
        );

        /**
         * Запись нового значения свойства модуля
         */
        Route::post(
            '/module/{code}/props',
            ['uses' => 'ModuleController@SetModulePropertyValue']
        );

        /**
         * Изменение значения свойства модуля
         */
        Route::put(
            '/module/{code}/props',
            ['uses' => 'ModuleController@PutModulePropertyValue']
        );


        Route::post(
            '/group/getaccess',
            ['uses' => 'ModuleController@getUserAccess']
        );

    }
);