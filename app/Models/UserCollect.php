<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCollect extends Model
{
    //保存用户提交数据
    public static function saveUserInfo($userInfo)
    {
        $userInfoModel = new static;
        $userInfoModel->uid = $userInfo['uid'];
        $userInfoModel->user_name = $userInfo['user_name'];
        $userInfoModel->shop_name = '';
        $userInfoModel->shop_icon = '';
        $userInfoModel->shop_desc = '';
        $userInfoModel->bind_phone = $userInfo['bind_phone'];
        $userInfoModel->address = $userInfo['address'];
        $userInfoModel->code_pic = $userInfo['code_pic'];
        $userInfoModel->review_type = $userInfo['review_type'];

        return $userInfoModel->save();
    }

    //检测是否提交
    public static function checkCommit($userInfo)
    {
        $re = static::where([
            ['uid','=',$userInfo['uid']],
            ['review_status','=',0],
        ])->first();

        return $re;
    }

    //结果查询
    public static function userCollectRes($uid,$bind_phone)
    {
        $re = static::where([
        	['uid','=',$uid],
        	['bind_phone','=',$bind_phone]
        ])->first();

        if ($re) {
            $outDate['review_status'] = $re['review_status'];
            $outDate['review_type'] = $re['review_type'];
            if($re['review_status'] == 2){
                $outDate['reason'] = $re['reason'];
            }
            
        	return $outDate;
        }

        return 404;
    }
}
