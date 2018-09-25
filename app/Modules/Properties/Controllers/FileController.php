<?php

namespace App\Modules\Properties\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\State;
use App\Modules\Logger\Controllers\LoggerController;
use App\Modules\Properties\Model\Files;
use App\Interfaces\ModuleInterface;
use App\Exceptions\CustomException;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Класс для работы с файлами
 *
 * @category Laravel_Modules
 * @package  App\Modules\User\Controllers
 * @author   Kukanov Oleg <speed.live@mail.ru>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://crm.lets-code.ru/
 */
class FileController extends Controller implements ModuleInterface
{
    /**
     * Название модуля
     *
     * @var string
     */
    public $moduleName = 'Properties';

    public $imageTypes = ['png', 'jpg', 'jpeg', 'bmp', 'svg', 'gif'];

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
     * Загружает файл на сервер, пишет даные о нём в БД
     * и позвращает результат
     *
     * @param Request->file('name') $file - файл из объекта Request
     *
     * @return Files
     * @throws CustomException
     */
    public function upload(UploadedFile $file)
    {
        User::can('properties_add_files', true);

        //проверяем файл на валидность
        if (!$file->isValid()) {
            throw new CustomException(
                [], [], 400,
                'Ошибка в получении файла, проверьте целостность файла: ' . $file->getPath()
            );
        }

        sleep(0.1);

        //получаем все нужные данные о файле
        $fileName   = substr(sha1('file_' . random_int(0,100) . time()), 0, 20) . '.' . $file->getClientOriginalExtension();
        $fileSize   = $file->getSize();
        $publicPath = config('filesystems.disks.public.public_path');
        $localPath  = storage_path('public') . '/' . $fileName;
        if (!$publicPath) $publicPath = '/storage/public/';
        $publicPath .= $fileName;
        $publicPath = env('APP_URL') . $publicPath;

        //перемещаем в публичное хранилище
        $file->move(storage_path('public'), $fileName);

        //вытаскиваем пользователя, который добавил файл
        if (!$User = State::User()) $User = ['id' => NULL];


        //проверим размер файла
        $maxSize = env('FILE_UPLOAD_MAX_SIZE', 819200); //100мб
        if ($fileSize > $maxSize) {
            unlink($localPath);
            throw new CustomException(
                [], ['file_name' => $fileName, 'file_size' => $fileSize,], 400,
                'Размер файла слишком большой, максимальный размер ' . $maxSize . ' байт'
            );
        }

        //записываем данные о файле БД
        //и возвращаем результат
        $File = new Files();
        $File->path = $publicPath;
        $File->local_path = $localPath;
        $File->name = $fileName;
        $File->uploader()->associate($User['id']);
        $File->save();

        $File->size = $fileSize;

        //логируем действие
        LoggerController::write(
            $this->getModuleName(), 'properties_add_files',
            null, 'file', $File->id,
            ['data' => self::modelFilter($File, Files::fields())]
        );


        return $File;
    }


    public function uploadByPath($path)
    {
        $file = new UploadedFile($path, basename($path), mime_content_type($path), filesize($path), UPLOAD_ERR_OK, true);
        $file = $this->upload($file);
        return $file;
    }

    public function uploadByLink($link)
    {
        $name = 'file_' . time() . '_' . basename($link);
        $path = storage_path('public') . '/' . $name;
        file_put_contents($path, file_get_contents($link));

        $file = new UploadedFile($path, $name, mime_content_type($path), filesize($path), UPLOAD_ERR_OK, true);


        $file = $this->upload($file);
        return $file;
    }


    /**
     * Возвращает данные о файле из БД по его id
     *
     * @param integer $id   - id файла
     * @param bool    $json - если в ответе ненужен json, передать false
     *
     * @return mixed
     * @throws CustomException
     */
    public function getFileById($id, $json = true)
    {
        User::can('properties_view_files', true);

        $result = Files::where('id', $id)
            ->with(
                [
                    'uploader' => function($query) {
                        $query->select(User::fields());
                    }
                ]
            )->first();

        if ($result) {
            $result['exist'] = Storage::disk('public')->exists($result['name']);
            //$result['path'] = env('APP_URL') . $result['path'];
            if ($result['exist']) {
                $result['size'] = Storage::disk('public')->getSize($result['name']);
                $sizeType = env('FILE_SIZE_TYPE') || 'byte';
                if ($sizeType == 'Mb') {
                    $result['size'] = round($result['size'] / 1024 / 1024, 3);
                }
                $result['size'] = $result['size'] . ' ' . env('FILE_SIZE_TYPE');

                $result['file_type'] = substr(
                    $result['name'],
                    strripos($result['name'], '.') + 1
                );
                $result['image'] = in_array($result['file_type'], $this->imageTypes);
            }
        }

        if ($json) {
            return parent::response(
                ['id' => $id], $result, 200
            );
        } else {
            return $result;
        }
    }

    /**
     * Удаляет из БД и файлового хранилища по его id
     *
     * @param integer $id   - id файла
     * @param bool    $json - если в ответе ненужен json, передать false
     *
     * @return mixed
     * @throws CustomException
     */
    public function deleteFileById($id, $json = true)
    {
        User::can('properties_delete_files', true);

        $result = false;
        $file = $this->getFileById($id, false);
        if ($file) {
            $result = Files::where('id', $id)->delete();
            unlink($file['local_path']);
        }

        if ($result) {
            //логируем действие
            LoggerController::write(
                $this->getModuleName(), 'properties_delete_files',
                null, 'file', $id,
                ['data' => self::modelFilter($file, Files::fields())]
            );
        }

        if ($json) {
            return parent::response(
                ['id' => $id], $result, 200
            );
        } else {
            return $result;
        }
    }

    public static function getFileExtension(Files $file)
    {
        return substr($file->name, -3);
    }
}