# json-rpc-server-dicoverable
extends `uma/json-rpc `(https://github.com/1ma/JsonRpc)

# Added:
  * `rpc.dicover` method
    https://spec.open-rpc.org/
  * Result validation
  * Remote schema loader

# Example
````php
$payload = '{"jsonrpc":"2.0","method":"rpc.discover","params":[],"id":1}';

// \frdl\i::c() returns an container instance
\frdl\i::c()->set( \webfan\hps\Api\RpcMethod\Test::class, function(\UMA\DIC\Container $c) {
    return new \webfan\hps\Api\RpcMethod\Test();
});		


$config = [
		'schemaLoaderPrefix' => '',
		'schemaLoaderDirs' => [],	
	//	'schemaCacheDir' => __DIR__.\DIRECTORY_SEPARATOR.'schema-store'.\DIRECTORY_SEPARATOR,			
		'schemaCacheDir' => sys_get_temp_dir() . \DIRECTORY_SEPARATOR . get_current_user(). \DIRECTORY_SEPARATOR . 'json-schema-store' . \DIRECTORY_SEPARATOR,
		'discovery' => 	true,
		'meta' => [
		  'openrpc' => '1.0.0-rc1',
		  "info" => [
              "title" => "JSON-RPC Server",
              "description" =>"This the RPC-part of an Frdlweb API Server definition https://look-up.webfan3.de/?goto=oid%3A1.3.6.1.4.1.37553.8.1.8.1.13878",
              "version" => "1.0.0",
          ],
		  'servers' => [
			[
		     'name' => 'Webfan Homepagesystem RPC API',
		     'summary' => 'Webfan Homepagesystem RPC API methods description',
		     'description' => 'This is the RPC part of an implementation of the Frdlweb API Specification (1.3.6.1.4.1.37553.8.1.8.1.13878)',
		     'url' => 'https://'.$_SERVER['SERVER_NAME'].'/software-center/modules-api/rpc/0.0.2/',
		    ]
			  
		  ],
		    'methods' => [],
		    'components' => [
			     'links' => [],
			     'contentDescriptors' => [],
			     'schemas' => [],
			     'examples' => [],
			  
			  ],
		 ],	
		];


	try{	
	    $server = new \frdlweb\Api\Rpc\Server(\frdl\i::c(), 50, $config, true);
		
	    $server->set('test', \webfan\hps\Api\RpcMethod\Test::class);
		
		$response = $server->run($payload);

	}catch(\Exception $e){
	  echo  $e->getMessage();	
	}



  echo print_r($response,true);
````
