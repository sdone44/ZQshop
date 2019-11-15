<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\UserShop;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use Illuminate\Support\Facades\Validator;

class CollectionController extends ApiController
{
    /**
     * 添加收藏
     */ 
    public function collectionAdd(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'shop_id' => 'required',
                'good_id' => 'required',
            ],
            [
                'user_id.required' => '用户id缺失',
                'shop_id.required' => '商铺id缺失',
                'good_id.required' => '商品id缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $uid = $request->user_id;
        $shopId = $request->shop_id;
        $gid = $request->good_id;

        //收藏是否已存在
        $where['user_id'] = $uid;
        $where['shop_id'] = $shopId;
        $where['good_id'] = $gid;
        $test = Collection::where($where)->first();
        if ($test) {
            return ['msg'=>'该商品已收藏'];
        }


        $model = new Collection;
        $model->user_id = $uid;
        $model->shop_id = $shopId;
        $model->good_id = $gid;

        $re = $model->save();

        if ($re) {
            return ['msg'=>'添加收藏成功！','code'=>200];
        }
        return ['msg'=>'添加收藏失败','error'=>$re];

    }

    /**
     * 删除收藏
     */ 
    public function collectionDelete(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
            ],
            [
                'id.required' => '用户id缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $id = $request->id;

        $model = Collection::find($id);   

        $re = $model->delete();

        if ($re) {
            return ['msg'=>'删除收藏成功！'];
        }
        return ['msg'=>'删除收藏失败','error'=>$re];

    }

    /**
     * 获取收藏列表
     */
    public function collectionList(Request $request){
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
            ],
            [
                'user_id.required' => '参数缺失',
            ]
        );

        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $where['user_id'] = $request->user_id;

        $collectionInfo = Collection::where($where)->get();
        // dd($collectionInfo);
        if(empty($collectionInfo)){
            return null;
        }
        $userCollectionInfo = [];
        foreach ($collectionInfo as $k => $info) {
            $re = UserShopsShopGoods::where([
                ['shop_id','=',$info->shop_id],
                ['good_id','=',$info->good_id],
            ])->first(); //商家是否还存在该商品检测
            if($re){
                $userCollectionInfo[$k]['collectionId'] = $info->id;
                $userCollectionInfo[$k]['shopInfo'] = UserShop::getShopInfoById($info->shop_id);
                $userCollectionInfo[$k]['goodsInfo'] = ShopGoods::getGoodDetail($info->good_id);
            }
            
        }
        $re = array_values($userCollectionInfo); //键值重新排序
        // dd($userCollectionInfo);
        return $this->success($re);
    }



}