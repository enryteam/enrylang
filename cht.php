<?php
header('Access-Control-Allow-Origin:*');
/**
 * index.php 入口
 */
define('APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('PHPFRAME_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

include PHPFRAME_PATH . '/phpframe/base.php';
error_reporting(0);//DEBUG: E_ALL/0
//error_reporting(E_ALL);//DEBUG: E_ALL/0
///////////////////////////////////三方翻译//////START////////////////////////////////////////////////
define("BAIDUTRAN_CBAIDUTRAN_URL_TIMEOUT",   5);
define("BAIDUTRAN_URL",            "http://api.fanyi.baidu.com/api/trans/vip/translate");
define("BAIDUTRAN_APP_ID",         "20180417000147254"); //替换为您的APPID
define("BAIDUTRAN_SEC_KEY",        "gN4Flq8M7fnOkDYOUauC");//替换为您的密钥
function translate($query, $from, $to)
{
    $args = array(
        'q' => $query,
        'appid' => BAIDUTRAN_APP_ID,
        'salt' => rand(10000,99999),
        'from' => $from,
        'to' => $to,

    );
    $args['sign'] = buildSign($query, BAIDUTRAN_APP_ID, $args['salt'], BAIDUTRAN_SEC_KEY);
    $ret = call(BAIDUTRAN_URL, $args);
    $ret = json_decode($ret, true);
    return $ret;
}
function buildSign($query, $appID, $salt, $secKey)
{
    $str = $appID . $query . $salt . $secKey;
    $ret = md5($str);
    return $ret;
}
function call($url, $args=null, $method="post", $testflag = 0, $timeout = BAIDUTRAN_CBAIDUTRAN_URL_TIMEOUT, $headers=array())
{
    $ret = false;
    $i = 0;
    while($ret === false)
    {
        if($i > 1)
            break;
        if($i > 0)
        {
            sleep(1);
        }
        $ret = callOnce($url, $args, $method, false, $timeout, $headers);
        $i++;
    }
    return $ret;
}

function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = BAIDUTRAN_CBAIDUTRAN_URL_TIMEOUT, $headers=array())
{
    $ch = curl_init();
    if($method == "post")
    {
        $data = convert($args);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    else
    {
        $data = convert($args);
        if($data)
        {
            if(stripos($url, "?") > 0)
            {
                $url .= "&$data";
            }
            else
            {
                $url .= "?$data";
            }
        }
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if(!empty($headers))
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if($withCookie)
    {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
    }
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

function convert(&$args)
{
    $data = '';
    if (is_array($args))
    {
        foreach ($args as $key=>$val)
        {
            if (is_array($val))
            {
                foreach ($val as $k=>$v)
                {
                    $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                }
            }
            else
            {
                $data .="$key=".rawurlencode($val)."&";
            }
        }
        return trim($data, "&");
    }
    return $args;
}
function unhtml($content){                             //定义自定义函数的名称
    $content = preg_replace ('/\n/is', ' ', $content);
    //$content = str_ireplace(chr(13),'',$content);
    $content = str_ireplace('<','&lt;',$content);
    $content = str_ireplace('>','&gt;',$content);
    //$content=htmlspecialchars($content);                //转换文本中的特殊字符
    // $content=str_ireplace(chr(13),"<br>",$content);       //替换文本中的换行符
    // $content=str_ireplace(chr(32)," ",$content);       //替换文本中的
    //$content=str_ireplace("[_[","<",$content);           //替换文本中的小于号
    // $content=str_ireplace(")_)",">",$content);           //替换文本中的大于号
    // $content=str_ireplace("|_|"," ",$content);              //替换文本中的空格

    return $content;                              //删除文本中首尾的空格
}
function unhtml_decode($content,$routeindex){
    $content = str_replace('& lt；','<',$content);
    $content = str_replace('& gt；','>',$content);
    $content = str_replace('&lt；','<',$content);
    $content = str_replace('&gt；','>',$content);
    $content = str_replace('“','"',$content);
    $content = str_replace('”','"',$content);
    $content = str_replace('&lt;','<',$content);
    $content = str_replace('&gt;','>',$content);
    $content = str_replace('<！--','<!--',$content);
    $content = str_replace('（','(',$content);
    $content = str_replace('）',')',$content);
    $content = str_replace('；',';',$content);
    $content = str_ireplace('？','?',$content);
    $content = str_replace('portal/index.php','portal/'.$routeindex.'.php',$content);


    return $content;                              //删除文本中首尾的空格
}
////语言缓存
$routeindex = strtolower(str_replace('.php','',str_replace('/portal/','',$_SERVER['PHP_SELF'])));
if(in_array($routeindex,array('en','jp','kor','cht')))
{
  ///支持语言代码 http://api.fanyi.baidu.com/api/trans/product/apidoc#languageList
  ob_start();
  pc_base::creat_app();
  $ob_str = ob_get_contents();

  $contentArr = str_split($ob_str,5000);//PS:低于6000翻译更精准
  file_put_contents($routeindex."-enry-".md5($_SERVER['QUERY_STRING']).".php",'<?php header("Content-type: text/html; charset=utf-8");?>');
  foreach ($contentArr as $content) {
    $bodyhtml = translate(unhtml($content), 'zh', $routeindex);//PS:母语简体中文
    file_put_contents($routeindex."-enry-".md5($_SERVER['QUERY_STRING']).".php",unhtml_decode($bodyhtml['trans_result'][0]['dst'],$routeindex),FILE_APPEND);
  }
  header('Location:'.$routeindex."-enry-".md5($_SERVER['QUERY_STRING']).".php".'');
  ob_close();
}
///////////////////////////////////三方翻译//////OVER////////////////////////////////////////////////
