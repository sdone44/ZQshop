<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;
use App\Models\UserCollect;

class CollectTools
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.accept-group').on('click', function () {
    // Your code.
    console.log($(this).data('id'));

    id = $(this).data('id');

    swal({ 
            title: "审核通过?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "同意",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/group-accept",
                dataType : "json",
                data : {
                    'id':id,
                },
                success : function(res) {
                    if(res){
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



$('.refuse-collect').on('click', function () {

    id = $(this).data('id');
    swal({ 
            title: "驳回原因", 
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
                    url : "/admin/collect-refuse",
                    dataType : "json",
                    data : {
                        'id':id,
                        'reason':inputValue
                    },
                    success : function(test) {
                        if(test){
                            swal('已驳回!','','success');
                            window.location.reload();
                        }else{
                            swal('驳回失败!','','error');
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

        $UserCollectModel = UserCollect::find($this->id);

        return "<a class='label label-danger refuse-collect' data-id='{$this->id}' data-status='{}'>驳回</a>";

    }

    public function __toString()
    {
        return $this->render();
    }
}