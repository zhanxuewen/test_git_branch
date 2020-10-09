<?php

namespace App\Helper;

use Carbon\Carbon;
use App\Foundation\PdoBuilder;

class BladeHelper
{
    use PdoBuilder;

    protected static $_redis = null;

    protected static $user_id = null;

    protected static $cache = null;

    protected static $routes = null;

    protected static $info = null;

    protected static function _getRedis($conn)
    {
        if (!isset(self::$_redis[$conn])) {
            self::$_redis[$conn] = (new self())->getRedis('analyze');
        }
        return self::$_redis[$conn];
    }

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
            self::$cache = self::_getRedis('analyze')->get(self::getUserId() . '_routes');
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
            $info = self::_getRedis('analyze')->get(self::getUserId() . '_info');
            self::$info = json_decode($info, true);
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
            $out .= '<li><i class="fa fa-caret-down"></i> <span onclick="showColumns(this, ' . $id . ')">' . $row->name . ' - ' . $row->code . ' 
            <i class="' . self::getTypeBg($row->type) . '"></i></span>';
            if (isset($rows[$id]))
                $out .= '<ul>' . self::buildGroupTree($id, $rows) . '</ul>';
            $out .= '</li>';
        }
        return $out;
    }

    protected static function getTypeBg($type)
    {
        switch ($type) {
            case 'default':
                return 'fa fa-battery-0';
            case 'project':
                return 'fa fa-battery-1';
            case 'module':
                return 'fa fa-battery-3';
            case 'table':
                return 'fa fa-battery-4';
        }
        return '';
    }

    public static function getColumnInfo($column, $table, $module, $project)
    {
        if (isset($column[$table])) return $column[$table];
        if (isset($column[$module])) return $column[$module];
        if (isset($column[$project])) return $column[$project];
        return $column['default'];
    }

    public static function dispatchMapShow($rails, $flags, $outline, $row, $ignore)
    {
        $output = '';
        $rails = explode(',', $rails);
        foreach ($flags as $flag) {
            if (in_array($flag['id'], $rails)) {
                $fa = self::checkOutline($outline, $flag['id'], $row, $ignore);
                $output .= '<i class="fa fa-lg ' . $fa . '" style="color: ' . $flag['color'] . '"></i> ';
            }
        }
        return $output;
    }

    protected static function checkOutline($outline, $id, $row, $ignore)
    {
        if (!isset($outline[$id])) {
            return 'fa-circle';
        }

        $out = $outline[$id];
        foreach ($row as $key => $value) {
            if (in_array($key, $ignore) || !isset($out->$key)) continue;
            if ($value != $out->$key) {
                return 'fa-check-square-o';
            }
        }

        return 'fa-check-circle';
    }

    public static function textCss($text)
    {
        if (strstr($text, '</>')){
            $text = str_replace('</>', '</span>', $text);
            $text = preg_replace('/<([\w:,%\-;# ]+)>/i', '<span style="${1}">', $text);
        }
        return $text;
    }

}