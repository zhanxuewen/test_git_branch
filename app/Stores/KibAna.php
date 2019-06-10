<?php

namespace App\Stores;

class KibAna
{
    protected static $url = 'https://kibana.wxzxzj.com/elasticsearch/_msearch';

    protected static function getIndex()
    {
        $index = ['index' => ['logstash-manage_server-*'], 'ignore_unavailable' => true, 'preference' => 1548307157438];
        return json_encode($index);
    }

    protected static function getConfig($ago, $now)
    {
        $query = "/home/program_box/manage_server/storage/logs/schedule.log";
        $config = ['version' => true, 'size' => 5000, 'sort' => [['@timestamp' => ['order' => 'desc', 'unmapped_type' => 'boolean']]], '_source' => ['excludes' => []], 'aggs' => ['2' => ['date_histogram' => ['field' => '@timestamp', 'interval' => '30m', 'time_zone' => "Asia/Shanghai", 'min_doc_count' => 1]]], 'stored_fields' => ['*'], 'script_fields' => [], 'docvalue_fields' => ['@timestamp'], 'query' => ['bool' => ['must' => [['match_all' => []], ["match_phrase" => ["path" => ["query" => $query]]], ['range' => ['@timestamp' => ['gte' => $ago, 'lte' => $now, 'format' => 'epoch_millis']]]], 'filter' => [], 'should' => [], 'must_not' => []]], 'highlight' => ['pre_tags' => ['@kibana-highlighted-field@'], 'post_tags' => ['@/kibana-highlighted-field@'], 'fields' => ['*' => []], 'fragment_size' => 2147483647]];
        return str_replace(['\/', 'script_fields":[]', 'match_all":[]', '*":[]'], ['/', 'script_fields":{}', 'match_all":{}', '*":{}'], json_encode($config));
    }

    public static function getLog($now, $ago)
    {
        $config = self::getConfig($ago, $now);
        $index = self::getIndex();
        $data = self::scheduleCurlPost(self::$url, "{$index}\n{$config}\n");
        $hits = json_decode($data)->responses[0]->hits;
        return $hits;
    }

    protected static function scheduleCurlPost($url, $data)
    {
        $token = 'Authorization: Basic dmFudGhpbms6NUUlcWQ4bXR3UUEkTTdSYg==';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['kbn-version: 6.2.4', 'content-type: application/x-ndjson', $token]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}