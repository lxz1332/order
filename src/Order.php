<?php

namespace cwkj\order;

use think\facade\Db;
use cwkj\money\Money;

class Order {

    //支付
    public function pay($order_id, $price_user, $cate_pay = 1, $jifen_user = 0) {
        if (!Db::name("goods_order")->where("order_id", $order_id)->update(['order_cate_pay' => $cate_pay, 'order_price_user' => $price_user, 'order_jifen_user' => $jifen_user, 'order_status' => 2, 'order_time_pay' => time()])) {
            return false;
        }
        return true;
    }

    //取消订单
    public function quxiao($order_id) {
        Db::startTrans();
        try {
            $find = Db::name('goods_order')->where('order_id', $order_id)->field('order_sn,user_id,order_price_user,order_jifen_user,order_status,order_cate_pay,order_time_pay')->find();
            if ($find['order_status'] == 3) {
                return ['code' => 0, 'msg' => '已经取消'];
            }
            $data['order_status'] = 3;
            $data['order_time_quxiao'] = time();
            $res = Db::name('goods_order')->where('order_id', $order_id)->update($data);
            if (!$res) {
                return ['code' => 0, 'msg' => '状态修改失败'];
            }
            if ($find['order_time_pay'] > 0) {
                //余额积分退款
                if ($find['order_cate_pay'] == 1) {
                    $Money = new Money();
                    if ($find['order_price_user'] > 0) {
                        $Money->add($find['user_id'], 1, $find['order_price_user'], '订单' . $find['order_sn'], 9);
                    }
                    if ($find['order_jifen_user'] > 0) {
                        $Money->add($find['user_id'], 2, $find['order_jifen_user'], '订单' . $find['order_sn'], 9);
                    }
                }
                //
            }
            // 提交事务
            Db::commit();
            return ['code' => 1, 'msg' => '操作成功'];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

}
