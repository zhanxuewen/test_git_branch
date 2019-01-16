<?php

namespace App\Library;

class _CURL
{
    protected function curlGet($url, array $data)
    {
        $response = $this->curl('GET', $url, json_encode($data));
        return json_decode($response);
    }
    
    protected function curlPost($url, array $data)
    {
        $response = $this->curl('POST', $url, json_encode($data));
        return json_decode($response);
    }
    
    protected function curlUpload($url, array $data)
    {
        $response = $this->curl('UPLOAD', $url, $data);
        return json_decode($response);
    }
    
    private function curl($method, $url, $data)
    {
        $ch = curl_init($url);
        if ($method == 'UPLOAD') {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $header = ['Content-Type: application/json', 'Content-Length: '.strlen($data)];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}