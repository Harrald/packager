<?php

require dirname(__FILE__) . '/Packager.php';

class Source
{
	const DESCRIPTOR_REGEX = '/\/\*\s*^---(.*?)^(?:\.\.\.|---)\s*\*\//ms';
	
	protected $code = '';
	protected $provides = array();
	protected $requires = array(); 
	
	function __construct($package_name, $source_path = '')
	{
		$this->package_name = $package_name;
		
		if ($source_path){
			$this->path = $source_path;
			$this->parse($source_path);
		}
	}
	
	static function parse_name($default, $name){
		$exploded = explode('/', $name);
		if (count($exploded) == 1) return array($default, $exploded[0]);
		if (empty($exploded[0])) return array($default, $exploded[1]);
		return array($exploded[0], $exploded[1]);
	}
	
	public function get_name()
	{
		if (!$this->name) $this->name = basename($this->path, '.js');
		return $this->name;
	}
	
	public function get_code()
	{
		return $this->code;
	}
	
	public function parse($source_path = '')
	{
		if ($source_path) $this->code = file_get_contents($source_path);
		
		if (!$this->code) throw new RuntimeException('Missing the code to parse. Did you forget to supply the source_path or set_code?');
		
		preg_match(self::DESCRIPTOR_REGEX, $this->code, $matches);
		if (empty($matches)) throw new Exception("No yaml header present in $source_path");
		
		$header = YAML::decode($matches[0]);
		foreach($header as $key => $value){
			$method = 'parse_' . strtolower($key);
			if (is_callable(array($this, $method))) $this->$method($value);
		}
	}
	
	public function parse_name($name)
	{
		$this->name = $name;
	}
	
	public function parse_provides($provides)
	{
		$provides = (array) $provides;
		$this->provides($provides);
	}
	
	public function parse_requires($requires)
	{
		$requires = (array) $requires;
		foreach ($requires as $i => $require) $require[$i] = implode('/', self::parse_name($this->package_name, $require));
		$this->requires($requires);
	}
	
	public function provides($provides)
	{
		$packager = Packager::get_instance();
		foreach ($provides as $component){
			$packager->add_component($source, $component);
			$this->provides[] = $component;
		}
		return $this;
	}
	
	public function requires($requires)
	{
		$packager = Packager::get_instance();
		foreach ($requires as $component){
			$packager->add_dependency($source, $component);
			$this->requires[] = $component;
		}
		return $this;
	}
	
	public function set_name($name)
	{
		$this->name = $name;
		return $this;
	}
	
	public function set_code($code)
	{
		$this->code = $code;
		return $this;
	}
}