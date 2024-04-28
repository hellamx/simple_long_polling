<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{

    public function test()
    {
        return view('welcome', ['messages' => Message::query()->get()]);
    }

    public function getMessages(Request $request): JsonResponse
    {
        $poll_live_timeout = 20; // Время жизни запроса в секундах
        $poll_timeout_to_db = 5; // Таймаут поллинга в БД (каждые 5 секунд)
        $last_data_id = $request->id; // ID последней отображенной записи

        // Закрываем сеанс сессий, чтобы избежать блокировки других запросов
        // Если необходимы какие-либо данные из сессии, то получаем выше
        session_write_close();

        // Автоматически заканчиваем скрипт после какого-то таймаута с запасом в секунду
        set_time_limit($poll_live_timeout + 1);

        // Счетчик для ручного отслеживания прошедшего времени (функция PHP set_time_limit() невозможна во время сна)
        $counter = $poll_live_timeout;

        // Счетчик запросов в бд
        $queries_counter = 0;

        // Запускаем цикл
        while($counter > 0) {

            $queries_counter += 1;

            // Получаем данные
            $data = Message::query()->where('id', '>', $last_data_id)->get();

            // Проверяем на наличие новых данныъ
            if($data->isNotEmpty()) {

                // Завершаем цикл, если данные появились
                break;

            } else {
                // Если данных нет - заглушаем скрипт на время
                sleep($poll_timeout_to_db);

                // Вручную уменьшаем время жизни скрипта
                $counter -= 5;
            }
        }

        // Логируем кол-во запросов в БД
        Log::info('query counter - ' . $queries_counter);

        // Если новые данные были получены - отправляем на клиент
        if(!empty($data) && $data->isNotEmpty()) return response()->json(array_merge(['status' => true], ['data' => $data->toArray()]));

        // Если данных нет - отправляем false
        return response()->json(['status' => false]);
    }

}
