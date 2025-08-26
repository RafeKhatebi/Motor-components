<?php
class SimpleCache {
    private static $dir = "cache/";
    
    public static function get($key) {
        $file = self::$dir . md5($key) . ".cache";
        if (file_exists($file) && (time() - filemtime($file)) < 3600) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }
    
    public static function set($key, $data) {
        if (!is_dir(self::$dir)) mkdir(self::$dir, 0755, true);
        $file = self::$dir . md5($key) . ".cache";
        file_put_contents($file, serialize($data));
    }
}