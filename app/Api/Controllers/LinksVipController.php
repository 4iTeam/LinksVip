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
        $check['hash']=$this->linksvip->hash(32);
        $check['hash'].='|'.strlen($check['hash']);
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
            unset($result['loi']);
            unset($result['trangthai']);
            unset($result['request']);
            if($result['status']==0){
                $result['error']='server';
            }
            return $result;
        }else{
            return ['status'=>0,'message'=>'Pin yếu, đang xạc pin nhé!','error'=>'wait'];
        }
    }
}