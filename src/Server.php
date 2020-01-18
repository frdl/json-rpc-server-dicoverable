<?php

declare(strict_types=1);

namespace frdlweb\Api\Rpc;


use frdlweb\Api\Rpc\DiscoverMethod;
use frdlweb\Api\Rpc\MethodDiscoverableInterface;

use frdlweb\Api\Rpc\SchemaLoader;

use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;



use TypeError;
use UMA\JsonRpc\Error;
use UMA\JsonRpc\Request;
use UMA\JsonRpc\Response;
use UMA\JsonRpc\Internal\Assert;
use UMA\JsonRpc\Internal\Input;
use UMA\JsonRpc\Internal\MiddlewareStack;
use UMA\JsonRpc\Internal\Validator;
use UMA\JsonRpc\Procedure;
use Opis\JsonSchema\Validator as OpisValidator;


class Server /* extends \UMA\JsonRpc\Server */
{

	protected $config = [];
	
    /**
     * @var ContainerInterface
   */
    protected $container;
  
    /**
     * @var string[]
   */
    protected $methods;
  
    /**
     * @var string[]
    */
    protected $middlewares;
 
    /**
     * @var int|null
    */
    protected $batchLimit;
	 
    public function __construct(ContainerInterface $container, int $batchLimit = null, array $config = null, bool $discovery = true)
    {
		if(!is_array($config)){
		  $config = [];	
		}
		$this->config = array_merge([
		'schemaLoaderPrefix' => '',
		'schemaLoaderDirs' => [],	
	//	'schemaCacheDir' => __DIR__.\DIRECTORY_SEPARATOR.'schema-store'.\DIRECTORY_SEPARATOR,			
		'schemaCacheDir' => sys_get_temp_dir() . \DIRECTORY_SEPARATOR . get_current_user(). \DIRECTORY_SEPARATOR . 'json-schema-store' . \DIRECTORY_SEPARATOR,
		'discovery' => 	$discovery,
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
		], $config);
		
		/*
        parent::__construct($container, $batchLimit);
		*/
        $this->container = $container;
        $this->batchLimit = $batchLimit;
        $this->methods = [];
        $this->middlewares = [];		

		
		if(true === $this->config['discovery']){
			/* $this->setDiscovery(DiscoverMethod::class, [$this, 'discoveryFactory']); */
			
			    $callable = [$this, 'discoveryFactory'];
				$this->setDiscovery(DiscoverMethod::class,static function(ContainerInterface $c) use($callable){
					return call_user_func_array($callable, func_get_args());
				});
				
		}
    }	

   public function discoveryFactory(ContainerInterface $c) : MethodDiscoverableInterface{
          $DiscoverMethod = new DiscoverMethod($this);
          $DiscoverMethod->config(null, $this->config['meta']);
	   
	   
	   return $DiscoverMethod;
   }	


	
  public function setDiscovery($serviceId, callable $factory){
   if(!$this->getContainer()->has( $serviceId)){  
	 if(
		 $this->container instanceof \Di\CompiledContainer
		 || 'CompiledContainer' === basename(get_class($this->container))
	   
	   ) {
		 $this->getContainer()->set( $serviceId, call_user_func_array($factory, [$this->container]));		
	 }elseif($factory instanceof \closure || 'ContainerBuilder' === basename(get_class($this->container)) ){ 
	     $this->getContainer()->set( $serviceId, $factory);		  
	 }else{ 
	     //$this->getContainer()->set( $serviceId, $factory);		  
		  $this->getContainer()->set( $serviceId, call_user_func_array($factory, [$this->container]));	
	 }
	}
	  
 	 	
	  $this->set('rpc.discover', $serviceId);
/**/	  
	  return $this;
  }
	
  public function getMethodDefinitions(){
	 return $this->methods;  
  }
	
  public function getContainer():ContainerInterface{
	 return $this->container;  
  }
	
  public function getConfig(){
	 return $this->config;  
  }
	
	
	
    public function set(string $method, string $serviceId): Server
    {
        if (!$this->container->has($serviceId)) {
            throw new LogicException("Cannot find service '$serviceId' in the container");
        }

        $this->methods[$method] = $serviceId;

        return $this;
    }

    public function attach(string $serviceId): Server
    {
        if (!$this->container->has($serviceId)) {
            throw new LogicException("Cannot find service '$serviceId' in the container");
        }

        $this->middlewares[$serviceId] = null;

        return $this;
    }

    /**
     * @throws TypeError
     */
    public function run(string $raw): ?string
    {
	
		
        $input = Input::fromString($raw, true);

        if (!$input->parsable()) {
            return self::end(Error::parsing());
        }

        if ($input->isArray()) {
            if ($this->tooManyBatchRequests($input)) {
                return self::end(Error::tooManyBatchRequests($this->batchLimit));
            }

            return $this->batch($input);
        }

        return $this->single($input);
    }

    protected function batch(Input $input): ?string
    {
        \assert($input->isArray());

        $responses = [];
        foreach ($input->data() as $request) {
            $pseudoInput = Input::fromSafeData($request);

            if (null !== $response = $this->single($pseudoInput)) {
                $responses[] = $response;
            }
        }

        return empty($responses) ?
            null : \sprintf('[%s]', \implode(',', $responses));
    }

    /**
     * @throws TypeError
     */
    protected function single(Input $input): ?string
    {
        if (!$input->isRpcRequest()) {
            return self::end(Error::invalidRequest());
        }

        $request = new Request($input);

        if (!\array_key_exists($request->method(), $this->methods)) {
            return self::end(Error::unknownMethod($request->id()), $request);
        }

        try {
            $procedure = Assert::isProcedure(
                $this->container->get($this->methods[$request->method()])
            );
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            return self::end(Error::internal($request->id()), $request);
        }

		
		
        if (!Validator::validate($procedure->getSpec(), $request->params())) {
            return self::end(Error::invalidParams($request->id()), $request);
        }

        $stack = MiddlewareStack::compose(
            $procedure,
            ...\array_map(function(string $serviceId) {
                return $this->container->get($serviceId);
            }, \array_keys($this->middlewares))
        );

		
		
        return self::end($stack($request), $request, $procedure, $this);
    }

    protected function tooManyBatchRequests(Input $input): bool
    {
        \assert($input->isArray());

        return \is_int($this->batchLimit) && $this->batchLimit < \count($input->data());
    }

    protected static function end(Response $response, Request $request = null, Procedure $procedure = null, Server $Server = null): ?string
    {
		if( $procedure && true !== $response instanceof Error && $procedure instanceof MethodDiscoverableInterface){
			
		   $spec = 	$procedure->getResultSpec();
				
			$result = json_decode(json_encode($response));

           if (!self::validateResponse($validation, $spec, $result->result, $Server)) {
			   $ea=$validation->getFirstError()->errorArgs();
              return self::end(new Error($request->id(), 'Invalid result '.print_r($ea,true),  $result->result), $request); 
           }				
		}
		
		//$spec =  $this->container->get($this->methods[$request->method()])->getResultSpec();
        //if (true !== $response instanceof Error && !Validator::validate( $this->container->get($this->methods[$request->method()])->getResultSpec(), $request->params())) {
         //   return self::end(Error::invalidParams($request->id()), $request);
      //  }		
		
        return $request instanceof Request && null === $request->id() ?
            null : \json_encode($response);
    }	
	
	
	
    public static function validateResponse(&$validation = null, \stdClass $schema, $data, Server $Server = null): bool
    {
		
		
        \assert(false !== \json_encode($data));

		
		
		if(null!==$Server){
		  $config =$Server->getConfig();	
		}else{
			$config = [
				        'schemaLoaderPrefix' => 'https://json-schema.org',
		                'schemaLoaderDirs' => [],	
	                 	'schemaCacheDir' =>sys_get_temp_dir() . \DIRECTORY_SEPARATOR . get_current_user(). \DIRECTORY_SEPARATOR . 'json-schema-store' . \DIRECTORY_SEPARATOR,	
				];
		}
	
        $validation = (new OpisValidator)
			->setLoader(new SchemaLoader($config['schemaLoaderPrefix'],
									 $config['schemaLoaderDirs'], 
									 $config['schemaCacheDir']))
		
            ->dataValidation($data, $schema);
		
		
	
		return $validation->isValid();
    }
	
}
