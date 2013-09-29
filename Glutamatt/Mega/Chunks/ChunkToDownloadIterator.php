<?php

namespace Glutamatt\Mega\Chunks ;

class ChunkToDownloadIterator implements \Iterator
{
	protected static $path = null ;
	protected static $isPrepared = false ;
	protected $current = 0;
	protected static $valid = true ;
	protected $batch_id = true ;

	const EXT = '.todl' ;
	const EXT_CUR = '.indl' ;

	public static function prepare($data, $path)
	{
		self::$path = $path ;
		@mkdir(self::$path, 0777, true) ;
		foreach ($data as $k => $v)
			self::add_value_file($k, $v) ;
		self::$isPrepared = true ;
	}

	public static function add_value_file($key , $value )
	{
		if(!self::$valid) return false ;
		$tmp = self::$path . uniqid() ;
		file_put_contents($tmp , serialize($value)) ;
		return @rename($tmp, self::$path . $key . self::EXT ) ;
	}

	public function __construct($batch_id = null )
	{
		if(!self::$isPrepared) throw new \Exception("call ChunkToDownloadIterator::prepare before instanciate ! ") ;
		$this->batch_id = $batch_id ;
		$this->next() ;
	}

	public function current () {
		return $this->valid()?unserialize(file_get_contents($this->current)):null;
	}

	public function next () {
		$loop = 0 ;
		while ($loop < 10)
		{
			usleep(rand(100, 200) * 1000 * ($loop+1));
			$loop++ ;
			$files = glob(self::$path . "*" . self::EXT) ;
			natsort($files);
			$file = array_shift($files) ;
			if($file && is_readable($file) && @rename($file, $file . self::EXT_CUR))
				return $this->current = $file . self::EXT_CUR ;
		}
		self::$valid = false ;
	}

	public function key () {
		return basename($this->current, self::EXT . self::EXT_CUR ) ;
	}

	public function valid () {
		return self::$valid ;
	}

	public function rewind () {}
}