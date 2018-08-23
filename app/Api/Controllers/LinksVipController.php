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
    function index(){
        $this->linksvip->maybeLogin();
        $check=$this->linksvip->checkLogin();
        if(empty($check)||empty($check['logged_in'])){
            return 'Chưa đăng nhập';
        }else{
            return 'Đã đăng nhập';
        }
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