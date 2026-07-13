<?php
namespace TMDBImporter\Domain;

class Season
{
    private int $id = 0;
    private int $tmdbId = 0;
    private int $showId = 0;
    private int $seasonNumber = 0;
    private string $name = '';
    private string $overview = '';
    private ?string $posterPath = null;
    private ?string $airDate = null;
    private int $episodeCount = 0;
    private array $episodes = [];

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

    public function getShowId(): int
    {
        return $this->showId;
    }

    public function setShowId(int $showId): self
    {
        $this->showId = $showId;
        return $this;
    }

    public function getSeasonNumber(): int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): self
    {
        $this->seasonNumber = $seasonNumber;
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

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): self
    {
        $this->posterPath = $posterPath;
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

    public function getEpisodeCount(): int
    {
        return $this->episodeCount;
    }

    public function setEpisodeCount(int $episodeCount): self
    {
        $this->episodeCount = $episodeCount;
        return $this;
    }

    public function getEpisodes(): array
    {
        return $this->episodes;
    }

    public function setEpisodes(array $episodes): self
    {
        $this->episodes = $episodes;
        $this->episodeCount = count($episodes);
        return $this;
    }

    public function addEpisode(Episode $episode): self
    {
        $this->episodes[] = $episode;
        $this->episodeCount = count($this->episodes);
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdbId,
            'show_id' => $this->showId,
            'season_number' => $this->seasonNumber,
            'name' => $this->name,
            'overview' => $this->overview,
            'poster_path' => $this->posterPath,
            'air_date' => $this->airDate,
            'episode_count' => $this->episodeCount,
        ];
    }
}