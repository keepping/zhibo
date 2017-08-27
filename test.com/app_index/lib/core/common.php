<?php

//编译生成css文件
function parse_css($urls)
{
    $path = get_resource_path('css', $urls);
    return "/" . $path . "?v=" . app_conf("DB_VERSION");
}

/**
 *
 * @param $urls 载入的脚本
 * @param $encode_url 需加密的脚本
 */
function parse_script($urls, $encode_url = array())
{
    $path = get_resource_path('js', $urls, $encode_url);
    return "/" . $path . "?v=" . app_conf("DB_VERSION");
}

function get_resource_path($type, $urls, $encode_url = null)
{
    $statics_path = 'public/runtime/statics/';
    $app_info = json_decode(@file_get_contents(APP_ROOT_PATH . $statics_path . 'app.json'), true);
    if (empty($app_info)) {
        $app_info = array();
    }

    $key = md5(implode(',', $urls));

    if (!IS_DEBUG && isset($app_info[$key]) && file_exists(APP_ROOT_PATH . $app_info[$key]['path'])) {
        return $app_info[$key]['path'];
    }

    if (!file_exists(APP_ROOT_PATH . $statics_path)) {
        mkdir(APP_ROOT_PATH . $statics_path, 0777, true);
    }

    switch ($type) {
        case 'js':
            $path = get_script_path($urls, $encode_url);
            break;
        case 'css':
            $path = get_css_path($urls);
            break;
        default:
            return;
    }

    if (empty($app_info[$key]) || $app_info[$key]['path'] != $path) {
        $app_info [$key] = array(
            'type' => $type,
            'path' => $path,
            'urls' => $urls,
            'encode_url' => $encode_url,
        );
        @file_put_contents(APP_ROOT_PATH . $statics_path . 'app.json', json_encode($app_info));
    }
    return $path;
}

function get_css_path($urls)
{
    $statics_path = 'public/runtime/statics/';
    $tmpl_path = $GLOBALS['tmpl']->_var['TMPL'];

    $css_content = '';
    foreach ($urls as $url) {
        $css_content .= @file_get_contents($url);
    }
    $css_content = preg_replace("/[\r\n]/", '', $css_content);
	$css_content = str_replace("../dist/statics/images/",$tmpl_path."/dist/statics/images/",$css_content);
    $css_url = $statics_path . md5($css_content) . '.css';
    if (!file_exists(APP_ROOT_PATH . $css_url)) {
        @file_put_contents(APP_ROOT_PATH . $css_url, $css_content);
        if ($GLOBALS['distribution_cfg']['CSS_JS_OSS']) {
            syn_to_remote_file_server($css_url);
        }
    }
    return $css_url;
}

function get_script_path($urls, $encode_url = array())
{
    $statics_path = 'public/runtime/statics/';
    if (count($encode_url) > 0) {
        require_once APP_ROOT_PATH . "system/libs/javascriptpacker.php";
    }

    $js_content = '';
    foreach ($urls as $url) {
        $append_content = @file_get_contents($url) . "\r\n";
        if (in_array($url, $encode_url)) {
            $packer = new JavaScriptPacker($append_content);
            $append_content = $packer->pack();
        }
        $js_content .= $append_content;
    }

    $js_url = $statics_path . md5($js_content) . '.js';
    if (!file_exists(APP_ROOT_PATH . $js_url)) {
        @file_put_contents(APP_ROOT_PATH . $js_url, $js_content);
        if ($GLOBALS['distribution_cfg']['CSS_JS_OSS']) {
            syn_to_remote_file_server($js_url);
        }
    }
    return $js_url;
}


/*ajax返回*/
function api_ajax_return($data,$is_debug=false)
{

    if($_REQUEST['post_type']!='json'){
        header("Content-Type:text/html; charset=utf-8");
        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        $GLOBALS['tmpl']->assign('data',$data);
        $tmpl_dir=$GLOBALS['class_name'].'-'.$GLOBALS['act'].'.html';

        $GLOBALS['tmpl']->display($tmpl_dir);
        die();
    }else{
        echo json_encode($data);
        die();
    }

}

?>