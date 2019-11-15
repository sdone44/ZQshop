<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Logic\ShopGoodsLogic;
use App\Logic\ShopCommentLogic;
use App\Models\ShopBrand;
use App\Models\ShopGoods;
use App\Models\ShopCategory;
use App\Models\Carousel;
use App\Models\UserShop;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\ShopProduct;
use App\Models\UserShopProduct;

class ShopGoodsController extends ApiController
{
    // 商品统计
    public function getGoodsCount(Request $request)
    {
        $outData = ShopGoodsLogic::getGoodsCount([['id', '>', 0]]);
        return $this->success($outData);
    }

    // 获取商品列表
    public function getGoodsList(Request $request)
    {
        //ZQ按分类查找
        $where = ['category_id'=>$request->category_id];
        $outData = ShopGoods::where($where)->get()->toArray();
        return $this->success($outData);

    }

    // 获取商品分类列表
    public function getGoodsCategory(Request $request)
    {

        $category_id = ShopCategory::first()->value('id');
        $outData = ShopGoodsLogic::getGoodsCategory(['id' => $category_id]);
        // $outData = ShopCategory::all();
        return $this->success($outData);
    }

    //分类栏商品2019.6.19（根据推荐商家显示）
    public function getShopGoodsCategory(Request $request)
    {

        $district['name'] = $request->district;
        // $district['name'] = '番禺区';
        $districtId = CityAddress::getDistrictId($district);

        if ($districtId) {
            $where[] = ['district','=',$districtId];
            $where[] = ['state','=',1];
            $shopId_arr = UserShop::where($where)->pluck('id')->toArray(); //获取商家id数组
            // dd($shopId_arr);
            $ShopsGoodsRes = UserShopsShopGoods::whereIn('shop_id',$shopId_arr)->get(['shop_id','category_id','good_id']);
            // dd($ShopsGoodsRes);
            $ShopsGoodsResMix = [];
            foreach ($ShopsGoodsRes as $k) {
                $ShopsGoodsResMix[$k->category_id][$k->good_id][] =$k->shop_id;
            }
            // dd($ShopsGoodsResMix);
            //整合数据结构
            $categoryDetailsStru = [];
            $i = 0;
            foreach ($ShopsGoodsResMix as $category_id => $goods) {
                $categoryDetailsStru[$i]['category_id'] = $category_id;
                $j = 0;
                foreach ($goods as $good_id => $shop_ids) {
                    foreach ($shop_ids as $k => $shop_id) {
                        $categoryDetailsStru[$i]['goodList'][$j]['good_id'] = $good_id;
                        $categoryDetailsStru[$i]['goodList'][$j]['shop_id'] = $shop_id;
                        $j++;
                    }
                }
                $i++;
            }
            // dd($categoryDetailsStru);
            $categoryDetails = UserShopsShopGoods::shop_details($categoryDetailsStru);
            // dd($categoryDetails);
            return $this->success($categoryDetails);
        }
        return ['msg'=>'当前无商家'];

    }


    // 获取商品详情
    public function getGoodsDetail(Request $request)
    {
        //参数校验
        $validator = Validator::make($request->all(),
            [
                'gid' => 'required',
                'shop_id' => 'required',
            ],
            [
                'gid.required' => 'gid参数缺失',
                'shop_id.required' => 'shop_id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $shop_id = $request->shop_id;
        $gid = $request->gid;
        
        // $gid = 15;
        // $shop_id = 1;
        $whereShop['id'] = $shop_id;
        
        $productInfo = ShopProduct::where('goods_id',$gid)->get();
        $specification_info = [];
        if($productInfo){
            $specification_info = ShopGoods::getGoodsDetailByProduct($productInfo);
        }

        $outData['shopGoods'] = ShopGoods::where('id',$gid)->first() ? ShopGoods::where('id',$gid)->first()->toArray() : null;
      
      	$userShopGoodsDetails = UserShopsShopGoods::where([
            ['shop_id','=',$shop_id],
            ['good_id','=',$gid],
        ])->first();
        if(!$userShopGoodsDetails){
            return ['msg'=>'商品不存在'];
        }
        $UserShopProductStock = UserShopProduct::where([
            ['shop_id','=',$shop_id],
            ['goods_id','=',$gid],
        ])->first();
        $outData['shopGoods']['goods_number'] = $UserShopProductStock['stock'];
      	$outData['shopGoods']['sell_volume'] = $userShopGoodsDetails['sell_volume'];
        $outData['shopGoods']['specInfo'] = $specification_info;
        $outData['shopInfo'] = Usershop::where($whereShop)->first() ? Usershop::where($whereShop)->first()->toArray() : null;
        UserShop::where('id',$shop_id)->increment('page_views', 1);
        // dd($outData);
        return $this->success($outData);        
    }


    //推荐商品列表
    public function getGoodsHot(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                // 'district' => 'required',
                'area_id' => 'required',
            ],
            [
                'area_id.required' => '地址缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $area_id = $request->area_id;

        //$where[] = ['area_id','=',$area_id];
        $where[] = ['area_id','like','%' . $area_id . '%'];
        $where[] = ['state','=',1];  //在开店的商家
        $shopId_arr = UserShop::where($where)->pluck('id')->toArray(); //获取商家id数组
        if (!empty($shopId_arr)){
            // dd($shopId_arr);
            $ShopsGoodsRes = UserShopsShopGoods::whereIn('shop_id',$shopId_arr)->where('is_recommend',1)->orderBy('stock','asc')->pluck('shop_id','good_id')->toArray();//good_id=>shop_id

            if(empty($ShopsGoodsRes)){
                return ['msg'=>'附近无商品'];
            }
            // dd($ShopsGoodsRes);
            $recommendGoodsRes = [];
            $i = 0;
            foreach ($ShopsGoodsRes as $good_id => $shop_id) {
              	$shopInfo = UserShop::getShopInfoById($shop_id);
                $goodsInfo = ShopGoods::getGoodDetail($good_id);
              	if($shopInfo && $goodsInfo && ($goodsInfo['is_on_sale']!=0)){
                	$recommendGoodsRes[$i]['shopInfo'] = $shopInfo;
                	$recommendGoodsRes[$i]['goodsInfo'] = $goodsInfo;
                  	$recommendGoodsRes[$i]['goodsInfo']['sell_volume'] = UserShopsShopGoods::where([
                        ['good_id','=',$good_id],
                        ['shop_id','=',$shop_id],
                    ])->first()->sell_volume;
                	$i++;
                }
            }
            // dd($recommendGoodsRes);
            return $this->success($recommendGoodsRes);
        }

        return ['msg'=>'当前无商家'];
    }
  
    //根据团购商品规格获得产品
    public function getProductBySpec(Request $request)
    {
        //参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
                'goods_id' => 'required',
                'spec_ids' => 'required',
                'spec_item_ids' => 'required'
            ],
            [
                'shop_id.required' => 'shop_id参数缺失',
                'goods_id.required' => 'goods_id参数缺失',
                'spec_ids.required' => 'spec_ids参数缺失',
                'spec_item_ids.required' => 'spec_item_ids参数缺失'
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $shop_id = $request->shop_id;
        $goods_id = $request->goods_id;
        $spec_ids = $request->spec_ids;
        $spec_item_ids = $request->spec_item_ids;
        // $shop_id = 23;
        // $goods_id = 16;
        // $spec_ids = '4_6';
        // $spec_item_ids = '1_5';
        $whereShopProduct = [
            ['goods_id','=',$goods_id],
            ['goods_specification_ids','=',$spec_ids],
            ['goods_spec_item_ids','=',$spec_item_ids]
        ];
        $whereShopStock = [
            ['goods_id','=',$goods_id],
            ['shop_id','=',$shop_id],
            ['goods_spec_item_ids','=',$spec_item_ids]
        ];
        $reShopProduct = ShopProduct::where($whereShopProduct)->first();
        $reShopStock = UserShopProduct::where($whereShopStock)->first();
        $reArr = [];
        if($reShopProduct){
            $reArr['product_id'] = $reShopProduct->id;
        }else{
            return ['msg'=>'平台规格信息缺失','code'=>'401'];
        }
        if($reShopStock){
          	$reArr['price'] = $reShopStock->price;
            $reArr['stock'] = $reShopStock->stock;
        }else{
            return ['msg'=>'商家规格信息缺失','code'=>'401','data'=>$shop_id];
        }
        return $this->success($reArr); 
    }
}