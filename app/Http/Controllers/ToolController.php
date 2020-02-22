<?php

namespace App\Http\Controllers;

use App\Library\Curl;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    protected $result = [];

    public function getDownload()
    {
        return view('tool.download');
    }

    public function getUpload()
    {
        return view('tool.upload');
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

    protected function ajaxUpload(Request $request)
    {
        $third_url = [
            'online' => 'https://msservice.wxzxzj.com',
            'dev' => 'http://dev.msservice.vanthink.cn'
        ];
        $env = $request->get('env', 'dev');
        $type = $request->get('type', 'image');
        $file = $request->file('file');
        putenv('THIRD_PARTY_HOST=' . $third_url[$env]);
        $info = [];
        if ($type == 'image') $info = $this->aliOss->uploadImage($file);
        if ($type == 'audio') $info = $this->aliOss->uploadAudio($file);
//        if ($type == 'video') $info = $this->aliOss->uploadVideo($file);
        if ($type == 'apk') $info = $this->aliOss->uploadApk($file);
        $result = isset($info['src']) ? $info['src'] : $info;
        $this->logContent('tool_upload', 'upload', $result);
        return $result;
    }

    public function ajaxDownload(Request $request)
    {
        $file = $request->get('file');
        $name = $request->get('name');
        return response()->download($file, $name)->deleteFileAfterSend(true);
    }

    protected function storeFiles($url, $name, $error = null)
    {
        $data = Curl::curlGet(str_replace(' ', '%20', $url));
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
