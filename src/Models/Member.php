<?php

namespace Clicars\Models;

use Clicars\Interfaces\IMember;

class Member implements iMember
{
    private int $id;
    private int $age;
    private array $subordinates;
    private ?IMember $boss;

    public function __construct(int $id, int $age)
    {
        $this->id = $id;
        $this->age = $age;
        $this->boss = null;
        $this->subordinates = [];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function addSubordinate(IMember $subordinate): IMember
    {
        $this->subordinates[$subordinate->getId()] = $subordinate;

        return $this;
    }

    public function removeSubordinate(IMember $subordinate): ?IMember
    {
        unset($this->subordinates[$subordinate->getId()]);

        return $this;
    }

    public function getSubordinates(): array
    {
        return $this->subordinates;
    }

    public function getBoss(): ?IMember
    {
        return $this->boss;
    }

    public function setBoss(?IMember $boss): IMember
    {
        if ($boss) {
            $this->boss = $boss;
            $boss->addSubordinate($this);
        }

        return $this;
    }
}