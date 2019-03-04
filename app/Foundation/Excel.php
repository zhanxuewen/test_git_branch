<?php

namespace App\Foundation;

use App\Import;
use App\Export;
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