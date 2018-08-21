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
    protected $user='';//User linksvip ở đây...
    protected $pass='';//Mật khẩu nữa...
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
            $client=$this->getClient();
            $client->post('https://linksvip.net/login/',[
                'form_params'=>[
                'u'=>$this->user,
                'p'=>$this->pass,
                'auto_login'=>'checked',
                ],

            ]);
            Cache::delete('linksvip_is_logged_in');
        }
    }
    function checkLogin(){
        return Cache::remember('linksvip_is_logged_in',60*6,function(){
            $client=$this->getClient();
            $response=[];
            try {
                $res = $client->get('https://linksvip.net/login/logined.php');
                $body = $res->getBody();

                if(strpos($body,$this->user)){
                    $response['logged_in']=true;
                }

            }catch (TransferException $exception){

            }
            return $response;
        });
    }

}