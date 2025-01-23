<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Db;
use think\App;
use think\facade\Request;
use app\common\model\YpayAccount as M;
use app\common\validate\YpayAccount as V;
use app\common\service\YpayUser as S;
use think\facade\Config;

class YpayAccount
{
    // 添加
    public static function goAdd($data)
    {
        $user = Db::table('ypay_user')->where('id',Session::get('front.id'))->find();
        if(empty($user['vip_time'])){
            return ['msg'=>"未开通会员套餐",'code'=>201];
        }
        $time = strtotime($user['vip_time']);
        if($time<time())
        {
            return ['msg'=>"会员套餐已过期",'code'=>201];
        }

        $data['user_id'] = Session::get('front.id');
        $channel = Db::table('admin_channel')->where('code',$data['code'])->find();
        $data['type'] = $channel['type'];
        $data['succcount'] = 0;
        $data['succprice'] = 0;
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        if(empty($channel))
        {
            return ['msg'=>"通道不存在或标识重复",'code'=>201];
        }
        

        if($data['code']=="wxpay_dy")
        {
            $data['status'] = 1;
        }
 
        if($data['type']=="qqpay"){
            if(empty($data['qq']))
            {
                return ['msg'=>"qq号不可为空",'code'=>201];
            }
            
        }
        if($data['code']=='alipay_app' || $data['code']=='wxpay_app' || $data['code']=='wxpay_zg' )
        {
            $data['status'] = 1;
        }
        
        
        if(!empty($data['aliappkey'])){
            $data['qr_url'] = $data['aliappkey'];
        }
        if($data['code']=='alipay_dmf')
        {
            $data['wxname'] = $data['zfbapppid'];
            $data['status'] = 1;
        }
 
        
        if(!empty($data['remark'])){
            $data['remark'] = strip_tags($data['remark']);
        }
    
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    
    //修改支付宝当面付通道
    public static function goEditAliPay($data){
        $update = [];
        try {
            if($data['code'] == 'alipay_dmf'){
                $update = 
                [
                    'wxname' => $data['appId'],
                    'cookie' => $data['publicKey'],
                    'qr_url' => $data['privateKey'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'alipay_app'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //修改微信APP挂机/自挂/店员通道
    public static function goEditWxPay($data){
        $update = [];
        try {
            if($data['code'] == 'wxpay_dy'){
                $update = 
                [
                    'wxname' => $data['wxname'],
                    'qr_url' => $data['qr_url'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else{
                $update = 
                [
                    'qr_url' => $data['qr_url'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //修改QQ通道
    public static function goEditQQPay($data){
        $update = [];
        try {
            if($data['code'] == 'qqpay_zg'){
                $update = 
                [
                    'qq' => $data['qq'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 编辑
    public static function goEdit($data,$id)
    {
        //获取通道ID
        $new_data['id'] = $id;
        //获取更改后的微信昵称
        $new_data['wxname'] = $data['wxname'];
        //验证
        // $validate = new V;
        // if(!$validate->scene('edit')->check($data))
        // return ['msg'=>$validate->getError(),'code'=>201];
        try {
             M::update($new_data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 更改在线状态
    public static function goStatus($data,$id)
    {
        $model =  M::find($id);
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save([
                'status' => $data,
            ]);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //更改使用状态
    public static function goIsStatus($data,$id)
    {
        $model =  M::find($id);
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save([
                'is_status' => $data,
            ]);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 删除
    public static function goRemove($id)
    {
        $model = M::find($id);
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
           $model->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 批量删除
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            M::destroy($ids);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 一键清理所有离线通道
    public static function goRemove_line($data)
    {   
        if(empty($data['type'])){
            return ['code' => 201,'msg' =>'请先选择类型'];
        }
         $where = ['status' => 0,'type'=>$data['type']];
        try{
            M::where($where)->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //获取二维码信息
    public static function GetQrlistQrcode($id)
    {
        $acc = Db::table('ypay_account')->where('id', $id)->where('user_id',S::getUserId())->find();
        if(empty($acc))
        {
            return ['code'=>0,'msg'=>'通道不存在!'];
        }
        
        return ['code'=>0,'msg'=>'系统错误!'];
    }
    
}


