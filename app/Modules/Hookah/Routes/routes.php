<?php

Route::group([
        'namespace' => 'App\Modules\Hookah\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => ['web','cors']
    ], function () {
        Route::get(   '/vendor',      ['uses' => 'VendorController@getVendors'      ]);
        Route::get(   '/vendor/{id}', ['uses' => 'VendorController@getVendorById'   ]);
        Route::post(  '/vendor',      ['uses' => 'VendorController@postVendor'      ]);
        Route::put(   '/vendor',      ['uses' => 'VendorController@putVendor'       ]);
        Route::delete('/vendor/{id}', ['uses' => 'VendorController@deleteVendorById']);

        Route::get(   '/line',      ['uses' => 'LineController@getLines'      ]);
        Route::get(   '/line/{id}', ['uses' => 'LineController@getLineById'   ]);
        Route::post(  '/line',      ['uses' => 'LineController@postLine'      ]);
        Route::put(   '/line',      ['uses' => 'LineController@putLine'       ]);
        Route::delete('/line/{id}', ['uses' => 'LineController@deleteLineById']);

        Route::get(   '/tobacco',      ['uses' => 'TobaccoController@getTobaccos'      ]);
        Route::get(   '/tobacco/{id}', ['uses' => 'TobaccoController@getTobaccoById'   ]);
        Route::post(  '/tobacco',      ['uses' => 'TobaccoController@postTobacco'      ]);
        Route::put(   '/tobacco',      ['uses' => 'TobaccoController@putTobacco'       ]);
        Route::delete('/tobacco/{id}', ['uses' => 'TobaccoController@deleteTobaccoById']);

        Route::post('/parese_page',    ['uses' => 'TobaccoController@getPageLinks'     ]);
        Route::post('/parese_product', ['uses' => 'TobaccoController@getProductData'   ]);
        Route::post('/array_add',      ['uses' => 'TobaccoController@addFromNamesArray']);

        Route::get(   '/category',      ['uses' => 'CategoryController@getCategories'     ]);
        Route::get(   '/category/{id}', ['uses' => 'CategoryController@getCategoryById'   ]);
        Route::post(  '/category',      ['uses' => 'CategoryController@postCategory'      ]);
        Route::put(   '/category',      ['uses' => 'CategoryController@putCategory'       ]);
        Route::delete('/category/{id}', ['uses' => 'CategoryController@deleteCategoryById']);

        Route::get(   '/mix',      ['uses' => 'MixController@getMixes'     ]);
        Route::get(   '/mix/{id}', ['uses' => 'MixController@getMixById'   ]);
        Route::post(  '/mix',      ['uses' => 'MixController@postMix'      ]);
        Route::put(   '/mix',      ['uses' => 'MixController@putMix'       ]);
        Route::delete('/mix/{id}', ['uses' => 'MixController@deleteMixById']);

        Route::get(   '/bookmark',      ['uses' => 'BookmarkController@getBookmarks'      ]);
        Route::get(   '/bookmark/{id}', ['uses' => 'BookmarkController@getBookmarkById'   ]);
        Route::post(  '/bookmark',      ['uses' => 'BookmarkController@postBookmark'      ]);
        Route::put(   '/bookmark',      ['uses' => 'BookmarkController@putBookmark'       ]);
        Route::delete('/bookmark/{id}', ['uses' => 'BookmarkController@deleteBookmarkById']);
        Route::delete('/bookmark',      ['uses' => 'BookmarkController@deleteBookmark'    ]);


        Route::get('/parse_mix', ['uses' => 'MixController@parseFromFile']);
    }
);

Route::group(
    [
        'namespace' => 'App\Modules\Hookah\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => [
            'web','auth','cors'
        ]
    ],
    function () {
    }
);
