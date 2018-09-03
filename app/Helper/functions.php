<?php

function getDbName($conn)
{
    return app('.env.array')[$conn]['database'];
}

function getPdo($conn)
{
    $db = app('.env.array')[$conn];
    return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
}