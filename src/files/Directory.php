<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 15-1-14
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */

namespace inhere\tools\files;

use DirectoryIterator;
use inhere\tools\exceptions\NotFoundException;

class Directory extends AbstractFileSystem
{
    /**
     * 查看一个目录中的所有文件和子目录
     * @param $dirName
     * @param bool $return
     * @throws NotFoundException
     * @return array|void
     */
    static public function ls($dirName, $return=true)
    {
        $list = [];

        try{
          /*** class create new DirectoryIterator Object ***/
            foreach ( new DirectoryIterator($dirName) as $item ) {
                $list[] = $item;
            }
        /*** if an exception is thrown, catch it here ***/
        } catch(\Exception $e){
            throw new NotFoundException($dirName.' 没有任何内容');
        }

        return $list;
    }

    //只获得目录结构
    static public function getList( $dirName, $pid=0, $son=0, $list = [] )
    {
        $dirName = self::pathFormat($dirName);

        if (!is_dir($dirName)) {
            throw new NotFoundException('目录'.$dirName.' 不存在！');
        }

        static $id = 0;

        foreach( glob($dirName.'*') as $v) {
            if ( is_dir($v) ){
                $id++;

                $list[$id]['id']   = $id;
                $list[$id]['pid']  = $pid;
                $list[$id]['name'] = basename($v);
                $list[$id]['path'] = realpath($v);

                //是否遍历子目录
                if ( $son ){
                    $list = self::getList($v,$id,$son,$list);
                }
            }
        }

        return $list;
    }

    /**
     * 获得目录下的文件，可选择类型、是否遍历子文件夹
     * @param $dir string 目标目录
     * @param $ext array('css','html','php') css|html|php
     * @param $recursive int|bool 是否包含子目录
     * @return array
     */
    static public function files($dir, $ext=null, $recursive=false)
    {
        $list = [];

        if ( is_array($ext) ){
            $ext = implode('|',$ext);
        }

        //glob()寻找与模式匹配的文件路径
        foreach( glob($dir.'*') as $file ) {
            // $id++;

            //如果没有传入$ext 则全部遍历，传入了则按传入的类型来查找
            if ( !$ext || preg_match("/\.($ext)$/i",$file)) {
                //basename — 返回路径中的 文件名部分
                $list[]          = basename($file);
            }

            //是否遍历子目录
            if ( is_dir($file) && $recursive){
                $list = array_merge($list, self::files($file,$ext,$recursive));
            }
        }

        return $list;
    }

    /**
     * 获得目录下的文件，可选择类型、是否遍历子文件夹
     * @param $dirName string 目标目录
     * @param $ext array('css','html','php') css|html|php
     * @param $recursive int|bool 是否包含子目录
     * @return array
     */
    static public function getFiles($dirName, $ext=null, $recursive=0, &$list=[])
    {

        $dirName= self::pathFormat($dirName);

        if (!is_dir($dirName)) {
            throw new NotFoundException('目录'.$dirName.' 不存在！');
        }

        if ( is_array($ext) ){
            $ext = implode('|',$ext);
        }

        static $id = 0;

        //glob()寻找与模式匹配的文件路径
        foreach( glob($dirName.'*') as $file ) {
            $id++;

            //如果没有传入$ext 则全部遍历，传入了则按传入的类型来查找
            if ( !$ext || preg_match("/\.($ext)$/i",$file)) {
                //basename — 返回路径中的 文件名部分
                $list[$id][] = File::getInfo($file); //文件的上次访问时间
            }

            //是否遍历子目录
            if ( $recursive && is_dir($file) ){
                $list = self::getFiles($file,$ext,$recursive,$list);
            }
        }

        return $list;
    }

    /**
     * 支持层级目录的创建
     * @param $path
     * @param int|string $mode
     * @return bool
     */
    static public function create($path, $mode=0664)
    {
        return is_dir($path) || mkdir($path, $mode, true);
    }

    /**
     * ********************** 创建多级目录 **********************
     * @param $path - 目录字符串
     * @param int $mode =0664 - 权限，默认 0664
     * @return bool
     */
    static public function make($path, $mode=0664)
    {
        return (is_dir($path) || mkdir($path, $mode, true)) && is_writable($path);
    }

    //复制目录内容
    static public function copy($oldDir, $newDir)
    {
        $oldDir = self::pathFormat($oldDir);
        $newDir = self::pathFormat($newDir);

        if ( !is_dir($oldDir) ) {
            throw new NotFoundException('复制失败：'.$oldDir.' 不存在！');
        }

        $newDir = self::create($newDir);

        foreach( glob($oldDir.'*') as $v) {
            $newFile = $newDir.basename($v);//文件

            //文件存在，跳过复制它
            if ( file_exists($newFile) ) {
                continue;
            }

            if ( is_dir($v) ) {
                self::copy($v,$newFile);
            } else {
                @copy($v,$newFile);//是文件就复制过来
                @chmod($newFile,0664);// 权限 0777
            }
        }

        return true;
    }

    /**
     * 删除目录及里面的文件
     * @param $dirName
     * @param  boolean $type [description]
     * @return bool [type]           [description]
     */
    static public function delete($dirName,$type=true)
    {
        $dirPath = self::pathFormat($dirName);

        if ( !is_dir($dirPath)) {
            return false;
        }

        foreach(glob($dirPath.'*') as $v) {
            is_dir($v) ? self::delete($v) : unlink($v);
        }

        !$type or rmdir($dirPath);//默认最后删掉自己

        return true;
    }

    // 比较文件路径
    static public function comparePath($newPath,$oldPath)
    {
        $oldDirName  = basename(rtrim($oldPath,'/'));
        $newPath_arr = explode('/', rtrim($newPath,'/'));
        $oldPath_arr = explode('/', rtrim($oldPath,'/'));

        $reOne  = array_diff($newPath_arr, $oldPath_arr);
        $numOne = count((array)$reOne);//

        /**
         * 跟框架在同一个父目录[phpTest]下
         * projectPath 'F:/www/phpTest/xxx/yyy/[zzz]'--应用目录 zzz,
         * yzonePath 'F:/www/phpTest/[yzonefk]'---框架目录 [yzonefk]
         * 从应用'F:/www/phpTest/xxx/yyy/[zzz]/'目录回滚到共同的父目录[这里是从zzz/web回滚到phpTest]
         * 入口文件 在 zzz/web/index.php
         */
        $dirStr = '__DIR__';

        for ($i=0;$i<=$numOne;$i++) {
            $dirStr = 'dirname( '.$dirStr.' )';
        }

        $dirStr .= '.\'';

        /**
         * 跟框架在不同父目录下,在回滚到共同的父目录后，再加上到框架的路径
         * newPath 'F:/www/otherDir/ddd/eee/xxx/yyy/[zzz]'--应用目录 zzz
         * oldPath 'F:/www/phpTest/[yzonefk]'---框架目录[yzonefk]
         */
        if (dirname($newPath) !== dirname($oldPath)) {
            $reTwo = array_diff($oldPath_arr, $newPath_arr);
            $reTwo = array_shift($reTwo);
            // $numTwo = count($reTwo);// 从框架目录向上回滚，找到相同的父节点，得到相隔几层
            $dirStr .= implode('/', (array)$reTwo);
        }

        $dirStr = $dirStr.'/'.$oldDirName.'/Gee.php\'';

        return $dirStr;
    }

    // TODO ....
    public function yaSuo($dirFile) {
        # code...
    }
    public function jieYa($file) {
        # code...
    }
}
