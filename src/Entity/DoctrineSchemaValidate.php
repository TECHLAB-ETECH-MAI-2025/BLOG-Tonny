<?php

namespace App\Entity;

use App\Repository\DoctrineSchemaValidateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineSchemaValidateRepository::class)]
class DoctrineSchemaValidate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
