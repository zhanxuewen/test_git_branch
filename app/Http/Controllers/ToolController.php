<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function getDownload()
    {
        return view('tool.download');
    }

    public function postDownload(Request $request)
    {
        $url = $request->get('url');
        $error = $request->filled('error') ? $request->get('error') : null;
        if (!strstr($url, '{replace}')) {
            $tmp = explode('/', $url);
            $file = $this->storeFiles($url, end($tmp), $error);
            $f_name = end($tmp);
        } else {
            $text = trim($request->get('text'));
            $items = explode("\r\n", $text);
            $zip = new \ZipArchive();
            $file = storage_path('app') . '/public/dump.zip';
            foreach ($items as $item) {
                $_url = str_replace('{replace}', trim($item), $url);
                $tmp = explode('/', $_url);
                $f = $this->storeFiles($_url, end($tmp), $error);
                if ($f === false) continue;
                if ($zip->open($file, \ZipArchive::CREATE) !== true) return response('Zip Error', 500);
                $zip->addFile($f, end($tmp));
                $zip->close();
                unlink($f);
            }
            $f_name = 'dump.zip';
        }
        return response()->download($file, $f_name)->deleteFileAfterSend(true);
    }

    protected function storeFiles($url, $name, $error = null)
    {
        $data = $this->curlGet($url, false);
        if (!is_null($error) && $data == $error) return false;
        $dir = storage_path('app') . '/public';
        $file = $dir . '/' . $name;
        $f = fopen($file, "w+");
        fputs($f, $data);
        fclose($f);
        return $file;
    }
}
