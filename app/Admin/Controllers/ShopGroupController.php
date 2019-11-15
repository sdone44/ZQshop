<?php

namespace App\Admin\Controllers;

use App\Models\ShopGroup;
use App\Models\ShopGoods;
use App\Models\ShopCategory;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use App\Admin\Extensions\GroupTools;


class ShopGroupController extends Controller
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
            ->header('团购管理')
            ->description('我的团购')
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
            ->header('创建时间团购')
            ->description('团购信息')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ShopGroup);
        /*
        * 功能显示控制
        */
        // $grid->disableCreateButton();
        //$grid->disablePagination();
        $grid->disableExport();
        $grid->disableFilter();
        /*
        * 数据来源
        */
        $userInfo = Auth::guard('admin')->user();
        $grid->model()->where('uid','=',$userInfo->id);

        $grid->goods_id('商品名')->display(function($gid){
            return ShopGoods::getGoodsName($gid);
        });
        $grid->primary_pic_url('商品主图')->display(function(){
            return ShopGoods::where('id',$this->goods_id)->pluck('primary_pic_url');
        })->image('', 75, 75);
        $grid->cur_num('已售量');
        $grid->max_num('商品总量');
        $grid->retail_price('团购价格');
        $grid->hot_desc('团购热词');
        $grid->state('团购状态')->display(function($state){
            $now = date('Y-m-d H:i:s',time());
            $start_time = $this->start_time;
            $state =  ($now < $this->start_time) ? '等待开始' : ($now < $this->end_time ? '进行中' : '已结束');
            return $state;
        })->label();
        $grid->notice('团服须知')->display(function(){
            return '<pre>'.$this->notice.'</pre>';
        });

        $grid->start_time('开始时间');
        $grid->end_time('结束时间');
        $grid->created_at('创建时间');
        // $grid->updated_at('Updated at');
        if(Admin::user()->isRole('my-shop')){
            $grid->review('审核状态')->display(function($review){
                $res = $review ? ($review==1 ? '<b>已通过</b>' : '<a style="color:red!important">未通过<a><br><span style="color:black">原因：'.$this->reason.'</span>') : '<a color="blue">审核中</a>';
                return $res;
            })->setAttributes(['style' => 'width:12%;']);
        }
        
        $grid->actions(function ($actions) {
            if($this->row->review == 2){
                $actions->disableDelete();
                //$actions->disableEdit();
                $actions->disableView();
            }else{
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
            }
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
        $show = new Show(ShopGroup::findOrFail($id));

        $show->id('Id');
        $show->uid('Uid');
        $show->goods_id('Goods id');
        $show->start_time('Start time');
        $show->end_time('End time');
        $show->state('State');
        $show->max_num('Max num');
        $show->cur_num('Cur num');
        $show->retail_price('Retail price');
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
        $form = new Form(new ShopGroup);
        // $form->ignore(['category_id']);
        $form->hidden('uid');
        $form->hidden('shop_id');
        $form->hidden('review');
        $form->hidden('reason');

        $form->select('category_id','商品分类')->options(ShopCategory::selectOptions(true))->load('goods_id', '/admin/select-goods');
        $form->select('goods_id','商品名')->options(function($id){
            return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
        })->rules('required')->load('original_price', '/admin/select-goods-price');
        $form->select('original_price','原价￥')->options(function($originalPrice){
            $gid = $this->goods_id;
            return ShopGoods::where('id', $gid)->pluck('retail_price', 'retail_price');
        })->rules('required')->help('商品原价');

        $form->currency('retail_price', '团购价格')->rules('required')->symbol('￥')->default(0.00)->rules('required|min:0.01');
        $form->number('max_num', '商品总量')->rules('required|min:1');
        $form->text('hot_desc', '团购热词')->help('用于搜索推广');
        $form->datetime('start_time', '开始时间')->default(date('Y-m-d H:i:s'));
        $form->datetime('end_time', '结束时间');
        $form->textarea('notice','团服须知');

        $form->saving(function (Form $form) {
            $userInfo = Auth::guard('admin')->user()->toArray();
            $form->uid = $userInfo['id'];
            $form->shop_id = $userInfo['shop_id'];

            if($form->start_time > $form->end_time){
                $error = new MessageBag([
                    'title'   => '时间错误',
                    'message' => '结束时间不能小于开始时间！',
                ]);
                return back()->with(compact('error'));
            }
            if($form->max_num   == 0){
                $error = new MessageBag([
                    'title'   => '数量错误！',
                    'message' => '商品总量不能为零',
                ]);
                return back()->with(compact('error'));
            }
            if(!$form->retail_price > 0){
                $error = new MessageBag([
                    'title'   => '团购价格错误！',
                    'message' => '团购价格不能为零！',
                ]);
                return back()->with(compact('error'));
            }
            //编辑时提交重置状态
            if(!$id = $form->model()->id){
                $form->review = 0;
                $form->reason = '';
            }else{
                $form->review = 0;
                $form->reason = '';
            }

        });

        /*
        * 按钮显示控制
        */
        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            // $tools->disableList();

            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉`查看`按钮
            $tools->disableView();

        });


        return $form;
    }
}
