<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Logging Class
 * Default CI Log class. This is different compared to the Application logger class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/general/errors.html
 */
class CI_Log {

	/**
	 * Path to save log files
	 *
	 * @var LoggerInterface
	 */
    protected $_logger;

	/**
	 * Level of logging
	 *
	 * @var int
	 */
	protected $_threshold = 1;

	/**
	 * Array of threshold levels to log
	 *
	 * @var array
	 */
	protected $_threshold_array = array();

	/**
	 * Predefined logging levels
	 *
	 * @var array
	 */
	protected $_levels = array('ERROR' => 1, 'DEBUG' => 2, 'INFO' => 3, 'ALL' => 4);

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$config =& get_config();

        // Specific Logger for CodeIgniter
        $logger = CI_Logger::getInstance();
        $this->_logger = $logger->withName('ci');

        $handlers = $this->_logger->getHandlers();
        for ($i = 0; $i < count($handlers); $i++) {
            if ($handlers[$i] instanceof Monolog\Handler\RotatingFileHandler) {
                $handlers[$i] = new Monolog\Handler\RotatingFileHandler('/var/www/log/ci.log');
            }
        }
        $this->_logger->setHandlers($handlers);

		if (is_numeric($config['log_threshold']))
		{
			$this->_threshold = (int) $config['log_threshold'];
		}
		elseif (is_array($config['log_threshold']))
		{
			$this->_threshold = 0;
			$this->_threshold_array = array_flip($config['log_threshold']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	$level 	The error level: 'error', 'debug' or 'info'
	 * @param	string	$msg 	The error message
	 * @return	bool
	 */
	public function write_log($level, $msg)
	{
		$level = strtoupper($level);

		if (( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
			&& ! isset($this->_threshold_array[$this->_levels[$level]]))
		{
			return FALSE;
        }

        switch ($level) {
            case 'ERROR':
                $this->_logger->error($msg);
                break;
            case 'DEBUG':
                $this->_logger->debug($msg);
                break;
            default:
                // info
                $this->_logger->info($msg);
                break;
        }
        // fwrite() returns the number of bytes written, or FALSE on error.
        // We replaced fwrite() with monologger
        $result = 1;

		return is_int($result);
	}
}
