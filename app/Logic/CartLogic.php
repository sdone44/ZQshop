<?php

namespace App\Logic;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Resources\ShopCart as ShopCartResource;
use App\Http\Resources\ShopGoods as ShopGoodsResource;
use App\Models\ShopCart;
use App\Models\ShopProduct;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use App\Models\UserShop;
use App\Models\ShopGroup;
use App\Models\ShopOrder;
use App\Models\ShopOrderGoods;
use App\Models\UserShopProduct;
use App\Logic\AddressLogic;


class CartLogic
{
    public function __construct()
    {

    }

    static public function addCart($goodsInfo, $number, $shopId,$user_id,$product_id = 0)
    {
        $newCart = new ShopCart();
        $where = [
            'goods_id' => $goodsInfo->id,
            'shop_id' => $shopId,
          	'product_id' => $product_id,
        ];
        $whereNum = [
            'goods_id' => $goodsInfo->id,
            'uid' => $user_id,
            'shop_id' => $shopId,
            'group_id' => 0,
          	'product_id' => $product_id,
        ];

        //$shopStock = UserShopsShopGoods::where($where)->first(); //商家商品库存
      	$shopStock = UserShopProduct::where($where)->first(); //商家商品库存
		//return $shopStock['stock'];
      	if(!$shopStock){
			return 4;        
        }
      
        if($shopStock['stock']==0){
            return 3; //商家无库存
        }
        $info = $newCart->where($whereNum)->first(); //购物车中商品若存在只增加数量
        // dd($info);
        if (!empty($info->goods_id)) {
            $info->number = $info->number + $number;//原有数量+增量
            // 库存超额判断
            if ($info->number > $shopStock['stock']) {
                return 2; //超出商家库存
            }
            $info->shop_id = $shopId;
            return $info->save();
        }
        $newCart->goods_sn = $goodsInfo->goods_sn;
        $newCart->retail_price = $goodsInfo->retail_price;
        $newCart->uid = $user_id;
        $newCart->goods_id = $goodsInfo->id;
        $newCart->goods_name = $goodsInfo->goods_name;
        $newCart->market_price = $goodsInfo->retail_price;
        $newCart->number = $number;
        // $newCart->list_pic_url = $goodsInfo->list_pic_url;
        $newCart->primary_pic_url = $goodsInfo->primary_pic_url;
        $newCart->shop_id = $shopId;

        if ($product_id) { //如果选择了规格
            $goodsProduct = ShopProduct::where('id',$product_id)->first();
            // dd($goodsProduct);
            $goods_specification_names = explode('_',$goodsProduct->goods_specification_names);
            $spec_item_names = explode('_',$goodsProduct->goods_spec_item_names);
            // dd($goods_specification_names);
            $goods_specifition_name_value = '';
            for($i=0;$i<count($goods_specification_names);$i++){
                $goods_specifition_name_value .= $goods_specification_names[$i] .'_'.$spec_item_names[$i].' ';
            }
            $newCart->goods_specifition_name_value = $goods_specifition_name_value;
            $newCart->goods_specifition_ids = $goodsProduct->goods_specification_ids;
          	$newCart->product_id = $product_id;
        }

        return $newCart->save();
    }

    static public function addCartToGroup($goodsInfo, $number, $shopId, $user_id, $groupId, $product_id = 0, $addressId, $request)
    {
        // dd($product_id);
        //检测该团购商品是否已经存在未付款订单
        $isExistOrder = ShopOrder::where([
            ['group_id','=',$groupId],
            ['uid','=',$user_id],
            ['order_status','=',10]//10-未付款状态
        ])->first();
        if($isExistOrder){
            $outData = $isExistOrder->toArray();
            $outData['goodsList'] = ShopOrderGoods::where('order_id',$outData['id'])->get();
            return ['code'=>201,'msg'=>'已存在未付款团购商品','data'=>$outData];
        }

        $newCart = new ShopCart();
        $where = [ //检查库存
            'good_id' => $goodsInfo->id,
            'shop_id' => $shopId
        ];
        $whereNum = [ //检查商品是否已存在
            'goods_id' => $goodsInfo->id,
            'uid' => $user_id,
            'group_id' => $groupId,
        ];

        $shopGroupInfo = ShopGroup::where('id',$groupId)->first();
        $remainderNum = $shopGroupInfo->max_num - $shopGroupInfo->cur_num; //剩余数量
        $nowTime = time();
        $endTime = strtotime($shopGroupInfo->end_time);
        $remainderTime =  $endTime - $nowTime > 0 ? $endTime - $nowTime : 0;//剩余时间
        // dd($shopGroupInfo);
        if(!$shopGroupInfo){ //若团购不存在
            return 2;
        }
        if(!$remainderTime){ //团购结束
            return 4;
        }
        if($number > $remainderNum){ //购买数超出
            return 3;
        }
        // $info = $newCart->where($whereNum)->first(); //购物车中商品若存在
        // if (!empty($info->goods_id)) {
        //     return $info;
        // }
        //添加购物车数据
        $newCart->goods_sn = $goodsInfo->goods_sn;
        $newCart->retail_price = $shopGroupInfo->retail_price; //这里是团购价格
        $newCart->uid = $user_id;
        $newCart->goods_id = $goodsInfo->id;
        $newCart->goods_name = $goodsInfo->goods_name;
        $newCart->market_price = $goodsInfo->retail_price;
        $newCart->number = $number;
        // $newCart->list_pic_url = $goodsInfo->list_pic_url;
        $newCart->primary_pic_url = $goodsInfo->primary_pic_url;
        $newCart->shop_id = $shopId;
        $newCart->group_id = $groupId;

        if ($product_id) { //如果选择了规格
            $goodsProduct = ShopProduct::where('id',$product_id)->first();
            // dd($goodsProduct);
            $goods_specification_names = explode('_',$goodsProduct->goods_specification_names);
            $spec_item_names = explode('_',$goodsProduct->goods_spec_item_names);
            // dd($goods_specification_names);
            $goods_specifition_name_value = '';
            for($i=0;$i<count($goods_specification_names);$i++){
                $goods_specifition_name_value .= $goods_specification_names[$i] .'_'.$spec_item_names[$i].' ';
            }
            $newCart->goods_specifition_name_value = $goods_specifition_name_value;
            $newCart->goods_specifition_ids = $goodsProduct->goods_specification_ids;
          	$newCart->product_id = $product_id;
        }

        if($newCart->save()){ //直接生成订单

            $checkedAddress = AddressLogic::getOneAddr($addressId, $user_id); // 选择地址
            if (empty($checkedAddress)) {
                $newCart->delete();
                return 401; //未填收货地址
            }

            $cartList = static::getCartList($whereNum);
            $orderData = [];
            foreach ($cartList as $shopid => $orderinfo) {
                if($shopid==$shopId){      
                    $orderData = $orderinfo;
                }
            }
            $orderData['actualPrice'] = $orderData['TotalPrice'];
            $orderData['postscript'] = $request->postscript??'暂无留言';
            $orderData['groupId'] = $groupId;
            // dd($orderData);
            $buyModel = new Buy();
            $buyRe = $buyModel->buyStep($request, $orderData, $checkedAddress,$user_id);

            if (empty($buyRe['error'])) {
                return $buyRe;
            }else{
                $newCart->delete();
                return 204;
            }

        }

        return ['msg'=>'处理失败'];
    }

    //返回购物车列表状态
    public static function getCartList($where)
    {
        $list = ShopCart::where($where)->get()->toArray();
      	foreach ($list as $k => $v) {   //检测购物车商品上架状态
            $list[$k]['is_on_sale'] = ($re = ShopGoods::where('id',$v['goods_id'])->first()) ? $re->is_on_sale : 0;
        }
        $shopIdArr = ShopCart::where($where)->pluck('shop_id')->toArray();
        // dd($list);
        $userCartList = [];
        //生成用户当前购物车列表
        foreach ($shopIdArr as $k => $shopId) {
            // $userCartList[$onelist['shop_id']] = ['shop_id'=>$onelist['shop_id']];
            $shopInfo = Usershop::where('id',$shopId)->get()->toArray();
            $userCartList[$shopId]['shopInfo'] = $shopInfo[0];
            $userCartList[$shopId]['cartList'] = [];
            $userCartList[$shopId]['TotalPrice'] = [];
            $userCartList[$shopId]['CountNum'] = [];
        }
        // dd($userCartList);
        foreach ($list as $k => $onelist) {
            // dd($onelist['shop_id']);
            foreach ($userCartList as $shopId=>$cartList) {
                if($shopId==$onelist['shop_id']){
                    $userCartList[$shopId]['cartList'][] = $onelist;
                    // dd($userCartList);
                }
            }
        }
        // dd($userCartList);
        //统计购物车列表页 (选中状态的)每单的总价总件
        foreach ($userCartList as $k => $cartInfo) {
            $TotalPrice = 0;
            $CountNum = 0;
            foreach ($cartInfo['cartList'] as $kk => $goodsInfo) {
                if($goodsInfo['checked']==1){
                    $TotalPrice = PriceCalculate($TotalPrice,'+',PriceCalculate($goodsInfo['retail_price'],'*',$goodsInfo['number']));
                    $CountNum += $goodsInfo['number'];
                }

            }
            $userCartList[$k]['TotalPrice'] = $TotalPrice;
            $userCartList[$k]['CountNum'] = $CountNum;
            // dd($userCartList);
        }
        // dd($userCartList);
        return $userCartList;
    }

    public static function getCheckedGoodsList($uid)
    {
        $cartList = ShopCart::getCheckedGoodsList($uid);
        $checkedGoodsList = ShopCartResource::collection($cartList);
        $goodsTotalPrice = 0.00;
        foreach($checkedGoodsList as $goodsVal){
            $goodsTotalPrice = PriceCalculate($goodsTotalPrice,'+',PriceCalculate($goodsVal['retail_price'],'*',$goodsVal['number']));
        }
        $freightPrice = array_sum(array_pluck($checkedGoodsList, 'freight_price'));
        return [
            'checkedGoodsList' => $checkedGoodsList,// 商品列表
            'goodsTotalPrice' => $goodsTotalPrice,// 商品总价格
            'freightPrice' => $freightPrice,// 商品运费总和
            'orderTotalPrice' => PriceCalculate($goodsTotalPrice,'+',$freightPrice)
        ];
    }

    public static function getBuyGoodsById($goodsId,$number = 1,$format = 1,$productId = '')
    {
        if($productId){
            $products = ShopProduct::where(['id' => $productId])->get()->keyBy ('goods_id');
        }
        $goodsInfos = ShopGoods::getGoodsList(['id'=>$goodsId]);
        foreach ($goodsInfos as $item_info){
            $product_goods_spec_item_names ='';
            $product_retail_price = 0;
            if($productId){
                $product_goods_spec_item_names = $products[$item_info->id]['goods_spec_item_names'];
                $product_retail_price = $products[$item_info->id]['retail_price'];
            }
            $checkedGoodsList[] = [
                "goods_id"=> $item_info->id,
                "product_id"=> $productId,
                "goods_name"=> $item_info->goods_name.' ' .$product_goods_spec_item_names,
                "market_price"=> $item_info->counter_price,
                "retail_price"=> $product_retail_price ? $product_retail_price:$item_info->retail_price,
                "number"=> $number,
                'freight_price' => $item_info->freight_price,
                "primary_pic_url"=>  $format ? config('filesystems.disks.oss.url').'/'.$item_info->primary_pic_url:$item_info->primary_pic_url,
                "list_pic_url"=>  $format ? config('filesystems.disks.oss.url').'/'.$item_info->primary_pic_url:$item_info->primary_pic_url,
            ];
        }
        $goodsTotalPrice = 0.00;
        foreach($checkedGoodsList as $goodsVal){
            $goodsTotalPrice = PriceCalculate($goodsTotalPrice,'+',PriceCalculate($goodsVal['retail_price'],'*',$number));
        }
        $freightPrice = array_sum(array_pluck($checkedGoodsList, 'freight_price'));
        return [
            'checkedGoodsList' => $checkedGoodsList,// 商品列表
            'goodsTotalPrice' => $goodsTotalPrice,// 商品总价格
            'freightPrice' => $freightPrice,// 商品运费总和
            'orderTotalPrice' => PriceCalculate($goodsTotalPrice,'+',$freightPrice)
        ];
    }

    // 清空购物车
    public static function clearCart($uid,$shopId){
        return ShopCart::where([
            'uid' => $uid,
            'checked'=> ShopCart::STATE_CHECKED,
            'shop_id' => $shopId,
        ])->delete();
    }

}
