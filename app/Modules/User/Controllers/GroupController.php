<?
namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Module\Controllers\ModuleController;
use App\Modules\Module\Model\Module;
use App\Modules\User\Model\Action;
use App\Modules\User\Model\User;
use App\Modules\User\Model\Group;
use App\Modules\User\Controllers\UserController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Exceptions\CustomDBException;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

/**
 * Класс для работы с группами пользователей
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://lets-code.ru/
 */
class GroupController extends Controller implements ModuleInterface
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
     * Создаёт новую группу пользователей
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomException
     */
    public function createGroup(Request $request)
    {
        User::can('user_addgroup', true);
        $data = $request->only(['name','description','code','sort']);
        $newGroup = [];

        if (Group::groupValidator($data)) {
            $newGroup = new Group();
            $newGroup->name = $data['name'];
            $newGroup->description = $data['description'];
            $newGroup->code = $data['code'];
            $newGroup->sort = ($data['sort']) ? $data['sort'] : 100;
            $newGroup->save();

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_addgroup',
                null, 'group', $newGroup->id
            );

            $this->pushPublicActionsToGroup($newGroup->id, false);
        }

        return parent::response($data, $newGroup, 200);
    }

    /**
     * Обновляет параметры группы пользователей
     *
     * @param Request $request - экземпляр Request
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function putGroup(Request $request)
    {
        User::can('user_putgroup', true);
        $data = $request->only(['name','description','code','sort']);

        $Group = Group::where('code', $data['code'])->first();
        if (!$Group) {
            throw new CustomDBException($data, [], 404, 'Not found');
        }
        $oldGroup = clone $Group;

        $data['update_id'] = $Group['id'];
        if (Group::groupValidator($data)) {
            $Group->name = $data['name'];
            $Group->description = $data['description'];
            $Group->code = $data['code'];
            $Group->sort = ($data['sort']) ? $data['sort'] : 100;
            $Group->save();

            $result = true;

            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'user_putgroup',
                null, 'group', $Group->id,
                ['data' => self::modelFilter($Group, Group::fields())],
                [$oldGroup, $Group]
            );

        } else {
            $result = false;
        }

        return parent::response($data, $result, 200);
    }

    /**
     * Удалят группу пользователей
     * Параметры DEL запроса:
     * code - символьный код группы пользователей
     *
     * @param $id - id группы
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function removeGroup($id)
    {
        User::can('user_removegroup', true);

        $Group = Group::where('id', $id)->first();

        if (!$Group) {
            throw new CustomDBException(['id' => $id], [], 404, 'Not found');
        }

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'user_removegroup',
            null, 'group', $Group->id
        );



        $Group->delete();
        return parent::response(true, $Group, 200);
    }

    /**
     * Возвращает данные группы пользователей по переданному коду
     *
     * @param string $code - символьный код группы
     *
     * @return mixed
     * @throws CustomDBException
     * @throws CustomException
     */
    public function getGroupByCode($code)
    {
        User::can('user_viewgroup', true);
        $Group = Group::where('code', $code)->first();

        if (!$Group) {
            throw new CustomDBException($code, [], 404, 'Not found');
        }

        return parent::response($code, $Group, 200);
    }

    /**
     * Возвращает список групп пользователей
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
    public function getGroups(Request $request)
    {
        User::can('user_viewgroup', true);

        return parent::response(
            $request->all(),
            parent::dbGet(new Group, $request),
            200
        );
    }

    /**
     * Проверяет существование группы с переданным кодом
     * возвращает экземпляр класса Group в случае удачи
     * в случае неудачи, выкидывает исключение
     *
     * @param string $group - символьный код группы
     *
     * @return mixed
     * @throws CustomDBException
     */
    public function issetGroup($group)
    {
        $FindGroup = Group::where('code', $group)->first();
        if (!$FindGroup) {
            throw new CustomDBException(
                $group, [], 404,
                'Group with code "'.$group.'" is not found'
            );
        }
        return $FindGroup;
    }

    public function pushPublicActionsToGroup($group_id, $json = true) {
        $Modules = Module::where('active', 1)->select(Module::fields())->get();
        $ModuleController = new ModuleController();
        $Actions = [];
        foreach ($Modules as $module) {
            $PublicAction = $ModuleController
                ->getModulePublicActions($module->code, false);
            foreach ($PublicAction as $action) {
                $OldAction = Action::where('module_id', $module->id)
                    ->where('group_id', $group_id)
                    ->where('name', $action)->first();
                if (!$OldAction) {
                    $newAction = new Action();
                    $newAction->name = $action;
                    $newAction->sort = 100;
                    $newAction->module()->associate($module->id);
                    $newAction->group()->associate($group_id);
                    $newAction->save();
                    $Actions[] = $newAction;
                }
            }
        }

        return ($json) ? parent::response(['group_id' => $group_id], $Actions, 200) : $Actions;
    }

}