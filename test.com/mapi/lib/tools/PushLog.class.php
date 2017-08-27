<?php

/**
 *
 */
class PushLog
{
    public static function log($content)
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        $handle = fopen(APP_ROOT_PATH . 'public/push.log', "a");
        if ($handle) {
            fwrite($handle, date('Y-m-d H:i:s') . ':' . $content . PHP_EOL);
            fclose($handle);
        }
    }
}
