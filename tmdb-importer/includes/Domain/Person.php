<?php
namespace TMDBImporter\Domain;

class Person
{
    private int $id = 0;
    private int $termId = 0;
    private int $tmdbId = 0;
    private string $name = '';
    private ?string $profilePath = null;
    private ?string $biography = null;
    private ?string $homepage = null;
    private ?string $knownForDepartment = null;
    private ?float $popularity = null;
    private ?string $birthday = null;
    private ?string $placeOfBirth = null;
    private ?string $deathday = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

public function getTermId(): int
    {
        return $this->termId;
    }

    public function setTermId(int $termId): self
    {
        $this->termId = $termId;
        return $this;
    }

    public function setTermId(int $termId): self
    {
        $this->termId = $termId;
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

    public function getProfilePath(): ?string
    {
        return $this->profilePath;
    }

    public function setProfilePath(?string $profilePath): self
    {
        $this->profilePath = $profilePath;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): self
    {
        $this->biography = $biography;
        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): self
    {
        $this->homepage = $homepage;
        return $this;
    }

    public function getKnownForDepartment(): ?string
    {
        return $this->knownForDepartment;
    }

    public function setKnownForDepartment(?string $knownForDepartment): self
    {
        $this->knownForDepartment = $knownForDepartment;
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

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getPlaceOfBirth(): ?string
    {
        return $this->placeOfBirth;
    }

    public function setPlaceOfBirth(?string $placeOfBirth): self
    {
        $this->placeOfBirth = $placeOfBirth;
        return $this;
    }

    public function getDeathday(): ?string
    {
        return $this->deathday;
    }

    public function setDeathday(?string $deathday): self
    {
        $this->deathday = $deathday;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->termId,
            'tmdb_id' => $this->tmdbId,
            'name' => $this->name,
            'profile_path' => $this->profilePath,
            'biography' => $this->biography,
            'homepage' => $this->homepage,
            'known_for_department' => $this->knownForDepartment,
            'popularity' => $this->popularity,
            'birthday' => $this->birthday,
            'place_of_birth' => $this->placeOfBirth,
            'deathday' => $this->deathday,
        ];
    }
}