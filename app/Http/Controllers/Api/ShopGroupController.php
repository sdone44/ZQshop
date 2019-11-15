<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ShopGoodsLogic;
use App\Logic\ShopCommentLogic;
use App\Models\ShopBrand;
use App\Models\ShopGoods;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopCategory;
use App\Models\CityAddress;
use App\Models\ShopGroup;
use App\Models\ShopProduct;
use Encore\Admin\Auth\Database\Administrator;



class ShopGroupController extends ApiController
{
    
    //团购商品列表
    public function shopGroupList(Request $request)
    {

        //返回进行中的团购商品
        $shopGroupList = ShopGroup::getUnderwayGroup();
        if(count($shopGroupList)){
            $shopGroupListArr = $shopGroupList->toArray();
            $nowTime = time();
            foreach ($shopGroupList as $k => $groupInfo) {
                $shopGroupListArr[$k] = array_merge(ShopGoods::getGoodSimpleDetail($groupInfo['goods_id']),$shopGroupListArr[$k]);
                $shopGroupListArr[$k]['remaining_time'] = strtotime($groupInfo['end_time']) - $nowTime;
            }
            // dd($shopGroupListArr);
            $shopGroupListArrRes = [];
            foreach ($shopGroupListArr as $k => $groupInfo) {
                $shopGroupListArrRes[$groupInfo['category_id']]['groupInfo'][] = $groupInfo;
                if(!isset($shopGroupListArrRes[$groupInfo['category_id']]['name'])){
                    $shopGroupListArrRes[$groupInfo['category_id']]['name'] = ShopCategory::where('id',$groupInfo['category_id'])->first() ? ShopCategory::where('id',$groupInfo['category_id'])->first()->name : '无分类数据';
                }
            }
            // dd($shopGroupListArrRes);
            return $this->success($shopGroupListArrRes);
        }
        return ['msg'=>'暂无团购'];

    }

    //团购页轮播图
    public function shopGroupCarousel(Request $request)
    {

        //返回进行中的团购商品
        $shopGroupList = ShopGroup::getUnderwayHotGroup();
        if(count($shopGroupList)){
            $shopGroupListArr = $shopGroupList->toArray();
            $nowTime = time();
            foreach ($shopGroupList as $k => $groupInfo) {
                $shopGroupListArr[$k] = array_merge(ShopGoods::getGoodSimpleDetail($groupInfo['goods_id']),$shopGroupListArr[$k]);
                $shopGroupListArr[$k]['remaining_time'] = strtotime($groupInfo['end_time']) - $nowTime;
            }

            return $shopGroupListArr;
        }
        return ['msg'=>'暂无团购'];

    }

    //团购商品详情
    public function shopGroupDetail(Request $request)
    {
        //参数校验
        $validator = Validator::make($request->all(),
            [
                'goods_id' => 'required',
                'group_id' => 'required'
            ],
            [
                'goods_id.required' => 'goods_id参数缺失',
                'group_id.required' => 'group_id参数缺失'
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $groupId = $request->group_id;
        $gid = $request->goods_id;
        // $gid = 15;
        // $groupId = 11;
        $outData = ShopGroup::where('id',$groupId)->first() ? ShopGroup::where('id',$groupId)->first()->toArray() : null;
        if(!$outData){
            return ['msg'=>'团购不存在'];
        }
        $grouperInfo = Administrator::find($outData['uid']);
        if(!$grouperInfo){
            return ['msg'=>'该团不存在'];
        }
        $outData['code_pic'] = $grouperInfo->code_pic;
        $outData['grouper_name'] = $grouperInfo->name;
        $outData['avatar'] = $grouperInfo->avatar;

        $nowTime = time();
        $endTime = strtotime($outData['end_time']);
        $remainingTime = $endTime - $nowTime > 0 ? $endTime - $nowTime : 0;
        $outData['remaining_time'] = $remainingTime;
        $productInfo = ShopProduct::where('goods_id',$gid)->get();
        $specification_info = [];
        if($productInfo){
            $specification_info = ShopGoods::getGoodsDetailByProduct($productInfo);
        }
        $outData['shopGoods'] = ShopGoods::where('id',$gid)->first() ? ShopGoods::where('id',$gid)->first()->toArray() : null;
        $outData['shopGoods']['specInfo'] = $specification_info;
        
        return $this->success($outData);        
    }

}