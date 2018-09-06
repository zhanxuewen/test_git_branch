<?php

namespace App\Helper;

class BladeHelper
{
    public static function getTree($p_id, $labels)
    {
        $out = "<ol>";
        foreach ($labels[$p_id] as $label) {
            $out .= "<li> <".$label['id']."> ".$label['name']." [ ".$label['level']." ]";
            if (isset($labels[$label['id']])) $out .= self::getTree($label['id'], $labels);
            $out .= "</li>";
        }
        $out .= "</ol>";
        return $out;
    }
    
    public static function renderOptions($item_s, $key, $array)
    {
        $out = '<ul class="option">';
        foreach ($item_s as $item) {
            $array[$key] == $item ? $out .= '<li class="checked">' : $out .= '<li>';
            $url = url('/analyze');
            foreach ($array as $k => $v) {
                $url .= '/'.($key == $k ? $item : $v);
            }
            $out .= '<a href = "'.trim($url, '/').'">'.$item.'</a></li>';
        }
        $out .= '</ul>';
        return $out;
    }
    
    public static function displayAccount($account)
    {
        return "<td>{$account['nickname']}</td><td>{$account['user_type_id']}</td><td>{$account['school_id']}</td>";
    }
}