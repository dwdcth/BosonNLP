<?php

/**
 * @class:   Boson语义分析
 * @author:  banshan
 * @version: 1.0
 * @date:    2017/1/9
 */
namespace Xdao\Util;
class BosonNLP
{

    const API_HOST = "http://api.bosonnlp.com";
    //情感分析 可以指定行业
    const SENTIMENT_GENERAL = ""; //通用
    const SENTIMENT_AUTO = "auto"; //汽车
    const SENTIMENT_KITCHEN = "kitchen"; //厨具
    const SENTIMENT_FOOD = "food"; //餐饮
    const SENTIMENT_NEWS = "news"; //新闻
    const SENTIMENT_WEIBO = "weibo"; //微博

    //新闻分类 classify/analysis
    const NEWS_SPORTS = 0;//体育
    const NEWS_EDUCATION = 1;//教育
    const NEWS_FINANCE = 2;//财经
    const NEWS_SOCIETY = 3;//社会
    const NEWS_ENTERTAINMENT = 4;// 娱乐
    const NEWS_MILITARY = 5;//军事
    const NEWS_HOME = 6;// 国内
    const NEWS_TECHNOLOGY = 7;// 科技
    const NEWS_INTERNET = 8;//互联网
    const NEWS_HOUSE = 9;// 房产
    const NEWS_INTERNATIONAL = 10;//国际
    const NEWS_WOMEN = 11;//女人
    const NEWS_AUTO = 12;//汽车
    const NEWS_GAME = 13;// 游戏
    
    //聚类动作 CLUSTER
    const CLUSTER_PUSH = "push";  //提交
    const CLUSTER_ANALYSIS = "analysis"; //分析
    const CLUSTER_STATUS = "status"; //状态
    const CLUSTER_RESULT = "result"; //结果
    const CLUSTER_CLEAR = "clear"; //清除

    //摘要字数模式
    const SUMMARY_EXCEED = 1; //严格
    const SUMMARY_NOT_EXCEED = 0; //不严格

    //情感参数
    //0~0.5之间判断为负面，
    //0.5~1之间判断为正面。

    /*
     * 聚类分析 参数 analysis
        alpha	(0, 1]	0.8	调节聚类最大cluster大小
        beta	(0, 1)	0.45	调节聚类平均cluster大小
    */
    const CLUSTER_DEFAULT_ALPHA = 0.8;
    const CLUSTER_DEFAULT_BETA = 0.45;

    private $apiKey = "";     //apikey  
    private $compress = true; //是否压缩
    private $throwEx = false; //是否抛出异常
    private $opt = [
        "http" => [
            "method" => "",
            "header" => "",
            "content" => ""
        ]
    ];

    //构建请求参数
    private function buildParm($data) {
        if (is_array($data)) {
            $this->opt["http"]["content"] = json_encode($data,JSON_UNESCAPED_UNICODE);
        } else if (is_string($data)) {
            if (substr($data, 0, 1) == "{") {
                $this->opt["http"]["content"] = $data;
            } else {
                $this->opt["http"]["content"] = "\"$data\"";
            }
        }
        if(strlen($this->opt["http"]["content"]) > 10 * 1024 && $this->compress){
            $this->opt["http"]["header"] .= "\r\n" . "Content-Encoding:gzip";
            $this->opt["http"]["content"] = gzencode($this->opt["http"]["content"]);
        }
    }

    //请求
    private function http_query($url, $data, $method = "POST") {
        $this->buildParm($data);
        $this->opt["http"]["method"] = $method;
        $context = stream_context_create($this->opt);
        $rep = @file_get_contents($url, false, $context);

        if (!$rep) {
            if($this->throwEx){ 
                throw new \Exception("Problem with $url, $php_errormsg"); 
            }
            else{
                return false;
            }
        } else {
            $header = $http_response_header;
            return ["header" => $header, "response" => $rep];
        }
    }
    //聚类参数
    private function makeClusterParam($strs, $startId = 1) {
        if (is_string($strs)) {
            $strs = [$strs];
        }
        $texts = [];
        foreach ($strs as $idx => $str) {
            $text = ["_id" => $idx + $startId, "text" => $str];
            $texts[] = $text;
        }
        return $texts;
    }
    /**
     * 
     * @param type $_apiKey 
     * @param type $_compress 是否压缩
     * @param type $_throwEx 是否抛出异常
     */
    public function __construct($_apiKey,$_compress=true,$_throwEx= false) {
        $this->apiKey = $_apiKey;
        $this->compress = $_compress;
        $this->throwEx = $_throwEx;
        $this->opt["http"]["header"] = "Content-Type:application/json\r\nAccept:application/json\r\nX-Token:{$this->apiKey}\r\n";
    }

    //移除英文标点
    public static function removePunct($str) {
        $pattern = array(
            "/[[:punct:]]/i", //英文标点符号
            '/[ ]{2,}/'
        );
        $str = preg_replace($pattern, ' ', $str);
        return $str;
    }
    
    //情感分析
    public function sentiment($data, $query = null) {
        $path = "/sentiment/analysis";
        if ($query) {
            $path .= "?" . $query;
        }
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //命名实体识别
    public function ner($data,$sensitivity=3) {
        $path = "/ner/analysis";
        $path .= "?sensitivity=$sensitivity";
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //依存文法分析
    public function depparser($data) {
        $path = "/depparser/analysis";
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //关键词提取
    public function keywords($data,$top_k=100,$segmented=0) {
        $path = "/keywords/analysis";
        if ($segmented){
            $path .= "?".http_build_query(compact("top_k","segmented"));
        }else{
            $path .= "?"."top_k=$top_k";
        }

        return $this->http_query(self::API_HOST . $path, $data);
    }

    //新闻分类
    public function classify($data) {
        $path = "/classify/analysis";
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //语义联想
    public function suggest($data, $top = null) {
        $path = "/suggest/analysis";
        if ($top) {
            $path .= "?top_k=" . $top;
        }
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //分词与词性标注
    public function tag($data, $query = null) {
        $path = "/tag/analysis";
        if ($query) {
            $path .= "?" . $query;
        }
        return $this->http_query(self::API_HOST . $path, $data);
    }

    //新闻摘要
    public function summary($content,$title="",$percentage=0.3,$not_exceed=0) {
        $path = "/summary/analysis";
        $title = isset($title) ? $title : "";
        $percentage = is_numeric($percentage) ? $percentage : 0.3;
        $not_exceed = is_numeric($not_exceed) ? $not_exceed : 0;
        $data = compact("content","title","percentage","not_exceed");

        return $this->http_query(self::API_HOST . $path, $data);
    }

    //时间转换
    public function time($data, $query = null) {
        $path = "/time/analysis?pattern=$data";
        if ($query) {
            $path .= $query;
        }
        return $this->http_query(self::API_HOST . $path, "");
    }


    //文本聚类引擎
    public function cluster($data, $action, $taskId, $alpha = null, $beta = null) {
        $path = "/cluster/$action/$taskId";

        if ($action != self::CLUSTER_PUSH) {
            $method = "GET";
        } else {
            $method = "POST";
        }
        if ($action == self::CLUSTER_ANALYSIS) {
            $alpha = isset($alpha) ? $alpha : self::CLUSTER_DEFAULT_ALPHA;
            $beta = isset($beta) ? $beta : self::CLUSTER_DEFAULT_BETA;
            $path .= "?" . http_build_query(["alpha" => $alpha, "beta" => $beta]);
        }
        $data = $this->makeClusterParam($data);
        return $this->http_query(self::API_HOST . $path, $data, $method);
    }

    //典型意见引擎
    public function comments($data, $action, $taskId, $alpha = null, $beta = null) {
        $path = "/cluster/$action/$taskId";

        if ($action != self::CLUSTER_PUSH) {
            $method = "GET";
        } else {
            $method = "POST";
        }
        if ($action == self::CLUSTER_ANALYSIS) {
            $alpha = isset($alpha) ? $alpha : self::CLUSTER_DEFAULT_ALPHA;
            $beta = isset($beta) ? $beta : self::CLUSTER_DEFAULT_BETA;
            $path .= "?" . http_build_query(["alpha" => $alpha, "beta" => $beta]);
        }
        $data = $this->makeClusterParam($data);
        return $this->http_query(self::API_HOST . $path, $data, $method);
    }

    //API 频率限制
    public function rate_limit_status() {
        $path = "/application/rate_limit_status.json";
        // 查询API 频率限制
        return $this->http_query(self::API_HOST . $path, "");
    }

}