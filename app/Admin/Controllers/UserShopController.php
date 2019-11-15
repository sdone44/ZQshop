<?php

namespace App\Admin\Controllers;

use App\Models\UserShop;
use App\Models\ShopArea;
use App\Models\ShopCategory;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\ShopSpecification;
use App\Models\ShopProduct;
use App\Models\UserShopProduct;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\MessageBag;
use Illuminate\Http\Request;

class UserShopController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        // $test = UserShop::find(1);

        // dd($test->shop_category);

        return Admin::content(function (Content $content) {

            $content->header('商铺列表');
            $content->description('商城商铺管理');

            $content->body($this->grid());
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

            $content->header('编辑');
            $content->description('商铺信息编辑');


            $content->body($this->form()->edit($id));
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

            $content->header('创建商铺');
            $content->description('商铺信息填写');

            $content->body($this->form());
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
            $isAdmin = Admin::user()->isAdministrator();
            if(!$isAdmin){ //最高管理员身份进入
                $grid->disableCreateButton();
                $grid->disableExport();
            }
            $grid->model()->where('shop_type', '<>', 3);
            $grid->id('ID')->sortable();
            $grid->shop_icon('商家图标')->image('',70,70);
            $grid->shop_name('商铺名');
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
            $grid->district('商家地区')->display(function ($district) {
                // dd($this->area_id);
                return CityAddress::getAddress($this->district);
            });
            
            // $area = $this;
            $grid->area_id('代理点')->display(function ($areaId) {

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
                // dd($res);

                $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
                $row_arr1 = [
                    // [
                    //     '商品主图：' . sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$this->shop_icon),
                    // ],
                    [
                        '商家地址：' . $this->shop_address,
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

                $goods = new Table(['代理商品','本月售出','总销量'],$res);
                $tab->add('代理商品', $goods);

                return $tab;
            }, '更多信息');

            // 搜索功能
            $grid->filter(function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                // 在这里添加字段过滤器
                $filter->like('shop_name', '商铺名');
                // $filter->between('created_at', 'Created Time')->datetime();
            });
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
            // $id = '4,5';
            // ShopArea::getAreaNameByAreaId($id);
            $form->hidden('id', 'ID');
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
            $form->currency('began_price', '起送价')
                ->symbol('￥');
            $form->switch('state', '运营状态')->states($states);
            // $form->time('business_start_time','营业开始时间')->format('HH:mm:ss');
            // $form->time('business_end_time','营业结束时间')->format('HH:mm:ss');
            $form->number('delivery_time','配送时间（分钟）')->min(0);
            $form->number('grade', '商家评分')->max(5)->min(0);

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
                                console.log("返回结果re为"+re);
                                k++;
                                if(re != 0){
                                    console.log("长度为"+re.length);
                                    l = re.length;
                                    console.log(re);
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
            Admin::script($script);
            $form->hasMany('user_shops_shop_goods','代理商品', function (Form\NestedForm $form) {
                // dump($form->model()->user_shops_shop_goods());
              	$form->display('good_id','商品主图')->with(function ($gid) {
                    $re = ShopGoods::find($gid);
                    if(!empty($re)){
                        $picUrl = $re->primary_pic_url;
                        return '<img style="margin: 8px;height: 160px;border: 1px solid #ddd;box-shadow: 1px 1px 5px 0 #a2958a;padding: 6px;" src="'.config('filesystems.disks.oss.url').'/'.$picUrl.'">';
                    }else{
                        return '<img>';
                    }     
                });
                $form->select('category_id','商品分类')->options(ShopCategory::selectOptions(true))->load('good_id', '/admin/select-goods');
                $form->select('good_id','商品名')->options(function($id){
                    return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
                })->load('goods_name','/admin/select-goods-name');
                $form->select('goods_name', '备注')
                    ->options(function($goods_name){
                    return ShopGoods::where('goods_name', $goods_name)->pluck('goods_name', 'goods_name');
                });

                // $html = '<input type="text" id="myt" name="user_shops_shop_goods['.$key.'][myt]" value="" class="form-control user_shops_shop_goods myt" placeholder="">';
                // $form->html($html,'产品属性');
                
                // $form->html('<input type="hidden" id="goods_name" name="user_shops_shop_goods['.$key.'][goods_name]" value="666" class="form-control user_shops_shop_goods goods_name">');
                //$form->myt('good_id','商品规格'); //

                // $form->number('stock','库存')->min(0)->default(0);
                $form->radio('is_recommend', '是否推荐')
                ->options([1=>'是',0=>'否'])
                ->default(1);
            });
            
            $form->saving(function (Form $form) {
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

                // dump($form);die;

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

    //商家商品规格信息下拉框获取
    public function getSpecByGid(Request $request)
    {
        $gid = $request->gid;

        $re = ShopProduct::where('goods_id',$gid)->get();
        $good = ShopGoods::find($gid);
        $picUrl = config('filesystems.disks.oss.url').'/'.$good->primary_pic_url;
        $resArr = [];
        if(count($re)){
            $resArr['data'] = $re;
            $resArr['picUrl'] = $picUrl;
            return $resArr;
        }else{ //如果商品无规格构造一个类似规格数组来保存
            $re = ShopGoods::where('id',$gid)->first();
            $reArr[0]['goods_id'] = $re->id;
            $reArr[0]['goods_spec_item_names'] = $re->goods_name."(无规格)";
            $reArr[0]['goods_spec_item_ids'] = 0;
            $reArr[0]['retail_price'] = $re->retail_price;
            $reArr[0]['id'] = 0; //product_id
            $resArr['data'] = $reArr;
            $resArr['picUrl'] = $picUrl;
            return $resArr;
        }
    }
    //商家商品规格信息编辑回调
    public function getSpecByGidForShop(Request $request)
    {
        $gid = $request->gid;
        $shopId = $request->shop_id;
        // $gid = 16;
        // $shopId = 23;
        $re = UserShopProduct::where([
            ['goods_id','=',$gid],
            ['shop_id','=',$shopId]
        ])->get();//1、回调结果and检测商品是否已保存到商家商品规格表
        $reCheck = ShopProduct::where('goods_id',$gid)->get();//2、检测商品是否有规格
        if((count($re)!=0)){//3、检测已保存的记录是否是规格商品
            if((count($re)==1) && ($re[0]->product_id == 0)){
                $isSpec = 0;
            }else{
                $isSpec = 1;
            }
        }
        
        if((count($re)!=0)){//1、user_shop_products表中已存在
            if($isSpec){//3、检测已保存的记录是否是规格商品
                $reArr = [];
                foreach ($re as $k => $products) {
                    $reArr[$k]['goods_id'] = $products->goods_id;
                    $reArr[$k]['goods_spec_item_names'] = $products->goods_spec_item_names;
                    if(empty($products->goods_spec_item_ids)){
                        $reArr[$k]['goods_spec_item_ids'] = ShopProduct::where([
                            ['goods_spec_item_names','=',$products->goods_spec_item_names],
                            ['goods_id','=',$products->goods_id]
                        ])->pluck('goods_spec_item_ids');
                    }else{
                        $reArr[$k]['goods_spec_item_ids'] = $products->goods_spec_item_ids;
                    }
                    $reArr[$k]['price'] = $products->price;
                    $reArr[$k]['product_id'] = $products->product_id;
                    $reArr[$k]['id'] = $products->id;
                    $reArr[$k]['stock'] = $products->stock;
                }
                return $reArr;

            }else{
                if(count($reCheck)!=0){//2、无规格商品添加了规格
                    $reArr = [];
                    foreach ($reCheck as $k => $products) {
                        $reArr[$k]['goods_id'] = $products->goods_id;
                        $reArr[$k]['goods_spec_item_names'] = $products->goods_spec_item_names;
                        $reArr[$k]['goods_spec_item_ids'] = $products->goods_spec_item_ids;
                        $reArr[$k]['price'] = $products->retail_price;
                        $reArr[$k]['product_id'] = $products->id;
                        $reArr[$k]['id'] = '';
                        $reArr[$k]['stock'] = 0; //product_id
                    }
                    return $reArr;
                }else{
                    $reArr = [];
                    foreach ($re as $k => $products) {
                        $reArr[$k]['goods_id'] = $products->goods_id;
                        $reArr[$k]['goods_spec_item_names'] = $products->goods_spec_item_names;
                        $reArr[$k]['goods_spec_item_ids'] = 0;
                        $reArr[$k]['price'] = $products->price;
                        $reArr[$k]['product_id'] = $products->product_id;
                        $reArr[$k]['id'] = $products->id;
                        $reArr[$k]['stock'] = $products->stock;
                    }
                    return $reArr;
                }
            }
        }else{
            $reArr = [];
            if(count($reCheck)){
                foreach ($reCheck as $k => $products) {
                    $reArr[$k]['goods_id'] = $products->goods_id;
                    $reArr[$k]['goods_spec_item_names'] = $products->goods_spec_item_names;
                    $reArr[$k]['goods_spec_item_ids'] = $products->goods_spec_item_ids;
                    $reArr[$k]['price'] = $products->retail_price;
                    $reArr[$k]['product_id'] = $products->id;
                    $reArr[$k]['id'] = '';
                    $reArr[$k]['stock'] = 0; //product_id
                }
                return $reArr;
            }else{ //如果商品无规格构造一个类似规格数组来保存
                $reCheck = ShopGoods::where('id',$gid)->first();
                $reArr[0]['goods_id'] = $reCheck->id;
                $reArr[0]['goods_spec_item_names'] = $reCheck->goods_name."(无规格)";
                $reArr[0]['goods_spec_item_ids'] = 0;
                $reArr[0]['price'] = $reCheck->retail_price;
                $reArr[0]['product_id'] = 0;
                $reArr[0]['id'] = '';
                $reArr[0]['stock'] = 0;

                return $reArr;
            }
        }

    }
  
  	//通过gid获取图片地址
    public function getPicUrlById(Request $request)
    {
        $gid = $request->gid;

        $re = ShopGoods::find($gid);
        if(!empty($re)){
            $url = $re->primary_pic_url;
            $picUrl = config('filesystems.disks.oss.url').'/'.$url;
            return $picUrl;
        }else{
            return false;
        } 
    }
}
