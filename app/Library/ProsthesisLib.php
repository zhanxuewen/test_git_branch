<?php

namespace App\Library;

use Redis;

class ProsthesisLib
{
    protected $host;

    protected $token;

    protected $project;

    public function __construct()
    {
        $this->host = rtrim(env('THIRD_PARTY_HOST'), '/') . '/';
        $this->token = \Hash::make(env('SERVICE_REQUEST_TOKEN'));
        $this->project = env('PROJECT_FOR_THIRD_PARTY', 'core');
    }

    // Public

    /**
     * @param $phone
     * @param string $connector
     * @return bool|string
     */
    public function findPhoneLocation($phone, $connector = '--')
    {
        return $this->locate('phone', $phone, $connector);
    }

    /**
     * @param $ip
     * @param string $connector
     * @return bool|string
     */
    public function findIpLocation($ip, $connector = '--')
    {
        return $this->locate('ip', $ip, $connector);
    }

    /**
     * @param $mobile
     * @param $message
     * @param array $params
     * @param int $limit
     * @param int $time
     * @param string $key
     * @return int
     */
    public function sendMsg($mobile, $message, $params = [], $limit = 2, $time = 1800, $key = '')
    {
        return $this->sendOut('message', $key . $mobile, $mobile, $message, $params, $limit, $time);
    }

    /**
     * @param $key
     * @param $mobile
     * @param $message
     * @param array $params
     * @param int $limit
     * @param int $time
     * @return int
     */
    public function sendVoice($key, $mobile, $message, $params = [], $limit = 2, $time = 1800)
    {
        return $this->sendOut('voice', $key . $mobile, $mobile, $message, $params, $limit, $time);
    }

    public function uploadImage($image, $name = '')
    {
        $response = $this->upload($image, 'image', $name);
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

    public function uploadAudio($audio, $name = '')
    {
        $response = $this->upload($audio, 'audio', $name);
        if ($response->code != 200) {
            return $response->data;
        }
        preg_match('/com\/([a-zA-Z0-9\.]+)/', $response->data, $match);
        return [
            'src' => $response->data,
            'name' => $match[1]
        ];
    }

    public function uploadVideo($video, $name = '')
    {
        return $this->upload($video, 'video', $name);
    }

    public function uploadApk($apk, $name = '')
    {
        return $this->upload($apk, 'apk', $name);
    }

    public function copyApk($from, $to)
    {
        return $this->copy('apk', 'apk/' . $from, 'apk/' . $to);
    }

    // ====================================================================================

    // Protected

    protected function locate($type, $value, $connector = '--')
    {
        $data = $this->curlGet($type . '/get/location', [$type => $value, 'connector' => $connector]);
        if ($data->code == 200) {
            return $data->data;
        }
        return false;
    }

    protected function upload($file, $type, $name = '')
    {
        if (empty($name)) $name = $file->getClientOriginalName();
        $curlFile = new \CURLFile($file, $file->getClientMimeType(), $name);
        $data = ['file' => $curlFile, 'type' => $type];
        return $this->curlUpload('aliOss/upload/file', $data);
    }

    protected function copy($type, $from, $to)
    {
        $data = ['type' => $type, 'from' => $from, 'to' => $to];
        return $this->curlPost('aliOss/copy/file', $data);
    }

    protected function sendOut($type, $rKey, $mobile, $message, $params = [], $limit = 2, $time = 1800)
    {
        $send_time = $this->getRedis($rKey) ?: 0;
        if ($send_time >= $limit) return 2;
        $sendData = ['phone' => $mobile, 'search' => $message, 'params' => $params, 'project' => $this->project];
        $data = $this->curlPost('phone/send/' . $type, $sendData);
        if ($data->code != 200) return 0;
        $this->setRedis($rKey, $send_time + 1, $time);
        return 1;
    }

    // ====================================================================================

    // Private

    private function curlGet($url, array $data)
    {
        $response = $this->curl('GET', $url, json_encode($data));
        return json_decode($response);
    }

    private function curlPost($url, array $data)
    {
        $response = $this->curl('POST', $url, json_encode($data));
        return json_decode($response);
    }

    private function curlUpload($url, array $data)
    {
        $response = $this->curl('UPLOAD', $url, $data);
        return json_decode($response);
    }

    /**
     * @param $method
     * @param $uri
     * @param $data
     * @return bool|string
     */
    private function curl($method, $uri, $data)
    {
        $symbol = strstr($uri, '?') ? '&' : '?';
        $url = $this->host . $uri . $symbol . 'token=' . $this->token;
        $ch = curl_init($url);
        if ($method == 'UPLOAD') {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $header = ['Content-Type: application/json', 'Content-Length: ' . strlen($data)];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function setRedis($key, $value, $timeout = 0)
    {
        Redis::set($key, $value);
        if ($timeout !== 0) {
            Redis::expire($key, $timeout);
        }
    }

    private function getRedis($key)
    {
        if (Redis::ttl($key) == 0) {
            //已过期但是未删除的redis
            return null;
        }
        return Redis::get($key);
    }
}
