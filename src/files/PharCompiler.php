<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 17:57
 */

namespace inhere\library\files;

/**
 * Class PharCompiler
 * @package inhere\library\files
 * @from Psy\Compiler (package psy/psysh)
 */
class PharCompiler
{
    private $version;
    private $pharName;
    private $pharFile;

    /**
     * Compiles psysh into a single phar file.
     * @param string $pharFile The full path to the file to create
     * @param string $version
     */
    public function compile($pharFile = 'your.phar', $version = '0.0.1')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $this->pharFile = $pharFile;
        $this->pharName = basename($pharFile);
        $this->version = $version;

        $phar = new \Phar($pharFile, 0, 'your.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = FileFinder::make()
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('Autoloader.php')
            ->in(__DIR__ . '/..');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $finder = FileFinder::make()
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->in(__DIR__ . '/../../build-vendor');

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);
    }

    /**
     * Add a file to the psysh Phar.
     * @param \Phar $phar
     * @param \SplFileInfo $file
     * @param bool $strip (default: true)
     */
    private function addFile($phar, $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n" . $content . "\n";
        }

        $phar->addFromString($path, $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     * @param string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), true)) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private static function getStubLicense()
    {
        $license = file_get_contents(__DIR__ . '/../../LICENSE');
        $license = str_replace('The MIT License (MIT)', '', $license);
        $license = str_replace("\n", "\n * ", trim($license));

        return $license;
    }

    const STUB_AUTOLOAD = <<<'EOS'
    Phar::mapPhar('psysh.phar');
    require 'phar://psysh.phar/build-vendor/autoload.php';
EOS;

    /**
     * Get a Phar stub for psysh.
     * This is basically the psysh bin, with the autoload require statements swapped out.
     * @return string
     */
    private function getStub()
    {
        $content = file_get_contents(__DIR__ . '/../../bin/psysh');
        $content = preg_replace('{/\* <<<.*?>>> \*/}sm', self::STUB_AUTOLOAD, $content);
        $content = preg_replace('/\\(c\\) .*?with this source code./sm', self::getStubLicense(), $content);

        $content .= '__HALT_COMPILER();';

        return $content;
    }
}
