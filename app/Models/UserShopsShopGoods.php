<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ShopGoods;
use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderGoods;
use App\Models\UserShop;
use Carbon\Carbon;

class UserShopsShopGoods extends Model
{
    // protected $table = "user_shops_shop_goods";
    protected $fillable = ['shop_id', 'category_id', 'good_id','stock','goods_name','is_recommend'];
    //
    public function user_shop()
    {
        return $this->belongsTo(UserShop::class, 'shop_id');
    }


    //用于后台商铺gird代理商品显示
    public static function shop_good($id)
    {
        $where = ['shop_id'=>$id];
        $goods = static::where($where)->get(['goods_name','stock','good_id','sell_volume']);
        $result = [];

        if(empty($goods[0])){
            $result[0] = ['暂无商品'];
            return $result;
        }

        $ShopOrderArr = [];
        $ShopOrder = ShopOrder::where([
            ['shop_id','=',$id],
            ['order_status','>',22],
        ])->pluck('id'); //当前商家完成支付的订单
        if(count($ShopOrder)!=0){
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            foreach ($ShopOrder as $k => $order_id) {
                $ShopOrderArr[] = $order_id;
            }
            // dd($ShopOrderArr);
            $ShopOrderGoods = ShopOrderGoods::whereIn('order_id',$ShopOrderArr)->whereBetween('created_at', [$startOfMonth,$endOfMonth])->get();
            // $ShopOrderGoods = ShopOrderGoods::whereIn('order_id',$ShopOrderArr)->get();
            // dd($ShopOrderGoods);
        }else{
            $ShopOrderGoods=[];
        }
        foreach ($goods as $k => $goodsInfo) {
            //统计月销量
            $sell_volume = 0;

            if(count($ShopOrderGoods)!=0){
                foreach ($ShopOrderGoods as $kk => $v_ShopOrderGoods) {
                    if($v_ShopOrderGoods->goods_id==$goodsInfo->good_id){
                        $sell_volume += $v_ShopOrderGoods->number;
                    }
                }
            } 

            $result[$k] = [$goodsInfo->goods_name,$sell_volume,$goodsInfo->sell_volume];
        }

        return $result;
    }

    //用于商家详情页的商品列表
    public static function shop_goods($id)
    {
    	$where = ['shop_id'=>$id];
    	$goods = static::where($where)->get(['good_id']);

        $result = [];

        foreach ($goods as $eachClass) {    //获取下架商品
            $re = ShopGoods::where([
                ['id','=',$eachClass->good_id],
                ['is_on_sale','=',1],
            ])->first();
            if($re){
                $result[] = $re;
            }else{
                continue;
            }
            
        }
        
        return $result;
    }
    //商家从UserShopsShopGoods 结构化  不含stock库存
    public static function shop_structrue_noStock($where = [])
    {
        
        $shop_details = static::where($where)->get();
        $shop_details_res = [];
        // dd($shop_details);
        foreach ($shop_details as $k) {
            $shop_details_res[$k->category_id][$k->good_id] = ['stock'=>$k->stock];

        }
        //整合数据结构
        $shop_details_mix = [];
        $i = 0;
        foreach ($shop_details_res as $category_id => $goods) {
            $shop_details_mix[$i]['category_id'] = $category_id;
            $j = 0;
            foreach ($goods as $good_id => $stock) {
                $shop_details_mix[$i]['goodList'][$j]['good_id'] = $good_id;
                $j++;
            }
            $i++;
        }
        // dd($shop_details_mix);
        return $shop_details_mix;
    }

    //商家从UserShopsShopGoods 结构化  含stock库存
    public static function shop_structrue($where = [])
    {
        
        $shop_details = static::where($where)->get();
        $shop_details_res = [];
        // dd($shop_details);
        foreach ($shop_details as $k) {
            $shop_details_res[$k->category_id][$k->good_id] = ['stock'=>$k->stock];

        }
        //整合数据结构
        $shop_details_mix = [];
        $i = 0;
        foreach ($shop_details_res as $category_id => $goods) {
            $shop_details_mix[$i]['category_id'] = $category_id;
            $j = 0;
            foreach ($goods as $good_id => $stock) {
                $shop_details_mix[$i]['goodList'][$j]['good_id'] = $good_id;
                $shop_details_mix[$i]['goodList'][$j]['stock'] = $stock['stock'];
                $j++;
            }
            $i++;
        }
        // dd($shop_details_mix);
        return $shop_details_mix;
    }

    //把结构化id的分类 商品 商铺 返回详情
    public static function shop_details($structrue)
    {
        $getCategory = ['name'];
        $getGood = ['goods_name','goods_desc','retail_price','unit_price','primary_pic_url'];
        $getShop = ['shop_name','shop_icon'];
        foreach ($structrue as $key => $category) {
            $category_detail = ShopCategory::where('id',$category['category_id'])->get($getCategory)->toArray();
            // dd($shop_res[$key]);
            $structrue[$key] = array_merge($category_detail[0],$structrue[$key]);
            foreach ($category['goodList'] as $k => $good) {
                $good_detail = ShopGoods::where('id',$good['good_id'])->get($getGood)->toArray();
                $shop_detail = UserShop::where('id',$good['shop_id'])->get($getShop)->toArray();
                $structrue[$key]['goodList'][$k] = array_merge($good_detail[0],$structrue[$key]['goodList'][$k]);
                $structrue[$key]['goodList'][$k] = array_merge($shop_detail[0],$structrue[$key]['goodList'][$k]);
            }
        }

        return $structrue;
    }
}
