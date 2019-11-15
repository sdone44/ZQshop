<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use App\Models\ShopOrder;

class OrderTools
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.accept').on('click', function () {
    // Your code.
    console.log($(this).data('id'));
    console.log($(this).data('status'));
    console.log($(this).data('actualprice'));
    console.log($(this).data('ordersn'));

});


$('.accept-refund').on('click', function () {
    // Your code.
    console.log($(this).data('id'));
    console.log($(this).data('status'));
    console.log($(this).data('actualprice'));
    console.log($(this).data('ordersn'));

    id = $(this).data('id');
    actualprice = $(this).data('actualprice');
    ordersn = $(this).data('ordersn');

    swal({ 
            title: "同意退款?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/order-refund",
                dataType : "json",
                data : {
                    'order_sn':ordersn,
                    'actual_price':actualprice,
                    'refund_desc':'退款',
                },
                success : function(test) {
                    console.log(test);
                    if(test.code==21){
                        swal('操作成功!','','success');
                        window.location.reload();
                    }else{
                        swal('操作失败!','','error');
                        window.location.reload();
                    }
                        
                },
            });
    });

});


$('.grid-accept-order').on('click', function () {
    // Your code.
    console.log('接单');
    id = $(this).data('id');
    swal({ 
            title: "确认接单?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/order-accept",
                dataType : "json",
                data : {
                    'id':id,
                },
                success : function(test) {
                    if(test){
                        swal('接单成功!','','success');
                        window.location.reload();
                    }else{
                        swal('接单失败!','','error');
                        window.location.reload();
                    }
                        
                },
            });
    });
});

$('.grid-refuse-order').on('click', function () {
    // Your code.
    console.log('拒绝');
    id = $(this).data('id');
    swal({ 
            title: "取消接单?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/order-refuse",
                dataType : "json",
                data : {
                    'id':id,
                },
                success : function(test) {
                    if(test){
                        swal('取消成功!','','success');
                        window.location.reload();
                    }else{
                        swal('取消失败!','','error');
                        window.location.reload();
                    }
                        
                },
            });
    });
});


$('.grid-finish-order').on('click', function () {
    // Your code.
    console.log('确认送达');
    id = $(this).data('id');
    swal({ 
            title: "确认送达?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/order-finish",
                dataType : "json",
                data : {
                    'id':id,
                },
                success : function(test) {
                    if(test){
                        swal('订单完成!','','success');
                        window.location.reload();
                    }else{
                        swal('操作失败!','','error');
                        window.location.reload();
                    }
                        
                },
            });
    });
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        $ShopOrderModel = ShopOrder::find($this->id);

        $orderStatus = $ShopOrderModel->order_status;

        $actualPrice = $ShopOrderModel->actual_price;

        $orderSn = $ShopOrderModel->order_sn;

        switch ($orderStatus) {
            case 0: //取消
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【订单取消】</a>";
                break;
            case 10://待支付
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【待支付】</a>";
                break;
            case 22://支付完成
                return "<a style='cursor:pointer;padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-accept-order' data-id='{$this->id}' data-status='{$orderStatus}'>确认接单</a>
                        <a style='cursor:pointer;padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-refuse-order' data-id='{$this->id}' data-status='{$orderStatus}'>取消接单</a>";
                break;
            case 32://已接单
                return "<a style='cursor:pointer;padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-finish-order' data-id='{$this->id}' data-status='{$orderStatus}'>确认送达</a>";
                break;
            case 40://订单完成
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【订单完成】</a>";
                break;
            case 21://确认申请退款
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【退款处理中】</a>";
                break;
            case 20://退款处理成功
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【已退款成功】</a>";
                break;
            case 19://用户申请退款
                return "<a style='cursor:pointer;padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='accept-refund' data-id='{$this->id}' data-status='{$orderStatus}' data-actualprice='{$actualPrice}' data-ordersn='{$orderSn}'>同意退款</a>";
                break;
            default:
                return "<a style='padding: .2em .6em .3em;font-size: 75%;font-weight: 700;line-height: 1;' class='grid-check-row' data-id='{$this->id}' data-status='{$orderStatus}'>【无状态订单】</a>";
                break;
        }

    }

    public function __toString()
    {
        return $this->render();
    }
}