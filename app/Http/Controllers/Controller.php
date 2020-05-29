<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function done($data = [], $message = null) {

        return response([
            'done' => "success",
            'message' => $message,
            'body' => $data,
        ], 200);

    }

    public static function error($message = "Ошибка", $status = 200, $code = 0, $errors = []) {

        return response([
            'done' => "error",
            'message' => $message,
            'code' => $code,
            'errors' => $errors
        ], $status);

    }

    /**
	 * Метод перевода размера файла из байтов в Кб, Мб и тд
     * 
	 * @param Int $size
     * 
	 * @return String
	 */
	public static function formatSize($size) {

		$metrics = [
			0 => 'байт',
			1 => 'Кб',
			2 => 'Мб',
			3 => 'Гб',
			4 => 'Тб',
		];

		$metric = 0;  

		while(floor($size / 1024) > 0){
			$metric ++;
			$size /= 1024;
		}     

		return round($size, 1) . " " . (isset($metrics[$metric]) ? $metrics[$metric] : '');

    }

    /**
     * Метод преобразования месяца в слово
     * 
     * @param int $time Метка системного времени Unix
     * @param int $type Требуемый формат месяца
     *          0 - январь
     *          1 - января
     *          2 - янв
     * 
     * @return string|bool
     */
    public static function dateToMonth($time = flase, $type = 0) {

        $months = [
            ['январь','января','янв'],
            ['февраль','февраля','фев'],
            ['март','марта','мар'],
            ['апрель','апреля','апр'],
            ['май','мая','мая'],
            ['июнь','июня','июня'],
            ['июль','июля','июля'],
            ['август','августа','авг'],
            ['сентябрь','сентября','сен'],
            ['октябрь','октября','окт'],
            ['ноябрь','ноября','нояб'],
            ['декабрь','декабря','дек'],
        ];

        $m = date("n", $time) - 1;

        return $months[$m][$type] ?? false;

    }

    /**
     * Метод преобразования даты из тайм-кода
     */
    public static function creteDateFromTime($time, $onlydate = false, $notoday = false) {

        $datetime = date("Y-m-d H:i:s", $time);
        return self::createDate($datetime, $onlydate, $notoday);

    }

    /**
     * Метод преобразования даты
     * 
     * @param mixed $datetime Время преобразования
     * @return string|bool
     */
    public static function createDate($datetime, $onlydate = false, $notoday = false) {

        if (!$time = strtotime($datetime))
            return false;

        // Сверка даты
        $now = date('z');
        $before = date('z', $time);

        $times = $onlydate ? "" : " в H:i";
    
        if ($now-$before == 0 AND !$notoday)
            return date("сегодня{$times}", $time);

        if ($now-$before == 1 AND !$notoday)
            return date("вчера{$times}", $time);

        $month = self::dateToMonth($time, 2);

        if (date("Y") != date("Y", $time))
            return date("d {$month} Y{$times}", $time);

        return date("d {$month}{$times}", $time);

    }

}
