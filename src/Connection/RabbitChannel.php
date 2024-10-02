<?php 

namespace EasyRabbit\Connection;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitChannel extends AMQPChannel
{
	/**
	 * @param \RabbitMQ $connection
	 */
	public function __construct(
		AMQPStreamConnection $connection
	) {
		parent::__construct($connection);
	}

	public function reply(string $queue): RabbitMQ
	{
		$instance = new RabbitMQ(true);
		$instance->queue($queue);

		return $instance;
	}
}
