<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Category;
use App\Modules\Hookah\Model\Mix;
use App\Modules\Hookah\Model\MixTobacco;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use function foo\func;
use Illuminate\Http\Request;

class MixController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Hookah';

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
     * Добавление микса
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postMix(Request $request)
    {
        $request->merge([
            'stowage'  => (is_string($request->get('stowage')))  ? json_decode($request->get('stowage'),  true) : $request->get('stowage'),
            'tobacco'  => (is_string($request->get('tobacco')))  ? json_decode($request->get('tobacco'),  true) : $request->get('tobacco'),
            'category' => (is_string($request->get('category'))) ? json_decode($request->get('category'), true) : $request->get('category'),
        ]);

        $Mix = Mix::post($request);
        if ($Mix) {

            $Mix->syncTobacco($request->get('tobacco'));
            $Mix->category()->sync($request->get('category'));
            LoggerController::write(
                $this->getModuleName(), 'hookah_postmix',
                null, 'mix', $Mix->id,
                ['data' => self::modelFilter($Mix, Mix::fields())]
            );
        }

        $Mix = $this->getMixById($Mix->id, false);
        return parent::response($request->all(), $Mix, 200);
    }

    /**
     * Изменение микса
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putMix(Request $request)
    {
        $request->merge([
            'stowage'  => (is_string($request->get('stowage')))  ? json_decode($request->get('stowage'),  true) : $request->get('stowage'),
            'tobacco'  => (is_string($request->get('tobacco')))  ? json_decode($request->get('tobacco'),  true) : $request->get('tobacco'),
            'category' => (is_string($request->get('category'))) ? json_decode($request->get('category'), true) : $request->get('category'),
        ]);

        $Mix = ['old' => false, 'new' => false];
        $Mix = Mix::put($request, ['category']);
        if (isset($Mix['old']) && isset($Mix['new'])) {

            $Mix['new']->syncTobacco($request->get('tobacco'));
            $Mix['new']->category()->sync($request->get('category'));

            LoggerController::write(
                $this->getModuleName(), 'hookah_putmix',
                null, 'mix', $Mix['new']->id,
                ['data' => self::modelFilter($Mix['new'], Mix::fields())],
                [$Mix['old'], $Mix['new']]
            );
        }

        return parent::response($request->all(), $Mix['new'], 200);
    }

    /**
     * Получение списка миксов
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getMixes(Request $request)
    {
        User::can('hookah_viewmix', true);
        return parent::response(
            $request->all(),
            parent::dbGet(new Mix(), $request, [], [
                'category' => new Category(),
                'tobacco'  => ['model' => new MixTobacco(), 'with' => ['vendor']]
            ]),
            200
        );
    }

    /**
     * Получение микса по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getMixById($id, $json = true)
    {
        User::can('hookah_viewmix', true);
        $Mix = Mix::where('id', $id)->with([
            'category',
            'tobacco' => function($q) {
                $q->with('vendor');
            }
        ])->first();
        if (!$Mix) throw new CustomException(['id' => $id], [], 404, 'Микс не найден');

        return ($json) ? parent::response(['id' => $id], $Mix, 200) : $Mix;
    }

    /**
     * Удаление микса по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteMixById($id)
    {
        User::can('hookah_deletemix', true);

        $Mix = $this->getMixById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletemix',
            null, 'mix', $Mix->id,
            ['data' => self::modelFilter($Mix, Mix::fields())]
        );

        return parent::response(['id' => $id], $Mix->delete(), 200);
    }
}