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
use App\Models\Carousel;
use Encore\Admin\Auth\Database\Administrator;

class UserShopController extends ApiController
{
    
    //商家列表页
    public function shopList(Request $request)
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

        //根据买家当前位置返回推荐
        $area_id = $request->area_id;
        

        if($area_id){
            $recommendShopList = UserShop::getShopList($area_id);
            return $this->success($recommendShopList);
        }
        return ['msg'=>'false'];
    }



    public function shopDetails(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                // 'district' => 'required',
                'shop_id' => 'required',
            ],
            [
                'shop_id.required' => 'shopid缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        //当前商铺
        $shop_id = $request->shop_id;
        UserShop::where('id',$shop_id)->increment('page_views', 1);

        $shop_res['shop_info'] = UserShop::where('id',$shop_id)->get()[0]->toArray();
      	$shop_res['shop_info']['code_pic'] = ($re = Administrator::where('bind_phone',$shop_res['shop_info']['bind_phone'])->first()) ? $re->code_pic : '';
        $shop_res['shop_goods_list'] = UserShopsShopGoods::shop_goods($shop_id);
        
        return $this->success($shop_res);

    }

}