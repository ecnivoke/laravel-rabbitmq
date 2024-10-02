<?php 

namespace EasyRabbit\Connection;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitConfig
{
	public array $options;

	/**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * 
     * Valid Options include:
     * @param string $vhost
     * @param bool $insist
     * @param string $login_method
     * @param null $login_response @deprecated
     * @param string $locale
     * @param float $connection_timeout
     * @param float $read_write_timeout
     * @param resource|array|null $context
     * @param bool $keepalive
     * @param int $heartbeat
     * @param float $channel_rpc_timeout
     * @param string|\PhpAmqpLib\Connection\AMQPConnectionConfig|null $ssl_protocol @deprecated
     * @param \PhpAmqpLib\Connection\AMQPConnectionConfig|null $config
     * 
     * @throws \Exception
     */
	public function __construct(
		public string $host,
        public int $port,
        public string $user,
        public string $password,
        array $options = []
	) {
		if(!empty($options) && false !== $err = $this->getOptionErrors($options))
		{
			throw new \Exception('Invalid options given: '.implode(', ', $err));
		}

		$this->options = $options;
	}

	private function getOptionErrors(array $options): false|array
	{
        $reflection = new \ReflectionMethod(AMQPStreamConnection::class, '__construct');
        $parameters = array_slice($reflection->getParameters(), 4); // Skip the parameters given in our own constructor.
        $formatted  = [];
        $errors     = [];

        foreach($parameters as $i => $parameter)
        {
            $formatted[$parameter->name] = 0;
        }

        foreach($options as $key => $value)
        {
            if(@$formatted[$key] === null)
            {
                $errors[] = $key;
            }
        }

        if(!empty($errors)) return $errors;

		return false;
	}
}
