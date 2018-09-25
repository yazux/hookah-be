<?php

namespace App\Modules\Properties\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\State;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Properties\Model\Properties;
use App\Modules\Properties\Model\PropertiesChoices;
use App\Modules\Properties\Model\PropertiesValues;
use App\Modules\Module\Model\Module;
use App\Modules\Module\Controllers\ModuleController as ModuleCTR;
use App\Modules\User\Controllers\UserController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Modules\Properties\Controllers\FileController as File;

/**
 * Класс для работы с свойствами сущностей в модулях
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class PropertiesController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Properties';

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
     * Удаляет свйоство с переданным ID
     *
     * @param integer $id - Идентификатор свойства
     *
     * @return mixed
     * @throws CustomException
     */
    public function deleteProperty($id)
    {
        User::can('properties_delete_property', true);

        $Property = new Properties();
        //проверим, существует ли такое свойство
        $Property = $Property::where('id', $id)->first();
        //если свойства нет выбрасываем исключение
        if (!$Property) {
            throw new CustomException(
                'Property is not defined', [], 400,
                'Property with id "'.$id.'" is not defined'
            );
        }

        //проверим, не дефолтное ли свойство пытается удалить пользователь
        $State = State::getInstance();
        $User = $State->getUser();
        //если пользователь пытается удалить дефолтное свойство
        if ($Property->default || $Property->default == '1') {
            //Если пользователь неявляется разработчиком
            //то недадим ему удалить такое свойство
            if ($User->login != env('DEVELOPER_LOGIN')) {
                throw new CustomException(
                    'add properties', [], 403,
                    'The current user does not have sufficient rights to' .
                    ' add properties. Only developers can remove default properties.'
                );
            }
        }

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'properties_delete_property',
            null, 'properties', $Property->id,
            ['data' => self::modelFilter($Property, Properties::fields())]
        );

        return parent::response(['id' => $id], $Property->delete(), 200);

    }

    /**
     * Создаёт новое свойство сущности в БД
     * Параметры POST запроса
     * string  name         - Имя свойства
     * string  code         - Символьный код свойства
     * int     sort         - Сортировка
     * string  module_code  - Символьный код модуля
     * string  field_entity - Сущность, к которой будет отновится свойство
     * string  field_type   - Тип свойства
     * boolean multiply     - Множественное ли свойство
     * boolean require      - Обязательное ли свойство
     * boolean active       - Активность свойства
     * boolean default      - Является ли свойство дефолтным (пользователи не могут их изменять)
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function postProperty(Request $request)
    {
        User::can('properties_add_property', true);
        $data = $request->only(Properties::fields());

        //проверим, не дефолтное ли свойство пытается добавить пользователь
        $State = State::getInstance();
        $User = $State->getUser();
        //если пользователь пытается добавить дефолтное свойство
        if ($data['default'] || $data['default'] == '1') {
            //Если пользователь неявляется разработчиком
            //то недадим ему добавить такое свойство
            if ($User->login != env('DEVELOPER_LOGIN')) {
                throw new CustomException(
                    'add properties', [], 403,
                    'The current user does not have sufficient rights to' .
                    ' add properties. Only developers can create default properties.'
                );
            }
        } else {
            $data['default'] = 0;
        }

        //return parent::response($data, $User, 200);

        //найдём модуль, который просит пользователь
        $Module = new Module();
        //метод выбросит исключение с ошибкой 404, если не найдёт
        //поэтому обрабатывать его не нужно
        $module = $Module->getModuleByCode($data['module_code']);
        $data['module_id'] = $module['id'];

        //проверим, существует ли запрашиваемая сущность в конфигах
        $ModuleCTR = new ModuleCTR();
        if (!$ModuleCTR->issetEntity($data['module_code'], $data['field_entity'])) {
            throw new CustomException(
                'Entity is not defined', [], 400,
                'Entity "'.$data['field_entity'].'" ' .
                'is not defined in module with code "'.$data['module_code'].'"'
            );
        }

        $Property = new Properties();
        $validate = $Property->propertiesValidator($data);
        if ($validate) {
            //проверим, есть ли у данного модуля и сущности поле с переданным кодом
            $issetProperty = $Property::where('module_id', $data['module_id'])
                ->where('field_entity', $data['field_entity'])
                ->where('code', $data['code'])->first();

            //если свойство в данном модуле у этой сущности уже есть
            //выбрасываем исключение
            if ($issetProperty) {
                throw new CustomException(
                    'Property already defined', [], 400,
                    'Property "'.$data['code'].'" is already defined in module ' .
                    ' with code "'.$data['module_code'].
                    '" and entity code "'.$data['field_entity'].'"'
                );
            }

            //добавляем новое свойство
            $Property->name = $data['name'];
            $Property->code = $data['code'];
            $Property->sort = $data['sort'];
            $Property->module()->associate($data['module_id']);
            $Property->field_entity = $data['field_entity'];
            $Property->field_type   = $data['field_type'];
            $Property->multiply     = $data['multiply'];
            $Property->require      = $data['require'];
            $Property->active       = $data['active'];
            $Property->default      = $data['default'];
            $Property->size         = $data['size'];
            $Property->show_in_filter = $data['show_in_filter'];

            if ($data['category_id']) {
                $Property->category()->associate($data['category_id']);
            }
            $Property->save();


            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_add_property',
                null, 'properties', $Property->id,
                ['data' => self::modelFilter($Property, Properties::fields())]
            );
        }

        return parent::response($data, $Property, 200);
    }

    /**
     * Создаёт новое свойство сущности в БД
     * Параметры PUT запроса
     * int     id           - ID свойства, которое нужно изменить
     * string  name         - Имя свойства
     * string  code         - Символьный код свойства
     * int     sort         - Сортировка
     * string  module_code  - Символьный код модуля
     * string  field_entity - Сущность, к которой будет отновится свойство
     * string  field_type   - Тип свойства
     * boolean multiply     - Множественное ли свойство
     * boolean require      - Обязательное ли свойство
     * boolean active       - Активность свойства
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putProperty(Request $request)
    {
        User::can('properties_put_property', true);
        $data = $request->only(Properties::fields());

        //найдём модуль, который просит пользователь
        $Module = new Module();
        //метод выбросит исключение с ошибкой 404, если не найдёт
        //поэтому обрабатывать его не нужно
        $module = $Module->getModuleByCode($data['module_code'], $data);
        $data['module_id'] = $module['id'];

        //проверим, существует ли запрашиваемая сущность в конфигах
        $ModuleCTR = new ModuleCTR();
        if (!$ModuleCTR->issetEntity($data['module_code'], $data['field_entity'])) {
            throw new CustomException(
                $data, [], 400,
                'Entity "'.$data['field_entity'].'" ' .
                'is not defined in module with code "'.$data['module_code'].'"'
            );
        }

        $Property = new Properties();

        //проверим, есть ли у данного модуля и сущности поле с переданным новым кодом,
        //тут не учитываем изменяемое свойство
        $issetProperty = $Property::where('module_id', $data['module_id'])
            ->where('field_entity', $data['field_entity'])
            ->where('id', '!=', $data['id'])
            ->where('code', $data['code'])->first();

        //если свойство в данном модуле у этой сущности уже есть
        //выбрасываем исключение
        if ($issetProperty) {
            throw new CustomException(
                $data, false, 400,
                'Property "'.$data['code'].'" is already defined in module ' .
                ' with code "'.$data['module_code'].
                '" and entity code "'.$data['field_entity'].'"'
            );
        }


        //проверим, существует ли такое свойство
        $issetProperty = $Property::where('id', $data['id'])->first();
        //если свойства нет выбрасываем исключение
        if (!$issetProperty) {
            throw new CustomException(
                $data, false, 400,
                'Property with id "'.$data['id'].'" is not defined'
            );
        }


        //проверим, не дефолтное ли свойство пытается изменить пользователь
        $State = State::getInstance();
        $User = $State->getUser();
        //если пользователь пытается изменить дефолтное свойство,
        //или сделать обычное свойство дефолтным
        if ($data['default']
            || $data['default'] == 'true'
            || $data['default'] == '1'
            || $issetProperty->default
            || $issetProperty->default == '1'
        ) {
            //Если пользователь неявляется разработчиком
            //то недадим ему изменить такое свойство
            if ($User->login != env('DEVELOPER_LOGIN')) {
                throw new CustomException(
                    'add properties', [], 403,
                    'The current user does not have sufficient rights to' .
                    ' add properties. Only developers can put default properties.'
                );
            }
        } else {
            $data['default'] = 0;
        }


        //дописываем id, которое валидатор не должен учитывать
        $data['update_id'] = $data['id'];

        $validate = $Property->propertiesValidator($data);
        if ($validate) {
            $Property = $issetProperty;
            $oldProperty = clone $Property;
            //обновляем
            $Property->name = $data['name'];
            $Property->code = $data['code'];
            $Property->sort = $data['sort'];
            $Property->module()->associate($data['module_id']);
            $Property->field_entity = $data['field_entity'];
            $Property->field_type   = $data['field_type'];
            $Property->multiply     = $data['multiply'];
            $Property->require      = $data['require'];
            $Property->active       = $data['active'];
            $Property->default      = $data['default'];
            $Property->size         = $data['size'];
            $Property->show_in_filter = $data['show_in_filter'];

            if ($data['category_id']) {
                $Property->category()->associate($data['category_id']);
            }

            if ($data['category_id'] == 0 || $data['category_id'] === null) {
                $Property->category()->dissociate();
            }

            $Property->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_put_property',
                null, 'properties', $Property->id,
                ['data' => self::modelFilter($Property, Properties::fields())],
                [$oldProperty, $Property]
            );
        }

        return parent::response($data, $Property, 200);
    }

    /**
     * Возвращает все свойства для указанных категорий,
     * либо все, где указаны категории, если не передавать
     * список категорий
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getCategoriesProperties(Request $request)
    {
        $categories = $request->get('categories');
        $all = (float) $request->get('show_all');
        if (is_string($categories)) $categories = json_decode($categories);


        if ($categories && count($categories)) {
            $result = Properties::whereIn('category_id', $categories)->orWhereNull('category_id');
        } else {
            $result = Properties::whereNotNull('category_id');
        }

        if (!$all) $result = $result->where('show_in_filter', 1);

        $result = $result->where('field_entity', 'sku')->with([
            'choices' => function($q) {
                $q->orderBy('sort');
            }
        ])->orderBy('sort')->get();

        return parent::response($request->all(), $result, 200);
    }


    /**
     * Возвращает список свойств по коду модуля
     * и/или по коду сущности свйоства
     *
     * @param string      $module_code - Символьный код модуля
     * @param string|bool $entity      - Символьный код сущности свойства
     *
     * @return mixed
     * @throws CustomException
     */
    public function getProperties(Request $request, $module_code, $entity = false, $entity_id = false)
    {
        User::can('properties_view_property', true);
        $data = $request->only('active');

        //найдём модуль, который просит пользователь
        $Module = new Module();
        //метод выбросит исключение с ошибкой 404, если не найдёт
        //поэтому обрабатывать его не нужно
        $module = $Module->getModuleByCode($module_code);

        if (!$entity) {
            $result = Properties::where('module_id', $module['id'])
                ->with('category')->get();
        } else {
            $result = Properties::where('module_id', $module['id'])
                ->with('category')->where('field_entity', $entity);
            if ($data['active']) {
                $result = $result->where('active', 1);
            }
            $result = $result->get();
        }


        foreach ($result as &$property) {
            if (Properties::isSelecting($property['id'])) {
                $property->choices = $property->choices()->get();
            }

            if ($entity_id && $entity_id != 'choices') {
                $property->value = $property->values()->get()
                    ->where('entity_id', $entity_id)
                    ->pluck('value');
                if (count($property->value)) {
                    if ($property->field_type == 'file') {
                        $File = new File();
                        $fileValues = [];
                        $fileFiles = [];
                        foreach ($property->value as $i => $fileId) {
                            $fileFiles[$i] = $File->getFileById($fileId, false);
                            $fileValues[$i] = $fileFiles[$i]['path'];
                        }
                        $property->value = $fileValues;
                        $property->files = $fileFiles;
                    } else { //файлы в любом случае обрабатывают в массивах, без разницы множественные или нет
                        if (!$property->multiply || $property->field_type == 'radio' && count($property->value)) {
                            $property->value = $property->value[0];
                        }
                    }
                }
            }

        } unset($property);


        return parent::response(
            [
                'data' => $data,
                'module_code' => $module_code,
                'field_entity_code' => $entity
            ],
            $result,
            200
        );
    }


    /**
     * Возвращает свойство по его ID
     *
     * @param integer $id - ID свойства
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getPropertyById($id)
    {
        User::can('properties_view_property', true);

        $result = Properties::where('id', $id)
            ->with('category')->first();

        if (!$result) {
            throw new CustomDBException([], $result, 404);
        }

        if (Properties::isSelecting($result['id'])) {
            $result->choices = $result->choices()->get();
        }

        return parent::response(['id' => $id], $result, 200);
    }

}