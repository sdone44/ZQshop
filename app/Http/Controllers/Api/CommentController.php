<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ShopGoodsLogic;
use App\Models\ShopOrder;
use App\Models\Comment;
use App\User;
use App\Logic\OrderLogic;

use Illuminate\Support\Facades\Validator;


class CommentController extends ApiController
{
    
    //商家评论列表
    public function commentList(Request $request)
    {

        //参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
            ],
            [
                'shop_id.required' => 'order_id缺失',
            ]
        );

        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        $shopId = $request->shop_id;

        $shopCommentList = Comment::where('shop_id',$shopId)->get()->toArray();
        $shopCommentListPaginate = Comment::where('shop_id',$shopId)->paginate(10)->toArray();

        if(empty($shopCommentList)){
            return ['msg'=>'暂无评论'];
        }
        // dd($shopCommentList);
        $shopCommentListRes = [];
        foreach ($shopCommentList as $k => $commentListInfo) {
            $shopCommentListRes[$k] = $commentListInfo;

            $shopCommentListRes[$k]['userInfo'] = User::find($commentListInfo['user_id'])?User::find($commentListInfo['user_id'])->toArray():'该用户不存在';
            $shopCommentListRes[$k]['orderInfo'] = ShopOrder::getOrderAndOrderGoodsListForComment(['id'=>$commentListInfo['order_id']])?ShopOrder::getOrderAndOrderGoodsListForComment(['id'=>$commentListInfo['order_id']])->toArray():'订单信息不存在';
        }

        if(!empty($shopCommentListRes)){
            $shopCommentListPaginate['data'] = $shopCommentListRes;
            return $this->success($shopCommentListPaginate);
        }
        return $this->failed('获取评论列表失败', 402);
    }



    //用户评论
    public function userComment(Request $request)
    {

        //参数校验
        $validator = Validator::make($request->all(),
            [
                'shop_id' => 'required',
                'order_id' => 'required',
                'user_id' => 'required',
                'grade' => 'required',
            ],
            [
                'shop_id.required' => 'shop_id缺失',
                'order_id.required' => 'order_id缺失',
                'user_id.required' => 'user_id缺失',
                'grade.required' => 'grade缺失',

            ]
        );
        if ($validator->fails()) {
            return $this->failed($validator->errors(), 403);
        }

        //当前商铺
        $orderId = $request->order_id;
        $userId = $request->user_id;
        $content = $request->content??'';
      	$listPicUrl = $request->list_pic_url??'';
        $grade = $request->grade;
      	$shopId = $request->shop_id;

        $orderModel = ShopOrder::find($orderId);
        if($orderModel->order_status == 42){
            return ['msg'=>'订单已评价！'];
        }


        $CommentModel = new Comment;

        $CommentModel->order_id = $orderId;
        $CommentModel->user_id = $userId;
        $CommentModel->content = $content;
        $CommentModel->grade = $grade;
      	$CommentModel->shop_id = $shopId;
      	$CommentModel->list_pic_url = $listPicUrl;


        $res = $CommentModel->save();

        if($res){
            $orderModel->order_status = 42;
            $orderModel->save();
            return ['msg'=>'评论成功'];
        }
        return $this->failed('评论失败', 402);
    }

}