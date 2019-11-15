<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Table;
use App\User;    // 引入模型
use Encore\Admin\Widgets\Tab;
class UsersController extends Controller
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

            $content->header('用户管理');
            $content->description('用户列表');

            $content->body($this->grid());
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

            $content->header('用户的编辑');
            $content->description('用户的编辑');

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

            $content->header('用户添加');
            $content->description('用户添加');

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
        return Admin::grid(User::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->avatar('用户头像')->image('',50, 50);
            $grid->column('name','用户名')->editable();
            $grid->phone('用户手机号')->display(function ($phone) {
                return $phone ? $phone : '未绑定';
            })->label('primary');
            $grid->column('nickname','用户微信昵称');
            
            $grid->role('用户身份')->display(function ($role) {
                // dd($this->area_id);
                $userRole = '';
                if($role==1){
                    $userRole = '商家';
                }else{
                    $userRole = '普通用户';
                }
                return $userRole;
            })->label();
            $grid->column('login_ip','最近登陆ip');
            $grid->column('login_time','最近登陆时间');
            
            
            // 搜索功能
          	$grid->filter(function ($filter) {
				// 去掉默认的id过滤器
                $filter->disableIdFilter();
                // 在这里添加字段过滤器
                $filter->like('phone', '手机号');
                //$filter->between('created_at', 'Created Time')->datetime();
            });
            
            $grid->disableExport();// 禁用导出
            $grid->disableCreation();// 禁用新增
 			$grid->disableRowSelector();//禁用行多选
        });
    }

     /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(User::class, function (Form $form) {
            $form->text('name', '用户名');
            $form->mobile('phone','用户手机号');
            $states = [
                'on'  => ['value' => 1, 'text' => '商家', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '普通用户', 'color' => 'default'],
            ];
            //$form->password('password', '用户密码')->help('修改用户密码');
            $form->switch('role', '用户状态')->states($states);
        });
    }

}
