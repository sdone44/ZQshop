<?php

namespace App\Http\Controllers\Api;

use App\Models\Carousel;
use App\Models\IndexCarousel;
use App\Models\Special;
use App\Models\ShopTopic;
use App\Models\UserAddress;
use App\Models\CityAddress;
use App\Models\UserShop;
use App\Models\UserShopsShopGoods;
use App\Models\ShopGoods;
use App\Logic\ShopGoodsLogic;
use App\Http\Resources\ShopTopic as ShopTopicResource;
use App\User;
use Illuminate\Http\Request;
use App\Common\SignatureHelper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use EasyWeChat\Factory;

class IndexController extends ApiController
{
    
    public function index_search(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'district' => 'required',
                'keyWord' => 'required',
            ],
            [
                'district.required' => '地址缺失',
                'keyWord.required' => '关键词缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $district['name'] = $request->district;
        $keyWord = $request->keyWord;
        //地址解析
        $addressId = CityAddress::getDistrictId($district);
        if (empty($addressId)) {
            return ['msg'=>'此地区无代理商'];
        }
        //当前地址有哪些商家
        $shopIdsRes = UserShop::getShopIdsByDistrict($addressId);
        // dd($shopRes);
        if (empty($shopIdsRes)) {
            return ['msg'=>'附近没有商家'];
        }
        //根据商家商品表返回结果集
        $shopGoodsRes = UserShopsShopGoods::whereIn('shop_id',$shopIdsRes)->where('goods_name','like','%'.$keyWord.'%')->get();
        if (empty($shopGoodsRes)) {
            return ['msg'=>'无该商品'];
        }
        // dd($shopGoodsRes);
        $searchRes = [];
        foreach ($shopGoodsRes as $k => $shopGoods) {
            $searchRes[$k]['goodsInfo'] = ShopGoods::getGoodDetail($shopGoods->good_id);
            $searchRes[$k]['shopInfo'] = UserShop::getShopInfoById($shopGoods->shop_id);
        }
        // dd($searchRes);
        return $this->success($searchRes);

    }

    public function index()
    {
        // 先获取当前登录的用户信息

        if (empty(\Auth::user())) {
            return $this->failed('用户未登录', 401);
        }else{
            $user_id = \Auth::user()->id;
        }
        $outData = [];
        // 专题导航信息
        $outData['specialList'] = Special::getSpecialList();
        // 首页轮播
        $outData['carouselInfo'] = Carousel::getCarouselByType(Carousel::BOOTH_TYPE_HOME);

        // 热门
        $outData['hotGoodsList'] = ShopGoodsLogic::getGoodsList(['is_hot' => 1], 6);

        // 新品
        $outData['newGoodsList'] = ShopGoodsLogic::getGoodsList(['is_new' => 1], 4);

        // 新品
        $outData['topicList'] = ShopTopicResource::collection(ShopTopic::getTopicListByPage())->additional(['code' => 200]);
        return $this->success($outData);
    }


    //token消息验证
    public function valid(Request $request)
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "zqgzs";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature && $echoStr ){
            echo $echoStr ;
            exit;
        }else {
            echo "";
        }
    }

    public function randomkeys($length)
    {
        $info="";
        $pattern = '1234567890';
        for($i=0;$i<$length;$i++) {
            $info .= $pattern{mt_rand(0,9)};    //生成php随机数
        }
        return $info;
    }

    //手机绑定(获取验证码)
    public function get_verification(Request $request)
    {
        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'phone' => 'required',
            ],
            [
                'user_id.required' => '用户id缺失',
                'phone.required' => '手机号缺失',
            ]
        );

        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $randCode = Redis::get($request->phone);
        if(!empty($randCode)){
            return ['msg'=>'请勿重复操作'];
        }

        $phone = $request->phone;
        $userId = $request->user_id;

        $is_bind_phone = User::where('phone',$phone)->pluck('phone')->toArray();
        if(!empty($is_bind_phone[0])){
            return ['msg'=>'该手机已绑定'];
        }   

        $is_phone = User::where('id',$userId)->pluck('phone')->toArray();
        // dd($is_phone);
        if (empty($is_phone)) {
            return ['msg'=>'用户不存在'];
        }
        if(!empty($is_phone[0])){
            return ['msg'=>'用户已绑定手机'];
        }        

        $randCode = $this->randomkeys(6); //随机生成6位验证码
        Redis::setex($phone, 300, $randCode);//验证信息存入redis,300s有效
        $params = array(
            'PhoneNumbers' => $phone,
            'SignName' => '智趣工作室',
            'TemplateCode' => 'SMS_164385116',
            'TemplateParam' => json_encode(array('code' => $randCode)),
            'RegionId' => 'cn-guangzhou',
            'Action' => 'SendSms',
            'Version' => '2017-05-25'//不能改
        );
        // var_dump($params);die;
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $result = $helper->request(
            'LTAI6powhdQzvT5W', //accesskey
            'DiepkyHJRY2NmZSrg3UXuHFXoIYXBC', //secret
            'dysmsapi.aliyuncs.com', 
            $params,
            'false'
        );
        // return json_encode($result);
        if($result){
            if($result->Code == 'OK'){
                return json_encode($result);
            }else{
                return  ['Code'=>false,'msg'=>$result];
            }
        }else{
            return ['Code'=>'404','msg'=>'短信服务器返回空'];
        }        
    }

    //验证码确认
    public function check_verification(Request $request)
    {

        // 参数校验
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
                'phone' => 'required',
                'verification_code' => 'required',
            ],
            [
                'user_id.required' => '用户id缺失',
                'phone.required' => '手机号缺失',
                'verification_code.required' => '验证码缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        //验证码是否存在
        $randCode = Redis::get($request->phone);
        if(empty($randCode)){
            return ['msg'=>'请先获取验证码'];
        }

        $phone = $request->phone;
        $userId = $request->user_id;
        $verificationCode = $request->verification_code;

        $is_phone = User::where('id',$userId)->pluck('phone')->toArray();
        if (empty($is_phone)) {
            return ['msg'=>'用户不存在'];
        }
        if(!empty($is_phone[0])){
            return ['msg'=>'手机已绑定'];
        }

        if($randCode == $verificationCode){
            $re = User::where('id',$userId)->update(['phone'=>$phone]);
            if($re){
                return ['msg'=>'绑定成功'];
            }else{
                return ['msg'=>'绑定失败'];
            }
            
        }else{
            return  ['Code'=>false,'msg'=>'验证码错误！'];
        }
    }

    //添加地址
    public function address_save(Request $request)
    {
        
        $json = $request->userAddress[0];
        // return $json;

        $newUserAddress = $json;

        $userAddress = new UserAddress;
        $userAddress->user_id = $newUserAddress['user_id'];
        $userAddress->area_info = $newUserAddress['area_info'];
        $userAddress->address = $newUserAddress['address'];
        $userAddress->phone = $newUserAddress['phone'];
        $userAddress->contact = $newUserAddress['contact'];
        $userAddress->default_address = $newUserAddress['default_address'];
        if($userAddress->default_address){//默认地址修改
            $userAddress->where([
                ['default_address','=',1],
                ['user_id','=',$userAddress->user_id]
            ])->update(['default_address'=>0]);
        }

        $res = $userAddress->save();

        if($res){
            return ['Code'=>'OK','msg'=>'地址保存成功'];
        }else{
            return  ['Code'=>false,'msg'=>'保存失败'];
        }
    }

    //查看地址列表
    public function address_list(Request $request)
    {

        $where = ['user_id'=>$request->user_id];
        $addressList = UserAddress::where($where)->latest()->orderBy('default_address','desc')->get();

        return json_encode($addressList);
    }

    //地址详情
    public function address_details(Request $request)
    {
        $id = $request->id;
        $addressDetails = UserAddress::where('id',$id)->first();
        if ($addressDetails) {
            return json_encode($addressDetails);
        }else{
            return  ['Code'=>false,'msg'=>'请求失败'];
        }
        
    }

    //执行修改地址
    public function address_update(Request $request)
    {

        $condition = ['area_info'=>$request->area_info,'address'=>$request->address,'phone'=>$request->phone,'contact'=>$request->contact,'default_address'=>$request->default_address];
        if($request->default_address){//默认地址修改
            UserAddress::where([
                ['default_address','=',1],
                ['user_id','=',$request->user_id]
            ])->update(['default_address'=>0]);
        }
        $res = UserAddress::where('id',$request->id)->update($condition);
        if ($res) {
            return ['Code'=>'OK','msg'=>'修改成功'];
        }else{
            return  ['Code'=>false,'msg'=>'请求失败'];
        }
    }

    //删除地址
    public function address_delete(Request $request)
    {

        $where = ['id'=>$request->id];
        
        if($request->default_address){//如果删掉了默认地址
            $newDate = UserAddress::where('user_id',$request->user_id)->pluck('created_at')->max();
            $res = UserAddress::where([
                ['user_id','=',$request->user_id],
                ['created_at','=',$newDate]
            ])->update(['default_address'=>1]);
        }
        $res = UserAddress::where($where)->delete();
        if ($res) {
            return ['Code'=>'OK','msg'=>'删除成功'];
        }else{
            return  ['Code'=>false,'msg'=>'请求失败'];
        }
    }

    //首页轮播图
    public function index_carousel(Request $request)
    {

        $data = IndexCarousel::where('states',1)->orderBy('sort_order', 'desc')->get()->toArray();

        return $this->success($data);
    }

    //个人中心
    public function userInfo(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'user_id' => 'required',
            ],
            [
                'user_id.required' => '用户id缺失',
            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }
        $userId = $request->user_id;
        $userInfo = User::find($userId);

        return $this->success($userInfo);
    }
  
  	//二维码生成
  	public function qrcode(Request $request)
    {
      	$app = Factory::miniProgram(config('wechat.payment.default'));
      
      	$response = $app->app_code->getQrCode('/path/to/page');
      
      	return $response;
        
    }
}
