<?php
namespace TMDBImporter\Infrastructure\TMDB\Exceptions;

use TMDBImporter\Core\Exceptions\TMDBException;

class TMDBNotFoundException extends TMDBException
{
    private int $tmdbId;
    private string $type;

    public function __construct(string $type, int $tmdbId, int $code = 404, \Throwable $previous = null)
    {
        $message = ucfirst($type) . " with TMDB ID {$tmdbId} not found";
        parent::__construct($message, $code, $previous);
        $this->tmdbId = $tmdbId;
        $this->type = $type;
    }

    public function getTmdbId(): int
    {
        return $this->tmdbId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}