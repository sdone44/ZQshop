<?php

namespace App\Admin\Controllers;

use App\Models\UserShop;
use App\Models\ShopArea;
use App\Models\ShopCategory;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\ShopProduct;
use App\Models\UserShopProduct;
use Encore\Admin\Auth\Database\Administrator;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Auth;


class MyShopController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {

        return Admin::content(function (Content $content) {
            $userInfo = Auth::guard('admin')->user()->toArray();
            $isExistShop = $userInfo['shop_id'];

            $content->header('我的商铺');
            $content->description('商铺管理');
            if($isExistShop){
                $content->body($this->grid());
            }else{
                $content->row(function (Row $row) {

                    $row->column(12, function (Column $column) {
                        $column->append(Dashboard::shopGuide());
                    });

                });
            }
            
        });
    }

    /**
     * Show interface.
     *
     * @param $id
     * @return Content
     */
    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('商铺详情');
            $content->description('商铺详情');

            $content->body(Admin::show(UserShop::findOrFail($id), function (Show $show) {

                $show->id();

                $show->id('ID')->sortable();
                $show->shop_icon('商家图标')->image('',70,70);
                $show->shop_name('商铺名');
                $show->shop_summary('商家简介');
                $show->phone('联系电话');
                $show->shop_address('商家地址');
                // $show->panel()
                // ->tools(function ($tools) {
                //     $tools->disableEdit();
                //     $tools->disableList();
                //     $tools->disableDelete();
                // });
            }));
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {

        return Admin::content(function (Content $content) use ($id) {
            $userInfo = Auth::guard('admin')->user()->toArray();
            $shopId = $userInfo['shop_id'];
            if($shopId==$id){
                $content->header('商铺编辑');
                $content->description('商铺信息商品编辑');
                $content->body($this->form()->edit($id));
            }else{
                $content->header('您没有权限！');
            }

                
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $userInfo = Auth::guard('admin')->user()->toArray();
            $isExistShop = $userInfo['shop_id'];
            if(!$isExistShop){
                $content->header('Create');
                $content->description('description');

                $content->body($this->form());

            }else{
                $error = new MessageBag([
                    'title'   => '您没有权限！',
                    'message' => '您已经创建了商铺！',
                ]);

                return back()->with(compact('error'));
            }
                
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        return Admin::grid(UserShop::class, function (Grid $grid) {
           /*
            * 数据来源
            */
            $userInfo = Auth::guard('admin')->user()->toArray();
            $bindPhone = $userInfo['bind_phone'];
            $isExistShop = $userInfo['shop_id'];
            $grid->model()->where('id', '=', $isExistShop);

           /*
            *功能显示控制
            */
            $grid->disableCreateButton();
            $grid->disablePagination();
            $grid->disableExport();
            $grid->disableFilter();
            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                // $actions->disableEdit();
                $actions->disableView();
            });

            if(!empty($isExistShop)){
                // $grid->id('ID')->sortable();
                $grid->shop_icon('商铺图标')->image('',70,70);
                $grid->shop_name('商铺名');
                // $grid->area_id('服务区域')
                //     ->select(ShopArea::getAllClasses(true));
                // $district = $this;
                $grid->shop_type('商户类型')->display(function ($type) {
                    // dd($this->area_id);
                    $typeRes = '';
                    if($type==1){
                        $typeRes = '平台商户';
                    }else if($type==2){
                        $typeRes = '自营商户';
                    }else{
                        $typeRes = '其他';
                    }
                    return $typeRes;
                })->label();
                $grid->district('商铺地区')->display(function ($district) {
                    // dd($this->area_id);
                    return CityAddress::getAddress($this->district);
                });
                
                // $area = $this;
                $grid->area_id('代理点')->display(function ($areaId) {
                    // dd($this);
                    return ShopArea::getAreaName($areaId);
                });
                $states = [
                    'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                    'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
                ];
                $grid->state('营业状态')->switch($states);

                $getListImg = $this;

                $grid->column('更多信息')->expand(function () use($getListImg) {
                    $res = UserShopsShopGoods::shop_good($this->id);
 
                    $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
                    $row_arr1 = [
                        // [
                        //     '商品主图：' . sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$this->shop_icon),
                        // ],
                        [
                            '商铺地址：' . $this->shop_address,
                        ],
                        [
                            '联系人：' . $this->user_name,
                        ],
                        [
                            '联系电话：' . $this->phone,
                        ],
                    ];
                    $table = new Table(['更多信息'], $row_arr1);
                    $tab = new Tab();
                    $tab->add('商铺基础信息', $table);

                    $box = new Box('商铺简介',$this->shop_summary);
                    $tab->add('商铺描述', $box);

                    $goods = new Table(['代理商品'],$res);
                    $tab->add('代理商品', $goods);

                    return $tab;
                }, '更多信息');
            }else{
                $grid->disableActions();
                $grid->column('您还没有自己的商铺！');
            }
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(UserShop::class, function (Form $form) {
            //当前商家用户信息
          	$form->tools(function (Form\Tools $tools) {
                $tools->disableDelete();
                $tools->disableView();
                //$tools->disableList();
            });
            $userInfo = Auth::guard('admin')->user()->toArray();
            $isExistShop = $userInfo['shop_id'];
            $userType = $userInfo['user_type'];
			//js
          	$append = '
            <div class=\"form-group\">
                <label  class=\"col-sm-2  control-label\">规格组合</label>
                <div class=\"col-sm-8\">
                    <div class=\"box\">
                        <div class=\"box-body no-padding\">
                            <table class=\"table table-condensed\">
                                <tr>
                                    <th>规格组合</th>
                                    <th>库存</th>
                                </tr>
                                <tbody id=\"spec_list_show\">
                                    值
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            ';
            $script = '
            $(document).ready(function(){

                //自定义css（隐藏商品名备注）
                $("label:contains(\"备注\")").parent().css("display","none");
                $(".add.btn.btn-success.btn-sm").click(function(){
                    setTimeout(function(){
                        $("label:contains(\"备注\")").parent().css("display","none");
                    },1 );
                    
                });

                //读取页面时加载商品规格信息
                // console.log($(".id").val());
                shop_id = $(".id").val()
                k = 0;
                $("label:contains(\"商品名\")+.col-sm-8 .user_shops_shop_goods.good_id").each(function(){
                    console.log($(this).val());

                    gid = $(this).val();
                    var node = $(this).parents(".form-group");

                    $.ajax({
                            type : "GET",
                            url : "/admin/get-spec-by-shop",
                            dataType : "json",
                            data : {
                                "gid":gid,
                                "shop_id":shop_id,
                            },
                            success : function(re) {
                                //console.log("返回结果re为"+re);
                                k++;
                                if(re != 0){
                                    //console.log("长度为"+re.length);
                                    l = re.length;
                                    //console.log(re);
                                    tr = "";
                                    for(i=0;i<l;i++){

                                        tr += "<tr>"+
                                                "<td>"+re[i].goods_spec_item_names+"<\/td>"+
                                                "<td>"+
                                                    "<div class=\"input-group\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][id]\" value=\""+re[i].id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][shop_id]\" value=\""+re[i].shop_id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][goods_id]\" value=\""+re[i].goods_id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][product_id]\" value=\""+re[i].product_id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][goods_spec_item_names]\" value=\""+re[i].goods_spec_item_names+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+"a"+k+i+"][goods_spec_item_ids]\" value=\""+re[i].goods_spec_item_ids+"\">"+
                                                        "<span class=\"input-group-addon\">￥<\/span>"+
                                                        "<input type=\"text\" readonly class=\"form-control\" name=\"user_shop_products["+"a"+k+i+"][price]\" value=\""+re[i].price+"\">"+
                                                        "<span class=\"input-group-addon\">元<\/span>"+
                                                        "<\/div><\/td><td><div class=\"input-group\">"+
                                                        "<input type=\"text\" class=\"form-control\"  name=\"user_shop_products["+"a"+k+i+"][stock]\" value=\""+re[i].stock+"\" >"+
                                                        "<span class=\"input-group-addon\">件<\/span><\/div><\/td><td><div class=\"input-group\">"+
                                                "<\/td>"+
                                              "<\/tr>";
                                    }                                    
                                }else{
                                    
                                }
                                node.after("<div class=\"form-group spec\">"+
                                                                            "<label  class=\"col-sm-2  control-label\">规格组合<\/label>"+
                                                                            "<div class=\"col-sm-8\">"+
                                                                                "<div class=\"box\">"+
                                                                                    "<div class=\"box-body no-padding\">"+
                                                                                        "<table class=\"table table-condensed\">"+
                                                                                            "<tr><th>规格名<\/th><th>价格</th><th>库存<\/th><\/tr>"+
                                                                                            "<tbody id=\"spec_list_show\">"+tr+"<\/tbody>"+
                                                                                        "<\/table>"+
                                                                                    "<\/div>"+
                                                                                "<\/div>"+
                                                                            "<\/div>"+
                                                                         "<\/div>");
                            }
                    });

                });

                //下拉框加载商品规格信息
                j = 0;
                $("body").on("change","label:contains(\"商品名\")+.col-sm-8 .user_shops_shop_goods.good_id",function(){
                    j++;
                    gid = $(this).val();
                    if (!gid && typeof(gid)!="undefined" && gid!=0){ 
                        return false;
                    }

                    node = $(this).parents(".form-group");
                                
                    $.ajax({
                            type : "GET",
                            url : "/admin/get-spec",
                            dataType : "json",
                            data : {
                                "gid":gid,
                            },
                            success : function(re) {
                                //console.log(re);
                                if(re.data != 0){ //存在规格属性
                                    //console.log(re.data.length);
                                    l = re.data.length;
                                    tr = "";
                                    for(i=0;i<l;i++){
                                        tr += "<tr>"+
                                                "<td>"+re.data[i].goods_spec_item_names+"<\/td>"+
                                                "<td>"+
                                                    "<div class=\"input-group\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][id]\" value=\"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][shop_id]\" value=\"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_id]\" value=\""+re.data[i].goods_id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][product_id]\" value=\""+re.data[i].id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_spec_item_names]\" value=\""+re.data[i].goods_spec_item_names+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_spec_item_ids]\" value=\""+re.data[i].goods_spec_item_ids+"\">"+
                                                        "<span class=\"input-group-addon\">￥<\/span>"+
                                                        "<input type=\"text\" readonly class=\"form-control\" name=\"user_shop_products["+j+i+"][price]\" value=\""+re.data[i].retail_price+"\">"+
                                                        "<span class=\"input-group-addon\">元<\/span>"+
                                                        "<\/div><\/td><td><div class=\"input-group\">"+
                                                        "<input type=\"text\" class=\"form-control\"  name=\"user_shop_products["+j+i+"][stock]\" value=\"0\" >"+
                                                        "<span class=\"input-group-addon\">件<\/span><\/div><\/td><td><div class=\"input-group\">"+
                                                "<\/td>"+
                                              "<\/tr>";
                                    }
                                }else{ //不存在规格属性
                                    tr += "<tr>"+
                                                "<td>"+re.data[i].goods_spec_item_names+"<\/td>"+
                                                "<td>"+
                                                    "<div class=\"input-group\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][id]\" value=\"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][shop_id]\" value=\"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_id]\" value=\""+re.data[i].goods_id+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][product_id]\" value=\"0\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_spec_item_names]\" value=\""+re.data[i].goods_spec_item_names+"\">"+
                                                        "<input type=\"hidden\" name=\"user_shop_products["+j+i+"][goods_spec_item_ids]\" value=\""+re.data[i].goods_spec_item_ids+"\">"+
                                                        "<span class=\"input-group-addon\">￥<\/span>"+
                                                        "<input type=\"text\" readonly class=\"form-control\" name=\"user_shop_products["+j+i+"][price]\" value=\""+re.data[i].retail_price+"\">"+
                                                        "<span class=\"input-group-addon\">元<\/span>"+
                                                        "<\/div><\/td><td><div class=\"input-group\">"+
                                                        "<input type=\"text\" class=\"form-control\"  name=\"user_shop_products["+j+i+"][stock]\" value=\"0\" >"+
                                                        "<span class=\"input-group-addon\">件<\/span><\/div><\/td><td><div class=\"input-group\">"+
                                                "<\/td>"+
                                              "<\/tr>";
                                }
                                node.prev(".form-group").prev(".form-group").children(".col-sm-8").find("img").attr("src", re.picUrl);
                                node.prev(".form-group").prev(".form-group").children(".col-sm-8").find("img").css({"margin": "8px","height": "160px","border": "1px solid #ddd","box-shadow": "1px 1px 5px 0 #a2958a","padding": "6px"});;
                                node.next(".spec").remove();
                                node.after("<div class=\"form-group spec\">"+
                                                                        "<label  class=\"col-sm-2  control-label\">规格组合<\/label>"+
                                                                        "<div class=\"col-sm-8\">"+
                                                                            "<div class=\"box\">"+
                                                                                "<div class=\"box-body no-padding\">"+
                                                                                    "<table class=\"table table-condensed\">"+
                                                                                        "<tr><th>规格名<\/th><th>价格</th><th>库存<\/th><\/tr>"+
                                                                                        "<tbody id=\"spec_list_show\">"+tr+"<\/tbody>"+
                                                                                    "<\/table>"+
                                                                                "<\/div>"+
                                                                            "<\/div>"+
                                                                        "<\/div>"+
                                                                     "<\/div>");
                                
                            }
                    });

                                
                    
                });
            });
            ';
            $form->hidden('id', 'ID');
            $form->text('user_name', '联系人')
                ->rules('required');
            $form->text('shop_name', '商铺名')
                ->rules('required');
            $form->image('shop_icon', '商铺图标')
                // ->rules('required')
                ->uniqueName()->help('建议300*300');
            $form->textarea('shop_summary', '商家简介');
            $form->multipleImage('shop_picture', '商铺图片')
                ->uniqueName()
                ->removable();
            $form->text('phone', '商家电话')
                ->rules('required');
          
            $form->select('city','城市')->options(['1'=>'广州市'])->load('district', '/admin/select-district')->help('重新选择请先点击右边 x');

            $form->select('district','区/县')->options(function($id){
                return CityAddress::where('id', $id)->pluck('name', 'id');
            })->load('area_id','/admin/select-area');

            $form->multipleSelect('area_id', '服务区域')
                ->rules('required')
                ->options(function($id){
                return ShopArea::getAreaNameByAreaId($id);
            });
          
            
            $form->text('shop_address', '商家地址')
                ->rules('required');
            $states = [
                'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
            ];
            $form->currency('began_price', '起送价')->symbol('￥');
            $form->hidden('shop_type');
            // $form->switch('state', '运营状态')->states($states);
            // $form->time('business_start_time','营业开始时间')->format('HH:mm:ss');
            // $form->time('business_end_time','营业结束时间')->format('HH:mm:ss');
            $form->number('delivery_time','配送时间（分钟）')->min(0);
            //$form->number('grade', '商家评分')->max(5)->min(0);


            /*
            * 商铺创建好了才能添加商品。 自营和平台商家也存在区别
            */
            if($isExistShop){
                if($userType==2){ //自营

                }else{
                  	Admin::script($script);
                    $form->hasMany('user_shops_shop_goods','代理商品', function (Form\NestedForm $form) {
                        $form->display('good_id','商品主图')->with(function ($gid) {
                    		$re = ShopGoods::find($gid);
                    		if(!empty($re)){
                        		$picUrl = $re->primary_pic_url;
                        		return '<img style="margin: 8px;height: 160px;border: 1px solid #ddd;box-shadow: 1px 1px 5px 0 #a2958a;padding: 6px;" src="'.config('filesystems.disks.oss.url').'/'.$picUrl.'">';
                    		}else{
                        		return '<img>';
                    		}                    
                		});
                        $form->select('category_id','商品分类')->options(ShopCategory::where('shop_id',0)->pluck('name','id'))->load('good_id', '/admin/select-goods');
                        $form->select('good_id','商品名')->options(function($id){
                            return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
                        })->load('goods_name','/admin/select-goods-name');
                        $form->select('goods_name', '备注')
                            ->options(function($goods_name){
                            return ShopGoods::where('goods_name', $goods_name)->pluck('goods_name', 'goods_name');
                        });
                        $form->radio('is_recommend', '是否推荐')
                        ->options([1=>'是',0=>'否'])
                        ->default(1);
                    });
                }
                    
            }
                
                


            $form->saving(function (Form $form) {
              //dump($form);die;
                $userInfo = Auth::guard('admin')->user()->toArray();
                $form->shop_type = $userInfo['user_type'];
                $userShopProducts = $form->user_shop_products;
                $userShopsShopGoods = $form->user_shops_shop_goods;

                //不能提交重复商品
                if(!empty($userShopsShopGoods)){
                    $good_ids = [];
                    foreach ($userShopsShopGoods as $k => $goods) {
                        $good_ids[] = $goods['good_id'];
                        if (empty($goods['good_id'])) {
                            $error = new MessageBag([
                                'title'   => '商品信息未选择',
                                'message' => '请勿提交空商品信息',
                            ]);
                            return back()->with(compact('error'));
                        }
                    }
                    if(count($userShopsShopGoods) != count(array_unique($good_ids))){
                        $error = new MessageBag([
                            'title'   => '提交商品错误',
                            'message' => '请勿添加相同的商品',
                        ]);
                        return back()->with(compact('error'));
                    }
                }
              	//商家商品规格信息处理逻辑
                $arrRemove = [];
                $arrNotInRemove = [];
                $arrUpdate = [];
                $todo = 0;
                //保存添加时先执行删除(删除已经不存在的关联数据)
                if(!empty($userShopProducts)){ //把需要删除的放入数组里保存，用于之后的usershopproduct删除
                    //1、删除点击移除按钮的关联商品规格
                    foreach ($userShopsShopGoods as $k => $goods) {
                        if($goods['_remove_'] == 1){
                            $arrRemove[] = $goods['good_id'];
                        }
                        if($goods['_remove_'] == 0){//执行增改标记
                            $todo = 1;
                        }
                    }
                    UserShopProduct::where([
                        ['shop_id','=',$form->id]
                    ])->whereIn('goods_id',$arrRemove)->delete();
                    //2、删除被替换掉的关联商品规格
                    foreach ($userShopProducts as $k => $product) {
                        $arrNotInRemove[] = $product['goods_id'];
                    }
                    UserShopProduct::where([
                        ['shop_id','=',$form->id]
                    ])->whereNotIn('goods_id',$arrNotInRemove)->delete();
                }
                //把删除的从userShopProducts中去除
                if(!empty($arrRemove)){
                    foreach ($userShopProducts as $k => $product) {
                        if(!in_array($product['goods_id'], $arrRemove)){
                            $arrUpdate[$k] = $product;
                        }
                    }
                }else{
                    $arrUpdate = $userShopProducts;
                }
                //不是全部删除才会执行增改
                if($todo){
                    foreach ($arrUpdate as $k => $spec) {
                        if(empty($spec['id'])){
                            // dump($form);die;
                            $modelUserShopProducts = new UserShopProduct;
                          	if($spec['product_id']!=0){ //如果是新规格覆盖无规格的，先把旧的无规格删除
                                UserShopProduct::where([
                                    ['shop_id','=',$form->id],
                                    ['goods_id','=',$spec['goods_id']],
                                    ['product_id','=',0],
                                ])->delete();
                            }
                            $modelUserShopProducts->shop_id = $form->id;
                            $modelUserShopProducts->goods_id = $spec['goods_id'];
                            $modelUserShopProducts->product_id = $spec['product_id'];
                            $modelUserShopProducts->goods_spec_item_names = $spec['goods_spec_item_names'];
                          	$modelUserShopProducts->goods_spec_item_ids = $spec['goods_spec_item_ids'];
                            $modelUserShopProducts->price = $spec['price'];
                            $modelUserShopProducts->stock = $spec['stock'];
                            $modelUserShopProducts->save();
                        }else{
                            $modelUserShopProducts = UserShopProduct::find($spec['id']);
                            $modelUserShopProducts->shop_id = $form->id;
                            $modelUserShopProducts->goods_id = $spec['goods_id'];
                            $modelUserShopProducts->product_id = $spec['product_id'];
                            $modelUserShopProducts->goods_spec_item_names = $spec['goods_spec_item_names'];
                          	$modelUserShopProducts->goods_spec_item_ids = $spec['goods_spec_item_ids'];
                            $modelUserShopProducts->price = $spec['price'];
                            $modelUserShopProducts->stock = $spec['stock'];
                            $modelUserShopProducts->save();
                        }
                    }
                }
            });
            //保存后回调
            $form->saved(function (Form $form) {
                //为当前用户存入新创建商铺的id
                $userInfo = Auth::guard('admin')->user()->toArray();
                $userId = $userInfo['id'];
                $userType = $userInfo['user_type'];
                $isExistShop = $userInfo['shop_id'];
                $userModel = Administrator::find($userId);

                if(!$isExistShop){
                    
                    //创建用户的商铺id
                    $userModel = Administrator::find($userId);
                    $userModel->shop_id = $form->model()->id;
                    //为自营商户创分类
                    if($userType==2){
                        $categoryModel = new ShopCategory;
                        $categoryModel->name = $form->model()->shop_name;
                        $categoryModel->keywords = $form->model()->shop_name;
                        $categoryModel->front_desc = $form->model()->shop_name;
                        $categoryModel->icon_url = $form->model()->shop_icon;
                        $categoryModel->parent_id = 0;
                        $categoryModel->sort_order = 255;
                        $categoryModel->show_index = 1;
                        $categoryModel->level = 0;
                        $categoryModel->shop_id = $form->model()->id;
                      	$categoryModel->save();
                    }
                    $userModel->save();
                }
            });
            // $form->display('created_at', 'Created At');
            // $form->display('updated_at', 'Updated At');
        });
    }

    public function getListImg($list_pic_url,$modelUrl){
        if(empty($list_pic_url) || empty($modelUrl)){
            return '';
        }
        $url ='';
        foreach($list_pic_url as $v){
            $url .= sprintf($modelUrl,config('filesystems.disks.oss.url').'/'.$v);
        }
        return $url;
    }

    
}
