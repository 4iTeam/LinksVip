<?php
namespace App\Api\Services;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LinksVipService{
    protected $user='ass247';//User linksvip ở đây...
    protected $pass='b';//Mật khẩu nữa...
    protected $loginEndpoint='';
    protected $getLinkEndpoint='https://linksvip.net/GetLinkFs';
    function __construct()
    {

    }
    function getClient(){
        if(!Storage::exists('cookies/linksvip.json')){
            Storage::put('cookies/linksvip.json','');
        }
        $file=Storage::path('cookies/linksvip.json');
        $cookie=new FileCookieJar($file,true);
        $client=new Client([
            'base_uri' => 'https://linksvip.net',
            'cookies'=>$cookie,
            'headers'=>[
                'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
            ],
        ]);
        return $client;
    }
    function getLink($link,$pass='undefined'){
        if(Cache::has('linksvip_catched_'.md5($link))){
            return Cache::get('linksvip_catched_'.md5($link));
        }
        if(Cache::has('linksvip_catched_'.md5($link.$pass))){
            return Cache::get('linksvip_catched_'.md5($link.$pass));
        }
        return Cache::remember('linksvip_get_link',0.2,function()use($link,$pass){//Giới hạn thời gian 0.2 phút (12 giây) 1 link
            $result= $this->_getLink($link,$pass);
            if(!empty($result['linkvip'])){//we found a link cache it
                Cache::put('linksvip_catched_'.md5($link),$result,24*60);//Link đã được get sẽ được lưu trong 24 giờ
                Cache::put('linksvip_catched_'.md5($link.$pass),$result,24*60);//Link đã được get sẽ được lưu trong 24 giờ
            }
            return $result;
        });
    }
    protected function _getLink($link,$pass='undefined'){
        if(!$pass){
            $pass='undefined';
        }
        $data=[
            'link'=>$link,
            'pass'=>$pass,
            'hash'=>$this->hash(32),
            'captcha'=>'',
        ];
        $result=['trangthai'=>0];
        try {
            $response = $this->getClient()->post($this->getLinkEndpoint, [
                'form_params' => $data,
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Origin' => 'https://linksvip.net',
                    'Referer' => 'https://linksvip.net',
                ],
                //'debug'=>'true',
            ]);
            $result = json_decode($response->getBody(), true);
        }catch (TransferException $e){
            $result['loi']='Lỗi kết nối';
        }

        $result['request']=$data;
        return $result;
    }
    function hash($length=32){
        return str_random($length);
    }
    function maybeLogin(){
        if(!$this->checkLogin()){
            $this->doLogin();
        }
    }
    function doLogin(){
        if(!$this->user || !$this->pass){
            return ;
        }
        $client=$this->getClient();
        $client->post('https://linksvip.net/login/',[
            'form_params'=>[
                'u'=>$this->user,
                'p'=>$this->pass,
                'auto_login'=>'checked',
            ],

        ]);
        Cache::delete('linksvip_userinfo');
    }
    function checkLogin(){
        $user=$this->getUserInfo();
        return !empty($user['logged_in']);
    }
    function getUserInfo(){
        return Cache::remember('linksvip_userinfo',60*6,function(){
            return $this->_getUserInfo();
        });

    }
    protected function _getUserInfo(){
        $client=$this->getClient();
        $response=[];
        if(!$this->user||!$this->pass){
            $response['error']='Bạn chưa cấu hình thông tin tài khoản hãy mở file '.__FILE__.' để cấu hình';
            return $response;
        }
        try {
            $res = $client->get('https://linksvip.net/login/logined.php');
            $body = $res->getBody()->getContents();
            $lines=explode("\n",$body);

            if(strpos($body,$this->user)){
                $response['logged_in']=true;
                if(preg_match('#Mã tài khoản &nbsp;&nbsp;&nbsp;&nbsp;<b>(.*?)</b>#',$body,$matches)){
                    $response['id']=$matches[1];
                }
                if(preg_match('#<span id="user">(.*?)</span>#',$body,$matches)){
                    $response['name']=$matches[1];
                }
                if($accountType=$this->_findLine($lines,'Loại tài khoản')) {

                    $accountType = Str::lower($accountType);
                    if (str_contains($accountType, 'vip')) {
                        $response['type'] = 'VIP';
                    } elseif (str_contains($accountType, 'free')) {
                        $response['type'] = 'Free';
                    } elseif (str_contains($accountType, 'premium')) {
                        $response['type'] = 'Premium';
                    } else {
                        $response['type'] = 'Unknown';
                    }
                }else{
                    $response['type'] = 'Not found';
                }
                $expiredAt=$this->_findLine($lines,'Hạn dùng');
                if($expiredAt){
                    if(preg_match('#Hạn dùng <span class="badge"[^>]*>(.*?)</span>#',$expiredAt,$matches)){
                        $response['expire_at']=$matches[1];
                    }

                }
            }else{
                $response['error']='Không thể đăng nhập hãy kiểm tra thông tin tài khoản ở '.__FILE__.'';
            }

        }catch (TransferException $exception){

        }
        return $response;
    }
    protected function _findLine($lines,$needle){
        if(!is_array($lines)) {
            $lines = explode("\n", $lines);
        }
        foreach ($lines as $line){
            if(mb_strpos($line,$needle)!==false){
                return $line;
            }
        }
        return '';
    }

}