<?php
declare(strict_types=1);

namespace frdlweb\Api\Rpc;

use Opis\JsonSchema\Loaders\File;
use Opis\JsonSchema\Schema;

class SchemaLoader extends File
{
    /** @var string[] */
    protected $dirs;
    /** @var string */
    protected $prefix;
    /** @var int */
    protected $prefixLength;
	
	protected $cacheDir;
    /**
     * File constructor.
     * @param string $prefix
     * @param string[] $dirs
     */
    public function __construct(string $prefix = '', array $dirs,string $cacheDir = null)
    {
		
		
        $this->dirs = $dirs;
        $this->prefix = $prefix;
        $this->prefixLength = strlen($prefix);
		if(!$cacheDir){
		   $cacheDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . get_current_user(). \DIRECTORY_SEPARATOR . 'json-schema-store' . \DIRECTORY_SEPARATOR;	
		}
		$this->cacheDir = $cacheDir;
		
    }
	
	
	public function prueCache(int $limit = 86400, $removeDir = false, $skipDotFiles = true){
		return \webfan\hps\patch\Fs::pruneDir($this->cacheDir, $limit, $skipDotFiles, $removeDir);
	}
	
	public function filepath($uri){
	   $path = str_replace(['http://', 'https://', '/'], ['','',\DIRECTORY_SEPARATOR], $uri);
	   return rtrim($this->cacheDir, \DIRECTORY_SEPARATOR.'/ ').\DIRECTORY_SEPARATOR.$path;	
	}
    /**
     * @inheritDoc
     */
    public function loadSchema(string $uri)
    {
        if (isset($this->schemas[$uri])) {
            return $this->schemas[$uri];
        }
        if ($this->prefixLength !== 0 && strpos($uri, $this->prefix) !== 0) {
            return null;
        }
        $path = substr($uri, $this->prefixLength);
		
		
        $schema = null;
		
		
		$cacheFile = $this->filepath($uri);
			
		
		if(file_exists($cacheFile) && is_file($cacheFile)){
               $schema = json_decode(file_get_contents($cacheFile), false);
               $schema = new Schema($schema, $uri);			
		}
		
	 if(null === $schema){	
        foreach ($this->dirs as $dir) {
            if (file_exists($dir . $path)) {
                $schema = json_decode(file_get_contents($dir . $path), false);
                $schema = new Schema($schema, $uri);
                break;
            }
        }
	 }
		
	
			 if(null === $schema){	
				
				 $c = file_get_contents($uri);
				 if(false!==$c){
					if(!is_dir(dirname($cacheFile))){
					   mkdir($cacheFile, 0755, true);	
					}
					file_put_contents($cacheFile, $c); 
                    $schema = json_decode($c, false);
                    $schema = new Schema($schema, $uri);					 
				 }
			 }
		
		
		

        $this->schemas[$uri] = $schema;
        return $schema;
    }
}

