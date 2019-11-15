<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('Api')->group(function () {
    // 在 "App\Http\Controllers\Api" 命名空间下的控制器
    Route::any('/order-notify', 'PaymentController@notify');
    //消息推送token验证
  	Route::any('/valid','IndexController@valid'); 
  	Route::any('/send-message','ShopOrderController@sendMessage'); //下单成功发送模板消息


    // 项目估价json
    Route::any('/project_json/types', 'ProjectJsonController@getProjectTypes');
    Route::any('/project_json/func-types', 'ProjectJsonController@getProjectFuncTypesByTypeId');
    Route::any('/project_json/models', 'ProjectJsonController@getProjectModelsByFunctypeId');

    // 微信相关
    Route::get('/getwxacodeunlimit', 'WeixinController@getwxacodeunlimit');
	Route::get('/goods-count', 'ShopGoodsController@getGoodsCount');// 统计商品总数
  	Route::get('/index-carousel','IndexController@index_carousel');//首页轮播图

	/*
    * 游客模式不需要用户信息也能浏览的
    */
  
  	//首页搜索
    Route::get('/index-search','IndexController@index_search');//首页搜索
  	//商铺相关
    Route::get('/shop-list','UserShopController@shopList');//商铺列表
    Route::get('/shop-details','UserShopController@shopDetails');//商铺详情
  	//团购商品
  	Route::get('/group-list','ShopGroupController@shopGroupList');//团购商品列表
    Route::get('/group-detail','ShopGroupController@shopGroupDetail');//团购商品列表
    Route::get('/group-carousel','ShopGroupController@shopGroupCarousel');//团购商品轮播
  
  	// 商品相关处理
    Route::get('/goods-count', 'ShopGoodsController@getGoodsCount');// 统计商品总数
    Route::get('/goods-list', 'ShopGoodsController@getGoodsList');// 获得商品列表
    Route::get('/goods-category', 'ShopGoodsController@getGoodsCategory');// 获得分类数据
    Route::get('/goods-detail', 'ShopGoodsController@getGoodsDetail');//获得商品的详情
    Route::get('/goods-hot','ShopGoodsController@getGoodsHot');//推荐商品
    Route::get('/shop-goods-category','ShopGoodsController@getShopGoodsCategory');// 获得商品列表
  	Route::get('/group-product','ShopGoodsController@getProductBySpec');//团购根据商品规格获得产品
  	//购物车
    Route::get('/cart-index', 'ShopCartController@index');//获取购物车的数据
  	
  	//订单
  	Route::get('/notify-order','MyShopController@notifyOrder');//新订单提醒
  
  	//地区地址
  	Route::get('/shop-area','ShopAddressController@getShopAreaByDistrict');//根据县区返回代理点
  	
  
    // 需要用户信息的
    Route::middleware('auth:api')->group(function () {
      	//用户信息收集
        Route::post('/user-collect','UserCollectController@UserCollectCommit');//用户信息申请
        Route::get('/user-collect-result','UserCollectController@UserCollectResult');//用户审核结果
      
      	//二维码生成
      	Route::get('/qrcode','IndexController@qrcode');//二维码生成
      
      	//我的商铺
      	Route::get('/my-shop','MyShopController@myShop');//我的商铺
        Route::get('/my-shop-order','MyShopController@myShopOrder');//我的商铺订单
      	Route::get('/my-shop-orderDetail','MyShopController@myShopOrderDetail');//我的商铺订单详情
      	Route::post('/my-shop-orderReceived','MyShopController@myShopOrderReceived');//接单
      	Route::post('/my-shop-orderComplete','MyShopController@myShopOrderComplete');//确认送达
      	Route::post('/my-shop-openSetting','MyShopController@myShopOpenSetting');//我的商铺营业设置
      
      	//手机绑定
      	Route::any('/get-verification','IndexController@get_verification'); //获取验证码
        Route::any('/check-verification','IndexController@check_verification'); //确认验证码绑定手机
      	
      
        // 砍价相关的
        Route::get('/bargain-list', 'BargainController@bargainList');
        Route::get('/bargain-goods-detail', 'BargainController@bargainGoodsDetail');
        Route::post('/bargain-help', 'BargainController@bargainHandleHelp');
        Route::get('/bargain-help-detail', 'BargainController@bargainHelpDetail');
        Route::get('/bargain-detail', 'BargainController@bargainDetail');

        Route::get('/project-type-json', 'ProjectElementController@getProjectType');
        Route::get('/project-goods', 'ProjectElementController@getProjectGoods');
        Route::post('/project-goods-transform', 'ProjectElementController@transform');

        // 主题&专题
        Route::get('/topic-list', 'ShopTopicController@getTopicList');
        Route::get('/topic-detail', 'ShopTopicController@getTopicDetail');

        // 分类目录
        Route::get('/catalog-index', 'ShopCategoryController@getCatalogIndex');
        Route::get('/catalog-current', 'ShopCategoryController@getCatalogCurrent');

        
      
        Route::get('/goods-related', 'ShopGoodsController@getGoodsRelated');//商品详情页的关联商品（大家都在看）
        Route::get('/goods-new', 'ShopGoodsController@getGoodsNew');// 新品
        

        // 收藏相关
        //Route::post('/collect-addordelete', 'ShopCollectController@addordelete');// 添加或取消收藏
        //Route::get('/collect-list', 'ShopCollectController@getList');// 获取收藏列表
      	Route::post('/collection-add','CollectionController@collectionAdd');// 添加收藏
        Route::post('/collection-delete','CollectionController@collectionDelete');// 删除收藏
        Route::get('/collection-list','CollectionController@collectionList');// 收藏列表


        // 品牌相关
        Route::get('/brand-detail', 'ShopBrandController@getDetail');// 获取品牌详情

        // 购物车相关处理
        Route::post('/cart-add', 'ShopCartController@add');//添加商品到购物车
        Route::post('/cart-update', 'ShopCartController@update');//更新购物车的商品
        Route::post('/cart-delete', 'ShopCartController@delete');//删除购物车的商品
        Route::post('/cart-checked', 'ShopCartController@checked');//选择或取消选择商品
        Route::get('/cart-goodscount', 'ShopCartController@goodsCount');//获取购物车商品件数
      	Route::post('/cart-check-all','ShopCartController@checkAll');//选择或取消选择商品

        // 下单相关
        Route::get('/cart-checkout', 'ShopOrderController@checkout');//下单前信息确认
        Route::post('/pay-now', 'ShopOrderController@payNow');//立即购买
        Route::post('/order-submit', 'ShopOrderController@orderSubmit');//下单
        Route::get('/pay-prepay', 'PaymentController@toPay');// 支付
      	Route::get('/order-refund','PaymentController@orderRefund');//订单退款
        Route::get('/order-refund-res','PaymentController@orderRefundRes');//订单退款结果


        // 下单收货地址管理
        //Route::get('/region-list', 'ShopRegionController@regionList');// 获取区域列表

        Route::get('/address-list', 'ShopAddressController@addressList');// 收货地址列表
        Route::get('/address-detail', 'ShopAddressController@addressDetail');// 收货地址详情
        Route::post('/address-save', 'ShopAddressController@addressSave');// 保存收货地址
        Route::post('/address-delete', 'ShopAddressController@addressDelete');// 删除收货地址
		Route::post('/address-default','ShopAddressController@addressDefault');// 直接设为默认地址
      	
      	
      
        //我的订单相关
        Route::get('/order-list', 'MyOrderController@orderList');// 订单列表
        Route::get('/order-detail', 'MyOrderController@orderDetail');// 订单详情
        Route::get('/order-express', 'MyOrderController@orderExpress');// 物流详情
      	Route::post('/order-cancel','ShopOrderController@orderCancel');//订单取消
      	Route::post('/order-user-refund','MyOrderController@orderUserRefund');// 用户取消已付款订单
      
      	

        // 领券中心
        Route::get('/coupon-center', 'ShopCouponController@getCouponList');
        // 领券中心
        Route::get('/coupon-mine', 'ShopCouponController@getMyCouponList');
        // 领券
        Route::post('/coupon-get', 'ShopCouponController@getCoupon');

        // 评论相关的
        Route::get('/comment-list', 'ShopCommentController@getCommentList');// 评论列表
        Route::get('/comment-count', 'ShopCommentController@getCommentCount');//评论总数
        Route::post('/comment-post', 'ShopCommentController@commentAdd');// 发表评论

        // 用户反馈相关的
        Route::get('/feedback-datalist', 'ShopFeedbackController@getDataList');// 获取反馈选项信息
        Route::post('/feedback-handle', 'ShopFeedbackController@feedbackHandle');// 反馈

        // 用户足迹相关
     	Route::get('/user-info','IndexController@userInfo');//用户信息
        Route::get('/footprint-list', 'ShopFootprintController@getList');// 获取足迹列表
      
      	//评论相关
      	Route::post('/user-comment','CommentController@userComment');//用户评论
        Route::get('/comment-list','CommentController@commentList');//用户列表


    });
  
    Route::any('/login', 'AuthenticateController@auto_login')->name('login');
});

