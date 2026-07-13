<?php
namespace TMDBImporter\Infrastructure\TMDB\Exceptions;

use TMDBImporter\Core\Exceptions\TMDBException;

class TMDBRateLimitException extends TMDBException
{
    private int $retryAfter;

    public function __construct(string $message, int $retryAfter = 10, int $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}