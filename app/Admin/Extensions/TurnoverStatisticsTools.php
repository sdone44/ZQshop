<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use App\Models\UserShop;

class TurnoverStatisticsTools
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT


$('.draw').on('click', function () {

    id = $(this).data('id');
    swal({ 
            title: "提款金额", 
            html:true,
            type: "prompt", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function(inputValue) { 
            re = inputValue.length;
            if(typeof(re) == "undefined"){
                return false;
            }else{
                $.ajax({
                    type : "GET",
                    url : "/admin/draw",
                    dataType : "json",
                    data : {
                        'id':id,
                        'val':inputValue
                    },
                    success : function(re) {
                        if(re==3){
                            swal('可提金额不足','','error');
                        }else if(re==1){
                            swal('提现成功!','','success');
                            window.location.reload();
                        }else{
                            swal('提现失败!','','error');
                            window.location.reload();
                        }
                            
                    },
                });
            }
                
    });
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        $UserShopModel = UserShop::find($this->id);

        return "<a class='label label-warning draw' data-id='{$this->id}' data-status='{}'>提现</a>";

    }

    public function __toString()
    {
        return $this->render();
    }
}