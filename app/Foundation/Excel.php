<?php

namespace App\Foundation;

use Maatwebsite\Excel\Facades\Excel as _Excel;

trait Excel
{
    public function import($file, $title = false)
    {
        _Excel::import($import = new Import($title), $file, 'import');
        return $import->rows;
    }

    public function export($name, $record, $ext = '.xls')
    {
        return _Excel::download(new Export($record), $name . $ext);
    }

    public function store($file, $data, $ext = '.xls')
    {
        _Excel::store(new Export($data), $file . $ext, $disk = 'export');
        return $this->getDiskPath($disk) . '/' . $file . $ext;
    }

    protected function getDiskPath($disk = 'local')
    {
        return config("filesystems.disks.{$disk}.root");
    }
}

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

use Maatwebsite\Excel\Concerns\FromCollection;

class Export implements FromCollection
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }
}