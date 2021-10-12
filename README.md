# tolkam/throwable-handler

Allows converting php errors to exceptions and handle them consistently.

## Documentation

The code is rather self-explanatory and API is intended to be as simple as possible. Please, read the sources/Docblock if you have any questions. See [Usage](#usage) for quick start.

## Usage

````php
use Tolkam\ThrowableHandler\ThrowableHandler;

// create handler with optional log file path
$logTo = __DIR__ . '/error.log';
$handler = new ThrowableHandler($logTo);

// choose what type of errors to handle
$handler->catchErrors();
$handler->catchExceptions();
$handler->catchShutdown();

// expose errors details instead of generic message
$handler->exposeErrors();

throw new Exception('OMG!');
````

## License

Proprietary / Unlicensed 🤷
