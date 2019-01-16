<?php

namespace App\Library;

use Illuminate\Http\UploadedFile;

class AliOss extends _CURL
{
    public function uploadImage($image)
    {
        $response = $this->upload($image, 'image');
        if ($response->code != 200) {
            return $response->data;
        }
        preg_match('/com\/([a-zA-Z0-9]+)\.([a-zA-Z]+)/', $response->data, $match);
        $imageInfo = getimagesize($image);
        return [
            'src' => $response->data,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'name' => $match[1],
            'type' => $match[2]
        ];
    }
    
    public function uploadAudio($audio)
    {
        $response = $this->upload($audio, 'audio');
        if ($response->code != 200) {
            return $response->data;
        }
        preg_match('/com\/([a-zA-Z0-9\.]+)/', $response->data, $match);
        return [
            'src' => $response->data,
            'name' => $match[1]
        ];
    }
    
    public function uploadApk($apk)
    {
        return $this->upload($apk, 'apk');
    }
    
    protected function upload(UploadedFile $file, $type)
    {
        $curlFile = new \CURLFile($file, $file->getClientMimeType(), $file->getClientOriginalName());
        $data     = ['file' => $curlFile, 'type' => $type];
        $host     = env('THIRD_PARTY_HOST');
        $url      = $host.(substr($host, -1) == '/' ? '' : '/').'aliOss/upload/file';
        return $this->curlUpload($url, $data);
    }
}