<?php
namespace def\Session;

class Session
{
    protected static $validOptions = [
        "cache_limiter"            => true, "cookie_domain"           => true, "cookie_httponly"         => true,
        "cookie_lifetime"          => true, "cookie_path"             => true, "cookie_secure"           => true,
        "entropy_file"             => true, "entropy_length"          => true, "gc_divisor"              => true,
        "gc_maxlifetime"           => true, "gc_probability"          => true, "hash_bits_per_character" => true,
        "hash_function"            => true, "name"                    => true, "referer_check"           => true,
        "serialize_handler"        => true, "use_cookies"             => true, "use_only_cookies"        => true,
        "use_trans_sid"            => true, "upload_progress.enabled" => true, "upload_progress.cleanup" => true,
        "upload_progress.prefix"   => true, "upload_progress.name"    => true, "upload_progress.freq"    => true,
        "upload_progress.min-freq" => true, "url_rewriter.tags"       => true,
    ];

    protected static $prefixDefault = "__main__";

    protected $prefix;

    protected static $flashPrefixDefault = "__flash__";

    protected $flashPrefix;

    public function __construct($prefix = null)
    {
        $this->prefix = isset($prefix) ? $prefix : static::$prefixDefault;
        $this->flashPrefix = static::$flashPrefixDefault;
    }

    private static function ensureIsArray(array $data, ...$keys) // nested
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                return false;
            }

            $data = $data[$key];
        }

        return true;
    }

    public function has($key)
    {
        return static::open() && self::ensureIsArray($_SESSION, $this->prefix)
            && array_key_exists($key, $_SESSION[$this->prefix]);
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $_SESSION[$this->prefix][$key] : $default;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function getAll()
    {
        return static::open() && self::ensureIsArray($_SESSION, $this->prefix) ? $_SESSION[$this->prefix] : [];
    }

    public function put($key, $value)
    {
        if (static::open() && (!isset($_SESSION[$this->prefix]) || is_array($_SESSION[$this->prefix]))) {
            $_SESSION[$this->prefix][$key] = $value;
        }
    }

    public function __set($key, $value)
    {
        $this->put($key, $value);
    }

    public function putAll(array $values)
    {
        if (static::open()) {
            if (self::ensureIsArray($_SESSION, $this->prefix)) {
                $values = array_merge($_SESSION[$this->prefix], $values);
            }

            $_SESSION[$this->prefix] = $values;
        }
    }

    public function remove($key)
    {
        if (static::open()) {
            unset($_SESSION[$this->prefix][$key]);
        }
    }

    public function __unset($key)
    {
        $this->remove($key);
    }

    public function removeAll()
    {
        if (static::open()) {
            unset($_SESSION[$this->prefix]);
        }
    }

    public function flashPrefix($prefix = null)
    {
        if (func_num_args()) {
            $this->flashPrefix = $prefix;
        }

        if (empty($this->flashPrefix)) {
            $this->flashPrefix = static::$flashPrefix;
        }

        return $this->flashPrefix;
    }

    public function flash($key, ...$values)
    {
        $flashStorage = $this->get($this->flashPrefix, []);

        if (!is_array($flashStorage)) {
            throw new \UnexpectedValueException("Flash session storage expected to be an array");
        }

        $flashStorage[$key] = self::ensureIsArray($flashStorage, $key)
            ? array_merge($flashStorage[$key], $values) : $values;

        if (func_num_args() > 1) { // just append
            $this->put($this->flashPrefix, $flashStorage);
        } else { // remove
            unset($_SESSION[$this->prefix][$this->flashPrefix][$key]);
        }

        return $flashStorage[$key];
    }

    public function __debugInfo()
    {
        return $this->getAll();
    }

    public static function status()
    {
        return session_status();
    }

    public static function statusIs($status)
    {
        return $status === static::status();
    }

    public static function enabled()
    {
        return !static::statusIs(PHP_SESSION_DISABLED);
    }

    public static function active()
    {
        return static::statusIs(PHP_SESSION_ACTIVE);
    }

    public static function inactive()
    {
        return static::statusIs(PHP_SESSION_NONE);
    }

    public static function open()
    {
        if (static::inactive() && session_start()) {
            session_register_shutdown();
        }

        return static::active();
    }

    public static function close()
    {
        if (static::active()) {
            session_write_close();
        }
    }

    public static function reset()
    {
        if (static::active()) {
            session_reset();
        }
    }

    public static function abort()
    {
        if (static::active()) {
            session_abort();
        }
    }

    public static function clear()
    {
        if (static::open()) {
            session_unset();
        }
    }

    public static function destroy()
    {
        if (static::open()) {
            session_unset();
            session_destroy();
        }
    }

    public static function id($id = null)
    {
        return func_num_args() ? session_id($id) : session_id();
    }

    public static function regenerateId($delete_old = false)
    {
        return session_regenerate_id($delete_old);
    }

    public static function name($name = null)
    {
        return func_num_args() ? session_name($name) : session_name();
    }

    public static function path($path = null)
    {
        return func_num_args() ? session_save_path($path) : session_save_path();
    }

    public static function getCookieParams()
    {
        return session_get_cookie_params();
    }

    public static function setCookieParams($lifetime, $path = "/", $domain = null, $secure = false, $httponly = false)
    {
        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    }

    public static function setSaveHandler(\SessionHandlerInterface $handler)
    {
        session_set_save_handler($handler);
    }

    public static function option($key, $value = null)
    {
        if (isset(static::$validOptions[$key])) {
            return 1 == func_num_args() ? ini_get("session.{$key}") : ini_set("session.{$key}", $value);
        }
    }

    public static function restoreOption($key)
    {
        if (isset(static::$validOptions[$key])) {
            ini_restore("session.{$key}");
        }
    }

    public static function restoreOptions()
    {
        foreach (array_keys(static::$validOptions) as $key) {
            ini_restore("session.{$key}");
        }
    }
}
