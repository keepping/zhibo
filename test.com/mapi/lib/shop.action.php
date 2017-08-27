<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
fanwe_require(APP_ROOT_PATH . 'mapi/shop/shop.action.php');
fanwe_require(APP_ROOT_PATH . 'mapi/shop/pay.action.php');
fanwe_require(APP_ROOT_PATH . 'mapi/shop/pai_podcast.action.php');
fanwe_require(APP_ROOT_PATH . 'mapi/shop/pai_user.action.php');
fanwe_require(APP_ROOT_PATH . 'mapi/lib/address.action.php');
class shopModule extends baseModule
{

    /*
     * 分销商品列表
     * */
    public function distribution_goods_list(){
        $distribution_goods_list = new shopCModule();
        $distribution_goods_list->distribution_goods_list();
    }

    /*
     * 添加分销商品
     * */
    public function add_distribution_goods(){
        $add_distribution_goods = new shopCModule();
        $add_distribution_goods->add_distribution_goods();
    }

    /*
     * 商品详情页面
     * */
    public function goods_details(){

        $goods_details = new shopCModule();
        $goods_details->goods_details();
    }

    /*
     * 主播商品管理列表页面
     * */
    public function podcasr_goods_management(){
        $podcasr_goods_management = new shopCModule();
        $podcasr_goods_management->podcasr_goods_management();
    }

    /*
     * 购物订单列表页面
     * */
    public function shop_order_list(){
        $shop_order_list = new shopCModule();
        $shop_order_list->shop_order_list();
    }

    /*
     * 购物订单详情页面
     * */
    public function shop_order_details(){
        $shop_order_details = new shopCModule();
        $shop_order_details->shop_order_details();
    }

    /*
     * 查看物流信息
     * */
    public function see_boring(){

        $see_boring = new shopCModule();
        $see_boring->see_boring();

    }

    /*
 * 主播下架商品
 * */
    public function podcasr_shelves_goods(){

        $podcasr_shelves_goods = new pai_podcastCModule();
        $podcasr_shelves_goods->podcasr_shelves_goods();
    }

    /*
     * 主播删除下架商品
     * */
    public function podcasr_delete_goods(){

        $podcasr_delete_goods = new pai_podcastCModule();
        $podcasr_delete_goods->podcasr_delete_goods();
    }

    /*
     * 主播清空下架商品
     * */
    public function podcasr_empty_goods(){

        $podcasr_empty_goods = new pai_podcastCModule();
        $podcasr_empty_goods->podcasr_empty_goods();
    }

    /*
     * 观众端购物商品列表页面
     * */
    public function shop_goods_list(){

        $shop_goods_list = new shopCModule();
        $shop_goods_list->shop_goods_list();
    }

    /*
     * 观众端购物商品详情页面
     * */
    public function shop_goods_details(){

        $shop_goods_details = new shopCModule();
        $shop_goods_details->shop_goods_details();
    }

    /*
     * 购物订单结算页面--买给主播
     * */
    public function order_settlement(){

        $order_settlement = new pai_userCModule();
        $order_settlement->order_settlement();
    }

    /*
     * 购物订单结算页面--买给自己
     * */
    public function order_settlement_user(){

        $order_settlement = new pai_userCModule();
        $order_settlement->order_settlement_user();
    }

    /**
     * 创建购物订单
     */
    public function create_shop_order(){

        $create_shop_order = new pai_userCModule();
        $create_shop_order->create_shop_order();
    }

    /*
     * 购物个人中心我的订单列表页面
     * */
    public function shop_order(){

        $shop_order = new shopCModule();
        $shop_order->shop_order();
    }

    /*
     * 购物个人中心我的订单列表页面删除订单
     * */
    public function shop_order_del(){

        $shop_order = new shopCModule();
        $shop_order->shop_order_del();
    }

    /*
     * 新增收货地址页面
     * */
    public function new_address(){

        $new_address = new shopCModule();
        $new_address->new_address();
    }

    /*
     * 保存收货地址
     * */
    public function editaddress(){
        $editaddress = new addressModule();
        $editaddress->editaddress();
    }

    /*
     * 查看购物订单详情
     * */
    public function virtual_shop_order_details(){
        $virtual_shop_order_details = new shopCModule();
        $virtual_shop_order_details->virtual_shop_order_details();
    }

    /*
     * 判断是否有库存
     * */
    public function goods_inventory(){

        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }

        $shop_info = $_REQUEST['shop_info'];
        $shop_info=json_decode($shop_info,true);
        $startTrans = $GLOBALS['db']->StartTrans(); //开始事物
        $root['status'] = 1;
        foreach($shop_info as $key => $value){
            $sql = "UPDATE ".DB_PREFIX."goods SET inventory=inventory-".intval($value['number'])." WHERE inventory>=".intval($value['number'])." and is_effect=1 and id=".intval($value['goods_id']);
            $GLOBALS['db']->query($sql);//减去库存
            if(!$GLOBALS['db']->affected_rows()){
                $goods_name = $GLOBALS['db']->getOne("SELECT name FROM ".DB_PREFIX."goods WHERE is_effect=1 and id=".$value['goods_id']);
                $root['status'] = 0;
                $root['error'] = '商品:“'."$goods_name".'”...库存不足.';
                $GLOBALS['db']->Rollback($startTrans);
                break;
            }
        }
        if($root['status'] == 1){
            $GLOBALS['db']->Commit($startTrans);
        }
        api_ajax_return($root);
    }

    /*
     * 购物车
     * */
    public function shop_shopping_cart(){
        $shop_shopping_cart = new shopCModule();
        $shop_shopping_cart->shop_shopping_cart();
    }

    /*
     * 加入购物车
     * */
    public function join_shopping(){
        $join_shopping = new shopCModule();
        $join_shopping->join_shopping();
    }

    /*
     * 修改购物车商品
     * */
    public function update_shopping_goods(){
        $update_shopping_goods = new shopCModule();
        $update_shopping_goods->update_shopping_goods();
    }

    /*
     * 删除购物车商品
     * */
    public function delete_shopping_goods(){
        $delete_shopping_goods = new shopCModule();
        $delete_shopping_goods->delete_shopping_goods();
    }

}
