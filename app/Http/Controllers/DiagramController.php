<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiagramController extends Controller
{
    public function uml(Request $request)
    {
        $_project = $request->get('project', 'core');
        $projects = ['core' => '在线助教', 'learning' => '百项过', 'common' => '通用'];
        $uml_dir = public_path('asset/diagrams/uml/');
        $images = file_exists($uml_dir . $_project . '/Modules.jpg') ? ['Modules.jpg'] : [];
        foreach (scandir($uml_dir . $_project) as $file) {
            if (!in_array($file, ['.', '..', '.DS_Store']) && !strstr($file, 'Modules')) $images[] = $file;
        }
        $dir = 'asset/diagrams/uml/' . $_project . '/';
        return view('diagram.uml', compact('projects', '_project', 'images', 'dir'));
    }
}
