<?php
namespace def\Session;

class Session
{
	protected static $validOptions = [
		'cache_limiter'            => true, 'cookie_domain'           => true, 'cookie_httponly'         => true,
		'cookie_lifetime'          => true, 'cookie_path'             => true, 'cookie_secure'           => true,
		'entropy_file'             => true, 'entropy_length'          => true, 'gc_divisor'              => true,
		'gc_maxlifetime'           => true, 'gc_probability'          => true, 'hash_bits_per_character' => true,
		'hash_function'            => true, 'name'                    => true, 'referer_check'           => true,
		'serialize_handler'        => true, 'use_cookies'             => true, 'use_only_cookies'        => true,
		'use_trans_sid'            => true, 'upload_progress.enabled' => true, 'upload_progress.cleanup' => true,
		'upload_progress.prefix'   => true, 'upload_progress.name'    => true, 'upload_progress.freq'    => true,
		'upload_progress.min-freq' => true, 'url_rewriter.tags'       => true,
	];

	protected static $flashPrefix = '__flash_';

	public static function enabled()
	{
		return \PHP_SESSION_DISABLED != \session_status();
	}

	public function __construct(array $options = [])
	{
		foreach($options as $key => $value) {
			$this->option($key, $value);
		}

		\register_shutdown_function([$this, 'close']);
	}

	public function open()
	{
		return $this->active() ?: (\session_start() && $this->active());
	}

	public function active()
	{
		return \PHP_SESSION_ACTIVE == \session_status();
	}

	public function close()
	{
		if($this->active()) \session_write_close();
	}

	public function clear()
	{
		if($this->active()) \session_unset();
	}

	public function destroy()
	{
		if($this->active()) {
			\session_unset(); \session_destroy();
		}
	}

	public function id($id = null)
	{
		return \func_num_args() ? \session_id($id) : \session_id();
	}

	public function regenerateId($delete_old = false)
	{
		return \session_regenerate_id((bool)$delete_old);
	}

	public function name($name = null)
	{
		return \func_num_args() ? \session_name($name) : \session_name(); 
	}

	public function path($path = null)
	{
		return \func_num_args() ? \session_save_path($path) : \session_save_path();
	}

	public function get($key, $default = null)
	{
		return $this->has($key) ? $_SESSION[$key] : $default;
	}

	public function set($key, $value)
	{
		if($this->open())
			return $_SESSION[$key] = $value;
	}

	public function remove($key)
	{
		if($this->open())
			unset($_SESSION[$key]);
	}

	public function has($key)
	{
		return $this->open() && array_key_exists($key, $_SESSION);
	}

	public function flash($key, $value = null)
	{	
		$key = static::$flashPrefix . $key;

		$this->open();

		$flash  = (isset($_SESSION[$key]) && \is_array($_SESSION[$key])) ? $_SESSION[$key] : [];

		if(\func_num_args() > 1)
			return $_SESSION[$key] = \array_merge($flash, \array_slice(\func_get_args(), 1));

		unset($_SESSION[$key]);

		return $flash;
	}

	public function option($key, $value = null)
	{
		if(isset(static::$validOptions[$key]))
			return 1 == \func_num_args() ? \ini_get("session.{$key}") : \ini_set("session.{$key}", $value);
	}

	public function restoreOption($key)
	{
		if(isset(static::$validOptions[$key]) && \get_cfg_var('cfg_file_path')) {
			\ini_set("session.{$key}", \get_cfg_var("session.{$key}"));
		}
	}

	public function restoreOptions()
	{
		if(\get_cfg_var('cfg_file_path'))
			foreach(\array_keys(static::$validOptions) as $key) \ini_restore("session.{$key}");
	}
}
