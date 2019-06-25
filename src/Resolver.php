<?php

namespace Hiraeth\Routing;

use Hiraeth\Application;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

/**
 *
 */
class Resolver implements ResolverInterface
{
	/**
	 *
	 */
	protected $adapters = array();


	/**
	 *
	 */
	protected $app = NULL;


	/**
	 *
	 */
	protected $parameters = array();


	/**
	 *
	 */
	protected $request = NULL;


	/**
	 *
	 */
	protected $responders = array();


	/**
	 *
	 */
	protected $response = NULL;


	/**
	 *
	 */
	protected $target = NULL;


	/**
	 *
	 */
	protected $result = NULL;


	/**
	 *
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}


	/**
	 *
	 */
	public function getRequest(): Request
	{
		return $this->request;
	}


	/**
	 *
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}


	/**
	 *
	 */
	public function getResult()
	{
		return $this->result;
	}


	/**
	 *
	 */
	public function getTarget()
	{
		return $this->target;
	}


	/**
	 * Resolve a target returned by `RouterInterface::match()` to a PSR-7 response
	 *
	 * @access public
	 * @param Request $request The server request that matched the target
	 * @param Response $response The response object to modify for return
	 * @param mixed $target The target to construct and/or run
	 * @return Response The PSR-7 response from running the target
	 */
	public function run(Request $request, Response $response, $target): Response
	{
		$this->parameters = [];
		$this->response   = $response;
		$this->request    = $request;
		$this->target     = $target;

		foreach ($this->adapters as $adapter) {
			$adapter = $this->app->get($adapter);

			if (!$adapter instanceof AdapterInterface) {
				throw new \RuntimeException(sprintf(
					'Configured adapter "%s" must implement Hiraeth\Routing\AdapterInterface',
					get_class($adapter)
				));
			}

			if (!$adapter->match($this)) {
				continue;
			}

			$this->result = $this->app->run($adapter($this), $this->parameters);
		}

		foreach ($this->responders as $responder) {
			$responder = $this->app->get($responder);

			if (!$responder instanceof ResponderInterface) {
				throw new \RuntimeException(sprintf(
					'Configured responder "%s" must implement Hiraeth\Routing\ResponderInterface',
					get_class($adapter)
				));
				//
			}

			if (!$responder->match($this)) {
				continue;
			}

			return $responder($this);
		}

		throw new RuntimeException(sprintf(
			'No registered responder matched the result, %s, returned from the current route.',
			!is_object($this->result)
				? var_export($this->result, TRUE)
				: get_class($this->result)
		));
	}


	/**
	 *
	 */
	public function setAdapters(array $adapters): ResolverInterface
	{
		$this->adapters = $adapters;

		return $this;
	}


	/**
	 *
	 */
	public function setParameters(array $parameters): ResolverInterface
	{
		foreach ($parameters as $parameter => $value) {
			$this->parameters[':' . $parameter] = $value;
		}

		return $this;
	}


	/**
	 *
	 */
	public function setResponders(array $responders): ResolverInterface
	{
		$this->responders = $responders;

		return $this;
	}
}
