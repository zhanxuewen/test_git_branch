<?php

namespace App\Library;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log
{
    protected $logger;
    protected $log_path;

    protected $name = 'logger';

    public function __construct()
    {
        $this->logger = new Logger($this->name);
        $this->log_path = storage_path('logs');
    }

    protected function bindModule($module)
    {
        $daily = env('APP_LOG') == 'daily' ? '-' . date('Y-m-d') : '';
        $url = $this->log_path . '/' . $module . $daily . '.log';
        $handlers = $this->logger->getHandlers();
        if (empty($handlers) or $handlers[0]->getUrl() != $url) {
            $this->logger->pushHandler(new StreamHandler($url, Logger::DEBUG));
        }
    }

    protected function sendEmail($message)
    {
//		if (!env('APP_DEBUG')) {
//			$email = new MailHelper();
//			foreach (config('mail.receiver.log') as $receiver) {
//				$email->sendEmail('log', ['log' => $message], $receiver, 'Log From Vanthink!');
//			}
//		}
    }

    protected function logMessage($method, $module, $message)
    {
        $this->bindModule($module);
        $this->sendEmail($message);
        return $this->logger->$method($message);
    }

    public function debug($module, $message)
    {
        return $this->logMessage('debug', $module, $message);
    }

    public function info($module, $message)
    {
        return $this->logMessage('info', $module, $message);
    }

    public function notice($module, $message)
    {
        return $this->logMessage('notice', $module, $message);
    }

    public function warning($module, $message)
    {
        return $this->logMessage('warning', $module, $message);
    }

    public function error($module, $message)
    {
        return $this->logMessage('error', $module, $message);
    }

    public function critical($module, $message)
    {
        return $this->logMessage('critical', $module, $message);
    }

    public function alert($module, $message)
    {
        return $this->logMessage('alert', $module, $message);
    }

    public function emergency($module, $message)
    {
        return $this->logMessage('emergency', $module, $message);
    }

}