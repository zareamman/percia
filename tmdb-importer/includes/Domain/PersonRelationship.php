<?php
namespace TMDBImporter\Domain;

class PersonRelationship
{
    private int $id = 0;
    private int $personTermId = 0;
    private string $objectType = '';
    private int $objectId = 0;
    private string $role = '';
    private ?string $characterName = null;
    private ?string $department = null;
    private int $creditOrder = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPersonTermId(): int
    {
        return $this->personTermId;
    }

    public function setPersonTermId(int $personTermId): self
    {
        $this->personTermId = $personTermId;
        return $this;
    }

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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }

    public function setCharacterName(?string $characterName): self
    {
        $this->characterName = $characterName;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getCreditOrder(): int
    {
        return $this->creditOrder;
    }

    public function setCreditOrder(int $creditOrder): self
    {
        $this->creditOrder = $creditOrder;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'person_term_id' => $this->personTermId,
            'object_type' => $this->objectType,
            'object_id' => $this->objectId,
            'role' => $this->role,
            'character_name' => $this->characterName,
            'department' => $this->department,
            'credit_order' => $this->creditOrder,
        ];
    }
}