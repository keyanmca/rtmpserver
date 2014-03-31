<?php

	
	class HooksConfig {
		public static $config;
	}
	HooksConfig::$config[HOOK_BEFORE_REQUEST_PROCESS][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'onBeforeProcess'
		)
	);
	HooksConfig::$config[HOOK_AFTER_REQUEST_PROCESS][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'onAfterProcess'
		)
	);
	HooksConfig::$config[HOOK_AFTER_REQUEST_PROCESS][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'onAfterProcess2'
		)
	);
	HooksConfig::$config[HOOK_BEFORE_ACTION_EXECUTE][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'onBeforeExecute'
		)
	);

	HooksConfig::$config[HOOK_AFTER_ACTION_EXECUTE][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(	
			'onAfterExecute'
		)
	);

	HooksConfig::$config[HOOK_PHP_ERROR][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'php_error_handler'
		)
	);

	HooksConfig::$config[HOOK_PHP_EXCEPTION][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'php_exception_handler'
		)
	);

	HooksConfig::$config[HOOK_ACTIONCHAIN_FAILED][] =  array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'actionchain_failed_handler'
		)
	);


	HooksConfig::$config[HOOK_BEFORE_SPECIFY_ACTION_EXECUTE_PREFIX.'ActionController'][] = array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'beforeActionControllerExecute'
		)
	);

	HooksConfig::$config[HOOK_ERRNO_PREFIX.'123'][] = array(
		FRAMEWORK_PATH.'config/GlobalConfig.class.php',
		array(
			'errno_123_handler'
		)
	);

	function php_error_handler($context, $event, $data) {	
		$errorInfo = $event->object;
		$errno = isset($errorInfo[0])?intval($errorInfo[0]):0;
		$errstr = isset($errorInfo[1])?$errorInfo[1]:'unknown error';
		$errfile = isset($errorInfo[2])?$errorInfo[2]:__FILE__;
		$errline = isset($errorInfo[3])?$errorInfo[3]:__LINE__;
		if (!($errinit = error_reporting() & $errno)) {// This error code is not included in error_reporting
			CLogger::warning ( "framework_php_error_reporting: errinit={$errinit}, errno={$errno}," . "error={$errstr},errorfile={$errfile}," . "errorline={$errline}", GlobalConfig::BINGO_LOG_ERRNO );
			return;
		}
		switch ($errno) {
			case E_USER_ERROR:
				CLogger::fatal ( "framework_php_error_handler errno={$errno}," . "error={$errstr},errorfile={$errfile}," . "errorline={$errline}", GlobalConfig::BINGO_LOG_ERRNO );
				break;
		
				case E_USER_WARNING:
				CLogger::warning ( "framework_php_error_handler errno={$errno}," . "error={$errstr},errorfile={$errfile}," . "errorline={$errline}", GlobalConfig::BINGO_LOG_ERRNO );
				break;
		
				case E_USER_NOTICE:
				CLogger::notice ( "framework_php_error_handler errno={$errno}," . "error={$errstr},errorfile={$errfile}," . "errorline={$errline}", GlobalConfig::BINGO_LOG_ERRNO );
				break;
		
				default:
				CLogger::fatal ( "framework_php_error_handler errno={$errno}," . "error={$errstr},errorfile={$errfile}," . "errorline={$errline}", GlobalConfig::BINGO_LOG_ERRNO );
				break;
		}
		
		/* Don't execute PHP internal error handler */
		return true;
	}

	function php_exception_handler($context, $event, $data){
		$exceptionInfo = $event->object;
        CLogger::fatal('framework_php_exception_handler '.var_export($exceptionInfo, true),
						GlobalConfig::BINGO_LOG_ERRNO);
	}

	function onBeforeProcess($context, $event, $data) {
	}

	function onAfterProcess($context, $event, $data) {
	}

    function onBeforeExecute($context, $event, $configData) {
    }

    function onAfterExecute($context, $event, $data) {
   	}
	function beforeActionControllerExecute(){
	}
	function onAfterProcess2(){

	}
	function actionchain_failed_handler($context, $event, $data) {
		$v = $context->getProperty(PROPERTY_ACTIONCHAIN_FAILED_INDEX);
		$chain = $event->source;	
		CLogger::trace('in the actionchain_failed_handler failed',
			 0,
			 array(
				'ActionChain.failedIndex' => $v,
			)
		);
	}
?>
