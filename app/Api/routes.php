<?php
/**
 * @var \Laravel\Lumen\Routing\Router $this
 */
$this->group(['prefix'     => 'v1'],function(){
    $this->get('/',function(){
        return ['success'=>false];
    });

    $this->group(['prefix'=>'links_vip'],function(){
        $this->get('/','LinksVipController@index');
        $this->get('/get','LinksVipController@getLink');
    });


});