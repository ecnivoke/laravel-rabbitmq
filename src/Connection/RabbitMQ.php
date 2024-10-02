<?php 

namespace EasyRabbit\Connection;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ
{
	protected AMQPStreamConnection $connection;

	/**
	 * @property RabbitChannel $channel
	 * Channel id has to be between 1 to 65535.
	 */
	protected RabbitChannel $channel;

	/**
	 * @property string $data
	 * Data to be send over the queue. It will be json serilized before being send.
	 */
	protected string $data;

	/**
	 * @property string $queue
	 * Name of the queue to use.
	 */
	protected string $queue;

	private self $reply;
	private \Closure $replyCallback;

	private \Closure|RabbitRouter $consumerCallback;

	public function __construct(
		protected RabbitConfig $config,
		public bool $isReply = false
	) {
		$connection 		= new AMQPStreamConnection(
			$this->config->host,
	        $this->config->port,
	        $this->config->user,
	        $this->config->password,
	        ...$this->config->options
		);

		$this->connection 	= $connection;
		$this->channel 		= new RabbitChannel($connection);
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Set the queue name that should be used.
	 */
	public function queue(string $queue): self
	{
		$this->queue = $queue;
		$this->channel->queue_declare($queue, auto_delete: false);

		return $this;
	}

	/**
	 * Listen for a message.
	 */
	public function listen(\Closure|RabbitRouter $callback): void
	{
		$this->consumerCallback = $callback;
		$this->channel->basic_consume($this->queue, no_ack: true, callback: [$this, 'callback']);

		$this->channel->consume();
	}

	/**
	 * Stop consuming after a single message has been recieved.
	 */
	public function listenOnce(\Closure $callback): void
	{
		$this->listen(function(RabbitChannel $channel, ...$params) use ($callback) {
			$callback($channel, ...$params);
			$channel->stopConsume();
		});
	}

	/**
	 * Will catch any replies recieved after sending a message.
	 */
	public function catchReply(\Closure $callback): self
	{
		$this->reply 			= new self(true);
		$this->replyCallback 	= $callback;

		return $this;
	}

	/**
	 * Set the data to be send over the queue.
	 * 
	 * @param mixed $data
	 */
	public function with(mixed $data): self
	{
		$this->data = json_encode($data, JSON_THROW_ON_ERROR);

		return $this;
	}

	/**
	 * Sends the message to the queue.
	 * 
	 * @param string $route
	 * @param string $id 		The unique identifier of this queue.
	 */
	public function send(string $route, string $id = ''): void
	{
		if(!isset($this->queue))
		{
			throw new \Exception('Queue not defined.');
		}

		$replyQueue = isset($this->reply) ? "{$this->queue}_$route$id" : null;
		$data 		= !$this->isReply
			// If the data send is not going to be a reply, we wrap it in a RabbitRequest instance.
			? json_encode(new RabbitRequest($route, $this->data, $replyQueue))
			: $this->data;

		$this->channel->basic_publish(
			new AMQPMessage($data),
			'',
			$this->queue
		);

		if($replyQueue)
		{
			$this->reply->queue($replyQueue);
			$this->reply->listenOnce($this->replyCallback);
			$this->reply->channel->queue_delete($this->reply->queue, if_empty: true); // Delete the temporary reply queue.
		}
	}

	/**
	 * Close the channel and connection.
	 */
	public function close(): void
	{
		$this->channel->close();
		$this->connection->close();
	}

	/**
	 * The method to be called by RabbitMQ's basic_consume.
	 * 
	 * This method is not intended to be called manually.
	 */
	public function callback(AMQPMessage $message): void
	{
		$data = json_decode($message->body, 1);

		$payload = $this->isReply
			? new RabbitReply($data['code'], @$data['data'])
			: new RabbitRequest($data['route'], @$data['data'], @$data['replyOnQueue']);

		($this->consumerCallback)($this->channel, $payload);
	}
}
