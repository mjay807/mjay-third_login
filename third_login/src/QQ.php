<?php
/**
 * Created by PhpStorm.
 * User: mjay
 * Date: 2017/11/27
 * Time: 下午5:32
 */
namespace mjay807\third_login;

class QQ {

    private $app_id;
    private $app_secret;
    private $callback;
    private $access_token;
    private $open_id;

    const API_URL_PREFIX = 'https://graph.qq.com';

    const Auth = '/oauth2.0/authorize?';
    const OAUTH_TOKEN_URL = '/oauth2.0/token?';
    const GET_USER_INFO = '/user/get_user_info?';
    const GET_OPEN_ID = '/oauth2.0/me?';

    public function __construct($param)
    {
        $this->app_id = $param['app_id'];
        $this->app_secret = $param['app_secret'];
        $this->callback = $param['callback'];

    }

    /**
     * @param string $state
     * @param string $scope
     * @return string
     * 跳转url链接
     */
    public function getAuthUrl($state = 'STATE', $scope = 'get_user_info')
    {
        return self::API_URL_PREFIX . self::Auth . 'response_type=code&client_id=' . $this->app_id . '&redirect_uri=' . urlencode($this->callback) . '&state=' . $state . '&scope=' . $scope . '&g_ut=1';
    }

    /**
     * @return array
     * 获取access_token
     */
    public function getAccessToken()
    {
        $code = isset($_GET['code']) && $_GET['code'] ? $_GET['code'] : '';
        if (!$code) return [0, '未获取到code'];
        $url = self::API_URL_PREFIX . self::OAUTH_TOKEN_URL . 'grant_type=authorization_code&client_id=' . $this->app_id . '&client_secret=' . $this->app_secret . '&code=' . $code . '&redirect_uri=' . $this->callback;
        $result = Curl::http_get($url);
        if (strpos($result, "callback") !== false)
        {
            $lpos = strpos($result, "(");
            $rpos = strrpos($result, ")");
            $response  = substr($result, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);
            if (isset($msg->error)) return [$msg->error, $msg->error_description];
        }
        if($result!==FALSE){
            $aTemp = explode("&", $result);
            $aParam = [];
            foreach($aTemp as $val){
                $aTemp2 = explode("=", $val);
                $aParam[$aTemp2[0]] = $aTemp2[1];
            }
            $this->access_token = $aParam["access_token"];
        }
        return $this->access_token;
    }

    /**
     * @return array
     * 获取用户openid
     */
    public function getOpenId()
    {
        $this->getAccessToken();
        $url = self::API_URL_PREFIX . self::GET_OPEN_ID . 'access_token=' . $this->access_token;
        $result = Curl::http_get($url);
        if (strpos($result, "callback") !== false)
        {
            $lpos = strpos($result, "(");
            $rpos = strrpos($result, ")");
            $response  = substr($result, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);
            if (isset($msg->error)) return [$msg->error, $msg->error_description];
        }
        if($result!==FALSE){
            $aTemp = array();
            preg_match('/callback\(\s+(.*?)\s+\)/i', $result, $aTemp);
            $aResult = json_decode($aTemp[1], true);
            $this->open_id = $aResult['openid'];
        }
        return $this->open_id;
    }

    /**
     * @return bool|mixed
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $this->getOpenId();
        //todo 获取用户信息
        $url = self::API_URL_PREFIX . self::GET_USER_INFO . 'access_token=' . $this->access_token . '&oauth_consumer_key=' . $this->app_id . '&openid=' . $this->open_id;
        $result = Curl::http_get($url);
        $response = json_decode($result, true);
        if (isset($response['ret']) && !$response['ret']){
            $response['openid'] = $this->open_id;
            return $response;
        }else{
            return false;
        }
    }
    




}