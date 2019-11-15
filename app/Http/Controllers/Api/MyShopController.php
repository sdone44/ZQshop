<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ShopGoodsLogic;
use App\Logic\ShopCommentLogic;
use App\Models\ShopBrand;
use App\Models\ShopGoods;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopCategory;
use App\Models\UserShop;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\ShopOrder;
use App\User;

use App\Models\Carousel;

class MyShopController extends ApiController
{
    
    //我的商铺列表
    public function myShop(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'bind_phone' => 'required',
            ],
            [
                'bind_phone.required' => '手机号缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $bindPhone = $request->bind_phone;
        $userModel = User::where('phone',$bindPhone)->first();
        if($userModel->role!=1){
            return ['msg'=>'非商家！'];
        }

        $myShopInfo = UserShop::where('bind_phone',$bindPhone)->first();
        if(empty($myShopInfo)){
            return ['msg'=>'您不是商家！'];
        }

        $myShopData = ShopOrder::getShopDateById($myShopInfo->id);
        $myShopInfoArr = $myShopInfo->toArray();

        $shopRes = array_merge($myShopInfoArr,$myShopData);
        
        return $this->success($shopRes);
    }

    //我的商铺订单
    public function myShopOrder(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
            ],
            [
                'shop_id.required' => '商铺id缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $shopId = $request->shop_id;
        $where['shop_id'] = $shopId;
        
      	//if($request->input('order_status')==22){
        //    ShopOrder::readNewOrder($shopId);
        //}
      
        if($request->input('order_status')){
            $where['order_status'] = $request->input('order_status');
        }

        $shopOrderList = ShopOrder::where($where)->orderBy('created_at','desc')->paginate(10)->toArray();
        // dd($shopOrderList);
        if(empty($shopOrderList)){
            return ['msg'=>'暂无订单'];
        }
        return $this->success($shopOrderList);
    }
  
  	//订单详情
    public function myShopOrderDetail(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
            ],
            [
                'order_id.required' => '订单id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $orderLogic = new OrderLogic();
        $where['id'] = $request->order_id;
        $orderInfo = $orderLogic->getOrderDetail($where);
        return $this->success($orderInfo);
    }
  	
  	//商家接单
    public function myShopOrderReceived(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
            ],
            [
                'order_id.required' => '订单id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $orderId = $request->order_id;
        $re = ShopOrder::where('id',$orderId)->update(['order_status'=>32]);

        if($re){
          	//订单已读
            ShopOrder::where('id',$orderId)->update(['is_new'=>0]);
            return $this->success($re);
        }
        return $this->failed('接单处理失败',204);
    }
  
  	//商家确认送达
    public function myShopOrderComplete(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
            ],
            [
                'order_id.required' => '订单id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $orderId = $request->order_id;
        $re = ShopOrder::where('id',$orderId)->update(['order_status'=>40]);

        if($re){
            return $this->success($re);
        }
        return $this->failed('送达完成处理失败',204);
    }

  	//提醒商家新订单消息
  	public function notifyOrder(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
            ],
            [
                'shop_id.required' => '商铺id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $shopId = $request->shop_id;

        $re = ShopOrder::isNewOrder($shopId);
        return $re;
        
    }
  
  	//我的商铺营业设置
    public function myShopOpenSetting(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
                'state' => 'required',
                'business_start_time' => 'required',
                'business_end_time' => 'required',
            ],
            [
                'shop_id.required' => 'shop_id参数缺失',
                'state.required' => 'state参数缺失',
                'business_start_time.required' => 'business_start_time参数缺失',
                'business_end_time.required' => 'business_end_time参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $shopId = $request->shop_id;
        $state = $request->state;
        $business_start_time = $request->business_start_time;
        $business_end_time = $request->business_end_time;

        $myShop = UserShop::find($shopId);
        $myShop->state = $state;
        $myShop->business_start_time = $business_start_time;
        $myShop->business_end_time = $business_end_time;

        $re = $myShop->save();
        if($re){
            return ['msg'=>'设置成功','code'=>1];
        }
        return $this->failed('设置失败',204);
    }

}