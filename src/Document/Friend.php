<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
//TODO: check assert
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections;
use App\Document\Type;
use App\Repository\FriendRepository;

#[MongoDB\Document(repositoryClass: FriendRepository::class)]
class Friend
{
    #[MongoDB\Id]
    #[Groups("get")]
    private string $id;

    /** @MongoDB\ReferenceOne(targetDocument=Type::class) */
    #[MongoDB\ReferenceOne(targetDocument: Type::class)]
    #[MongoDB\Field(type: 'id')]
    #[Assert\NotBlank]
    #[Groups("get")]
    private string $type;

    #[MongoDB\ReferenceOne(targetDocument: Type::class, storeAs: 'id')]
    #[MongoDB\Field(type: 'string')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'le nom de son ami doit être plus long',
        maxMessage: 'est ce vraiment un nom? C\'est trop long',
    )]
    #[Groups("get")]
    private string $name;

    #[MongoDB\Field(type: 'int')]
    #[Assert\NotBlank]
    #[Groups("get")]
    private int $value;

    #[MongoDB\Field(type: 'collection')]
    #[Groups("get")]
    private array $tags = [];

    #[MongoDB\Field(type: 'bool')]
    private bool $alive;

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
        $this->tags = $tags;

        return $this;
    }

    public function getAlive(): ?bool
    {
        return $this->alive;
    }

    public function setAlive(bool $alive): self
    {
        $this->alive = $alive;

        return $this;
    }
}