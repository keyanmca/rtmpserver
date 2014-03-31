<?php

/**
 * @package bingo/config
 * @author  zhangdongjin@baidu.com
 *
 * Bingo支持三种autoload模式：
 *
 * 1、普通模式
 *
 * 当自动加载类ClassA时，Bingo默认从include_path中加载ClassA.class.php
 *
 * 2、加速模式
 *
 * 从所有include_path中寻找名为“xxx.class.php”的文件，建立class=>file的映射，
 * autoload时直接从cache读取，不用再扫目录
  `* 加速模式的效果和普通模式一样，只是提升了性能
 *
 * 3、极限模式
 *
 * 从配置的路径中递归找出所有php(.php, .inc)文件，利用正则从文件中找出class和interface，建立class=>file的映射
 * autoload时直接从cache读取，不用再扫目录
 *
 * note：
 *
 * 1、当出现以下情况时，自动刷新cache：cache不存在、或者无法从cache中找到相关项、或者cache指出的文件加载失败
 * 2、使用第三种模式时，需要注意避免类名冲突
 *
 * Tips：
 *
 * 1、当你的目录层次较少，且include_path也较少时，采用普通模式即可；
 * 2、当你的include_path较多，但目录层次不深时，采用加速模式；
 * 3、当你的include_path较多，且目录层次较深时，采用极限模式；
 *
 * CAUTION:
 *
 * 极限模式无法区别被注释的类，因此会错误的将被注释的类加入cache
 * 为了避免该问题，可以这样注释：
 *
 * /*class SomeClass{
 * ...
 *
 * */
class AutoLoadConfig {
    // 是否使用class cache
    const USE_AUTOLOAD_CACHE = true;
    // 是否打开极限模式，USE_AUTOLOAD_CACHE为true时生效
    const AUTOLOAD_CACHE_EXTREME_MOD = true;
    // 仅用于极限模式，递归为这些目录建立cache
    public static $AUTOLOAD_PATH_CONFIG;

}

// 仅用于极限模式，递归为这些目录建立cache
AutoLoadConfig::$AUTOLOAD_PATH_CONFIG = array(
    CONFIG_PATH, // 必须保留
    FRAMEWORK_PATH, // 必须保留
    MODULE_PATH,
    PHPLIB_PATH,
);
?>
