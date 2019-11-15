<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\Auth;


class HomeController extends Controller
{
    public function index()
    {

        return Admin::content(function (Content $content) {
            $userInfo = Auth::guard('admin')->user();      //得到的一个用户信息
            $userChar = $userInfo->bind_phone?$userInfo->bind_phone:$userInfo->username;

            // $content->header();
            $content->description('您好~ ' . $userChar.'! 欢迎来到智趣商家管理系统');

            // $content->row(Dashboard::test());


            $content->row(function (Row $row) {

                $row->column(12, function (Column $column) {
                    $column->append(Dashboard::shopManageMenu());
                });

                // $row->column(4, function (Column $column) {
                //     $column->append(Dashboard::extensions());
                // });

            });
            $content->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });


            });

        });
    }
}
