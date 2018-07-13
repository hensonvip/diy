<?php
/**
 * Logger class
 * User: vincent.cao
 * Date: 14-4-22
 * Time: 上午9:39
 */

class Logger {

    protected $_logger;
	protected $_log_path;
	protected $_threshold	= 1;
	protected $_date_fmt	= 'Y-m-d H:i:s';
	protected $_enabled	= TRUE;
	protected $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3');
    protected static $_mem = null;
    protected $_mem_key = 'member_sdk_log';
    protected $_redis = null;
    protected $_redis_key = 'xsx_cls_log_redis_key';
    protected $_log_pre = 'log_';
    protected $_environment_main = '';

	public function __construct($config=array())
	{
//
        $this->_type = isset($config['type']) ? $config['type'] : 'file';


        $this->_log_path = isset($config['log_path']) ? $config['log_path'] : '';

        if ( ! is_dir(LOG_PATH))
        {
            @mkdir(LOG_PATH, 0777);
        }

        if ( ! is_dir($this->_log_path))
        {
            @mkdir($this->_log_path, 0777);
        }

	}

	public function writeLog($msg, $level = 'error', $log_name='response')
	{
        if ($this->_enabled === FALSE)
        {
            return FALSE;
        }


        $level = strtoupper($level);

        if ( ! isset($this->_levels[$level]))
        {
            return FALSE;
        }


        $data['msg'] = $msg;
        $data['level'] = $level;
        $data['date'] = time();
        $data['logpath'] = $this->_log_path;
        $data['filepath'] = $this->_log_path.$this->_log_pre.$log_name.'_'.date('Ymd').'_'.$level.'.log';

        $this->_writeLogFile($msg, $data['filepath'], $level);


	}

    public function readLog($num, $date, $level)
    {
        if ($this->_enabled === FALSE)
        {
            return FALSE;
        }

        empty($date) && $date = date('Y-m-d');

        $this->_readLogFile($num, $date, $level);

    }

    private function _writeLogFile($msg, $filepath, $level='DEBUG')
    {
//         $filepath = $this->_log_path.$this->_log_pre.$log_name.'_'.date('Ymd').'_'.$level.'.log';

        if ( ! $fp = @fopen($filepath, 'a+'))
        {
            return FALSE;
        }

        $message = $this->_logFormat($msg, $level);

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        return TRUE;

    }

    private function _readLogFile($num, $date, $level)
    {
        $filepath = $this->_log_path.'sdk_log_'.$date.'_'.$level.'.log';
        $fp = @fopen($filepath, "r"); //以只读的方式打开online.txt文件
        $pos=-2;
        $eof="";
        $str="";
        while($num>0)
        {
            while($eof!="\n")
            {
                if(!fseek($fp,$pos,SEEK_END))
                {
                    $eof=fgetc($fp);
                    $pos--;
                }else
                {
                    break;
                }
            }
            $str.=fgets($fp);
            $eof="";
            $num--;
        }

        fclose($fp);

        echo nl2br($str);

        return TRUE;
    }


    private function _logFormat($msg, $level)
    {
//         mb_strlen($msg) > 10000 && $msg = mb_substr($msg, 0, 10000).'...';
//         $log_str = $level . ' - ' . date($this->_date_fmt). ' --> '.$msg."\n";
	    $data['date'] = time();
        $date = date('D, d M y H:i:s O', $data['date']);
        $record_time = date('Y-m-d H:i:s', $data['date']);
        $log_str = '['.$date.']['.$level.']['.$record_time.'] '.$msg."\n";

        return $log_str;
    }

}
