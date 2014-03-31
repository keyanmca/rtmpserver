<?php
/***************************************************************************
 * 
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file ActionControllerConfig.class.php
 * @author gaowei(gaowei@baidu.com)
 * @date 2013/12/25 14:30:25
 * @brief 
 *  
 **/

class ActionControllerConfig {
	public static $config;
}
ActionControllerConfig::$config = array(
	'/^\/rest\/2.0\/rtmp\/video\/publish' => array (
		'rtmpvideopublish',
		UI_PATH . '/video/ActionVideoPublish.class.php',
		null,
		null 
	),
	
);



