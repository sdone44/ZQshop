<?php

namespace App\Admin\Controllers;

use App\Models\UserShop;
use App\Models\ShopArea;
use App\Models\ShopCategory;
use App\Models\ShopGoods;
use App\Models\UserShopsShopGoods;
use App\Models\CityAddress;
use App\Models\ShopOrder;
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


class TurnoverStatisticsController  extends Controller
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
            

            $grid->disableCreateButton();
            // $grid->disableExport();
            $grid->disableActions();

            $grid->shop_icon('商家图标')->image('',70,70);
            $grid->shop_name('商铺名');
            $grid->shop_type('商户类型')->display(function ($type) {
                // dd($this->area_id);
                $typeRes = '';
                if($type==1){
                    $typeRes = '平台商户';
                }else if($type==2){
                    $typeRes = '自营商户';
                }else if($type==3){
                    $typeRes = '团购店';
                }else{
                    $typeRes = '其他';
                }
                return $typeRes;
            })->label('default');
            $grid->cur_monthy_retail_val('可提现金额（￥）')->display(function () {
                 $re = ShopOrder::getShopTotalDateById($this->id);
                 return $re['total_retail_value'];
             })->label('danger');
            $grid->withdrawals('已提现金额（￥）')->label();

            // $grid->area_id('代理点')->display(function ($areaId) {
            //     return ShopArea::getAreaName($areaId);
            // });
            // $grid->cur_monthy_order_num('本月订单数')->display(function () {
            //     $re = ShopOrder::getShopMonthlyDateById($this->id);
            //     return '<b style="color:red">'.$re['monthly_order_num'].'</b>';
            // });
            // $grid->cur_monthy_refund_order_num('本月退款订单数')->display(function () {
            //     $re = ShopOrder::getShopMonthlyDateById($this->id);
            //     return '<b style="color:green">'.$re['monthly_refund_order_num'].'</b>';
            // });
            // $grid->cur_monthy_val('本月成交金额')->display(function () {
            //     $re = ShopOrder::getShopMonthlyDateById($this->id);
            //     return '<b style="color:red">'.$re['monthly_value'].'</b>';
            // });
            // $grid->cur_monthy_refund_val('本月退款金额')->display(function () {
            //     $re = ShopOrder::getShopMonthlyDateById($this->id);
            //     return '<b style="color:green">'.$re['monthly_refund_value'].'</b>';
            // });
            // $grid->cur_monthy_retail_val('本月真实营业额')->display(function () {
            //     $re = ShopOrder::getShopMonthlyDateById($this->id);
            //     return '<b style="color:blue">'.$re['monthly_retail_value'].'</b>';
            // });



            $getListImg = $this;
            $grid->column('更多信息')->expand(function () use($getListImg) {
                $res = UserShopsShopGoods::shop_good($this->id);

                $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
                $row_arr1 = [
                    // [
                    //     '商品主图：' . sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$this->shop_icon),
                    // ],
                    [
                        '商家所在区域：' . CityAddress::getAddress($this->district),
                    ],
                    [
                        '商家详细地址：' . $this->shop_address,
                    ],
                    [
                        '联系人：' . $this->user_name,
                    ],
                    [
                        '联系电话：' . $this->phone,
                    ],
                    [
                        '商铺简介：' . $this->shop_summary,
                    ],
                ];
                
                $tab = new Tab();

                $table = new Table(['商铺基础信息'], $row_arr1);
                $tab->add('商铺基础信息', $table);

                $goods = new Table(['代理商品'],$res);
                $tab->add('代理商品', $goods);

                $totalBusinessInfoRes = ShopOrder::getShopTotalDateById($this->id);
                $userShopInfo = new Table(['总营业信息'],[
                    ['总浏览量：'.$this->page_views],
                    ['总订单数：' . $totalBusinessInfoRes['total_order_num']],
                    ['总成交金额：' . $totalBusinessInfoRes['total_value']],
                    ['总退款订单数：' . $totalBusinessInfoRes['total_refund_order_num']],
                    ['总退款金额：' . $totalBusinessInfoRes['total_refund_value']],
                    ['总真实营业额：' . $totalBusinessInfoRes['total_retail_value']],
                ]);
                $tab->add('总营业信息', $userShopInfo);

                $curMonthBusinessInfoRes = ShopOrder::getShopMonthlyDateById($this->id);
                $curMonthBusinessInfo = new Table(['当月营业信息'],[
                    ['当月订单数：' . $curMonthBusinessInfoRes['monthly_order_num']],
                    ['当月成交金额：' . $curMonthBusinessInfoRes['monthly_value']],
                    ['当月退款订单数：' . $curMonthBusinessInfoRes['monthly_refund_order_num']],
                    ['当月退款金额：' . $curMonthBusinessInfoRes['monthly_refund_value']],
                    ['当月真实营业额：' . $curMonthBusinessInfoRes['monthly_retail_value']],
                ]);
                $tab->add('当月营业信息', $curMonthBusinessInfo);

                $drawingRecordRes = new Table(['提现记录'],[
                    ['2019-09-01 提现：300.00￥'],
                    ['2019-09-01 提现：400.00￥'],
                ]);
                $tab->add('提现记录', $drawingRecordRes);
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
            $form->select('city','城市')->options(['1'=>'广州市'])->load('district', '/admin/select-district')->help('重新选择请先点击右边 x');

            $form->select('district','区/县')->options(function($id){
                return CityAddress::where('id', $id)->pluck('name', 'id');
            })->load('area_id','/admin/select-area');

            $form->multipleSelect('area_id', '服务区域')
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
            // $form->time('business_start_time','营业开始时间')->format('HH:mm:ss');
            // $form->time('business_end_time','营业结束时间')->format('HH:mm:ss');
            $form->number('delivery_time','配送时间（分钟）')->min(0);
            $form->number('grade', '商家评分')->max(5)->min(0);
            
            $form->hasMany('user_shops_shop_goods','代理商品', function (Form\NestedForm $form) {
                // dump($form->model()->user_shops_shop_goods());
                $form->select('category_id','商品分类')->options(ShopCategory::selectOptions(true))->load('good_id', '/admin/select-goods');
                $form->select('good_id','商品名')->options(function($id){
                    return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
                })->load('goods_name','/admin/select-goods-name');
                $form->select('goods_name', '备注(请勿修改)')
                    ->options(function($goods_name){
                    return ShopGoods::where('goods_name', $goods_name)->pluck('goods_name', 'goods_name');
                });
                $form->number('stock','库存')->min(0);
                $form->radio('is_recommend', '是否推荐')
                ->options([1=>'是',0=>'否'])
                ->default(1);
            });
            
            $form->saving(function (Form $form) {
                // dump($form);die;
                if(!empty($form->user_shops_shop_goods)){
                    $good_ids = [];
                    foreach ($form->user_shops_shop_goods as $k => $goods) {
                        $good_ids[] = $goods['good_id'];
                    }
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
