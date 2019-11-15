<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use App\Logic\Buy;
use App\Models\ShopOrder;
use App\Models\ShopGroup;
use App\Models\ShopOrderGoods;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Validator;
use App\User;


class PaymentController extends ApiController
{


    public function toPay(Request $request){
        // 验证规则
        $validator = Validator::make($request->all(),
            [
                'order_id' => 'required',
                'user_id' => 'required'
            ],
            [
                'order_id.required' => '订单号缺失',
                'user_id.required' => '用户id缺失'
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 401);
        }
        $orderId = $request->order_id;
        $userId = $request->user_id;


        // 查询订单
        $order_info = ShopOrder::find($orderId)->toArray();
        $userInfo = User::find($userId);
        if(empty($order_info)){
            return $this->failed('订单不存在', 401);
        }
      	//团购订单控制(团购支付人数满就不能再支付订单)
        if($order_info['group_id']!=0){
            $ShopGroupModel = ShopGroup::where('id',$order_info['group_id'])->first();
            if($ShopGroupModel->cur_num==$ShopGroupModel->max_num){
                return ['msg'=>'团购已结束','code'=>401];
            }
        }
        // 开始生成预支付订单
        $buy = new Buy();
        $buy_info = $buy->pay_step1($order_info,$userInfo->openid);
		//return $buy_info;
        if($buy_info){
            if($order_info['group_id'] != 0){
                $num = count(ShopOrderGoods::where('order_id',$orderId)->get());
                ShopGroup::where('id',$order_info['group_id'])->increment('cur_num', $num);
            }
            return $this->success($buy_info);
        }
      	return ['msg'=>$buy_info,'code'=>401,'errors'=>$buy_info];
        //return $this->failed('支付失败', 401);

    }

    //订单退款
    public function orderRefund(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_sn' => 'required',
                'actual_price' => 'required',
                'refund_desc' => 'required',
            ],
            [
                'order_sn.required' => '订单sn参数缺失',
                'actual_price.required' => '金额参数缺失',
                'refund_desc.required' => '退款原因参数缺失',

            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $app = Factory::payment(config('wechat.payment.default'));
        $actualPrice = $request->actual_price;
        $refundDesc = $request->refund_desc;
        $randCode = randomkeys(6);

        $orderSno = $request->order_sn;
        $refundNumber = $orderSno . $randCode; //自己生成
        $totalFee = $actualPrice*100;
        $refundFee = $actualPrice*100;
        $config = [
           'refund_desc'=>$refundDesc,
        ];
        //根据商户订单号退款, 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byOutTradeNumber($orderSno,$refundNumber,$totalFee,$refundFee,$config = []);

        if($result['result_code'] == 'SUCCESS'){
            ShopOrder::where('order_sn',$orderSno)->update(['is_new'=>0,'order_status'=>21,'refund_number'=>$refundNumber]);//21退款处理中

            return ['msg'=>'退款成功','code'=>21];
        }
        return $result;
    }


    //订单退款结果查询
    public function orderRefundRes(Request $request)
    {
        
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'order_sn' => 'required',
            ],
            [
                'order_sn.required' => '订单sn参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $orderSno = $request->order_sn;

        $result = $app->refund->queryByOutTradeNumber($orderSno);


        if($result['refund_status_0'] == 'PROCESSING'){
            return ['msg'=>'退款处理中','code'=>0];
        }else if($result['refund_status_0'] == 'SUCCESS'){
            ShopOrder::where('order_sn',$orderSno)->update(['order_status'=>20]);//20订单退款成功
            return ['msg'=>'已退款成功','code'=>1];
        }else{
            return ['msg'=>$result,'code'=>404];
        }
    }

    /**
     * 支付提醒
     */
    public function notify()
    {
        $this->pay_log(var_export(file_get_contents('php://input'), true));
        $app = Factory::payment(config('wechat.payment.default'));
        $response = $app->handlePaidNotify(function($message, $fail){
            $this->pay_log('notify  ' . var_export($message, true));
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order = ShopOrder::where(['order_sn' => $message['out_trade_no']])->first();
            if (!$order) { // 如果订单不存在
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($order->order_status >= ShopOrder::STATUS_ALREADY_PAID) {
                return true; // 已经支付成功了就不再更新了
            }

            ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 （给的钱少 这次就算了）//////////

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if($message['result_code'] === 'SUCCESS'){
                    // 判断支付金额
                    $order->pay_time = time(); // 更新支付时间为当前时间
                    $order->trade_no = $message['transaction_id'];// 微信交易号存放到数据库【退款等会用到】
                    if ($message['total_fee'] == ($order->actual_price * 100)) {
                        // 不是已经支付状态则修改为已经支付状态
                        $order->order_status = ShopOrder::STATUS_ALREADY_PAID;
                        $order->is_new = 1;
                    } else {
                        //生产的
                        $order->order_status = ShopOrder::STATUS_WAIT_PAY;
                        //add_my_log('order', '支付金额与订单金额不符：' . $order->actual_price . '（元）=>' . $notify->total_fee . '(分)', 3, json_encode($order->toArray()), '微信支付回调');
                    }

                }elseif($message['result_code'] === 'FAIL'){
                    // 用户支付失败
                    $order->order_status = ShopOrder::STATUS_WAIT_PAY;
                }
            } else { // 用户支付通讯失败
                return $fail('通信失败，请稍后再通知我');
            }
            $order->save(); // 保存订单
            return true; // 返回处理完成
        });
        return $response;
    }



    /**
     * 记录日志
     */
    private function pay_log($msg)
    {
        $msg = date('H:i:s') . "|" . $msg . "\r\n";
        $msg .= '| GET:' . var_export($_GET, true) . "\r\n";
        file_put_contents('./log/member_pay' . date('Y-m-d') . ".log", $msg, FILE_APPEND);
    }
}
