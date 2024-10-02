# This package is not maintained for the public

## Example uses

Create a service that listens for any incoming requests on the *user-service* queue.
```php
use EasyRabbit\RabbitRouter;
use EasyRabbit\Connection\RabbitMQ;
use EasyRabbit\Connection\RabbitConfig;
use EasyRabbit\Message\RabbitRequest;

$connection = new RabbitMQ(new RabbitConfig('172.17.0.1', 5576, 'guest', 'guest'));

$connection
	->queue('user-service')
	->listen(new RabbitRouter([
		// RabbitRouter takes an array of key-value pairs. The key is the route which will get matched when a
		// message is recieved from EasyRabbit\Connection\RabbitMQ in the format of a EasyRabbit\Message\RabbitRequest.
		// The value of the pair is the method or closure that will get executed. A message will get send back to a given
		// reply queue in case one is provided.
		'register-invoke' => MyRegisterUserHandler::class,
		'register-method' => [MyRegisterUserHandler::class, 'register'],
		'register-closure' => function(RabbitRequest $request): void {

			$user = createUser($request->data);

			sendWelcomeMail($user->email);
		},
	]));

$connection->close();
```

Creates a service that sends data to the *user-service* to create a user.
```php
use EasyRabbit\Connection\RabbitMQ;
use EasyRabbit\Connection\RabbitChannel;
use EasyRabbit\Connection\RabbitConfig;
use EasyRabbit\Message\RabbitReply;

$connection = new RabbitMQ(new RabbitConfig('172.17.0.1', 5576, 'guest', 'guest'));

$connection
	->queue('user-service')
	// Append extra data to the message.
	->with([
		'username' 	=> 'Monke',
		'email' 	=> 'test@test.nl',
	])
	// This parameter is the route which will be matched by the EasyRabbit\RabbitRouter when the message is received.
	->send('register-invoke');

$connection->close();
```

Create a service that listenens for any incoming requests on the *product-service* queue.
```php
use EasyRabbit\RabbitRouter;
use EasyRabbit\Connection\RabbitMQ;
use EasyRabbit\Connection\RabbitConfig;
use EasyRabbit\Message\RabbitRequest;

$connection = new RabbitMQ(new RabbitConfig('172.17.0.1', 5576, 'guest', 'guest'));

$connection
	->queue('product-service')
	->listen(new RabbitRouter([
		'get-product' => MyGetProductHandler::class,
	]));

$connection->close();
```

Creates a service that asks for a product with a specific uuid and waits for the response.
```php
use EasyRabbit\Connection\RabbitMQ;
use EasyRabbit\Connection\RabbitChannel;
use EasyRabbit\Connection\RabbitConfig;
use EasyRabbit\Message\RabbitReply;

$productUuid = '7d4ec64c-60dc-4d26-84bc-d95225322b94';

$connection = new RabbitMQ(new RabbitConfig('172.17.0.1', 5576, 'guest', 'guest'));

$connection
	->queue('product-service')
	// Set a reply callback that starts listening after a message has been sent to the set queue.
	->catchReply(function(RabbitChannel $channel, RabbitReply $reply): void {
		if($reply->code !== 200) {
			echo 'Error occured';
			return ;
		}

		echo "Product recieved: {$reply->data['name']}";
	})
	->with([
		'uuid' => $productUuid,
	])
	// The second parameter is a unique identifier that ensures a unique reply queue will be created.
	// This will guarantee that the reply callback, set previously, with the correct response data.
	// This unique queue will be deleted right after the reply was received.
	->send('get-product-method', $productUuid);

$connection->close();
```
