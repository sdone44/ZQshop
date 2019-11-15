<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ShopGoodsLogic;
use App\Logic\ShopCommentLogic;
use App\Models\ShopGoods;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopCategory;
use App\Models\UserShop;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\UserCollect;

class UserCollectController extends ApiController
{
    
    //用户申请数据提交
    public function UserCollectCommit(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'uid' => 'required',
                //'shop_name' => 'required',
                'user_name' => 'required',
                //'shop_icon' => 'required',
                //'shop_desc' => 'required',
                'bind_phone' => 'required',
                'address' => 'required',
                'code_pic' => 'required',
                'review_type' => 'required',

            ],
            [
                'uid.required' => 'uid参数缺失',
                'user_name.required' => 'user_name参数缺失',
                //'shop_name.required' => 'shop_name参数缺失',
                //'shop_icon.required' => 'shop_icon参数缺失',
                //'shop_desc.required' => 'shop_desc参数缺失',
                'bind_phone.required' => 'bind_phone参数缺失',
                'address.required' => 'address参数缺失',
                'code_pic.required' => 'code_pic参数缺失',
                'review_type.required' => 'review_type参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $userInfo['uid'] = $request->uid;
        //$userInfo['shop_name'] = $request->shop_name;
        $userInfo['user_name'] = $request->user_name;
        //$userInfo['shop_icon'] = $request->shop_icon;
        //$userInfo['shop_desc'] = $request->shop_desc;
        $userInfo['bind_phone'] = $request->bind_phone;
        $userInfo['address'] = $request->address;
        $userInfo['code_pic'] = $request->code_pic;
        $userInfo['review_type'] = $request->review_type;

        // $userInfo['uid'] = 1;
        // $userInfo['user_name'] = 0;
        // $userInfo['shop_name'] = 2;
        // $userInfo['shop_icon'] = 3;
        // $userInfo['shop_desc'] = 4;
        // $userInfo['bind_phone'] = 5;
        // $userInfo['address'] = 6;
        // $userInfo['code_pic'] = 7;

        $check = UserCollect::checkCommit($userInfo);

        if($check){
            return ['msg'=>'您已提交申请'];
        }        

        $re = UserCollect::saveUserInfo($userInfo);

        if($re){
            return ['msg'=>'提交成功'];
        }
        return ['msg'=>'提交失败'];
    }

    //结果查询
    public function UserCollectResult(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'uid' => 'required',
                'bind_phone' => 'required',
            ],
            [
                'uid.required' => 'uid参数缺失',
                'bind_phone.required' => 'bind_phone参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $uid = $request->uid;
        $bind_phone = $request->bind_phone;

        // $uid = 1;
        // $bind_phone = 5;

        $re = UserCollect::userCollectRes($uid,$bind_phone);

        if($re==404){
            return ['msg'=>'查询失败,内容为空'];
        }
        return $re;
    }


}