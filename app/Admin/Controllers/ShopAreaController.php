<?php

namespace App\Admin\Controllers;

use App\Models\ShopArea;
use App\Models\CityAddress;
use App\Models\AreaTabs;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopAreaController extends Controller
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

            $content->header('市场区域列表');
            $content->description('区域管理');

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

            $content->body(Admin::show(ShopArea::findOrFail($id), function (Show $show) {

                $show->id();
                $show->area_name('区域名');
                $show->status('状态');
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
        return Admin::grid(ShopArea::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->city('城市')->display(function ($city) {
                // dd($this->area_id);
                return CityAddress::getCityAddress($this->city);
            });
            $grid->district('地区')->display(function ($district) {
                // dd($this->area_id);
                return CityAddress::getAddress($this->district);
            });
            $grid->area_name('区域名');
            $grid->tabs_id('区域标签')->select(AreaTabs::all()->pluck('name','id'));

            $grid->lv('等级')->display(function ($lv) {
                // dd($this->area_id);
                return $lv?'真实地址':'虚拟地址';
            });            
            // 设置text、color、和存储值
            $states = [
                'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
            ];
            $grid->status('运营状态')->switch($states);
            // $grid->created_at();
            // $grid->updated_at();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(ShopArea::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->select('city','城市')->options(['1'=>'广州市'])->load('district', '/admin/select-district');
            $form->select('district','区/县')->options(function($id){
                return CityAddress::where('id', $id)->pluck('name', 'id');
            });
            $form->text('area_name', '代理区域')
                ->rules('required');
            $form->select('tabs_id','区域标签')->options(AreaTabs::all()->pluck('name','id'))->rules('required');
            $states = [
                'on'  => ['value' => 1, 'text' => '开', 'color' => 'primary'],
                'off' => ['value' => 0, 'text' => '关', 'color' => 'default'],
            ];
            $form->switch('status', '运营状态')->states($states);
            $form->number('sort_order', '排序')->default(255);
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');

        });
    }

    public function district(Request $request)
    {
        $pid = $request->get('q');

        return CityAddress::where('pid', $pid)->get(['id', DB::raw('name as text')]);
    }

    public function area(Request $request)
    {
        $district = $request->get('q');

        return ShopArea::where('district', $district)->get(['id', DB::raw('area_name as text')]);
    }
}
