<?php

namespace slkj\unionpay;

/**
 * Class UnionLogger
 *
 * <project_root_folder>/assets/logs/<method>/<date>_<method>.log
 *
 * Defined each method by static mode
 *
 * @method static log($message)
 * @method static debug($message)
 * @method static info($message)
 * @method static warn($message)
 * @method static error($message)
 * @package slkj\unionpay
 */
class UnionLogger
{
    const ENV_SHOW_FILE_NAME    = 'LOGGER_LOG2FILE_SHOW_FILE_NAME';
    const ENV_ECHO_MESSAGE      = 'LOGGER_LOG2FILE_ECHO_MESSAGE';

    const LOG_EXTENSION = '.txt';
    const MAX_FILE_SIZE = 2;

    /**
     * magic static methods definition
     * @param string $method
     * @param array $args
     */
    public static function __callStatic($method, $args) {
        if (count($args) == 2) {
            self::write($args[0], $method, $args[1]);
        } else {
            self::write($args[0], $method);
        }
    }

    /**
     * Write message to file
     * @param $message
     * @param $method
     */
    private static function write($message, $method = 'log', $prefix = '') {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        if ($prefix) {
            $message = "{$prefix}:{$message}";
        }
        list($usec, $sec) = explode(" ", microtime());
        $message = date('Y-m-d H:i:s') . " {$usec} - {$method} " . "\t\t" . Utilities::u2c($message) . "\r\n";
        $file = self::isBackupFile($method);
        $file = realpath($file);
        $fd = fopen($file, 'ab');
        fwrite($fd, $message);

        // set AND 1
        if (isset($_ENV[self::ENV_SHOW_FILE_NAME]) && $_ENV[self::ENV_SHOW_FILE_NAME] == 1) {
            echo "{$file} => ";
        }
        // not set OR 1
        if (!isset($_ENV[self::ENV_ECHO_MESSAGE]) || $_ENV[self::ENV_ECHO_MESSAGE] == 1) {
            echo "{$message}";
        }

        fclose($fd);
    }


    /**
     * To backup file when size > MAX_FILE_SIZE
     * @param $method
     * @return string
     */
    private static function isBackupFile($method)
    {
        $file = self::getLogFile($method);
        clearstatcache($file);
        $size = filesize($file);
        if ($size > self::MAX_FILE_SIZE * 1024 * 1024) {
            $flag = rename($file, $file . '.' . date('YmdHis') . '.backup');
            if ($flag) {
                touch($file);
            }
        }
        return $file;
    }

    /**
     * <project_root_folder>/assets/log/<method>/<date>_<method>.log
     * @param $method
     * @return string
     */
    private static function getLogFile($method) {
        $sr = DIRECTORY_SEPARATOR;
        $ext = self::LOG_EXTENSION;
        $today = date('Ymd');

        $path = __DIR__ . "{$sr}..{$sr}..{$sr}..{$sr}assets/logs/{$method}/"; // via composer: <project_root_folder>/assets/log/<name>
        if (!is_dir($path)) {
            mkdir($path,0777,true);
        }
        $file = "{$path}{$today}_{$method}{$ext}";
        if (!file_exists($file)) {
            touch($file);
        }
        return $file;
    }
}