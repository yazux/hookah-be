# Backend CRM reppo

Содержит все необходилмые даннные для бэкенда CRM-ки.
БД разворачивается командой php artisan migration

## Документация в API

### Просмотр

Пока работает просмотр на стандартной демонстрации swagger
Позже развену на нашем сервере.

Как открыть API доки:

 * Переходим по ссылке: [swager doc](http://petstore.swagger.io/)
 * В строку в шапке вставляем ссылку: **http://be-srv1.igid24.ru/swagger**
 * Жмём "Explore"
 * Наслаждаемся документацией
 
Как открыть readme.md доки:
 
  * Переходим по ссылке: [stackedit](https://stackedit.io/editor)
  * Жмём на логотип в левом верхнем углу
  * Жмём "Import from URL"
  * В строку вставляем ссылку: **http://be-srv1.igid24.ru/readme**
  * Жмём "Ok"
  * Наслаждаемся документацией

### Редактирование

Редактирование доступно через [бесплатный редактор](http://editor.swagger.io/)

Как редактировать доки:

 * Переходим по [ссылке](http://editor.swagger.io/)
 * Жмём File->Import File
 * Выбираем наш файл swagger.yaml из папки проекта /public/swagger.yaml
 * Редактируем так как нам нужно
 
Для сохранения изменений:
 
 * В открытом редакторе жмём File->Download Yaml
 * Кидаем скачанный файл swagger.yaml в папку проекта /public/swagger.yaml, с заменой старого
 * Заливаем в свою ветку на BB
 * При слиянии доки обновятся в master'е и будут доступны по [ссылке](http://be-srv1.igid24.ru/swagger) 
   
## Состояние приложения на бэкенде

За состояние отвечает синглтон **/app/Http/Controllers/State**

Получить текущее состояние приложения можно вызвав статический метод **getInstance()**

Пример:

    use App\Http\Controllers\State;
    
    $State = State::getInstance();

Используя состояние можно получить и установить некоторые статические данные:

    use App\Http\Controllers\State;
    
    $State = State::getInstance();
    
    //получить текущего авторизованного пользователя
    $User = $State->getUser();
    
    //получить запрос, который пришёл от клиента
    $Request = $State->getRequest();
    
    //получить конфиги определённого модуля
    $moduleName = 'User';
    $UserModuleConfig = $State->getConfig($moduleName);
    
    //установить конфиги определённого модуля
    $moduleName = 'User';
    //полный массив конфигов
    $config = [];
    $UserModuleConfig = $State->setConfig($config, $moduleName)
    
   
Метод **setConfig()** служит для установки дополнительных кофигов, не описаных в файлах конфигураций,
которые могут измениться в течении жизни приложения. Пример такого свойства **load**, которое показывает
удалось ли успешно инициализировать модуль при старте приложения.

## Основные правила написания кода на бэкенде

Строго собрлюдать PSR и *ОБЯЗАТЕЛЬНО* использовать phpDoc.
При использовании в методах внедрения зависимостей (на пример Request $request)
описывать входные параметры, которые требует метод (обязательыне и не обязательные).

Регистрация модулей происходит в файле: **/app/Modules/ModulesServiceProvider.php**
Для активации модулей требуется их указывать в файле **/config/module.php**

## Модули

* Все модули располагаются в паппке /app/Modules
* Каждый модуль располагается в отдельном каталоге
* Каждый модуль должен содержать в себе следующие каталоги и файлы:
  * Controllers 
    * YourNameController1.php
    * YourNameController2.php
    * YourNameController3.php
  * Model
    * Model1.php
    * Model2.php
  * Routes
    * routes.php
  * config.json

Для корректной работы модуля он должен быть зарегистирован в файле:
**/config/module.php**, на пример:

    <?
    return [
	    'modules' => [
		    'module',
            'user'
	    ],
        'require_modules' => [
            'module',
            'user'
        ],
        'module_dir' => __DIR__.'/../app/Modules',
    ];

В секции modules добавляются все модули, которые должны работать в системе.
В секции require_modules добавляются обязательные модули, без которых система работать не будет.
Название помодуля в секции должно начинаться с маленькой буквы.
Название папки модуля должно начинаться с большой буквы.

## Конфигурации модулей

Располагаются по адресу: **/app/Modules/ModuleName/config.json**

Имеет следующую структуру:

    {
      "module_name":  "", //символьный код модуля, английские символы с маленькой буквы
      "description":  "", //описание модуля на русском языке
      "dependencies": [], //список зависимостей модуля
      "properties":   {}, //список свйоств модуля
      "actions":      {}, //список действий в модуле
      "entity":       {}  //список сущностей, доступных в модуле
    }

### module_name
  
Символьный код модуля, маленькими буквами на английском языке
Пример: User, Module, Logger, Tasks
  
### description

Описание модуля для разработчика, на русском или английском языке.
При автоматичской инициализации модуля используется для описания в БД.
  
### dependencies
  
Зависимости модуля (массив символьных кодов других модулей)
Пример: ["user", "module", "logger", "tasks"]
  
### properties
  
Объект свойств модуля (выводятся пользователю и заносятся в БД)
Своство может быть одиночного значения и множественного значения.
  
Возможные типы свойтсв модулей ([используются HTML5 поля](http://htmlbook.ru/html/input/type)):
 * text      - Текстовое поле ввода
 * textarea  - Блок текста
 * checkbox  - Флаг
 * number    - Целое число
 * radio     - Радио кнопки
 * select    - Выпадающий список выбора
 * tel       - Телефон
 * email     - Email
 * range     - Ползунок выбора
 * file      - Файл
 * hidden    - Скрытое поле
 * password  - Поле для ввода пароля
 * color     - Поле для выбора цвета
 * date      - Поле выбора даты
 * time      - Поле выбора времени
 * datetime  - Поле выбора даты и времени
  
Структура свойства одиночного значения:
  
    "exampleproperty": {
      "name":          "Пример свойства модуля", //название на русском языке
      "code":          "exampleproperty",        //символьный код на английском языке, без пробелов и спецсимволов
      "placeholder":   "Введите значение",       //placeholder для поля ввода в админке
      "type":          "text",                   //тип свойства
      "multiply":      false,                    //обязательно false для одиночного значения
      "default_value": "null",                   //значение по умолчанию
      "values":        {}                        //варианты значения свойства, обязательно [] для одиночного значения (кроме Checkbox)
    }
  
Структура свойства множественного значения:
    
    "exampleproperty": {
      "name":          "Пример свойства модуля", //название на русском языке
      "code":          "exampleproperty",        //символьный код на английском языке, без пробелов и спецсимволов
      "placeholder":   "Введите значение",       //placeholder для поля ввода в админке
      "type":          "text",                   //тип свойства
      "multiply":      true,                     //обязательно true для множественного значения
      "default_value": "null",                   //значение по умолчанию
      "values": {                                //варианты значения свойства, обязательно заполненный массив для множественного значения
        "key_1": "значение 1",                   //возможные значения свойства
        "key_2": "значение 2",
        "key_3": "значение 3",
      } 
    }
   
Пример структуры для одиночного свойтва типа Checkbox:
  
    "exampleproperty": {
      "name":          "Пример свойства модуля", //название на русском языке
      "code":          "exampleproperty",        //символьный код на английском языке, без пробелов и спецсимволов
      "placeholder":   "Введите значение",       //placeholder для поля ввода в админке
      "type":          "text",                   //тип свойства
      "multiply":      false,                    //обязательно true для множественного значения
      "default_value": "null",                   //значение по умолчанию
      "values": {                                //варианты значения свойства, обязательно заполненный массив для множественного значения
        "key_1": "значение 1",                   //значение свойства
      } 
    }
      
**Значние ключа в объекте и поля "code" должны обязательно совпадать**
  
### actions
  
Действия, определённые в модуле (на пример: добавлять/изменять пользователей в модуле users)

В файле конфигураций обязательно должно быть определено действие **<КодМодуля>_access**
Пример: **module_access** для модуля **Module**, **user_access** для модуля **User** 
Оно отвечает за возможность пользователя получать доступ к функциям модуля.
Без него модуль работать не будет.

Структура блока действия:
  
    "module_access": {
      "code":        "module_access",                     //Символьный код действия в модуля
      "name":        "Доступ к модулю",                   //Название действия (отображается в админке)
      "description": "Открывает доступ к функциям модуля" //Описание действия (отображается в админке)
    },
      
**Значние ключа в объекте и поля "code" должны обязательно совпадать**

### entity

Сущности, определённые в модуле (на пример в модуле User - пользователи)

Структура блока сущностей:
  
    "entity": {
        "users": {
          "code": "users",                            //код сущности
          "model": "App\\Modules\\User\\Model\\User", //модуль сущности
          "name": "Пользователи"                      //название сущности
        }
      },
      

## Контроллеры модулей

Располагаются по адресу: **/app/Modules/ModuleName/Controllers/YourController.php**

Все контроллеры именуются по следующим правилам:

  * Имя контроллера начинается с большой буквы
  * Используются только английские буквы без спецсимволов
  * Имя контроллера должно заканчиваться на "Controller.php"
  
namespace контроллера следующий: **namespace App\Modules\<ИмяМодуля>\Controllers;**
На пример: **namespace App\Modules\User\Controllers;**

Все контроллеры должны наследоваться от **App\Http\Controllers\Controller;**
и имплементировать интерфейс **App\Interfaces\ModuleInterface;**

### Обязательные методы и свойства контроллеров

Название модуля:

    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'User';

С большой буквы

Геттер названия модуля, используется во время загрузки модуля и получения конфигов

    /**
     * Вернёт код модуля
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

### Генерирование ответов для клиента

Для генерации ответа клиенту следует использовать конструкцию:
**return parent::response($request, $response, $response_code);**
вместо:
**retrun response()->json($response, $response_code)**

Метод parent::response() форматирует ответ для клиента в требуемую, стандартизированную форму.
Принимает следующие параметры:

  * $request - массив с пармаметрами запроса, который пришел от клиента
  * $response - ответ клиенту (array, bool, int, string, object)
  * $response_code - http код ответа для клиента (int) -   [Все возможные коды ответа](https://ru.wikipedia.org/wiki/Список_кодов_состояния_HTTP)
  
#### Исключения в ответах

В случае, если дальнейшее исполнение метода невозможно при текущих условиях, следует выбросить исключение.
Все исключения в системе автоматически отлавливаются и на основе них генерируется ответ для клиента.

Исключение имеет следующий вид:
    
    throw new CustomException(
        $request, $response, $response_code, $exception_message
    );

Пример:

    throw new CustomException(
        $request->all(),
        [], 404, 'Not found'
    );
    
Принимает следующие параметры:

  * $request - массив с пармаметрами запроса, который пришел от клиента
  * $response - ответ клиенту (array, bool, int, string, object)
  * $response_code - http код ответа для клиента (int) -   [Все возможные коды ответа](https://ru.wikipedia.org/wiki/Список_кодов_состояния_HTTP)
  * $exception_message - текст исключения, который отправится на клиент и в логи
  
### Методы родителя (Controller)

#### Выборка данных

Для выборки данных из БД используется метод родителя:
**parent::dbGet(ModuleModelInterface $model, Request $request)**

Принимает следующие параметры:

  * $model - экземпляр модели, из которой нужно произвести выборку
  * $request - массив с пармаметрами запроса, который пришел от клиента
    * page - номер необходимой для выобрки страницы (для страничной навигации)
    * count - количество записей на одной странице (для страничной навигации)
    * order_by - поле для сортировки, можно получить так: Model::fiedls() (для страничной навигации)
    * order_type - направление сортировки asc/desc (для страничной навигации)
    * filter - правила фильтрации выборки
  
  Фильтр поддерживание следующие операторы:
  
  * '<'  - Значение меньше переданного
  * '>'  - Значение больше переданного
  * '<=' - Значение меньше или равно переданному
  * '>=' - Значение больше или равно переданному
  * '='  - Значение равно переданному
  * '!=' - Значение не переданному
  * '%'  - Значение включает переданное (поиск с обеих сторон)
  * '=%' - Значение включает переданное (поиск с правой стороны)
  * '%=' - Значение включает переданное (поиск с левой стороны)
    
Поле фильтр имеет слудеющую структуру:

    filter = [
        [
            "поле_фильтрации",
            "условие_фильтации",
            "значение_поля"
        ],
        "условие_фильтрации (и/или)",
        [
            "поле_фильтрации",
            "условие_фильтации",
            "значение_поля"
        ],
    ]
    
Пример поля **filter**:

    filter = [
        [
            "id",
            "=",
            "1"
        ],
        "or",
        [
            "id",
            "=",
            "5"
        ],
    ]
    
Для выборки записей с полем равным NULL, следует отправлять запрос с параметром '?filter=[["field", "=", "null"]]'
Фильтр может искать и по массиву значений, для этого третьим параметром фильтра нужно передать массив:
'?filter=[["id", "=", ["1","2","3"]]]'. Но при таком запросе поддерживается только оператор "=", другие операторы будут игнорироваться
    
Пример полного запроса:

    http://be-srv1.igid24.ru/api/log?filter=[["entity_id", ">","1"],"and",["entity_type","=",["tasks","user","module"]]]&count=10&page=2&order_by=id&order_type=desc
  
В поле **filter** можно указывать от 1 поля для фильтрации до бесконечности. Вложенные фильтры не поддерживаются.
  
Пример использования:

    public function getActions(Request $request)
    {
        $UserCTR = new UserController();
        if (!$UserCTR->can('user_viewactions')) {
            throw new CustomException(
                'view groups', [], 403,
                'The current user does not have sufficient rights to' .
                ' view actions'
            );
        }

        return parent::response(
            $request->all(),
            parent::dbGet(new Action, $request),
            200
        );
    }
    
Метод возвращает список действий пользователей в модулях, которые есть в БД.

#### Получение конфигов

Метод  **getConfig** возвращает конфигурации модуля

Принимает следующие параметры:

  * $moduleName - символьный код модуля, конфиг которого, нужно получить
  
Пример импользования:

    use App\Http\Controllers\Controller;
    
    class ActionController extends Controller implements ModuleInterface
    {
        public $moduleName = 'User';

        public function getModuleName()
        {
            return $this->moduleName;
        }
        
        public function example()
        {
            $Controller = new Controller();
  
            $Config = $Controller->getConfig($this->getModuleName());
            
            return $Config
        }
    }

В примере метод **example** вернет массив конфигов модуля **User** 
    
#### Другие вспомогательные методы

Метод **getModuleEntity($moduleName, $jsonResponse = true)** позволяет получить список сущностей из конфига

доступен в App\Modules\Module\Controllers\ModuleController;

Метод **issetEntity($moduleCode, $entityCode)** позволяет проверить на существование сущность с переданным кодом

доступен в App\Modules\Module\Controllers\ModuleController;

Метод **getJsonTestStatus** позволяет проверять ошибки выполнения функции json_decode
  
Пример импользования:

    use App\Http\Controllers\Controller;
    
    class ActionController extends Controller implements ModuleInterface
    {
        public function example()
        {
            $Controller = new Controller();
 
            $Config = $Controller->getConfig($this->getModuleName());
            $ConfigJson = json_encode($Config);
            
            $Config = json_decode($ConfigJson);
            
            $JSONError = $Controller->getJsonTestStatus();
                       
            if ($JSONError) {
                throw new CustomException($this->getModuleName(), false, 500, $JSONError);
            }
            
            return $Config
        }
    }

Метод проверяет на корректность последнее выполнение функции json_decode.
Возвращает false, если ошибок не было. Если была ошибка, вернёт её описание.

Метод **can** позволяет проверить имеет ли пользователь доступ к выполнению какого-либо действия
опредедённого в конфиге модуля

Метод доступен в модуле **User**, в контроллере **UserController** и в моделе **User**

Пример использования:

    use App\Modules\User\Controllers\UserController;
    
    $UserController = new UserController();
    
    if (!$UserController->can('user_addgroup')) {
      throw new CustomException(
        $data, [], 403,
        'The current user does not have sufficient rights to create group'
        );
    }
    
Пример использования при помощи модели:

    User::can('user_addgroup', true);
    
Второй параметр указывает, следует ли выбрасывать исключение если пользователь не имеет доступа к действию

В примере мы проверим, имеет ли текущий пользователь права на добавление групп пользователей.
Если пользователь не авторизован, то будет возвращён статус 401.
Если происходит доступ к публичным методам, не требующим авторизации, то метод всегда вернёт true.
Метод автоматически определяет текущего пользователя из синглтона **State**.

Для использования в API данного метода необходимо сделать запрос по адресу: **/user/{login}/can/{action}**
(список действий модуля можно получить, сделав запрос по адресу: **/module/{code}/actions**)

Где вместо login передаётся логин пользователя, которому требуется проверить доступ,
а в action действие модуля, к которому проверям доступ. Метод требует авторизации.

Возвращает true или false, в зависимости от того, имеет ли пользователь доступ или нет соответственно.

Метод **modelFilter($model, $fields)** (статический) позволяет отфильтровать поля экземпляра модели для отдачи на фронтенд только необходимых,
в качестве параметров принимает экземпляр модели (на пример User) и массив необходимых полей (на пример из модели User::fields())

Пример использования:
    
    $resultFields = self::modelFilter(User::where('id', 10)->get(), User::fields());

## Модели модулей

Располагаются по адресу: **/app/Modules/ModuleName/Model/YourModel.php**

Все модели именуются по следующим правилам:

  * Имя модели начинается с большой буквы
  * Используются только английские буквы без спецсимволов
  
namespace моделей следующий: **namespace App\Modules\<ИмяМодуля>\Model;**
На пример: **namespace App\Modules\User\Model;**

Все модели должны наследоваться от **Classes\BaseModel**

### Обязательные методы и свойства моделей

Название таблицы, к котрой принадлежит модель

    protected $table = 'module_users_group_actions';

Поля таблицы, доступные для выборки пользователем

    /**
     * Поля таблицы, доступные для выборки
     *
     * @var array
     */
    protected static $fillable = [
        'id', 'name', 'group_id', 'module_id', 'sort',
        'description', 'created_at', 'updated_at'
    ];

## Роуты модулей

Располагаются по адресу: **/app/Modules/ModuleName/Routes/routes.php**

Все роуты подчиняются следующим правилам:

  * Роуты объединяются в группы **Route::group()**
  * Параметры метода **Route::group()** строго определены:

Пример:
  
    Route::group([
        'namespace' => 'App\Modules\User\Controllers',
        'as' => 'module.',
        'prefix' => 'api',
        'middleware' => 'web'
    ]);
    
Где namespace совпадает с namespace контроллера, middleware всегда использует "web", остальные дописывать по усмотрению.
Для указания нескольких middleware используется следующая конструкция:

    'middleware' => [
        'web','auth'
    ]
    
middleware "auth" пропускает к роутам только авторизованных пользователей.

Второй параметр метода **Route::group()** - функция - замыкание, с самими роутами:

    Route::group(
        [
            'namespace' => 'App\Modules\User\Controllers',
            'as' => 'module.',
            'prefix' => 'api',
            'middleware' => 'web'
        ],
        function () {
            /**
             * Логин (авторизация) пользователя по логину и паролю
             * либо по email и паролю
             */
            Route::post('/login', ['uses' => 'AuthController@Login']);
            /**
             * Регистрация пользователя/создание нового пользователя
             */
            Route::post('/signup', ['uses' => 'UserController@CreateUser']);
            /**
             * Получение обновлённого access_token при помощи refresh_token
             */
            Route::get('/login/refresh', ['uses' => 'AuthController@RefreshToken']);
        }
    );

## Логирование данных

Для отслеживания происходящешо в системе при каждом добавлении/измненении/удалении чего-либо происходит логирование события.
за это отвечает метод **write**, который находится в **LoggerController**. 

Метод принимает следующие параметры:
   
   * $module_code* - символьный код модуля
   * $action*      - символьный код действия в модуле
   * $description - текстовое подробное описание действия
   * $entity_type - тип сущности (user, task, и так далее)
   * $entity_id   - id сущности
   * $push        - false, если отправка данных на frontend не требуется и массив вида ['data' => $data], если трубется

Если требуется отправка данных на frontend, то все параметры являются обязательными. Всё, что будет передано в значении ключа 'data'
в массиве, который передаётся в параметре $push, будет отправленно событием на frontend.

Пример использования: 

    LoggerController::write(
        $this->getModuleName(),
        'add_task_coexecutor',
        'Добавление соисполнителя ' . $User->login . '('.$User->id.') к задаче номер ' . $Task->id,
        'task',
        $Task->id,
        [
            'data' => [
                'user' => self::modelFilter($User, User::fiedls()),
                'task' => self::modelFilter($Task, Tasks::fiedls())
            ]
        ]
    );
    
    
## Работа с сервисом Moneta.ru

Алгоритм регистрации в сервисе следующий (в скобках указанны функции WSDL монеты, которые используются при операции):

    - Инициализация регистрации в сервисе путём заполнения основой регистрационной формы (CreateProfile)
    - Создание профиля директора (CreateProfile, DIRECTOR)
    - Создание формы документа (паспорта) директора (CreateProfileDocument)
    - Создание банковского аккаунта компании (CreateBankAccount)
    - Ожидание подтверждения банковского аккаунта со стороны сервиса (Не работает в тестовом режиме Монеты)
    - Подтверждение аккаунта со стороны сервиса путём запроса на URL: /api/moneta/event со значением ключа действия MUVE_UNIT - означающего перенос клиента из одной группы в другую
    - Создание расширенного банковского аккаунта (CreateAccount)
    - Формирование Монетой документа - договора о работе с клиентом (в автоматическом режиме)
    - Сохранение документа на сервере и отдача его пользователю
    - Пользователь должен распечатать и заполнить документ, после отправить в офис Монета почтой и заполнить форму о отправке документов
    - Заполнение формы отправки документов
    - Далее ожидание подтверждения принятия документов со стороны сервиса (в этот момент компания уже может принимать платежи)
    
В случе, если в течении 30 дней после заполнения формы отправки документов, они не пришли в офис компании, то со стороны Монеты придёт уведомление на URL: /api/moneta/event со значением ключа действия MUVE_UNIT, о том, что клиент переносится из рабочей группы в группу "Клиенты без заявления".
И больше не может принимать платежи
     
В документации к сервису Монета указанно, что во время регистрации требуется так же заполнять профили бенефициара и учредителя (и их документы), 
но на самом деле этого делать не надо, эти данные Монета получает сама, кроме того, она не даст через API выполнить запрос CreateProfile с типом отличным от DIRECTOR


Алгоритм оплаты через сервис следующий:

    - Попытка холдирования средств на карте пользователя (Invocice)
    - Получение данных трнзакции в ответе на запрос Invocice и отправка пользователя на платёжную форму 
    - Получение проверочного запроса от Монеты (она сама отправляет запрос на URL - /moneta/event/pay)
        - Сравнение данных транзакции полученных от монеты и созданных у нас
        - Ответ монете о корректности или не корректности транзакции
    - Ожидание оплаты пользователем на платёжной форме
    - Получение от Монеты повторного проверочного запроса (она сама отправляет запрос на URL - /moneta/event/pay)
    - Сравнение данных транзакции полученных от монеты и созданных у нас
        - Если данные корректны, то холдирование средств пользователя и путём ответа монете на запрос
    - Ожадание подтверждения отправки товара продавцом или отмены заказа
    - Вслучае отмены заказа
        - Ожидание подтверждения отмены заказа от продавца
        - Вслучае подтверждения отмены
            - Возврат средств пользователю (CancelTransaction)
        - В случае отмены подтверждения
            - Арбитраж заказа
    - В случае отправки товара
        - Запрос на подтверждение транзакции и списание средств (ConfirmTransaction) 
        
Все операции обмена с сервисом Монета и уведомления от него логируются в корень проекта, в папку /logs

Запросы в сервис и его ответы логруются в файлах: moneta_log_d_m_Y.txt
Уведомления и проверочные запросы, которые присылает монета и ответы на них логируются в файлах moneta_events_log_d_m_Y.txt

Документакция:

    - https://www.moneta.ru/doc/MONETA.MerchantAPI.v2.ru.pdf
    - https://www.moneta.ru/doc/MONETA.Assistant.ru.pdf
    - Личный кабинет: https://demo.moneta.ru/operationsInfo.htm