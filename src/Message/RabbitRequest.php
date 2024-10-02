<?php 

namespace EasyRabbit\Message;

class RabbitRequest extends RabbitPayload
{
	public function __construct(
		public string $route,
		public mixed $data,
		public null|string $replyOnQueue = null,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'route' => $this->route,
			'data' 	=> $this->data,
			'replyOnQueue' => $this->replyOnQueue
		];
	}
}
