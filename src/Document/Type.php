<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document]
class Type
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'ce type d\'ami doit contenir plus de caractères',
        maxMessage: 'le nom de ce type est trop long',
    )]
    private string $name;

    public function getId(): ?string
    {
        return $this->id;
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
}