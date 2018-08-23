<?php
/**
 * Created by PhpStorm.
 * User: 4iteam
 * Date: 05-Jun-18
 * Time: 4:17 PM
 */

namespace App\Api\Controllers;


use App\Api\Services\LinksVipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;

class LinksVipController extends Controller
{
    protected $linksvip;
    function __construct(LinksVipService $service)
    {
        $this->middleware('auth.api');
        $this->linksvip=$service;

    }
    function index(Request $request){
        if($request->input('do_login')){
            $this->linksvip->doLogin();
            return redirect()->to('v1/links_vip');
        }
        $this->linksvip->maybeLogin();
        $check=$this->linksvip->checkLogin();
        if(empty($check)){
            return 'Chưa đăng nhập, có thể do thông tin tài khoản không chính xác hãy cập nhật thông tin tài khoản và nhấp vào liên kết bên dưới để thử lại.<br>
            <a href="?do_login=1">Thử lại ngay</a>

';

        }
        return $check;
    }
    function getLink(Request $request){
        $link=$request->input('link');
        $result=$this->linksvip->getLink($request->input('link'),$request->input('pass'));
        $result=new Fluent($result);
        if($result['request']['link']===$link){
            $result['status']=$result['trangthai'];
            $result['error']='';
            $result['message']=$result['loi'];
            $result['html']=$result['result'];
            unset($result['result']);
            unset($result['loi']);
            unset($result['trangthai']);
            unset($result['request']);
            if($result['status']==0){
                $result['error']='server';
            }

            return $result;
        }else{
            return ['status'=>0,'message'=>'Máy chủ bận, hãy thử lại sau vài phút!','error'=>'wait'];
        }
    }
}