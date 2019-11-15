<?php

namespace App\Admin\Controllers;

use App\Models\Comment;
use App\Models\ShopOrder;
use App\User;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
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
            ->header('评论管理')
            ->description('用户评论管理')
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
        $grid = new Grid(new Comment);
        /*
         * 功能显示控制
         */
        $grid->disableCreateButton();
        //$grid->disablePagination();
        $grid->disableExport();
        $grid->disableFilter();
        $userInfo = Auth::guard('admin')->user()->toArray();
        $bindPhone = $userInfo['bind_phone'];
        $isExistShop = $userInfo['shop_id'];
        $isAdmin = Admin::user()->isAdministrator();
        if(!$isAdmin){ //最高管理员身份进入
            $grid->model()->where('shop_id', '=', $isExistShop);
        }
        //$grid->id('Id');
        $grid->user_id('用户昵称')->display(function($uid){
            return User::find($uid)->nickname;
        });
        //$grid->shop_id('商铺ID');
        $grid->order_id('订单编号')->display(function($orderId){
            return ShopOrder::find($orderId)->order_sn;
        });
        $grid->content('评论内容')->display(function($content){
            return "<span style='color:blue;max-width:200px;display:block;'>$content</span>";
        });
        $grid->grade('评分')->label();
        $grid->list_pic_url('评论图片')->display(function($listPicUrl){
            $imgUrl = '<img src="%s" style="max-width:160px;max-height:160px" class="img img-thumbnail">';
            $listPic = Comment::getListImg($listPicUrl,$imgUrl);
            return $listPic;
        }); 
        $grid->created_at('评论时间');
        $grid->actions(function ($actions) {
                // $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
                
        });
        //$grid->updated_at('Updated at');

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
        $show = new Show(Comment::findOrFail($id));

        $show->id('Id');
        $show->user_id('User id');
        $show->shop_id('Shop id');
        $show->order_id('Order id');
        $show->content('Content');
        $show->grade('Grade');
        $show->list_pic_url('List pic url');
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
        $form = new Form(new Comment);

        $form->number('user_id', 'User id');
        $form->number('shop_id', 'Shop id');
        $form->number('order_id', 'Order id');
        $form->text('content', 'Content');
        $form->decimal('grade', 'Grade')->default(0.0);
        $form->text('list_pic_url', 'List pic url');

        return $form;
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
