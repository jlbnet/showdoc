<?php
namespace Home\Model;
use Home\Model\BaseModel;

class UserModel extends BaseModel {

    /**
     * 用户名是否已经存在
     * 
     */
    public function isExist($username){
        return  $this->where("username = '%s'",array($username))->find();
    }

    /**
     * 注册新用户
     * 
     */
    public function register($username,$password,$email,$tel){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        return $this->add(array(
            'username'=>$username,
            'password'=>$password, 
            'reg_time'=>time(), 
            'email'=>$email,
            'tel'=>$tel
        ));
    }

    //修改用户密码
    public function updatePwd($uid, $password, $email, $tel){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        return $this->where("uid ='%d' ",array($uid))->save(array('password'=>$password, 'email'=>$email, 'tel'=>$tel));   
    }

    public function addTel($uid, $tel) {
        return $this->where("uid ='%d' ",array($uid))->save(array('tel'=>$tel));  
    }

    /**
     * 返回用户信息
     * @return 
     */
    public function userInfo($uid){
        return  $this->where("uid = '%d'",array($uid))->find();
    }
    
    /**
     *@param username:登录名  
     *@param password 登录密码   
     */
    
    public function checkLogin($username,$password){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        $where=array($username,$password);
        return $this->where("username='%s' and password='%s'",$where)->find();
    }

    public function checkEmail($email) {
        $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";//定义正则表达式  
        if($email != ""){
            if(preg_match($checkmail,$email)){                       //用正则表达式函数进行判断  
                return true;
            }else{  
                return false;
            }  
        } else {
            return true;
        }
    }

    /**
     * 手机号验证
     * @param tel 手机号
     * @return -1 --- 手机号格式错误
     *         0  --- 一切正常
     *         1  --- 已存在
     */
    public function checkTel($tel, $uid = null) {
        $checkTel = '/^1[345678]{1}\d{9}$/';
        if (!preg_match($checkTel, $tel)) {
            return -1;
        } else if ($this->checkExistTel($tel, $uid)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 验证手机号在数据库是否已存在
     */
    public function checkExistTel($tel, $uid) {
        $where = array();
        $where['tel'] = array('eq', $tel);
        if ($uid) {
            $where['uid'] = array('neq', $uid);
        }
        return $this->where($where)->select();
    }

    /**
     * 用户是否有手机号
     */
    public function existTel($uid) {
        return $this->where('uid=%s', $uid)->getField('tel');
    }
    
}