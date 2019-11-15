<?php

namespace App\Admin\Controllers;

use App\Models\UserShop;
use App\Models\ShopArea;
use App\Models\ShopCategory;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
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

            $content->header('Edit');
            $content->description('description');


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

            $content->header('Create');
            $content->description('description');

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

            $grid->id('ID')->sortable();
            $grid->shop_icon('商家图标')->image('',70,70);
            $grid->shop_name('商铺名');
            // $grid->area_id('服务区域')
            //     ->select(ShopArea::getAllClasses(true));
            // $district = $this;
            $grid->district('商家地区')->display(function ($district) {
                // dd($this->area_id);
                return CityAddress::getAddress($this->district);
            });
            
            // $area = $this;
            $grid->area_id('代理点')->display(function ($area) {
                // dd($this);
                return ShopArea::getAreaName($this->area_id);
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

                $goods = new Table(['代理商品'],$res);
                $tab->add('代理商品', $goods);

                return $tab;
            }, '更多信息');
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

            $form->display('id', 'ID');
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
            $form->select('city','城市')->options(['1'=>'广州市'])->load('district', '/admin/select-district');

            $form->select('district','区/县')->options(function($id){
                return CityAddress::where('id', $id)->pluck('name', 'id');
            })->load('area_id','/admin/select-area');

            $form->select('area_id', '服务区域')
                ->rules('required')
                ->options(function($id){
                return ShopArea::where('id', $id)->pluck('area_name', 'id');
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
            $form->time('business_start_time','营业开始时间')->format('HH:mm:ss');
            $form->time('business_end_time','营业结束时间')->format('HH:mm:ss');
            $form->number('delivery_time','配送时间（分钟）')->min(0);
            $form->number('grade', '商家评分')->max(5)->min(0);
            
            $form->hasMany('user_shops_shop_goods','代理商品', function (Form\NestedForm $form) {
                // dump($form->model()->user_shops_shop_goods());
                $form->select('category_id','商品分类')->options(ShopCategory::selectOptions(true))->load('good_id', '/admin/select-goods');
                $form->select('good_id','商品名')->options(function($id){
                    return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
                })->load('goods_name','/admin/select-goods-name');
                $form->select('goods_name', '备注')
                    ->options(function($goods_name){
                    return ShopGoods::where('goods_name', $goods_name)->pluck('goods_name', 'goods_name');
                });
                $form->number('stock','库存')->min(0);
                $form->radio('is_recommend', '是否推荐')
                ->options([1=>'是',0=>'否'])
                ->default(1);
            });
            
            $form->saving(function (Form $form) {
                if(!empty($form->user_shops_shop_goods)){
                    $good_ids = [];
                    foreach ($form->user_shops_shop_goods as $k => $goods) {
                        $good_ids[] = $goods['good_id'];
                    }
                    // dump($good_ids);die;
                    if(count($form->user_shops_shop_goods) != count(array_unique($good_ids))){
                        $error = new MessageBag([
                            'title'   => '提交商品错误',
                            'message' => '请勿添加相同的商品',
                        ]);

                        return back()->with(compact('error'));
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

    
}
