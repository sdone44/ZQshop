<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;

class ShopArea extends Model
{
    //
     use ModelTree, AdminBuilder;

     protected $table = "shop_areas";

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('parent_id');
        $this->setOrderColumn('sort_order');
        $this->setTitleColumn('area_name');

    }

    public function user_shop()
	{
	    return $this->hasMany(UserShop::class,'area_id');
	}

	public static function getAllClasses($noRoot = false)
    {
        $classes = static::all(['id', 'area_name']);

        $result = [];

        // if (!$noRoot) {
        //     $result = [
        //         0 => 'root'
        //     ];
        // } else {
        //     $result = [
        //         0 => 'null'
        //     ];
        // }
        foreach ($classes as $eachClass) {
            $result[$eachClass->id] = $eachClass->area_name;
        }

        return $result;
    }

    //后台获取代理区域名toGrid
    public static function getAreaName($areaIdArr)
    {
        $areaNameRes = '';
        // dd($areaIdArr);
        if(!empty($areaIdArr)){
            foreach ($areaIdArr as $k => $areaId) {
                // dd($areaId);
                // dd(static::find($areaId));
                $areaNameRes.= '<span class="label label-success">'.static::find($areaId)['area_name'] . '</span><br>';
            }
        }else{
            return null;
        }

        return $areaNameRes;
    }

    //后台form获取代理区域名
    public static function getAreaNameByAreaId($areaIdArr)
    {

        if($areaIdArr){
            return static::whereIn('id',$areaIdArr)->pluck('area_name','id') ? static::whereIn('id',$areaIdArr)->pluck('area_name','id')->toArray() : null;
        }

        return null;
    }
}
