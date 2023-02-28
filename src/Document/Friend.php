<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
//TODO: check assert
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections;
use App\Document\Type;
use App\Repository\FriendRepository;

#[MongoDB\Document(repositoryClass: FriendRepository::class)]
class Friend
{
    #[MongoDB\Id]
    private string $id;

    /** @MongoDB\ReferenceOne(targetDocument=Type::class) */
    #[MongoDB\Field(type: 'id')]
    #[Assert\NotBlank]
    private string $type;

    #[MongoDB\ReferenceOne(targetDocument: Type::class, storeAs: 'id')]
    #[MongoDB\Field(type: 'string')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'le nom de son ami doit être plus long',
        maxMessage: 'est ce vraiment un nom? C\'est trop long',
    )]
    private string $name;

    #[MongoDB\Field(type: 'int')]
    #[Assert\NotBlank]
    private int $value;

    #[MongoDB\Field(type: 'collection')]
    private array $tags = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        //TODO: gotta check string exception
        $this->tags = $tags;

        return $this;
    }
}