<?php

namespace App\Console\Commands\XLDN;

use App\Console\other\Http\ShowapiRequest;
use App\Console\Schedules\Learning\ExportBXGMonthCardReport;
use App\Console\Schedules\Learning\ExportBXGMonthReport;
use App\Console\Schedules\XLDN\ExportXLDNMonthOperator;
use App\Console\Schedules\ZXZJ\ExportZXZJWeekProduct;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SyncVanthinkUser extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:vanthink:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步万星用户';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(){
/*
        config(['database.default' => 'local']);
        $url = "http://route.showapi.com/1797-1";
        $showapi_appid="344385";
        $showapi_sign="29404746b6c540ae93c9e3f1ee48f995";
        $i = 49;
        do {
            $save_data = [];

            $req = new ShowapiRequest($url, $showapi_appid, $showapi_sign);
            $req->addTextPara("page", $i);
            $req->addTextPara("url", "MzUyMDA4OTY3MQ==");
            $response = $req->post();
            $res = json_decode( $response->getContent(), true );
            var_dump(  $response->getContent() );
            $flag = false;
            if (isset( $res['showapi_res_body']) && count($res['showapi_res_body'])
                && isset( $res['showapi_res_body']['article_info'])
                && count($res['showapi_res_body']['article_info'])){
                $body = $res['showapi_res_body'];
                $articles = $body['article_info'];
                foreach ( $articles as $article){
                    $save_title = $article['title'];
                    $save_url = $article['url'];
                    $save_publish_time = $article['publish_time'];
                    $save_cover_img = $article['cover_img'];
                    $save_index = $i;

                    $save_data[] =[
                        'name' => '浪尖聊大数据',
                        'wechat' => 'bigdatatip',
                        'biz' => 'MzUyMDA4OTY3MQ==',
                        'title' => $save_title,
                        'url' => $save_url,
                        'cover_img' => $save_cover_img,
                        'publish_time' => $save_publish_time ,
                        'index' => $save_index,
                    ];

                }
                $err_code = $body['err_code'];
                $err_msg = $body['err_msg'];

                $this->info('page : '.$i.', code: '.$err_code.', msg: '.$err_msg );
                $status = $body['status'];
                $flag = $body['has_more'] &&  $status == 'success';
            }
            $i++;
            if (count($save_data)){
                \DB::table('article')->insert($save_data);
            }
            sleep( rand(1,8) );
        } while ($flag);








        dd( 'done ');

        $res0 = '{"showapi_res_error":"","showapi_res_id":"6022358b8d57bad71f20fe40","showapi_res_code":0,"showapi_res_body":{"article_info":[{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfrYVxlxd18jQ5USxErUgM8ltqFiaYpNW6TfvhYdAj7qOBEWIcoDElZxuI3Io3KiccZsr7XcS4dcY1A/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-08 22:31:30","title":"Flink实战 - Binlog日志并对接Kafka实战","url":"https://mp.weixin.qq.com/s/QFlZ3ACvqi1Idp5FdB7EUg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfrYVxlxd18jQ5USxErUgM8ZicD6scC34XFlVviaUHcYEnoTicIaDIChdOgC6ewlia1ic8Fs37iaoGiaPMjA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-08 22:31:30","title":"十道海量数据处理面试题","url":"https://mp.weixin.qq.com/s/zLMXQKTDedH24Hd7tA8LYQ"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfhHHDicffUD4pe7uT1CQdozkeeeVqWutbLAs6RYicyRCwfQiceOWvc9buvKVXMQaPVW3d00h5WQKcdw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-06 17:48:18","title":"Flink 在实时金融数据湖的应用","url":"https://mp.weixin.qq.com/s/VzCjNJK5EV8zm7WH6MFwag"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdtTiaAIjUu261pSprzGBIxMNRic4JA8DvrcIRWAdy65MRU9anEtB0PRZS1micFMgjeVcicx1R9q8BKNQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-06 17:48:18","title":"5种经典的数据分析思维和方法","url":"https://mp.weixin.qq.com/s/_jvXsP-UzThCUbthmsXsJg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdtTiaAIjUu261pSprzGBIxMwVTUAZaS5tDKHpx6ApHFGzTCSaF3t30TzPLqFPlTO6l0qSC0YGBYHw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-05 12:24:22","title":"协同过滤推荐算法在MapReduce与Spark上实现对比","url":"https://mp.weixin.qq.com/s/jtXmAeEQosE5rsWvLd8Cwg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdtTiaAIjUu261pSprzGBIxMMOILdFSXCsNSDIll0LuWkiajXXGbZiaI3YfgibI34gKs56iaTNnNdt9njA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-05 12:24:22","title":"一篇文章，读懂Netty的高性能架构之道","url":"https://mp.weixin.qq.com/s/Uq24FZWEmoT5X8HthrNx_Q"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcEdxicuwOvyC6NgdnVJ9sYyRFBWoplPcKVY0aGVn7ibKAQibldicIzRx3p8XrHyGCBqcMIoIxOradgibQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-04 17:33:36","title":"Tips｜如何高效入门大数据？","url":"https://mp.weixin.qq.com/s/B-vBwqgUYof9YW5WoHjjcw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcEdxicuwOvyC6NgdnVJ9sYy9XY2sWWCytzuPss3lH4CL6HXxVaCHruOcAsVMfvia2ibeCIIos24wfvQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-04 17:33:36","title":"数仓潮汐猎人 | 数据仓库企业数仓拉链表制作","url":"https://mp.weixin.qq.com/s/OEIQkbwYL_Q4-VR4PjKqzg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXeg9eOZQyp91MX0UUUiaKxAT7lpejSIsgdDnX00VB78GC4sykC7P2lH6rfZAlibYrIx2ic5GurVIJiatg/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-03 11:51:26","title":"天猫双十一，Flink 是如何支撑一分钟破百亿成交额的？","url":"https://mp.weixin.qq.com/s/gfJbAXzg7Z0BArK8WFTKeA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXeg9eOZQyp91MX0UUUiaKxATWBnY2gsaGWy324bMvD4r7P3AaS9AtXSlY6o1mkmwb5Cd6sIiawYhhRA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-03 11:51:26","title":"用户建模教程：3步搭建一个流失模型","url":"https://mp.weixin.qq.com/s/xJWHqDEivnwhP3q0nl5NCA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcqYC0bOBmJ3zNscDE6SqrOuIicqWJicsYL7YowfIAI8DAS1LHtE2WZbpuqvkg3Ao0PMF3VbnNQupmg/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-01 23:49:37","title":"利用 Spark DataSource API 实现Rest数据源","url":"https://mp.weixin.qq.com/s/ILxS5zCJak-WLkZtBIwBLw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcqYC0bOBmJ3zNscDE6SqrOXX8cuku3vGCibrcn0vhFByJvjkh8PKW8bwplOYudib9oRq8Izic6ictcOw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-02-01 23:49:37","title":"浅谈 RESTful API","url":"https://mp.weixin.qq.com/s/W0fkFm3TeMf-AuuncLq30w"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdploy4aZnt5AfSpP3OAxxlUiaKbZv6HBtoAJw6mZ7b6Lz0KxKqb7PWXnRuNQ4uSQlvKf1r7K2Q0RA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-31 15:02:24","title":"用户留存分析案例 | 以京东、淘宝、饿了么为例！","url":"https://mp.weixin.qq.com/s/TeJtMB8iF8hlC7dwrTiJ5g"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdploy4aZnt5AfSpP3OAxxlwdhxBicPibEUZVeEicRNjshSFdDOHhATkwiaRK4NlsNIk4zwEiaOptYPrEg/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-31 15:02:24","title":"干货 | 优秀的数据敏感度应该如何培养？","url":"https://mp.weixin.qq.com/s/vGqQZevCVuYEdH_7vwtSTg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXft31crJFHwicMvskPawjJIKhajQ56X09G1xTdfaXSXWR3tcpLhlaMw8gss9AW02RxNEYXKf3YGuMQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-29 23:42:49","title":"干货 | 基于 Python 的信用评分模型实战！","url":"https://mp.weixin.qq.com/s/YWf4L1VilbnxrNCtgb5Wjw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXft31crJFHwicMvskPawjJIKbcEvN17py84hcmKZIiab1avUuoot3mAyjmkaSLv5AL7ag99uJ0dAFCw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-29 23:42:49","title":"「数据分析报告」思路提升超实用指南！","url":"https://mp.weixin.qq.com/s/dQGyYOyVbVZa3C7k7miz8w"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfcFtZTWY1BeniaHl1dRrzJIZqyLibfae0N8iafWqibsA6oWGCvExP7mEictWGImTjZxnvMhmzdsvHRS2w/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-28 21:48:40","title":"稀疏索引与其在Kafka和ClickHouse中的应用","url":"https://mp.weixin.qq.com/s/rqzoU1_qUvLjDQNoU9tEQQ"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXeEvYM1obrjy8wAIBflkxR4CBSroS8fkO7VQXsPGB5kuekBXvxrqiawXKsbPd8atvEZgztCV3kcXNw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-27 23:32:29","title":"网易基于Filebeat的日志采集服务设计与实践","url":"https://mp.weixin.qq.com/s/vpXygg0wVZVF8bIQ_nKLnw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXeEvYM1obrjy8wAIBflkxR48lHlN5rIBJwPEFtupialjTIJ8jk7ZOcDOh5YuTjTqWkSicV9vPyd11icQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-27 23:32:29","title":"代码中大量的ifelse，你有什么优化方案?","url":"https://mp.weixin.qq.com/s/liaad1UCOuF6_wMGCC-PhQ"}],"contact_me":"","err_code":"","err_msg":"","has_more":1,"left":0,"next_page":1,"ret_code":"0","status":"success"}}';

        $res1 = '{"showapi_res_error":"","showapi_res_id":"602235e68d57bad71f33698d","showapi_res_code":0,"showapi_res_body":{"article_info":[{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdSktTibiaof2ickVwVhu4iaiaxB0upPqbABFHQXpLOBIJcgLwxwK1kwqsO4aYjicq0Z8TiabAow4icmrG9ibQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-26 22:54:56","title":"金融数仓体系建设","url":"https://mp.weixin.qq.com/s/vJUBIGUMyyVm-cjfBSOqqg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdSktTibiaof2ickVwVhu4iaiaxBfFPrZgxniaGU6rWLNpd0p3AkWbaBSibpiaKOemEtSbLTF6yicz8JAzVaoA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-26 22:54:56","title":"Redis 越来越慢？常见延迟问题定位与分析","url":"https://mp.weixin.qq.com/s/HMb8dgAIczoscZaWEXc7vw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXceqyRicSJly5KXbIdmNxgrLhX2h9EhA2O0WQgmUjOtztu6YjiaDznSv1kXEkZvJ7XyC5mosOSsx55g/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-25 23:44:28","title":"分享 | 企业大数据平台仓库架构建设思路","url":"https://mp.weixin.qq.com/s/xUpwttIxIJYSpOGLZxKE2A"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfAnWPQhvibQaiagb6ic0A7SukGkdTWUIiaBIiabdUaI5oibch1q6ibphHezQOCzzzsZ2QePLr0I8840icPgQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-25 23:44:28","title":"常用的 JVM 性能调优监控工具，太强大了！","url":"https://mp.weixin.qq.com/s/-Ipywa11EbkdHi3lZV5kRA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfAnWPQhvibQaiagb6ic0A7SukBXlnibTrRCQOgicqTeQ6YZW4T59CM81x8icNeJcVNS0G334j00ic2mUyHQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-25 09:10:31","title":"实时数据仓库必备技术：Kafka知识梳理","url":"https://mp.weixin.qq.com/s/zC9PGco4rUSDIJfsch1TuQ"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_png/TwK74MzofXdUbAOOfc1QZv0hF7e8LVl9nSKeBzSD4fGRM9NNL1UkicU2b76Qr4FzGMp5YMxrWzEFamD2lTqhOIw/0?wx_fmt=png","mp_name":"","publish_time":"2021-01-24 16:54:22","title":"如何成为顶尖的“数据分析师”？10年前辈万字经验","url":"https://mp.weixin.qq.com/s/JwcpSSZ8-AtKETbXWk2xUg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfAnWPQhvibQaiagb6ic0A7SukgBaQ07ia9EOTefk5gicPiccZYPfQAJT5wssFlWsuW0HEopHw1uoDEJP8A/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-23 12:06:11","title":"建立数据思维的13个实用思维工具","url":"https://mp.weixin.qq.com/s/Kx2ethc3tZLMJNTD6ufktQ"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcrREhJ4oic0m27EGI5Sib99K4480b5nzQl3ZTJETCbT1IibTzXxjLQy2hPHVPLwoCNviadhSgsdHZAqw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-21 22:33:11","title":"数仓潮汐猎人 | 数据仓库企业数仓拉链表制作","url":"https://mp.weixin.qq.com/s/2TLqTx8LCZIBhSNT5C3mqw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcrREhJ4oic0m27EGI5Sib99Kw1m9nGrQuwq20vlubj96f4FUqRDfIJqeWszBEFKuiaJLwWReEnzcASg/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-21 22:33:11","title":"19 条 MySQL 技巧，效率至少提高 3倍！","url":"https://mp.weixin.qq.com/s/xnPoDtWwlatXRr2xpHNY8A"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfruPMaUUOvL41KDaFicqfPJUicKcMub5fVWjfDAXmXTq8duJh5Lqrm3DiayM5iaWUBS65ErHpcKU1lSw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-21 11:53:51","title":"Flink SQL FileSystem Connector 分区提交与自定义小文件合并策略 ​","url":"https://mp.weixin.qq.com/s/OanhDnYUDGpWz5IVrMzOGw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfruPMaUUOvL41KDaFicqfPJSTRvk32TVIibeq31A31l9htDicO8K5B4bZDmjuyEia7YUkYhV9AiauXibeQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-20 17:41:42","title":"从Lambda到无Lambda，领英吸取到的教训","url":"https://mp.weixin.qq.com/s/P4JwUYo5vPz5gFT4x4_3tA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfruPMaUUOvL41KDaFicqfPJXwurS9KoHfO7JAnLnoW4BF4PYyd5fbPSGWw9SKgtqPZesQlrwiapJxQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-20 17:41:42","title":"工作 5 年，同事连 Java 日志体系都没搞懂！","url":"https://mp.weixin.qq.com/s/slHdxSZtfetJaOx-HJsKhg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdNicsA1UVhI1JqPY0ALFhFFnLfdLyUyfk0dR4JrFdlc0DryVq4rPQjsX5BBrLAPBXfT1ZucnQ6TaA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-18 23:10:43","title":"再谈双亲委派模型与Flink的类加载策略","url":"https://mp.weixin.qq.com/s/TSHQ4R9LcT4eeT-2F5pRvg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdUbAOOfc1QZv0hF7e8LVl9EDADxbfeo8JHLgu4hM6SSAOhFXb3Zzms1TYWZC4u42ianF6Kx3Rw72w/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-18 23:10:43","title":"6大常用数据分析模型详解","url":"https://mp.weixin.qq.com/s/rR4RAPmO8zoiUJzJmC2GQg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXd6J7GssnCicmCnGicfypWPdPQIpX5OIhRpd8HhPmyANKCicrhhdD71QngnhlfGbzvh7ZrPyK151fQ3w/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-17 12:06:02","title":"醍醐灌顶 | 我们谈论的Exactly once到底是什么？","url":"https://mp.weixin.qq.com/s/JN4t8vX19zqAQyjcEgmfvg"}],"contact_me":"","err_code":"","err_msg":"","has_more":1,"left":0,"next_page":2,"ret_code":"0","status":"success"}}';

        $res2 = '{"showapi_res_error":"","showapi_res_id":"602236258d57bad81f80293f","showapi_res_code":0,"showapi_res_body":{"article_info":[{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdrkJ8Fn8oQKIOicZeJZbVdWrjqHQurhPwef1Y4udiaI8xVvCuw9cRgnVgkdBp3xib24WSWO9xhibDMJQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-16 16:22:31","title":"干货，OLAP数仓从百万到百亿级数据量实时分析","url":"https://mp.weixin.qq.com/s/iPq3iKdMaH-abm5SjHBZDQ"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdrkJ8Fn8oQKIOicZeJZbVdWZpe4C4AtFEXibUeluxKaZia2XVL5MEzonWNgRqGgicV7xnvx5DcXE6mTQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-15 08:36:28","title":"Apache Doris在京东搜索实时OLAP中的应用实践","url":"https://mp.weixin.qq.com/s/mrMOp0oG7YiJOfHVKCPhjg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdrkJ8Fn8oQKIOicZeJZbVdWBeyic6J6SAM3SicffNZDkB5FbOJeMd3JoChak9BXTBYKUibF0Xz7tJR3Q/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-15 08:36:28","title":"深入理解 RabbitMQ 的前世今生","url":"https://mp.weixin.qq.com/s/5dBRXrFj-FAa26zgibHs_Q"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcO9vKZtCTRibXiaCOJqcbohcAg38owKtKuFicBdYba9CXentEUicdVXibLpuicnoMK2mlUfdYbka2mJ9uw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-14 00:12:17","title":"浅淡 Apache Kylin 与 ClickHouse 的对比","url":"https://mp.weixin.qq.com/s/FOD80FrOnNTyDi-V7sTGUw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXec77G0THfB7Oia5jornB2QRDJI66eglO1xqAtO5fU8dL88Pef0cibxic8yqydIctMwHtsK2HWsQy2Pg/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-13 12:12:59","title":"基于实际业务场景的几套超干指标体系！","url":"https://mp.weixin.qq.com/s/ZuVkZmjEBxMY0aXCNFbrKA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfo4mgerYINwGOk0JDicYgRffhDy5dI4TVmiapjUAbkSR4Laf03mnvDsibFDjNPapaZ0A1ibTxW6yElLA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-11 22:34:33","title":"字节跳动ClickHouse在用户增长分析场景的应用","url":"https://mp.weixin.qq.com/s/ac8LgqJ-e09VLelj9Y7x9g"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfo4mgerYINwGOk0JDicYgRfGicfJGJFfELFR4VDwk48dW726hsLfvM2qkkicEetdAicz08KWtmQnXgNA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-11 12:34:36","title":"推荐系统之标签体系","url":"https://mp.weixin.qq.com/s/9D9d1-q3f0rnNfNuibUB2g"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXdvKoib8ElGPL2JqyAyYzo4ZyUykVa7shz9zHrxkiaLSjSvMyqecNjbXRGmg9O28OnIrOqy4hbB9RgA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-11 12:34:36","title":"从Nginx、Apache工作原理看为什么Nginx比Apache高效！","url":"https://mp.weixin.qq.com/s/LwkwvClxE8rKYovRT5CVIg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXcyaWzzibyfxTe5B6rqy89blZMmjFF6JicFFBicPgyVd96fJVpRnx8ars2ticoXA3dqpbrricUoY750j8w/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-10 12:27:22","title":"大数据kafka理论实操面试题","url":"https://mp.weixin.qq.com/s/tHEd8eOCQ3sXwcmP9F1H9Q"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_png/TwK74MzofXcyaWzzibyfxTe5B6rqy89blibnZNb4EHxxDia5Zs2QR2gkc5aN6b8icj78yMqaxTuzmdmbq7TEPBBWjw/0?wx_fmt=png","mp_name":"","publish_time":"2021-01-10 12:27:22","title":"【唯实践】基于Alluxio优化电商平台热点数据访问性能","url":"https://mp.weixin.qq.com/s/XBMq35FsjIXso3oYjg-6UA"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_png/TwK74MzofXdUbAOOfc1QZv0hF7e8LVl94pTw7B1iboEPTzpicpBweeohbw18JZoribZk1fhFnsuSv2tvfcQ3ibhnGg/0?wx_fmt=png","mp_name":"","publish_time":"2021-01-09 09:36:18","title":"如何构建数据分析框架？分享3个底层思维框架，小白也能看懂","url":"https://mp.weixin.qq.com/s/8lklPrncylH3YnaWp3Nw3A"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfo4mgerYINwGOk0JDicYgRf5v3diagJMDxK2LuRch05WbyaMO8bxmUQsG9KHSiaZdiaVIuqNlISqshHw/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-09 09:36:18","title":"Linux 系统 inodes 资源耗尽，如何解决？","url":"https://mp.weixin.qq.com/s/AK5-oBset0jvrH6KleylEw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfkePB4fEpuTv02quCB5zJgPVWU4xKVTU30ic749rzsGr55aX3cREhuJF30en6jPmJCrDALAibJqqjQ/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-08 08:53:24","title":"全面解析 52 条 SQL 语句性能优化策略，建议收藏！","url":"https://mp.weixin.qq.com/s/7mb3txsewSyRNHhJXMeWxg"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXc3NH1KlkR9PPWnB0tsnEkM9TboOkxO75QyXFNwMlgtJbmW1dyFBzyvc8SJ0JFxVhGzIA0FhiaW55Q/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-07 09:21:18","title":"漫谈千亿级数据优化实践：数据倾斜","url":"https://mp.weixin.qq.com/s/Yx85Y63qF8hqInLhM_uPRw"},{"abstract":"","contact_me":"不需要发票用户可以直接联系作者QQ617512435,9折优惠,更多详情请私聊","cover_img":"http://mmbiz.qpic.cn/mmbiz_jpg/TwK74MzofXfkePB4fEpuTv02quCB5zJgtgbQg2npy3fyCngq7YbU2qF9v3MDdpADPwybYCpV15XvemwGm9EhJA/0?wx_fmt=jpeg","mp_name":"","publish_time":"2021-01-07 09:21:18","title":"深渊之刃 | Greenplum数据库之拉链表的实现","url":"https://mp.weixin.qq.com/s/5HYa6vojMeZtdSm-NqLMtA"}],"contact_me":"","err_code":"","err_msg":"","has_more":1,"left":0,"next_page":3,"ret_code":"0","status":"success"}}';


        dd( json_decode( $res0 , true) , json_decode( $res1 , true));


*/
//        $tmp = new ExportBXGMonthReport();
        $tmp = new ExportZXZJWeekProduct();
        $tmp->handle();
//
        dd('done');
//        $this->clearJwtToken();
//
//        dd('done');


        config(['database.default' => 'zxzj_dev']);

        $sql = <<<EOF
SELECT
	user.*,user_account.id as account_id, user_account.*
FROM
	`user_account` 
	left join user on user.id = user_account.user_id
WHERE
	user_account.`user_type_id` = 1 
	and user.phone = 15210061227
-- 	and user_account.is_available = 1
EOF;
        $user_info = \DB::select(\DB::raw($sql));

        $user_info = json_decode(json_encode($user_info),true);

        $time = Carbon::now()->toDateTimeString();
        config(['database.default' => 'kids_dev']);

        foreach ($user_info as $item_user){

            $phone = $item_user['phone'];

            $user = DB::table('user')
                ->where('phone', $phone)
                ->whereNull('deleted_at')
                ->first();


            if (empty($user)){
                if (empty($phone)){
                    dd($item_user );
                }


                DB::table('user')->insert([
                    'phone' => $phone,
                    'is_available' => 1,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);

                $user = DB::table('user')
                    ->where('phone', $phone)
                    ->whereNull('deleted_at')
                    ->first();
            }


            $user_account = DB::table('user_account')
                ->where('user_id', $user->id)
                ->where('user_type_id', 5)
                ->whereNull('deleted_at')
                ->first();


            if (empty($user_account)){
                DB::table('user_account')->insert([
                    'nickname' =>  $item_user['nickname'],
                    'user_id' =>   $user->id,
                    'user_type_id' =>  5,
                    'password' =>  $item_user['password'],
                    'head_url' =>  $item_user['head_url'],
                    'school_id' => 0,
                    'is_available' => 1,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);

                $user_account = DB::table('user_account')
                    ->where('user_id', $user->id)
                    ->where('user_type_id', 5)
                    ->whereNull('deleted_at')
                    ->first();
            }

            // user_attribute

            $user_account_attribute = DB::table('user_account_attribute')
                ->where('account_id', $user_account->id)
                ->where('key', 'core_account_id')
                ->first();

            if (empty($user_account_attribute)){
                DB::table('user_account_attribute')->insert([
                    'account_id' => $user_account->id,
                    'key' => 'core_account_id',
                    'value' => $item_user['account_id'],
                ]);
            }


            echo '+';

        }

        dd('done');


    }


    public function clearJwtToken()
    {
        Redis::set('time','202004141153');
        $res = Redis::get('time');

        dd($res);

    }
}
