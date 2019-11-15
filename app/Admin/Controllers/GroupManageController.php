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
use Illuminate\Http\Request;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use App\Admin\Extensions\GroupTools;



class GroupManageController extends Controller
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
            ->header('团购审核')
            ->description('审核列表')
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
        $grid->disableCreateButton();
        //$grid->disablePagination();
        $grid->disableExport();
        $grid->disableFilter();
        /*
        * 数据来源
        */
        $grid->model()->orderBy('review', 'asc')->orderBy('created_at', 'desc');

        $grid->goods_id('商品名')->display(function($gid){
            return ShopGoods::getGoodsName($gid);
        });
        $grid->primary_pic_url('商品主图')->display(function(){
            return ShopGoods::where('id',$this->goods_id)->pluck('primary_pic_url');
        })->image('', 75, 75);
        $grid->cur_num('已售量');
        $grid->max_num('商品总量');
        $grid->retail_price('团购价格');
        $grid->hot_desc('团购热词')->label();
        $grid->notice('团服须知')->display(function(){
            return '<pre>'.$this->notice.'</pre>';
        });
        $states = [
            'on'  => ['value' => 1, 'text' => '是', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '否', 'color' => 'default'],
        ];
        $grid->is_hot('是否推荐')->switch($states);
        $grid->start_time('开始时间');
        $grid->end_time('结束时间');
        $grid->created_at('创建时间');
        // $grid->updated_at('Updated at');
        $grid->review('审核状态')->display(function($review){
            $res = $review ? ($review==1 ? '<b>已通过</b>' : '<b style="color:red!important">未通过</b><br><span style="color:black">原因：'.$this->reason.'</span>') : '<a color="blue">待审核</a>';
            return $res;
        })->setAttributes(['style' => 'width:12%;']);
        // $grid->disableActions();
        $grid->actions(function ($actions) {
            if($this->row->review == 0){
                $actions->prepend(new GroupTools($actions->getKey()));
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
        // $form->ignore(['is_hot']);
        $form->hidden('uid');
        $form->hidden('shop_id');
        $states = [
            'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
        ];
        $form->switch('is_hot', '是否推荐')->states($states);
        $form->select('category_id','商品分类')->options(ShopCategory::selectOptions(true))->load('goods_id', '/admin/select-goods');
        $form->select('goods_id','商品名')->options(function($id){
            return ShopGoods::where('id', $id)->pluck('goods_name', 'id');
        })->rules('required')->load('original_price', '/admin/select-goods-price');
        $form->select('original_price','原价￥')->options(function($gid){
            return ShopGoods::where('id', $gid)->pluck('goods_name', 'id');
        })->rules('required')->help('商品原价');
        $form->currency('retail_price', '团购价格')->rules('required')->symbol('￥')->default(0.00);
        $form->number('max_num', '商品总量');
        $form->text('hot_desc', '团购热词')->help('用于搜索推广');
        $form->datetime('start_time', '开始时间')->default(date('Y-m-d H:i:s'));
        $form->datetime('end_time', '结束时间');

        $form->saving(function (Form $form) {

        });

        /*
        * 按钮显示控制
        */
        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            $tools->disableList();

            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉`查看`按钮
            $tools->disableView();

        });
        $form->footer(function ($footer) {

            // 去掉`重置`按钮
            // $footer->disableReset();

            // 去掉`提交`按钮
            // $footer->disableSubmit();

            // 去掉`查看`checkbox
            // $footer->disableViewCheck();

            // 去掉`继续编辑`checkbox
            // $footer->disableEditingCheck();

            // 去掉`继续创建`checkbox
            // $footer->disableCreatingCheck();

        });


        return $form;
    }

    public function groupAccept(Request $request)
    {
        $id = $request->id;

        $re = ShopGroup::where('id',$id)->update(['review'=>1]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }

    public function groupRefuse(Request $request)
    {
        $id = $request->id;

        $reason = $request->reason??'无';

        $re = ShopGroup::where('id',$id)->update(['review'=>2,'reason'=>$reason]);

        if($re){
            return 1;
        }else{
            return 0;
        }
    }
}
