<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopGroup extends Model
{
    //进行中的团购商品
    public static function getUnderwayGroup(){

    	$now = date('Y-m-d H:i:s',time());
    	return static::where([
    		['start_time','<',$now],
    		['end_time','>',$now],
    		['review','=',1]
    	])->whereRaw('cur_num<max_num')->orderBy('created_at','desc')->get();
    }

    //进行中的推荐团购商品
    public static function getUnderwayHotGroup(){

    	$now = date('Y-m-d H:i:s',time());
    	return static::where([
    		['start_time','<',$now],
    		['end_time','>',$now],
    		['review','=',1],
    		['is_hot','=',1]
    	])->orderBy('created_at','desc')->get();
    }

}