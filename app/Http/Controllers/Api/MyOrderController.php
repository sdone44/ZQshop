<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopCart;
use App\Logic\AddressLogic;
use App\Logic\OrderLogic;
use App\Models\ShopOrder;

class MyOrderController extends ApiController
{


    // 订单列表
    public function orderList(Request $request)
    {
        // 先获取当前登录的用户信息
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
              	
            ],
            [
                'user_id.required' => '用户id参数缺失',

            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

      	$orderLogic = new OrderLogic();
        $user_id = $request->user_id;

        $where['uid'] = $user_id;
      
        if(!empty($request->statusTab)){
            $where['order_status'] = $request->statusTab;
        }
      
        $orderList = $orderLogic->getOrderList($where);

        return $this->success($orderList);
    }

    // 订单详情
    public function orderDetail(Request $request)
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

    // 取消订单
    public function orderCancel(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
                'user_id' => 'required',
            ],
            [
                'order_id.required' => '订单id参数缺失',
                'user_id.required' => '用户id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $where['uid'] = $request->user_id;
        $where['id'] = $request->order_id;
        $orderLogic = new OrderLogic();
        $re = $orderLogic->orderCancel($where);
        if($re){
            return $this->message('操作成功');
        }
    }
  
  	//用户取消已付款订单
    public function orderUserRefund(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
                'refund_desc' => 'required',

            ],
            [
                'order_id.required' => '订单id参数缺失',
                'refund_desc.required' => 'desc参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        
        $orderId = $request->order_id;
        $refundDesc = $request->refund_desc;
        $re = ShopOrder::where('id',$orderId)->update(['order_status'=>19,'refund_desc'=>$refundDesc]); //19用户申请取消付款订单

        if($re){
            return $this->success($re);
        }
        return $this->failed('取消处理失败',204);
    }

    // 物流详情
    public function orderExpress(Request $request)
    {
        // 先获取当前登录的用户信息
        if (empty(\Auth::user())) {
            return $this->failed('用户未登录', 401);
        }
        $user_id = \Auth::user()->id;
    }

}