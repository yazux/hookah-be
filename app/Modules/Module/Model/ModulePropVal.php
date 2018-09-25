<?php

namespace App\Modules\Module\Model;
use App\Exceptions\Handler;
use App\Interfaces\ModuleModelInterface;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Module\Controllers\ModuleController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Modules\Properties\Controllers\FileController as File;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomDBException;
use App\Exceptions\CustomException;

use League\Flysystem\Exception;
use App\Modules\Module\Model\Module;



class ModulePropVal extends Model implements ModuleModelInterface
{
    /**
     * @var string
     */
    public $table = 'module_prop_val';

    /**
     * @var array
     */
    protected $fillable = [
        'module_id',
        'code',
        'value_code',
        'value'
    ];

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fields = [
        'module_id',
        'code',
        'value_code',
        'value'
    ];

    /**
     * Вернёт поля таблицы, доступные для выборки
     *
     * @return array
     */
    public static function fields()
    {
        // TODO: Implement fields() method.
        return self::$fields;
    }

    /**
     * Все возможные типы полей, которые могут принимать свойства
     * @var array
     */
    private $propertyType = [
        'text',
        'textarea',
        'checkbox',
        'number',
        'radio',
        'select',
        'tel',
        'email',
        'range',
        'file',
        'hidden',
        'image',
        'password',
        'color',
        'date',
        'datetime',
        'time',
        'url'
    ];

    /**
     * Валидирует поля для записи значения свойства модуля
     * Не проверяет тип значения свойства
     *
     * @param $data
     * @return bool
     * @throws CustomValidationException
     */
    private function SetModulePropertyValueValidator($data)
    {
        $validator = Validator::make( (array)$data, [
            'module_id' => 'required|numeric|max:9999999999',
            'code' => 'required|max:255',
            'value' => 'required|max:50000',
        ]);

        if ($validator->fails()) {
            throw new CustomValidationException($validator, 'validation error', $data);
        } else{
            return true;
        }
    }

    /**
     * Валидирует тип нового значения свойства модуля
     *
     * @param $propertyValue
     * @param $propertyType
     * @return bool
     * @throws CustomValidationException
     */
    public function PropertyValueTypeValidator( $propertyValue, $propertyType )
    {
        $propertyValueArray = ['value' => $propertyValue];
        $ruleArray = [];

        switch( $propertyType ){
            case 'text':     $ruleArray = ['value' => 'required|max:255']; break;
            case 'textarea': $ruleArray = ['value' => 'required|max:50000']; break;
            case 'checkbox': $ruleArray = ['value' => 'required|max:255']; break;
            case 'number':   $ruleArray = ['value' => 'required|numeric']; break;
            case 'radio':    $ruleArray = ['value' => 'required|max:255']; break;
            case 'select':   $ruleArray = ['value' => 'required|max:255']; break;
            case 'tel':      $ruleArray = ['value' => 'required|max:255']; break;
            case 'email':    $ruleArray = ['value' => 'required|email']; break;
            case 'range':    $ruleArray = ['value' => 'required|numeric']; break;
            case 'file':     $ruleArray = ['value' => 'required']; break;
            case 'hidden':   $ruleArray = ['value' => 'required|max:2500']; break;
            case 'image':    $ruleArray = ['value' => 'required|image']; break;
            case 'password': $ruleArray = ['value' => 'required|max:255']; break;
            case 'color':    $ruleArray = ['value' => 'required|max:255']; break;
            case 'date':     $ruleArray = ['value' => 'required|date_format:d.m.Y']; break;
            case 'datetime': $ruleArray = ['value' => 'required|date_format:d.m.Y G:i:s']; break;
            case 'time':     $ruleArray = ['value' => 'required|date_format:G:i:s']; break;
            case 'url':      $ruleArray = ['value' => 'required|url|max:2500']; break;
        }

        $validator = Validator::make( (array)$propertyValueArray, $ruleArray);

        if ($validator->fails()) {
            throw new CustomValidationException(
                $validator,
                'Property validation error',
                [
                    'prop_value' => $propertyValue,
                    'prop_type' => $propertyType
                ]
            );
        } else{
            return true;
        }

    }

    /**
     * Возвращает значение свойства модуля по коду модуля и свойства
     *
     * @param $moduleName
     * @param $propCode
     * @return mixed
     * @throws CustomDBException
     */
    public function GetModulePropertyValue($moduleName, $propCode)
    {
        $Module = new Module();
        $ModuleArray = $Module->GetModuleByCode($moduleName);
        if( !count($ModuleArray) ){
            throw new CustomDBException(['module_code' => $moduleName, 'prop_code' => $propCode], false, 404, 'Модуль не найден');
        }

        $ModulePropValue = $this::where('module_id', $ModuleArray['id'])->where('code', $propCode)->get();
        if (!$ModulePropValue || !count($ModulePropValue)) {
            $ModuleController = new ModuleController();
            $properties = $ModuleController->getModuleProperties($moduleName, false);
            if (!$properties[$propCode]) {
                throw new CustomDBException(['module_code' => $moduleName, 'prop_code' => $propCode], false, 404, 'Свойство не найдено');
            }
            $ModulePropValue = [
                [
                    "id" =>  0,
                    "created_at" => "",
                    "updated_at" => "",
                    "module_id" => $ModuleArray->id,
                    "value" => $properties[$propCode]['default_value'],
                    "code" => $propCode
                ]
            ];
        }

        return $ModulePropValue;
    }

    /**
     * Проверяет корректность перданных значений свойств модуля
     * относитально настроек свойства в конфигах модуля
     *
     * @param $data
     * @param $property
     * @return bool
     * @throws CustomException
     */
    public function CheckMultiplyProperty($data, $property){
        $data['multiply'] = ($data['multiply'] == 'true' || $data['multiply'] == 1) ? true : false;
        //если передали множественное свойстно, но оно таким не является
        if( $data['multiply'] && !$property['multiply']){
            //выбросим исключение
            throw new CustomException(
                $data, $property, 400, "Property '".$data['prop_code']."' is not multiply"
            );
        }
        //если передали одиночное свойство, а оно множественное
        if( !$data['multiply'] && $property['multiply'] ){
            //выбросим исключение
            throw new CustomException(
                $data, $property, 400,
                "Property '".$data['prop_code']."' is multiply, property value must be multiply also"
            );
        }
        //если свойство множественное, а значения не массив
        if( $property['multiply'] && !is_array($data['value']) ){
            //выбросим исключение
            throw new CustomException($data, $property, 400, "Property values must by array");
        }
        //если свойство единичное, а передан массив
        if( !$property['multiply'] && is_array($data['value']) ){
            //выбросим исключение
            throw new CustomException($data, $property, 400, "Property values must by not array");
        }

        return true;
    }


    /**
     * Добавляет новое обновляет старое значение свойства модуля
     *
     * @param $data
     * @param $property
     * @param bool $isUpdate
     * @return ModulePropVal
     * @throws CustomDBException
     * @throws CustomException
     */
    public function SetModulePropertyValue($data, $property, $configProperty, $request)
    {
        $property['multiply'] = $data['multiply'] = $configProperty['multiply'];
        $Module = new Module();
        //вытащим из базы запись о переданном модуле
        $ModuleArray = $Module->GetModuleByCode($data['module_name']);
        //выбросим исключение, если такого модуля не нашли
        if( !count($ModuleArray) || !$ModuleArray ){
            throw new CustomDBException($data, false, 404, 'Module not found');
        }
        //проверим, является ли тип поля допустимым
        if( !in_array( $property['type'], $this->propertyType) ){
            throw new CustomException(
                ['data' => $data, 'property' => $property],
                false, 400, "'".$property['type']." is not valid property type"
            );
        }

        if ($property['type'] == 'file' && !$request->hasFile('value')) {
            throw new CustomException(
                ['data' => $data, 'property' => $property],
                false, 400, "Для этого поля требуется прикрепить файл"
            );
        }

        //если свойство множественное
        if( $configProperty['multiply'] && $property['type'] != 'file' ) {
            //$data['value'] = json_decode($data['value'], true);
        }

        //проверим множественные свойства
        if( !$this->CheckMultiplyProperty($data, $property) ){
            throw new CustomException(
                ['data' => $data, 'property' => $property],
                false, 400, "Property value is not correct"
            );
        }

        //если свойство множественное
        if( $property['multiply'] ){
            $validateData = true;
            $validateType = true;
            foreach( $data['value'] as $value ){
                //валидируем входные параметры для добавления в БД
                $valueData = [
                    'module_id' => $ModuleArray['id'],
                    'value' => $value,
                    'code' => $data['prop_code']
                ];
                $validateData = $validateData && $this->SetModulePropertyValueValidator($valueData);
                //проверяем тип значения поля свойства для корректного добавления и использования далее
                $validateType = $validateType && $this->PropertyValueTypeValidator( $valueData['value'], $property['type']);
            }
            $data = [
                'module_id' => $ModuleArray['id'],
                'value' => $data['value'],
                'code' => $data['prop_code']
            ];
        } else{
            //валидируем входные параметры для добавления в БД
            $data = [
                'module_id' => $ModuleArray['id'],
                'value' => $data['value'],
                'code' => $data['prop_code']
            ];
            $validateData = $this->SetModulePropertyValueValidator($data);
            //проверяем тип значения поля свойства для корректного добавления и использования далее
            $validateType = $this->PropertyValueTypeValidator( $data['value'], $property['type']);
        }

        //если при валидации возникли ошибки
        if( !$validateData || !$validateType ) {
            //тут уже должно было вылететь исключение в валидаторе,
            //но если этого почему-то не случилось, выбросим ещё одно
            throw new CustomException(
                [
                    'data_validator' => $validateData,
                    'prop_type_validator' => $validateType,
                    'data' => $data
                ], false, 500, "Validation data failed"
            );
        }

        $ModuleCTR = new ModuleController();
        LoggerController::write(
            $ModuleCTR->getModuleName(), 'module_setmoduleprop',
            'Установка значения свойства ' . $property['name'] . ' в модуле ' .
            $ModuleArray->code . '(' . $ModuleArray->id . ')'
            , 'module', $ModuleArray->id
        );

        //если свойство множественное
        if( $property['multiply'] ){
            return $this->SetMultiplyModulePropertyValue($data);
        } else {
            return $this->SetSingleModulePropertyValue($data, $request);
        }

    }

    /**
     * Добавляет новое обновляет старое множественное значение свойства модуля
     * Вызывается методом SetModulePropertyValue
     *
     * @param $data
     * @return array
     * @throws CustomException
     */
    public function SetMultiplyModulePropertyValue($data)
    {
        //return $data;
        //результирующий массив всех свойств
        $newPropertyValueArr = [];

        //проверяем, существует ли уже значение этого поля для этого модуля в БД или нет
        $issetModuleProp = $this::where('module_id', $data['module_id'])
            ->where('code', $data['code']);

        if (count($issetModuleProp->get())) { //если значения уже есть, удаляем их
            $issetModuleProp->delete();
        }

        foreach ($data['value'] as $key => $value){
            //добавляем значение свойства в БД
            $newPropertyValue = new ModulePropVal();
            $newPropertyValue->module_id = $data['module_id'];
            $newPropertyValue->code = $data['code'];
            $newPropertyValue->value = $value;
            $newPropertyValue->save();
            $newPropertyValueArr[] = $newPropertyValue;
        }

        return $newPropertyValueArr;
    }

    /**
     * Добавляет новое обновляет старое единичное значение свойства модуля
     * Вызывается методом SetModulePropertyValue
     *
     * @param $data
     * @param $request
     * @return ModulePropVal
     * @throws CustomException
     */
    public function SetSingleModulePropertyValue($data, $request)
    {
        //проверяем, существует ли уже значение этого поля для этого модуля в БД или нет
        $issetModuleProp = $this::where('module_id', $data['module_id'])
            ->where('code', $data['code'])
            ->first();

        //если передаётся файл, то сохраним его
        if ($request->hasFile('value')) {
            $File = new File();
            $file = $File->upload($request->file('value'));
            $data['value'] = $file['id'];

            if ($issetModuleProp) {
                //удаляем старый файл
                $File->deleteFileById($issetModuleProp->value, false);
            }

        }

        //если мы добавляем новое свойство
        //if( !$isUpdate ) {
        if(!$issetModuleProp){
            /*//если оно уже существует в БД
            if (count($issetModuleProp)) {
                //выбросим исключение
                $data['isset_property'] = $issetModuleProp;
                throw new CustomException(
                    [ 'isset_property' => $issetModuleProp,  'data' => $data ],
                    false, 400, "Property value is already defined in database"
                );
            }*/

            //добавляем значение свойства в БД
            $newPropertyValue = new ModulePropVal();
            $newPropertyValue->module_id = $data['module_id'];
            $newPropertyValue->code = $data['code'];
            $newPropertyValue->value = $data['value'];
            $newPropertyValue->save();

            if ($request->hasFile('value')) {
                $File = new File();
                $newPropertyValue->value = $File->getFileById($newPropertyValue->value, false);
            }

            return $newPropertyValue;

        } else{
            //если мы обновляем существующее свойство и если свойства не существует в БД
            if ( !count($issetModuleProp) || !$issetModuleProp ) {
                //выбросим исключение
                throw new CustomException( $data, false, 404, "Property value is not defined in database" );
            }
            //обновляем значение свойства
            $issetModuleProp->value = $data['value'];
            $issetModuleProp->save();

            if ($request->hasFile('value')) {
                $File = new File();
                $issetModuleProp->value = $File->getFileById($issetModuleProp->value, false);
            }

            return $issetModuleProp;
        }
    }

    /**
     * Удаляет значение свойства модуля по коду модуля и коду свойства
     *
     * @param $moduleName
     * @param $propCode
     * @return mixed
     * @throws CustomDBException
     */
    public function RemoveModulePropertyValue($moduleName, $propCode){
        $Module = new Module();
        $ModuleArray = $Module->GetModuleByCode($moduleName);
        if( !count($ModuleArray) ){
            throw new CustomDBException(['module_code' => $moduleName, 'prop_code' => $propCode], false, 404, 'Module not found');
        }

        $ModulePropValue = $this::where('module_id', $ModuleArray['id'])->where('code', $propCode)->delete();

        $ModuleCTR = new ModuleController();
        //получим все определённые в конфиге свойства данного модуля
        $ModuleProperties = $ModuleCTR->GetModuleProperties($moduleName, false);
        $property = $ModuleProperties[$propCode];
        LoggerController::write(
            $ModuleCTR->getModuleName(), 'module_removemoduleprop',
            'Установка значения свойства ' . $property['name'] . ' в модуле ' .
            $ModuleArray->code . '(' . $ModuleArray->id . ')'
            , 'module', $ModuleArray->id
        );

        return $ModulePropValue;
    }
}
