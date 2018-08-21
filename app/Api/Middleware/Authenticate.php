<?php
/**
 * Created by PhpStorm.
 * User: 4iteam
 * Date: 20-Aug-18
 * Time: 1:04 PM
 */

namespace App\Api\Middleware;


use Illuminate\Http\Request;

class Authenticate
{
    protected $apiKey='';
    function handle(Request $request,\Closure $next){
        if($this->apiKey) {
            if ($request->input('apiKey') !== $this->apiKey) {
                return ['status' => 0];
            }
        }
        return $next($request);
    }
}