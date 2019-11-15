<?php

namespace App\Admin\Controllers;

use App\Models\IndexCarousel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class IndexCarouselController extends Controller
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

            $content->header('首页管理');
            $content->description('轮播图管理');

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

            $content->header('Detail');
            $content->description('description');

            $content->body(Admin::show(IndexCarousel::findOrFail($id), function (Show $show) {

                $show->id();

                $show->created_at();
                $show->updated_at();
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
        return Admin::grid(IndexCarousel::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->title('标题');
            $grid->pic_url('图片')->image('',70,70);
            $states = [
                'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
            ];
            $grid->states('启用状态')->switch($states);
            $grid->sort_order('排序');
            // $grid->created_at('创建时间');
            // $grid->updated_at('更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(IndexCarousel::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('title','标题');
            $form->image('pic_url','图片')->uniqueName();
            $form->number('sort_order','排序')
                ->default(0);
            $states = [
                'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
            ];
            $form->switch('states', '启用状态')->states($states);
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
