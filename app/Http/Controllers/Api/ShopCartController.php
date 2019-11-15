<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\CartLogic;
use App\Logic\AddressLogic;
use App\Models\ShopGoods;
use App\Models\ShopCart;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ShopCart as ShopCartResource;
use App\Models\UserShopsShopGoods;
use App\Models\ShopOrderGoods;
use App\Models\UserShopProduct;

class ShopCartController extends ApiController
{


    // // 获取购物车的数据
    public function index(Request $request)
    {
        
        // 参数校验 
        // $validator = Validator::make($request->all(),
        //     [
        //         'user_id' => 'required',
        //     ],
        //     [
        //         'user_id.required' => '用户id参数缺失',
        //     ]
        // );
        // if ($validator->fails()) {
        //     return $this->failed($validator->errors(), 403);
        // }
        if(!$request->input('user_id')){
            return ['msg'=>'未登录'];
        }

        $user_id = $request->user_id;

        $cartData = CartLogic::getCartList([['uid','=',$user_id]]);


        return $this->success($cartData);
    }

    // 添加商品到购物车
    public function add(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'goods_id' => 'required',
                'number' => 'required|numeric',
                'shop_id'=>'required',
            ],
            [
                'user_id.required' => '用户id参数缺失',
                'goods_id.required' => '商品id参数缺失',
                'shop_id.required' => '商铺id参数缺失',
                'number.required' => '购买数量参数缺失',
                'number.numeric' => '购买数量需要是数字',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
		
        $user_id = $request->user_id;
        $goodsId = $request->goods_id;
        $number = $request->number;
        $shopId = $request->shop_id;
        $product_id = $request->product_id ?? 0; //用于规格

        $groupId = $request->group_id; //团购品id
        $type = $request->type; //购买类型 1-商家购买  2-团购
        $addressId = $request->address_id;

        // $groupId = 9;
        // $addressId = 5;

        if(!$type){
            return ['msg'=>'缺少type参数',];
        }

        $goodsInfo = ShopGoods::getGoodsInfo($goodsId);
      
      	if(!$goodsInfo){
        	return ['msg'=>'商品信息为空'];
        }

        if($type == 1){ //商铺购买
            $re = CartLogic::addCart($goodsInfo,$number,$shopId,$user_id,$product_id);

          	if($re===4){
            	return ['msg'=>'商家无此产品！','code'=>$re];
            }else if($re===2){
                return ['msg'=>'超出商家库存！','code'=>'202'];
            }else if($re===3){
                return ['msg'=>'商家无库存','code'=>'203'];
            }else if($re){
                $cartData = CartLogic::getCartList([['uid','=',$user_id],['checked','=',ShopCart::STATE_CHECKED],['group_id','=',0]]);
                return $this->success($cartData);
            }
            return $this->failed('购物车添加失败', 201);
        }

        if($type == 2){//团购
            if(!$groupId){
                return ['msg'=>'无此团购'];
            }
            $re = CartLogic::addCartToGroup($goodsInfo,$number,$shopId,$user_id,$groupId,$product_id,$addressId,$request);

            if(isset($re['code']) && $re['code'] == 201){
                return $this->success($re);
            }
            if($re===2){
                return ['msg'=>'团购不存在'];
            }
            if($re===3){
                return ['msg'=>'超出购买数'];
            }
            if($re===4){
                return ['msg'=>'团购已结束'];
            }
            if($re===401){
                return $this->failed('未查到用户收货地址，请检查您的收货地址', 401);
            }
            if($re===204){
                return $this->failed('订单生成失败', 204);
            }
            $outData = $re;
            $outData['goodsList'] = ShopOrderGoods::where('order_id',$outData['id'])->get();

            return $this->success($outData);
        }

        return ['msg'=>'未知错误','type'=>$type];
            
    }

    // 更新购物车的商品
    public function update(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
                'user_id' => 'required',
                'goods_id' => 'required',
                'number' => 'required|numeric',
                'shop_id'=>'required',
            ],
            [
                'id.required' => '购物车表id参数缺失',
                'user_id.required' => '用户id参数缺失',
                'goods_id.required' => '商品id参数缺失',
                'shop_id.required' => '商铺id参数缺失',
                'number.required' => '购买数量参数缺失',
                'number.numeric' => '购买数量需要是数字',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $where['id'] = $request->id;
        $goodsId = $request->goods_id;
        $shopId = $request->shop_id;
        $number = $request->number;
        $user_id = $request->user_id;
      	$product_id = $request->product_id;
        $whereStock = [
            'good_id' => $goodsId,
            'shop_id' => $shopId
        ];

        $info = ShopCart::where($where)->first();
        //$shopStock = UserShopsShopGoods::where($whereStock)->first();
      	if($product_id){
        	$shopStock = UserShopProduct::where([
        		['goods_id','=',$goodsId],
          		['shop_id','=',$shopId],
              	['product_id','=',$product_id],
        	])->first();
        }else if($product_id==0){
        	$shopStock = UserShopProduct::where([
        		['goods_id','=',$goodsId],
          		['shop_id','=',$shopId],
        	])->first();
        }

        if (!empty($info->goods_id)) {
  
            if ($number > $shopStock->stock) {
                // $info->number = $shopStock->stock;
                return ['code'=>201,'msg'=>'没有这么多商品！','error'=>$shopStock];
            }else{
                $info->number = $number;
            }
            
            $re = $info->save();
        }else{
            return ['msg'=>'商家无该商品'];
        }


        if($re){
            $cartData = CartLogic::getCartList([['uid','=',$user_id]]);
            // dd($cartData);
            return $this->success($cartData);
        }

        return $this->failed('更新失败','201');
    }

    // 删除购物车的商品
    public function delete(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'id' => 'numeric',
            ],
            [
                'id.numeric' => '购物车参数不合法',
                'user_id.required' => '用户id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $user_id = $request->user_id;
        $where['id'] = $request->id;

        $re = ShopCart::where($where)->delete();
        if($re){
            $cartData = CartLogic::getCartList([['uid','=',$user_id]]);
            // dd($cartData);
            return $this->success($cartData);
        }
        return $this->failed('删除失败','201');
    }

    // 选择或取消选择商品
    public function checked(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
                'user_id' => 'required',
                'checked' => 'required|numeric',
            ],
            [
                'id.required' => '商品id参数缺失',
                'user_id.required' => '用户id参数缺失',
                'checked.required' => '参数缺失',
                'checked.numeric' => '非法参数',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $user_id = $request->user_id;
        $id = $request->id;
        $checked = $request->checked;

        $where = [
            'id'=>$id,
        ];
        // $model = ShopCart::where($where)->first();
        // if($model->checked==$checked){
        //     return ['msg'=>'请勿重复操作','error'=>$model];
        // }
        $re = ShopCart::where($where)->update(['checked' => $checked]);
        if($re){
            $cartData = CartLogic::getCartList([['uid','=',$user_id]]);

            return $this->success($cartData);
        }
        return ['msg'=>'更新选中失败','error'=>$re];

        
        
    }

    // 获取购物车商品件数
    public function goodsCount(Request $request)
    {
        if (empty(\Auth::user()->id)) {
            $user_id = 0;
        } else {
            $user_id = \Auth::user()->id;
        }
        $goodsCount = ShopCart::getGoodsCount(['uid' => $user_id]);
        return $this->success(['goodsCount'=>$goodsCount]);
    }

    //购物车商品全选勾选
    public function checkAll(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'checked_all' => 'required|numeric',
                'shop_id'=>'required',
            ],
            [
                'user_id.required' => '用户id参数缺失',
                'shop_id.required' => '商家id参数缺失',
                'checked_all.required' => '参数缺失',
                'checked_all.numeric' => '非法参数',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $user_id = $request->user_id;
        $shop_id = $request->shop_id;
        $checked_status = $request->checked_all;
        $where = [
            ['uid','=',$user_id,],
            ['shop_id','=',$shop_id,]
        ];
        if ($checked_status) {//全选
            $checked = ['checked'=>1];
        }else{
            $checked = ['checked'=>0];
        }
        $re = ShopCart::where($where)->update($checked);
        if($re){
            $cartData = CartLogic::getCartList([['uid','=',$user_id]]);

            return $this->success($cartData);
        }
        return ['msg'=>'更新选中失败','error'=>$re];
    }

}