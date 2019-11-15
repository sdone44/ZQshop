<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopGoods extends Model
{
    const STATE_ON_SALE = 1;// 上架中
    const STATE_NOT_SALE = 0;// 已下架

    const STATE_NOT_DELETE = 0;// 正常
    const STATE_DELETE = 1;// 已删除

    const STATE_SALE_LIMIT = 1;// 限购商品
    const STATE_SALE_NOT_LIMIT = 0;// 非限购商品

    const STATE_SALE_RECOMMEND = 1;// 推荐商品
    const STATE_SALE_NOT_RECOMMEND = 0;// 非推荐商品

    const STATE_SALE_NEW = 1;// 新品
    const STATE_SALE_NOT_NEW = 0;// 非新品

    const STATE_VIP = 1;// 会员专属
    const STATE_NOT_VIP = 0;// 非会员专属

    const STATE_ON_SALE_STRING = '上架中';
    const STATE_NOT_SALE_STRING = '已下架';

    const STATE_NOT_DELETE_STRING = '正常';
    const STATE_DELETE_STRING = '已删除';

    const STATE_SALE_LIMIT_STRING = '限购商品';
    const STATE_SALE_NOT_LIMIT_STRING = '非限购商品';

    const STATE_SALE_RECOMMEND_STRING = '推荐商品';
    const STATE_SALE_NOT_RECOMMEND_STRING = '非推荐商品';

    const STATE_SALE_NEW_STRING = '新品';
    const STATE_SALE_NOT_NEW_STRING = '非新品';

    const STATE_VIP_STRING = '会员专属';
    const STATE_NOT_VIP_STRING = '非会员专属';

    //
    public function shop_category()
    {
        return $this->belongsTo(ShopCategory::class,'category_id');
    }

    public function products()
    {
        return $this->hasMany(ShopProduct::class, 'goods_id');
    }

    public function checked_products()
    {
        return $this->hasOne(ShopProduct::class, 'goods_id');
    }


    public function specifications()
    {
        return $this->hasMany(ShopGoodsSpecification::class, 'goods_id');
    }

    public function goods_attribute()
    {
        return $this->hasMany(ShopGoodsAttribute::class, 'goods_id');
    }

    // public function user_shop()
    // {
    //     return $this->belongsToMany(UserShop::class,'user_shops_shop_goods','good_id','id');//第三个参数是你定义关联关系模型的外键名称，第四个参数你要连接到的模型的外键名称
    // }

    // 是否上架
    public static function getSaleDispayMap()
    {
        return [
            self::STATE_ON_SALE => self::STATE_ON_SALE_STRING,
            self::STATE_NOT_SALE => self::STATE_NOT_SALE_STRING,
        ];
    }

    // 商品删除状态
    public static function getDeleteDispayMap()
    {
        return [
            self::STATE_NOT_DELETE => self::STATE_NOT_DELETE_STRING,
            self::STATE_DELETE => self::STATE_DELETE_STRING,
        ];
    }
    // 是否限购
    public static function getLimitDispayMap()
    {
        return [
            self::STATE_SALE_LIMIT => self::STATE_SALE_LIMIT_STRING,
            self::STATE_SALE_NOT_LIMIT => self::STATE_SALE_NOT_LIMIT_STRING,
        ];
    }
    // 是否推荐
    public static function getRecommendDispayMap()
    {
        return [
            self::STATE_SALE_RECOMMEND => self::STATE_SALE_RECOMMEND_STRING,
            self::STATE_SALE_NOT_RECOMMEND => self::STATE_SALE_NOT_RECOMMEND_STRING,
        ];
    }

    // 是否新品
    public static function getNewDispayMap()
    {
        return [
            self::STATE_SALE_NEW => self::STATE_SALE_NEW_STRING,
            self::STATE_SALE_NOT_NEW => self::STATE_SALE_NOT_NEW_STRING,
        ];
    }

    // 是否是会员专属
    public static function getVipDispayMap()
    {
        return [
            self::STATE_VIP => self::STATE_VIP_STRING,
            self::STATE_NOT_VIP => self::STATE_NOT_VIP_STRING,
        ];
    }

    // 多图上传处理
    public function getListPicUrlAttribute($pictures)
    {
        if (is_string($pictures)) {
            return json_decode($pictures, true);
        }

        return $pictures;
    }

    public function setListPicUrlAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['list_pic_url'] = json_encode($pictures);
        }
    }

    public function getDetailsPicUrlAttribute($pictures)
    {
        if (is_string($pictures)) {
            return json_decode($pictures, true);
        }

        return $pictures;
    }

    public function setDetailsPicUrlAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['details_pic_url'] = json_encode($pictures);
        }
    }
    // 获取商品列表
    public static function getGoodsList($where= [],$pagesize='' ,$order='sort_order asc'){
        $model =  static::where(array_merge([
//            ['is_delete', '=', static::STATE_NOT_DELETE],
//            ['is_on_sale', '=', static::STATE_ON_SALE],
        ], $where))->orderByRaw($order);
        if($pagesize){
            return $model->paginate($pagesize);
        }
        return $model->get();
    }

    // 获取商品详情
    public static function getGoodsDetail($where,$product_id =0){
        if($product_id){
            return static::with(['checked_products' => function ($query) use($product_id) {
                $query->where('id', '=', $product_id);
            }])->where(array_merge([
                ['is_delete', '=', static::STATE_NOT_DELETE],
                ['is_on_sale', '=', static::STATE_ON_SALE],
            ], $where))->first();
        }
        return static::where(array_merge([
            ['is_delete', '=', static::STATE_NOT_DELETE],
            ['is_on_sale', '=', static::STATE_ON_SALE],
        ], $where))->first();
    }

    //获取商品详情及规格
    public static function getGoodsDetailByProduct($productInfo){
        // dd($productInfo);
        $specification_info = [];
        foreach($productInfo as $product){
            $specification_ids = explode('_',$product->goods_specification_ids);
            $specification_names = explode('_',$product->goods_specification_names);
            $spec_item_ids = explode('_',$product->goods_spec_item_ids);
            $spec_item_names = explode('_',$product->goods_spec_item_names);
            // $price = $product->retail_price;
            // $productId = $product->id;
                foreach($specification_ids as $k =>$s_ids){
                $specification_info[$s_ids]['sp_id'] = $s_ids;
                $specification_info[$s_ids]['sp_name'] = $specification_names[$k];
                if(!isset($specification_info[$s_ids]['items'])){
                    $specification_info[$s_ids]['items'] = [];
                }
                $specification_info[$s_ids]['items'][$spec_item_ids[$k]] = [
                    'sp_item_id' =>$spec_item_ids[$k],
                    'sp_item_name' =>$spec_item_names[$k],
                    // 'price' =>$price,
                    // 'product_id'=>$productId
                ];
            }
        }
        $specification_info = array_values($specification_info);

        return $specification_info;
    }
    
    public static function getGoodsInfo($gid){
        return static::where([
            ['is_delete', '=', static::STATE_NOT_DELETE],
            ['is_on_sale', '=', static::STATE_ON_SALE],
            ['id', '=', $gid],
        ])->first();
    }

    //获取商品名
    public static function getGoodsName($id){
        $goods = static::where('id',$id)->get(['goods_name']);
        if(!count($goods)==0){
            return $goods[0]['goods_name'];
        }
        return '无此商品';
    }

    //根据商品id获取商品详情
    public static function getGoodDetail($id){
        $goods = static::where('id',$id)->get()->toArray();
        return $goods[0];
    }

    //根据商品id获取简略商品详情
    public static function getGoodSimpleDetail($id){
        $conditon = ['goods_name','category_id','goods_desc','primary_pic_url','group_pic_url'];
        $goods = static::where('id',$id)->get($conditon)->toArray();
        return $goods[0];
    }
}
