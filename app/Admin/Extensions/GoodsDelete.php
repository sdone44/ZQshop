<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class GoodsDelete
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    id = $(this).data('id');
    swal({ 
            title: "确认删除?", 
            type: "warning", 
            showCancelButton: true, 
            closeOnConfirm: false, 
            confirmButtonText: "确定",
            cancelButtonText:"取消", 
            confirmButtonColor: "#ec6c62" 
        }, function() { 
            $.ajax({
                type : "GET",
                url : "/admin/goods-delete",
                dataType : "json",
                data : {
                    'id':id,
                },
                success : function(test) {
                    if(test){
                        swal('删除成功!','','success');
                        window.location.reload();
                    }else{
                        swal('删除失败!','','error');
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


        return "<a class='grid-check-row' data-id='{$this->id}'><i style='cursor:pointer' class='fa fa-trash'></i></a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}