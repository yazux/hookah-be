<?php

Route::group(
    [
        'namespace' => 'App\Modules\Properties\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => ['web','cors']
    ],
    function () {

        /**
         * Получение свойств по коду модуля и по коду сущности
         */
        Route::get(
            '/properties/{module_code}/entity/{entity_code}',
            ['uses' => 'PropertiesController@getProperties']
        );


        Route::get(
            '/properties',
            ['uses' => 'PropertiesController@getCategoriesProperties']
        );

        Route::get(
            '/sku/{sku_id}/values',
            ['uses' => 'ValuesController@getSKUPropertiesValues']
        );






        /**
         * Получение свойств и вариантов выбора по коду модуля и по коду сущности
         */
        Route::get(
            '/properties/{module_code}/entity/{entity_code}/{entity_id}',
            ['uses' => 'PropertiesController@getProperties']
        );

        /**
         * Получение свойств по коду модуля
         */
        Route::get(
            '/properties/{module_code}',
            ['uses' => 'PropertiesController@getProperties']
        );

        /**
         * Получение свойства по его ID
         */
        Route::get(
            '/properties/byid/{id}',
            ['uses' => 'PropertiesController@getPropertyById']
        );

        /**
         * Получение списка вариантов выбора свйоства сущности
         */
        Route::get(
            '/properties/{prop_id}/choices',
            ['uses' => 'ChoicesController@getPropertyChoices']
        );

        /**
         * Получение варианта выбора свйоства сущности по его id
         */
        Route::get(
            '/properties/{prop_id}/choices/{id}',
            ['uses' => 'ChoicesController@getPropertyChoice']
        );

        /**
         * Получение списка значений свйоства сущности
         */
        Route::get(
            '/properties/{prop_id}/entity/{id}/values',
            ['uses' => 'ValuesController@getPropertyValues']
        );
        /**
         * Получение конкретного значения свйоства сущности
         */
        Route::get(
            '/properties/{prop_id}/entity/{entity_id}/values/{id}',
            ['uses' => 'ValuesController@getPropertyValues']
        );

        /**
         * Получение списка значений свойст с
         * параметрами свойства и вариантами выбора
         */
        Route::get(
            '/entity/values',
            ['uses' => 'ValuesController@getAllPropertyValues']
        );
        /**
         * Получение файла по id
         */
        Route::get(
            '/properties/file/{id}',
            ['uses' => 'FileController@getFileById']
        );
    }
);

Route::group(
    [
        'namespace' => 'App\Modules\Properties\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web','auth','cors'
        ]
    ],
    function () {
        /**
         * Добавление нового свйоства
         */
        Route::post('/properties', ['uses' => 'PropertiesController@postProperty']);

        /**
         * Изменение существующего свойства
         */
        Route::put('/properties', ['uses' => 'PropertiesController@putProperty']);

        /**
         * Удаление свйоства
         */
        Route::delete(
            '/properties/{id}',
            ['uses' => 'PropertiesController@deleteProperty']
        );


        /**
         * Добавление варианта выбора к свйоству сущности по id свойства
         */
        Route::post(
            '/properties/{prop_id}/choices',
            ['uses' => 'ChoicesController@postPropertyChoices']
        );


        /**
         * Обновление вариантов выбора к свйоств сущности по id свойства
         */
        Route::post(
            '/properties/{prop_id}/choicesarr',
            ['uses' => 'ChoicesController@postPropertyChoicesArray']
        );

        /**
         * Изменение варианта выбора к свйоству сущности
         */
        Route::put(
            '/properties/{prop_id}/choices',
            ['uses' => 'ChoicesController@putPropertyChoices']
        );

        /**
         * Удаление варианта выбора к свйоству сущности
         */
        Route::delete(
            '/properties/{prop_id}/choices/{id}',
            ['uses' => 'ChoicesController@deletePropertyChoices']
        );








        /**
         * Добавление значения свйоства сущности по id свойства
         */
        //Route::post('/properties/values', ['uses' => 'ValuesController@postPropertyValues']);
        Route::post('/properties/values', ['uses' => 'ValuesController@updatePropertyValueByObject']);


        Route::post(
            '/properties/values/update',
            ['uses' => 'ValuesController@updatePropertyValueByObject']
        );

        /**
         * Изменение значения свйоства сущности по id свойства
         */
        Route::put(
            '/properties/values',
            ['uses' => 'ValuesController@putPropertyValue']
        );



        /**
         * Удаление всех значений свйоства сущности
         */
        Route::delete(
            '/properties/{prop_id}/entity/{id}/values',
            ['uses' => 'ValuesController@deletePropertyValues']
        );

        /**
         * Удаление конкретного значения свйоства сущности
         */
        Route::delete(
            '/properties/{prop_id}/entity/{entity_id}/values/{id}',
            ['uses' => 'ValuesController@deletePropertyValues']
        );

        /**
         * Удаление значения свйоства - файла
         */
        Route::delete(
            '/properties/values/files',
            ['uses' => 'ValuesController@deleteFileValues']
        );


        Route::delete(
            '/property/value',
            ['uses' => 'ValuesController@deleteValue']
        );


        /**
         * Удаление файла по id
         */
        Route::delete(
            '/properties/file/{id}',
            ['uses' => 'FileController@deleteFileById']
        );
    }
);
