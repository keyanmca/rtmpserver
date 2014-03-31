<?php

	/**
	 * initial framework's environment  
	 *
	 * initial default include_path, autoload, context and so on,
	 * this file is required by framework/common/common.conf.php
	 * which is auto prepended before every request.
	 *
	 * @package bingo/framework/common
	 * @author  liubin01@baidu.com 
	 */


	define('FRAMEWORK_PATH',         PHPLIB_PATH.'framework/');
    define('FRAMEWORK_ACTIONS_PATH', PHPLIB_PATH.'framework/actions/');
	// framework reserved hooks
	define('HOOK_BEFORE_REQUEST_PROCESS', '__beforeRequestProcess__');
	define('HOOK_AFTER_REQUEST_PROCESS',  '__afterRequestProcess__');
	define('HOOK_BEFORE_ACTION_EXECUTE',  '__beforeActionExecute__');
	define('HOOK_AFTER_ACTION_EXECUTE',   '__afterActionExecute__');
	define('HOOK_PHP_ERROR',              '__phpError__');
	define('HOOK_PHP_EXCEPTION',          '__phpException__');
	define('HOOK_BEFORE_SPECIFY_ACTION_EXECUTE_PREFIX', 
								'__hookBeforeSpecifyActionExecutePrefix__');
	define('HOOK_AFTER_SPECIFY_ACTION_EXECUTE_PREFIX',
								'__hookAfterSpecifyActionExecutePrefix__');
	define('HOOK_ERRNO_PREFIX', '__hookErrnoPrefix__');
	define('HOOK_ACTIONCHAIN_FAILED', '__hookActionChainFailed__');

	/** 
	 * the prefix of the special key which is set to 
	 * the context's dictionary by framework
	 */
	define('FRAMEWORK_RESERVED_PREFIX', '__framework__');
	define('PROPERTY_ACTIONCHAIN_FAILED_INDEX',
                     FRAMEWORK_RESERVED_PREFIX.'__ActionChainFailedIndexKey__');

    date_default_timezone_set('Asia/Chongqing');

    $requestTime = gettimeofday();
    define('REQUEST_TIME_MS', intval($requestTime['sec']*1000 + $requestTime['usec']/1000));
	
    //ini_set('include_path', CONFIG_PATH.':'.ini_get('include_path'));

	spl_autoload_register('__autoload'); 

    require_once FRAMEWORK_PATH."config/AutoLoadConfig.class.php";
    require_once FRAMEWORK_PATH."common/BingoUtils.class.php";
	require_once FRAMEWORK_PATH.'config/GlobalConfig.class.php';
	require_once PHPLIB_PATH.'log/CLogger.class.php';
	require_once FRAMEWORK_PATH.'context/Context.class.php';
    define('REQUEST_ID', BingoUtils::genLogid($requestTime));
    CLogger::setLogId(REQUEST_ID);

	/**
	 * define framework's autoload function
	 * @param String
     */
    function __autoload($className)
    {
        if(AutoLoadConfig::USE_AUTOLOAD_CACHE) {
            return BingoUtils::quickLoadClass($className);
        }

        include_once $className.'.class.php';
	}

	/**
	 * context of the request, during the request, there is only
	 * one instance of the class Context, Singleton Designed.
	 */
	global $context;

	$context = Context::getInstance();
	$ret = $context->initial();
	if (true !== $ret) {
        CLogger::fatal('context->initial failed', GlobalConfig::BINGO_LOG_ERRNO);
		exit;
	}

	if (false === GlobalConfig::$phpErrorReportingSwitch) {
        //	turn off php error reporting, error is handled by bingo framework.
		error_reporting(0);
	}

	if (true === GlobalConfig::$hookPhpErrorSwitch) {
		set_error_handler('error_handler');
	}

	if (true === GlobalConfig::$hookPhpExceptionSwitch) {
		set_exception_handler('exception_handler');
    }

	function error_handler() {
		$args = func_get_args();
		Context::getInstance()->fireEvent(new Event(HOOK_PHP_ERROR, 
			  'framework/common/env_init.php',
			  $args)
		);			
	}

	function exception_handler($ex) {
        CLogger::fatal('exception handler :'. $ex->__toString());
    }

	$context->setProperty('page_status',0);
?>
