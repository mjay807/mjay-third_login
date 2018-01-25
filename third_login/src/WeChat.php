<?php
/**
 * Created by PhpStorm.
 * User: mjay
 * Date: 2018/1/25
 * Time: 下午4:32
 */
namespace mjay807\third_login;

class WeChat {

    private $app_id;//appid
    private $app_secret;//appsecret
    private $callback;//回调
    private $access_token;
    private $open_id;

    //基础API链接
    const API_URL_PREFIX = 'https://api.weixin.qq.com';
    const GET_CODE = 'https://open.weixin.qq.com/connect/qrconnect?';

    const GET_ACCESS_TOKEN = '/sns/oauth2/access_token?';
    const REFRESH_TOKEN = '/sns/oauth2/refresh_token?';
    const GET_AUTH = '/sns/auth?';
    const GET_USERINFO = '/sns/userinfo?';


    public function __construct($params)
    {
        $this->app_id = $params['app_id'];
        $this->app_secret = $params['app_secret'];
        $this->callback = $params['callback'];
    }

    /**
     * @param string $state
     * @return string
     * 返回获取code链接
     */
    public function authUrl($state = 'STATE')
    {
        return self::GET_CODE . 'appid=' . $this->app_id . '&redirect=' . urlencode($this->callback) . '&response_type=code&scope=snsapi_login&state=' . $state . '#wechat_redirect';
    }

    /**
     * @return string
     * 获取access_token
     */
    public function getAccessToken()
    {
        $code = isset($_GET['code']) && $_GET['code'] ? $_GET['code'] : '';
        if (!$code) header('location', $this->authUrl());
        $url = self::API_URL_PREFIX . self::GET_ACCESS_TOKEN . 'appid=' . $this->app_id . '&appsecret=' . $this->app_secret . '&code=' . $code . '&grant_type=authorization_code';
        $result = Curl::http_get($url);
        $json = json_decode($result, true);
        if (isset($json['access_token']) && $json['access_token']) $this->access_token = $json['access_token'];
        if (isset($json['openid']) && $json['openid']) $this->open_id = $json['openid'];
        return $this->access_token;
    }

    /**
     * @return mixed
     * 获取用户信息
     */
    public function getUserInfo()
    {
        if (!$this->access_token || !$this->open_id) $this->getAccessToken();
        $url = self::API_URL_PREFIX . self::GET_USERINFO . 'access_token=' . $this->access_token . '&openid=' . $this->open_id;
        $result = Curl::http_get($url);
        $json = json_decode($result, true);
        if (isset($json['errcode']) && $json['errcode'] == '40003') {
            $this->getAccessToken();
            $this->getUserInfo();
        }
        return $json;
    }

}