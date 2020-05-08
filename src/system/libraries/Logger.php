<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\JsonFormatter;

/**
 * Logging Class
 * Application logger class. Used for application specific code
 */
class CI_Logger {
    // TODO: Need to refactor this class and
    // actually make it extend a PSR-3 logger interface!

    private static $logger = null;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct($config = array())
	{
        self::getInstance();
    }


    public static function getInstance() {
        if (self::$logger == null) {
            // create a log channel
            self::$logger = new Logger('app');

            // Setup formatter for easy parsing in CloudWatch
            $formatter = new JsonFormatter();

            // Log to standard out
            $handler = new StreamHandler('php://stdout');
            $handler->setFormatter($formatter);
            self::$logger->pushHandler($handler);

            // Add daily logging to development
            if (ENVIRONMENT === 'development') {
                $handler = new RotatingFileHandler('/var/www/log/app.log');
                self::$logger->pushHandler($handler);
            }

            // Add web request information
            self::$logger->pushProcessor(new WebProcessor());
            // Add ine/file/class/method details
            self::$logger->pushProcessor(new IntrospectionProcessor());
            // Add container identifier to pick out from ecs cluster
            self::$logger->pushProcessor(function ($record) {
                $record['extra']['containerId'] = (getenv('HOSTNAME') !== false ? getenv('HOSTNAME') : null);
                return $record;
            });
        }

        return self::$logger;
    }


    /**
     * System is unusable.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function emergency($message, array $context = array()) {
        self::$logger->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function alert($message, array $context = array()) {
        self::$logger->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function critical($message, array $context = array()) {
        self::$logger->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function error($message, array $context = array()) {
        self::$logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function warning($message, array $context = array()) {
        self::$logger->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function notice($message, array $context = array()) {
        self::$logger->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function info($message, array $context = array()) {
        self::$logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function debug($message, array $context = array()) {
        self::$logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array()) {
        self::$logger->log($level, $message, $context);
    }
}
