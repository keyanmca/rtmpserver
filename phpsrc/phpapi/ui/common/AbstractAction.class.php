<?php
/***************************************************************************
 * 
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
 
 
 
/**
 * @file AbstractAction.class.php
 * @author gaowei(gaowei@baidu.com)
 * @date 2013/09/02 11:56:51
 * @brief 
 *  
 **/
require_once (PHPLIB_PATH . '/log/CLogger.class.php');

abstract class AbstractAction extends Action {
    // 定义返回值样式
    protected $result = array ("errno" => 1 );
    protected $logMsg = '';
    protected $logCode = 0;
	private $_startTime = 0;
	private $_executeTime = 0;
	private $_importKey = array(
			'cursor',
			'path',
		);

    /**
     * @return the $logMsg
     */
    public function getLogMsg() {
        return $this->logMsg;
    }
    
    /**
     * @return the $logCode
     */
    public function getLogCode() {
        return $this->logCode;
    }
    
    /**
     * @param string $logMsg
     */
    public function setLogMsg($logMsg) {
        $this->logMsg = $logMsg;
        return $this;
    }
    
    /**
     * @param number $logCode
     */
    public function setLogCode($logCode) {
        $this->logCode = $logCode;
        return $this;
    }
    
    /**
     *
     * @param array $param        	
     * @param int $code        	
     * @param string $msg        	
     * @param string $logLevel        	
     */
    public function serverInit($msg = '', $code = 0, array $param = array(), $logLevel = 'notice') {
       
	   $this->_startTime = microtime(true);
    }
    
      
    
    /**
     * 
     * 检测返回值是否为整型,若为整型，设置错误码退出。
     * @param mixed $res
     * @param string $strLog
     */
    public function attemptExit($res, $strLog = '', $infoArray = array()) {
	
		$this->_executeTime = round((microtime(true) - $this->_startTime)*1000);
		
        if (is_int ( $res )) {
            $this->result ['errno'] = $res;
			$this->serverQuit ( $strLog , $infoArray); 
        }
    }
	
    public function attemptJqueryExit($res, $strLog = '', $infoArray = array()) {
        if (is_int ( $res )) {
            $this->result ['errno'] = $res;
			$this->serverJqueryQuit ( $strLog , $infoArray = array()); 
        }
    }
 
    /**
     * 
     * 统一退出出口
     * @param string $strLog
     */
    public function serverQuit($strLog = '' , $infoArray = array()) {
				
		
		$infoArray = array_merge($infoArray,$_GET,$_POST);
		foreach($infoArray as $key => $value){
			if(in_array($key, $this->_importKey))
				continue;
			if(strlen($value)>100){
				$infoArray[$key] = substr($value,0,100).'...';
			}
		}
		
		$this->_executeTime = round((microtime(true) - $this->_startTime)*1000);
		if(isset($_SERVER['HTTP_USER_AGENT']))  
			$ua =  strtolower($_SERVER['HTTP_USER_AGENT']);
		else   
			$ua = '';
        if (! empty ( $this->result )) {
       		header('Content-Type: application/json; charset=UTF-8');
			$finalRet = json_encode ( $this->result );
			echo $finalRet;

			$infoArray['useragent'] = $ua;
			$infoArray['exec_time'] = $this->_executeTime;
			if(isset($this->result['errno']))
				$infoArray['ret_code'] = $this->result['errno'];
			else
				$infoArray['ret_code'] = 0;
        }else{
			$infoArray['useragent'] = $ua;
			$infoArray['exec_time'] = $this->_executeTime;
		}

       	CLogger::notice ('request done',$this->logCode, $infoArray);
		
        exit ();
    }

	public function serverAsyncQuit($strLog = '' , $infoArray = array()) {
		$infoArray = array_merge($infoArray,$_GET,$_POST);
		foreach($infoArray as $key => $value){
			if(in_array($key, $this->_importKey))
				continue;
			if(strlen($value)>100){
				$infoArray[$key] = substr($value,0,100).'...';
			}
		}
		ob_end_clean();
		ob_start();		
		$this->_executeTime = round((microtime(true) - $this->_startTime)*1000);
		if(isset($_SERVER['HTTP_USER_AGENT']))  
			$ua =  strtolower($_SERVER['HTTP_USER_AGENT']);
		else   
			$ua = '';
        if (! empty ( $this->result )) {
       		header('Content-Type: application/json; charset=UTF-8');
			$finalRet = json_encode ( $this->result );
			echo $finalRet;
			$infoArray['useragent'] = $ua;
			$infoArray['exec_time'] = $this->_executeTime;
			if(isset($this->result['errno']))
				$infoArray['ret_code'] = $this->result['errno'];
			else
				$infoArray['ret_code'] = 0;
        }else{
			$infoArray['useragent'] = $ua;
			$infoArray['exec_time'] = $this->_executeTime;
		}
       	CLogger::notice ('request done',$this->logCode, $infoArray);
		header('Connection: close');
		header("Content-Length: ".ob_get_length());
		ob_end_flush();
		flush();
		
    }


}
