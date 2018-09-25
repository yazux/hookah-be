<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Classes\TestHelper;
use Illuminate\Support\Facades\DB;

class UserControllerTest extends TestCase
{
    //use DatabaseTransactions;

    /**
     * Добавление пользователя
     *
     * @return null
     */
    public function testPostUser()
    {
        $this->json(
            'POST', '/api/signup',
            [
                'login' => 'unitTestUser1',
                'email' => 'user1@unit.ru',
                'password' => 'Bb103ecc',
                'password_confirm' => 'Bb103ecc',
            ]
        )->assertResponseStatus(200)->seeJsonStructure(
            [
                'success',
                'response' => [
                    'id', 'login', 'email',
                    'created_at', 'updated_at'
                ]
            ]
        );

        $TestHelper = TestHelper::gi();
        $TestHelper->removeUser(['email' => 'user1@unit.ru']);
    }

    /**
     * Обновление пользователя
     *
     * @return null
     */
    public function testPutUser()
    {
        $TestHelper = TestHelper::gi();
        $User = $TestHelper->user($this);
        $Auth = $TestHelper->auth($User, $this);

        $this->json(
            'POST', '/api/user',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                '_method' => 'PUT',
                'unit_test' => 'true',
                'id'       => $User['id'],
                'login'    => 'unitTestUser_update',
                'email'    => 'user_update@unit.ru',
                'password' => 'Bb103ecc_update',
                'password_confirm' => 'Bb103ecc_update'
            ]
        )->assertResponseStatus(200)->seeJsonStructure(
            [
                'success',
                'response' => [
                    'id', 'login', 'email',
                    'created_at', 'updated_at'
                ]
            ]
        );

        $TestHelper->removeUser(['email' => 'user_update@unit.ru']);
    }

    /**
     * Удаление пользователя
     *
     * @return null
     */
    public function testDeleteUser()
    {
        $TestHelper = TestHelper::gi();
        //логинимся как админ
        $Auth = $TestHelper->auth($TestHelper::$user_admin, $this);
        $UserToDelete = $TestHelper->user($this);

        $this->json(
            'POST', '/api/user/id/' . $UserToDelete['id'],
            [
                'authorization' => $Auth['response']['token_data']['token'],
                '_method' => 'DELETE',
                'unit_test' => 'true'
            ]
        )->assertResponseStatus(200);
        $TestHelper->removeUser();
    }

    /**
     * Получение пользователя по id
     *
     * @return null
     */
    public function testGetUserByid()
    {
        $TestHelper = TestHelper::gi();
        $User = $TestHelper->user($this);
        $Auth = $TestHelper->auth($User, $this);

        $this->json(
            'GET', '/api/user/id/' . $User['id'],
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test' => 'true'
            ]
        )->assertResponseStatus(200);

        $this->json(
            'GET', '/api/user/id/0',
            [
                'unit_test' => 'true',
                'authorization' => $Auth['response']['token_data']['token']
            ]
        )->assertResponseStatus(404);

        $this->json(
            'GET', '/api/user/id/' . $User['id'],
            ['unit_test' => 'true']
        )->assertResponseStatus(401);

        $this->json(
            'GET', '/api/user/' . $User['login'] . '/isadmin',
            [
                'unit_test' => 'true',
                'authorization' => $Auth['response']['token_data']['token']
            ]
        )->assertResponseStatus(200);
        //->seeJson(['response' => false])

        $TestHelper->removeUser();
    }

    /**
     * Получение списка пользователей
     *
     * @return null
     */
    public function testGetUsers()
    {
        $TestHelper = TestHelper::gi();
        //логинимся как админ
        $Auth = $TestHelper->auth($TestHelper::$user_admin, $this);

        $this->json(
            'GET', '/api/user',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test' => 'true'
            ]
        )->assertResponseStatus(200);

        $this->json('GET', '/api/user', ['unit_test' => 'true'])
            ->assertResponseStatus(401);
    }

    /**
     * Получение текущего пользователя
     *
     * @return null
     */
    public function testGetCurrentUser()
    {
        $TestHelper = TestHelper::gi();
        //логинимся как админ
        $Auth = $TestHelper->auth($TestHelper::$user_admin, $this);

        $this->json(
            'GET', '/api/user/current',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test' => 'true'
            ]
        )->assertResponseStatus(200);
    }

    /**
     * Получение групп пользователя
     *
     * @return null
     */
    public function testGetUserGroups()
    {
        $TestHelper = TestHelper::gi();
        $User = $TestHelper->user($this);
        $Auth = $TestHelper->auth($User, $this);

        $this->json(
            'GET', '/api/user/' . $User['login'] . '/group',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test' => 'true'
            ]
        )->assertResponseStatus(200);

        $this->json(
            'GET', '/api/user/' . $User['login'] . '/group',
            ['unit_test' => 'true']
        )->assertResponseStatus(401);

        $TestHelper->removeUser();
    }

    /**
     * Прикрепление и открепление пользователя от групп
     *
     * @return null
     */
    public function testAttachDetachUserToFromGroup()
    {
        $TestHelper = TestHelper::gi();
        $User = $TestHelper->user($this);
        //логинимся как админ
        $Auth = $TestHelper->auth($TestHelper::$user_admin, $this);

        $Group = \App\Modules\User\Model\Group::create(
            [
                'name' => 'Unit test group',
                'sort' => 100,
                'code' => 'unit_test_group'
            ]
        );

        $this->json(
            'POST', '/api/user/attachgroup',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test'     => 'true',
                'login'         => $User['login'],
                'group'         => $Group['code']
            ]
        )->assertResponseStatus(200);


        $this->json(
            'POST', '/api/user/detachgroup',
            [
                'authorization' => $Auth['response']['token_data']['token'],
                'unit_test'     => 'true',
                'login'         => $User['login'],
                'group'         => $Group['code']
            ]
        )->assertResponseStatus(200);

        $TestHelper->removeUser();
        $Group->delete();
    }

}

