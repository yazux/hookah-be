<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\User\Model\Group;
use App\Modules\User\Model\User;
use App\Modules\Module\Model\Module;
use App\Modules\Module\Controllers\ModuleController as ModuleCTR;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Model\Action;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

/**
 * Класс для работы с действиями пользователей в модулях
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class ActionController extends Controller implements ModuleInterface
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
     * Возвращает все действия в базе
     * Параметры GET запроса:
     * page - номер страницы для отображения постраничной навигации
     * count - количество элементов для отображения на странице
     * order_by - поле для сортировки (одно из полей массива ModelName::fields())
     * order_type - направление сортировки (asc/desc)
     *
     * @param Request $request - экзампляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function getActions(Request $request)
    {
        User::can('user_viewactions', true);

        return parent::response(
            $request->all(),
            parent::dbGet(new Action, $request),
            200
        );
    }

    /**
     * Возвращает действие по его id
     *
     * @param integer $id - ID действия в базе данных
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getActionById($id)
    {
        User::can('user_viewactions', true);

        //проверим существование такого действия
        $Action = Action::where('id', $id)->first();
        if (!$Action) {
            throw new CustomDBException(
                $id, [], 404,
                'Actions with id "'.$id.'"'.
                '" is not find'
            );
        }

        return parent::response($id, $Action, 200);
    }

    /**
     * Возвращает действие с кодом $code для модуля
     * с кодом $module_code
     *
     * @param string $code        - символьный код действия
     * @param string $module_code - символьный код модуля
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getAction($code, $module_code)
    {
        User::can('user_viewactions', true);

        $data = [
            'code' => $code,
            'module_code' => $module_code
        ];

        //проверим существование модуля с переданным кодом
        $Module = Module::where('code', $module_code)->first();
        if (!$Module) {
            throw new CustomDBException(
                $data, [], 404,
                'Module with code "'.$module_code.'" is not find'
            );
        }

        //проверим существование действия с данным модулем
        $Actions = Action::where('name', $code)
            ->where('module_id', $Module['id'])
            ->first();
        if (!$Actions) {
            throw new CustomDBException(
                $data, [], 404,
                'Actions "'.$code.'" '.
                'width module code "'.$module_code.'"'.
                '" is not find'
            );
        }

        return parent::response($data, $Actions, 200);
    }

    /**
     * Создаёт в БД запись с разрешеним на какое-либо действие
     * для конкретной группы пользователей
     * Параметры POST запроса:
     * name - символьный код действия, указанный в конфигах модуля
     * description - описание действия, можно брать из конфигов модуля
     * group_code - символьный код группы пользователей
     * module_code - символьный код модуля действия
     * sort - сортировка
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function createAction(Request $request)
    {
        User::can('user_addactions', true);

        $data = $request->only(
            ['name','description','group_code','module_code','sort']
        );

        $NamePrefix = substr($data['name'], 0, strlen($data['module_code']));

        if ($NamePrefix != $data['module_code']) {
            throw new CustomException(
                $data, [], 400,
                'Action name must begin with the prefix '.
                'of the module name to which it refers: "'.
                $data['module_code'].'_"'
            );
        }

        $newAction = new Action();
        if ($newAction->actionValidator($data)) {

            $IssetGroupModule = $this->_checkIssetGroupModule(
                [
                    'group_code' => $data['group_code'],
                    'module_code' => $data['module_code']
                ]
            );

            //проверим, есть ли в конфигах модуля такое действие
            $ModuleCTR = new ModuleCTR();
            if (!$ModuleCTR->issetAction($data['module_code'], $data['name'])) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Actions "'.$data['name'].'" '.
                    'is not find in module "'.$data['module_code'].'"'
                );
            }

            //проверим существование такого же действия
            //с данным модулем и этой группой
            $Actions = Action::where('name', $data['name'])
                ->where('module_id', $IssetGroupModule['module']['id'])
                ->where('group_id', $IssetGroupModule['group']['id'])
                ->first();
            if ($Actions) {
                throw new CustomDBException(
                    $data, [], 400,
                    'Actions "'.$data['name'].'" '.
                    'width module code "'.
                    $IssetGroupModule['module']['code'].'" and '.
                    'group code "'.$IssetGroupModule['group']['code'].
                    '" is already added'
                );
            }

            $newAction->name = $data['name'];
            $newAction->description = $data['description'];
            $newAction->sort = ($data['sort']) ? $data['sort'] : 100;
            $newAction->module()->associate($IssetGroupModule['module']);
            $newAction->group()->associate($IssetGroupModule['group']);
            $newAction->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_addactions',
                null, 'action', $newAction->id
            );

            $result = true;
        } else {
            $result = false;
        }

        return parent::response($data, $result, 200);
    }

    /**
     * Обновляет в БД запись с разрешеним на какое-либо действие
     * для конкретной группы пользователей
     * Параметры POST запроса:
     * name - символьный код действия, указанный в конфигах модуля
     * description - описание действия, можно брать из конфигов модуля
     * group_code - символьный код группы пользователей
     * module_code - символьный код модуля действия
     * sort - сортировка
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function updateAction(Request $request)
    {
        User::can('user_putactions', true);

        $data = $request->only(
            ['name','description','group_code','module_code','sort']
        );
        $newAction = new Action();

        if ($newAction->actionValidator($data)) {

            $IssetGroupModule = $this->_checkIssetGroupModule(
                [
                    'group_code' => $data['group_code'],
                    'module_code' => $data['module_code']
                ]
            );

            //проверим, есть ли в конфигах модуля такое действие
            $ModuleCTR = new ModuleCTR();
            if (!$ModuleCTR->issetAction($data['module_code'], $data['name'])) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Actions "'.$data['name'].'" '.
                    'is not find in module "'.$data['module_code'].'"'
                );
            }

            //проверим существование такого же действия
            //с данным модулем и этой группой
            $Actions = Action::where('name', $data['name'])
                ->where('module_id', $IssetGroupModule['module']['id'])
                ->where('group_id', $IssetGroupModule['group']['id'])
                ->first();
            if (!$Actions) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Actions "'.$data['name'].'" '.
                    'width module code "'.
                    $IssetGroupModule['module']['code'].'" and '.
                    'group code "'.$IssetGroupModule['group']['code'].
                    '" is not find'
                );
            }
            $oldActions = clone $Actions;
            $Actions->name = $data['name'];
            $Actions->description = $data['description'];
            $Actions->sort = ($data['sort']) ? $data['sort'] : 100;
            $Actions->module()->associate($IssetGroupModule['module']);
            $Actions->group()->associate($IssetGroupModule['group']);
            $Actions->save();

            $result = $Actions;

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_putactions',
                null, 'action', $Actions->id, false, [$oldActions, $Actions]
            );

        } else {
            $result = false;
        }

        return parent::response($data, $result, 200);
    }


    /**
     * Удаляет разрешение на действие для группы пользователей
     * Параметры POST запроса:
     * name - символьный код действия, указанный в конфигах модуля
     * group_code - символьный код группы пользователей
     * module_code - символьный код модуля действия
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function removeAction(Request $request)
    {
        User::can('user_removeactions', true);

        $data = $request->only(['name','group_code','module_code']);
        $newAction = new Action();
        if ($newAction->removeActionValidator($data)) {
            $IssetGroupModule = $this->_checkIssetGroupModule(
                [
                    'group_code' => $data['group_code'],
                    'module_code' => $data['module_code']
                ]
            );

            //проверим существование такого же действия
            //с данным модулем и этой группой
            $Actions = Action::where('name', $data['name'])
                ->where('module_id', $IssetGroupModule['module']['id'])
                ->where('group_id', $IssetGroupModule['group']['id'])->first();
            if (!$Actions) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Actions "'.$data['name'].'" '.
                    'width module code "'.
                    $IssetGroupModule['module']['code'].'" and '.
                    'group code "'.$IssetGroupModule['group']['code'].
                    '" is not find'
                );
            }

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_removeactions',
                null, 'action', $Actions->id
            );

            $Actions = $Actions->delete();
            $result = ($Actions) ? true : false;

        } else {
            $result = false;
        }

        return parent::response($data, $result, 200);
    }

    /**
     * Проверяет существует ли группа и модуль
     * по переданным id или кодам
     * в $data передаётся массив
     * вида: ['group_code' => '', 'module_code' => '']
     * либо: ['group_id' => '', 'module_id' => '']
     * Вид массива контролитруется параметром $onId
     *
     * @param array $data - массив c id или code группы и модуля
     * @param bool  $onId - true если нужен поиск по id и false если по коду
     *
     * @return array
     * @throws CustomDBException
     */
    private function _checkIssetGroupModule($data, $onId = false)
    {
        $Group = false;
        $Module = false;
        if (!$onId) {
            //проверим существование группы с переданным кодом
            $Group = Group::where('code', $data['group_code'])->first();
            if (!$Group) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Group with code "'.$data['group_code'].'" is not find'
                );
            }
            //проверим существование модуля с переданным кодом
            $Module = Module::where('code', $data['module_code'])->first();
            if (!$Module) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Module with code "'.$data['module_code'].'" is not find'
                );
            }
        } else {
            //проверим существование группы с переданным id
            $Group = Group::where('id', $data['group_id'])->first();
            if (!$Group) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Group with id "'.$data['group_id'].'" is not find'
                );
            }
            //проверим существование модуля с переданным id
            $Module = Module::where('id', $data['module_id'])->first();
            if (!$Module) {
                throw new CustomDBException(
                    $data, [], 404,
                    'Module with id "'.$data['module_id'].'" is not find'
                );
            }
        }

        return [
            'group' => $Group,
            'module' => $Module
        ];
    }

}