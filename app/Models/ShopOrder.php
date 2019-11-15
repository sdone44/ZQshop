<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShopOrder extends Model
{
    const STATUS_INVALID = 0;//订单关闭或无效，用户取消也置0
    const STATUS_WAIT_PAY = 10;//订单待支付
    const STATUS_REFUND_APPLY = 19;//用户申请退款
    const STATUS_REFUND_SUCCESS = 20;//退款成功
    const STATUS_REFUND_SUBMIT = 21;//确认申请退款
    const STATUS_ALREADY_PAID = 22;//订单支付完成
    const STATUS_DELIVERING = 32;//确认配送
    const STATUS_COMPLETED = 40;//订单收货完成
    const STATUS_COMMENTED = 42;//订单评价完成

    const STATUS_INVALID_STRING = '订单取消';//0 订单关闭或无效，用户取消也置0
    const STATUS_WAIT_PAY_STRING = '待支付';//10 订单待支付
    const STATUS_REFUND_APPLY_STRING = '申请退款';//19 用户申请退款
    const STATUS_REFUND_SUCCESS_STRING = '退款成功';//20 退款成功
    const STATUS_REFUND_SUBMIT_STRING = '确认申请退款';//21 确认申请退款
    const STATUS_ALREADY_PAID_STRING = '支付完成';//22 订单支付完成
    const STATUS_DELIVERING_STRING = '已配送';//32 确认配送
    const STATUS_COMPLETED_STRING = '已收货';//40 订单收货完成
    const STATUS_COMMENTED_STRING = '已评价';//42订单评价完成

    const SHIPING_STATUS_WAIT_SEND = 0;// 待发货
    const SHIPING_STATUS_SEND = 10;// 已发货
    const SHIPING_STATUS_SENDED = 20;// 已收货

    // 核验订单状态
    const PAY_WAIT = 0;// 待支付下单
    const PAY_WAIT_CHECK = 1;// 已支付下单 待核验
    const PAY_CHECKED_OK = 2;// 核验完成
    const PAY_CHECKED_ERROR = 4;// 核验失败或出现问题

    //
    protected $table = "shop_order";
    protected $fillable = [
        'order_sn',
        'uid',
        'order_status',
        'shipping_status',
        'pay_status',
        'consignee',
        'country',
        'province',
        'city',
        'district',
        'address',
        'mobile',
        'postscript',
        'pay_name',
        'pay_id',
        'actual_price',
        'order_price',
        'goods_price',
        'add_time',
        'confirm_time',
        'pay_time',
        'freight_price',
        'callback_status',
        'coupon_id',
        'coupon_price',
        'trade_no',
        'shop_id',
        'group_id',
    ];
    public static function getStatusDisplayMap()
    {
        return [
            self::STATUS_INVALID => self::STATUS_INVALID_STRING,  //0
            self::STATUS_WAIT_PAY => self::STATUS_WAIT_PAY_STRING, //10
            self::STATUS_ALREADY_PAID => self::STATUS_ALREADY_PAID_STRING, //22
            self::STATUS_DELIVERING => self::STATUS_DELIVERING_STRING, //32
            self::STATUS_COMPLETED => self::STATUS_COMPLETED_STRING, //40
            self::STATUS_COMMENTED => self::STATUS_COMMENTED_STRING, //42
            self::STATUS_REFUND_APPLY => self::STATUS_REFUND_APPLY_STRING, //19
            self::STATUS_REFUND_SUCCESS => self::STATUS_REFUND_SUCCESS_STRING, //20
            self::STATUS_REFUND_SUBMIT => self::STATUS_REFUND_SUBMIT_STRING, //21
        ];
    }

    // 关联订单商品
    public function orderGoods()
    {
        return $this->hasMany(ShopOrderGoods::class, 'order_id');
    }

    /**
     * 获取订单 及 订单商品列表
     */
    public static function getOrderAndOrderGoodsList($condition)
    {
        return static::with('orderGoods')->where($condition)->orderBy('id', 'desc')->paginate(10);
    }

    /**
     * 获取订单 及 订单商品列表 评论用
     */
    public static function getOrderAndOrderGoodsListForComment($condition)
    {
        return static::with('orderGoods')->where($condition)->get()[0];
    }


    /**
     * 获取订单数量
     */
    public static function countOrder($condition)
    {
        $n = static::where($condition)->count();
        return $n ? $n : 0;
    }

    /**
     * 商家数据
     */
    public static function getShopDateById($shopId)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $shopData['order_finish_num'] = static::where([ //总已完成订单
            ['shop_id','=',$shopId],
            ['order_status','=',40]
        ])->count(); 
        $shopData['order_waited'] = static::where([ //待接单
            ['shop_id','=',$shopId],
            ['order_status','=',22]
        ])->count(); 
        $shopData['order_wait_send'] = static::where([ //待送达
            ['shop_id','=',$shopId],
            ['order_status','=',32]
        ])->count();
        $shopData['order_refund'] = static::where([ //退款申请数
            ['shop_id','=',$shopId],
            ['order_status','=',19]
        ])->count();
        $shopData['total_value'] = static::where([  //总成交额
            ['shop_id','=',$shopId],
            ['order_status','=',40]
        ])->sum('actual_price');
        $shopData['today_value'] = static::where([  //今日付款金额
            ['shop_id','=',$shopId],
            ['order_status','>',21]
        ])->whereDate('created_at', $today)->sum('actual_price');
        $shopData['monthly_order_num'] = static::where([  //本月订单数
            ['shop_id','=',$shopId],
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->count();
        $shopData['monthly_value'] = static::where([  //本月成交额
            ['shop_id','=',$shopId],
            ['order_status','=',40]
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->sum('actual_price');
      	$shopData['total_order_num'] = static::where([  //总订单数
            ['shop_id','=',$shopId],
            ['order_status','<>',10],
        ])->count();

        return $shopData;

    }

    /**
     * 商家总数据统计
     */
    public static function getShopTotalDateById($shopId)
    {
        $shopData['total_order_num'] = static::where([  //总订单数（已完成的）
            ['shop_id','=',$shopId],
            ['order_status','=',40],
        ])->count();
        $shopData['total_value'] = static::where([  //总成交额
            ['shop_id','=',$shopId],
            ['order_status','=',40]
        ])->sum('actual_price');
        $shopData['total_refund_order_num'] = static::where([  //总退款订单数
            ['shop_id','=',$shopId],
            ['order_status','=',21]
        ])->count();
        $shopData['total_refund_value'] = static::where([  //总退款金额
            ['shop_id','=',$shopId],
            ['order_status','=',21]
        ])->sum('actual_price');
        $shopData['total_retail_value'] = sprintf("%.2f",$shopData['total_value'] - $shopData['total_refund_value']); //总真实成交额

        return $shopData;

    }

    public static function getShopMonthlyDateById($shopId)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $shopData['monthly_order_num'] = static::where([  //本月订单数
            ['shop_id','=',$shopId],
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->count();
        $shopData['monthly_refund_order_num'] = static::where([  //本月退款订单数
            ['shop_id','=',$shopId],
            ['order_status','=',21]
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->count();
        $shopData['monthly_value'] = static::where([  //本月成交额
            ['shop_id','=',$shopId],
            ['order_status','=',40]
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->sum('actual_price');
        $shopData['monthly_refund_value'] = static::where([  //本月退款金额
            ['shop_id','=',$shopId],
            ['order_status','=',21]
        ])->whereBetween('created_at', [$startOfMonth,$endOfMonth])->sum('actual_price');
        $shopData['monthly_retail_value'] = $shopData['monthly_value'] - $shopData['monthly_refund_value']; //本月真实成交额

        return $shopData;

    }


    public function getAddTimeAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            // Leave this part off if you want to keep the property as
            // a Carbon object rather than always just returning a string
            ->toDateTimeString()
            ;
    }

    //取消订单
    public static function cancelOrder($id)
    {
        $n = static::where('id',$id)->update(['order_status'=>0]);
        return $n ? $n : 0;
    }

    //是否有新订单
    public static function isNewOrder($shopId)
    {
        $res = static::where([
            ['shop_id','=',$shopId],
            ['order_status','=',22],
            ['is_new','=',1]
        ])->pluck('is_new')->toArray();
        return $res?1:0;
    }

    //已阅新订单处理
    public static function readNewOrder($shopId)
    {
        $res = static::where([
            ['shop_id','=',$shopId],
            ['is_new',1]
        ])->update(['is_new'=>0]);
        return $res; //res值为处理了多少条
    }
}
