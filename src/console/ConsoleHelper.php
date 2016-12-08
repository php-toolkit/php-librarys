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
}
