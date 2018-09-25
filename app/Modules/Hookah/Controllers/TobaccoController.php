<?php

namespace App\Modules\Hookah\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hookah\Model\Line;
use App\Modules\Hookah\Model\Tobacco;
use App\Modules\Hookah\Model\Vendor;
use App\Modules\Properties\Controllers\FileController;
use App\Modules\Logger\Controllers\LoggerController;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;
use App\Modules\Properties\Model\Files;

class TobaccoController extends Controller implements ModuleInterface
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
     * Добавление постащика
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function postTobacco(Request $request)
    {

        if ($request->hasFile('hero_image')) {
            $file = self::upFile($request, 'hero_image');
            $request->merge(['hero_image_id' => $file['id']]);
        }

        $Tobacco = Tobacco::post($request);
        if ($Tobacco) {
            LoggerController::write(
                $this->getModuleName(), 'hookah_posttobacco',
                null, 'tobacco', $Tobacco->id,
                ['data' => self::modelFilter($Tobacco, Tobacco::fields())]
            );
        }

        $Tobacco = $this->getTobaccoById($Tobacco->id, false);
        return parent::response($request->all(), $Tobacco, 200);
    }

    /**
     * Изменение постащика
     *
     * @param Request $request
     *
     * @return mixed
     * @throws CustomException
     */
    public function putTobacco(Request $request)
    {
        $Tobacco = ['old' => false, 'new' => false];


        if ($request->hasFile('hero_image')) {
            $file = self::upFile($request, 'hero_image');
            $request->merge(['hero_image_id' => $file['id']]);
        }

        $Tobacco = Tobacco::put($request);

        if (isset($Tobacco['old']) && isset($Tobacco['new'])) {

            LoggerController::write(
                $this->getModuleName(), 'hookah_puttobacco',
                null, 'tobacco', $Tobacco['new']->id,
                ['data' => self::modelFilter($Tobacco['new'], Tobacco::fields())],
                [$Tobacco['old'], $Tobacco['new']]
            );
            if ($request->hasFile('hero_image')) FileController::call('deleteFileById', $Tobacco['old']['hero_image_id'], false);

        }

        return parent::response($request->all(), $Tobacco['new'], 200);
    }

    /**
     * Получение списка постащиков
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\CustomDBException
     */
    public function getTobaccos(Request $request)
    {
        User::can('hookah_viewtobacco', true);
        return parent::response(
            $request->all(),
            parent::dbGet(new Tobacco(), $request, [], [
                'heroImage' => new Files(),
                'vendor'    => new Vendor(),
                'line'      => new Line()
            ]), 200
        );
    }

    /**
     * Получение поставщика по ID
     *
     * @param      $id
     * @param bool $json
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function getTobaccoById($id, $json = true)
    {
        User::can('hookah_viewtobacco', true);
        $Tobacco = Tobacco::where('id', $id)->with(['heroImage', 'vendor', 'line'])->first();
        if (!$Tobacco) throw new CustomException(['id' => $id], [], 404, 'Производитель не найден');

        return ($json) ? parent::response(['id' => $id], $Tobacco, 200) : $Tobacco;
    }

    /**
     * Удаление поставщика по ID
     *
     * @param $id
     *
     * @return mixed
     * @throws CustomException
     * @throws \App\Exceptions\CustomDBException
     */
    public function deleteTobaccoById($id)
    {
        User::can('hookah_deletetobacco', true);

        $Tobacco = $this->getTobaccoById($id, false);

        LoggerController::write(
            $this->getModuleName(), 'hookah_deletetobacco',
            null, 'tobacco', $Tobacco->id,
            ['data' => self::modelFilter($Tobacco, Tobacco::fields())]
        );

        return parent::response(['id' => $id], $Tobacco->delete(), 200);
    }

    public function addFromNamesArray(Request $request)
    {
        $result = [];
        $data = $request->only(
            'names',
            'vendor_id',
            'line_id',
            'fortress',
            'composition',
            'variety',
            'nicotine'
        );

        if (is_string($data['names'])) $data['names'] = json_decode($data['names'], true);

        foreach ($data['names'] as $name) {
            $productData = [
                'name'       => $name,
                'vendor_id'  => $data['vendor_id'],
                'line_id'    => $data['line_id'],
                'fortress'   => $data['fortress'],
                'composition'=> $data['composition'],
                'variety'    => $data['variety'],
                'nicotine'   => $data['nicotine'],
            ];
            $request->replace($productData);

            $Tobacco = Tobacco::aFind($productData);

            if ($Tobacco) {
                $productData['isset'] = 1;
            } else {
                $Tobacco = Tobacco::post($request);
                if ($Tobacco) {
                    $productData['added'] = 1;
                } else {
                    $productData['added'] = 0;
                }
                $productData['isset'] = 0;
            }
            $result[] = $productData;
        }

        return parent::response($data, $result, 200);
    }

    public function getPageLinks(Request $request)
    {
        require_once __DIR__  . '/../../../Classes/simple_html_dom.php';
        $link = $request->get('link');
        $vendor = $request->get('vendor');
        $result = [];
        $html = file_get_html($link);
        $html = $html->find('a');


        foreach ($html as $item) {
            if (is_object($item)) {
                $link = $item->getAttribute('href');
                if ($link && strripos($link, 'nn-kalyan.ru/product/')) {
                    //$result['dom'][] = $link;
                    $text = $item->plaintext;

                    if ($text && is_string($text)) {
                        $text = str_replace($vendor, '', $text);
                        $text = preg_replace('/[^a-zA-Z]/ui', '', $text);
                        if ($text && strlen($text)) $result[] = $text;
                    }
                }
            }
        }

        $result = array_values(array_unique($result));
        return parent::response(['link' => $link], $result, 200);
    }
    public function getProductData(Request $request)
    {
        $links = json_decode($request->get('links'), true);
        $result = [];

        if (is_array($links)) {
            foreach ($links as $link) {
                $result[] = $this->parseProduct($link, $request->get('vendor'));
            }
        }

        return parent::response(['links' => $links], $result, 200);
    }
    public function parseProduct($link, $vendor)
    {
        require_once __DIR__  . '/../../../Classes/simple_html_dom.php';
        $result = [
            'name' => ''
        ];
        $html = file_get_html($link);

        $productName = $html->find('h1.product_title');
        if ($productName[0] && is_object($productName[0])) $productName = $productName[0]->plaintext;
        else $productName = '';
        if ($productName) {
            $corrName = str_replace($vendor, '', $productName);
            $corrName = preg_replace('/[^a-zA-Z]/ui', '', $corrName);
            $result['name'] = $corrName;
        }

        $image = $html->find('img.wp-post-image');
        if ($image[0] && is_object($image[0])) $result['image'] = $image[0]->getAttribute('src');

        return $result;
    }

}