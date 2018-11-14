<?php

namespace App;

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
