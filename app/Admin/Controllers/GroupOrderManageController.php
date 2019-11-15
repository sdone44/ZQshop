<?php

namespace App\Admin\Controllers;

use App\Logic\AddressLogic;
use App\Models\ShopOrder;
use App\Models\ShopOrderGoods;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Table;
use App\Admin\Extensions\OrderTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Validator;


class GroupOrderManageController extends Controller
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


            $content->header('团购订单列表');
            $content->description('订单管理');

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
        $content = Admin::content(function (Content $content) use ($id) {

            $content->header('订单信息修改');
            $content->description('订单管理');

            $content->body($this->form($id)->edit($id));
        });
        return $content;
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('header');
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
        return Admin::grid(ShopOrder::class, function (Grid $grid) {

            // $isAdmin = Admin::user()->isAdministrator();
            // $userInfo = Auth::guard('admin')->user()->toArray();
            // $isExistShop = $userInfo['shop_id'];
            /*
            * 数据来源
            */
            // if(!$isAdmin&&$isExistShop){
            //     $grid->model()->where('shop_id', '=', $isExistShop);
            // }else if($isAdmin){
            //     $grid->model()->where('id', '>', 0);
            // }else{
            //     $grid->model()->where('id', '=', 0);
            // }
            // $grid->model()->orderBy('id', 'desc');
            /*
            *   导出数据处理
            */
            

            $grid->model()->where('group_id','<>',0);
            $grid->model()->orderBy('id', 'desc');
            // $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->actions(function ($actions){
                $actions->disableDelete();
            });
            // $grid->id('序号')->sortable();
            $grid->order_sn('订单编号');
            $grid->order_status('订单状态')->display(function ($status) {
                switch($status){
                    case ShopOrder::STATUS_WAIT_PAY:
                        $label = 'label-danger';
                        break;
                    case ShopOrder::STATUS_ALREADY_PAID:
                        $label = 'label-success';
                        break;
                    default:
                        $label = 'label-info';
                }
                $status = ShopOrder::getStatusDisplayMap()[$status];
                return "<span class='label {$label}'>{$status}</span>";
            });
            // 这里是多个信息一起显示
            $grid->column('购物信息')->expand(function (){
                $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
                $goodsInfo = [];
                foreach($this->orderGoods as $goods){
                    //$goodsInfo[] = [sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$goods->list_pic_url).' '.$goods->goods_name.' '.$goods->retail_price.' * '.$goods->number];
                    $goodsInfo[] = [sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$goods->list_pic_url)];
                    $goodsInfo[] = [$goods->goods_name.' '.$goods->retail_price.' * '.$goods->number];
                }
                $row_arr1 = [
                    ['收货人：' . $this->consignee],
                    ['收件人手机：' . $this->mobile],
                    ['收货地址：' . AddressLogic::getRegionNameById($this->province).' '.AddressLogic::getRegionNameById($this->city).' '.AddressLogic::getRegionNameById($this->district).' '.$this->address],
                ];
                $row_arr1 = array_merge($goodsInfo,$row_arr1);
                $table = new Table(['购物信息'], $row_arr1);
                return $table;
            }, '购物信息');
            //$grid->order_price('订单金额');
            //$grid->coupon_price('优惠金额');
            $grid->actual_price('总金额');
            $grid->num('数量')->display(function(){
                $num = count(ShopOrderGoods::where('order_id',$this->id)->get());
                return $num;
            });
            // $grid->column('order_status', '状态')->filter([
            //     0 => '订单取消',
            //     22 => '已付款',
            //     32 => '已接单',
            //     40 => '订单完成',
            // ]);

            

            $grid->pay_time('支付时间')->display(function ($payTime) {
                if($payTime==0){
                    $time = '未支付';
                }else{
                    $time = date('Y-m-d H:i:s',$payTime);
                }
                return $time;
            });

            $grid->created_at('创建时间');
            //$grid->updated_at('更新时间');
            $grid->filter(function ($filter) {
                $filter->like('order_sn', '订单编号');
                $filter->equal('mobile', '收件人手机');
                $filter->equal('order_status', '订单状态')->select(ShopOrder::getStatusDisplayMap());
                $filter->between('pay_time', '付款时间')->datetime();
            });

            $grid->disableActions();
                
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id='')
    {

        return Admin::form(ShopOrder::class, function (Form $form){
            $form->display('id', '序号');
            $form->display('order_sn', '订单编号');
            $form->display('order_status', '订单状态')->with(function ($value) {
                return ShopOrder::getStatusDisplayMap()[$value];
            });
            $form->display('goods_price', '商品总价');
            $form->display('order_price', '订单金额');
            $form->display('coupon_price', '优惠金额');
            $form->display('actual_price', '支付金额');
            $form->display('consignee', '收件人');
            $form->display('mobile', '收件人手机号');
            $form->display('uid', '下单用户ID');
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

        });
    }

    public function acceptOrder(Request $request)
    {
        $id = $request->id;

        $re = ShopOrder::where('id',$id)->update(['order_status'=>32]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }

    public function refuseOrder(Request $request)
    {
        $id = $request->id;

        $re = ShopOrder::where('id',$id)->update(['order_status'=>0]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }

    public function orderRead(Request $request)
    {
        $shopId = $request->shop_id;

        $re = ShopOrder::where('shop_id',$shopId)->update(['is_new'=>0]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }

    //订单退款
    public function orderRefund(Request $request)
    {
        // 参数校验

        $app = Factory::payment(config('wechat.payment.default'));
        $actualPrice = $request->actual_price;
        $refundDesc = $request->refund_desc;
        $randCode = randomkeys(6);

        $orderSno = $request->order_sn;
        $refundNumber = $orderSno . $randCode; //自己生成
        $totalFee = $actualPrice*100;
        $refundFee = $actualPrice*100;
        $config = [
           'refund_desc'=>$refundDesc,
        ];
        //根据商户订单号退款, 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byOutTradeNumber($orderSno,$refundNumber,$totalFee,$refundFee,$config = []);

        return $result;

        if($result['result_code'] == 'SUCCESS'){
            ShopOrder::where('order_sn',$orderSno)->update(['order_status'=>21,'refund_number'=>$refundNumber]);//21退款处理中

            return ['msg'=>'退款成功','code'=>21];
        }
        return $result;
    }
}
