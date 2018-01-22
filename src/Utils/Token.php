<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/26
 * Time: 下午11:55
 */

namespace Inhere\Library\Utils;

/**
 * Usage:
 * $user = $db->query(['name' => $_POST['name'] ]);
 * 1.
 *  gen:
 *      $password = Token::gen('123456');
 *  verify:
 *      Token::verify($user['password'], '123456');
 * 2.
 *  gen:
 *      $password = Token::hash('123456');
 *  verify:
 *      Token::verifyHash($user['password'], '123456');
 */
class Token
{
    /**
     * 指明应该使用的算法
     * $2a BLOWFISH算法。
     * $5  SHA-256
     * $6  SHA-512
     * @var string
     */
    private static $algo = '$2y';

    /**
     * cost parameter 就是成本参数
     * $10 这是以2为底的对数，指示计算循环迭代的次数（10 => 2^10 = 1024），取值可以从04到31。
     * @var string
     */
    private static $cost = '$10';

    /**
     * @return bool|string
     */
    public static function uniqueSalt()
    {
        return substr(sha1(mt_rand()), 0, 22);
    }

    /**
     * this will be used to generate a hash
     * @param $password
     * @return string
     */
    public static function gen($password)
    {
        return crypt($password, self::$algo . self::$cost . '$' . self::uniqueSalt());
    }

    /**
     * this will be used to compare a password against a hash
     * @param string $hash
     * @param string $password the user input
     * @return bool
     */
    public static function verify($hash, $password)
    {
        return hash_equals($hash, crypt($password, $hash));
    }

    /**
     * 2 生成
     * @todo from php.net
     * @param $password
     * @param int $cost
     * @return string
     * @throws \RuntimeException
     */
    public static function hash($password, $cost = 11)
    {
        if (false === ($bytes = openssl_random_pseudo_bytes(17, $cStrong)) || false === $cStrong) {
            throw new \RuntimeException('exec gen hash error!');
        }

        /* To generate the salt, first generate enough random bytes. Because
         * base64 returns one character for each 6 bits, the we should generate
         * at least 22*6/8=16.5 bytes, so we generate 17. Then we get the first
         * 22 base64 characters
         */
        $salt = substr(base64_encode($bytes), 0, 22);
        /* As blowfish takes a salt with the alphabet ./A-Za-z0-9 we have to
         * replace any '+' in the base64 string with '.'. We don't have to do
         * anything about the '=', as this only occurs when the b64 string is
         * padded, which is always after the first 22 characters.
         */
        $salt = str_replace('+', ".", $salt);
        /* Next, create a string that will be passed to crypt, containing all
         * of the settings, separated by dollar signs
         */
        $param = '$' . implode('$', [
                '2x', //select the most secure version of blowfish (>=PHP 5.3.7)
                str_pad($cost, 2, "0", STR_PAD_LEFT), //add the cost in two digits
                $salt //add the salt
            ]);

        //now do the actual hashing
        return crypt($password, $param);
    }

    /**
     * 2 验证
     * Check the password against a hash generated by the generate_hash
     * function.
     * @param $hash
     * @param $password
     * @return bool
     */
    public static function verifyHash($hash, $password): bool
    {
        /* Regenerating the with an available hash as the options parameter should
         * produce the same hash if the same password is passed.
         */
        return crypt($password, $hash) === $hash;
    }

    /**
     * @param string $pwd
     * @param string $algo
     * @param array $opts
     * @return bool|string
     */
    public static function pwdHash(string $pwd, string $algo, array $opts = [])
    {
        $opts = array_merge([
            'cost' => 9
        ], $opts);

        return password_hash($pwd, $algo, $opts);
    }

    /**
     * @param string $pwd
     * @param string $hash
     * @return bool|string
     */
    public static function pwdVerify(string $pwd, string $hash)
    {
        return password_verify($pwd, $hash);
    }

    /**
     * 生成guid
     * @return string
     */
    public static function GUid(): string
    {
        mt_srand((double)microtime() * 10000);

        $charId = strtolower(md5(uniqid(mt_rand(), true)));
        // $hyphen = chr(45);
        $uuid = substr($charId, 0, 8) .
            substr($charId, 8, 4) .
            substr($charId, 12, 4) .
            substr($charId, 16, 4) .
            substr($charId, 20, 12);

        return $uuid;
    }

    /**
     * *******生成唯一序列号*******
     * @param $var array || obj
     * @return string
     */
    public static function md5($var): string
    {
        //serialize()序列化，串行化
        return md5(md5(serialize($var)));
    }
}
