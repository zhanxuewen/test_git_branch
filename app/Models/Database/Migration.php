<?php

namespace App\Models\Database;

use App\Models\BaseModel;

class Migration extends BaseModel
{
    protected $table = 'database_migrations';

    protected $fillable = ['migration_name', 'table_name', 'migrate_type', 'engine', 'id_type', 'columns', 'index', 'timestamps', 'has_deleted'];

}
