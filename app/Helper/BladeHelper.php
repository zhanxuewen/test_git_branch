<?php

namespace App\Helper;

use Carbon\Carbon;
use App\Foundation\PdoBuilder;

class BladeHelper
{
    use PdoBuilder;

    protected static $user_id = null;

    protected static $cache = null;

    protected static $routes = null;

    protected static $info = null;

    public static function getUserId()
    {
        if (is_null(self::$user_id)) {
            self::$user_id = \Auth::user()->id;
        }
        return self::$user_id;
    }

    public static function getCache()
    {
        if (is_null(self::$cache)) {
            self::$cache = (new self())->getRedis('analyze')->get(self::getUserId() . '_routes');
        }
        return self::$cache;
    }

    protected static function getRoutes()
    {
        if (is_null(self::$routes)) {
            $routes = [];
            foreach (json_decode(self::getCache(), true) as $route) {
                list($method, $uri) = explode('@', $route);
                if (strstr($method, 'GET')) $routes[] = $uri;
            }
            self::$routes = $routes;
        }
        return self::$routes;
    }

    public static function getUserInfo()
    {
        if (is_null(self::$info)) {
            self::$info = (new self())->getRedis('analyze')->get(self::getUserId() . '_info');
        }
        return self::$info;
    }

    public static function getTree($p_id, $labels)
    {
        $out = '';
        foreach ($labels[$p_id] as $label) {
            $id = $label['id'];
            $badge = '<span class="badge">' . $label['level'] . '</span>';
            $multi = '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>';
            $has = isset($labels[$id]) ? $multi : '';
            $out .= '<li class="treeview"><a href="#">' . $badge . $label['name'] . ' <' . $id . '>' . $has . '</a>';
            if (isset($labels[$id])) {
                $out .= '<ul class="treeview-menu">' . self::getTree($id, $labels) . '</ul>';
            }
            $out .= '</li>';
        }
        return $out;
    }

    public static function renderOptions($item_s, $key, $array)
    {
        $out = '<div class="btn-group" role="group">';
        foreach ($item_s as $item) {
            $class = $array[$key] == $item ? ' btn-primary active' : '';
            $url = url('/analyze');
            foreach ($array as $k => $v) {
                $url .= '/' . ($key == $k ? $item : $v);
            }
            $out .= '<a class="btn btn-default' . $class . '" href = "' . trim($url, '/') . '">' . $item . '</a>';
        }
        return $out . '</div>';
    }

    public static function displayAccount($account)
    {
        return "<td>{$account['nickname']}</td><td>{$account['user_type_id']}</td><td>{$account['school_id']}</td>";
    }

    public static function oneColumnTable($rows, $title = null)
    {
        if (empty($rows)) return '';
        $out = '<table class="table table-bordered table-hover">';
        if (!empty($title)) $out .= '<caption>' . $title . '</caption>';
        foreach ($rows as $row) {
            $out .= '<tr><td>' . $row . '</td></tr>';
        }
        return $out . '</table>';
    }

    public static function treeView($label, $children, $icon)
    {
        foreach ($children as $k => $child) {
            if (!self::checkRoute($child)) unset($children[$k]);
        }
        if (empty($children)) return '';
        $uri = substr(explode('?', \Request::getRequestUri())[0], 1);
        $active = in_array($uri, $children) ? ' menu-open' : '';
        $block = in_array($uri, $children) ? 'style="display: block;"' : '';
        $parent = '<i class="fa ' . $icon . '"></i><span>' . $label . '</span>';
        $angle = '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>';
        $out = '<li class="treeview' . $active . '"><a href="#">' . $parent . $angle . '</a><ul class="treeview-menu" ' . $block . '>';
        foreach ($children as $name => $url) {
            $act = $url == $uri ? ' class="active"' : '';
            $fa = $url == $uri ? 'fa-check-circle-o' : 'fa-circle-o';
            $out .= '<li' . $act . '><a href="' . url($url) . '"><i class="fa ' . $fa . '"></i> ' . $name . '</a></li>';
        }
        return $out . '</ul></li>';
    }

    public static function single_bar($name, $url, $icon)
    {
        if (!self::checkRoute($url)) return '';
        return '<li><a href="' . url($url) . '"><i class="fa ' . $icon . '"></i><span>' . $name . '</span></a></li>';
    }

    protected static function checkRoute($route)
    {
        $exist = false;
        foreach (self::getRoutes() as $item) {
            if ($route == 'analyze/select/no_group' && $item == 'analyze/{type}/{group}/{auth?}') $exist = true;
            if ($route == $item) $exist = true;
        }
        return $exist;
    }

    public static function modifierToIcon($modifier)
    {
        $modifiers = ['public' => 'fa-unlock fa-flip-horizontal text-green', 'protected' => 'fa-key text-gry', 'private' => 'fa-lock text-red'];
        return $modifiers[$modifier];
    }

    public static function equalOrBold($item, $value)
    {
        return $item == $value ? $item : '<b>' . $item . '</b>';
    }

    public static function unsigned($column)
    {
        return isset($column->unsigned) && $column->unsigned == 1 ? '(unsigned)' : '';
    }

    public static function monthOption($month, $start)
    {
        $start = Carbon::parse($start . '/1');
        $diff = Carbon::today()->endOfMonth()->diffInMonths($start);
        $out = '';
        for ($i = 0; $i <= $diff; $i++) {
            $value = $start->year . '/' . $start->month;
            $check = $value == $month ? 'selected' : '';
            $out .= '<option value="' . $value . '" ' . $check . '>' . $value . '</option>';
            $start->addMonth();
        }
        return $out;
    }

    public static function checkThisMonth($month)
    {
        $now = Carbon::now();
        return $now->year . '/' . $now->month == $month;
    }


    public static function buildGroupTree($p_id, $rows)
    {
        $out = '';
        foreach ($rows[$p_id] as $row) {
            $id = $row->id;
            $out .= '<li><i class="fa fa-caret-right"></i> <span onclick="showColumns(this, ' . $id . ')">' . $row->name . ' - '. $row->code . '</span>';
            if (isset($rows[$id]))
                $out .= '<ul>' . self::buildGroupTree($id, $rows) . '</ul>';
            $out .= '</li>';
        }
        return $out;
    }

    public static function getColumnInfo($column, $table, $module, $project)
    {
        if (isset($column[$table])) return $column[$table];
        if (isset($column[$module])) return $column[$module];
        if (isset($column[$project])) return $column[$project];
        return $column['default'];
    }

}