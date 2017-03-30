<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 16-7-28
 * Time: 10:35
 * BDWeather.php
 */

namespace inhere\library\utils;

use inhere\library\helpers\CurlHelper;

/**
 * Baidu 天气/空气质量查询
 */
class BDWeather
{
    const WEATHER_API = 'http://apis.baidu.com/apistore/weatherservice';
    const AQI_API = 'http://apis.baidu.com/apistore/aqiservice';

    /**
     * 查询可用城市列表
     * @param string $apikey 百度apikey
     * @param $cityName
     * @return array
     */
    public static function cityList($apikey, $cityName)
    {
        $url = self::WEATHER_API. '/citylist';

        return self::send($url, ['cityname' => $cityName], ["apikey: $apikey"]);
    }

    /**
     * 天气查询_根据城市拼音
     * @param string $cityPinyin 城市拼音 e.g. beijing
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function byCityPinyin($cityPinyin, $apikey)
    {
        $url = self::WEATHER_API. '/weather';

        return self::send($url, ['citypinyin' => $cityPinyin], ["apikey: $apikey"]);
    }

    /**
     * 天气查询_根据城市名称(中文)
     * @param string $cityName 城市名(中文)  e.g. 北京
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function byCityName($cityName, $apikey)
    {
        $url = self::WEATHER_API. '/cityname';

        return self::send($url, ['cityname' => $cityName], ["apikey: $apikey"]);
    }

    /**
     * 天气查询_带历史7天和未来4天
     *
     * @param string $cityName 城市名(中文) e.g. 北京
     * @param string $cityCode 天气预报城市代码(可通过cityInfo获取到) e.g. 101010100
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function recentWeathers($cityName, $cityCode, $apikey)
    {
        $url = self::WEATHER_API. '/recentweathers';

        return self::send($url, [
            'cityname'  => $cityName,
            'cityid'    => $cityCode,
        ], ["apikey: $apikey"]);
    }

    /**
     * 城市信息查询
     * JSON返回示例 :
     *   {
     *       errNum: 0,
     *       retMsg: "success",
     *       retData: {
     *           cityName: "北京",
     *           provinceName: "北京",
     *           cityCode: "101010100",  //天气预报城市代码
     *           zipCode: "100000",      //邮编
     *           telAreaCode: "010"     //电话区号
     *       }
     *   }
     *
     * @param string $cityName 城市名(中文)  e.g. 北京
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function cityInfo($cityName, $apikey)
    {
        $url = self::WEATHER_API. '/cityinfo';

        return self::send($url, ['cityname' => $cityName], ["apikey: $apikey"]);
    }

    /**
     * 空气质量指数可用城市列表
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function aqiCityList($apikey)
    {
        $url = self::AQI_API. '/citylist';

        return self::send($url, [], ["apikey: $apikey"]);
    }

    /**
     * 空气质量指数
     * @param string $cityName 城市名(中文)  e.g. 北京
     * @param string $apikey 百度apikey
     * @return array
     */
    public static function aqi($cityName, $apikey)
    {
        $url = self::AQI_API. '/aqi';

        return self::send($url, ['city' => $cityName], ["apikey: $apikey"]);
    }

    protected static function send($url, array $params = [], array $headers = [])
    {
        $res = CurlHelper::get($url, $params , $headers);

        return json_decode($res, true);
    }
}
