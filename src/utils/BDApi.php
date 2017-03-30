<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 16-7-28
 * Time: 10:35
 * BDApi.php baidu public api
 */

namespace inhere\library\utils;

use inhere\library\helpers\CurlHelper;

/**
 * Baidu 提供的免费api,查询一些公共信息(ip, 天气...)
 */
class BDApi
{
    const OPEN_API = 'http://apis.baidu.com/apistore';

    /**
     * ip地址信息查询
     * @param string $ip ip address
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function ipInfo($ip, $apikey)
    {
        $url = self::OPEN_API. '/iplookupservice/iplookup';

        return self::send($url, ['ip' => $ip], ["apikey: $apikey"]);
    }

    /**
     * 手机号码归属地的查询，获取号码在的省份以及对应的运营商
     *
     * API JSON返回示例 :
     *   {
     *       errNum: 0,
     *       errMsg: "success",
     *       retData: {
     *          telString: "15846530170", //手机号码
     *          province: "黑龙江",    // 省份
     *          carrier: "黑龙江移动"  // 运营商
     *       }
     *   }
     * @param string $mobilephone mobile phone number
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function mobilePhoneInfo($mobilephone, $apikey)
    {
        $url = self::OPEN_API. '/mobilephoneservice/mobilephone';

        return self::send($url, ['tel' => $mobilephone], ["apikey: $apikey"]);
    }

    /**
     * 查询手机号的归属地信息
     *
     * api JSON返回示例 :
     *   {
     *       "errNum": 0,
     *       "retMsg": "success",
     *       "retData": {
     *           "phone": "15210011578", // 手机号码
     *           "prefix": "1521001", // 手机号码前7位
     *           "supplier": "移动 ", // 移动
     *           "province": "北京 ", // 省份
     *           "city": "北京 ", // 城市
     *           "suit": "152卡" // 152卡
     *       }
     *   }
     * @param string $mobilenumber mobile phone number
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function mobileNumberInfo($mobilenumber, $apikey)
    {
        $url = self::OPEN_API. '/mobilenumber/mobilenumber';

        return self::send($url, ['tel' => $mobilenumber], ["apikey: $apikey"]);
    }

    /**
     * 彩票种类查询
     * @param string $lotteryType 彩票类型，
     *                            1 表示全国彩 e.g. 双色球,七星彩,超级大乐透，
     *                            2 表示高频彩票，
     *                            3 表示低频彩票，
     *                            4 标识境外高频彩票，
     *                            5 标识境外低频彩票
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function lotteryList($lotteryType, $apikey)
    {
        $url = self::OPEN_API. '/lottery/lotterylist';

        return self::send($url, ['lotterytype' => $lotteryType], ["apikey: $apikey"]);
    }

    /**
     * 提供彩票最新开奖、历史开奖结果查询
     *
     * @param string $lotteryCode 彩票编号，通过彩票种类查询接口可获得
     * @param string $apikey 百度apikey
     * @param int $recordcnt 记录条数，范围为1~20
     * @return array
     */
    public static function lotteryQuery($lotteryCode, $apikey, $recordcnt = 2)
    {
        $url = self::OPEN_API. 'lottery/lotteryquery';

        return self::send($url, [
            'lotterycode' => $lotteryCode,
            'recordcnt'   => $recordcnt,
        ], ["apikey: $apikey"]);
    }

    protected static function send($url, array $params = [], array $headers = [])
    {
        $res = CurlHelper::get($url, $params , $headers);

        return json_decode($res, true);
    }
}
