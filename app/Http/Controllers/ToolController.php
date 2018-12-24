<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ToolController extends Controller
{
    protected $result = [];

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
            $this->appendResult($f_name, ($file == false ? 'fail' : 'success'));
        } else {
            $text = trim($request->get('text'));
            $separator = $request->filled('separator') ? $request->get('separator') : "\r\n";
            $items = $separator == '\r\n' ? explode("\r\n", $text) : explode($separator, $text);
            $zip = new \ZipArchive();
            $file = storage_path('app') . '/public/dump.zip';
            foreach ($items as $item) {
                $_url = str_replace('{replace}', trim($item), $url);
                $tmp = explode('/', $_url);
                $name = end($tmp);
                $f = $this->storeFiles($_url, $name, $error);
                $this->appendResult(trim($item), ($f == false ? 'fail' : 'success'));
                if ($f === false) continue;
                if ($zip->open($file, \ZipArchive::CREATE) !== true) return response('Zip Error', 500);
                $zip->addFile($f, $name);
                $zip->close();
                unlink($f);
            }
            $f_name = 'dump.zip';
        }
        return redirect('tool/download')
            ->with(is_file($file) ? 'file' : 'none_file', json_encode($file . '|' . $f_name))
            ->with('result', json_encode($this->result));
    }

    public function ajaxDownload(Request $request)
    {
        $file = $request->get('file');
        $name = $request->get('name');
        return response()->download($file, $name)->deleteFileAfterSend(true);
    }

    protected function storeFiles($url, $name, $error = null)
    {
        $data = $this->curlGet(str_replace(' ', '%20', $url), false);
        if (!is_null($error) && $data == $error) return false;
        $dir = storage_path('app') . '/public';
        $file = $dir . '/' . $name;
        $f = fopen($file, "w+");
        fputs($f, $data);
        fclose($f);
        return $file;
    }

    protected function appendResult($content, $type)
    {
        $this->result[$type][] = $content;
    }
}