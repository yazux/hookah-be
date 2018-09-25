<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Model\Invites;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Properties\Controllers\FileController;
use App\Modules\User\Model\Group;
use App\Modules\User\Model\ResetPassword;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UsersToGroup;
use App\Modules\User\Model\Action;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\State;
use Illuminate\Support\Facades\Mail;

/**
 * Класс для работы с пользователями
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class UserController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'User';

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }


    /**
     * Отправляет пользователю email
     * с ссылкой для подтверждения аккаунта
     *
     * @param $userId
     * @param $json
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function getConfirmEmail($userId, $json = true)
    {
        $User = $this->getUserById($userId, true);
        if ($User->email_confirm) throw new CustomDBException(
            [], [], 400, 'Email указанного пользователя уже подтверждён.'
        );

        $email_code = password_hash($User->email . config("app.encrypt_salt"), PASSWORD_BCRYPT);

        //отправляем письмо с восстановлением пароля
        $sendMail = Mail::send(
            'emails.confirm_email',
            [
                'email' => $User->email,
                'code'  => $email_code,
                'link'  => env('FE_URL') . '/user/' . $User->id . '/settings?email_confirm=1&code=' . $email_code
            ],
            function ($m) use ($User) {
                $m->to($User->email, $User->login)->subject('2Гид: Подтверждение e-mail адреса');
            }
        );

        $result = ($sendMail === null) ? true : false;

        $User = User::where('id', $User->id)->first();
        if ($result) $User->update(['email_code' => $email_code]); //$User->save();

        return ($json) ? parent::response(['user_id' => $userId], $result, 200) : $result;
    }

    /**
     * Подтверждает email пользователя
     *
     * @param $userId
     *
     * @return mixed
     * @throws CustomException
     */
    public function confirmEmail($userId) {
        $hash = request()->get('code');
        //$User = $this->getUserById($userId, true);
        $User = User::where('id', $userId)->first();
        if ($User->email_confirm) throw new CustomException(
            [], [], 400, 'Email указанного пользователя уже подтверждён.'
        );

        if ($hash != $User->email_code) throw new CustomException(
            [], [], 400, 'Код не совпадает, запросите подтверждение ещё раз.'
        );

        $User->email_confirm = 1;
        $User->save();

        return parent::response(['user_id' => $userId], $User, 200);
    }

    public function putHeroImage(Request $request)
    {
        $User = User::where('id', $request->get('user_id'))->first();
        if (!$User) throw new CustomDBException([], [], 404, 'Пользователь не найден');

        if (!$request->hasFile('hero_image')) throw new CustomDBException([], [], 400, 'В запросе тербуется передать файл');

        $file = self::upFile($request, 'hero_image');

        //удаляем старый файл
        if ($User->hero_image_id) FileController::call('deleteFileById', $User->hero_image_id, false);

        $User->hero_image_id = $file['id'];
        $User->save();

        return $this->getUserById($User->id);
    }

    /**
     * Возвращает пользователя, являющегося разработчиком
     *
     * @param bool $json - формат ответа
     *
     * @return mixed
     */
    public function getDeveloperUser($json = true)
    {
        $User = User::where('login', env('DEVELOPER_LOGIN'))
            ->select(User::fields())->first();

        return ($json) ? parent::response(request()->all(), $User, 200) : $User;
    }

    /**
     * Возвращает текущего пользователя
     *
     * @param bool $json - формат ответа
     *
     * @return mixed
     */
    public function getCurrentUser($json = true)
    {
        $User = State::User();
        return ($json) ? parent::response(request()->all(), $User, 200) : $User;
    }

    /**
     * Проверяет, является ли текущий пользователь админом
     *
     * @param string $loginOrId - логин пользователя
     * @param bool   $json      - вернуть чистый json или форматированный объект
     *
     * @return bool|mixed
     */
    public function isAdmin($loginOrId, $json = false)
    {
        $result = false;
        if (is_numeric($loginOrId)) {
            $User = User::where('id', $loginOrId)->first();
        } else {
            $User = User::where('login', $loginOrId)->first();
        }

        if ($User && $User != []) {
            $userGroups = $User->groups()->get();
            $userGroups = array_pluck($userGroups, 'code', 'id');
            if (in_array(env('ADMIN_GROUP'), $userGroups)) $result = true;

        }

        if ($User['login'] == 'admin') $result = true;

        return ($json) ? parent::response(['loginOrId' => $loginOrId], $result, 200) : $result;
    }

    /**
     * Проверят может ли текущий
     * пользователь выполнять действие $action
     *
     * @param string $action         - символьный код действия
     * @param bool   $ThrowException - выбрасывать ли исключение
     *
     * @return bool
     *
     * @throws CustomDBException
     * @throws CustomException
     */
    public static function can($action, $ThrowException = false)
    {
        $CurrentUser = State::User();

        //если текущего пользователя не существует
        //значит происходит доступ к публичным методам
        //или
        //если доступ запрашивает суперпользователь, то всегда true
        if (!$CurrentUser
            || $CurrentUser['login'] == env('SUPERUSER_LOGIN')
        ) {
            return true;
        }

        //получим id всех груп, в которых состоит пользователь
        $CurrentUser['groups_id'] = $CurrentUser['groups']->pluck('id');

        //если пользователь не состоит ни в одной группе
        if (!count($CurrentUser['groups_id'])) {
            throw new CustomDBException(
                $CurrentUser['login'], [], 400,
                'Пользователь "' . $CurrentUser['login']
                . '" не состоит ни в одной группе'
            );

        }
        $CurrentUser['groups_id'] = $CurrentUser['groups_id']->toArray();

        $action = Action::where('name', $action)
            ->whereIn('group_id', $CurrentUser['groups_id'])
            ->first();

        $result = ($action) ? true : false;

        if (!$result && $ThrowException) {
            throw new CustomException(
                [], [], 403, CustomException::text('403')
            );
        }

        return $result;
    }


    /**
     * Проверят может ли пользователь с логином $userLogin
     * выполнять действие $action
     *
     * @param string $userLogin - логин пользователя
     * @param string $action    - символьный код действия
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function canAction($userLogin, $action)
    {
        $request = [
            'login' => $userLogin,
            'action' => $action,
        ];

        $User = User::where('login', $userLogin);
        $CurrentUser = $User->first();
        if (!$CurrentUser) throw new CustomDBException($userLogin, [], 404, 'User not found');

        //получим id всех груп, в которых состоит пользователь
        $CurrentUser['groups_id'] = $CurrentUser->groups()->get()->pluck('id');

        //если пользователь не состоит ни в одной группе
        if (!count($CurrentUser['groups_id'])) {
            throw new CustomDBException(
                $userLogin, [], 400,
                'User "'.$userLogin.'" is not a member of any groups'
            );
        }

        $action = Action::where('name', $action)
            ->where('group_id', $CurrentUser['groups_id'])
            ->first();

        $result = ($action) ? true : false;

        return parent::response($request, $result, 200);
    }


    /**
     * Создаёт нового пользователя
     * Параметры POST запроса:
     * login - логин пользователя
     * email - email пользователя
     * password - пароль пользователя
     * password_confirm - подтверждеие пароля, поле должно совпадать с полем password
     * sort - сортировка
     *
     * @param Request $request - Экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function createUser(Request $request)
    {
        User::can('user_addusers', true);
        $data = $request->only(
            [
                'login', 'email', 'password', 'password_confirm', 'sort', 'groups',
                'name', 'surname', 'middle_name', 'phone', 'birthday', 'address'
            ]
        );
        $newUser = false;


        //формируем логин из email
        if (!$data['login']) {
            $data['login'] = preg_replace(
                '/[^a-zA-Zа-яА-Я0-9]/ui', '',
                substr($data['email'], 0, stripos($data['email'], '@'))
            );
        }

        if (User::valid($data, 'post', new User())) {
            $password = $this->encryptPassword($data['password']);
            $data['password_confirm'] = $password;
            $newUser = new User();
            $newUser->login = $data['login'];
            $newUser->email = $data['email'];
            $newUser->password = $password;
            $newUser->sort = (isset($data['sort']) && $data['sort']) ? $data['sort'] : 100;
            $newUser->name        = $data['name'];
            $newUser->surname     = $data['surname'];
            $newUser->middle_name = $data['middle_name'];
            $newUser->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_addusers', null, 'users', $newUser->id,
                ['data' => self::modelFilter($newUser, User::fields())]
            );

            if (!$data['groups']) $data['groups'] = [];

            //10 - id группы для всех пользователей
            //туда надо обязательно добавить пользователя
            $data['groups'][] = env('USERS_GROUP_FOR_ALL_USERS', 10);

            if (is_array($data['groups'])) {
                //$newUser->groups = $this->updateUserGroup($newUser, $data['groups']);
                $newUser->groups()->sync($data['groups']);
            }

            if ($newUser) {

                //отпраляем email с подтверждением почты
                $this->getConfirmEmail($newUser->id, false);

                $request->replace([
                    'login'    => $data['email'],
                    'password' => $data['password']
                ]);
                $Auth = new AuthController();
                return $Auth->login($request);
            }

        }

        return parent::response($data, $newUser, 200);
    }

    /**
     * Изменяет параметры созданного пользователя
     * Параметры POST запроса:
     * login - логин пользователя
     * email - email пользователя
     * password - пароль пользователя
     * password_confirm - подтверждеие пароля, поле должно совпадать с полем password
     * sort - сортировка
     *
     * @param Request $request - Экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function putUser(Request $request)
    {
        $data = $request->only(
            'id', 'login', 'email', 'password',
            'password_confirm', 'sort', 'groups',
            'name', 'surname', 'middle_name',
            'phone', 'birthday', 'address'

        );
        $data['update_id'] = $data['id'];

        $data['phone'] = preg_replace('~\D+~', '', $data['phone']);

        $CurrentUser = State::User();

        //если пользователь обновляет не свои данные
        if ($CurrentUser['email'] != $data['email']) {
            User::can('user_putusers', true);
        }

        $User = User::where('id', $data['update_id'])->first();
        if (!$User) throw new CustomDBException($data, [], 404, 'Пользователь не найден');

        $oldUser = clone $User;
        if (User::valid($data, 'put', new User())) {

            $User->login = $data['login'];
            $User->email = $data['email'];
            $User->sort = ($data['sort']) ? $data['sort'] : 100;

            $User->name        = $data['name'];
            $User->surname     = $data['surname'];
            $User->middle_name = $data['middle_name'];
            $User->phone       = $data['phone'];
            $User->birthday    = $data['birthday'];
            $User->address     = $data['address'];

            if ($data['password']) {
                $data['password'] = $this->encryptPassword($data['password']);
                $data['password_confirm'] = $data['password'];
                $User->password = $data['password'];
            }

            $User->save();
            //логируем действие
            LoggerController::write(
                $this->getModuleName(),
                'user_putusers', null, 'users', $User->id,
                ['data' => self::modelFilter($User, User::fields())],
                [$oldUser, $User]
            );

            if (is_array($data['groups']) && count($data['groups']))
                $User->groups = $User->groups->sync($data['groups']);

            $User->groups = $User->groups()->get()->pluck('id');
        }

        return parent::response($data, $User, 200);
    }


    public function updateUserGroup(User $user, $groupIdArray)
    {
        if (is_array($groupIdArray)) {

            //получим все существующие группы, в которых состоит пользователь
            $UsersGroups = UsersToGroup::where('user_id', $user->id)
                ->select('id', 'group_id')->get()->pluck('group_id');

            //все группы в системе
            $AllGroupsFull = Group::where('id', '!=', '0')->select('id', 'name')
                ->get();
            $AllGroups = $AllGroupsFull->pluck('id');
            $AllGroupsFull = $AllGroupsFull->keyBy('id');

            foreach ($AllGroups as $groupId) {

                //Проверим, добавлен ли уже пользователь в эту группу или нет
                $issetUserInGroup = UsersToGroup::where('user_id', $user->id)
                    ->where('group_id', $groupId)->select('id')->first();

                //если группа есть в запрсе, значит добавляем пользователя в группу
                if (in_array($groupId, (array) $groupIdArray)) {
                    //проверим, принадлежит ли уже пользователь этой группе
                    //если не принадлежит, то добавляем его в группу
                    if ( !$issetUserInGroup ) {
                        $result = ($user->groups()->attach($groupId)) ? false : true;
                        if ($result) {
                            //логируем действие
                            LoggerController::write(
                                $this->getModuleName(), 'user_attachusertogroup',
                                'Добавление пользователя ' . $user->login .
                                '('.$user->id.')' . ' к группе ' . $AllGroupsFull[$groupId]->name .
                                '('.$AllGroupsFull[$groupId]->id.')',
                                'users', $user->id,
                                [
                                    'data' => [
                                        'user' => self::modelFilter($user, User::fields()),
                                        'group' => self::modelFilter($AllGroupsFull[$groupId], Group::fields()),
                                    ]
                                ]
                            );
                        }
                    }
                } else { //если нет, то удаляем пользователя из группы
                    //проверим, принадлежит ли уже пользователь этой группе
                    //если принадлежит, то удаляемм его из группы
                    if ( $issetUserInGroup ) {
                        $result = ($user->groups()->detach($groupId)) ? true : false;
                        if ($result) {
                            //логируем действие
                            LoggerController::write(
                                $this->getModuleName(), 'user_detachuserfromgroup',
                                'Удаление пользваотеля ' . $user->login .
                                '('.$user->id.')' . ' из группы ' . $AllGroupsFull[$groupId]->name .
                                '('.$AllGroupsFull[$groupId]->id.')',
                                'users', $user->id,
                                [
                                    'data' => [
                                        'user' => self::modelFilter($user, User::fields()),
                                        'group' => self::modelFilter($AllGroupsFull[$groupId], Group::fields()),
                                    ]
                                ]
                            );
                        }
                    }
                }
            }
        }

        // вернём массив с id групп, в которых есть пользователь
        return UsersToGroup::where('user_id', $user['id'])->select('id', 'group_id')
            ->get()->pluck('group_id');
    }

    /**
     * Добавляет пользователя с логином login к группе с кодом group
     * Параметры POST запроса:
     * login - Логин пользователя
     * group - Символьный код группы
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function attachUserGroup(Request $request)
    {
        User::can('user_attachusertogroup', true);
        $data = $request->only(['login','group']);

        //проверим существует ли переданные пользователь и группа
        $FindUser = $this->issetUser($data['login']);
        $Group = new GroupController();
        $FindGroup = $Group->issetGroup($data['group']);

        //проверим, принадлежит ли уже пользователь этой группе
        $issetAttach = UsersToGroup::where('user_id', $FindUser['id'])
            ->where('group_id', $FindGroup['id'])->first();
        if ($issetAttach) {
            throw new CustomException(
                $data, false, 400,
                'The user "'.$data['login'].'" already belongs to the group "'.
                $data['group'].'"'
            );
        }
        //добавляем пользователя к группе
        $result = ($FindUser->groups()->attach($FindGroup['id'])) ? false : true ;

        if ($result) {
            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_attachusertogroup',
                'Добавление пользователя ' . $FindUser->login .
                '('.$FindUser->id.')' . ' к группе ' . $FindGroup->name .
                '('.$FindGroup->id.')',
                'users', $FindUser->id,
                [
                    'data' => [
                        'user' => self::modelFilter($FindUser, User::fields()),
                        'group' => $FindGroup,
                    ]
                ]
            );
        }

        return parent::response($data, $result, 200);
    }

    /**
     * Удаляет пользователя с логином login из группы с кодом group
     * Параметры POST запроса:
     * login - Логин пользователя
     * group - Символьный код группы
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function detachUserGroup(Request $request)
    {
        User::can('user_detachuserfromgroup', true);
        $data = $request->only(['login','group']);

        //проверим существует ли переданные пользователь и группа
        $FindUser = $this->issetUser($data['login']);
        $Group = new GroupController();
        $FindGroup = $Group->issetGroup($data['group']);
        //удаляем пользователя из групппы
        $result = ($FindUser->groups()->detach($FindGroup['id'])) ? true : false;

        if ($result) {
            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_detachuserfromgroup',
                'Удаление пользваотеля ' . $FindUser->login .
                '('.$FindUser->id.')' . ' из группы ' . $FindGroup->name .
                '('.$FindGroup->id.')',
                'users', $FindUser->id,
                [
                    'data' => [
                        'user' => self::modelFilter($FindUser, User::fields()),
                        'group' => self::modelFilter($FindGroup, Group::fields()),
                    ]
                ]
            );
        }

        return parent::response($data, $result, 200);
    }


    /**
     * Возвращает список пользователей в системе
     * Параметры GET запроса:
     * page - номер страницы для отображения постраничной навигации
     * count - количество элементов для отображения на странице
     * order_by - поле для сортировки (одно из полей массива ModelName::fields())
     * order_type - направление сортировки (asc/desc)
     * filter - фильтрация выборки по полям модели
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function getUsers(Request $request)
    {
        User::can('user_viewusers', true);
        $data = $request->only('count', 'page');
        $result = [];
        $result = parent::dbGet(new User, $request, [], [], true);


        if (isset($data['count']) && $data['count']) {
            $result = $result->with('groups')->paginate($data['count']);
            $users  = $result->items();
        } else {
            $result = $result->with('groups')->get();
            $users  = $result;
        }



        foreach ($users as &$user) {
            $user->fullGroup = $user->groups;
            $groups = $user->groups->pluck('id');
            unset($user->groups);
            $user->groups = $groups;
        } unset($user);


        if (isset($data['count']) && $data['count']) {
            $result = $result->toArray();
            $result['data'] = $users;
        } else {
            $result = $users;
        }

        return parent::response($request->all(), $result, 200);
    }

    /**
     * Возвращает пользователя по его логину
     *
     * @param string $login - логин пользователя
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getUserByLogin($login)
    {

        $State = State::getInstance();
        $CurrentUser = $State->getUser();
        //если пользователь просматиривает не свои данные
        if ($CurrentUser['login'] != $login) {
            User::can('user_viewusers', true);
        }

        $FindUser = User::where('login', $login)->first();
        if (!$FindUser) {
            throw new CustomDBException($login, [], 404, 'Not found');
        }
        return parent::response($login, $FindUser, 200);
    }

    /**
     * Возвращает пользователя по его email
     *
     * @param string $email - email пользователя
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getUserByEmail($email)
    {

        $State = State::getInstance();
        $CurrentUser = $State->getUser();
        //если пользователь просматиривает не свои данные
        if ($CurrentUser['email'] != $email) {
            User::can('user_viewusers', true);
        }

        $FindUser = User::where('email', $email)->first();
        if (!$FindUser) {
            throw new CustomDBException($email, [], 404, 'Not found');
        }
        return parent::response($email, $FindUser, 200);
    }

    /**
     * Возвращает пользователя по его id
     *
     * @param integer $id   - id пользователя
     * @param boolean $json - формат ответа
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getUserById($id, $json = false)
    {
        $State = State::getInstance();
        $CurrentUser = $State->getUser();
        //если пользователь просматиривает не свои данные
        if ($CurrentUser['id'] != $id) User::can('user_viewusers', true);


        $FindUser = User::where('id', $id)->with('heroImage', 'groups')->first();
        if (!$FindUser) throw new CustomDBException($id, [], 404, 'Пользователь не найден');



        $FindUser['isAdmin'] = $this->isAdmin($FindUser->login, false);
        $Roles = []; $CompaniesToAddProduct = [];
        $Invites = Invites::where('user_id', $FindUser->id)->where('invite', 2)->with('company')->get();
        $Companies = $FindUser->companies()->get();
        foreach ($Invites as $invite) {
            $Roles[$invite->company_id] = $invite->role;
            if (in_array($invite->role, ['admin', 'manager'])) {
                $CompaniesToAddProduct[$invite->company->id] = $invite->company;
            }
        }
        foreach ($Companies as $company) {
            $Roles[$company->id] = 'admin';
            $CompaniesToAddProduct[$company->id] = $company;
        }
        $FindUser->roles = $Roles;
        $FindUser->companies_to_add_products = array_values($CompaniesToAddProduct);




        return ($json) ? $FindUser : parent::response($id, $FindUser, 200) ;
    }

    /**
     * Удаляет пользователя по логину
     *
     * @param string $login - логин пользователя
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeUserByLogin($login)
    {
        User::can('user_removeusers', true);

        $User = User::where('login', $login)->first();

        if (!$User) {
            throw new CustomException(
                $login, [], 404,
                'User is not defined'
            );
        }
        $result = $User->delete();

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_removeusers', null, 'users', $User->id,
            ['data' => self::modelFilter($User, User::fields())]
        );

        return parent::response($login, $result, 200);
    }

    /**
     * Удаляет пользователя по email
     *
     * @param string $email - email пользователя
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeUserByEmail($email)
    {
        User::can('user_removeusers', true);

        $User = User::where('email', $email)->first();
        if (!$User) {
            throw new CustomException(
                $email, [], 404,
                'User is not defined'
            );
        }
        $result = $User->delete();

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_removeusers', null, 'users', $User->id,
            ['data' => self::modelFilter($User, User::fields())]
        );

        return parent::response($email, $result, 200);
    }

    /**
     * Удаляет пользователя по id
     *
     * @param integer $id - id пользователя
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeUserById($id)
    {
        User::can('user_removeusers', true);

        $User = User::where('id', $id)->first();
        if (!$User) {
            throw new CustomException(
                $id, [], 404, 'User is not defined'
            );
        }

        $result = $User->delete();

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_removeusers', null, 'users', $id,
            ['data' => self::modelFilter($User, User::fields())]
        );

        return parent::response($id, $result, 200);
    }

    /**
     * Хеширует пароль пользователя
     *
     * @param string $password - пароль пользователя в незашифрованном виде
     *
     * @return bool|string
     */
    public function encryptPassword($password)
    {
        return password_hash($password.config("app.encrypt_salt"), PASSWORD_BCRYPT);
    }

    /**
     * Сравнивает хеш пароля с ввеным паролем
     *
     * @param string $input_pass - пароль пользователя в незашифрованном виде
     * @param string $db_hash    - хеш пароля пользователя из базы данных
     *
     * @return bool
     */
    public function checkPassword($input_pass, $db_hash)
    {
        return password_verify($input_pass.config("app.encrypt_salt"), $db_hash);
    }

    /**
     * Вернёт массив ролей пользователя, если ролей нет, вернёт пустой массив
     *
     * @param string $userLogin - логин пользователя
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getUserGroup($userLogin)
    {
        $FindUser = User::where('login', $userLogin)->first();

        $State = State::getInstance();
        $CurrentUser = $State->getUser();
        if ($CurrentUser['login'] != $FindUser['login']) {
            User::can('user_viewgroup', true);
        }

        if (!$FindUser) {
            throw new CustomDBException(
                $userLogin, [], 404,
                'User with login "'.$userLogin.'" is not found'
            );
        }

        $groups = $FindUser->groups()->get();

        return parent::response($userLogin, $groups, 200);
    }

    /**
     * Проверяем существование пользователя с переданным логином
     * возвращает экземпляр класса User в случае удачи
     * в случае неудачи, выкидывает исключение
     *
     * @param string $login - логин пользователя
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function issetUser($login)
    {
        $FindUser = User::where('login', $login)->first();
        if (!$FindUser) {
            throw new CustomDBException(
                $login, [], 404,
                'User with login "'.$login.'" is not found'
            );
        }
        return $FindUser;
    }

    /**
     * Функция для восстановления пароля пользователя
     * Записывает токен восстановления в БД и
     * отправляет email с ссылкой пользователю
     *
     * @param string $email - логин пользователя
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function sendReturnEmail($email)
    {

        //найдём пользователя с переданным логином
        $user = User::where('email', $email)->first();

        //если пльзователя не существует
        if (!$user || $user == []) {
            throw new CustomDBException($email, [], 404, 'Пользователь с таким email не найден');
        }

        //генерируем токен для восстановления пароля
        $Auth = new AuthController();
        $token = $Auth->generateAuthToken();

        //удалим все предыдущие запросы на восстановление
        //от этого пользователя
        $oldRequest = ResetPassword::where('user_id', $user['id'])->delete();

        //запишем в БД факт запроса восстановления
        $ResetPassword = new ResetPassword();
        $ResetPassword->reset_token = $token;
        $ResetPassword->user()->associate($user['id']);
        $TokenSave = $ResetPassword->save();

        //отправляем письмо с восстановлением пароля
        $sendMail = Mail::send(
            'emails.reset_password',
            [
                'name' => $user['login'],
                'link' => env('RESET_PASSWORD_URL') .
                    '?user='.$user['login'] . '&token=' . $token
            ],
            function ($m) use ($user) {
                $m->to($user->email, $user->login)->subject('test email message');
            }
        );


        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_getresetemail',
            null, 'users', $user->id
        );


        return parent::response(
            $email,
            [
                'delete_old_request' => $oldRequest,
                'token_save' => $TokenSave,
                'send_mail' => ($sendMail == null) ? true : $sendMail,
            ],
            200
        );
    }

    /**
     * Сбрасывает пароль пользователя и отправляет
     * ему email с новым паролем
     *
     * @param $email
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function breakPassword($email) {
        $user = User::where('email', $email)->first();

        //если пльзователя не существует
        if (!$user || $user == []) {
            throw new CustomDBException($email, [], 404, 'Пользователь с таким email не найден');
        }
        $password = $this->getRandomString();
        $sendMail = Mail::send(
            'emails.break_password', [
                'email'     => $user['email'],
                'password' => $password,
                'url'      => env('FE_URL')
            ],
            function ($m) use ($user) {
                $m->to($user->email, $user->login)
                    ->subject(env('SITE_NAME', '2Гид') . ': Восстановление пароля');
            }
        );

        if ($sendMail) {
            $user->password = $this->encryptPassword($password);
            $user->save();

            LoggerController::write(
                $this->getModuleName(), 'user_breakpassword',
                null, 'users', $user->id
            );
        }
        return parent::response($email, $sendMail, 200);
    }

    /**
     * Изменяет пароль по запросу пользователя
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function changePassword(Request $request) {
        $data = $request->only('old_password', 'password', 'password_confirm');
        $User = State::User();
        if (!$User) throw new CustomException($data, [], 403);
        $User = User::where('id', $User['id'])->first();
        if (!$this->checkPassword($data['old_password'], $User['password'])) {
            throw new CustomException(
                array_merge($data, [
                    $User['password'],
                    $this->encryptPassword($data['password'])
                ]), [], 404, 'Старый пароль указан неверно.'
            );
        }
        if ($data['password'] !== $data['password_confirm']) {
            throw new CustomException($data, [], 404, 'Новые пароли не совпадают.');
        }

        $User->password = $this->encryptPassword($data['password']);
        $User->save();
        return parent::response($data, State::User(), 200);
    }

    /**
     * Устанавливает новый пароль пользователя
     * Параметры POST запроса:
     * token - токен смены паролья
     * password - новый проль
     * password_confirm - новый пароль повторно
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     *
     * @throws CustomDBException
     * @throws CustomException
     * @throws CustomValidationException
     */
    public function resetPassword(Request $request)
    {
        $data = $request->only('token', 'password', 'password_confirm');

        $TokenData = ResetPassword::where('reset_token', $data['token'])->first();

        //если токен не существует
        if (!$TokenData || $TokenData == []) {
            throw new CustomDBException(
                $TokenData, [], 404, 'Reset token not found'
            );
        }

        //время жизни запроса на восстановление
        $TokenTime = env('RESET_PASSWORD_TIME');
        //если прошедшее время превышает время жизни запроса
        if ((time() - strtotime($TokenData['created_at'])) > $TokenTime) {
            throw new CustomException(
                $TokenData, [], 400, 'Request lifetime has expired.' .
                ' Maximum lifetime is ' . $TokenTime . 's.'
            );
        }

        //проверим переданные пароли
        $validatorRules = [
            'password' => 'required',
            'password_confirm' => 'required|same:password',
        ];
        $validator = Validator::make((array)$data, $validatorRules);
        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator, 'Password data validation error', $data
            );
        }

        //получим пользователя, который пытается сменить пароль
        $user = $TokenData->user()->first();
        //проверим, не пытается ли пользователь установить тот же самый пароль
        $SameOldPass = $this->checkPassword($data['password'], $user['password']);
        //если пользователь пытается установить тот же самый пароль
        if ($SameOldPass) {
            throw new CustomException(
                $TokenData, [], 400,
                'The new password can not be the same as the old password'
            );
        }
        //хешируем новый пароль
        $newPassword = $this->encryptPassword($data['password']);
        //меняем пароль пользователя в БД
        $user->password = $newPassword;
        $changePass = $user->save();

        //удялем из БД запрос на смену пароля
        ResetPassword::where('reset_token', $data['token'])->delete();

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_resetpassword',
            null, 'users', $user->id
        );

        return parent::response(
            $data, ($changePass == null) ? true : $changePass, 200
        );

    }
}