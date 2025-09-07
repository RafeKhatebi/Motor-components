<?php
class CacheManager
{
    private static $cache_dir = __DIR__ . '/../cache/';

    public static function init()
    {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }

    public static function get($key)
    {
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';

        if (!file_exists($file))
            return null;

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    public static function set($key, $value, $duration = 300)
    {
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';

        $data = [
            'value' => $value,
            'expires' => time() + $duration
        ];

        file_put_contents($file, serialize($data));
    }

    public static function delete($key)
    {
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function clear()
    {
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
?>