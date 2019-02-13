<?php

namespace App\Foundation;

trait ArrayFunc
{
    public function preg_in_array($preg, $array)
    {
        foreach ($array as $item) {
            if (preg_match($preg, $item) > 0) return $item;
        }
        return false;
    }

    public function preg_search_in_array($preg, $array)
    {
        $data = [];
        foreach ($array as $item) {
            if (preg_match($preg, $item) > 0) $data[] = $item;
        }
        return $data;
    }
}