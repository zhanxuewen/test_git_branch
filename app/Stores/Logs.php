<?php

namespace App\Stores;

class Logs
{
    public function schedule($day)
    {
        $hits = KibAna::getLog(time() * 1000, (time() - 86400 * $day) * 1000);
        $list = [];
        $hits = empty($hits->hits) ? [] : array_reverse($hits->hits);
        foreach ($hits as $hit) {
            $message = $hit->_source->message;
            foreach (explode("\n", $message) as $item) {
                $list[] = $this->getMessage($item);
            }
        }
        return [count($list), $list];
    }

    protected function getMessage($message)
    {
        preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $message, $match);
        return ['time' => $match[0], 'message' => $message];
    }
}
