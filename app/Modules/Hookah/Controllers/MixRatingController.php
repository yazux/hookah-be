<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Mix;
use App\Modules\Hookah\Model\MixRating;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;

class MixRatingController extends Controller implements ModuleInterface
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
     * Добавление рейтинга микса
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postMixRating(Request $request)
    {
        $MixRating = MixRating::post($request, ['mix' => 'mix_id','user' => 'user_id']);
        if ($MixRating) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_postmixrating',
                null, 'mixrating', $MixRating->id,
                ['data' => self::modelFilter($MixRating, MixRating::fields())]
            );
        }

        $MixRating = $this->getMixRatingById($MixRating->id, false);

        if ($MixRating->rating) $MixRating->mix->increment('rating');
        else $MixRating->mix->decrement('rating');

        return parent::response($request->all(), $MixRating, 200);
    }

    /**
     * Изменение рейтинга микса
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putMixRating(Request $request)
    {
        $MixRating = ['old' => false, 'new' => false];
        $MixRating = MixRating::put($request, ['mix' => 'mix_id','user' => 'user_id']);
        if (isset($MixRating['old']) && isset($MixRating['new'])) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_putmixrating',
                null, 'mixrating', $MixRating['new']->id,
                ['data' => self::modelFilter($MixRating['new'], MixRating::fields())],
                [$MixRating['old'], $MixRating['new']]
            );
        }

        $MixRating = $this->getMixRatingById($MixRating['new']->id, false);

        if ($MixRating->rating) $MixRating->mix->increment('rating', 2);
        else $MixRating->mix->decrement('rating', 2);

        return parent::response($request->all(), $MixRating, 200);
    }

    /**
     * Получение списка рейтингов миксов
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getMixRatings(Request $request)
    {
        User::can('hookah_viewmixrating', true);
        return parent::response(
            $request->all(),
            parent::dbGet(new MixRating(), $request, [], [
                'user' => new User(), 'mix' => new Mix(),
            ]),
            200
        );
    }

    /**
     * Получение рейтинга микса по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getMixRatingById($id, $json = true)
    {
        User::can('hookah_viewmixrating', true);

        $MixRating = MixRating::where('id', $id)->with(['mix', 'user'])->first();
        if (!$MixRating) throw new CustomException(['id' => $id], [], 404, 'Микс не найден');

        return ($json) ? parent::response(['id' => $id], $MixRating, 200) : $MixRating;
    }

    /**
     * Удаление рейтинга микса по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteMixRatingById($id)
    {
        User::can('hookah_deletemixrating', true);

        $MixRating = $this->getMixRatingById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletemixrating',
            null, 'mixrating', $MixRating->id,
            ['data' => self::modelFilter($MixRating, MixRating::fields())]
        );

        return parent::response(['id' => $id], $MixRating->delete(), 200);
    }
}