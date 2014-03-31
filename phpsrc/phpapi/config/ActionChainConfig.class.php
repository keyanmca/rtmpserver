<?php
/***************************************************************************
 * 
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file ActionChainConfig.class.php
 * @author gaowei(gaowei@baidu.com)
 * @date 2013/12/25 11:56:51
 * @brief 
 *  
 **/
class ActionChainConfig {
	public static $config;
}
ActionChainConfig::$config = array(
	array(
		'action_controller',
		FRAMEWORK_ACTIONS_PATH.'/ActionController.class.php',
		ActionControllerConfig::$config
	),
);



