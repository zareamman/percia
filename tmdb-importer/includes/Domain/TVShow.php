<?php
namespace TMDBImporter\Domain;

class TVShow
{
    private int $id = 0;
    private int $tmdbId = 0;
    private string $name = '';
    private string $originalName = '';
    private string $overview = '';
    private ?string $firstAirDate = null;
    private ?string $lastAirDate = null;
    private int $numberOfSeasons = 0;
    private int $numberOfEpisodes = 0;
    private float $rating = 0.0;
    private int $voteCount = 0;
    private ?string $posterPath = null;
    private ?string $backdropPath = null;
    private array $genreIds = [];
    private array $keywordIds = [];
    private array $networkIds = [];
    private array $personRelationships = [];
    private array $companyIds = [];
    private array $seasonNumbers = [];
    private string $status = 'draft';
    private ?string $originalLanguage = null;
    private ?float $popularity = null;
    private string $type = '';

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
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

    public function getFirstAirDate(): ?string
    {
        return $this->firstAirDate;
    }

    public function setFirstAirDate(?string $firstAirDate): self
    {
        $this->firstAirDate = $firstAirDate;
        return $this;
    }

    public function getLastAirDate(): ?string
    {
        return $this->lastAirDate;
    }

    public function setLastAirDate(?string $lastAirDate): self
    {
        $this->lastAirDate = $lastAirDate;
        return $this;
    }

    public function getNumberOfSeasons(): int
    {
        return $this->numberOfSeasons;
    }

    public function setNumberOfSeasons(int $numberOfSeasons): self
    {
        $this->numberOfSeasons = $numberOfSeasons;
        return $this;
    }

    public function getNumberOfEpisodes(): int
    {
        return $this->numberOfEpisodes;
    }

    public function setNumberOfEpisodes(int $numberOfEpisodes): self
    {
        $this->numberOfEpisodes = $numberOfEpisodes;
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

    public function getNetworkIds(): array
    {
        return $this->networkIds;
    }

    public function setNetworkIds(array $networkIds): self
    {
        $this->networkIds = $networkIds;
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

    public function getSeasonNumbers(): array
    {
        return $this->seasonNumbers;
    }

    public function setSeasonNumbers(array $seasonNumbers): self
    {
        $this->seasonNumbers = $seasonNumbers;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'ID' => $this->id,
            'post_title' => $this->name,
            'post_content' => $this->overview,
            'post_status' => $this->status,
            'post_type' => 'tmdb_tv_show',
        ];
    }
}