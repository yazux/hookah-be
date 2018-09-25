<?php

namespace App\Http\Controllers;


use App\Classes\Sypexgeo;
use App\Modules\Properties\Controllers\FileController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Interfaces\ControllerInterface;
use App\Exceptions\CustomException;
use App\Modules\Module\Model\Module;
use App\Interfaces\ModuleModelInterface;


/**
 * Основной класс - контроллер
 *
 * @category Laravel_Сontrollers
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class Controller extends BaseController implements ControllerInterface
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $moduleDir = '';
    public $moduleList = [];
    protected $configFields = [
        'module_name',
        'description',
        'properties',
        'actions',
        'dependencies'
    ];

    protected static $fieldAlias = [
        'id'             => 'Номер (идентификатор)',
        'created_at'     => 'Дата создания',
        'updated_at'     => 'Дата изменения',
        'code'           => 'Символьный код',
        'description'    => 'Описание',
        'sort'           => 'Сортировка',
        'active'         => 'Активность',
        'text'           => 'Текст',
        'entity'         => 'Сущность',
        'entity_type'    => 'Сущность',
        'entity_id'      => 'Номер (id) сущности',
        'module_id'      => 'Номер (id) модуля',
        'comment_id'     => 'Номер (id) комментария',
        'file_id'        => 'Номер (id) файла',
        'name'           => 'Название (имя)',
        'type'           => 'Тип',
        'phone'          => 'Телефон',
        'email'          => 'Email',
        'customer_id'    => 'Номер (id) контрагента',
        'user_id'        => 'Номер (id) пользователя',
        'user_ip'        => 'IP адресс пользователя',
        'log_type'       => 'Тип записи лога',
        'log_action'     => 'Выполненное действие',
        'multiply'       => 'Является множественным',
        'require'        => 'Является обязательным',
        'default'        => 'Является стандартным',
        'path'           => 'Публичный путь',
        'local_path'     => 'Локальный путь',
        'property_id'    => 'Номер (id) свойства',
        'value'          => 'Значение',
        'taskmaster_id'  => 'Постановщик задачи',
        'responsible_id' => 'Ответственный',
        'project_id'     => 'Номер (id) проекта',
        'deadline'       => 'Крайний срок',
        'status'         => 'Статус',
        'status_id'      => 'Номер (id) статуса',
        'parent_task_id' => 'Номер (id) родительской задачи',
        'time_start'     => 'Время начала',
        'time_finish'    => 'Время окончания',
        'wasted_time'    => 'Затраченное время',
        'timeline_id'    => 'Номер (id) временного промежутка',
        'executor_id'    => 'Номер (id) соисполнителя',
        'task_id'        => 'Номер (id) задачи',
        'group_id'       => 'Номер (id) группы',
        'planed_date'    => 'Планируемая дата',
        'actual_date'    => 'Фактическая дата',
        'login'          => 'Логин',
        'password'       => 'Пароль'
    ];

    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = '';

    public function getModuleName()
    {
        $self = self::factory();
        return $self->moduleName;
    }

    /**
     * Создаёт экземпляр дочернего класса при вызове
     *
     * @return Controller
     */
    public static function factory()
    {
        $class = get_called_class();
        return new $class();
    }

    protected static function getLoggerAlias ($key) {
        if (array_key_exists($key, self::$fieldAlias)) {
            return self::$fieldAlias[$key] . ': ';
        } else {
            return '';
        }
    }

    /**
     * Конструктор, устанавливает путь к модулям и список модулей
     *
     * Controller constructor.
     */
    function __construct()
    {
        $this->moduleDir  = config("module.module_dir");
        $this->moduleList = config("module.modules");
    }

    /**
     * Фильтрует экземпляр модели в соответствии с
     * переданными полями в массиве $fields
     *
     * @param object|array $model  - Экземпляр модели
     * @param array        $fields - поля модели, полученные методом Model::fields()
     *
     * @return array
     */
    public static function modelFilter($model, $fields)
    {
        $result = [];

        if (is_object($model)) {
            $model = json_decode(json_encode($model), true);
        }

        if (!is_array($fields)) {
            return $model;
        }

        foreach ($model as $key => $modelField) {
            if (in_array($key, $fields)) {
                $result[$key] = $modelField;
            }
        }

        return $result;
    }

    /**
     * Вспомогательный метод, служит для выборки данных из модели
     * Параметры GET запроса для параметра $request:
     * page - номер страницы для отображения постраничной навигации
     * count - количество элементов для отображения на странице
     * order_by - поле для сортировки (одно из полей массива ModelName::fields())
     * order_type - направление сортировки (asc/desc)
     * filter - массив фильтра в json формате (пример ниже - $filter)
     * Правила фильтрации: $filter = [
     *      ['поле', 'условие', 'значение'],
     *      'and'
     *      ['id', '>', '10'],
     *      'and'
     *      ['sort', '<', '100']
     *      'or'
     *      ['email', '=', 'example@gmail.com']
     *      'or'
     *      ['login', '!=', 'example']
     * ];
     * Массив обязательно содержит массивы
     * Доступные условия для фильтра: <, >, =, !=, ?, %, >=, <=
     *
     * @param ModuleModelInterface $model     - модель из которой производим выборку
     * @param Request              $request   - экземпляр Request
     * @param array                $filter    - массив с правилами фильтрации
     * @param array                $relations - массив отношений
     * @param boolean              $get       - флаг возврата выборки или объекта
     *
     * @return mixed
     * @throws CustomException
     */
    public static function dbGet(
        ModuleModelInterface $model, Request $request,
        $filter = [], $relations = [], $get = false
    ) {
        $data = $request->only('page', 'count', 'order_by', 'order_type', 'filter');
        //получим поля модели, доступные для выборки
        $fields = $model::fields();
        $_fields = $fields;
        $fields = self::attachFieldNamesToTable($fields, $model);

        //добавляем сортировку
        $result = $model::select($fields)->orderBy(
            ($data['order_by']   && in_array($data['order_by'], $_fields)) ? $data['order_by'] : 'id',
            ($data['order_type'] && in_array($data['order_type'], ['asc','desc'])) ? $data['order_type'] : 'asc'
        );
        //если в запросе есть фильтр
        if (isset($data['filter'])) {
            //читаем массив с фильтром
            if (is_string($data['filter'])) {
                $data['filter'] = json_decode($data['filter'], true);
            }
            //проверим ошибки декодирования
            $JSONError = self::getJsonTestStatus();
            //если json считался c ошибками, то скажем пользователю об этом
            if ($JSONError) {
                throw new CustomException($data['filter'], false, 500, $JSONError);
            }
            $filter = $data['filter'];
            $request->merge(['filter' => $filter]);
        }

        $relationsFilter = false;
        if (isset($filter['model']) && isset($filter['relation'])) {
            $relationsFilter = $filter['relation'];
            $filter = $filter['model'];
        } elseif (isset($filter['model']) && !isset($filter['relation'])) {
            $filter = $filter['model'];
        } elseif (!isset($filter['model']) && isset($filter['relation'])) {
            $relationsFilter = $filter['relation'];
            $filter = [];
        }

        //если передали массив фильтров
        //и он не является массивом с фильтрами,
        //а параметры фильтра лежат в изначальном массиве $filter
        if (count($filter)
            && array_key_exists(0, $filter)
            && !is_array($filter[0])
        ) {
            //это ошибка, и надо выкинуть исключение
            throw new CustomException(
                ['filter' => $filter], false, 400,
                "Incorrect filter format. ".
                "Correct example: ".
                "\$filter = [['id', '>', '10'], 'and', ['id', '<', '20']];"
            );
        }

        //если передали массив филтров и исключение не было выброшено,
        //значит фильтруем данные
        if (count($filter)) {
            //Старый метод через функцию whereRaw
            //$filterRaw = self::buildFilterRowString($filter, $model, $fields);
            //дополняем запрос по установленному фильтру
            //$result = $result->whereRaw($filterRaw);

            //новый метод через ORM
            if (isset($data['filter']['condition'])
                && $data['filter']['condition'] == 'and'
            ) { //тут фильтруем И по условию модели И по условию связи
                $result = $result->where(
                    function ($q) use ($filter, $model, $fields, $result) {
                        $q = self::buildFilterRowString_new(
                            $filter, $model, $fields, $q
                        );
                    }
                );
            } else { //тут фильтруем по условия модели ИЛИ по условию связи
                $result = self::buildFilterRowString_new(
                    $filter, $model, $fields, $result
                );
            }

        }

        //если передали фильтр по смежным таблицам

        //filter={
        //  "model":[["id","=","4"],"or",["id","=","5"]],
        //  "relation":[["model","field","=","1"],"or",["model2","field2","=","3"]]
        //}
        $result_relation = '';
        if ($relationsFilter && count($relations)) {
            $result = self::buildRelationFilterRowString(
                $relationsFilter, $result, $relations
            );
        }


        //если пришёл массив со связями
        if (count($relations)) {
            //то добираем их из бд
            foreach ($relations as $key => $model) {

                if (is_array($model)) {
                    if (isset($model['model'])) {
                        $fields = [];
                        $with = false;
                        if (isset($model['fields']) && is_array($model['fields'])) {
                            $fields = $model['fields'];
                        }

                        if (isset($model['with'])) {
                            $with = $model['with'];
                        }

                        $Model = $model['model'];
                        //смотрим какие поля доступны для выборки
                        //если их не передали из контроллера
                        if (!count($fields)) {
                            $fields = $Model::fields();
                        }

                        //выбираем их из связаной модели
                        $result->with(
                            [$key => function ($query) use ($fields, $with) {
                                if ($with) {
                                    $query->select($fields)->with($with);
                                } else {
                                    $query->select($fields);
                                }
                            }]
                        );
                    }
                } else {
                    //смотрим какие поля доступны для выборки
                    $fields = $model::fields();
                    $fields = self::attachFieldNamesToTable($fields, $model);

                    //выбираем их из связаной модели
                    $result->with(
                        [$key => function ($query) use ($fields) {
                            $query->select($fields);
                        }]
                    );
                }

            }
        }
        $request->merge(['SQL' => $result->toSql(), 'fields' => $_fields]);
        if (!$get) {
            if ($data['count']) {
                $result = $result->paginate($data['count']);
            } else {
                $result = $result->get();
            }
        }

        return $result;
    }

    /**
     * Добавляет к каждому названию поля название таблицы
     * ['id', 'name', 'sort'] => ['table.id', 'table.name', 'table.sort']
     *
     * @param array                $fields - Массив полей БД из модели
     * @param ModuleModelInterface $model  - Модель
     *
     * @return array
     */
    public static function attachFieldNamesToTable(array $fields,
        ModuleModelInterface $model
    ) {
        $modelObj = new $model();
        foreach ($fields as &$field) {
            $field = $modelObj->table . '.' . $field;
        } unset($field);

        return $fields;
    }


    public static function buildRelationFilterRowString(
        array $filter, $result, $relations
    ) {
        $OrKey = false;
        foreach ($filter as $itemF) {
            if (!is_array($itemF)) {
                if (strtoupper($itemF) == 'OR') {
                    $OrKey = true;
                } else {
                    $OrKey = false;
                }
            } else {
                $itemF = [
                    'model'  => $itemF[0],
                    'field'  => $itemF[1],
                    'symbol' => $itemF[2],
                    'value'  => $itemF[3],
                ];

                $modelObj = $relations[ $itemF['model'] ];
                if (is_array($modelObj) && isset($modelObj['model'])) {
                    $modelObj = $modelObj['model'];
                }

                if (!isset($modelObj)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Модель для фильтрации по связи не найдена"
                    );
                }

                $table = $modelObj->table;



                //проверям параметры на корректность
                $paramArray = ['<','>','=','!=','?','%','=%','%=','>=','<=','!=!','==='];
                if (!in_array($itemF['symbol'], $paramArray)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Incorrect filter parameter: '".$itemF['symbol']."'. ".
                        "Accepted parameters: " . implode(', ', $paramArray)
                    );
                }



                $itemF['field'] = $table. '.' . $itemF['field'];
                //если 3-тий параметр массив
                if (is_array($itemF['value'])) {
                    if ($OrKey) {
                        $result = $result->whereHas(
                            $itemF['model'], function ($q) use ($itemF) {
                            $q->orWhereIn($itemF['field'], $itemF['value']);
                        }
                        );
                    } else {
                        $result = $result->whereHas(
                            $itemF['model'], function ($q) use ($itemF) {
                            $q->whereIn($itemF['field'], $itemF['value']);
                        }
                        );
                    }
                } else {

                    if ($itemF['symbol'] == '='
                        && strtoupper($itemF['value']) == 'NULL'
                    ) {
                        if ($OrKey) {
                            $result = $result->whereHas(
                                $itemF['model'], function ($q) use ($itemF) {
                                $q->orWhereNull($itemF['field'], $itemF['value']);
                            }
                            );
                        } else {
                            $result = $result->whereHas(
                                $itemF['model'], function ($q) use ($itemF) {
                                $q->whereNull($itemF['field'], $itemF['value']);
                            }
                            );
                        }
                    } else {

                        if ($OrKey) {
                            $selectKey = 'orWhere';
                        } else {
                            $selectKey = 'where';
                        }

                        //составляем строку запроса
                        switch ($itemF['symbol']) {
                            case '%':
                                $result = $result->whereHas(
                                    $itemF['model'],
                                    function ($q) use ($itemF, $selectKey) {
                                        $q->{$selectKey}(
                                            $itemF['field'], 'like',
                                            '%' . $itemF['value'] . '%'
                                        );
                                    }
                                );
                                break;
                            case '=%':
                                $result = $result->whereHas(
                                    $itemF['model'], function ($q) use ($itemF, $selectKey) {
                                    $q->{$selectKey}(
                                        $itemF['field'], 'like',
                                        $itemF['value'] . '%'
                                    );
                                }
                                );
                                break;
                            case '%=':
                                $result = $result->whereHas(
                                    $itemF['model'], function ($q) use ($itemF, $selectKey) {
                                    $q->{$selectKey}(
                                        $itemF['field'], 'like',
                                        '%' . $itemF['value']
                                    );
                                }
                                );
                                break;
                            case '!=':
                                $result = $result->whereDoesntHave(
                                    $itemF['model'], function ($q) use ($itemF, $selectKey) {
                                    $q->{$selectKey}($itemF['field'], $itemF['value']);
                                }
                                );
                                break;
                            case '!=!':
                                $result = $result->doesntHave($itemF['model']);
                                break;
                            case '===':
                                $result = $result->has($itemF['model']);
                                break;
                            default:
                                $result = $result->whereHas(
                                    $itemF['model'], function ($q) use ($itemF, $selectKey) {
                                    $q->{$selectKey}($itemF['field'], $itemF['value']);
                                }
                                );
                                break;
                        }
                    }
                }
                $OrKey = false;

            }
        }

        return $result;
    }

    public static function buildFilterRowString_new(array $filter,
        ModuleModelInterface $table, array $fields, $result
    ) {
        $OrKey    = false;
        $modelObj = new $table();
        $table    = $modelObj->table;
        foreach ($filter as $itemFilter) {
            //если в $itemFilter массив, значит это
            //условие для фильтации
            if (is_array($itemFilter)) {
                $itemFilter = [
                    'field'  => $table . '.' . $itemFilter[0],
                    'symbol' => $itemFilter[1],
                    'value'  => $itemFilter[2],
                ];


                //проверям параметры на корректность
                $paramArray = ['<','>','=','!=','?','%','=%','%=','>=','<='];
                if (!in_array($itemFilter['symbol'], $paramArray)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Incorrect filter parameter: '".$itemFilter['symbol']."'. ".
                        "Accepted parameters: " .
                        " '<','>','=','!=','?','%','=%','%=','>=','<='"
                    );
                }

                //если пытаются фильтровать по полю, которого нет в тлибце
                if (!in_array($itemFilter['field'], $fields)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Field: '".$itemFilter['field']."'. is not exist in table.".
                        " Existing fields: ".implode(', ', $fields)
                    );
                }

                //если 3-тий параметр массив
                if (is_array($itemFilter['value'])) {
                    if ($OrKey) {
                        $result = $result->orWhereIn(
                            $itemFilter['field'], $itemFilter['value']
                        );
                    } else {
                        $result = $result->whereIn(
                            $itemFilter['field'], $itemFilter['value']
                        );
                    }
                } else {

                    if ($itemFilter['symbol'] == '='
                        && strtoupper($itemFilter['value']) == 'NULL'
                    ) {
                        if ($OrKey) {
                            $result = $result->orWhereNull($itemFilter['field']);
                        } else {
                            $result = $result->whereNull($itemFilter['field']);
                        }
                    } else {

                        if ($OrKey) {
                            $selectKey = 'orWhere';
                        } else {
                            $selectKey = 'where';
                        }

                        //составляем строку запроса
                        switch ($itemFilter['symbol']) {
                            case '%':
                                $result = $result->{$selectKey}(
                                    $itemFilter['field'],
                                    'like',
                                    '%' . $itemFilter['value'] . '%'
                                );
                                break;
                            case '=%':
                                $result = $result->{$selectKey}(
                                    $itemFilter['field'],
                                    'like',
                                    $itemFilter['value'] . '%'
                                );
                                break;
                            case '%=':
                                $result = $result->{$selectKey}(
                                    $itemFilter['field'],
                                    'like',
                                    '%' . $itemFilter['value']
                                );
                                break;
                            default:
                                $result = $result->{$selectKey}(
                                    $itemFilter['field'], $itemFilter['value']
                                );
                                break;
                        }
                    }
                }
                $OrKey = false;
            } else {
                //если нет, значит фильтрация идёт по нескольким полям (or, and)
                //и надо их объединить
                if (strtoupper($itemFilter) == 'OR') {
                    $OrKey = true;
                } else {
                    $OrKey = false;
                }

            }
        }

        return $result;
    }

    /**
     * Обёртка для функции self::uploadOneFileFormRequest
     *
     * @param Request $request      - запрос от клиента
     * @param string  $propertyName - название свойства, в котором лежит файл
     *
     * @return FileController|boolean
     */
    public static function upFile(Request $request, $propertyName = '')
    {
        return self::uploadOneFileFormRequest($request, $propertyName);
    }

    /**
     * Обёртка для функции self::uploadAllFilesFromRequest
     *
     * @param Request $request - запрос от клиента
     *
     * @return array
     * @throws CustomException
     */
    public static function upFiles(Request $request)
    {
       return self::uploadAllFilesFromRequest($request);
    }

    /**
     * Загружает 1 файл на сервер и возвращает его объект
     *
     * @param Request $request      - запрос от клиента
     * @param string  $propertyName - название свойства, в котором лежит файл
     *
     * @return FileController|boolean
     */
    public static function uploadOneFileFormRequest(Request $request, $propertyName = '')
    {
        if (!$propertyName || !$request->hasFile($propertyName)) {
            return false;
        }
        return FileController::call('upload', $request->file($propertyName));
    }

    /**
     * Загружает несколько файлов на сервер
     * и возвращает массив с объектами файлов
     *
     * @param Request $request - запрос от клиента
     *
     * @return array
     * @throws CustomException
     */
    public static function uploadAllFilesFromRequest(Request $request)
    {
        $files  = $request->allFiles();
        $result = [];
        if (count($files['file']) > 10) {
            throw new CustomException(
                $request->all(), false, 400,
                'Максимальное количество файлов для загрузки - 10'
            );
        }

        if (isset($files['file']) && count($files['file'])) {
            foreach ($files['file'] as $file) {
                $result[] = FileController::call('upload', $file);
            }
        }
        return $result;
    }

    /**
     * Строит SQL строку запроса из массива фильтров из GET запроса
     *
     * @param array                $filter - Массив фильтров из GET запроса
     * @param ModuleModelInterface $table  - Модель
     * @param array                $fields - Массив полей БД из модели
     *
     * @return string
     * @throws CustomException
     */
    public static function buildFilterRowString(array $filter,
        ModuleModelInterface $table, array $fields
    ) {
        $filterRaw = '';
        $modelObj = new $table();
        $table = $modelObj->table;
        foreach ($filter as $itemFilter) {
            //если в $itemFilter массив, значит это
            //условие для фильтации
            if (is_array($itemFilter)) {

                //проверям параметры на корректность
                $paramArray = ['<','>','=','!=','?','%','=%','%=','>=','<='];
                if (!in_array($itemFilter[1], $paramArray)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Incorrect filter parameter: '".$itemFilter[1]."'. ".
                        "Accepted parameters: " .
                        " '<','>','=','!=','?','%','=%','%=','>=','<='"
                    );
                }

                //если пытаются фильтровать по полю, которого нет в тлибце
                if (!in_array($table . '.' . $itemFilter[0], $fields)) {
                    throw new CustomException(
                        ['filter' => $filter], false, 400,
                        "Field: '".$itemFilter[0]."'. is not exist in table.".
                        " Existing fields: ".implode(', ', $fields)
                    );
                }

                //обернём строку в ковычки, если она без них
                if (is_string($itemFilter[2])
                    && !strripos($itemFilter[2], "'")
                    && strtoupper($itemFilter[2]) != 'NULL'
                ) {
                    $itemFilter[2] = "'" . $itemFilter[2] . "'";
                }

                //если 3-тий параметр массив
                if (is_array($itemFilter[2])) {
                    $filterRaw .= $itemFilter[0] . ' IN ' .
                        '(' . implode(', ', $itemFilter[2]) . ')';
                } else {

                    if ($itemFilter[1] == '='
                        && strtoupper($itemFilter[2]) == 'NULL'
                    ) {
                        $itemFilter[1] = '';
                        $itemFilter[2] = 'IS NULL';
                    }

                    //составляем строку запроса
                    switch ($itemFilter[1]) {
                        case '%':
                            $filterRaw .= $table . '.' . $itemFilter[0] .
                                " LIKE '%" . str_replace("'", '', $itemFilter[2]) . "%'";
                            break;
                        case '=%':
                            $filterRaw .= $table . '.' . $itemFilter[0] .
                                " LIKE '" . str_replace("'", '', $itemFilter[2]) . "%'";
                            break;
                        case '%=':
                            $filterRaw .= $table . '.' . $itemFilter[0] .
                                " LIKE '%" . str_replace("'", '', $itemFilter[2]) . "'";
                            break;
                        default:
                            $filterRaw .= $table . '.' . $itemFilter[0] .
                                ' ' . $itemFilter[1] . ' ' . $itemFilter[2];
                            break;
                    }
                }

            } else {
                //если нет, значит фильтрация идёт по нескольким полям (or, and)
                //и надо их объединить
                $filterRaw .= ' '.$itemFilter.' ';
            }
        }

        return $filterRaw;
    }


    /**
     * Проверяет доступность модуля и пишет его в БД,
     * если его там нет
     * запись происходит на основе данных ин файла config.json
     * внутри папки с модулем
     *
     * @param string $moduleName - символьный код модуля
     *
     * @return boolean
     */
    public function checkModule($moduleName)
    {
        $Config = $this->getConfig($moduleName);
        $Module = Module::where('code', $moduleName)->first();

        //если модуля с таким кодом не найдено
        if (!$Module) {
            //добавляем модуль как неактивный
            $Module = new Module();
            $Module->CreateModule(
                [
                    'code'        => $Config['module_name'],
                    'description' => $Config['description'],
                    'active'      => 1
                ]
            );
        }

        return true;
    }

    /**
     * Форматирует ответ для клиента, приводит его к правильному виду
     *
     * @param mixed   $request    - запрос, который пришёл с клиента
     * @param mixed   $response   - отовый к отправке на клиент
     * @param integer $statusCode - статус ответа (200, 500, 404 и т.д.)
     *
     * @return mixed
     */
    public function response($request, $response, $statusCode)
    {
        return response()->json(
            [
                'success' => true,
                'status'  => $statusCode,
                'errors'  => [
                    'messages' => '',
                    'errors'   => ''
                ],
                'request'  => $request,
                'response' => $response
            ],
            $statusCode
        );
    }

    /**
     * Возвращает конфиги модуля, по его имени
     *
     * @param string $moduleName - символьный код модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function getConfig($moduleName = null)
    {
        // TODO: Implement getConfig() method.

        $State = State::getInstance();
        $config = $State->getConfig($moduleName);

        if (!array_key_exists($moduleName, $config)) {
            //проверим переданное имя модуля
            if (!$moduleName || $moduleName == null || $moduleName == '') {
                throw new CustomException(
                    $moduleName, false, 400,
                    "Undefined module name: " . $moduleName
                );
            }

            //получим список модулей из конфига
            if (!count($this->moduleList)) {
                $this->moduleList = config("module.modules");
            }
            if (!$this->moduleDir) {
                $this->moduleDir = config("module.module_dir");
            }

            $config = $this->correctModuleValidator($moduleName);

            $JSONError = self::getJsonTestStatus();
            //если json считался без ошибок, возвращаем массив с конфигами
            if ($JSONError) {
                throw new CustomException($moduleName, false, 500, $JSONError);
            }
            $configError = $this->checkConfigFields($config, $moduleName);

            $State->setConfig($config, $moduleName);
        } else {
            $config = $config[$moduleName];
        }

        return $config;
    }

    /**
     * Проверяет наличие всех обязательных полей
     * в файле конфигураций модуля
     *
     * @param array  $config     - массив конфигов модуля из файла config.json
     * @param string $moduleName - символьный код модуля
     *
     * @return boolean
     * @throws CustomException
     */
    public function checkConfigFields($config, $moduleName)
    {
        foreach ($this->configFields as $field) {
            if (!array_key_exists($field, $config)) {
                throw new CustomException(
                    '', false, 500, "Field '".$field.
                    "' is not defined in configuration file".
                    " in module '".$moduleName."'"
                );
            }
        }

        if (!array_key_exists(strtolower($moduleName).'_access', $config['actions'])) {
            throw new CustomException(
                '', false, 500, "Actions '".$moduleName."_access'".
                " is not defined in module '".$moduleName."'"
            );
        }

        return true;
    }

    /**
     * Проверяет существует ли модуль и его конфиг,
     * читает конфиг в ассоциативный массив и возвращает его
     * выбрасывает исключения в случае ошибок чтения или преобразования
     *
     * @param string $moduleName - символьный код модуля
     *
     * @return mixed
     * @throws CustomException
     */
    public function correctModuleValidator($moduleName)
    {
        $moduleList = $this->moduleList;
        //проверим наличие имени модуля в массиве всех модулей в конфиге
        if (!in_array(strtolower($moduleName), $moduleList)) {
            throw new CustomException(
                $moduleName, $moduleList, 404,
                "Module '".$moduleName."' is not defined".
                " in module list"
            );
        }
        //проверим наличие папки модуля в app/Modules
        if (!is_dir($this->moduleDir.'/'.ucfirst($moduleName))) {
            throw new CustomException(
                $moduleName, false, 501, "Module folder is not defined".
                " on module " . $moduleName
            );
        }
        //проверим наличе файла конфигурации в папке с модулем
        if (!file_exists($this->moduleDir.'/'.ucfirst($moduleName).'/config.json')) {
            throw new CustomException(
                $moduleName, false, 501, "Config file is not defined".
                " on module " . $moduleName
            );
        }
        //читаем файл конфигурации
        $fileContent = file_get_contents(
            $this->moduleDir.'/'.ucfirst($moduleName).'/config.json'
        );
        //выкинем исключение, если файл прочитать не удалось
        if (!$fileContent) {
            throw new CustomException(
                $moduleName, false, 500, "Can not read configuration file".
                " on module " . $moduleName
            );
        }

        $config = json_decode($fileContent, true);

        return $config;
    }

    /**
     * Проверяет на ошибку последнее выполнение функции json_decode
     * В случае ошибки вернёт строку с текстом ошибки, в случае успеха false
     *
     * @return bool|string
     */
    public static function getJsonTestStatus()
    {
        $result = 'Ошибка разбора JSON строки';
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return false;
            case JSON_ERROR_DEPTH:
                return $result.': память переполненна.';
            case JSON_ERROR_STATE_MISMATCH:
                return $result.'';
            case JSON_ERROR_CTRL_CHAR:
                return $result.': в строке присутствуют некорректные символы.';
            case JSON_ERROR_SYNTAX:
                return $result.': JSON строка имеет некорректный формат.';
            case JSON_ERROR_UTF8:
                return $result.': неизвестная кодировка.';
            default:
                return $result.': неизвестная ошибка,';
        }
    }

    /**
     * Возвращает переданный масси, в котором ключи массива
     * заменены на ключи из дочернего массива (передаётся в $key)
     *
     * @param string $key   - ключ в элементе массива
     * @param array  $array - ассоциативный массив
     *
     * @return bool
     */
    public static function arrayToValueKey($key, $array)
    {

        $result = false;
        if (!$array || !$key) {
            return $result;
        }
        $array = json_decode(json_encode($array), true);

        foreach ($array as $item) {
            if (!array_key_exists($key, $item)) {
                continue;
            }
            $result[$item[$key]] = $item;
        }

        return $result;
    }

    /**
     * Вызывает нестатичный метод дочернего класса
     *
     * @param string $method    - Название метода
     * @param array  $arguments - Аргументы
     *
     * @return mixed
     * @throws CustomException
     */
    public static function call($method = '', ...$arguments)
    {
        $controller = self::factory();
        if (!method_exists($controller, $method)) {
            throw new CustomException([
                'method'    => $method,
                'arguments' => $arguments
            ], [], 500, 'Метода "' . $method
                . '" не существует в классе "'
                . get_called_class() . '"'
            );
        }

        return $controller->{$method}(...$arguments);
    }

    /**
     * Генерирует рандомную строку заданной длины
     *
     * @param int $length - длина строки
     *
     * @return string
     */
    public function getRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
