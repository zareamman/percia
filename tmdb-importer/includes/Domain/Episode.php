<?php
namespace TMDBImporter\Domain;

class Episode
{
    private int $id = 0;
    private int $tmdbId = 0;
    private int $seasonId = 0;
    private int $episodeNumber = 0;
    private string $name = '';
    private string $overview = '';
    private ?int $runtime = null;
    private ?string $airDate = null;
    private ?string $stillPath = null;
    private float $rating = 0.0;
    private int $voteCount = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTmdbId(): int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getSeasonId(): int
    {
        return $this->seasonId;
    }

    public function setSeasonId(int $seasonId): self
    {
        $this->seasonId = $seasonId;
        return $this;
    }

    public function getEpisodeNumber(): int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): self
    {
        $this->episodeNumber = $episodeNumber;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getOverview(): string
    {
        return $this->overview;
    }

    public function setOverview(string $overview): self
    {
        $this->overview = $overview;
        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): self
    {
        $this->runtime = $runtime;
        return $this;
    }

    public function getAirDate(): ?string
    {
        return $this->airDate;
    }

    public function setAirDate(?string $airDate): self
    {
        $this->airDate = $airDate;
        return $this;
    }

    public function getStillPath(): ?string
    {
        return $this->stillPath;
    }

    public function setStillPath(?string $stillPath): self
    {
        $this->stillPath = $stillPath;
        return $this;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }

    public function setVoteCount(int $voteCount): self
    {
        $this->voteCount = $voteCount;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdbId,
            'season_id' => $this->seasonId,
            'episode_number' => $this->episodeNumber,
            'name' => $this->name,
            'overview' => $this->overview,
            'runtime' => $this->runtime,
            'air_date' => $this->airDate,
            'still_path' => $this->stillPath,
            'rating' => $this->rating,
            'vote_count' => $this->voteCount,
        ];
    }
}