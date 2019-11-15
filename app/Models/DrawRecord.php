<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawRecord extends Model
{
    //
    public static function getDrawRecordArrByShopId($shopId)
    {
        $DrawRecord = static::where('shop_id',$shopId)->orderBy('created_at','desc')->get();
        if(count($DrawRecord)==0){
        	return [];
        }
        $DrawRecordArr = [];
        foreach ($DrawRecord as $k => $v ){
        	$DrawRecordArr[][] = $v->created_at . ' 提现：' . $v->withdrawals . '￥';
        }
        return $DrawRecordArr;
    }
}
