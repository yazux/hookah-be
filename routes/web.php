<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(
    ['middleware' => ['cors']],
    function () {
        Route::any(
            '/',
            function () {
                return response()->json(
                    [
                        "success" => true,
                        "status" => 200,
                        "errors" => [
                            "messages" => "",
                            "errors" => ""
                        ],
                        "request" => '',
                        "response" => [
                            'message' => 'Hello! This is Yazu projects API.',
                            'test' => 'success'
                        ]
                    ]
                );
            }
        );





        Route::get(
            '/test/email',
            function () {
                $result = Mail::send(['test' => 'Test message from GID'], [], function ($message) {
                    $message->from(env('MAIL_FROM_ADDRESS'), env('SITE_NAME'));
                    $message->to('speed.live@mail.ru');
                });

                return response()->json($result);
            }
        );




        Route::get(
            '/api',
            function () {
                return response()->json(
                    [
                        "success" => true,
                        "status" => 200,
                        "errors" => [
                            "messages" => "",
                            "errors" => ""
                        ],
                        "request" => '',
                        "response" => [
                            'message' => 'Hello! This is CRM API.'
                        ]
                    ]
                );
            }
        );

        Route::post('/api/pusher', function() {

                $event = \App\Http\Controllers\EventPusher::event(
                    'main',
                    'my-event',
                    [
                        'message' => 'test ' . date('d.m.Y G:i:s', time()),
                        'action' => 'get_users',
                        'data' => ''
                    ]
                );

                return response()->json(
                    [
                        "success" => true,
                        "status" => 200,
                        "errors" => [
                            "messages" => "",
                            "errors" => ""
                        ],
                        "request" => '',
                        "response" => [
                            'message' => 'Hi there Pusher!',
                            'test' => 'success',
                            'event' => $event
                        ]
                    ]
                );
            });



    }
);








