<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends BaseController {


	//注册
	public function register(){
		if (!IS_POST) {
			$this->assign('CloseVerify',C('CloseVerify'));
			$this->display ();
		}else{
			$username = I("username");
			$password = I("password");
			$confirm_password = I("confirm_password");
			$email = I("email");
			$tel = I("tel");
			$v_code = I("v_code");
			
			if (!D('User')->checkEmail($email)) {
				return $this->message(L('email_incorrect'));
			}

			$check_tel_result = D('User')->checkTel($tel);
			
			if ($check_tel_result === -1) {
				return $this->message(L('tel_incorrect'));
			} else if ($check_tel_result === 1) {
				return $this->message(L('tel_exist'));
			}

			if (C('CloseVerify') || $v_code && $v_code == session('v_code') ) {
				if ( $password != '' && $password == $confirm_password) {

					if ( ! D("User")->isExist($username) ) {
						$ret = D("User")->register($username,$password, $email, $tel);
						if ($ret) {
							$this->message(L('register_succeeded'),U('Home/User/login'));					    
						}else{
							$this->message(L('username_or_password_incorrect'));
						}
					}else{
						$this->message(L('username_exists'));
					}

				}else{
					$this->message(L('code_much_the_same'));
				}
			}else{
					$this->message(L('verification_code_are_incorrect'));
			}
		}
	}



	//登录
	public function login()
	{
		if (!IS_POST) {
			//如果有cookie记录，则自动登录
			$cookie_token = cookie('cookie_token');
			if ($cookie_token) {
				$ret = D("UserToken")->getToken($cookie_token);
				if ($ret && $ret['token_expire'] > time() ) {
					$login_user = D("User")->where(array('uid' => $ret['uid']))->field('password', true)->find();
					session("login_user" , $login_user);
					$this->message(L('auto_login_succeeded'),U('Home/Item/index'));
					exit();
				}
			}
			$this->assign('CloseVerify',C('CloseVerify'));
		  	$this->display ();

		}else{
		  $username = I("username");
		  $password = I("password");
		  $v_code = I("v_code");
		  if (C('CloseVerify')) { //如果关闭验证码
		  	$ret = D("User")->checkLogin($username,$password);
		    if ($ret) {
		      session("login_user" , $ret );
		      $token = D("UserToken")->createToken($ret['uid']);
	          cookie('cookie_token',$token,60*60*24*90);//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
		      unset($ret['password']);
	          $this->message(L('login_succeeded'),U('Home/Item/index'));		        
		    }else{
		      $this->message(L('username_or_password_incorrect'));
		    }
		  }else{
			  if ($v_code && $v_code == session('v_code')) {
			    $ret = D("User")->checkLogin($username,$password);
			    if ($ret) {
			      session("login_user" , $ret );
		      	  $token = D("UserToken")->createToken($ret['uid']);
          		  cookie('cookie_token',$token,60*60*24*90);//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
			      unset($ret['password']);

		          $this->message(L('login_succeeded'),U('Home/Item/index'));		        
			    }else{
			      $this->message(L('username_or_password_incorrect'));
			    }

			  }else{
			    $this->message(L('verification_code_are_incorrect'));
			  }	
		  }
		  

		}
	}

	//生成验证码
	public function verify(){
	  //生成验证码图片
	  Header("Content-type: image/PNG");
	  $im = imagecreate(44,18); // 画一张指定宽高的图片
	  $back = ImageColorAllocate($im, 245,245,245); // 定义背景颜色
	  imagefill($im,0,0,$back); //把背景颜色填充到刚刚画出来的图片中
	  $vcodes = "";
	  srand((double)microtime()*1000000);
	  //生成4位数字
	  for($i=0;$i<4;$i++){
	  $font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255)); // 生成随机颜色
	  $authnum=rand(1,9);
	  $vcodes.=$authnum;
	  imagestring($im, 5, 2+$i*10, 1, $authnum, $font);
	  }
	  $_SESSION['v_code'] = $vcodes;

	  for($i=0;$i<200;$i++) //加入干扰象素
	  {
	    $randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
	    imagesetpixel($im, rand()%70 , rand()%30 , $randcolor); // 画像素点函数
	  }
	  ImagePNG($im);
	  ImageDestroy($im);
	}

	public function setting(){
		$user = $this->checkLogin();
		if (!IS_POST) {
		  $this->assign("user",$user);
		  $this->display ();

		}else{
			$username = $user['username'];
			$password = I("password");
			$new_password = I("new_password");
			$email = I("email");
			$tel = I("tel");

			if (!D('User')->checkEmail($email)) {
				return $this->message(L('email_incorrect'));
			}

			$check_tel_result = D('User')->checkTel($tel, $user['uid']);
			
			if ($check_tel_result === -1) {
				return $this->message(L('tel_incorrect'));
			} else if ($check_tel_result === 1) {
				return $this->message(L('tel_exist'));
			}

			$ret = D("User")->checkLogin($username,$password);
			if ($ret) {
				$ret = D("User")->updatePwd($user['uid'],$new_password, $email, $tel);
				if ($ret) {
					$this->message(L('modify_succeeded'),U("Home/Item/index"));
				}else{
					$this->message(L('modify_faild'));
				}
			}else{	
				$this->message(L('old_password_incorrect'));
			}
		}
	}

	//退出登录
	public function exist(){
		$login_user = $this->checkLogin();
		session("login_user" , NULL);
		cookie('cookie_token',NULL);
		session(null);
		$this->message(L('logout_succeeded'),U('Home/index/index'));
	}

	public function addTel() {
		$user = $this->checkLogin();
		if (!IS_POST){
			D("HttpStatus")->setStatus("405");
			return;
		}

		$json = file_get_contents('php://input');
		$jsonInfo = (array)json_decode($json);
		$tel = $jsonInfo["tel"];
		$check_tel_result = D('User')->checkTel($tel, $user['uid']);
			
		if ($check_tel_result === -1) {
			$data["errno"] = "400";
			$data["message"] = "手机号格式不正确";
			$this->ajaxReturn($data);
		} else if ($check_tel_result === 1) {
			$data["errno"] = "400";
			$data["message"] = "手机号已存在";
			$this->ajaxReturn($data);
		} else {
			$ret = D("User")->addTel($user['uid'], $tel);
			if ($ret) {
				$data["errno"] = "200";
				$data["message"] = "SUCCESS";
				$this->ajaxReturn($data);
			}else{
				$data["errno"] = "500";
				$data["message"] = "数据库添加异常";
				$this->ajaxReturn($data);
			}
		}
	}
}
