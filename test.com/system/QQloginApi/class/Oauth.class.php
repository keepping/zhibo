<?php
/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */

require_once(CLASS_PATH."Recorder.class.php");
require_once(CLASS_PATH."URL.class.php");
require_once(CLASS_PATH."ErrorCase.class.php");

class Oauth{

    const VERSION = "2.0";
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
    const GET_USER_INFO_URL = "https://graph.qq.com/user/get_user_info";

    protected $recorder;
    public $urlUtils;
    protected $error;
    

    function __construct(){
        $this->recorder = new Recorder();
        $this->urlUtils = new URL();
        $this->error = new ErrorCase();
    }

    public function qq_login($callback = null,$appid = null){
        if(!$appid) {
            $appid = $this->recorder->readInc("appid");
        }
        if(!$callback) {
            $callback = $this->recorder->readInc("callback");
        }

        $scope = $this->recorder->readInc("scope");

        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        $this->recorder->write('state',$state);

        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $appid,
            "redirect_uri" => urlencode($callback),
            "state" => $state,
            "scope" => $scope
        );

        $login_url =  $this->urlUtils->combineURL(self::GET_AUTH_CODE_URL, $keysArr);

        header("Location:$login_url");
    }

    public function qq_callback($callback = null,$appkey=null,$appid=null){
        $state = $this->recorder->read("state");

        //--------验证state防止CSRF攻击
        if($_GET['state'] != $state){
            $this->error->showError("30001");
        }
        if(!$appkey){
            $appkey= $this->recorder->readInc("appkey");
        }
        if(!$callback) {
            $callback = $this->recorder->readInc("callback");
        }
        if(!$appid){
            $appid=$this->recorder->readInc("appid");
        }
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $appid,
            "redirect_uri" => urlencode($callback),
            "client_secret" => $appkey,
            "code" => $_GET['code']
        );

        //------构造请求access_token的url
        $token_url = $this->urlUtils->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->urlUtils->get_contents($token_url);

        if(strpos($response, "callback") !== false){

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);

            if(isset($msg->error)){
                $this->error->showError($msg->error, $msg->error_description);
            }
        }

        $params = array();
        parse_str($response, $params);

        $this->recorder->write("access_token", $params["access_token"]);
        return $params["access_token"];

    }

    public function get_openid(){

        //-------请求参数列表
        $keysArr = array(
            "access_token" => $this->recorder->read("access_token")
        );

        $graph_url = $this->urlUtils->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->urlUtils->get_contents($graph_url);

        //--------检测错误是否发生
        if(strpos($response, "callback") !== false){

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($response);
        if(isset($user->error)){
            $this->error->showError($user->error, $user->error_description);
        }

        //------记录openid
        $this->recorder->write("openid", $user->openid);
        return $user->openid;

    }

    public function get_pc_user_info($access_token, $openid)
    {
        $appid = $this->recorder->readInc("appid");
        $use_info_keysArr = array(
            "access_token" => $access_token,
            "openid" => $openid,
            "oauth_consumer_key" => $appid,
            "format"=> "json",
        );
        $graph_use_info_url = $this->urlUtils->combineURL(self::GET_USER_INFO_URL, $use_info_keysArr);
        $response = $this->urlUtils->get_contents($graph_use_info_url);
        return json_decode($response, true);
    }
}
