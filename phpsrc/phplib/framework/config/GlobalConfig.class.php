<?php

	/**
	 * GlobalConfig.class.php's detail config 
	 *
	 * config GlobalConfig::$rootAction and GlobalConfig::$initActions
	 * @package bingo/config
	 */

	class GlobalConfig {

		public static $isDebug;
		public static $hookBeforeRequestProcessSwitch;
		public static $hookAfterRequestProcessSwitch;
		public static $hookBeforeActionExecuteSwitch;
		public static $hookAfterActionExecuteSwitch;
		public static $hookBeforeSpecifyActionExecuteSwitch;
		public static $hookAfterSpecifyActionExecuteSwitch;
		public static $hookPhpErrorSwitch;
		public static $hookPhpExceptionSwitch;
		public static $phpErrorReportingSwitch;
        public static $rootAction;
        public static $LOG_CONFIG;

        const BINGO_LOG_ERRNO = 1000;
	}

	/**
	 * tell the framework whether open the debug feather or not
	 */
	GlobalConfig::$isDebug = true;
	GlobalConfig::$hookBeforeRequestProcessSwitch = true;
	GlobalConfig::$hookAfterRequestProcessSwitch  = true;
	GlobalConfig::$hookBeforeActionExecuteSwitch  = true;
	GlobalConfig::$hookAfterActionExecuteSwitch   = true;
	GlobalConfig::$hookBeforeSpecifyActionExecuteSwitch = true;
	GlobalConfig::$hookAfterSpecifyActionExecuteSwitch = true;
	GlobalConfig::$hookPhpErrorSwitch             = true;
	GlobalConfig::$hookPhpExceptionSwitch         = true;
	GlobalConfig::$phpErrorReportingSwitch        = true;


	/**
     * initial root action
	 *
	 * 1st element of the array is the actionID : String
	 * 2nd element of the array is the class name of the Action : String
	 * 3rd element of the array is the parameter of method 
	 *     	                       Action::initial($object) : Object
	 */

	GlobalConfig::$rootAction  = array(
        'ActionChain',
        FRAMEWORK_ACTIONS_PATH.'/ActionChain.class.php',
        ActionChainConfig::$config
    );

?>
