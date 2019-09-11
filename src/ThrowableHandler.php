<?php declare(strict_types=1);

namespace Tolkam\ThrowableHandler;

use ErrorException;
use Throwable;

class ThrowableHandler
{
    /**
     * filename to write errors to
     * @var string
     */
    protected $filename;

    /**
     * expose errors flag
     * @var bool
     */
    protected $exposeErrors;

    /**
     * verbose output flag
     * @var bool
     */
    protected $verboseErrors;

    /**
     * @param string|null  $filename Filename to write errors to
     */
    public function __construct(string $filename = null)
    {
        // disable error displaying as we're going to display them ourselves
        // ini_set('display_errors', '0');
        
        // check if log file is writable
        if ($filename && !@fopen($filename, 'a')) {
            $this->handle(new ErrorException(error_get_last()['message']));
            return;
        }
        
        $this->filename = $filename;
    }

    /**
     * Enables error message display
     *
     * @return void
     */
    public function exposeErrors(): void
    {
        $this->exposeErrors = true;
    }

    /**
     * Enables verbose error message
     *
     * @return void
     */
    public function verboseErrors(): void
    {
        $this->verboseErrors = true;
    }

    /**
     * Register all handlers
     *
     * @return void
     */
    public function catchAll(): void
    {
        $this->catchErrors();
        $this->catchShutdown();
        $this->catchExceptions();
    }

    /**
     * Register errors handler
     *
     * @return void
     */
    public function catchErrors(): void
    {
        set_error_handler([$this, 'onError']);
    }

    /**
     * Register shutdown handler
     *
     * @return void
     */
    public function catchShutdown(): void
    {
        register_shutdown_function([$this, 'onShutdown']);
    }

    /**
     * Register exceptions handler
     *
     * @return void
     */
    public function catchExceptions(): void
    {
        set_exception_handler([$this, 'handle']);
    }

    /**
     * Process runtime errors
     *
     * @param  int $type
     * @param  string $message
     * @param  string $file
     * @param  int $line
     * @return void
     */
    public function onError(int $type, string $message, string $file, int $line)
    {
        // respect error reporting settings
        if (! (error_reporting() & $type)) {
            return;
        }
        
        $this->handle(new ErrorException($message, 0, $type, $file, $line));
    }

    /**
     * Process shutdown errors
     *
     * In PHP7 fatal errors are Throwables,
     * but php.net says "most", not "all". So, just in case
     *
     * @return void
     */
    public function onShutdown()
    {
        if (!$err = error_get_last()) {
            return;
        }
        
        ob_get_level() && ob_clean();
        
        $this->handle(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
    }


    /**
     * Handles thrown error
     *
     * @param  Throwable $t
     * @return void
     */
    public function handle(Throwable $t)
    {
        $this->writeLog($t);
        $this->sendResponse($t);
    }
    
    /**
     * Writes error to log
     *
     * @param Throwable $t
     */
    protected function writeLog(Throwable $t)
    {
        $logEntry = (string) $t;
    
        if ($this->filename) {
            $logEntry = '[' . date('d-M-Y H:i:s e') . '] ' . $logEntry . PHP_EOL;
            error_log($logEntry, 3, $this->filename);
        } else {
            error_log($logEntry);
        }
    }
    
    /**
     * Prints the response
     *
     * @param Throwable $t
     *
     * @return void
     */
    protected function sendResponse(Throwable $t)
    {
        $body = $this->exposeErrors ? $t->__toString() : 'An error has occurred';

        // Command line
        if (PHP_SAPI === 'cli') {
            $body = isset($_SERVER['TERM']) ? "\033[41;1;97m " . $body . " \033[0m\n" : $body;
        } else {
            if (! headers_sent()) {
                header('HTTP/1.0 500 Unknown Error');
                header('Content-Type: text/plain; charset=utf-8');
                header('Cache-Control: private, no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
            }
        }
        
        echo $body;

        // exit with failure status
        exit(1);
    }
}
