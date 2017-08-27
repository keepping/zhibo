<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PublicFileAction extends BaseAction
{
    public function do_upload()
    {
        if (intval($_REQUEST['upload_type']) == 0) {
            $result = $this->uploadFile();
        } else {
            $result = $this->uploadImage($_REQUEST['thumb']);
        }
        if ($result['status'] == 1) {
            $list = $result['data'];
            if (intval($_REQUEST['upload_type']) == 0) {
                $file_url = "." . $list[0]['recpath'] . $list[0]['savename'];
            } else {
                $file_url = "." . $list[0]['bigrecpath'] . $list[0]['savename'];
                $thumb_url = "." . $list[0]['thumbpath'] . $list[0]['savename'];
            }
            $public_url = $file_url;
            if ($GLOBALS['distribution_cfg']['OSS_TYPE'] && $GLOBALS['distribution_cfg']['OSS_TYPE'] != 'NONE') {
                if ($_REQUEST['dir'] != 'file') {
                    syn_to_remote_image_server($file_url);
                }
            }

            $avatar_data = json_decode(stripslashes($_POST['avatar_data']), true);
            if (!empty($avatar_data)) {
                $x = intval($avatar_data['x']);
                $y = intval($avatar_data['y']);
                $width = intval($avatar_data['width']);
                $height = intval($avatar_data['height']);
                $rotate = intval($avatar_data['rotate']);

                $file_url = get_cut_image($file_url, $x, $y, $width, $height, $rotate);
            }
            $response = array(
                'state' => 200,
                'message' => $result['info'],
                'result' => $file_url,
                'fullname' => get_spec_image($file_url),
                'dst' => strim($_REQUEST['dst']),
            );
            ajax_file_return($response);
        } else {
            $response = array(
                'state' => 0,
                'message' => $result['info'],
                'result' => null,
                'dst' => strim($_REQUEST['dst']),
            );
            ajax_file_return($response);
        }
    }

    public function do_cut_upload()
    {
        require_once APP_ROOT_PATH . "system/utils/crop.php";
        $width = floatval($_REQUEST['w']);
        $height = floatval($_REQUEST['h']);
        $dst = strim($_REQUEST['dst']);
        $crop = new CropAvatar($_POST['avatar_src'], $_POST['avatar_data'], $_FILES['avatar_file'], $width, $height);

        $response = array(
            'state' => 200,
            'message' => $crop->getMsg(),
            'result' => $crop->getResult(),
            'dst' => $dst
        );

        echo json_encode($response);
    }


    /**
     * 上传图片的通公基础方法
     *
     * @return array
     */
    protected function uploadImage($thumb)
    {
        if (conf("WATER_MARK") != "") {
            $water_mark = get_real_path() . conf("WATER_MARK");  //水印
        } else {
            $water_mark = "";
        }
        $alpha = conf("WATER_ALPHA");   //水印透明
        $place = conf("WATER_POSITION");  //水印位置

        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = conf('MAX_IMAGE_SIZE');  /* 配置于config */
        //设置上传文件类型

        $upload->allowExts = explode(',', conf('ALLOW_IMAGE_EXT')); /* 配置于config */

        $dir_name = to_date(get_gmtime(), "Ym");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "d");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "H");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }


        $save_rec_Path = "/public/attachment/" . $dir_name . "/origin/";  //上传时先存放原图
        $savePath = APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/"; //绝对路径
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/")) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/");
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/", 0777);
        }
        $domain_path = get_domain() . APP_ROOT . $save_rec_Path;

        $upload->saveRule = "uniqid";   //唯一
        $upload->savePath = $savePath;
        if ($thumb == 1) {
            $upload->thumb = true;
            $upload->thumbMaxWidth = 40;
            $upload->thumbMaxHeight = 50;
        }
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            foreach ($uploadList as $k => $fileItem) {
                $file_name = $fileItem['savepath'] . $fileItem['savename'];  //上图原图的地址
                //水印图
                $big_save_path = str_replace("origin/", "", $savePath);  //大图存放图径
                $big_file_name = str_replace("origin/", "", $file_name);

//					Image::thumb($file_name,$big_file_name,'',$big_width,$big_height);
                @file_put_contents($big_file_name, @file_get_contents($file_name));
                if (file_exists($water_mark)) {
                    Image::water($big_file_name, $water_mark, $big_file_name, $alpha, $place);
                }
                $big_save_rec_Path = str_replace("origin/", "", $save_rec_Path);  //上传的图存放的相对路径
                $uploadList[$k]['recpath'] = $save_rec_Path;
                $uploadList[$k]['bigrecpath'] = $big_save_rec_Path;
                $last_index = strrpos($save_rec_Path, '/') + 1;
                $uploadList[$k]['thumbpath'] = substr($save_rec_Path, 0,
                        $last_index) . 'thumb_' . substr($save_rec_Path, $last_index);
//        			if(app_conf("PUBLIC_DOMAIN_ROOT")!='')
//        			{
//	        			$origin_syn_url = app_conf("PUBLIC_DOMAIN_ROOT")."/es_file.php?username=".app_conf("IMAGE_USERNAME")."&password=".app_conf("IMAGE_PASSWORD")."&file=".get_domain().APP_ROOT."/public/attachment/".$dir_name."/origin/".$fileItem['savename']."&path=attachment/".$dir_name."/origin/&name=".$fileItem['savename']."&act=0";
//	        			$big_syn_url = app_conf("PUBLIC_DOMAIN_ROOT")."/es_file.php?username=".app_conf("IMAGE_USERNAME")."&password=".app_conf("IMAGE_PASSWORD")."&file=".get_domain().APP_ROOT."/public/attachment/".$dir_name."/".$fileItem['savename']."&path=attachment/".$dir_name."/&name=".$fileItem['savename']."&act=0";
//	        			@file_get_contents($origin_syn_url);
//	        			@file_get_contents($big_syn_url);
//        			}
            }
            return array("status" => 1, 'data' => $uploadList, 'info' => L("UPLOAD_SUCCESS"));
        } else {
            return array("status" => 0, 'data' => null, 'info' => $upload->getErrorMsg());
        }
    }


    /**
     * 上传文件公共基础方法
     *
     * @return array
     */
    protected function uploadFile()
    {
        $upload = new UploadFile();
        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'txt', 'zip', 'rar', 'pdf'),
        );

        //设置上传文件大小
        $upload->maxSize = conf('MAX_IMAGE_SIZE');  /* 配置于config */
        //设置上传文件类型
        if (!empty($ext_arr[$_REQUEST['dir']])) {
            $upload->allowExts = $ext_arr[$_REQUEST['dir']];
        } else {
            $upload->allowExts = explode(',', conf('ALLOW_IMAGE_EXT')); /* 配置于config */
        }


        $dir_name = to_date(get_gmtime(), "Ym");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "d");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "H");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }


        $save_rec_Path = "/public/attachment/" . $dir_name . "/";  //上传时先存放原图
        $savePath = APP_ROOT_PATH . "public/attachment/" . $dir_name . "/"; //绝对路径
        $domain_path = get_domain() . APP_ROOT . $save_rec_Path;


        $upload->saveRule = "uniqid";   //唯一
        $upload->savePath = $savePath;

        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            foreach ($uploadList as $k => $fileItem) {
                $uploadList[$k]['recpath'] = $save_rec_Path;
                if (app_conf("PUBLIC_DOMAIN_ROOT") != '') {
                    $syn_url = app_conf("PUBLIC_DOMAIN_ROOT") . "/es_file.php?username=" . app_conf("IMAGE_USERNAME") . "&password=" . app_conf("IMAGE_PASSWORD") . "&file=" . $domain_path . $fileItem['savename'] . "&path=attachment/" . $dir_name . "/&name=" . $fileItem['savename'] . "&act=0";
                    @file_get_contents($syn_url);
                }
            }
            return array("status" => 1, 'data' => $uploadList, 'info' => L("UPLOAD_SUCCESS"));
        } else {
            return array("status" => 0, 'data' => null, 'info' => $upload->getErrorMsg());
        }
    }

}

?>