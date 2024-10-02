<?php 

namespace EasyRabbit\Message;

class RabbitReply extends RabbitPayload
{
	public function __construct(
		public int $code,
		public mixed $data,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'code' => $this->code,
			'data' => $this->data,
		];
	}
}
