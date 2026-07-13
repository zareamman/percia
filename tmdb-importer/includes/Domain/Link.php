<?php
namespace TMDBImporter\Domain;

class Link
{
    private string $objectType = '';
    private int $objectId = 0;
    private string $server = '';
    private string $language = '';
    private string $quality = '';
    private string $url = '';

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function setObjectType(string $objectType): self
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function setObjectId(int $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function setServer(string $server): self
    {
        $this->server = $server;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getQuality(): string
    {
        return $this->quality;
    }

    public function setQuality(string $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'object_type' => $this->objectType,
            'object_id' => $this->objectId,
            'server' => $this->server,
            'language' => $this->language,
            'quality' => $this->quality,
            'url' => $this->url,
        ];
    }
}