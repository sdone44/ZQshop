<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;


class UserShop extends Model
{
    //
    public function shop_areas()
    {
        return $this->belongsTo(ShopArea::class,'area_id');
    }

    public function user_shops_shop_goods()
    {
        return $this->hasMany(UserShopsShopGoods::class,'shop_id','id');
    }


    public static function shop_list()
    {
        $resulte = static::where([
            ['state','=',1],
            ['shop_type','<>',3],
        ])->get()->toArray();
        return $resulte;
    }

    // 多图上传处理
    public function getShopPictureAttribute($pictures)
    {
        if (is_string($pictures)) {
            return json_decode($pictures, true);
        }

        return $pictures;
    }

    public function setShopPictureAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['shop_picture'] = json_encode($pictures);
        }
    }

    //根据distrcit返回推荐商家
    public static function getShopList($areaId = 0)
    {
        $shopList = static::where([
            ['area_id','like','%' .','. $areaId .','. '%'],
            ['state','=','1'], //运营中的
          	['shop_type','<>',3],//不能是团购店
        ])->get()->toArray();
        if($shopList){
            return $shopList;
        }else{
            return null;
        }
        
    }
    //根据distrcit返回商家id集
    public static function getShopIdsByDistrict($district)
    {
        $shopIds = static::where([
            ['district','=',$district],
            ['state','=','1'], //运营中的
          	['shop_type','<>',3],//不能是团购店
        ])->pluck('id')->toArray();
        if($shopIds){
            return $shopIds;
        }else{
            return null;
        }
        
    }

    //根据商铺id返回商家信息
    public static function getShopInfoById($id)
    {
        $shopInfo = static::where([
            ['id','=',$id] //运营中的
        ])->get()->toArray();
        if($shopInfo){
            return $shopInfo[0];
        }else{
            return null;
        }
        
    }

    //后台gird根据商铺id返回商家名
    public static function getShopNameById($id)
    {
        $shopInfo = static::find($id);
        if($shopInfo){
            return $shopInfo->shop_name;
        }else{
            return null;
        }
        
    }

    //商家多选服务点
    public function getAreaIdAttribute($areaId)
    {
        return explode(',', $areaId);
    }

    public function setAreaIdAttribute($areaId)
    {
        $this->attributes['area_id'] = ','.implode(',', $areaId) .',';
    }
}
