<?php

namespace App\Foundation;

use Carbon\Carbon as _Carbon;

trait Carbon
{
    protected function getWeekLabel($index, $lang = 'en')
    {
        $week = [
            'en' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'ch' => ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'],
            'zh' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
        ];
        return $week[$lang][$index - 1];
    }

    /**
     * @return _Carbon
     */
    public function now()
    {
        return _Carbon::now();
    }

    public function thisWeekDay($index)
    {
        $today = $this->now()->dayOfWeek;
        $today = ($today == 0) ? 7 : $today;
        $prefix = ($index < $today) ? 'last ' : 'this ';
        return new _Carbon($prefix . $this->getWeekLabel($index));
    }

    /**
     * @param $index
     * @return _Carbon
     */
    public function thisMonthDay($index)
    {
        $day = $this->now();
        $dayCount = $day->daysInMonth;
        if ($index > $dayCount || $index > 31 || $index < 1) {
            $index = $dayCount;
        }
        $day->day = $index;
        return $day->startOfDay();
    }

    /**
     * @param $index
     * @return _Carbon
     */
    public function lastMonthDay($index)
    {
        $day = $this->thisMonthDay(1)->addDays(-1);
        $dayCount = $day->daysInMonth;
        if ($index > $dayCount || $index > 31 || $index < 1) {
            $index = $dayCount;
        }
        $day->day = $index;
        $day = $day->startOfDay();
        return $day;
    }

    public function lastWeek()
    {
        $begin = $this->thisWeekDay(1)->addDays(-7);
        $end = $this->thisWeekDay(7)->addDays(-7)->endOfDay();
        return ['begin' => $begin, 'end' => $end];
    }


    public function lastMonth()
    {
        $begin = $this->lastMonthDay(1);
        $end = $this->lastMonthDay(0)->endOfDay();
        return ['begin' => $begin, 'end' => $end];
    }
}