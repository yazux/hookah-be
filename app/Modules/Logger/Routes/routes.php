<?php
Route::group(
    [
        'namespace' => 'App\Modules\Logger\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web'
        ]
    ],
    function () {
        Route::get('/company/{id}/events', ['uses' => 'LoggerController@getCompanyEvents']);
        Route::get('/events', ['uses' => 'LoggerController@getAllEvents']);
        Route::get('/log', ['uses' => 'LoggerController@getLog']);
    }
);



Route::group(
    [
        'namespace' => 'App\Modules\Logger\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web','auth'
        ]
    ],
    function () {
        Route::post('/log', ['uses' => 'LoggerController@writeApi']);
        Route::delete('/log', ['uses' => 'LoggerController@removeAll']);
        Route::delete('/log/{id}', ['uses' => 'LoggerController@remove']);
    }
);
