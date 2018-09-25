<?php

namespace App\Modules\Logger\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EventPusher;
use App\Modules\Companies\Controllers\CompanyController;
use App\Modules\Logger\Model\Logger;
use App\Modules\Market\Model\Product;
use App\Modules\Module\Controllers\ModuleController;
use App\Modules\Module\Model\Module;
use App\Modules\News\Model\News;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Model\Group;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UsersToGroup;
use App\Modules\User\Model\Action;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Класс для работы с логами
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class LoggerController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Logger';

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
     * Создаёт запись в логе
     *
     * @param string  $module_code    - символьный код модуля
     * @param mixed   $action         - символьный код действия в модуле
     * @param string  $description    - описание действия
     * @param string  $entity_type    - тип сущности
     * @param string  $entity_id      - id сущности
     * @param boolean $push           - флаг, указывающий нужно ли пушить событие либо массив, если нужно
     * @param array   $putChangedData - массив со старой (первый элемент) и новой (второй элемент) сущностью
     * @param integer $company_id     - id компании
     *
     * @return mixed
     * @throws CustomException
     */
    public static function write(
        $module_code,
        $action,
        $description = null,
        $entity_type = null,
        $entity_id = null,
        $push = false,
        $putChangedData = [],
        $company_id = null
    ) {

        $UserToReturn = [];
        //массив, который в итоге будем записывать в лог
        $WriteArray = [
            'log_action' => $action,
            'log_type' => 'system',
            'description' => htmlspecialchars($description),
        ];


        $Module = new ModuleController();
        //проверим, существут ли модуль и загружен ли он
        $Module = $Module->exist($module_code);
        if (!$Module) {
            throw new CustomException(
                [$module_code, $action], false, 500,
                'Module is not load, check log table'
            );
        }

        $WriteArray['module_id'] = $Module['id'];

        $State = State::getInstance();
        //вытащим из состояния текущего пользователя
        //если там false, значит система работает в автоматическом режиме
        //и действие производит не пользователь
        $CurrentUser = $State->getUser();
        //если дейтвие производит пользователь
        if ($CurrentUser) {
            $WriteArray['log_type'] = 'user';
            $WriteArray['user_id'] = $CurrentUser['id'];
            $WriteArray['user_ip'] = $CurrentUser['ip'];

            $UserToReturn = $CurrentUser;
        } else {
            $UserToReturn = [
                'id' => false,
                'name' => 'Системное событие'
            ];
        }


        //получим действие, которое выполняет пользователь
        $Module = new ModuleController();
        $Action = $Module->issetAction($module_code, $WriteArray['log_action']);
        if (!$Action) {
            throw new CustomException(
                [$module_code, $action], false, 500,
                'Action is not defined in module'
            );
        }

        $originalDescription = $WriteArray['description'];
        if ($CurrentUser) {
            $State = State::getInstance();
            $User = $State->getUser();
            $WriteArray['description'] = 'Пользователь ' . $User['login'] .
                ' выполнил действие: ';
        } else {
            $WriteArray['description'] = 'В системе выполнилось действие: ';
        }


        if (!$originalDescription) {
            $WriteArray['description'] .= $Action['name'];
            if ($entity_id) {
                $WriteArray['description'] .= ' номер ' . $entity_id;
            }
        } else {
            $WriteArray['description'] .= $originalDescription;
        }


        if (count($putChangedData) == 2) {
            $oldFields = json_decode(json_encode($putChangedData[0]), true);
            $newFields = json_decode(json_encode($putChangedData[1]), true);
            $changedString = '';
            foreach ($oldFields as $key => $oldField) {
                if ($oldField != $newFields[$key]) {
                    $changedData = $putChangedData[1][$key];
                    if (!is_array($changedData)) {
                        $changedString .= self::getLoggerAlias($key) .
                            $oldField . ' -> ' . $putChangedData[1][$key] . '</br>';
                    }
                }
            }
            $WriteArray['description'] .= '<br/>' . $changedString;
        }

        //создаём новую запись в логе
        $Logger = new Logger();
        $Logger->user_ip     = (array_key_exists('user_ip', $WriteArray)) ? $WriteArray['user_ip'] : NULL;
        $Logger->log_type    = $WriteArray['log_type'];
        $Logger->log_action  = $WriteArray['log_action'];
        $Logger->description = $WriteArray['description'];
        $Logger->entity_type = $entity_type;
        $Logger->entity_id   = $entity_id;
        if ($company_id)  $Logger->company()->associate($company_id);
        if (isset($WriteArray['user_id'])) {
            $Logger->user()->associate($WriteArray['user_id']);
        }
        $Logger->module()->associate($WriteArray['module_id']);
        $Logger->save();



        //пушим событие, если это требуется
        if ($push && is_array($push) && array_key_exists('data', $push)) {
            $event = EventPusher::event(
                EventPusher::getDefaultChannel(),
                $action,
                [
                    'time' => time(),
                    'action' => $action,
                    'data' => $push['data'],
                    'user' => $UserToReturn
                ]
            );
        }
        
        return $Logger;
    }


    /**
     * Создаёт запись в логе через api запрос
     * Параметры POST запроса:
     * module_code - символьный код модуля
     * action - символьный код действия в модуле
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function writeApi(Request $request)
    {
        $data = $request->only('module_code', 'action', 'description');

        $Module = new ModuleController();
        //проверим, существует ли в конфигах модуля переданное действие
        if (!$Module->issetAction($data['module_code'], $data['action'])) {
            throw new CustomException(
                [$data['module_code'], $data['action']], false, 400,
                'Action "'.$data['action'].'" is not find in module "'.
                $data['module_code'].'"'
            );
        }

        //пишем в лог
        $result = self::write(
            $data['module_code'],
            $data['action'],
            $data['description']
        );

        return parent::response(
            [$data['module_code'], $data['action']],
            $result, 200
        );
    }

    /**
     * Удаляет из лога запись с переданным id
     * Вернёт id удалённой записи
     *
     * @param integer $id - id записи, которую надо удалить
     *
     * @return mixed
     * @throws CustomException
     */
    public function remove($id)
    {
        User::can('logger_remove', true);
        $UserController = new UserController();

        $result = Logger::where('id', $id)->delete();

        return parent::response(
            ['id' => $id], $result, 200
        );
    }


    /**
     * Удаляет все записи из лога
     * Вернёт количество удалённых записей
     * Параметры DELTE запроса:
     * from - Дата с коротой нужно удалить записи
     * to - Дата до которой нужно удалить записи
     * Можно передать только 1 параметр, тогда удалятся
     * все записи до переданной даты либо после (зависит от параметра)
     * Если не передавать ничего, то удалятся все записи
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeAll(Request $request)
    {
        User::can('logger_remove', true);

        $data = $request->only('from', 'to');

        //если ни одной даты не передали
        if (!$data['from'] && !$data['to']) {
            $result = Logger::where('id', '>', '0');
        } else {
            //если есть обе даты, то удаляем промежеток записей
            if ($data['from'] && $data['to']) {
                $result = Logger::where('created_at', '>', $data['from'])
                    ->where('created_at', '<', $data['to']);
            } else {
                //если есть только дата "ДО"
                if (!$data['from']) {
                    //удаляем все записи до этой даты
                    $result = Logger::where('created_at', '<', $data['to']);
                }
                //если есть только дата "ПОСЛЕ"
                if (!$data['to']) {
                    //удаляем все записи старше этой даты
                    $result = Logger::where('created_at', '>', $data['from']);
                }
            }
        }

        $result = $result->delete();

        return parent::response(
            $data, $result, 200
        );
    }

    /**
     * Удаляет записи из лога за период времени
     * Вернёт количество удалённых записей
     * Параметры POST запроса:
     * from - дата с которой нужно удалить логи (гггг-мм-дд чч:мм:сс)
     * to - дата по которую нужно удалить логи (гггг-мм-дд чч:мм:сс)
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function removeDate(Request $request)
    {
        User::can('logger_remove', true);

        $data = $request->only('from', 'to');

        //если ни одной даты не передали
        if (!$data['from'] && !$data['to']) {
            throw new CustomException(
                false, [], 400,
                'Date "to"/"from" is not defined.'
            );
        }

        //если есть обе даты, то удаляем промежеток записей
        if ($data['from'] && $data['to']) {
            $result = Logger::where('created_at', '>', $data['from'])
                ->where('created_at', '<', $data['to']);
        } else {
            //если есть только дата "ДО"
            if (!$data['from']) {
                //удаляем все записи до этой даты
                $result = Logger::where('created_at', '<', $data['to']);
            }
            //если есть только дата "ПОСЛЕ"
            if (!$data['to']) {
                //удаляем все записи старше этой даты
                $result = Logger::where('created_at', '>', $data['from']);
            }
        }
        $result = $result->delete();
        return parent::response($data, $result, 200);
    }

    /**
     * Возвращает список логов в БД
     * Параметры GET запроса:
     * page - номер страницы для отображения постраничной навигации
     * count - количество элементов для отображения на странице
     * order_by - поле для сортировки (одно из полей массива ModelName::fields())
     * order_type - направление сортировки (asc/desc)
     * filter - параметры фильтацрии
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function getLog(Request $request)
    {
        User::can('logger_view', true);

        //связанные модели и методы
        $relations = [
            'user' => new User(),
            'module' => new Module(),
        ];

        //получаем из модели нужные записи
        $result = parent::dbGet(new Logger, $request, [], $relations);
        $result = json_decode(json_encode($result), true);

        $logArray = [];
        //если запрос с постраничной навигацией
        if (array_key_exists('data', $result)) {
            $logArray = $result['data'];
        } else { //если без постраничной навигации
            $logArray = $result;
        }

        //достаём из конфигов данные о действии
        $Module = new ModuleController();
        foreach ($logArray as &$logItem) {
            $logItem['action'] = $Module->issetAction(
                $logItem['module']['code'], $logItem['log_action']
            );
        } unset($logItem);

        //если запрос с постраничной навигацией
        if (array_key_exists('data', $result)) {
            $result['data'] = $logArray;
        } else {
            $result = $logArray;
        }

        return parent::response($request->all(), $result, 200);
    }


    /**
     * Возвращает события добавления товаров и новостей
     * для всех компаний
     *
     * @param Request $request - запрос от клиента
     *
     * @return mixed
     */
    public function getAllEvents(Request $request) {
        $User      = State::User();
        $CurrUser  = $User;
        $user_id   = ($request->get('user_id')) ? $request->get('user_id') : $User['id'];
        $User      = User::where('id', $user_id)->first();
        $CurrUser  = User::where('id', $CurrUser['id'])->first();
        $result    = [];
        $count     = $request->get('count');
        if ($CurrUser) {
            $CurrCompanies = $CurrUser->companies()->get()->pluck('id')->toArray();
        } else {
            $CurrCompanies = [];
        }


        $Events = Logger::whereNotNull('entity_id')
            ->whereIn('log_action', ['market_postproduct', 'news_postnews'])
            ->orderBy('created_at', 'desc');

        if ($count) $Events = $Events->paginate($count);
        else $Events = $Events->get();
        $EventsTemp = $Events;

        if ($EventsTemp instanceof LengthAwarePaginator) {
            $EventsTemp->getCollection()
                ->transform(function ($item) use ($CurrCompanies, $CurrUser) {
                    $item = $this->formatEvent($item, $CurrCompanies, $CurrUser);
                    return $item;
                });
        } elseif ($EventsTemp instanceof Collection) {
            $EventsTemp->transform(function ($item) use ($CurrCompanies, $CurrUser) {
                $item = $this->formatEvent($item, $CurrCompanies, $CurrUser);
                return $item;
            });
        }

        foreach ($EventsTemp as $Event) if ($Event) $result[] = $Event;

        $Events = $Events->toArray();
        if ($count) $Events['data'] = $result;
        else $Events = $result;


        return parent::response($request->all(), $Events, 200);
    }

    /**
     * Форматирует сущности для событий
     *
     * @param Logger $Event
     * @param        $CurrCompanies
     * @param        $CurrUser
     *
     * @return Logger|null
     */
    public function formatEvent(Logger $Event, $CurrCompanies, $CurrUser, $moderated = true) {
        $Entity = null;
        if ($Event['log_action'] === 'market_postproduct') {
            $Entity = Product::where('id', $Event['entity_id'])
                ->with(['category',
                    'company'    => function ($q) {$q->with('heroImage');},
                    'defaultSku' => function ($q) {$q->with('heroImage');}
                ]);
            if ($moderated) $Entity = $Entity->where('public', 2);
            $Entity = $Entity->first();


            if ($Entity) {
                $Entity['owner'] = false;
                //проверим, может ли текущий пользователь редактировать сущность
                if (isset($Entity['company_id']) && in_array($Entity['company_id'], $CurrCompanies))
                    $Entity['owner'] = true;

                if (isset($Entity['user_id']) && $Entity['user_id'] == $CurrUser['id'])
                    $Entity['owner'] = true;

                //->whereIn('company_id', $CurrCompanies)->orWhere('user_id', $CurrUser['id'])
            }

        } elseif ($Event['log_action'] === 'news_postnews') {
            $Entity = News::where('id', $Event['entity_id'])
                ->with(['heroImage',
                    'company' => function ($q) {$q->with('heroImage');}
                ]);

            if ($moderated) $Entity = $Entity->where('moderate', 1);
            $Entity = $Entity->first();

            if ($Entity) {
                $Entity['owner'] = false;
                //проверим, может ли текущий пользователь редактировать сущность
                if (isset($Entity['company_id']) && in_array($Entity['company_id'], $CurrCompanies))
                    $Entity['owner'] = true;
            }

        }

        if ($Entity) {
            if (isset($Entity['company'])) {
                $Event['company'] = $Entity['company'];
                unset($Entity['company']);
                $result[] = $Event;
            }
            $Event['entity'] = $Entity;
        }

        return ($Event['entity']) ? $Event : null;
    }

    /**
     * Возвращает события добавления товаров и новостей
     * для всех компаний в которых состоит пользователь
     *
     * @param Request $request - запрос от клиента
     *
     * @return mixed
     */
    public function getUserEvents(Request $request) {
        $User      = State::User();
        $CurrUser  = $User;
        $user_id   = ($request->get('user_id')) ? $request->get('user_id') : $User['id'];
        $User      = User::where('id', $user_id)->first();
        $CurrUser  = User::where('id', $CurrUser['id'])->first();
        $result    = [];
        $Events    = [];
        $count     = $request->get('count');
        $Companies = $User->companies()->with(['users' => function($q) {
            $q->select('module_users.id');
        }])->get();
        $CurrCompanies = $CurrUser->companies()->get()->pluck('id')->toArray();

        $Users = [];
        if ($Companies) {
            foreach ($Companies as $Company) {
                if (count($Company->users)) {
                    $Users = array_merge($Users, $Company->users->pluck('id')->toArray());
                }
            }
        }

        $Events = Logger::whereIn('user_id', $Users)
            ->whereIn('log_action', ['market_postproduct', 'news_postnews'])
            ->whereNotNull('entity_id')
            ->orderBy('created_at', 'desc');

        if ($count) {
            $Events = $Events->paginate($count)->toArray();
            $EventsTemp = $Events['data'];
        } else {
            $Events = $Events->get()->toArray();
            $EventsTemp = $Events;
        }

        foreach ($EventsTemp as $Event) {
            if ($Event['log_action'] === 'market_postproduct') {
                $Event['entity'] = Product::where('id', $Event['entity_id'])
                    ->with([
                        'company', 'category',
                        'defaultSku' => function ($q) {
                            $q->with('heroImage');
                        }
                    ])->first();
            } elseif ($Event['log_action'] === 'news_postnews') {
                $Event['entity'] = News::where('id', $Event['entity_id'])
                    ->with('company', 'heroImage')->first();
            } else {
                continue;
            }

            $Event['entity']['owner'] = false;
            //проверим, может ли текущий пользователь редактировать сущность
            if (isset($Event['entity']['company_id']) && in_array($Event['entity']['company_id'], $CurrCompanies)) {
                $Event['entity']['owner'] = true;
            }

            if ($Event['entity'] && isset($Event['entity']['company'])) {
                $Event['company'] = $Event['entity']['company'];
                unset($Event['entity']['company']);
                $result[] = $Event;
            }
        }

        if ($count) {
            $Events['data'] = $result;
        } else {
            $Events = $result;
        }

        return parent::response($request->all(), $Events, 200);
    }

    /**
     * Возвращает события добавления товаров и новостей
     * для компании с переданным id
     *
     * @param Request $request    - запрос от клиента
     * @param integer $company_id - id компании
     *
     * @return mixed
     * @throws CustomException
     */
    public function getCompanyEvents(Request $request, $company_id) {
        $User      = State::User();
        $CurrUser  = $User;
        $user_id   = ($request->get('user_id')) ? $request->get('user_id') : $User['id'];
        $User      = User::where('id', $user_id)->first();
        $CurrUser  = User::where('id', $CurrUser['id'])->first();
        $result    = [];
        $count     = $request->get('count');
        if ($CurrUser) $CurrCompanies = $CurrUser->companies()->get()->pluck('id')->toArray();
        else $CurrCompanies = [];


        $Company = CompanyController::call('getCompanyById', $company_id, false);
        $Users   = $Company->users->pluck('id')->toArray();

        $Events = Logger::whereNotNull('entity_id')
            ->whereIn('user_id', $Users)
            ->where('company_id', $company_id)
            ->whereIn('log_action', ['market_postproduct', 'news_postnews'])
            ->orderBy('created_at', 'desc');

        if ($count) $Events = $Events->paginate($count);
        else $Events = $Events->get();
        $EventsTemp = $Events;

        //флаг, показывать только опубликованные товары
        $moderate = $request->get('moderate');

        if ($EventsTemp instanceof LengthAwarePaginator) {
            $EventsTemp->getCollection()
                ->transform(function ($item) use ($CurrCompanies, $CurrUser, $moderate) {
                    $item = $this->formatEvent($item, $CurrCompanies, $CurrUser, $moderate);
                    return $item;
                });
        } elseif ($EventsTemp instanceof Collection) {
            $EventsTemp->transform(function ($item) use ($CurrCompanies, $CurrUser, $moderate) {
                $item = $this->formatEvent($item, $CurrCompanies, $CurrUser, $moderate);
                return $item;
            });
        }

        foreach ($EventsTemp as $Event) if ($Event) $result[] = $Event;

        $Events = $Events->toArray();
        if ($count) $Events['data'] = $result;
        else $Events = $result;


        return parent::response($request->all(), $Events, 200);
    }

}