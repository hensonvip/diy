<?php
/**
 * MALL API Loader
 *
 * User: vincent.cao
 * Date: 14-9-10
 * Time: 下午2:37
 */

class Loader
{
    protected static $_instance;
    protected static $_basePath = array();

    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public static function setBasePath($basePath)
    {
        if (is_array($basePath)) {
            self::$_basePath = $basePath;
        } elseif (is_string($basePath)) {
            self::$_basePath = array($basePath);
        }
        return self::getInstance();
    }

    /**
     *
     * Constructor
     *
     * Registers instance with spl_autoload stack
     *
     */
    protected function __construct()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
    
    /**
     * Autoload a class
     *
     * @param  string $class
     * @return bool
     */
    public static function autoload($class)
    {
        $loader = self::getInstance();
        
        if ($loader) {
            if ($loader->_autoload($class)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Attempt to autoload a class
     *
     * @param  string $class
     * @return mixed False if not matched, otherwise result if include operation
     */
    public function _autoload($class)
    {
        $classPath = $this->getClassPath($class);
        if (false !== $classPath) {
            return include $classPath;
        }
        return false;
    }
    
    /**
     * Helper method to calculate the correct class path
     *
     * @param string $class
     * @return False if not matched other wise the correct path
     */
    public function getClassPath($class)
    {
        foreach (self::$_basePath as $path) {
            $classPath = $path . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
            if (self::isReadable($classPath)) {
                return $classPath;
            }
        }
    
        return false;
    }
    
    /**
     * Returns TRUE if the $filename is readable, or FALSE otherwise.
     * This function uses the PHP include_path, where PHP's is_readable()
     * does not.
     *
     * Note from ZF-2900:
     * If you use custom error handler, please check whether return value
     *  from error_reporting() is zero or not.
     * At mark of fopen() can not suppress warning if the handler is used.
     *
     * @param string   $filename
     * @return boolean
     */
    public static function isReadable($filename)
    {
        if (is_readable($filename)) {
            // Return early if the filename is readable without needing the
            // include_path
            return true;
        }
    
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'
                && preg_match('/^[a-z]:/i', $filename)
        ) {
            // If on windows, and path provided is clearly an absolute path,
            // return false immediately
            return false;
        }
    
        foreach (self::explodeIncludePath() as $path) {
            if ($path == '.') {
                if (is_readable($filename)) {
                    return true;
                }
                continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . $filename;
            if (is_readable($file)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Explode an include path into an array
     *
     * If no path provided, uses current include_path. Works around issues that
     * occur when the path includes stream schemas.
     *
     * @param  string|null $path
     * @return array
     */
    public static function explodeIncludePath($path = null)
    {
        if (null === $path) {
            $path = get_include_path();
        }
    
        if (PATH_SEPARATOR == ':') {
            // On *nix systems, include_paths which include paths with a stream
            // schema cannot be safely explode'd, so we have to be a bit more
            // intelligent in the approach.
            $paths = preg_split('#:(?!//)#', $path);
        } else {
            $paths = explode(PATH_SEPARATOR, $path);
        }
        return $paths;
    }
    
    
}
