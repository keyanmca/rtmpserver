<?php
/**
* brief of BingoUtils.php:
* 
* @author 
* @date 2009/07/20 15:40:05
* @version $Revision: 1.6 $ 
* @todo 
*/

class BingoUtils
{
    const INC_BUF_NAME = 'autoload_cache.php';
    const LIB_DEP_FILE_NAME = 'dep_libs.conf';

    private static $LOADED_LIBS = array();
    public static $arrIncludeBuffer = array();

    public static function genLogid($requestTime = null)
    {
        if($requestTime === null)
        {
            $requestTime = gettimeofday();
        }

        return (intval($requestTime['sec'] * 100000 + $requestTime['usec'] / 10) & 0xFFFFFFFF);
    }

  

    public static function refreshAutoloadCache()
    {
        $strCacheFileName = CACHE_PATH.self::INC_BUF_NAME;
        $strTmpCacheFileName = CACHE_PATH.self::INC_BUF_NAME.mt_rand(0, 100000);

        // 极限模式
        if(AutoLoadConfig::AUTOLOAD_CACHE_EXTREME_MOD)
        {
            self::$arrIncludeBuffer = ClassesFinder::findClasses(AutoLoadConfig::$AUTOLOAD_PATH_CONFIG, true);
        }
        else
        {
            $arrIncludePath = explode(':', ini_get('include_path'));
            self::$arrIncludeBuffer = ClassesFinder::findClasses($arrIncludePath, false);
        }

        $strIncludeBuffer = "<?php\nBingoUtils::\$arrIncludeBuffer = ".var_export(self::$arrIncludeBuffer, true).";\n?>\n";

        if (file_put_contents($strTmpCacheFileName, $strIncludeBuffer, LOCK_EX) > 0) {
            rename($strTmpCacheFileName, $strCacheFileName);
        }
    }

    public static function quickLoadClass($strClassName)
    {
        $strCacheFileName = CACHE_PATH.self::INC_BUF_NAME;

        // 文件不存在、加载失败、或者类文件不存在，则刷新cache
        if (!file_exists($strCacheFileName)
            || !(include_once $strCacheFileName)
            || !array_key_exists($strClassName, self::$arrIncludeBuffer)
            || !file_exists(self::$arrIncludeBuffer[$strClassName])) {
            self::refreshAutoloadCache();
        }

//        print_r(self::$arrIncludeBuffer);

        if (!array_key_exists($strClassName, self::$arrIncludeBuffer)) {
            return false;
        }
        return (include_once self::$arrIncludeBuffer[$strClassName]) !== false;
    }
}

class ClassesFinder
{
    /**
     * @brief   对给定的一组目录，找出其中所有的类到文件的关系映射
     *
     * @params  $dirs - 目录数组
     *          $recu - 是否递归子目录
     * @return  array
     * @retval  array(
     *              'class1' => '/xxx/xxx/xxx/xx.php'
     *              'class2' => '/xxx/xxx/xxx/xx.php'
     *              ...
     *              )
     **/
    public static function findClasses($dirs, $recu)
    {
        $arrClasses = array();

        foreach($dirs as $dir)
        {
            if(!$recu)
            {
                $classes = self::_getAllClasses($dir);
            }
            else
            {
                $files = self::_getAllFilesRecu($dir);
                $classes = self::_buildClassPath($files);
            }
            $arrClasses = array_merge($arrClasses, $classes);
        }
        return $arrClasses;
    }

    /**
     * @brief   对给定的一组文件，找到其中存在的class并得到class->file的映射 
     *
     * @return  private static function 
     * @retval   
     * @date 2009/07/09 11:49:34
     **/
    private static function _buildClassPath($files)
    {
        $classPath = array ();
        foreach ($files as $per) {
            $tail = substr ($per,strlen($per)-4,4);
            if ($tail === ".php" || $tail === ".inc") {
                preg_match_all('~^\s*(?:abstract\s+|final\s+)?(?:class|interface)\s+(\w+)~mi',
                    file_get_contents($per), $res);
                if (count($res[1]) == 0) {
                    continue;
                }
                foreach ($res[1] as $value) {
                    $tmp = trim($value);
                    if ($tmp !== "") {
                        if (isset($classPath[$tmp])) {
                            $old_time = filemtime ($classPath[$tmp]);
                            $new_time = filemtime ($per);
                            if ($old_time > $new_time) {
                                continue;
                            }
                        }
                        $classPath [$tmp] = $per;
                    }
                }
            }
        }
        return $classPath;
    }

    /**
     * @brief 获取文件夹下的所有类
     *
     * @return  private static function 
     * @retval   
     * @date 2009/07/09 11:52:32
     **/
    private static function _getAllClasses($dir)
    {
        $arrClasses = array();
        $arr = scandir($dir);
        foreach($arr as $v)
        {
            $file = "$dir/$v";
            if(!is_file($file) || $v{0} == '.')
            {
                continue;
            }
            $tmp = explode('.class.php', $v);
            if(count($tmp) == 2 && strlen($tmp[1]) == 0)
            {
                $arrClasses[$tmp[0]] = $file;
            }
        }
        return $arrClasses;
    }

    /**
     * @brief 递归获取文件夹下的所有文件
     * @desc  考虑到php本身对递过层次的限制，为避免core dump，此处采用广搜遍历
     *
     * @return  private static function 
     * @retval   
     * @date 2009/07/09 11:52:32
     **/
    private static function _getAllFilesRecu($dir)
    {
        $first = 0;
        $last = 1;
        $queue = array (
            0 => $dir
        );
        $files = array ();
        while ($first < $last) 
        {
            $value = $queue[$first++];
            if (!is_dir($value)) {
                if (is_file($value)) {
                    array_push($files,$value);   
                }
            }
            else
            {
                $arr = scandir ($value);
                if ($arr === false) {
                    continue;
                }
                foreach ($arr as $per) {
                    if($per{0} === ".") {
                        continue;
                    }
                    $tmp = $value."/".$per;
                    $queue[$last++] = $tmp;
                }
            }
        }
        return $files;
    }
}

?>
