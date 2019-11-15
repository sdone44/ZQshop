<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityAddress extends Model
{
	//后台获取城市 名
    public static function getCityAddress($id)
    {

        $result = static::where('id',$id)->get(['name'])[0]->toArray();
        // dd($result);
        return $result['name'];

    }
    //后台获取区县 名
    public static function getAddress($id)
    {

        if($id==0){
            $str = '智趣空间';

            return $str;
        }else{
            $result = static::where('id',$id)->get(['name'])[0]->toArray();
            // dd($result);
            return $result['name'];
        }       

    }

    //根据区名返回区id
    public static function getDistrictId($where)
    {

        $result = static::where($where)->get(['id'])->toArray();
        // dd($result);
        if ($result) {
        	return $result[0]['id'];
        }else{
        	return null;
        }

    }
}
