<?php
/***************************************************************************
 *
 * Copyright (c)2012 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * 
 * @author  gaowei(gaowei@baidu.com)
 * @version $Revision: 1.0.0
 */

require_once (UI_PATH . '/common/AbstractAction.class.php');
class ActionVideoPublish extends AbstractAction {
	/**
	 * 
	 * 初始化返回值
	 * @param object $initObject
	 */
	public function initial($initObject) {
		$this->result = array ("errno" => 1,);
		$this->logCode = 0;
		return true;
	}
	
	/**
	 * 
	 * 逻辑执行函数
	 * @param object $context
	 * @param object $actionParam
	 */
	public function execute($context, $actionParam = null) {
		$this->serverInit ();
		$this->serverQuit (self::ALLOW);
	}
	
	
}

?>
	
