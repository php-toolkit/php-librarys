<?php


namespace inhere\librarys\console;

/**
 *
 */
class ConsoleHelper
{

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * get key Max Width
     *
     * @param  array  $data
     * [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     * ]
     * @return int
     */
    public static function keyMaxWidth(array $data, $expactInt = true)
    {
        $keyMaxWidth = 0;

        foreach ($data as $key => $value) {
            // key is not a integer
            if ( !$expactInt || !is_numeric($key) ) {
                $width = mb_strlen($key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    /**
     * spliceArray
     * @param  array  $data
     * e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     * ]
     * @param  int    $keyMaxWidth
     * @param  string $sepChar  e.g ' | ' => OUT: key | value
     * @param  string $leftChar e.g ' * '
     * @return string
     */
    public static function spliceKeyValue($data, $keyMaxWidth, $sepChar = ' ', $leftChar='')
    {
        $text = '';
        foreach ($panelData as $key => $value) {
            $text .= $leftChar;

            if ($keyMaxWidth) {
                $text .= str_pad($key, $keyMaxWidth, ' ') . $sepChar;
            }

            $text .= "$value\n";
        }

        return $text;
    }
}
