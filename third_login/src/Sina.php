<?php
/**
 * Class Sina
 * Created by PhpStorm.
 * User: mjay
 * Date: 2017/11/27
 * Time: 下午5:32
 * @package mjay\third_login
 * @author mjay
 * @version 1.0
 * @uses
 *     $option = [
 *         'app_id' => 'xxxxxxxx',
 *         'app_secret' => 'xxxxxxxx',
 *         'callback' => 'xxxxxxxx'
 *    ]
 *    $sina = new Sina($option);
 *    $sina->getAuth();//获取新浪跳转链接
 * 新浪开放平台登录
 * @todo 修改名字为auth
 */
namespace mjay\third_login;

use extensions\OAuthException;
use extensions\SaeTClientV2;
use extensions\SaeTOAuthV2;
class Sina
{
    private $app_id;
    private $app_secret;
    private $callback;
    private $access_token;

    private $sina;
    public function __construct($params)
    {
        $this->app_id = $params['app_id'];
        $this->app_secret = $params['app_secret'];
        $this->callback = $params['callback'];

        $this->sina = new SaeTOAuthV2($this->app_id, $this->app_secret);
    }

    /**
     * @return array
     * 获取授权链接
     */
    public function getAuth($code = 'code', $state = NULL)
    {
        $url = $this->sina->getAuthorizeURL($this->callback, $code, $state);
        return $url;
    }

    /**
     * @return array|bool|mixed
     * @throws OAuthException
     * 获取access_token
     */
    public function getAccessTocken()
    {
        $code = isset($_GET['code']) && $_GET['code'] ? _GET['code'] : '';
        if (!$code) return false;
        ini_set('arg_separator.output','&');
        if ($this->access_token) return $this->access_token;
        $token = $this->sina->getAccessToken($code);
        if ($token) {
            $this->access_token = $token;
        }
        return $this->access_token;
    }

    /**
     * @return array|bool
     * @throws OAuthException
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $this->getAccessTocken();
        $sinaApi = new SaeTClientV2($this->app_id, $this->app_secret, $this->access_token['access_token']);
        $user_info = $sinaApi->show_user_by_id($this->access_token['openid']);
        if(isset($user_info['error'])) return false;
        return $user_info;
    }

}