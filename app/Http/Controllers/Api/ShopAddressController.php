<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Logic\AddressLogic;
use App\Models\ShopAddress;
use App\Models\ShopArea;
use App\Models\CityAddress;
use Illuminate\Support\Facades\DB;

class ShopAddressController extends ApiController
{

    // 收货地址列表
    public function addressList(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
            ],
            [
                'user_id.required' => 'id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $user_id = $request->user_id;

        $list = AddressLogic::getAddrList(['uid' => $user_id]);
        return $this->success($list);
    }

    // 收货地址详情
    public function addressDetail(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
            ],
            [
                'id.required' => 'id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $info = AddressLogic::getOneAddr($request->id);
        return $this->success($info);
    }

    // 保存收货地址
    public function addressSave(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'name' => 'required',
                'mobile' => 'required',
                'province_id' => 'required',
                'city_id' => 'required',
                'district_id' => 'required',
                'area_id' => 'required',
                'address' => 'required',
                'is_default' => 'required',
            ],
            [
                'user_id.required' => 'user_id参数缺失',
                'name.required' => '收货人参数缺失',
                'mobile.required' => '收货人手机号参数缺失',
                'province_id.required' => 'province_id参数缺失',
                'city_id.required' => 'city_id参数缺失',
                'district_id.required' => 'district_id参数缺失',
                'address.required' => '详细地址参数缺失',
                'is_default.required' => 'is_default参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        if ($request->id && $request->id > 0) {
            $model = ShopAddress::find($request->id);
        } else {
            $model = new ShopAddress();
        }
        $model->user_name = $request->name;
        $model->mobile = $request->mobile;
        $model->uid = $request->user_id;
        $model->country_id = 1;
        $model->country = '中国';
        $model->province_id = $request->province_id;
        $model->province = $request->province;
        $model->city_id = $request->city_id;
        $model->city = $request->city;
        $model->district_id = $request->district_id;
        $model->district = $request->district;
        $model->area_id = $request->area_id;
        $model->area_name = $request->area_name;
        $model->address = $request->address;
        //$model->is_default = intval($request->is_default);
      	$model->is_default = 1;
        $model->status = ShopAddress::STATUS_ON;
        // 开启事务
        DB::beginTransaction();
        try {
            DB::table('shop_address')
                ->where('is_default', ShopAddress::DEFAULT_ON)
                ->update(['is_default' => ShopAddress::DEFAULT_OFF]);
            $re = $model->save();
            DB::commit();
            return $this->message('操作成功');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failed('报错失败', 403);
        }

    }

    // 删除收货地址
    public function addressDelete(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
                'user_id'=> 'required',
            ],
            [
                'id.required' => 'id参数缺失',
                'user_id.required' => '用户id参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $uid = $request->user_id;
        
        $model = ShopAddress::find($request->id);
        $default = $model->is_default? 1 : 0;
        
        $re = $model->delete();
        if ($re) {
            if($default){ //
                $defaultAddress = ShopAddress::where('uid',$uid)->orderBy('created_at','desc')->first();
                if(!empty($defaultAddress)){
                    $defaultAddress->is_default = 1;
                    $defaultAddress->save();
                }
            }
            return $this->message('删除成功');
        }else{
            return $this->failed('删除失败');
        }
    }

    //直接设为默认地址
    public function addressDefault(Request $request)
    {
        if (empty(\Auth::user())) {
            return $this->failed('用户未登录', 401);
        }else{
            $user_id = \Auth::user()->id;
            $whereId = ['id'=>$request->id];
        }
        $res = ShopAddress::where([['uid','=',$user_id]])->update(['is_default'=>0]);

        if($res){
            $re = ShopAddress::where($whereId)->update(['is_default'=>1]);
            return $this->message('操作成功');
        }
        
    }

    //根据县区返回代理点
    public function getShopAreaByDistrict(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'district' => 'required',
            ],
            [
                'district.required' => 'district参数缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        
        $district['name'] = $request->district;
        $districtId = CityAddress::getDistrictId($district);

        $ShopArea = ShopArea::where([
            ['district','=',$districtId],
            ['status','=',1],
          	['lv','=',1]
        ]
        )->pluck('area_name','id');
        
      	if(!$ShopArea->first()){
            $ShopArea = ShopArea::where([
                ['lv','=',0],
            ]
            )->pluck('area_name','id');
            if(!$ShopArea->first()){
                return ['msg'=>'空空如也'];
            }
            $ShopAreaRes = [];
            $i = 0;
            foreach ($ShopArea as $area_id => $area_name) {
                $ShopAreaRes[$i]['area_id'] = $area_id;
                $ShopAreaRes[$i]['area_name'] = $area_name;
                $i++;
            }

            return $this->success($ShopAreaRes);
        }
      
        $ShopAreaRes = [];
        $i = 0;
        foreach ($ShopArea as $area_id => $area_name) {
            $ShopAreaRes[$i]['area_id'] = $area_id;
            $ShopAreaRes[$i]['area_name'] = $area_name;
            $i++;
        }

        return $this->success($ShopAreaRes);
    }


}