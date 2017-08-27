<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserPaymentNoticeAction extends CommonAction{
	public function index()
	{
		if(trim($_REQUEST['user_id'])!='')
		{
			$user_id = intval($_REQUEST['user_id']);
			$map['user_id'] = $user_id;
		}

		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		
		$model = M ("UserLog");
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$list = $this->get("list");
		$this->assign("list",$list);
		$this->display();
		return;
	}

	
}
?>