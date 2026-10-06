<?php

namespace App\Service\Scout;

use Psr\Http\Message\UriInterface;

class NonCrawlableUriException extends \Exception
{
    private UriInterface $uri;

    public function __construct(
        UriInterface $uri,
        string $message = 'Cannot scout %s: it is not a Web page',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf($message, $uri), $code, $previous);

        $this->uri = $uri;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
}
