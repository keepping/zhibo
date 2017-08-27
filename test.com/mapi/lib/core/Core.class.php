<?php

/**
 *
 */
class Core
{
    /**
     * 报错方法
     * @param  string $msg [description]
     */
    public static function error($msg = '')
    {
        header('Content-Type: text/html; charset=utf-8');
        if (IS_DEBUG) {
            $msg = $msg ?: '出错了！';
            die('Message:' . $msg);
        } else {
            echo '<!DOCTYPE html><html><head><title>Laravel</title><style>html, body {height: 100%;}body {margin: 0;padding: 0;width: 100%;display: table;font-weight: 100;}.container {text-align: center;display: table-cell;vertical-align: middle;}.content {text-align: center;display: inline-block;}.title {font-size: 96px;}</style></head><body><div class="container"><div class="content"><div class="title">404!页面找不到哦，2s后返回上一页</div></div></div><script>window.setTimeout("history.back(-1)",2000); </script></body></html>';
            die;
        }
    }
}
