<?php

namespace App\Admin\Controllers\Shop;

use App\Models\ShopCategory;
use App\Models\ShopGoods;
use App\Models\ShopBrand;
use App\Models\ShopAttribute;
use App\Models\ShopSpecification;
use App\Models\UserShopsShopGoods;
use App\Admin\Extensions\GoodsDelete;
use App\Models\UserShopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;

class ShopGoodsController extends Controller
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
            $userType = $userInfo['user_type'];
            
            if($userType==2){
                $content->header('我的商铺');
                $content->description('商品管理');
            }else{
                $content->header('商城商品列表');
                $content->description('商城商品管理');
            }

                

            $content->body($this->grid());
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

            $content->header('商城商品列表');
            $content->description('商城商品管理');

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

            $content->header('新增商品');
            $content->description('商品管理');

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
        return Admin::grid(ShopGoods::class, function (Grid $grid) {
            /*
            * 数据来源
            */
            $userInfo = Auth::guard('admin')->user()->toArray();
            $userType = $userInfo['user_type'];
            
            if($userType==2){
                $userShopId = $userInfo['shop_id'];
                $shopCategory = ShopCategory::where('shop_id',$userShopId)->first();
                $shopCategoryId = $shopCategory->id;
                $grid->model()->where('category_id', '=', $shopCategoryId);
            }
            

            $grid->model()->orderBy('sort_order', 'asc');
            $grid->id('序号')->sortable();
            $grid->primary_pic_url('商品主图')->image('', 75, 75);
            $grid->goods_name('商品名')->label('info')->limit(50);
            // $grid->goods_sn('商品编号')->limit(50);
            if($userType!=2){
                $grid->category_id('商品分类')
                    ->select(ShopCategory::getAllClasses(true));
            }
                
            // $grid->brand_id('品牌id')
            //     ->select(ShopBrand::getAllClasses(true));
            $grid->goods_number('商品库存量');
            $grid->sort_order('商品排序');

            $getListImg = $this;
            // 这里是多个信息一起显示
            $grid->column('其他信息')->expand(function () use($getListImg) {
                $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
                $row_arr1 = [
                    [
                        '商品主图：' . sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$this->primary_pic_url),
                    ],
                    [
                        '商品列表图：' .$getListImg->getListImg($this->list_pic_url,$imgUrl) ,
                    ],
                    [
                        '商品详情图：' .$getListImg->getDetailsImg($this->details_pic_url,$imgUrl) ,
                    ],
                    [
                        '商品关键词：' . $this->keywords,
                    ],
                    [
                        // '商品摘要：' . $this->goods_brief,
                    ],
                    [
                        // '专柜价格：￥' . $this->counter_price,
                        // '附加价格：￥' . $this->extra_price,
                        '零售价格：￥' . $this->retail_price,
                        // '单位价格，单价：￥' . $this->unit_price,
                        // '运费：￥' . $this->freight_price,
                    ],
                ];
                $table = new Table(['其他信息'], $row_arr1);
                $tab = new Tab();
                $tab->add('商品基础信息', $table);

                $box = new Box('商品描述',$this->goods_desc);
                $tab->add('商品描述', $box);

                return $tab;
            }, '其他信息');



//            $grid->goods_price('单价');
//            $grid->goods_marketprice('市场价');
//            $grid->goods_onsaleprice('折扣价');
//            $grid->goods_salenum('销售量');
//            $grid->goods_click('点击量');
//            $grid->goods_carousel('轮播图片')->image('', 50, 50);
//            $grid->goods_description_pictures('描述图片')->image('', 50, 50);
//
////            $grid->goods_storage('库存');
//            $grid->goods_state('状态')
//                ->select(Good::getStateDispayMap());
//            $grid->sort('排序');
//            $grid->created_at('创建时间');
//            $grid->updated_at('更新时间');
            $grid->actions(function ($actions) {
                $userInfo = Auth::guard('admin')->user()->toArray();
                $userType = $userInfo['user_type'];
                
                // $actions->disableEdit();
                if($userType==2){
                    $actions->append(new GoodsDelete($actions->getKey()));
                    $actions->disableDelete();
                }
                // $actions->append(new GoodsDelete($actions->getKey()));
              	$actions->disableDelete();
                $actions->disableView();
            });

            $grid->filter(function ($filter) {
                $filter->like('goods_name', '商品名');
                $filter->in('class_id', '分类')
                    ->multipleSelect(ShopCategory::getAllClasses(true));
                $filter->equal('is_delete', '状态')
                    ->radio(ShopGoods::getDeleteDispayMap());
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
        return Admin::form(ShopGoods::class, function (Form $form) {
            $userInfo = Auth::guard('admin')->user()->toArray();
            $userType = $userInfo['user_type'];
            
            $form->display('id', '序号');
			$form->hidden('id');
            $form->text('goods_name', '商品名')
                ->rules('required');
            //     $test = new ShopCategory;
            // dd($test);
            if($userType!=2){
                $form->select('category_id', '商品分类')
                    ->rules('required')
                    ->options(ShopCategory::selectOptions(true));
            }else{
                $form->hidden('category_id');
            }
                
            // $form->select('brand_id', '品牌id')
            //     ->rules('required')
            //     ->options(ShopBrand::getAllClasses(true));

            // $form->currency('counter_price', '专柜价格')
            //     ->symbol('￥');

            // $form->currency('extra_price', '附加价格')
            //     ->symbol('￥');

            $form->currency('retail_price', '零售价格')->rules('required')
                ->symbol('￥');

            // $form->currency('unit_price', '单位价格，单价')
            //     ->symbol('￥');
            // $form->currency('freight_price', '运费，单价')
            //     ->symbol('￥');

            $form->textarea('keywords', '商品关键词')->rules('required');
            // $form->textarea('goods_brief', '商品摘要');
            $form->textarea('goods_desc', '商品描述')->rules('required');
             $form->addSpecification('products', '添加规格',function(){
                 
                 return ShopSpecification::all();
            });
            // $form->textarea('promotion_desc', '促销描述');
            // $form->text('promotion_tag', '促销标签')
            //     ->value(' ');

            $form->image('primary_pic_url', '商品主图')
                ->rules('required')
                ->uniqueName()->help('建议300*300');

            $form->multipleImage('list_pic_url', '商品展示图')
              	->removable()
                ->uniqueName();


            $form->multipleImage('details_pic_url', '商品详情图')
                ->uniqueName()
                ->removable();
            $form->image('group_pic_url', '团购商品轮播展示图')
                ->uniqueName()->help('团购商品必填');

            $form->number('goods_number', '库存')->default(10);
            // $form->number('sell_volume', '销售量');
            $form->radio('is_on_sale', '上架状态')
                ->options(ShopGoods::getSaleDispayMap())
                ->default(ShopGoods::STATE_ON_SALE);
            // $form->radio('is_delete', '删除状态')
            //     ->options(ShopGoods::getDeleteDispayMap())
            //     ->default(ShopGoods::STATE_NOT_DELETE);
            // $form->radio('is_limited', '是否限购')
            //     ->options(ShopGoods::getLimitDispayMap())
            //     ->default(ShopGoods::STATE_SALE_NOT_LIMIT);
            $form->radio('is_hot', '是否推荐')
                ->options(ShopGoods::getRecommendDispayMap())
                ->default(ShopGoods::STATE_SALE_NOT_RECOMMEND);
            $form->image('hot_pic_url', '推荐图')
                ->uniqueName();

            $form->radio('is_new', '是否新品')
                ->options(ShopGoods::getNewDispayMap())
                ->default(ShopGoods::STATE_SALE_NEW);
            // $form->radio('is_vip_exclusive', '是否是会员专属')
            //     ->options(ShopGoods::getVipDispayMap())
            //     ->default(ShopGoods::STATE_NOT_VIP);
            // $form->currency('vip_exclusive_price', '会员专享价')
            //     ->symbol('￥');
            $form->number('sort_order','排序')
                ->default(255);

            // $form->hasMany('goods_attribute', '添加属性', function (Form\NestedForm $form) {
            //     $form->select('attribute_id', '选择属性')->options(ShopAttribute::pluck('name','id'));
            //     $form->text('value', '属性值');
            // });

//            $form->addSpecification('attribute_category', 'wewe');
//            $form->divide();
//            $form->hasMany('products', '添加规格', function (Form\NestedForm $form) {
//                $form->number('goods_number','库存')->default(255)->rules('required|min:1|max:20');
//                $form->currency('retail_price', '单价')
//                    ->symbol('￥');
//            });
          	$form->tools(function (Form\Tools $tools) {
                $tools->disableDelete();
                $tools->disableView();
                //$tools->disableList();
            });
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
          	$script = '$(document).ready(function(){$(".box-header>.box-tools [data-toggle=\"tooltip\"]").click();})';
          	Admin::script($script);
            //保存前回调
            $form->saving(function (Form $form) {
              //dump($form);die;
                $userInfo = Auth::guard('admin')->user()->toArray();
                $userType = $userInfo['user_type'];
                
                if($userType==2){ //自营商家只能加到自己的分类下
                    $userShopId = $userInfo['shop_id'];
                    $shopCategory = ShopCategory::where('shop_id',$userShopId)->first();
                    $shopCategoryId = $shopCategory->id;
                    $form->category_id = $shopCategoryId;

                }
            });

            //保存后回调
            $form->saved(function (Form $form) {
                $userInfo = Auth::guard('admin')->user()->toArray();
                $userType = $userInfo['user_type'];
              	$productsRes = $form->products;
				//同时更新平台下关联的商家商品规格价格
                if($form->model()->id){//编辑下才会执行
                    if(!empty($productsRes)){
                        foreach ($productsRes as $k => $products) {
                            $re = UserShopProduct::where('product_id',$products['id'])->update(['price'=>$products['retail_price']]);
                        }
                    }else{
                        $gid = $form->id;
                        $price = $form->retail_price;
                        UserShopProduct::where('goods_id',$gid)->update(['price'=>$price]);
                    }
                }
                if($userType==2){
                    $userShopId = $userInfo['shop_id'];
                    $shopCategory = ShopCategory::where('shop_id',$userShopId)->first();
                    $shopCategoryId = $shopCategory->id;

                    //同时在商户商品表增加商品信息
                    $goodId = $form->model()->id;
                    $userShopsShopGoodsFirst = UserShopsShopGoods::where('good_id',$goodId)->first();

                    if (!$userShopsShopGoodsFirst) { //新增时
                        
                        $userShopsShopGoodsModel = new UserShopsShopGoods;
                        $userShopsShopGoodsModel->shop_id = $userShopId;
                        $userShopsShopGoodsModel->category_id = $shopCategoryId;
                        $userShopsShopGoodsModel->good_id = $goodId;
                        $userShopsShopGoodsModel->goods_name = $form->goods_name;
                        $userShopsShopGoodsModel->stock = $form->goods_number;
                        $userShopsShopGoodsModel->is_recommend = $form->is_hot;

                    }else{ //编辑时

                        $userShopsShopGoodsModel = UserShopsShopGoods::find($userShopsShopGoodsFirst->id);
                        $userShopsShopGoodsModel->goods_name = $form->goods_name;
                        $userShopsShopGoodsModel->stock = $form->goods_number;
                        $userShopsShopGoodsModel->is_recommend = $form->is_hot;

                    }
                    $userShopsShopGoodsModel->save();
                }                


            });


            
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
    
    public function getDetailsImg($details_pic_url,$modelUrl){
        if(empty($details_pic_url) || empty($modelUrl)){
            return '';
        }
        $url ='';
        foreach($details_pic_url as $v){
            $url .= sprintf($modelUrl,config('filesystems.disks.oss.url').'/'.$v);
        }
        return $url;
    }

    public function goods(Request $request)
    {
        $id = $request->get('q');

        return ShopGoods::where('category_id', $id)->get(['id', DB::raw('goods_name as text')]);
    }

    public function goodsName(Request $request)
    {
        $id = $request->get('q');

        return ShopGoods::where('id', $id)->get(['goods_name', DB::raw('goods_name as text')]);
    }
  
  	public function goodsPrice(Request $request)
    {
        $id = $request->get('q');

        return ShopGoods::where('id', $id)->get(['retail_price', DB::raw('retail_price as text')]);
    }

    public function goodsDelete(Request $request)
    {
        $id = $request->id;

        $re = UserShopsShopGoods::where('good_id',$id)->delete();
        $res = ShopGoods::where('id',$id)->delete(); 

        if($re&&$res){
            return 1;
        }else{
            return 0;
        }

    }
}
