<?php

namespace App\Modules\Properties\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Module\Controllers\ModuleController;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;
use App\Modules\Properties\Controllers\FileController as File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Класс для работы с значениями свойств сущностей в модулях
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class ValuesController extends Controller implements ModuleInterface
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
/*
    public function postPropertyValues(Request $request)
    {
        $result = [];
        $resultValues = [];
        $data = $request->all();

        $PropsIds = array_keys($data['values']);
        $Props = Properties::whereIn('id', $PropsIds)->get();


        foreach ($Props as $Prop) {
            $value = $data['values'][$Prop['id']];


            //если свойство множественное
            if ($Prop['multiply']) {
                $DBValue = PropertiesValues::where('property_id', $Prop['id'])
                    ->where('entity_id', $data['entity_id']);

                //если поле файл
                if ($Prop['field_type'] === 'file') {
                    //если есть старые значения, то удаляем их и файлы
                    if ($DBValue->get()) {
                        foreach ($DBValue as $DBItemValue) {
                            File::call('deleteFileById', $DBItemValue['value'], false);
                        }
                        $DBValue->delete();
                    }

                    //сохраняем новые файлы
                    $i = 0;
                    foreach ($value as $itemValue) {
                        //if ($request->hasFile('values.' . $Prop['id'] . '.' . $i)) {

                            $File = File::call('upload', $itemValue);
                            $DBValue = new PropertiesValues();
                            $resultValues[] = self::updateValue($DBValue, [
                                'code' => $Prop['code'],
                                'value' => $File['id'],
                                'property_id' => $Prop['id'],
                                'entity_id' => $data['entity_id']
                            ]);
                        //}
                        $i++;
                    }

                //если не файл
                } else {
                    //если есть старые значения, то удаляем их
                    if ($DBValue) $DBValue->delete();

                    //сохраняем новые файлы
                    foreach ($value as $itemValue) {
                        $DBValue = new PropertiesValues();
                        $resultValues[] = self::updateValue($DBValue, [
                            'code'        => $Prop['code'],
                            'value'       => $itemValue,
                            'property_id' => $Prop['id'],
                            'entity_id'   => $data['entity_id']
                        ]);
                    }
                }

            //если свойство единственное
            } else {
                $DBValue = PropertiesValues::where('property_id', $Prop['id'])
                    ->where('entity_id', $data['entity_id'])->first();

                //если поле файл
                if ($Prop['field_type'] === 'file' && $request->hasFile('values.' . $Prop['id'])) {
                    //если есть старое значение, удаляем старый файл
                    if ($DBValue) File::call('deleteFileById', $DBValue['value'], false);

                    //сохраняем новый файл
                    $File = File::call('upload', $value);
                    if (!$DBValue) $DBValue = new PropertiesValues();
                    $resultValues[] = self::updateValue($DBValue, [
                        'code'        => $Prop['code'],
                        'value'       => $File['id'],
                        'property_id' => $Prop['id'],
                        'entity_id'   => $data['entity_id']
                    ]);

                //если не файл
                } else {
                    $newValue = [
                        'code'        => ($DBValue) ? $DBValue['code']        : $Prop['code'],
                        'value'       => $value,
                        'property_id' => ($DBValue) ? $DBValue['property_id'] : $Prop['id'],
                        'entity_id'   => ($DBValue) ? $DBValue['entity_id']   : $data['entity_id']
                    ];
                    if (!$DBValue) $DBValue = new PropertiesValues();
                    $resultValues[] = self::updateValue($DBValue, $newValue);
                }
            }

        }

        $result = $resultValues;

        return parent::response($data, $result, 200);
    }
*/

    public function deleteValue(Request $request) {
        $data = $request->only('property_id', 'entity_id', 'value');

        $Value = PropertiesValues::where('property_id', $data['property_id'])
            ->where('entity_id', $data['entity_id'])
            ->where('value', $data['value'])->first();

        if (!$Value) {
            throw new CustomException(
                $data, [], 404, 'Искомое свойство не найдено.'
            );
        }

        return parent::response($data, $Value->delete(), 200);
    }

    public function updatePropertyValueByObject(Request $request) {
        $data = $request->all();
        if (is_string($data['_request_meta_data'])) {
            $data['_request_meta_data'] = json_decode($data['_request_meta_data']);
        }
        $result = [];
        //проверям, имеет ли пользователь право на выполнение действия
        $UserCTR = new UserController();
        if (!$UserCTR->can('properties_add_property_values')) {
            throw new CustomException(
                $data, [], 403, 'У пользователя нет прав на выполнение действия'
            );
        }

        $Validator = new PropertiesValues();
        foreach ($data['_request_meta_data'] as $value) {

            //получим данные о сущности, свойстве и модуле,
            //которые будут исользоваться
            $entityData = $this::_getEntityData(
                $value->property_id,
                $value->entity_id
            );
            //проверим существуют ли уже значения для этого свойства
            $issetValue = PropertiesValues::where('entity_id', $value->entity_id)
                ->where('property_id', $value->property_id)
                ->select('id', 'value');
            $oldValue = $issetValue->get();

            //если множественное
            if (($entityData['property']['multiply']
                || is_array($value->value)
                || $value->field_type == 'file')
                && $value->field_type != 'radio'
            ) {
                $valid = true; //для файлов по умлочанию тру так как при загрузке файлы
                //валидируются в контроллере автоматически
                //если передаётся файл, то сохраним его
                if ($value->field_type == 'file') {
                    $value->value = [];
                    //так как и множественные и одиночные файлы приходят в массиве
                    //то перебираем его и сохраням каждый файл

                    if ($request->{$value->code} && current($request->{$value->code}))
                        foreach ($request->{$value->code} as $fileRequest) {
                            $File = new File();
                            $file = $File->upload($fileRequest);
                            $value->value[] = $file['id'];
                        }

                    /*
                    //удаляем старые файлы
                    foreach ($oldValue as $oldFile) {
                        File::call('deleteFileById', $oldFile['id'], false);
                    }
                    $issetValue->delete();
                    */

                } else { //если поле НЕ файл
                    //удаляем старые значения
                    if (count($oldValue)) {
                        $issetValue->delete();
                    }
                    //валидируем поле
                    $valid = $Validator->valuesValidator($value);
                }
                //перебираем переданные значения и сохраняем каждое
                foreach ($value->value as $valueItem) {
                    if ($valid) {
                        $result[$value->code][] = self::saveNewValue(
                            [
                                'code' => $value->code,
                                'value' => $valueItem,
                                'property_id' => $value->property_id,
                                'model' => $entityData['entity_type']['model'],
                                'entity_id' => $value->entity_id
                            ]
                        );
                    }
                }

            } else { //если единственное
                //если поле чекбокс/радио и он снят, удаляем значение
                if (($value->field_type == 'radio' || $value->field_type == 'checkbox') && $value->value == null) {
                    $issetValue->delete();
                } else {
                    $valid = $Validator->valuesValidator($value);

                    if (!count($oldValue)) { //если старых значений не существует, то создаём новые
                        if ($valid) { //если свойство не пустое, то сохраняем его
                            $result[$value->code] = self::saveNewValue(
                                [
                                    'code' => $value->code,
                                    'value' => $value->value,
                                    'property_id' => $value->property_id,
                                    'model' => $entityData['entity_type']['model'],
                                    'entity_id' => $value->entity_id
                                ]
                            );
                        }
                    } else { //если старые значения есть, то обновим их
                        if ($valid) { //если новое значение не пустое, то сохраняем его
                            $oldValue = $issetValue->first();
                            $oldValue->code = $value->code;
                            $oldValue->value = $value->value;
                            $oldValue->property()->associate($value->property_id);
                            $oldValue->entity($entityData['entity_type']['model'])
                                ->associate($value->entity_id);
                            $oldValue->save();
                            $result[$value->code] = $oldValue;
                        } else { //если пустое, значит нужно удалить старое значение
                            $oldValue = $issetValue->first();
                            $result[$value->code] = $oldValue;
                            $issetValue->delete();
                        }
                    }

                }

            } //if multiple
        } //for

        return parent::response($data, $result, 200);
    }

    private static function updateValue(PropertiesValues $Value, $data)
    {
        $Value->code        = ($data['code'])        ? $data['code']        : $Value->code;
        $Value->value       = ($data['value'])       ? $data['value']       : $Value->value;
        $Value->property_id = ($data['property_id']) ? $data['property_id'] : $Value->property_id;
        $Value->entity_id   = ($data['entity_id'])   ? $data['entity_id']   : $Value->entity_id;
        $Value->save();
        return $Value;
    }

    private static function saveNewValue($data) {
        $Value = new PropertiesValues();
        $Value->code = $data['code'];
        $Value->value = $data['value'];
        $Value->property()->associate($data['property_id']);
        $Value->entity($data['model'])
            ->associate($data['entity_id']);
        $Value->save();
        return $Value;
    }


    public function deleteFileValues(Request $request)
    {
        User::can('properties_delete_property_values', true);
        $data = $request->only('value', 'property_id');

        $ValueRequest = PropertiesValues::where('property_id', $data['property_id'])
            ->where('value', $data['value']);
        $Value = $ValueRequest->first();
        $result['value'] = $Value;
        //если свойство - файл, удаляем все файлы с диска
        $File = new File();
        $File->deleteFileById($Value['value'], false);

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'properties_delete_property_values',
            null, 'properties', $data['property_id']
        );
        $result['delete'] = $ValueRequest->delete();

        return parent::response($data, $result, 200);
    }

    /**
     * Добавляет в БД новое значение свйоства
     * Парметры POST запроса:
     * integer property_id - ID свйоства
     * integer entity_id   - ID сущности
     * string  code        - симольный код значения
     * string  value       - значение
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function postPropertyValue(Request $request)
    {
        User::can('properties_add_property_values', true);
        $data = $request->only(
            'property_id', 'entity_id', 'code', 'value'
        );

        $Values = new PropertiesValues();
        $validate = $Values->valuesValidator($data);
        if ($validate) {

            $entityData = $this::_getEntityData(
                $data['property_id'], $data['entity_id']
            );

            if ($entityData['property']['multiply']) {
                $issetValue = PropertiesValues::where(
                    'entity_id', $data['entity_id']
                )->where('property_id', $data['property_id'])
                    ->where('code', $data['code'])
                    ->first();
            } else {
                $issetValue = PropertiesValues::where(
                    'entity_id', $data['entity_id']
                )->where('property_id', $data['property_id'])->first();
            }

            if ($issetValue) {
                throw new CustomException(
                    'Property value is already exist', [], 400,
                    'Property value with entity_id "' . $data['entity_id'] .
                    '" and property_id "' .
                    $data['property_id'] . '" is already exist.' .
                    'This property is not multiply.'
                );
            }

            //если передаётся файл, то сохраним его
            if ($request->hasFile('value')) {
                $File = new File();
                $file = $File->upload($request->file('value'));
                $data['value'] = $file['id'];
            }

            $Values->code = $data['code'];
            $Values->value = $data['value'];
            $Values->property()->associate($data['property_id']);
            $Values->entity($entityData['entity_type']['model'])
                ->associate($data['entity_id']);
            $Values->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_add_property_values',
                null, 'prop_value', $Values->id,
                ['data' => self::modelFilter($Values, PropertiesValues::fields())]
            );


        }

        return parent::response(
            $data, $Values, 200
        );
    }

    /**
     * Обновляет в БД существующее значение свйоства
     * Парметры POST запроса:
     * string  _method     - Метод запроса, всего нужно передавать "PUT"
     * integer id          - ID свойства для обновления
     * integer property_id - ID свйоства
     * integer entity_id   - ID сущности
     * string  code        - симольный код значения
     * string  value       - значение
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putPropertyValue(Request $request)
    {
        User::can('properties_put_property_values', true);
        $data = $request->only(
            'property_id', 'entity_id', 'code', 'value', 'id'
        ); $data['update_id'] = $data['id'];

        $Value = new PropertiesValues();
        $validate = $Value->valuesValidator($data);
        if ($validate) {

            //получим текущее значение свйоства
            $Value = PropertiesValues::where('id', $data['id'])->first();
            if (!$Value) {
                throw new CustomException(
                    'Property value is not exist', [], 400,
                    'Property value with id "' . $data['id'] .
                    ' is not exist'
                );
            }

            $entityData = $this::_getEntityData(
                $data['property_id'], $data['entity_id']
            );

            if ($entityData['property']['multiply']) {
                $issetValue = PropertiesValues::where(
                    'entity_id', $data['entity_id']
                )->where('property_id', $data['property_id'])
                    ->where('code', $data['code'])
                    ->where('id', '!=', $data['id'])
                    ->first();
            } else {
                $issetValue = PropertiesValues::where(
                    'entity_id', $data['entity_id']
                )->where('property_id', $data['property_id'])
                    ->where('id', '!=', $data['id'])->first();
            }

            if ($issetValue) {
                throw new CustomException(
                    'Property value is already exist',
                    ['isset_value' => $issetValue],
                    400,
                    'Property value with entity_id "' . $data['entity_id'] .
                    '" and property_id "' .
                    $data['property_id'] . '" is already exist.' .
                    'This property is not multiply.'
                );
            }

            //если передаётся файл, то
            //удалим старый файл и сохраним новй
            if ($request->hasFile('value')) {
                $File = new File();
                //удаляем файл
                $File->deleteFileById($Value['value'], false);
                $file = $File->upload($request->file('value'));
                $data['value'] = $file['id'];
            }
            $oldValue = clone $Value;
            $Value->code = $data['code'];
            $Value->value = $data['value'];
            $Value->property()->associate($data['property_id']);
            $Value->entity($entityData['entity_type']['model'])
                ->associate($data['entity_id']);
            $Value->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_put_property_values',
                null, 'prop_value', $Value->id,
                ['data' => self::modelFilter($Value, PropertiesValues::fields())],
                [$oldValue, $Value]
            );

        }

        return parent::response(
            $data, $Value, 200
        );
    }

    /**
     * Возвращает занчения свйоства для указанной сущности
     * Если передать $value_id, то вернёт конкретное значение,
     * если не передавать, то все значения
     *
     * @param integer      $property_id - ID свойства
     * @param integer      $entity_id   - ID сущности
     * @param integer|bool $value_id    - ID значения
     *
     * @return mixed
     * @throws CustomException
     */
    public function getPropertyValues($property_id, $entity_id, $value_id = false)
    {
        User::can('properties_view_property_values', true);

        $entityData = $this::_getEntityData(
            $property_id, $entity_id
        );

        if ($value_id) {
            $Values = PropertiesValues::where('property_id', $property_id)
                ->where('entity_id', $entity_id)
                ->where('id', $value_id)
                ->first();
        } else {
            $Values = PropertiesValues::where('property_id', $property_id)
                ->where('entity_id', $entity_id)
                ->get();
        }

        return parent::response(
            [
                'property_id' => $property_id,
                'entity_id' => $entity_id
            ], $Values, 200
        );
    }

    /**
     * Возвращает список свойств со значениями и ваиантами выбора
     * для сущности с переданным id
     * Параметры GET запроса:
     * _method - всегда GET
     * properties - массив свойств, которые нужно получить
     * entity_id - id сущности, для которой получаем свойства
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function getAllPropertyValues(Request $request)
    {
        User::can('properties_view_property_values', true);
        $data = $request->only('_method', 'properties', 'entity_id');
        $result = [];

        if (isset($data['properties'])) {
            //читаем массив
            $data['properties'] = json_decode($data['properties'], true);
            //проверим ошибки декодирования
            $JSONError = self::getJsonTestStatus();
            //если json считался c ошибками, то скажем пользователю об этом
            if ($JSONError) {
                throw new CustomException(
                    $data['properties'], false, 500, $JSONError
                );
            }
        }

        $Entity = DB::table('module_properties_values')
            ->leftJoin(
                'module_properties',
                'module_properties_values.property_id', '=', 'module_properties.id'
            )
            ->leftJoin(
                'module_properties_choices',
                'module_properties_choices.property_id', '=', 'module_properties.id'
            )
            ->select(
                [
                    'module_properties_values.id AS value_id',
                    'module_properties_values.code AS value_code',
                    'module_properties_values.entity_id AS value_entity_id',
                    'module_properties_values.value AS value_value',
                    'module_properties_values.created_at AS value_created_at',
                    'module_properties_values.updated_at AS value_updated_at',
                    'module_properties.*',
                    'module_properties_choices.id AS choices_id',
                    'module_properties_choices.name AS choices_name',
                    'module_properties_choices.code AS choices_code',
                    'module_properties_choices.sort AS choices_sort'
                ]
            );

        if (isset($data['properties'])) {
            $Entity = $Entity->whereIn(
                'module_properties.code', $data['properties']
            )->get();
        } else {
            $Entity = $Entity->get();
        }

        $Entity = json_decode(json_encode($Entity), true);

        foreach ($Entity as $item) {

            if ($item['field_type'] == 'file') {
                $FileController = new FileController();
                $item['value_value'] = $FileController->getFileById(
                    $item['value_value'], false
                );
            }

            if (isset($result[$item['code']])) {
                $result[$item['code']]['values'][ $item['value_code'] ] = [
                    "id" => $item['value_id'],
                    "code" => $item['value_code'],
                    "entity_id" => $item['value_entity_id'],
                    "value" => $item['value_value'],
                    "property_id" => $item['value_created_at'],
                    "updated_at" => $item['value_updated_at'],
                ];
            } else {
                $result[$item['code']] = [
                    "id" => $item['id'],
                    "name" => $item['name'],
                    "sort" => $item['sort'],
                    "module_id" => $item['module_id'],
                    "field_entity" => $item['field_entity'],
                    "field_type" => $item['field_type'],
                    "multiply" => $item['multiply'],
                    "require" => $item['require'],
                    "active" => $item['active'],
                    "values" => [
                        $item['value_code'] => [
                            "id" => $item['value_id'],
                            "code" => $item['value_code'],
                            "entity_id" => $item['value_entity_id'],
                            "value" => $item['value_value'],
                            "property_id" => $item['value_created_at'],
                            "updated_at" => $item['value_updated_at'],
                        ]
                    ]
                ];
            }

            if ($item['choices_code']) {
                $result[$item['code']]['choices'][$item['choices_code']] = [
                    'id' => $item['choices_id'],
                    'name' => $item['choices_name'],
                    'code' => $item['choices_code'],
                    'sort' => $item['choices_sort'],
                ];
            }
        }

        return parent::response(
            [
                'data' => $data
            ], $result, 200
        );
    }

    /**
     * Удаляет занчения свйоства для указанной сущности
     * Если передать $value_id, то удалит конкретное значение,
     * если не передавать, то все значения
     *
     * @param integer      $property_id - ID свойства
     * @param integer      $entity_id   - ID сущности
     * @param integer|bool $value_id    - ID значения
     *
     * @return mixed
     * @throws CustomException
     */
    public function deletePropertyValues($property_id, $entity_id, $value_id = false)
    {
        User::can('properties_delete_property_values', true);

        $entityData = $this::_getEntityData(
            $property_id, $entity_id
        );

        $ValuesRequest = PropertiesValues::where('property_id', $property_id)
            ->where('entity_id', $entity_id);
        if ($value_id) {
            $ValuesRequest->where('id', $value_id);
        }
        $Values = $ValuesRequest;
        $Values = $Values->get();

        //если свойство - файл, удаляем все файлы с диска
        if ($entityData['property']['field_type'] == 'file') {
            $File = new File();
            foreach ($Values as $value) {
                $File->deleteFileById($value['value'], false);
            }

        }

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'properties_delete_property_values',
            null, 'properties', $property_id,
            ['data' => self::modelFilter($Values, PropertiesValues::fields())]
        );


        $result = $ValuesRequest->delete();

        return parent::response(
            [
                'property_id' => $property_id,
                'entity_id' => $entity_id,
                'values' => $Values
            ], $result, 200
        );
    }

    /**
     * Возвращает массив с
     *  - Свйоством, для котрого запрашиваются данные
     *  - Типом сущности, для котрого запрашиваются данные
     *  - Сущностью, для котрой запрашиваются данные
     *
     * @param integer $property_id - id свойства
     * @param integer $entity_id   - id сущности
     *
     * @return mixed
     * @throws CustomException
     */
    private static function _getEntityData($property_id, $entity_id)
    {
        //получим свойство, значение которого пытаются записать
        $result['property'] = Properties::where('id', $property_id)->first();
        if (!$result['property']) {
            throw new CustomException(
                ['property_id' => $property_id, 'entity_id' => $entity_id],
                [], 404, 'Свойство с id "'.$property_id.'" не найдено'
            );
        }

        //получим модуль, к которому относится свойство
        $result['property']->module = $result['property']->module()->first();
        if (!$result['property']->module) {
            throw new CustomException(
                ['property_id' => $property_id, 'entity_id' => $entity_id], [], 404,
                'Модуль с id "'.$result['property']->module_id.'" не найден'
            );
        }

        //получим тип сущности, значение которой пытается записать пользователь
        $ModuleCTR = new ModuleCTR();
        $result['entity_type'] = $ModuleCTR->issetEntity(
            $result['property']['module']['code'],
            $result['property']['field_entity']
        );
        if (!$result['entity_type']) {
            throw new CustomException(
                ['property_id' => $property_id, 'entity_id' => $entity_id], [], 404,
                'Сущность скодом "' . $result['property']['field_entity'] .
                '" не найдена в модуле с кодом "' .
                $result['property']['module']['code'] . '"'
            );
        }

        //получим сущность, значение которой пытается записать пользователь
        $result['entity'] = $result['entity_type']['model']::where('id', $entity_id)
            ->first();

        if (!$result['entity']) {
            throw new CustomException(
                ['property_id' => $property_id, 'entity_id' => $entity_id], [], 404,
                'Сущность id "' . $entity_id . '" не найдена'
            );
        }

        return $result;
    }


    public function getSKUPropertiesValues($sku_id)
    {
        $result = [];
        $Values = PropertiesValues::whereHas('property', function ($q) {
            $q->where('field_entity', 'sku');
        })
            ->where('entity_id', $sku_id)
            ->select('id', 'property_id', 'value', 'entity_id')
            ->with('property')
            ->get();

        foreach ($Values as $Value) {

            if ($Value['property']['field_type'] === 'file') {
                $Value['value'] = File::call('getFileById', $Value['value'], false);
            }

            if (array_key_exists($Value['property_id'], $result)) {
                if (!is_array($result[$Value['property_id']])) {
                    $oldValue = $result[$Value['property_id']];
                    $result[$Value['property_id']] = [];
                    $result[$Value['property_id']][] = $oldValue;
                }
                $result[$Value['property_id']][] = $Value['value'];
            } else {
                $result[$Value['property_id']] = $Value['value'];
            }
        }

        return parent::response(['sku_id' => $sku_id], $result, 200);
    }
}