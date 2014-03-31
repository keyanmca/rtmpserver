<?php

	/**
	 * the context of the request
	 * the core class of the framework, contains dicts, hooks, actions and
     * all the main operations, expose operations to developers.
	 * @package bingo/framework
	 */

	abstract class Action {

		public $actionID;
		
		public static function getAction($actionID, $actionClassPath, 
												    $initObject = null) {
			if (!is_string($actionClassPath) || 0 >= strlen($actionClassPath)) {
                CLogger::warning('actionClassPath is invalid', GlobalConfig::BINGO_LOG_ERRNO,
								  array(
                                      'actionID' => var_export($actionID, true),
                                      'actionClassPath' => var_export($actionClassPath, true)
								  )	
				);
				return null;
			}
			$actionObject = null;
			$lastPos = strrpos($actionClassPath, '/');
			if (false === $lastPos || $lastPos >= (strlen($actionClassPath) - 1)) {
                CLogger::warning('actionClassPath is invalid',
								 GlobalConfig::BINGO_LOG_ERRNO,
								 array(
							'actionID' => var_export($actionID, true),
					 'actionClassPath' => var_export($actionClassPath, true)
								 )
				);
				return null;
			}
				
			$actionClassFileName = substr($actionClassPath, $lastPos + 1);
			if (false === ($suffixPos = strpos($actionClassFileName, '.class.php'))
				|| $suffixPos == 0) {
				CLogger::warning('actionClassFileName is invalid,'	
								.'actionClassFileName should end with .class.php',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
				'actionClassFileName' => var_export($actionClassFileName, true)
								  )
				);
				return null;	
			} 
			$actionClassName = substr($actionClassFileName, 0, $suffixPos);
			$ret = include_once($actionClassPath);
			if (!$ret) {
				CLogger::warning('include class failed', GlobalConfig::BINGO_LOG_ERRNO,
								  array(
							'actionID'        => var_export($actionID, true),
							'actionClassPath' => var_export($actionClassPath, true)
								  )
				);
				return null;
			}
			if (!class_exists($actionClassName)) {
				CLogger::warning('Action class does not exist, Action filename '
								.'should end with .class.php, and the prefix should '
								.'be the classname',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
							'actionID' 		  => var_export($actionID, true),
							'actionClassPath' => var_export($actionClassPath, true),
							'actionClassName' => var_export($actionClassName, true)
								  )
				);
				return null;
			}
			$actionObject = new $actionClassName;
			if (!($actionObject instanceof Action)) {
				CLogger::warning('the object reflected is not an Action', 
					     		  GlobalConfig::BINGO_LOG_ERRNO,
								 array(
							'actionID'        => var_export($actionID, true),
							'actionClassName' => var_export($actionClassName, true)
							 	 )
				);	
				return null;
			}
			$actionObject->actionID = $actionID;
			if (true !== $actionObject->initial($initObject)) {
				CLogger::warning('action->initial() failed',
						  		  GlobalConfig::BINGO_LOG_ERRNO,	
								  array(
							'actionID'        => var_export($actionID, true),
							'actionClassName' => var_export($actionClassName, true)
								  )
				);
				return null;
			}
			return $actionObject;
		}
		public function initial($initObject) {
			return true;
		}
		public abstract function execute($context, $actionParam = null);
	}

	class Event {
		public $name;
		public $source;
		public $object;

		public function __construct($name, $source, $object) {
			$this->name = $name;
			$this->source = $source;
			$this->object = $object;
		}
	}	
	class Error {
		public $errno;
		public $error;

		public function __construct($errno, $error) {
			global $context;
			$context->lastError = $this;
			$this->errno = $errno;
			$this->error = $error;
		}
	}
	class Context {

		public  $rootAction;
		private $isHooksInitialed;
		private $actionExecedStack;
		private $actionExecedStackPos;	
		private $actionStack;
		private $dict;	
		private $hooks;
		private $actions;
		private $denyActions;
		public $lastError;
		public $lastFailedAction;

		private static $instance;

		private function __construct() {
		}

		public function getCurrentAction() {
			if (0 >= count($this->actionStack)) {
				return null;
			}
			return $this->actionStack[count($this->actionStack) - 1];
		}

		public function getActionStack() {
			return $this->actionStack;
		}

		public function getCurrentActionParent() {
			if (1 >= count($this->actionStack)) {
				return null;
			}
			return $this->actionStack[count($this->actionStack) - 2];
		}
		public function getDebugInfo() {
			return var_export($this, true);
		}
		public function getActionExecedStack() {
			if (true === GlobalConfig::$isDebug){
				return $this->actionExecedStack;
			}
			return null;
		}
		public function getAllStack() {
			return debug_backtrace();
		}
		public function printAllStack() {
			debug_print_backtrace();
		}
		public function initial() {
			$this->actionExecedStack = array();
			$this->actionExecedStackPos = 1;
			$this->isHooksInitialed = false;
			$this->actionStack = array();
			$this->dict = array();
			$this->denyActions = array();	
			$this->actions = array();

			$this->rootAction = $this->getAction(GlobalConfig::$rootAction[0], 
				     	     			         GlobalConfig::$rootAction[1],	
								           		 GlobalConfig::$rootAction[2]);
			if (!($this->rootAction instanceof Action)) {
				CLogger::fatal('create rootAction failed', GlobalConfig::BINGO_LOG_ERRNO,
								array(
					'actionID' 	=> var_export(GlobalConfig::$rootAction[0], true),
			  'actionClassPath' => var_export(GlobalConfig::$rootAction[1], true)
								)
				);
				return false;
			}
			return true;
		}
		public function addDenyActionID($actionID) {
			if (!is_string($actionID) || 0 >= strlen($actionID)) {
				CLogger::warning('actionID is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								  array('actionID' => var_export($actionID, true))						);
				return false;
			}
			$this->denyActions[] = $actionID;
			return true;
		}
		public function removeDenyActionID($actionID) {
			$ret = array_search($actionID, $this->denyActions);
			if (is_int($ret)) {
				unset($this->denyActions[$ret]);
				return true;
			}
			return false;
		}
		public function modifyProperty($key, $value) {
			if (!is_string($key) || 0 >= strlen($key)) {
				CLogger::warning('key is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								  array('key' => var_export($key, true))
				);
				return false;
			}
			$this->dict[$key] = $value;
			return true;
		}
		public function getProperty($key) {
			if (!is_string($key) || 0 >= strlen($key)) {
				return null;
			}
			if (false === array_key_exists($key, $this->dict)) {
				return null;
			}
			return $this->dict[$key];
		}

		public function setProperty($key, $value) {
			if (!is_string($key) || 0 >= strlen($key)) {
				CLogger::warning('key is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								  array('key' => var_export($key, true))
				);
				return false;
			}
			if (isset($this->dict[$key])) {
				CLogger::warning('can not cover key which already exists',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array('key' => var_export($key, true))
				);
				return false;
			}
			$this->dict[$key] = $value;
			return true;
		}
		public function getAction($actionID, $actionClassPath = null, 
											 $initObject = null) {
			if (!is_string($actionID) || 0 >= strlen($actionID)) {
				CLogger::warning('actionID  is invalid',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'actionID' => var_export($actionID, true),
								'actionClassPath' => var_export($actionClassPath, true)
								  )
				);
				return null;
			}
			if (array_key_exists($actionID , $this->actions)) {
				return $this->actions[$actionID];
			}
			$action = Action::getAction($actionID, $actionClassPath, $initObject);
			if (!($action instanceof Action)) {
				CLogger::warning('get Action failed', GlobalConfig::BINGO_LOG_ERRNO,
									array(
						'actionID'	=> var_export($actionID, true),
						'actionClassPath' => var_export($actionClassPath, true),
						'context.actions' => var_export($this->actions, true)
									)
				);
				return null;
			}
			$this->actions[$actionID] = $action;
			return $action;
		}
		public static function getInstance() {
			if (!(self::$instance instanceof Context)) {
				self::$instance = new Context();			
			}
			return self::$instance;
		}
		public function addEventHook($eventName, $hookIncludePath, $hook) {
			if (!is_string($eventName) || 0 >= strlen($eventName)) {
				CLogger::warning('event name is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								 array('eventName' => var_export($eventName, true))	
				);
				return false;
			}
			if (true !== $this->isHooksInitialed) {
				require_once(FRAMEWORK_PATH.'config/HooksConfig.class.php');
				$this->hooks = HooksConfig::$config;
				if (!is_array($this->hooks)) {
					$this->hooks = array();
				}
				$this->isHooksInitialed = true;
			}
			$this->hooks[$eventName][] = array($hookIncludePath, $hook);
			return true;
		}
		public function fireEvent($event) {
			if (!($event instanceof Event) || !is_string($event->name)) {
				CLogger::warning('event is invalid',
								 GlobalConfig::BINGO_LOG_ERRNO,
								 array('event' => var_export($event, true))
				);
				return false;
			}
			if (true !== $this->isHooksInitialed) {
				require_once(FRAMEWORK_PATH.'config/HooksConfig.class.php');
				$this->hooks = HooksConfig::$config;
				if (!is_array($this->hooks)) {
					$this->hooks = array();
				}
				$this->isHooksInitialed = true;
			}
			if (!array_key_exists($event->name, $this->hooks)) {
				return false;
			}
			$hooksArr = $this->hooks[$event->name];
			if (!is_array($hooksArr) || 0 >= count($hooksArr)) {
				return false;
			}
			$isProcessed = false;
			foreach ($hooksArr as $index => $hookInfo) {
				$object   = null;
				$method   = null;
				$func     = null;
				$data     = null;	
				$hasData  = null;
		
				if (!is_array($hookInfo) || 2 != count($hookInfo)
					|| !is_string($hookInfo[0]) || 0 >= strlen($hookInfo[0])
					|| !is_array($hookInfo[1]) || 0 >= count($hookInfo[1])) {
					CLogger::warning('hook config error, one hook should '
									.'be a array contains two elements. '
									.'the first element of the hook config '
								    .'should be a filepath which need to be '
									.'included for running the hook. '
									.'the second element of the hook config '	
									.'should be a array which at least contains '
									.'one element',
									 GlobalConfig::BINGO_LOG_ERRNO,
									array(
								'eventName'  => var_export($event->name, true),
								'hooks[eventName][i]' => var_export($index, true),
								'hookInfo' => var_export($hookInfo, true)
									)				
					);
					continue;
				}
				$hook = $hookInfo[1];
				if (is_object($hook[0])) {
					$object = $hook[0];
					if (count($hook) < 2) {
						$method = "on".$event->name;
					} else {
						if (is_string($hook[1]) && strlen($hook[1]) > 0) {
							$method = $hook[1];
						} else {
							$method = "on".$event->name;
						}
						if (count($hook) > 2) {
							$data = $hook[2];
							$hasData = true;
						}
					}
				} elseif (is_string($hook[0]) && strlen($hook[0]) > 0) {
					$func = $hook[0];
					if (count($hook) > 1) {
						$data = $hook[1];
						$hasData = true;
					}
				} else {
					CLogger::warning('fireEvent hook invalid',
									  GlobalConfig::BINGO_LOG_ERRNO,		
									array(
						'eventName'  => var_export($event->name, true),
						'hooks[eventName][i]' => var_export($index, true),
						'hook'		 => var_export($hook, true)
									)
					);	
					continue;
				}
 
				if (isset($object)) {
					$callback = array($object, $method);
				} elseif ( false !== ($pos = strpos($func, "::"))) {
					if (strlen($func) <= $pos + 2) {
						CLogger::warning('fireEvent hook invalid',
										  GlobalConfig::BINGO_LOG_ERRNO,	
										  array(
								'eventName' => var_export($event->name, true),
								'func' 		=> var_export($func, true),
								'hooks[eventName][i]' => var_export($index, true),
											'hook' 	=> var_export($hook, true)
										  )
						);
						continue;
					}
					$callback = array(substr($func, 0, $pos), 
									  substr($func, $pos + 2));
				} else {
					$callback = $func;
				}
				
				$ret = include_once($hookInfo[0]);
				if (!$ret) {
					CLogger::warning('include hook filepath failed ',
									  GlobalConfig::BINGO_LOG_ERRNO,	
									  array(
						'eventName' => var_export($event->name, true),
						'hooks[eventName][i]' => var_export($index, true),
						'include_once' => var_export($hookInfo[0], true)
									  )
					);
					continue;
				}	
				if (!is_callable($callback)) {
					CLogger::warning('hook is not callable', GlobalConfig::BINGO_LOG_ERRNO,
									  array(
										'callback'     => var_export($callback, true),
										'eventName' => var_export($event->name, true),
							'hooks[eventName][i]' => var_export($index, true)
									  )
					);
					continue;
				}
				call_user_func($callback, Context::getInstance(), $event, $data);
				$isProcessed = true;
			}
			if (true !== $isProcessed) {
				CLogger::warning('no hook processed this event',	
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'eventName' => var_export($event->name, true)
								  )
				);
				return false;
			}
			return true;
		}
		public function fireError($error) {
			if (!($error instanceof Error) 
				|| !is_int($error->errno) || !is_string($error->error)) {
				CLogger::warning('error object is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								  array('error' => var_export($error, true))
				);
				return false;
			}
			$event = new Event(HOOK_ERRNO_PREFIX.$error->errno, 
							   'error:'.$error->errno , $error);
			return $this->fireEvent($event);	
		}
		public function callAction($actionID, $actionParam = null) {
			if (!is_string($actionID) || 0 >= strlen($actionID)) {
				CLogger::warning('actionID is not valid', GlobalConfig::BINGO_LOG_ERRNO,
								  array('actionID' => var_export($actionID, true))
				);
				return false;
			}
			if (is_int(array_search($actionID, $this->denyActions))){
				return false;
			} 
			$action = $this->getAction($actionID); 
			if (!($action instanceof Action)) {
				CLogger::warning('action is not instance of Action',
								  GlobalConfig::BINGO_LOG_ERRNO,
								  array(
									'actionID' => var_export($actionID, true),
									'action'   => var_export($action, true)
								  )
				);
				return false;
			}
			$this->actionStack[count($this->actionStack)] = $action;
			if (true === GlobalConfig::$isDebug) {
				// compute in stack prefix
				$inPrefix = '';
				for ($i = 0; $i < $this->actionExecedStackPos; $i ++) {
					$inPrefix .= '>>>';
				}
				$this->actionExecedStack[] = $inPrefix.$actionID;
				$this->actionExecedStackPos ++;	
			}

			if (true === GlobalConfig::$hookBeforeActionExecuteSwitch) {	
				$this->fireEvent(new Event(HOOK_BEFORE_ACTION_EXECUTE,
										   'Context', $action));
			}

			if (true === GlobalConfig::$hookBeforeSpecifyActionExecuteSwitch) {
				$this->fireEvent(new Event(
					HOOK_BEFORE_SPECIFY_ACTION_EXECUTE_PREFIX.$action->actionID,
					'Context', $action));
			}

			// execute action
			$ret = $action->execute($this, $actionParam);
		
			if (true === GlobalConfig::$hookAfterSpecifyActionExecuteSwitch) {
				$this->fireEvent(new Event(
					HOOK_AFTER_SPECIFY_ACTION_EXECUTE_PREFIX.$action->actionID,
					'Context', $action));
			}	

			if (true === GlobalConfig::$hookAfterActionExecuteSwitch) {
				$this->fireEvent(new Event(HOOK_AFTER_ACTION_EXECUTE,
										   'Context', array($action, $ret)));
			}

			unset($this->actionStack[count($this->actionStack) - 1]);
			if (true === GlobalConfig::$isDebug) {
				// compute out stack prefix
				$this->actionExecedStackPos --;	
				$outPrefix = '';
				for ($i = 0; $i < $this->actionExecedStackPos; $i ++) {
					$outPrefix .= '<<<';
				}
				$this->actionExecedStack[] = $outPrefix.$actionID;
			}
			return $ret;
		}
	}
?>
