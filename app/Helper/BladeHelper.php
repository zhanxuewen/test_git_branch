<?php

namespace App\Helper;

class BladeHelper
{
    protected static $level
        = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
        ];
    
    public static function getTree($p_id, $labels)
    {
        $out = '';
        foreach ($labels[$p_id] as $label) {
            $id    = $label['id'];
            $badge = '<span class="badge">'.self::$level[$label['level']].'</span>';
            $out   .= '<li class="treeview"><a href="#">'.$badge.$label['name'].' <'.$id.'>';
            if (isset($labels[$id])) {
                $out .= '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>';
            }
            $out .= '</a>';
            if (isset($labels[$id])) {
                $out .= '<ul class="treeview-menu">';
                $out .= self::getTree($id, $labels);
                $out .= '</ul>';
            }
            $out .= '</li>';
        }
        return $out;
    }
    
    public static function renderOptions($item_s, $key, $array)
    {
        $out = '<div class="btn-group" role="group">';
        foreach ($item_s as $item) {
            $class = $array[$key] == $item ? $class = ' btn-primary active' : '';
            $url   = url('/analyze');
            foreach ($array as $k => $v) {
                $url .= '/'.($key == $k ? $item : $v);
            }
            $out .= '<a class="btn btn-default'.$class.'" href = "'.trim($url, '/').'">'.$item.'</a>';
        }
        $out .= '</div>';
        return $out;
    }
    
    public static function displayAccount($account)
    {
        return "<td>{$account['nickname']}</td><td>{$account['user_type_id']}</td><td>{$account['school_id']}</td>";
    }
    
    public static function oneColumnTable($title, $rows)
    {
        if (empty($rows)) return '';
        $out = '<table class="table table-bordered table-hover">';
        $out .= '<caption>'.$title.'</caption>';
        foreach ($rows as $row) {
            $out .= '<tr><td>'.$row.'</td></tr>';
        }
        $out .= '</table>';
        return $out;
    }
}