<?php 

namespace EasyRabbit;

class RabbitRouter
{
	protected RabbitRequest $request;

	/**
	 * @param array $routes
	 */
	public function __construct(
		protected array $routes
	) {}

	/**
	 * Calls the appropriate route and sends the data in a
	 * reply if a reply queue is defined in the request.
	 * 
	 * @param RabbitChannel $channel
	 * @param RabbitRequest $request
	 * 
	 * @return bool 	Whether a reply was sent.
	 */
	public function __invoke(RabbitChannel $channel, RabbitRequest $request): bool
	{
		$this->request = $request;

		$code 		= 404;
		$result 	= null;

		if($callback = @$this->routes[$request->route])
		{
			[$code, $result] = $callback instanceof \Closure
				? $this->callClosure($callback)
				: $this->callMethod($callback);
		}

		// Send a reply if a queue is defined in the request.
		if($request->replyOnQueue)
		{
			$channel->reply($request->replyOnQueue)->with([
				'code' => $code,
				'data' => $result
			])->send('');

			return true;
		}

		return false;
	}

	private function callClosure(\Closure &$callback): array
	{
		$code 		= 200;
		$result 	= null;

		// Try to call the user defined method.
		try {
			$result = $callback($this->request);
		} catch (\Exception $e) {
			$code = 500;
		}

		return [$code, $result];
	}

	private function callMethod(string|array &$callback): array
	{
		$class 		= $callback;
		$method 	= '__invoke';

		$code 		= 200;
		$result 	= null;

		// Parse callback.
		if(is_array($callback))
		{
			[$class, $method] = $callback;
		}

		// Try to call the user defined method.
		try {
			$result = (new $class)->$method($this->request);
		} catch (\Exception $e) {
			$code = 500;
		}

		return [$code, $result];
	}
}
