<?php

namespace App\Classes;

use App\Http\Controllers\Controller;
use App\Interfaces\ModuleModelInterface;
use Faker\Provider\Base;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\CustomValidationException;
use Illuminate\Http\Request;
use App\Exceptions\CustomException;

/**
 * Class BaseModel - базовая модель для работы с БД
 *
 * @package App\Classes
 */
class BaseModel extends Model implements ModuleModelInterface
{
    /**
     * Название таблицы, к которой относится модель
     *
     * @var string
     */
    public $table = '';

    /**
     * Флаг, используются ли поля
     * created_at и updated_at в таблице
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Поля таблицы
     *
     * @var array
     */
    public $fillable = [];

    /**
     * Правила валидации при записи в таблицу
     *
     * @var array
     */
    public $rules = [];

    /**
     * Текста ошибок валидации данных модели
     *
     * @var array
     */
    public $messages = [];

    /**
     * Поля, которые должны возвращаться Accessor'ами
     *
     * @var array
     */
    public static $accessors = [];

    /**
     * Создаёт экземпляр дочернего класса при вызове
     *
     * @return BaseModel
     */
    public static function factory()
    {
        $class = get_called_class();
        return new $class();
    }

    /**
     * Возвращает правила валидации
     * входных данных для таблицы
     *
     * @return array
     */
    public static function rules()
    {
        $self = self::factory();
        return $self->rules;
    }

    /**
     * Возвращает все поля таблицы
     *
     * @return array
     */
    public static function fields()
    {
        $self = self::factory();
        return $self->fillable;
    }

    /**
     * Для обратной совместимости
     * @return array|mixed
     */
    public static function fiedls() {
        return self::fields();
    }

    /**
     * Возвращает таблицу модели
     *
     * @return string
     */
    public static function table()
    {
        $self = self::factory();
        return $self->table;
    }

    /**
     * Возвращает текста ошибок для валидатора
     *
     * @return array
     */
    public static function messages()
    {
        $self = self::factory();
        return $self->messages;
    }

    /**
     * Проверяет существование сущности по её id
     *
     * @param integer $id             - id сущности
     * @param boolean $throwException - флаг выбрасывания исключения
     *
     * @return BaseModel
     * @throws CustomException
     */
    public static function exist($id, $throwException = false)
    {
        $self = self::factory();
        $self::where('id', $id)->first();

        if ($throwException && !$self) {
            throw new CustomException([], [], 404);
        }

        return $self;
    }

    /**
     * Статическая обёртка для метода validator
     *
     * @param array     $data        - Request::only(BaseModel::fields())
     * @param string    $requestType - Тип запроса (post или put)
     * @param BaseModel $Model       - Модель для вставки
     *
     * @return bool
     * @throws CustomException
     */
    public static function valid($data, $requestType = 'post', BaseModel $Model)
    {
        $self = self::factory();
        return $self->validator($data, $requestType, $Model);
    }

    /**
     * Валидирует входные параметры для вставки в таблицу
     *
     * @param array     $data        - Request::only(BaseModel::fields())
     * @param string    $requestType - Тип запроса (post или put)
     * @param BaseModel $Model       - Модель для вставки
     *
     * @return bool
     * @throws CustomException
     */
    public function validator($data, $requestType = 'post', BaseModel $Model)
    {
        $rules  = $this->rules();
        $fields = $this->fields();
        $id     = false;

        //если в списке полей есть сортирока, но её не передали
        //то добавляем стандартное значение сортировки
        if (array_keys($fields, 'sort') && !isset($data['sort'])) {
            $data['sort'] = 100;
        }


        //условие срабатывает, если мы пытаемся обновить запись
        if (strtolower($requestType) == 'put'
            &&
            (
                (array_key_exists('update_id', $data) && $data['update_id'])
                ||
                (array_key_exists('id', $data) && $data['id'])
            )
        ) {
            $rules['id'] = 'required|integer|min:1|max:9999999999';
            $id = ($data['id']) ? $data['id'] : $data['update_id'];
        }

        if (strtolower($requestType) == 'post') {
            unset($data['id']);
            unset($data['update_id']);
        }


        //добавляем игнорирование обновляемой записи
        //если у неё есть уникальное поле
        $oldRules = $rules;
        foreach ($oldRules as $field => &$ruleString) {
            $ruleArray = explode('|', $ruleString);
            foreach ($ruleArray as &$rule) {
                if (stristr($rule, 'unique') && $id) {
                    $rule .= ',' . $id;
                }
            } unset($rule);
            $rules[$field] = implode('|', $ruleArray);
        } unset($ruleString);

        if ($Messages = $Model->messages()) {
            $validator = Validator::make($data, $rules, $Messages);
        } else {
            $validator = Validator::make($data, $rules);
        }


        if ($validator->fails()) {
            throw new CustomException($data, ['rules' => $rules],
                400, $validator->messages()->first());
        } else {
            return true;
        }
    }

    /**
     * Создаёт новую запись в БД
     * используя переданные данные
     * из запроса
     * В массиве $relation необходимо передавать
     * массив из ключей связей и функций связей из модели
     * пример для модели Users:
     * ['group_id' => 'group'] - связь пользователя с группой
     *
     * @param Request $request  - Запрос от клиента
     * @param array   $relation - Массив с отношениями
     *
     * @return bool|static
     * @throws CustomException
     */
    public static function post(Request $request, $relation = [])
    {
        $Entity = false;
        $Model  = self::factory();
        $Data   = $request->only($Model::fields());
        unset($Data['id']);
        unset($Data['update_id']);

        if ($Model::valid($Data, 'post', $Model)) {
            $Entity = $Model::create($Data);
            if (count($relation)) {
                //$Entity = new $Model();
                foreach ($Data as $key => $value) {
                    if (array_key_exists($key, $relation)) {
                        if ($value) {
                            if (is_array($value)) {
                                $Entity->{$relation[$key]}()->sync($value);
                            } else {
                                $Entity->{$relation[$key]}()->associate($value);
                            }
                        }
                        /*else {
                            $Entity->{$relation[$key]}()->dissociate();
                        }*/
                    } else {
                  //      $Entity->{$key} = $value;
                    }
                }
                $Entity->save();
            }
            //else {$Entity = $Model::create($Data);}
        }

        return $Entity;
    }


    /**
     * Обновляет существующую запись в БД
     * используя переданные данные
     * из запроса
     * В массиве $relation необходимо передавать
     * массив из ключей связей и функций связей из модели
     * пример для модели Users:
     * ['group_id' => 'group'] - связь пользователя с группой
     *
     * @param Request $request - Запрос от клиента
     * @param array   $relation - Массив с отношениями
     *
     * @return array
     * @throws CustomException
     */
    public static function put(Request $request, $relation = [])
    {
        $Entity    = false;
        $OldEntity = false;
        $Model  = self::factory();
        $Data   = $request->only(array_merge(['update_id', 'id'], $Model::fields()));

        if (!isset($Data['update_id']) && isset($Data['id'])) {
            $Data['update_id'] = $Data['id'];
        }

        if ($Model::valid($Data, 'put', $Model)) {
            $Entity = $Model::where('id', $Data['id'])->first();
            if (!$Entity) {
                throw new CustomException(
                    [], [], 404,
                    'Запись с id "' . $Data['id'] . '" не найдена'
                );
            }
            unset($Data['update_id']);
            $OldEntity = clone $Entity;


            if (count($relation)) {
                foreach ($Data as $key => $value) {
                    if (array_key_exists($key, $relation)) {
                        if ($value) {
                            if (is_array($value)) {
                                $Entity->{$relation[$key]}()->sync($value);
                            } else {
                                $Entity->{$relation[$key]}()->associate($value);
                            }
                        } else {
                            if (!is_array($value)) {
                                $Entity->{$relation[$key]}()->dissociate();
                            }
                        }
                    } else {
                        $Entity->{$key} = $value;
                    }
                }
                $Entity->save();
            } else {
                $Entity->update($Data);
            }
        }

        return [
            'old' => $OldEntity,
            'new' => $Entity
        ];
    }

    /**
     * Вызывает все кастомные Accessor'ы
     * из модели и добавляет полученные поля
     * к элементам выборки
     *
     * @param LengthAwarePaginator|Collection $Data - выборка из БД
     *
     * @return mixed
     */
    public static function getAccessorsValues($Data) {
        $Model = self::factory();
        if ($Data instanceof LengthAwarePaginator) {
            $Data->getCollection()
                ->transform(function ($item) use ($Model) {
                    return self::collectionItemTransform($item, $Model);
                });
        } elseif ($Data instanceof Collection) {
            $Data->transform(function ($item) use ($Model) {
                return self::collectionItemTransform($item, $Model);
            });
        }
        return $Data;
    }

    /**
     * Функция для вызова кастомного Accessor'а
     * для одного элемента выборки
     *
     * @param BaseModel $item  - Элемент выборки
     * @param BaseModel $Model - Модель из которой делали выборку
     *
     * @return mixed
     */
    public static function collectionItemTransform($item, BaseModel $Model)
    {
        $fields = $Model::$accessors;
        foreach ($fields as $field) {
            $method = 'get' . ucfirst($field) . 'Attr';
            if (method_exists($Model, $method)) {
                $item->{$field} = $Model->{$method}($item);
            }
        }
        return $item;
    }

    /**
     * Функция - обёртка для Controller::dbGet()
     *
     * @param Request $request   - экземпляр Request
     * @param array   $filter    - массив с правилами фильтрации
     * @param array   $relations - массив отношений
     * @param bool    $get       - флаг возврата выборки или объекта
     *
     * @return mixed
     */
    public static function get(
        Request $request,
        array $filter = [],
        array $relations = [],
        $get = false
    ) {
        $Model = self::factory();
        return Controller::dbGet(
            $Model,
            ($request) ? $request : request(),
            $filter,
            $relations,
            $get
        );
    }

    /**
     * Возвращает первый найденый элемент по переданным данным
     *
     * @param array $data
     *
     * @return BaseModel
     */
    public static function associateFind($data)
    {
        $Model = self::factory();
        $Fields = $Model->fields();
        $Search = null;
        foreach ($data as $key => $value) {
            if (in_array($key, $Fields)) {
                if (!$Search) $Search = $Model::where($key, $value);
                $Search = $Search->where($key, $value);
            }
        }

        return $Search->first();
    }

    /**
     * Обёртка для метода self::associateFind()
     *
     * @param $data
     *
     * @return BaseModel
     */
    public static function aFind($data)
    {
        return self::associateFind($data);
    }

    /**
     * Транслит руского текста в английский
     *
     * @param $string
     *
     * @return mixed
     */
    public static function transliterate($string)
    {
        $roman = array("Sch","sch",'Yo','Zh','Kh','Ts','Ch','Sh','Yu','ya','yo','zh','kh','ts','ch','sh','yu','ya','A','B','V','G','D','E','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','','Y','','E','a','b','v','g','d','e','z','i','y','k','l','m','n','o','p','r','s','t','u','f','','y','','e');
        $cyrillic = array("Щ","щ",'Ё','Ж','Х','Ц','Ч','Ш','Ю','я','ё','ж','х','ц','ч','ш','ю','я','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Ь','Ы','Ъ','Э','а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','ь','ы','ъ','э');
        return str_replace($cyrillic, $roman, $string);
    }
}
