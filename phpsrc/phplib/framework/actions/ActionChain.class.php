<?php

	/**
	 * a component of Action 
	 *
	 * ActionChain supports actions to be executed in sequence order,
	 * someone could use this action in logic flow.
	 *
	 * @package bingo/framework
	 * @author  liubin01@baidu.com
   	 */

	class ActionChain extends Action {

		protected $actions;
		protected $pos;

		public function initial($initObject) {
			if (!is_array($initObject) && null !== $initObject) {
				CLogger::warning('ActionChain initial failed', 
								  GlobalConfig::BINGO_LOG_ERRNO, 
								  array('initObject' => var_export($initObject, true))
				);
				return false;
			}
			$this->actions = $initObject;
			$this->pos = -1;
			return true;	
		}
		public function	addAction($actionID, $actionClassPath = null, 
											 $initObject = null) {
			return $this->addActionByIndex(count($this->actions),
										   $actionID,
										   $actionClassPath,
										   $initObject);
		}
		public function addActionByIndex($index, $actionID, 
							$actionClassPath = null, $initObject = null) {
			if (!is_string($actionID) || 0 >= strlen($actionID) || !is_int($index)) {
				CLogger::warning('actionID or index is invalid',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'actionID' => var_export($actionID, true),
									'index'	   => var_export($index, true)
								  ) 
				);
				return false;
			} elseif (!is_array($this->actions)) {
				$this->actions = array();
			}
			if ($index < 0) {
				$index = 0;
			} elseif ($index >= count($this->actions)) {
				$index = count($this->actions);
			}

			if ($index <= $this->pos) {
				CLogger::warning('can not add action before executed ' 	
								.'ActionChain position',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'actionID' => var_export($actionID, true),
									'index'	   => var_export($index, true),
									'ActionChain.pos' => var_export($this->pos, true)
								  )
				);
				return false;
			}

			if ($index == count($this->actions)) {
				$this->actions[] = array($actionID, $actionClassPath, $initObject);
			} else {
				$beforeArr = array_slice($this->actions, 0, $index);
				$afterArr  = array_slice($this->actions, $index);
				$this->actions = array_merge($beforeArr, 
						array(array($actionID, $actionClassPath, $initObject)),
											 $afterArr);
			}
			return true; 
		}
		public function removeActionByID($actionID) {
			if (!is_string($actionID) || 0 >= strlen($actionID)
				|| !is_array($this->actions)) {
				CLogger::warning('actionID is invalid or not in the '
								.'ActionChain.actions',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
								  	'actionID' => var_export($actionID, true),
						 'ActionChain.actions' => var_export($this->actions, true)
								  )
				);
				return false;
			}
			foreach($this->actions as $index => &$actionInfo) {
				if (is_array($actionInfo) && count($actionInfo) > 0) {
					if ($actionInfo[0] == $actionID) {
						$ret = $this->removeActionByIndex($index);
						if (true !== $ret) {
							CLogger::warning('removeActionByID(actionID) failed',
											  GlobalConfig::BINGO_LOG_ERRNO,
											  array(
							    'actionID' => var_export($actionID, true),
					 'ActionChain.actions' => var_export($this->actions, true)	
											  )
							);
						}
						return $ret;
					}
				} else {
					CLogger::warning('ActionChain.actions config invalid',
									  GlobalConfig::BINGO_LOG_ERRNO,
									  array( 
						'ActionChain.actions.index' => var_export($index, true),
					'ActionChain.actions.actionInfo' => var_export($actionInfo, true)
								      )
					);
				}
			}
			CLogger::notice('ActionChain.actions does not have this action',
							  0,
							  array(
								'actionID'	=> var_export($actionID, true),
					  'ActionChain.actions' => var_export($this->actions, true)
							  )
			);
			return false;
		}
		public function removeActionByIndex($index) {
			if (!is_array($this->actions) 
				|| !is_int($index) 
				|| $index < 0 
				|| $index >= count($this->actions)) {
				CLogger::warning('ActionChain.actions does not have this index',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'index' => var_export($index, true),
					  'ActionChain.actions' => var_export($this->actions, true)
								  )
				);
				return false;
			}
			if ($index <= $this->pos) {
				CLogger::warning('can not remove action before executed ' 	
								.'ActionChain position',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
							'index' 	=> var_export($index, true),
						'ActionChain.pos' 	=> var_export($this->pos, true),
						'ActionChain.actions' => var_export($this->actions, true)
								  )
				);
				return false;
			}
			$beforeArr = array_slice($this->actions, 0, $index);
			$afterArr  = array_slice($this->actions, $index + 1);
			$this->actions = array_merge($beforeArr, $afterArr);
			return true;
		}
		public function getActionsCount() {
			if (!is_array($this->actions)) {
				return  0;
			}
			return count($this->actions);
		}
		public function getActions() {
			return $this->actions;
		}
		public function setActions($actions) {
			if (null === $actions) {
				$this->actions = null;
				return true;
			} elseif (!is_array($actions)) {
				CLogger::warning('setActions(actions) actions is invalid',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array('actions' => var_export($actions, true))	
				);
				return false;
			}
			$this->actions = $actions;
			$this->pos = -1;
			return true;
		}	
		public function execute($context, $actionParam = null) {
			if (!is_array($this->actions) || 0 >= count($this->actions)) {
				CLogger::warning('ActionChain.actions is invalid or empty',
								  GlobalConfig::BINGO_LOG_ERRNO
					
				);
				return false;
			}
		
			$this->pos ++;
			for ( ; $this->pos < count($this->actions); $this->pos ++) {
				$i = $this->pos;
				$actionInfo = $this->actions[$i];
				if (!is_array($actionInfo) || 0 >= ($cnt = count($actionInfo))) {
					CLogger::warning('ActionChain executing action is not valid',
									  GlobalConfig::BINGO_LOG_ERRNO,		
									  array(
							'ActionChain.index'  => var_export($i, true),
					'ActionChain.actions'=> var_export($this->actions, true)
									  )
					);
					$this->pos = -1;
					$context->modifyProperty(PROPERTY_ACTIONCHAIN_FAILED_INDEX, $i);
					$context->fireEvent(new Event(HOOK_ACTIONCHAIN_FAILED,
												  $this, $i));
					return false;
				}
				$actionID = $actionInfo[0];

				if (1 == $cnt) {
					$actionClassPath = null;
					$initObject = null;
				} elseif (2 == $cnt) {
					$actionClassPath = $actionInfo[1];
					$initObject = null;
				} else {
					$actionClassPath = $actionInfo[1];	
					$initObject = $actionInfo[2];
				}
				$action = $context->getAction($actionID, 
											  $actionClassPath,
											  $initObject);
				if(!($action instanceof Action)) {
					CLogger::warning('ActionChain->execute() getAction failed',	
									  GlobalConfig::BINGO_LOG_ERRNO,
									  array(
							'actionID' 		    => var_export($actionID, true),
							'ActionChain.index' => var_export($i, true),
						'ActionChain.actions' => var_export($this->actions, true)
									  )
					);
					$this->pos = -1;
					return false;
				}
				$ret = $context->callAction($action->actionID, $this);
				if (false === $ret) {
					CLogger::warning('some action execute failed in the ActionChain ',
									  GlobalConfig::BINGO_LOG_ERRNO,
									  array(
										'actionID'	=> var_export($actionID, true),
									  	'ActionChain.index' => var_export($i, true)
									  )
					);
					$this->pos = -1;
					// tell developer which actionID executed failed
					$context->modifyProperty(PROPERTY_ACTIONCHAIN_FAILED_INDEX, $i);
					$context->lastFailedAction = $action;
					$context->fireEvent(new Event(HOOK_ACTIONCHAIN_FAILED,
												  $this, $i));
					return false;
				}
			}
			$this->pos = -1;
			return true;
		}
	}
?>
