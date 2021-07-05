<?php
class Cache {
    private static $cache = array();
    public static $fileCache = array();    

    /**
     * Store value by key in Memory Cache
     */
    public static function Store($key, $value){
        $file = Utilities::getCallerInfo(2, true)["name"];
        Cache::$cache[$file.$key] = $value;
        return $value;
    }

    /**
     * Get value by key from Memory Cache
     */
    public static function Get($key) {
        $file = Utilities::getCallerInfo(2, true)["name"];
        if(isset(Cache::$cache[$file.$key]))
            return Cache::$cache[$file.$key];
        return null;
    }

    public static function GetOrStore($key, $fun){
        $data = Cache::Get($key);
		if($data != null)
			return $data;

		$result = $fun();
		if($result == null) return null; 
		return Cache::Store($key, $result);
    }

    public static function GetOrStoreByDb($key, $fun, $override = false){
        $data = Cache::Get($key);
		if($data != null && !$override)
			return $data;

		$result = $fun();
		if(count($result) == 0) return null; 
		return Cache::Store($key, $result->fetch());
    }

    public static function GetOrStoreByDbMultiple($key, $fun, $override = false){
        $data = Cache::Get($key);
		if($data != null && !$override)
			return $data;

		$result = $fun();
		if(count($result) == 0) return null; 
		return Cache::Store($key, $result->fetchAll());
    }
    
    private static function getStoreFileLocation() {
        if(!file_exists(_ROOT_DIR . "/temp/cache/"))
            mkdir(_ROOT_DIR . "/temp/cache/", 0777);
        return _ROOT_DIR . "/temp/cache/";
    }

    public static function StoreFile($key, $value) {
        $folder = Cache::getStoreFileLocation();
        file_put_contents($folder . $key, $value);
    }

    /**
     * @param String $old type how long can be stored files old (+3 month)
     */
    public static function GetFile($key, $old) {
        $folder = Cache::getStoreFileLocation();
        $fileTime = strtotime($old, filemtime($folder . $key));        
        if(file_exists($folder . $key) && time() < $fileTime) {
            return file_get_contents($folder . $key);
        }
        return null;
    }

}