<?php
namespace Home\Controller;
use Think\Controller;
class FileController extends BaseController {
    
    //展示某个项目的单个文件
    public function index(){
        import("Vendor.Parsedown.Parsedown");
        $file_id = I("file_id/d");
        $file = D("Files")->where(" file_id = '$file_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $file['item_id'])) {
            $this->message(L('no_permissions'));
            return;
        }

        $ItemPermn = $this->checkItemPermn($login_user['uid'] , $file['item_id']) ;
        $ItemCreator = $this->checkItemCreator($login_user['uid'],$file['item_id']);
 
        $this->assign("file" , $file);
        $this->display();
    }
    
    //展示单个页面
    public function single(){
        import("Vendor.Parsedown.Parsedown");
        $file_id = I("file_id/d");
        $file = D("Files")->where(" file_id = '$file_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $file['item_id'],$_SERVER['REQUEST_URI'])) {
            $this->message(L('no_permissions'));
            return;
        }

        $ItemPermn = $this->checkItemPermn($login_user['uid'] , $file['item_id']) ;
        $ItemCreator = $this->checkItemCreator($login_user['uid'],$file['item_id']);

        $this->assign("file" , $file);
        $this->assign("login_user" , $login_user);
        $this->display();
    }
    
    //编辑页面
    public function edit(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d");
        $item_id = I("item_id/d");

        $file_history_id = I("file_history_id/d");
        $copy_file_id = I("copy_file_id/d");

        if ($file_id > 0 ) {
            if ($file_history_id) {
                $file = D("FileHistory")->where(" file_history_id = '$file_history_id' ")->find();
            }else{
                $file = D("Files")->where(" file_id = '$file_id' ")->find();
            }
            $default_cat_id = $file['cat_id'];
        }
        /** 不支持文件复制接口
        elseif ($copy_file_id) {
            $copy_file = D("Files")->where(" file_id = '$copy_file_id' ")->find();
            $file['file_title'] = $copy_file['file_title']."-copy";
            $file['file_url'] = $copy_file['file_url'];
            $file['item_id'] = $copy_file['item_id'];
            $default_cat_id = $copy_file['cat_id'];

        } */
        else{
            //查找用户上一次设置的目录
            $last_file = D("Files")->where(" author_uid ='$login_user[uid]' and $item_id = '$item_id' ")->order(" addtime desc ")->limit(1)->find();
            $default_cat_id = $last_file['cat_id'];
        }

        $item_id = $file['item_id'] ?$file['item_id'] :$item_id;

        
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $Catalog = D("Catalog")->where(" cat_id = '$default_cat_id' ")->find();
        if ($Catalog['parent_cat_id']) {
            $default_second_cat_id = $Catalog['parent_cat_id'];
            $default_child_cat_id = $default_cat_id;

        }else{
            $default_second_cat_id = $default_cat_id;
        }
        $this->assign("file" , $file);
        $this->assign("item_id" , $item_id);
        $this->assign("default_second_cat_id" , $default_second_cat_id);
        $this->assign("default_child_cat_id" , $default_child_cat_id);

        $this->display();        
    }
    
    //保存
    public function save(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;
        $file_title = I("file_title") ?I("file_title") : L("default_title");
        $file_url = I("file_url") ?I("file_url") : "";
        $file_comments = I("file_comments") ?I("file_comments") :'';
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $item_id = I("item_id/d")? I("item_id/d") : 0;
        $s_number = I("s_number/d")? I("s_number/d") : 99;

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $data['file_title'] = $file_title ;
        $data['file_url'] = $file_url ;
        $data['file_comments'] = $file_comments ;
        $data['s_number'] = $s_number ;
        $data['item_id'] = $item_id ;
        $data['cat_id'] = $cat_id ;
        $data['addtime'] = time();
        $data['author_uid'] = $login_user['uid'] ;
        $data['author_username'] = $login_user['username'];

        if ($file_id > 0 ) {
            
            //在保存前先把当前页面的版本存档
            $file = D("Files")->where(" file_id = '$file_id' ")->find();
            $insert_history = array(
                'file_id'=>$file['file_id'],
                'item_id'=>$file['item_id'],
                'cat_id'=>$file['cat_id'],
                'file_title'=>$file['file_title'],
                'file_comments'=>$file['file_comments'],
                'file_url'=>$file['file_url'],
                's_number'=>$file['s_number'],
                'addtime'=>$file['addtime'],
                'author_uid'=>$file['author_uid'],
                'author_username'=>$file['author_username'],
                );
             D("FileHistory")->add($insert_history);

            $ret = D("Files")->where(" file_id = '$file_id' ")->save($data);

            //统计该file_id有多少历史版本了
            $Count = D("FileHistory")->where(" file_id = '$file_id' ")->Count();
            if ($Count > 20 ) {
               //每个单页面只保留最多20个历史版本
               $ret = D("FileHistory")->where(" File_id = '$file_id' ")->limit("20")->order("file_history_id desc")->select();
               D("FileHistory")->where(" file_id = '$file_id' and file_history_id < ".$ret[19]['file_history_id'] )->delete();
            }

            //更新项目时间
            D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time"=>time()));

            $return = D("Files")->where(" file_id = '$file_id' ")->find();
        }else{
            
            $file_id = D("Files")->add($data);

            //更新项目时间
            D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time"=>time()));

            $return = D("Files")->where(" file_id = '$file_id' ")->find();
        }
        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }
        $this->sendResult($return);
        
    }

    //删除页面
    public function delete(){
        $file_id = I("file_id/d")? I("file_id/d") : 0;
        $file = D("Files")->where(" file_id = '$file_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $file['item_id']) && $login_user['uid'] != $file['author_uid']) {
            $this->message(L('no_permissions_to_delete_file',array("author_username"=>$file['author_username'])));
            return;
        }

        if ($file) {
            
            $ret = D("Files")->where(" file_id = '$file_id' ")->delete();
            //更新项目时间
            D("Item")->where(" item_id = '$file[item_id]' ")->save(array("last_update_time"=>time()));

        }
        if ($ret) {
           $this->message(L('delete_succeeded'),U("Home/item/show?item_id={$file['item_id']}"));
        }else{
           $this->message(L('delete_failed'),U("Home/item/show?item_id={$file['item_id']}"));
        }
    }

    //历史版本
    public function history(){
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;
        $this->assign("file_id" , $file_id);

        $FileHistory = D("FileHistory")->where("file_id = '$file_id' ")->order(" addtime desc")->limit(10)->select();

        if ($FileHistory) {
            foreach ($FileHistory as $key => &$value) {
                $value['file_title'] = $file_title ? $file_title : $value['file_title'] ;
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
        }

        $this->assign("FileHistory" , $FileHistory);

        $this->display();        

    }
    
    //上传页面
    public function upload(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d");
        $item_id = I("item_id/d");

        $file_history_id = I("file_history_id/d");
        $copy_file_id = I("copy_file_id/d");

        if ($file_id > 0 ) {
            if ($file_history_id) {
                $file = D("FileHistory")->where(" file_history_id = '$file_history_id' ")->find();
            }else{
                $file = D("Files")->where(" file_id = '$file_id' ")->find();
            }
            $default_cat_id = $file['cat_id'];
        }
        //如果是复制接口
        elseif ($copy_file_id) {
            $copy_file = D("Files")->where(" file_id = '$copy_file_id' ")->find();
            $file['file_title'] = $copy_file['file_title']."-copy";
            $file['file_url'] = $copy_file['file_url'];
            $file['item_id'] = $copy_file['item_id'];
            $default_cat_id = $copy_file['cat_id'];

        }else{
            //查找用户上一次设置的目录
            $last_file = D("Files")->where(" author_uid ='$login_user[uid]' and $item_id = '$item_id' ")->order(" addtime desc ")->limit(1)->find();
            $default_cat_id = $last_file['cat_id'];
        }

        $item_id = $file['item_id'] ?$file['item_id'] :$item_id;

        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $Catalog = D("Catalog")->where(" cat_id = '$default_cat_id' ")->find();
        if ($Catalog['parent_cat_id']) {
            $default_second_cat_id = $Catalog['parent_cat_id'];
            $default_child_cat_id = $default_cat_id;

        }else{
            $default_second_cat_id = $default_cat_id;
        }
        $this->assign("file" , $file);
        $this->assign("item_id" , $item_id);
        $this->assign("default_second_cat_id" , $default_second_cat_id);
        $this->assign("default_child_cat_id" , $default_child_cat_id);

        $this->display();
    }
    
    //上传图片
    public function uploadImg(){
        $qiniu_config = C('UPLOAD_SITEIMG_QINIU') ;
        if (!empty($qiniu_config['driverConfig']['secrectKey'])) {
          //上传到七牛
          $Upload = new \Think\Upload(C('UPLOAD_SITEIMG_QINIU'));
          $info = $Upload->upload($_FILES);
          $url = $info['editormd-image-file']['url'] ;
          echo json_encode(array("url"=>$url,"success"=>1));
        }else{
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize  = 3145728 ;// 设置附件上传大小
            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath = './Public/Uploads/';// 设置附件上传目录
            $upload->savePath = '';// 设置附件上传子目录
            $info = $upload->upload() ;
            if(!$info) {// 上传错误提示错误信息
              $this->error($upload->getError());
              return;
            }else{// 上传成功 获取上传文件信息
              $url = substr($upload->rootPath,2).$info['editormd-image-file']['savepath'].$info['editormd-image-file']['savename'] ;
              echo json_encode(array("url"=>$url,"success"=>1));
            }
        }
    }

    //比较两个版本，同时显示两份内容
    public function diff(){
        $login_user = $this->checkLogin();
        $file_history_id = I("file_history_id/d");
        $file_id = I("file_id/d");

        $file = D("Files")->where(" file_id = '$file_id' ")->find();
        $cur_file_url = $file['file_url'];

        $item_id = $file['item_id'] ?$file['item_id'] :$item_id;

        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $file = D("FileHistory")->where(" file_history_id = '$file_history_id' ")->find();
        $history_file_url = $file_url ? $file_url : $file['file_url'] ;
        
        $this->assign("cur_file_url" , $cur_file_url);
        $this->assign("history_file_url" , $history_file_url);
        $this->display(); 
    }

    // 关注与取消关注
    public function watch() {
        if (!IS_POST){
            D("HttpStatus")->setStatus("405");
            return;
        }
        
        $login_user = $this->checkLogin(false);
        // 读取application/json流
        $json = file_get_contents('php://input');
        $jsonInfo = (array)json_decode($json);
        $file_id = $jsonInfo["id"];
        $watch = $jsonInfo["state"];
        
        if (!$file_id) {
            $data["errno"] = "400";
            $data["message"] = "请求参数错误";
            $this->ajaxReturn($data);
            return;
        }

        $is_watched = D("Item")->findWatched("0", "file", $file_id, $login_user['uid']);

        $data["errno"] = "200";
        $data["message"] = "SUCCESS";

        if ($is_watched && $watch == "1") {
            $where["uid"] = $login_user['uid'];
            $where["file_id"] = $file_id;

            D("FileUser")->where($where)->delete();
        } elseif (!$is_watched && $watch == "0") {
            $add['uid'] = $login_user['uid'];
            $add['file_id'] = $file_id;
            M('FileUser')->data($add)->add();
        }
        
        $data["data"] = D("Item")->findWatched("1", "file", $file_id, null);
        $this->ajaxReturn($data);
    }

    // 关注列表与当前用户关注状态
    public function getWatchData() {
        if (!IS_POST){
            D("HttpStatus")->setStatus("405");
            return;
        }

        $login_user = $this->checkLogin(false);
        // 读取application/json流
        $json = file_get_contents('php://input');
        $jsonInfo = (array)json_decode($json);
        $file_id = $jsonInfo["id"];
        
        if (!$file_id) {
            $data["errno"] = "400";
            $data["message"] = "请求参数错误";
            $this->ajaxReturn($data);
            return;
        }

        $watch = D("Item")->findWatched("2", "file", $file_id, $login_user['uid']);

        $data["errno"] = "200";
        $data["message"] = "SUCCESS";
        $data["data"] = $watch;
        $this->ajaxReturn($data);
    }
}