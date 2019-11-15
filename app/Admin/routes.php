<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
  	
    $router->get('/', 'HomeController@index');
  	//团购管理
    $router->resource('shop-group', ShopGroupController::class);
  	//团购审核管理
    $router->resource('group-manage', GroupManageController::class);
  	// 后台团购审核
    $router->get('group-accept', 'GroupManageController@groupAccept');//审核同意
    $router->get('group-refuse', 'GroupManageController@groupRefuse');//审核驳回
  	//团购订单管理
    $router->resource('group-order-manage', GroupOrderManageController::class);
  	//营业额统计管理
    $router->resource('turnover-statistics', TurnoverStatisticsController::class);
  	$router->get('draw', 'TurnoverStatisticsController@draw');//统计提现功能
  	//用户信息收集（主要收集用户的二维码）
    $router->resource('user-collect', UserCollectController::class);
  	$router->get('collect-refuse', 'UserCollectController@collectRefuse');//用户申请驳回
    //商铺管理 
    $router->resource('user-shop', UserShopController::class);
  	$router->get('get-spec', 'UserShopController@getSpecByGid');//获取后台商品规格
    $router->get('get-spec-by-shop', 'UserShopController@getSpecByGidForShop');//获取商家商品规格信息
  	$router->get('get-picurl', 'UserShopController@getPicUrlById');//获取商品图片url
    //我的商铺
    $router->resource('my-shop', MyShopController::class);
  	//评论管理
    $router->resource('comment',CommentController::class);

    //市场区域管理
    $router->resource('shop-area', ShopAreaController::class);
  	$router->resource('area-tabs', AreaTabsController::class);//区域标签
    $router->get('select-district', 'ShopAreaController@district');
    $router->get('select-area', 'ShopAreaController@area');

    //首页轮播图管理
    $router->resource('index-carousel', IndexCarouselController::class);

    //轮播图管理
    $router->resource('carousel', CarouselController::class);
    // 导航管理
    $router->resource('nav', NavigationController::class);
    //专类管理
    $router->resource('special', SpecialController::class);
    //商品管理
    $router->resource('goods', GoodController::class);
    //商品分类管理
    $router->resource('classes', ClassController::class);

    // 前台用户管理
    $router->resource('User', UsersController::class);

    //新闻管理
    $router->resource('News', NewsController::class);

    //前端用户管理
    //$router->resource('front-users', FrontEndUserController::class);

    // 项目类型管理
    $router->resource('project-type', ProjectTypeController::class);

    // 项目功能分类管理
    $router->resource('project-func-type', ProjectFuncTypeController::class);

    // 功能模块管理
    $router->resource('project-model', ProjectModelController::class);

    // 功能点管理
    $router->resource('project-dot', ProjectFuncDotController::class);

    // 品牌管理
    $router->resource('shop-brand', BrandController::class);

    // 统计管理
    $router->resource('stat', StatController::class);

    // 
    $router->resource('module', ModuleController::class);
    $router->get('module-list', 'ModuleController@module_list')->name('module-list');


    Route::group(['namespace' => 'Module'], function (Router $router) {
        $router->resource('module-adv', AdvController::class);
        $router->resource('module-a', ModuleAController::class);
        $router->resource('module-b', ModuleBController::class);
        $router->resource('module-c', ModuleCController::class);
        $router->resource('module-e', ModuleEController::class);
        $router->resource('module-f', ModuleFController::class);
        $router->post('special_item_add', 'ModuleIndexController@special_item_add')->name('special_item_add');
        $router->post('special_item_del', 'ModuleIndexController@special_item_del')->name('special_item_del');
        $router->post('special_item_handle', 'ModuleIndexController@special_item_handle')->name('special_item_handle');
        $router->post('update_item_sort', 'ModuleIndexController@update_item_sort')->name('update_item_sort');
    });

    Route::post('uploadFile', 'UploadController@uploadImg')->name('admin-up');

    /********* shop相关********/
    Route::group(['namespace' => 'Shop'], function (Router $router) {
        // 商品分类管理
        $router->resource('shop-category', ShopCategoryController::class);
        // 规格管理
        $router->resource('shop-specification', ShopSpecificationController::class);

        // 属性分类管理
        $router->resource('shop-attribute-category', ShopAttributeCategoryController::class);
        // 属性管理
        $router->resource('shop-attribute', ShopAttributeController::class);

        // 优惠券管理
        $router->resource('shop-coupon', ShopCouponController::class);

        // 商品管理
        $router->get('select-goods', 'ShopGoodsController@goods');
        $router->get('select-goods-name', 'ShopGoodsController@goodsName');
      	$router->get('goods-delete', 'ShopGoodsController@goodsDelete');
      	$router->get('select-goods-price', 'ShopGoodsController@goodsPrice');//下拉列表选择价格
        $router->resource('shop-goods', ShopGoodsController::class);
        // 主题&专题管理
        $router->resource('shop-topics', ShopTopicController::class);
        // 商城订单管理
      	$router->get('order-accept', 'ShopOrderController@acceptOrder');//接单
        $router->get('order-refuse', 'ShopOrderController@refuseOrder');//拒单
      	$router->get('order-finish', 'ShopOrderController@finishOrder');//确认送达（订单完成）
      	$router->get('order-read', 'ShopOrderController@orderRead');//订单已阅
        $router->resource('shop-order', ShopOrderController::class);
      	$router->resource('shop-group-order', ShopGroupOrderController::class);//团购订单
        // 用户反馈
        $router->resource('shop-feedback', FeedbackController::class);
    });

});
