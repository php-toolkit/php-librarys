<?php
/**
 * function collection
 */

if ( !function_exists('local_env') ) {
    function local_env($name = null, $default = null)
    {
        return inhere\library\collections\Local::env($name, $default);
    }
}

if ( !function_exists('html_minify') ) {
    function html_minify($body) {
        $search = array('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '/\n/','/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s');
        $replace = array(' ', ' ','>','<','\\1');

        return preg_replace($search, $replace, $body);
    }
}

if ( !function_exists('value') ) {
    /**
     * @param $value
     * @return mixed
     *
     * value(new Class)->xxx
     */
    function value($value) {
        return $value;
    }
}

if ( !function_exists('tap') ) {
    function tap($value, callable $callback) {

        $callback($value);

        return $value;
    }
}

if ( !function_exists('cookie') ) {
    /**
     * cookie get or set
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function cookie($name, $default=null)
    {
        // set, when $name is array
        if ($name && is_array($name) ) {
            $d = [
                'value' => '', 'expire' => null, 'path' => null, 'domain' => null, 'secure' => false, 'httpOnly' => false
            ];

            foreach ($name as $n => $value) {
                if ( !$n || !is_string($n) ) {
                    continue;
                }

                if ( is_array($value) ) {
                    $d = array_merge($d, $value);
                } elseif (is_scalar($value)) {
                    $d['value'] = $value;
                } else {
                    continue;
                }

                $_COOKIE[$n] = $d['value'];
                setcookie($n, $d['value'], $d['expire'], $d['path'], $d['domain'], $d['secure'], $d['httpOnly']);
            }

            return $name;
        }

        // get
        if ($name && is_string($name)) {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
        }

        return $default;
    }
}

if ( !function_exists('session') ) {
    /**
     * session get or set
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function session($name, $default=null)
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException('session set or get failed. Session don\'t start.');
        }

        // set, when $name is array
        if ($name && is_array($name) ) {
            foreach ($name as $key => $value) {
                if (is_string($key)) {
                    $_SESSION[$key] = $value;
                }
            }

            return $name;
        }

        // get
        if ($name && is_string($name)) {
            return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
        }

        return $default;
    }
}

if ( !function_exists('imagecreatefrombmp') ) {

    function imagecreatefrombmp($p_sFile)
    {
        // Load the image into a string
        $file = fopen($p_sFile,"rb");
        $read = fread($file,10);
        while(!feof($file)&&($read<>"")) {
            $read .= fread($file, 1024);
        }

        $temp   = unpack("H*",$read);
        $hex    = $temp[1];
        $header = substr($hex,0,108);
        $width  = $height = 0;

        //    Process the header
        //    Structure: http://www.fastgraph.com/help/bmp_header_format.html
        if (substr($header,0,4)=="424d") {
            //    Cut it in parts of 2 bytes
            $header_parts    =    str_split($header,2);

            //    Get the width        4 bytes
            $width            =    hexdec($header_parts[19].$header_parts[18]);

            //    Get the height        4 bytes
            $height            =    hexdec($header_parts[23].$header_parts[22]);

            //    Unset the header params
            unset($header_parts);
        }

        //    Define starting X and Y
        $x                =    0;
        $y                =    1;

        //    Create newimage
        $image            =    imagecreatetruecolor($width,$height);

        //    Grab the body from the image
        $body            =    substr($hex,108);

        //    Calculate if padding at the end-line is needed
        //    Divided by two to keep overview.
        //    1 byte = 2 HEX-chars
        $body_size        =    (strlen($body)/2);
        $header_size    =    ($width*$height);

        //    Use end-line padding? Only when needed
        $usePadding        =    ($body_size>($header_size*3)+4);

        //    Using a for-loop with index-calculation instaid of str_split to avoid large memory consumption
        //    Calculate the next DWORD-position in the body
        for ($i=0;$i<$body_size;$i+=3)
        {
            //    Calculate line-ending and padding
            if ($x>=$width)
            {
                //    If padding needed, ignore image-padding
                //    Shift i to the ending of the current 32-bit-block
                if ($usePadding)
                    $i    +=    $width%4;

                //    Reset horizontal position
                $x    =    0;

                //    Raise the height-position (bottom-up)
                $y++;

                //    Reached the image-height? Break the for-loop
                if ($y>$height)
                    break;
            }

            //    Calculation of the RGB-pixel (defined as BGR in image-data)
            //    Define $i_pos as absolute position in the body
            $i_pos    =    $i*2;
            $r        =    hexdec($body[$i_pos+4].$body[$i_pos+5]);
            $g        =    hexdec($body[$i_pos+2].$body[$i_pos+3]);
            $b        =    hexdec($body[$i_pos].$body[$i_pos+1]);

            //    Calculate and draw the pixel
            $color    =    imagecolorallocate($image,$r,$g,$b);
            imagesetpixel($image,$x,$height-$y,$color);

            //    Raise the horizontal position
            $x++;
        }

        //    Unset the body / free the memory
        unset($body);

        //    Return image-object
        return $image;
    }
}

if ( !function_exists('imagebmp')) {
    function imagebmp($img, $path = '')
    {

    }
}

if ( !function_exists('make_object')) {
    function make_object($class)
    {
        static $__object_list_box = [];

        if ( !isset($__object_list_box[$class]) ) {
            $__object_list_box[$class] = new $class;
        }

        return $__object_list_box[$class];
    }
}
