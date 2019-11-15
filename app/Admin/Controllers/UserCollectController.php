<?php

namespace App\Admin\Controllers;

use App\Models\UserCollect;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use App\Admin\Extensions\CollectTools;
use Illuminate\Http\Request;

class UserCollectController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('用户申请信息')
            ->description('信息列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserCollect);

        /*
        * 按钮显示控制
        */
        $grid->disableCreateButton();
        //$grid->disableActions();

        $grid->user_name('用户名');
        $grid->code_pic('用户二维码')->display(function($picUrl){
            // dd($picUrl);
            $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
            $reUrl = sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$picUrl);
            return $reUrl;
        });
        //$grid->shop_name('商铺名');
        //$grid->shop_icon('商铺图标')->display(function($picUrl){
            // dd($picUrl);
            //$imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
            //$reUrl = sprintf($imgUrl,config('filesystems.disks.oss.url').'/'.$picUrl);
            //return $reUrl;
        //});
        //$grid->shop_desc('商铺描述');
        $grid->bind_phone('绑定手机');
        $grid->address('商家地址');
        $grid->review_type('申请类型')->display(function($type){
            $userType = '';
            if($type==0){
                $userType = '后台用户';
            }else if($type==1){
                $userType = '平台商家';
            }else if($type==2){
                $userType = '自营商家';
            }else if($type==3){
                $userType = '拼团队长';
            }else{
                $userType = '无';
            }
            return $userType;
        })->label('primary');
        $grid->review_status('审核状态')->display(function($review){
            $res = $review ? ($review==1 ? '<b>已通过</b>' : '<b style="color:red!important">未通过</b><br><span style="color:black">原因：'.$this->reason.'</span>') : '<a color="blue">待审核</a>';
            return $res;
        })->setAttributes(['style' => 'width:12%;']);
        $grid->created_at('提交时间');
        // $grid->updated_at('Updated at');
      	$grid->actions(function ($actions) {
            if($this->row->review_status == 0){
                $actions->prepend(new CollectTools($actions->getKey()));
            }
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        }); 

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(UserCollect::findOrFail($id));

        $show->id('Id');
        $show->user_name('User name');
        $show->shop_name('Shop name');
        $show->shop_icon('Shop icon');
        $show->shop_desc('Shop desc');
        $show->bind_phone('Bind phone');
        $show->address('Address');
        $show->review_status('Review status');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserCollect);

        $form->text('user_name', '联系人');
        $form->text('shop_name', '商铺名');
        $form->text('shop_icon', '商家图标');
        $form->text('shop_desc', '商家描述');
        $form->text('bind_phone', '绑定手机');
        $form->text('address', '地址');

        return $form;
    }
  
  	public function collectRefuse(Request $request)
    {
        $id = $request->id;

        $reason = $request->reason??'无';

        $re = UserCollect::where('id',$id)->update(['review_status'=>2,'reason'=>$reason]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }
}
