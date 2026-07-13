<?php
namespace TMDBImporter\Domain;

class Movie
{
    private int $id = 0;
    private int $tmdbId = 0;
    private string $title = '';
    private string $originalTitle = '';
    private string $overview = '';
    private ?string $releaseDate = null;
    private ?int $runtime = null;
    private float $rating = 0.0;
    private int $voteCount = 0;
    private ?string $posterPath = null;
    private ?string $backdropPath = null;
    private array $genreIds = [];
    private array $keywordIds = [];
    private array $personRelationships = [];
    private array $companyIds = [];
    private array $videoIds = [];
    private string $status = 'draft';
    private ?string $originalLanguage = null;
    private ?float $popularity = null;

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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOriginalTitle(): string
    {
        return $this->originalTitle;
    }

    public function setOriginalTitle(string $originalTitle): self
    {
        $this->originalTitle = $originalTitle;
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

    public function getReleaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?string $releaseDate): self
    {
        $this->releaseDate = $releaseDate;
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

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): self
    {
        $this->posterPath = $posterPath;
        return $this;
    }

    public function getBackdropPath(): ?string
    {
        return $this->backdropPath;
    }

    public function setBackdropPath(?string $backdropPath): self
    {
        $this->backdropPath = $backdropPath;
        return $this;
    }

    public function getGenreIds(): array
    {
        return $this->genreIds;
    }

    public function setGenreIds(array $genreIds): self
    {
        $this->genreIds = $genreIds;
        return $this;
    }

    public function getKeywordIds(): array
    {
        return $this->keywordIds;
    }

    public function setKeywordIds(array $keywordIds): self
    {
        $this->keywordIds = $keywordIds;
        return $this;
    }

    public function getPersonRelationships(): array
    {
        return $this->personRelationships;
    }

    public function setPersonRelationships(array $personRelationships): self
    {
        $this->personRelationships = $personRelationships;
        return $this;
    }

    public function getCompanyIds(): array
    {
        return $this->companyIds;
    }

    public function setCompanyIds(array $companyIds): self
    {
        $this->companyIds = $companyIds;
        return $this;
    }

    public function getVideoIds(): array
    {
        return $this->videoIds;
    }

    public function setVideoIds(array $videoIds): self
    {
        $this->videoIds = $videoIds;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->originalLanguage;
    }

    public function setOriginalLanguage(?string $originalLanguage): self
    {
        $this->originalLanguage = $originalLanguage;
        return $this;
    }

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): self
    {
        $this->popularity = $popularity;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'ID' => $this->id,
            'post_title' => $this->title,
            'post_content' => $this->overview,
            'post_status' => $this->status,
            'post_type' => 'tmdb_movie',
        ];
    }
}