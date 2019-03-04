<?php

namespace App;

use Maatwebsite\Excel\Concerns\ToArray;

class Import implements ToArray
{
    public $title;

    public $rows;

    public function __construct($with_title = false)
    {
        $this->title = $with_title;
    }

    public function array(array $rows)
    {
        if (count($rows) == 0) return [];
        if ($this->title) {
            $title = array_shift($rows);
            foreach ($rows as &$row) {
                $items = [];
                foreach ($row as $key => $item) {
                    $items[$title[$key]] = $item;
                }
                $row = $items;
            }
        }
        $this->rows = $rows;
        return true;
    }
}
