<?php

namespace App\Modules\Module\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\State;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Module\Model\Module;
use App\Modules\Module\Model\ModulePropVal;
use App\Modules\User\Controllers\UserController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\Action;
use App\Modules\User\Model\User;
use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Modules\Properties\Controllers\FileController as File;

/**
 * Класс для работы с модулями системы
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class ModuleController extends Controller implements ModuleInterface
{

    /**
     * Код модуля
     *
     * @var string
     */
    public $moduleName = 'Module';

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getModulePublicActions($moduleName, $jsonResponse = true)
    {
        $config = parent::getConfig($moduleName);
        if (isset($config['public_actions'])) {
            $actions = $config['public_actions'];
            if ($jsonResponse) {
                return parent::response($moduleName, $actions, 200);
            } else {
                return $actions;
            }
        }
        if ($jsonResponse) {
            return parent::response($moduleName, [], 200);
        } else {
            return [];
        }
    }

    public function getUserAccess(Request $request) {
        $data = $request->only('group_id');
        $result = []; $modules = [];

        $result['modules'] = Module::where('id', '!=', '')->get();

        foreach ($result['modules'] as &$module) {
            $config = $this->getConfig($module->code);
            if (isset($config['actions']) && is_array($config['actions'])) {
                $actions = [];
                foreach ($config['actions'] as &$action) {
                    $action['db'] = Action::where('name', $action['code'])
                        ->where('module_id', $module->id)
                        ->where('group_id', $data['group_id'])->select('id', 'name')->first();
                    if (!$action['db']) {

                        $actionNew = new Action();
                        $actionNew->name = $action['code'];
                        $actionNew->description = $action['name'];
                        $actionNew->sort = 100;
                        $actionNew->module()->associate($module->id);
                        $actionNew->group()->associate($data['group_id']);
                        $actionNew->save();

                        $actions[] = $actionNew;
                    }
                } unset($action);

                if (count($actions)) {
                    $module->actions = $actions;
                    $modules[] = $module;
                }
            }
        } unset($module);

        $result['modules'] = $modules;

        return parent::response(['data' => $data], $result, 200);
    }

    /**
     * Проверяет, существует ли в конфигах
     * сущность с кодом $entityCode для модуля с кодом $moduleCode
     *
     * @param string $moduleCode - символьный код модуля
     * @param string $entityCode - символьный код сущности
     *
     * @return mixed
     */
    public function issetEntity($moduleCode, $entityCode)
    {
        $EntityList = $this->getModuleEntity($moduleCode, false);
        $find = false;

        foreach ($EntityList as $Entity) {
            if ($find) {
                continue;
            }
            if ($Entity['code'] == $entityCode) {
                $find = $Entity;
            }
        }
        return $find;
    }

    /**
     * Проверяет, существует ли в конфигах
     * действие с кодом $actionCode для модуля с кодом $moduleCode
     *
     * @param string $moduleCode - символьный код модуля
     * @param string $actionCode - символьный код действия
     *
     * @return mixed
     */
    public function issetAction($moduleCode, $actionCode)
    {
        $ActionList = $this->getModuleActions($moduleCode, false);
        $find = false;

        foreach ($ActionList as $Action) {
            if ($find) {
                continue;
            }
            if ($Action['code'] == $actionCode) {
                $find = $Action;
            }
        }
        return $find;
    }

    /**
     * Проверяет существует ли запись о модуле в БД
     * Вернёт массив с записями подуля если существует или false если нет
     * Если запись существует, но модуль не активен, вернёт false
     * Так же проверяет, загрузился ли модуль, если нет, вернёт false
     *
     * @param string $module_code - символьный код модуля
     *
     * @return bool
     */
    public function exist($module_code)
    {
        $module_code = strtolower($module_code);
        $Module = new Module();
        //найдём модуль
        $Module = $Module::where('code', $module_code)->first();

        //если записи о модуле нет
        if (!$Module) {
            return false;
        }
        //если модуль не активен
        if (intval($Module['active']) != 1) {
            return false;
        }

        //вытащим конфиги модуля
        $ModuleConfig = $this->getConfig($module_code);
        //проверим, загружен ли модуль
        if (!array_key_exists('load', $ModuleConfig)
            || !$ModuleConfig['load']
        ) {
            return false;
        }

        return $Module;
    }

    /**
     * Создаёт запись о новом модуле в таблице Modules
     * Параметры POST запроса:
     * code - символьный код модуля
     * description - описание модуля
     * sort - сортировка
     * active - активность
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function createModule(Request $request)
    {
        User::can('module_createmodule', true);

        $data = $request->only(['code','description','sort','active']);
        $Module = new Module();
        $result = $Module->CreateModule($data);

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'module_createmodule',
            null, 'module', $result->id,
            ['data' => self::modelFilter($result, Module::fields())]
        );

        return parent::response($data, $result, 200);
    }

    /**
     * Удаляет запись о модуле в таблице Modules
     *
     * @param string $code - символьный код модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeModuleByCode($code)
    {
        User::can('module_removeemodule', true);

        $Module = new Module();
        $IssetModule = Module::where('code', $code)->first();

        if (!$IssetModule) {
            throw new CustomException(
                'Not found', [], 404, 'Module not found'
            );
        }

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'module_removeemodule',
            null, 'module', $Module->id,
            ['data' => self::modelFilter($Module, Module::fields())]
        );

        $result = $Module->RemoveModuleByCode($code);

        return parent::response($code, $result, 200);
    }

    /**
     * Возвращает список всех модулей
     * Параметры GET запроса:
     * page - номер страницы для отображения постраничной навигации
     * count - количество элементов для отображения на странице
     * order_by - поле для сортировки (одно из полей массива ModelName::fields())
     * order_type - направление сортировки (asc/desc)
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModules(Request $request)
    {
        User::can('module_viewemodule', true);

        $result = parent::dbGet(new Module, $request);

        if (array_key_exists('data', $result)) {
            $Modules = $result['data'];
        } else {
            $Modules = $result;
        }

        foreach ($Modules as &$Module) {
            $ModuleConfig = $this->getConfig($Module['code']);
            $Module['user_access'] = $ModuleConfig['user_access'];
            $Module['load'] = $ModuleConfig['load'];
        } unset($Module);

        if (array_key_exists('data', $result)) {
            $result['data'] = $Modules;
        } else {
            $result = $Modules;
        }

        return parent::response($request->all(), $result, 200);
    }

    /**
     * Возвращает модуль по его коду
     *
     * @param string $code - символьный код модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleByCode($code)
    {
        User::can('module_viewemodule', true);

        $Module = new Module();
        $result = $Module->GetModuleByCode($code);
        return parent::response($code, $result, 200);
    }

    /**
     * Обновляет свойства модуля
     * Параметры POST запроса:
     * code - символьный код модуля
     * description - описание модуля
     * sort - сортировка
     * active - активность
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putModuleByCode(Request $request)
    {
        User::can('module_putemodule', true);
        $data = $request->only(['code','new_code','description','sort','active']);
        $Module = new Module();
        $oldModule = Module::where('code', $data['code'])->first();
        $result = $Module->PutModuleByCode($data);

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'module_putemodule',
            null, 'module', $result->id,
            ['data' => self::modelFilter($result, Module::fields())],
            [$oldModule, $result]
        );

        return parent::response($data, $result, 200);
    }

    /**
     * Вернет список свойсв модуля
     * Если результат требуется обработать на бэкенде,
     * то передать $jsonResponse = false
     * В противном случае вернет экзампляр response()->json();
     *
     * @param string $moduleName   - символьный код модуля
     * @param bool   $jsonResponse - true/false
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleProperties($moduleName, $jsonResponse = true)
    {
        User::can('module_viewemoduleprop', true);

        //ищем модуль с переданным кодом в базе
        $Module = Module::where('code', $moduleName)->first();
        if (!count($Module)) {
            throw new CustomDBException(
                $moduleName, false, 404, 'Module not found'
            );
        }
        //получем все возможные свойства модуля из конфига
        $config = parent::getConfig($moduleName);
        if ($config['properties']) {
            $props = $config['properties'];
            //вытаскиваем из БД значение свойст
            $DBProps = ModulePropVal::where('module_id', $Module['id'])->get();

            $values = [];
            foreach ($DBProps as &$value) {
                $values[$value['code']][] = $value['value'];
            } unset($value);


            //дописываем в выбору значения свойст, которые есть в БД
            foreach ($props as &$prop) {
                if (isset($values[$prop['code']])) {
                    if ($prop['multiply']) {
                        $prop['value'] = $values[$prop['code']];
                    } else {
                        $prop['value'] = current($values[$prop['code']]);
                        if ($prop['type'] == 'file') {
                            $File = new File();
                            $prop['value'] = $File->getFileById($prop['value'], false);
                        }
                    }
                } else {
                    $prop['value'] = null;
                }
            } unset($prop);

            //отдаём ответ на клиент
            if ($jsonResponse) {
                return parent::response($moduleName, $props, 200);
            } else {
                return $props;
            }
        }

        return parent::response($moduleName, [], 200);
    }

    /**
     * Вернет список действий модуля
     * Если результат требуется обработать на бэкенде,
     * то передать $jsonResponse = false
     * В противном случае вернет экзампляр response()->json();
     *
     * @param string $moduleName   - символьный код модуля
     * @param bool   $jsonResponse - true/false
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleActions($moduleName, $jsonResponse = true)
    {
        User::can('module_viewemoduleactions', true);

        $config = parent::getConfig($moduleName);
        if (isset($config['actions'])) {
            $actions = $config['actions'];
            if ($jsonResponse) {
                return parent::response($moduleName, $actions, 200);
            } else {
                return $actions;
            }
        }
        throw new CustomException(
            $moduleName,
            $config, 404, "Actions is not defined in config file"
        );
    }

    /**
     * Возвращает список групп,
     * которым разрешено выполнять переданное действие
     *
     * @param string $moduleName - Код модуля
     * @param string $actionName - Код действия
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleActionGroup($moduleName, $actionName)
    {
        User::can('module_viewemoduleactions', true);

        $Module = new Module();
        $module = $Module->GetModuleByCode($moduleName);

        $groups = [];
        $groups = Action::where('module_id', $module->id)
            ->select('id', 'name', 'group_id')
            ->where('name', $actionName)->with('group')->get()->pluck('group');

        return parent::response(
            ['module_name' => $moduleName, 'action_name' => $actionName],
            $groups,
            200
        );
    }

    /**
     * Вернет список сущностей модуля
     * Если результат требуется обработать на бэкенде,
     * то передать $jsonResponse = false
     * В противном случае вернет экзампляр response()->json();
     *
     * @param string $moduleName   - символьный код модуля
     * @param bool   $jsonResponse - true/false
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleEntity($moduleName, $jsonResponse = true)
    {
        User::can('module_viewemoduleentity', true);

        $config = parent::getConfig($moduleName);
        if (isset($config['entity'])) {
            $actions = $config['entity'];
            if ($jsonResponse) {
                return parent::response($moduleName, $actions, 200);
            } else {
                return $actions;
            }
        }
        throw new CustomException(
            $moduleName,
            $config, 404, "Entity is not defined in config file"
        );
    }


    /**
     * Вернёт весь массив конфигов
     * На вход принимает символьный код модуля
     *
     * @param string $moduleName - симольный код модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModuleConfig($moduleName)
    {
        User::can('module_viewemoduleconfig', true);

        $config = parent::getConfig($moduleName);
        return parent::response($moduleName, $config, 200);
    }

    /**
     * Возвращает значение свойсва модуля
     * На вход принимает символьный код модуля и код свойства
     *
     * @param string $moduleName - символьный код модуля
     * @param string $propCode   - симольный код свойства модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function getModulePropertyValue($moduleName, $propCode)
    {
        User::can('module_viewemoduleprop', true);

        $ModuleProp = new ModulePropVal();
        $result = $ModuleProp->GetModulePropertyValue($moduleName, $propCode);
        return parent::response(
            ['module_name' => $moduleName, 'prop_code' => $propCode],
            $result, 200
        );
    }

    /**
     * Добавляет новое значение свойства модуля
     * Параметры POST запроса:
     * module_name - символьный код модуля
     * prop_code   - символьный код свойства модуля
     * value       - значение свойства модуля
     * multiply    - являетя ли свойство множественным
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function setModulePropertyValue(Request $request)
    {
        User::can('module_setmoduleprop', true);
        $data = $request->only(['module_name', 'prop_code', 'value', 'multiply']);

        $Module = new Module();
        $Module = $Module->GetModuleByCode($data['module_name']);
        if (!$Module) {
            throw new CustomException(
                $data, $Module, 404,
                "Module '" . $data['module_name'] . "' is not defined"
            );
        }

        //получим все определённые в конфиге свойства данного модуля
        $ModuleProperties = $this->GetModuleProperties($data['module_name'], false);

        //если у него нет запрошеного свойства, выбросим исключение
        if (!array_key_exists($data['prop_code'], $ModuleProperties)) {
            throw new CustomException(
                $data, $ModuleProperties, 404,
                "Property '".$data['prop_code']."' is not defined in module '".
                $data['module_name']."'"
            );
        }

        $moduleProperty = $this->getModuleProperties($data['module_name'], false);
        if (!$moduleProperty[$data['prop_code']]) {
            throw new CustomException($data, false, 404, 'Property not found');
        }
        $moduleProperty = $moduleProperty[$data['prop_code']];

        $ModuleProp = new ModulePropVal();
        //отправляем в модель полученные данные
        $result = $ModuleProp->SetModulePropertyValue(
            $data, $ModuleProperties[$data['prop_code']], $moduleProperty, $request
        );


        return parent::response($data, $result, 200);
    }

    /**
     * Обновляет значение свойства модуля
     * Параметры POST запроса:
     * module_name - символьный код модуля
     * prop_code   - символьный код свойства модуля
     * value       - значение свойства модуля
     * multiply    - являетя ли свойство множественным
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putModulePropertyValue(Request $request)
    {
        User::can('module_putmoduleprop', true);

        $data = $request->only(['module_name', 'prop_code', 'value', 'multiply']);

        $Module = new Module();
        $Module = $Module->GetModuleByCode($data['module_name']);
        if (!$Module) {
            throw new CustomException(
                $data, $Module, 404,
                "Module '" . $data['module_name'] . "' is not defined"
            );
        }


        //получим все определённые в конфиге свойства данного модуля
        $ModuleProperties = $this->GetModuleProperties($data['module_name'], false);

        //если у него нет запрошеного свойства, выбросим исключение
        if (!array_key_exists($data['prop_code'], $ModuleProperties)) {
            throw new CustomException(
                $data,
                $ModuleProperties,
                404,
                "Property '".$data['prop_code']."' is not defined in module '".
                $data['module_name']."'"
            );
        }

        //отправляем в модель полученные данные
        $ModuleProp = new ModulePropVal();
        $result = $ModuleProp->SetModulePropertyValue(
            $data, $ModuleProperties[$data['prop_code']], true
        );


        return parent::response($data, $result, 200);
    }

    /**
     * Удаляет значение свойства модуля по коду модуля и коду свойства
     *
     * @param string $moduleName - символьный код модуля
     * @param string $propCode   - символьный код свойства модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeModulePropertyValue($moduleName, $propCode)
    {
        User::can('module_removemoduleprop', true);

        $Module = new Module();
        $Module = $Module->GetModuleByCode($moduleName);
        if (!$Module) {
            throw new CustomException(
                [$moduleName, $propCode], $Module, 404,
                "Module '" . $moduleName . "' is not defined"
            );
        }

        $ModuleProp = new ModulePropVal();
        $result = $ModuleProp->RemoveModulePropertyValue($moduleName, $propCode);

        /*//логируем действие
        LoggerController::write(
            $this->getModuleName(), 'module_removemoduleprop',
            null, 'module', $Module->id,
            [
                'data' => [
                    'module' => self::modelFilter($Module, Module::fields()),
                    'property' => $result
                ]
            ]
        );*/

        return parent::response(
            ['module_name' => $moduleName, 'prop_code' => $propCode],
            $result, 200
        );
    }

}