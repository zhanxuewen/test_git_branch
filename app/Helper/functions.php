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

function treeview($label, $children, $icon)
{
    $out
        = '<li class="treeview">
                <a href="#">
                    <i class="fa '.$icon.'"></i>
                    <span>'.$label.'</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">';
    foreach ($children as $name => $url) {
        $out
            .= '<li><a href="'.url($url).'"><i class="fa fa-circle-o"></i> '.$name.'</a></li>';
    }
    $out
        .= '</ul>
            </li>';
    return $out;
}

function single_bar($name, $url, $icon)
{
    $out
        = '<li>
                <a href="'.url($url).'">
                    <i class="fa '.$icon.'"></i> <span>'.$name.'</span>
                </a>
            </li>';
    return $out;
}