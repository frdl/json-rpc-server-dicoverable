<?php
declare(strict_types=1);

namespace frdlweb\Api\Rpc;


use frdlweb\Api\Rpc\MethodDiscoverableInterface;
use frdlweb\Api\Rpc\Server;
use stdClass;


class DiscoverMethod implements MethodDiscoverableInterface
{
	/*
				'$vars' => [
				         'subdomain' =>'domainundhomepagespeicher',
			        	 'host' =>'webfan.de',
			        	 'port' =>443,
			        	 'path' =>'/software-center/modules-api/rpc/0.0.2/',
				],
				
	     'variables' => [
				      'subdomain' => [
						    'default' => 'domainundhomepagespeicher',
						    'description' => 'Homepagesystem Workspace Subdomain',
						  ],

				      'host' => [
						    'default' => 'webfan.de',
						    'description' => 'API Provider Domain',
						  ],		
				       'port' => [
						      'default' => 443,
						   ],
					   
				       'path' => [
						      'default' => '/software-center/modules-api/rpc/0.0.2/',
						   ],			 
				  ],
				
				
				*/
    protected $server;
    protected $meta = [
		//'$schema' => 'https://raw.githubusercontent.com/open-rpc/meta-schema/master/schema.json#',
		'openrpc' => 'GENERATED FIELD: Do Not Edit',
		"info" => [
              "title" =>  "JSON-RPC Server",
              "description" =>  "This the RPC-part of an Frdlweb API Server definition https://look-up.webfan3.de/?goto=oid%3A1.3.6.1.4.1.37553.8.1.8.1.13878",
              "version" =>  "1.0.0",
        ],
		  'servers' => [
			[
		     'name' => 'Webfan Homepagesystem RPC API',
		     'summary' => 'Webfan Homepagesystem RPC API methods description',
		     'description' => 'This is the RPC part of an implementation of the Frdlweb API Specification (1.3.6.1.4.1.37553.8.1.8.1.13878)',
		  //   'url' => 'https://'.$_SERVER['SERVER_NAME'].'/software-center/modules-api/rpc/0.0.2/',
		    ]
			  
		  ],
		  'methods' => [],
		  'components' => [
			     'links' => [],
			     'contentDescriptors' => [],
			     'schemas' => [
				        //  'JSONRpcRequestParameter' => 
				 ],
			     'examples' => [],
			  
			  ],
		];
    protected $outputfile = null;
	protected $cacheTime;
	
	
	public function __construct(Server $server,string $outputfile = null, int $cacheTime = 300){
		$this->cacheTime = $cacheTime;
		$this->server = $server;
		$this->outputfile = ($outputfile) ? $outputfile : $_SERVER['DOCUMENT_ROOT'] . \DIRECTORY_SEPARATOR . 'openrpc.json';		
		
		$this->schema('JSONRpcRequestParameterSpec', ['$ref' => 'https://json-schema.org/draft-07/schema#']);
	}	

	
	public function getMeta(){
		
	     foreach($this->server->getMethodDefinitions() as $method_name => $serviceId){
			 
			
			 $procedure = $this->server->getContainer()->get($serviceId);
			 
			 $method = new \stdclass;
			 $method->name = $method_name;
			// $method->params = $procedure->getSpec();
			
			 
			 if($procedure instanceof MethodDiscoverableInterface){
				 $method->params = $procedure->getParametersSpec();
				 $method->result = $procedure->getResultSpec();
				 
				 
				 if($procedure->getSummary()){
					  $method->summary = $procedure->getSummary(); 
				 }
				 
				 if($procedure->getDescription()){
					  $method->description = $procedure->getDescription(); 
				 }				 
			
				 
				 
				 
				 $examples = $procedure->getExamples();
				 if($examples){
					$method->examples = $examples; 
				 }
			
				 $links = $procedure->getLinks();
				 if($links){
					$method->links = $links; 
				 }
				 
				 
				 $procedure->discover($this);
				 
			 }elseif($procedure instanceof  \UMA\JsonRpc\Procedure){
				 $method->params =[ 
					 /*
					 [
						 'name' => 'JSON-Result',
						 //$procedure->getSpec()
						 '$schema' => 'https://json-schema.org/draft-07/schema#',
					 ]
					 */
				  ];
				 $method->result = [
					         '$ref' => 'https://json-schema.org/draft-07/schema#',
					 ];
			 }else{
				throw new MetadataException('Procedure does not match an valid interface in '.__METHOD__); 
			 }
			 
			 $this->meta['methods'][] = $method;
		 }
		
		$this->meta['openrpc'] = 'GENERATED FIELD: Do Not Edit';
		$this->meta['components']['examples'] = (object)$this->meta['components']['examples'];
		$this->meta['components']['links'] = (object)$this->meta['components']['links'];
		$this->meta['components']['contentDescriptors'] = (object)$this->meta['components']['contentDescriptors'];
		$this->meta['components']['schemas'] = (object)$this->meta['components']['schemas'];

		return $this->meta;
	}
	
    /**
     * {@inheritdoc}
     */
    public function __invoke(\UMA\JsonRpc\Request $request): \UMA\JsonRpc\Response
    {
		$params = $request->params();


	
		
		try{
              return new \UMA\JsonRpc\Success($request->id(), $this->getMeta());					
		}catch(\Exception $e){
			return new \UMA\JsonRpc\Error($request->id(), sprintf('Error: `%s`', $e->getMessage()));
		}
    }


    public function getSpec(): ?\stdClass
    {
        return \json_decode('
{
 "$schema": "https://json-schema.org/draft-07/schema#",
  "type": ["null", "array", "object"],
  "properties": {

  },
  "required" : [],
  "additionalProperties": true
}
'
        );
    }
	
	public function getParametersSpec(): ?array
	{
	    return [];	
	}
	
	
	public function getExamples(): ?array
	{
		return [];
	}		
	
	public function getLinks(): ?array
	{
		return [];
	}	
	public function getSummary(): ?string
	{
		return 'Method as specified in https://spec.open-rpc.org/';
	}
	
	public function getDescription(): ?string
	{
		return "Returns an OpenRPC schema as a description of this service";
	}
	
    /**
     * {@inheritdoc}
     */	
	public function discover(DiscoverMethod $DiscoverMethod) : void {
		
	}
	

   public function getResultSpec(): ?\stdClass {
	        return \json_decode('
{
  "$ref": "https://raw.githubusercontent.com/open-rpc/meta-schema/master/schema.json"
}
'
        );		
		
   }
	
	

	
	public function descriptor($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta['components']['contentDescriptors'];	
		}elseif(1===count($args)){
			return $this->meta['components']['contentDescriptors'][$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta['components']['contentDescriptors'] = $args[1];
			}else{
			    $this->meta['components']['contentDescriptors'][$args[0]] = $args[1];
			}
		}
	}	
	
	public function example($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta['components']['examples'];	
		}elseif(1===count($args)){
			return $this->meta['components']['examples'][$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta['components']['examples'] = $args[1];
			}else{
			    $this->meta['components']['examples'][$args[0]] = $args[1];
			}
		}
	}		
	
	public function link($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta['components']['links'];	
		}elseif(1===count($args)){
			return $this->meta['components']['links'][$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta['components']['links'] = $args[1];
			}else{
			    $this->meta['components']['links'][$args[0]] = $args[1];
			}
		}

	}		
	
	public function info($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta['info'];	
		}elseif(1===count($args)){
			return $this->meta['info'][$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta['info'] = $args[1];
			}else{
			    $this->meta['info'][$args[0]] = $args[1];
			}
		}

	}	
	
	
	public function schema($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta['components']['schemas'];	
		}elseif(1===count($args)){
			return $this->meta['components']['schemas'][$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta['components']['schemas'] = $args[1];
			}else{
			    $this->meta['components']['schemas'][$args[0]] = $args[1];
			}
		}

	}	
	
	public function config($key = null, $value = null){
		$args = func_get_args();
		if(0===count($args)){
		   return $this->meta;	
		}elseif(1===count($args)){
			return $this->meta[$args[0]];
		}elseif(2===count($args)){
			if(null === $args[0]){
				$this->meta = $args[1];
			}else{
			    $this->meta[$args[0]] = $args[1];
			}
		}

	}
	
	

}
