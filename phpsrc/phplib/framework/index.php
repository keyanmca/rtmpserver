<?php
/***************************************************************************
 * 
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file index.php
 * @author gaowei(gaowei@baidu.com)
 * @date 2013/08/29 14:50:08
 * @brief 
 *  
 **/

//例子， 使用此框架的人可以拷贝后使用
//模块路径下需要有ui，module，cache，template,config目录
//config路径下准备ActionChainConfig.class.php文件，用于路由

define('ROOT_PATH' , dirname(__FILE__).'/');
$GLOBALS['LOG'] =  array(
	'intLevel'                      => 0x1F,
    'strLogFile'            => ROOT_PATH.'/log/test.log',
    'intMaxFileSize'    => 0,
    'arrSelfLogFiles'       => array()
);

define('UI_PATH',            ROOT_PATH.'ui/');
define('MODULE_PATH',        ROOT_PATH.'module/');
define('CACHE_PATH',         ROOT_PATH.'cache/');
define('TEMPLATE_PATH',      ROOT_PATH.'template/');
define('CONFIG_PATH',        ROOT_PATH.'config/');
define('PHPLIB_PATH', '/home/work/apps/phplib/');
require_once(PHPLIB_PATH.'framework/config/GlobalConfig.class.php'); 
require_once(PHPLIB_PATH.'framework/common/env_init.php');
if (true === GlobalConfig::$hookBeforeRequestProcessSwitch) {
	$context->fireEvent(new Event(HOOK_BEFORE_REQUEST_PROCESS
 		, 'index.php', null));
}
$context->callAction($context->rootAction->actionID);
if (true === GlobalConfig::$hookAfterRequestProcessSwitch) {
	$context->fireEvent(new Event(HOOK_AFTER_REQUEST_PROCESS
 		, 'index.php', null));
}

?>




