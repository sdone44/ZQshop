<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Logic\CartLogic;
use App\Models\ShopCart;
use App\Models\ShopOrder;
use App\Models\UserShop;
use App\Models\UserShopsShopGoods;
use App\Logic\AddressLogic;
use App\Logic\ShopCouponLogic;
use App\Logic\Buy;
use App\Http\Resources\ShopMyCoupon;
use EasyWeChat\Factory;
use Delayer\Client;
use Delayer\message;

class ShopOrderController extends ApiController
{


    // 校验购物车商品
    public function checkout(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'address_id' => 'required',
                'user_id' => 'required',
                'shop_id'=>'required',
            ],
            [
                'address_id.required' => '参数缺失',
                'user_id.required' => '用户id参数缺失',
                'shop_id.required' => '商铺id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $shop_id = $request->shop_id;
        $addressId = $request->address_id;
        $user_id = $request->user_id;

        $where = [
            ['uid','=',$user_id],
            ['checked','=',1]
        ]; 


        $cartList = CartLogic::getCartList($where);
        if(empty($cartList)){
            return ['msg'=>'购物车为空','error'=>$cartList];
        }
        $checkedShopOrder = [];
        foreach ($cartList as $shopId => $orderinfo) {
            if($shopId==$shop_id){      
                $checkedShopOrder = $orderinfo;
            }
        }
        if(empty($checkedShopOrder)){
            return ['msg'=>'该商家订单异常','code'=>201];
        }
      
      	/**
         * 检测商品状态（1、禁止对下架商品进行结算。2、禁止对商家删除的商品进行结算）
         */
        
        foreach ($checkedShopOrder['cartList'] as $k => $goodsInfo) {
            if($goodsInfo['is_on_sale'] == 0){  //第1步
                return ['msg'=>'存在下架商品','goods'=>$goodsInfo];
            }
            $isExistGoods = UserShopsShopGoods::where([
                ['shop_id','=',$shop_id],
                ['good_id','=',$goodsInfo['goods_id']],
            ])->first();
            if(!$isExistGoods){   //第2步
                return ['msg'=>'商家下架商品','goods'=>$goodsInfo];
            }
        }
        // dd($checkedShopOrder);
        $outData['cartListInfo'] = $checkedShopOrder;
        $outData['checkedAddress'] = AddressLogic::getOneAddr($addressId, $user_id); // 选择地址
        $shopModel = UserShop::find($shop_id);
        //起送价判断
        if($outData['cartListInfo']['TotalPrice']<$shopModel->began_price){
            return ['msg'=>'未达起送价'];
        }

        return $this->success($outData);
    }

    // 立即购买(只买一件)
    public function payNow(Request $request){
        if (empty(\Auth::user()->id)) {
            $this->user_id = 0;
        } else {
            $this->user_id = \Auth::user()->id;
        }
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'address_id' => 'required',
                'productId' => 'required',
                'goodsId' => 'required',
                'buynumber' => 'required',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $outData = CartLogic::getBuyGoodsById($request->goodsId,$request->buynumber,1,$request->productId);
        $couponInfo = ShopCouponLogic::checkedCoupon($this->user_id,$request->couponId,$outData['goodsTotalPrice']);
        $outData['checkedAddress'] = AddressLogic::getOneAddr($request->address_id, $this->user_id); // 选择地址
        $outData['checkedCoupon'] = $couponInfo['checkedCoupon']; // 选择的优惠券
        $outData['couponList'] = ShopMyCoupon::collection(ShopCouponLogic::getAvailableCouponListByGoodsPrice($outData['goodsTotalPrice'],$this->user_id)); //  优惠券列表
        $outData['couponPrice'] = $couponInfo['couponPrice']; // 选中的优惠金额
        $outData['actualPrice'] = PriceCalculate($outData['orderTotalPrice'], '-', $outData['couponPrice']); // 真实付款金额
        return $this->success($outData);
    }


    // 提交订单(用来生成订单)
    public function orderSubmit(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'address_id' => 'required',
                'user_id' => 'required',
                'shop_id'=>'required',
            ],
            [
                'address_id.required' => '参数缺失',
                'user_id.required' => '用户id参数缺失',
                'shop_id.required' => '商铺id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $shop_id = $request->shop_id;
        $addressId = $request->address_id;
        $user_id = $request->user_id;

        // $shop_id = 1;
        // $addressId = 5;
        // $user_id = 3;
        $where = [
            ['uid','=',$user_id],
            ['checked','=',1]
        ]; 
        
        // $orderData = CartLogic::getCheckedGoodsList($this->user_id);
        $cartList = CartLogic::getCartList($where);
        $orderData = [];
        foreach ($cartList as $shopId => $orderinfo) {
            if($shopId==$shop_id){      
                $orderData = $orderinfo;
            }
        }
        
        $checkedAddress = AddressLogic::getOneAddr($addressId, $user_id); // 选择地址
        if (empty($checkedAddress)) {
            return $this->failed('未查到用户收货地址，请检查您的收货地址', 401);
        }

        $orderData['actualPrice'] = $orderData['TotalPrice'];
        $orderData['postscript'] = $request->postscript??'暂无留言';
        $orderData['groupId'] = 0; //从商铺购买
        // dd($orderData);

        $buyModel = new Buy();
        $buyRe = $buyModel->buyStep($request, $orderData, $checkedAddress,$user_id);

        if (empty($buyRe['error'])) {
            return $this->success($buyRe);
        }
        return $this->failed($buyRe['error'], 403);
    }


    // 取消订单
    public function orderCancel(Request $request)
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

        $re = ShopOrder::cancelOrder($orderId);

        if ($re) {
            return ['msg'=>'取消成功'];
        }
        return $this->failed('取消失败', 403);
    }

    //发送模板消息
    public function sendMessage()
    {
        if($request->status){
            $status = '已下单';
        }
        $config = config('wechat.mini_program.default');
        $app = Factory::miniProgram($config);
        $app->template_message->send([
            'touser' => $request->open_id, //'user-openid',
            'template_id' => 'uMzYqLGtja-f5AVcdr390hrXOvQRkZuX0aiIEJjcVcY',//'template-id',
            'page' => 'page',
            'form_id' => $request->form_id, //'form-id',
            'data' => [
                'keyword1' => $request->contact,//收货人
                'keyword2' => $request->address,//收货地址
                'keyword3' => $request->order_sn,//订单编号
                'keyword4' => $request->price,//订单金额
                'keyword5' => $status,//订单状态
                'keyword6' => $request->retain,//备注
            ],
        ]);
        return $this->message('消息发送成功');
    }

    //测试
    public function test()
    {
        $client  = new \Delayer\Client(config('database.redis.default'));
        // dd($client);
        $orderId = '32';
        $data    = [
            'orderId' => $orderId,
            'action'  => 'close',
        ];
        $data = $orderId;
        $message = new Message([
            // 任务ID，必须全局唯一
            'id'    => $orderId,
            // 主题，取出任务时需使用
            'topic' => 'close_order',
            // 必须转换为string类型
            'body'  => json_encode($data),
        ]);
        // 第2个参数为延迟时间，第3个参数为延迟到期后如果任务没有被消费的最大生存时间(秒)
        $ret = $client->push($message, 2, 20);
        var_dump($ret);
    }

    public function testRes()
    {

        $client  = new \Delayer\Client(config('database.redis.default'));

        $message = $client->pop('close_order');
        // $message = $client->bPop('close_order', 1);
        // $message = $client->remove($id);
        // 没有任务时，返回false
        var_dump($message);
        // var_dump($message->id);

    }

}