<?

namespace App\Interfaces;

interface ExceptionInterface
{
    /**
     * Возвращает переданный в конструктор, текстовый ответ, который отправится клиенту
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse();

    /**
     * Возвращает переданный в конструктор, код ответа сервера, который отправится клиенту
     *
     * @return int
     */
    public function getStatusCode();
}