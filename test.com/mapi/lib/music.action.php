<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class musicModule  extends baseModule
{


	/**
	 * 添加音乐
	 */
	public function add_music(){

		$root = array();
		$root['status'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $music_type = intval($m_config['music_type']);
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID

			$audio_id = strim($_REQUEST['audio_id']);//音乐id ====>通过这个，比较本地音乐是否存在
            if($music_type == 1){
                $audio_data = explode('&amp;',$audio_id);
                $audio_id = $audio_data[0];
            }

			$sql = "select audio_id from ".DB_PREFIX."user_music where audio_id = '".$audio_id."' and user_id = ".$user_id;

			if (!$GLOBALS['db']->getRow($sql)){
				$audio_link = strim($_REQUEST['audio_link']);//音乐下载地址
				$lrc_link = strim($_REQUEST['lrc_link']);//歌词下载地址
				$audio_name = strim($_REQUEST['audio_name']);//歌曲名
				$artist_name = strim($_REQUEST['artist_name']);//演唱者
				$time_len = strim($_REQUEST['time_len']);//时长

				$user_music = array();
				$user_music['user_id'] = $user_id;
				$user_music['audio_id'] = $audio_id;
				$user_music['audio_link'] = $audio_link;
				$user_music['lrc_link'] = $lrc_link;
				$user_music['audio_name'] = $audio_name;
				$user_music['artist_name'] = $artist_name;
				$user_music['create_time'] = NOW_TIME;
				$user_music['time_len'] = $time_len;
				$user_music['api_type'] = 0;

                if($music_type==1){
                    $user_music['lrc_type'] = 0;
                    $req = $this->get_lyric($lrc_link);
                    $user_music['lrc_content'] = $req;
                    $user_music['music_type'] = 1;
                }else{
                    $lrc = $this->get_lrc($audio_id,0);
                    $user_music['lrc_type'] = $lrc['lrc_type'];
                    $user_music['lrc_content'] = $lrc['lrc_content'];
                    $user_music['music_type'] = 0;
                }
				$GLOBALS['db']->autoExecute(DB_PREFIX."user_music", $user_music,"INSERT");

				$root['audio_id'] = $audio_id;
			}else{
				$root['error'] = "已经存在,无需再添加";
				$root['status'] = 1;
			}
		}

		ajax_return($root);
	}

	/**
	 * 删除音乐
	 */
	public function del_music(){

		$root = array();
		$root['status'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $music_type = intval($m_config['music_type']);
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID

			$audio_id = strim($_REQUEST['audio_id']);//音乐id
            if($music_type==1){
                $audio_data = explode('&amp;',$audio_id);
                $audio_id = $audio_data[0];
            }

            $sql = "delete from ".DB_PREFIX."user_music where audio_id = '".$audio_id."' and user_id = ".$user_id;
			$GLOBALS['db']->query($sql);

			$root['status'] = 1;
		}

		ajax_return($root);
	}

	/**
	 * 用户的音乐列表
	 */
	public function user_music(){

		$root = array();
		$root['status'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $music_type = intval($m_config['music_type']);

			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
			$page = intval($_REQUEST['p']);//取第几页数据


			if($page==0)$page = 1;
			$page_size=20;
			$limit = (($page-1)*$page_size).",".$page_size;

			$sql = "select audio_id,audio_link,lrc_link,audio_name,artist_name,time_len,api_type,music_type  from ".DB_PREFIX."user_music where user_id = ".$user_id." and music_type=".$music_type." limit ".$limit;
			$list = $GLOBALS['db']->getAll($sql);
            foreach($list as $k=>$v){
                if($v['music_type']==1){
                    $list[$k]['audio_id'] = $v['audio_id'].'&'.$v['audio_link'].'&'.$v['lrc_link'];
                }
            }
			$root['list'] = $list;
			if (count($list) == $page_size)
				$root['has_next'] = 1;
			else
				$root['has_next'] = 0;

			//$sql = "select count(*) from ".DB_PREFIX."user_music where user_id = ".$user_id;

			//$root['count'] = $GLOBALS['db']->getOne($sql);
			$root['page'] = $page;//
			$root['status'] = 1;
		}

		ajax_return($root);
	}
	/**
	 * 搜索音乐列表
	 */
	public function search(){

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $music_type = intval($m_config['music_type']);
        if($music_type==1){//伴奏
            $root = array();
            $root['status'] = 1;


            $keyword = strim($_REQUEST['keyword']);//搜索关键字
            $page = intval($_REQUEST['p']);//取第几页数据


            $root['REQUEST'] = print_r($_REQUEST,1);

            if($page==0)$page = 1;
            $page_size=intval(10*$page);

            $list = $this->get_accompany($keyword,$page_size);

            $root['list'] = $list;

            $root['has_next'] = 0;
            $root['page'] = $page;//

            ajax_return($root);
        }else{
            $root = array();
            $root['status'] = 1;

            $keyword = strim($_REQUEST['keyword']);//搜索关键字
            $page = intval($_REQUEST['p']);//取第几页数据
            //$root['REQUEST'] = print_r($_REQUEST,1);

            if($page==0)$page = 1;
            $page_size=2;
            $limit = (($page-1)*$page_size).",".$page_size;

            $url = "http://tingapi.ting.baidu.com/v1/restserver/ting";//?method=baidu.ting.search.catalogSug&query=%E5%A4%A7
            fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');

            $params = array();
            $my_header = array();
            $my_header['Connection'] = 'keep-alive';
            $my_header['Origin'] = 'http://music.baidu.com';
            $my_header['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0';
            $my_header['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
            $my_header['Referer'] = 'http://music.baidu.com/';
            $my_header['Accept-Encoding'] = 'gzip, deflate, sdch';
            $my_header['Accept-Language'] = 'zh-CN,zh;q=0.8';
            $my_header['Host'] = 'tingapi.ting.baidu.com';

            $params['method'] = 'baidu.ting.search.catalogSug';
            $params['query'] = urlencode($keyword);

            $trans = new transport();
            $req = $trans->request($url,$params,'GET',$my_header);

            $req = json_decode($req['body'],1);

            $list = array();
            foreach ( $req['song'] as $k => $v )
            {
                $song = array();
                $song['api_type'] = 0;//http://tingapi.ting.baidu.com
                $song['audio_id'] = $v['songid'];
                $song['audio_name'] = $v['songname'];
                $song['artist_name'] = $v['artistname'];
                $song['audio_link'] = '';
                $song['lrc_link'] = '';
                $song['time_len'] = 0;

                $list[] = $song;
            }
            $root['list'] = $list;

            $root['has_next'] = 0;
            $root['page'] = $page;//


            ajax_return($root);

        }

	}
    /**
     * 返回下载音乐地址1
     */
	function get_lrc($audio_id,$api_type){
		$url = "http://tingapi.ting.baidu.com/v1/restserver/ting";//?method=baidu.ting.search.catalogSug&query=%E5%A4%A7
		fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');

		$params = array();
		$my_header = array();
		$my_header['Connection'] = 'keep-alive';
		$my_header['Origin'] = 'http://music.baidu.com';
		$my_header['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0';
		$my_header['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$my_header['Referer'] = 'http://music.baidu.com/';
		$my_header['Accept-Encoding'] = 'gzip, deflate, sdch';
		$my_header['Accept-Language'] = 'zh-CN,zh;q=0.8';
		$my_header['Host'] = 'tingapi.ting.baidu.com';

		$params['method'] = 'baidu.ting.song.lry';
		$params['songid'] = $audio_id;

		$trans = new transport();

		$req = $trans->request($url,$params,'GET',$my_header);
		$req = json_decode($req['body'],1);

		$lrc = array();
		$lrc['audio_id'] = $audio_id;
		$lrc['api_type'] = $api_type;
		$lrc['lrc_type'] = 0;
		$lrc['lrc_title'] = $req['title'];
		$lrc['lrc_content'] = $req['lrcContent'];
		if ($lrc['lrc_content'] != ''){
			$lrc['status'] = 1;
		}else{
			$lrc['status'] = 0;
		}



		return $lrc;
	}

	/**
	 * 获得歌词3
	 */
	public function getlrc(){

		$root = array();
		$root['status'] = 1;

		$user_id = intval($GLOBALS['user_info']['id']);//用户ID


		$audio_id = strim($_REQUEST['audio_id']);//
		$api_type = intval($_REQUEST['api_type']);//


		$sql = "select audio_id,api_type,lrc_type,audio_name from ".DB_PREFIX."user_music where audio_id = '".$audio_id."' and user_id = '".$user_id."'";
		$user_music = $GLOBALS['db']->getRow($sql);
		if ($user_music && $user_music['lrc_content'] != ''){
			$lrc = array();
			$lrc['audio_id'] = $audio_id;
			$lrc['api_type'] = $user_music['api_type'];
			$lrc['lrc_type'] = $user_music['lrc_type'];
			$lrc['lrc_title'] = $user_music['audio_name'];
			$lrc['lrc_content'] = $user_music['lrc_content'];
		}else{
			$lrc = $this->get_lrc($audio_id,$api_type);

			if ($GLOBALS['user_info']['id'] && $lrc['status'] == 1){
				$user_music = array();
				$user_music['lrc_type'] = $lrc['lrc_type'];
				$user_music['lrc_content'] = $lrc['lrc_content'];
				$GLOBALS['db']->autoExecute(DB_PREFIX."user_music", $user_music,"UPDATE","audio_id = '".$audio_id."' and user_id = '".$user_id."'");
			}

		}

		$root['lrc'] = $lrc;

		ajax_return($root);
	}

	/**
	 * 返回下载音乐地址
	 */
	public function downurl(){


		$root = array();
		$root['status'] = 1;

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $music_type = intval($m_config['music_type']);
        if($music_type == 1){
            //$user_id = intval($GLOBALS['user_info']['id']);//用户ID
            $audio_id = strim($_REQUEST['audio_id']);//
            $audio_data = explode('&amp;',$audio_id);
            $url = $audio_data[2];

            $req = $this->get_lyric($url);

            $audio['audio_link'] = $audio_data[1];
            $audio['audio_ext'] = 'mp3';
            $audio['lrc_content'] = $req;
            $audio['lrc_link'] = $audio_data[2];
            $root['audio'] = $audio;

            //}
            ajax_return($root);
        }else{
            $audio_id = strim($_REQUEST['audio_id']);//
            $api_type = intval($_REQUEST['api_type']);//

            //http://tingapi.ting.baidu.com/v1/restserver/ting?method=baidu.ting.song.downWeb&songid=262765491&bit=128&_t=1466082469621
            $url = "http://tingapi.ting.baidu.com/v1/restserver/ting";//?method=baidu.ting.search.catalogSug&query=%E5%A4%A7
            fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');


            $my_header = array();
            $my_header['Connection'] = 'keep-alive';
            $my_header['Origin'] = 'http://music.baidu.com';
            $my_header['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0';
            $my_header['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
            $my_header['Referer'] = 'http://music.baidu.com/';
            $my_header['Accept-Encoding'] = 'gzip, deflate, sdch';
            $my_header['Accept-Language'] = 'zh-CN,zh;q=0.8';
            $my_header['Host'] = 'tingapi.ting.baidu.com';


            $params = array();
            $params['method'] = 'baidu.ting.song.downWeb';
            $params['songid'] = $audio_id;
            $params['bit'] = 128;
            $params['_t'] = NOW_TIME * 1000 + rand(1,999);

            //echo 'sss';exit; 1466082469 621

            $trans = new transport();

            $req = $trans->request($url,$params,'GET',$my_header);
            $req = json_decode($req['body'],1);

            if ($req['bitrate']){
                $bitrate = $req['bitrate'][0];
                //print_r($req['bitrate']);
                //exit;

                $audio['audio_link'] = $bitrate['file_link'];
                if($audio['audio_link']==''){
                    log_err_file(array(__FILE__,__LINE__,__METHOD__,$audio_id,$bitrate));
                }
                $audio['audio_ext'] = $bitrate['file_extension'];
                $audio['audio_size'] = $bitrate['file_size'];
                $audio['time_len'] = $bitrate['file_duration'];

                //====================获得歌词===================
                $lrc = $this->get_lrc($audio_id,$api_type);


                $audio['lrc_type'] = 0;
                $audio['lrc_title'] = $lrc['lrc_title'];
                $audio['lrc_content'] = $lrc['lrc_content'];

                $root['audio'] = $audio;
            }else{
                log_err_file(array(__FILE__,__LINE__,__METHOD__,$req));
                $root = $this->downurl2(array('audio_id'=>$audio_id,'api_type'=>$api_type));
//				$root['error'] = "无法下载该音乐";
//				$root['status'] = 0;
            }
            ajax_return($root);
        }
	}
    /**
     * 返回下载音乐地址3
     */
    public function get_lyric($lrc_link){
        $url = $lrc_link;//?method=baidu.ting.search.catalogSug&query=%E5%A4%A7
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url );
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_POST, 1 ); //启用POST提交
        curl_setopt ($ch, CURLOPT_POSTFIELDS,$lrc_link);
        $result = curl_exec ($ch);
        curl_close($ch);

        $result = preg_replace('/<.{1,7},.{1,7}>/','',$result);
        return $result;
    }
    /**
     * 返回下载音乐地址2
     */
	public function downurl2($req){

		$root = array();
		$root['status'] = 1;

		$audio_id = strim($req['audio_id']);//
		$api_type = intval($req['api_type']);//

		//http://music.baidu.com/data/music/links?songIds=
		$url = "http://music.baidu.com/data/music/links?songIds=".$audio_id;
		fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');

		$trans = new transport();

		$req = $trans->request($url,'','GET');

		$req = json_decode($req['body'],1);

		if ($req['data']){
			$songList = $req['data']['songList'][0];

			$audio['audio_link'] = $songList['songLink'];
            if($audio['audio_link']==''){
                log_err_file(array(__FILE__,__LINE__,__METHOD__,$audio_id,$songList));
            }
			$audio['audio_ext'] = $songList['format'];
			$audio['audio_size'] = $songList['size'];
			$audio['time_len'] = $songList['time'];

			//====================获得歌词===================
			$lrc = $this->get_lrc($audio_id,$api_type);

			$audio['lrc_type'] = 0;
			$audio['lrc_title'] = $lrc['lrc_title'];
			$audio['lrc_content'] = $lrc['lrc_content'];

			$root['audio'] = $audio;
		}else{
            log_err_file(array(__FILE__,__LINE__,__METHOD__,$req));
			$root['error'] = "无法下载该音乐";
			$root['status'] = 0;
		}

		return $root;
	}
    /**
     * 获取伴奏
     */
     public function get_accompany($keyword,$page_size){

         if(0){
             $url = "http://search.aichang.cn/aichang/search/search.php";
             fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');

             $params = array();
             $my_header = array();
             $my_header['Connection'] = 'keep-alive';
             $my_header['Origin'] = 'http://www.aichang.cn/';
             $my_header['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0';
             $my_header['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
             $my_header['Referer'] = 'http://www.aichang.cn/';
             $my_header['Accept-Encoding'] = 'gzip, deflate, sdch';
             $my_header['Accept-Language'] = 'zh-CN,zh;q=0.8';
             $my_header['Host'] = 'search.aichang.cn';

             //$params['method'] = 'baidu.ting.search.catalogSug';
             $params['query'] = urlencode($keyword);

             $trans = new transport();

             $req = $trans->request($url,$params,'GET',$my_header);

             //$root[json] = $json;
             $req = json_decode($req['body'],1);
         }else{
             $url = "https://apis.aichang.cn/apiv5/banzou/searchv2.php";
             $params = array();
             $params['keyword'] = $keyword;
             $params['count'] = $page_size;
             $req = $this->curl_https($url,$params);
             $req = json_decode($req,1);

         }

         //echo 'sss';exit;
         if(isset($req['song']['other'])){
             $req['song'] = array_merge($req['song']['songs'],$req['song']['other']['songs']);
         }else{
             $req['song'] = $req['song']['songs'];
         }

         $list = array();
         foreach ( $req['song'] as $k => $v )
         {
             $song = array();
             $song['api_type'] = 0;
             $song['audio_id'] = $v['bzid'].'&'.$v['fullpath'].'&'.$v['fulllrcpath'];
             $song['audio_name'] = $v['name'];
             $song['artist_name'] = $v['singer'];
             $song['audio_link'] = $v['fullpath'];
             $song['lrc_link'] = $v['fulllrcpath'];
             $song['time_len'] = 0;

             $list[] = $song;
         }
         return $list;
     }
    /**
     * https 方法
     */
    public function curl_https($url, $data=array(), $header=array(), $timeout=30){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);

        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);

        return $response;

    }
}