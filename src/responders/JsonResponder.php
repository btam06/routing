<?php

namespace Hiraeth\Routing;

use stdClass;
use Exception;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

/**
 * {@inheritDoc}
 */
class JsonResponder implements Responder
{
	/**
	 * @var StreamFactory|null
	 */
	protected $streamFactory = NULL;


	/**
	 *
	 */
	public function __construct(StreamFactory $stream_factory)
	{
		$this->streamFactory = $stream_factory;
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(Resolver $resolver): Response
	{
		$result   = $resolver->getResult();
		$response = $resolver->getResponse();
		$content  = json_encode($result);

		if ($content) {
			$stream = $this->streamFactory->createStream($content);

			return $response
				->withStatus(200)
				->withBody($stream)
				->withHeader('Content-Type', 'application/json')
				->withHeader('Content-Length', (string) $stream->getSize())
			;
		}

		throw new Exception('Failed converting result to JSON');
	}


	/**
	 * {@inheritDoc}
	 */
	public function match(Resolver $resolver): bool
	{
		$result = $resolver->getResult();

		if (is_array($result)) {
			return TRUE;
		}

		if (is_object($result)) {
			if ($result instanceof JsonSerializable) {
				return TRUE;
			}

			if ($result instanceof stdClass) {
				return TRUE;
			}
		}

		return FALSE;
	}
}
