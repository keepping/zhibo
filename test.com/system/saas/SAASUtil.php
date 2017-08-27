<?php
namespace App\Saas;

/**
 * SAAS系统业务操作工具栏
 *
 */
class SAASUtil
{
    
    /**
     * 生成一个随机的应用开发APPID，以fw字符开头并由数字和字母组成的18位字符串。
     *
     * @return 随机生成的APPID
     */
    public static function makeAppId()
    {
        return 'fw'.substr(md5(uniqid(mt_rand(),true)),8,16);
    }

    /**
     * 生成一个随机的应用开发APP密钥，由数字和字母组成的32位字符串。
     *
     * @return 随机生成的APP密钥
     */
    public static function makeAppSecret()
    {
        return md5(uniqid(mt_rand(),true));
    }
    
}

?>