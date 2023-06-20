<?php

namespace Clicars\Models;

use Clicars\Interfaces\IMafia;
use Clicars\Interfaces\IMember;

class Mafia implements IMafia
{
    private IMember $godfather;

    private array $members;

    public function __construct(IMember $godfather)
    {
        $this->godfather = $godfather;

        $this->members = [];

        $this->addMember($godfather);
    }

    public function getGodfather(): IMember
    {
        return $this->godfather;
    }

    public function setGodFather(IMember $godfather): IMember
    {
        $this->godfather = $godfather;

        return $godfather;
    }

    public function addMember(IMember $member): ?IMember
    {
        $this->members[$member->getId()] = $member;

        return $member;
    }

    public function deleteMember(IMember $member): ?IMember
    {
        unset($this->members[$member->getId()]);

        return $member;
    }

    public function getMember(int $id): ?IMember
    {
        return $this->members[$id] ?? null;

    }

    public function getMembers(): array
    {
        return $this->members;

    }

    public function sendToPrison(IMember $member): bool
    {
        // First, we send the member to prison.
        $this->deleteMember($member);

        // Then, delete the member from the boss subordinates.
        if (!is_null($member->getBoss())) {
            $member->getBoss()->removeSubordinate($member);
        }
        //Finally, we have to relocate subordinates.
        $this->relocateSubordinatesWhenEnterToPrison($member);
        
        return true;
    }

    private function relocateSubordinatesWhenEnterToPrison(IMember $member): void
    {
        $newBoss = null;
        // Starts with searching a possible boss on the boss subordinates. 
        if (!is_null($member->getBoss())) {
            foreach ($member->getBoss()->getSubordinates() as $possibleBoss) {
                if ((is_null($newBoss) || $possibleBoss->getAge() > $newBoss->getAge())) {
                    $newBoss = $possibleBoss;
                }
            }
        }
        // If null, we search a possible subordinate to be the new boss, and assign her as subordinate
        // to the boss of the imprisoned member.
        if (is_null($newBoss)) {

            foreach ($member->getSubordinates() as $possibleBoss) {
                if (is_null($newBoss) || $possibleBoss->getAge() > $newBoss->getAge()) {
                    $newBoss = $possibleBoss;
                }
            }

            if (!is_null($newBoss)) {
                $newBoss->setBoss($member->getBoss());
                if (is_null($member->getBoss())) {
                    $this->setGodFather($newBoss);
                }
            }
        }
        // Lastly, we set the new boss to the other subordinates.
        foreach ($member->getSubordinates() as $subordinate) {
            if($newBoss->getId() != $subordinate->getId()) {
                $subordinate->setBoss($newBoss);
            }
        }

    }

    public function releaseFromPrison(IMember $member): bool
    {
        // First of all, we add the member to the mafia.
        $this->addMember($member);

        // After that, we relocate the subordinates, undoing the previous relocation.
        $this->relocateSubordinatesWhenReleaseFromPrison($member);

        return true;
    }

    private function relocateSubordinatesWhenReleaseFromPrison(IMember $member): void
    {
        // Add the released member to the list of subordinates of his boss (if applicable).
        if (!is_null($member->getBoss())) {
            $member->getBoss()->addSubordinate($member);
        } else {
            $this->setGodFather($member);
        }

        // Now, iterate over the subordinates of the member, and for each subordinate, set his boss as the
        // member who has been released from prison (removing the subordinate from the list of the old boss).
        foreach ($member->getSubordinates() as $subordinate) {
            $subordinate->getBoss()?->removeSubordinate($subordinate);
            $this->getMember($subordinate->getId())->setBoss($member);
        }  

    }
    // We start from the premise that the godfather is not included in the search of big bosses.
    public function findBigBosses(int $requiredSubordinates=IMafia::MIN_SUBORDINATES_FOR_BIG_BOSS): array
    {
        $bigBosses = [];
        // Recursively, we find how many members are subordinates of the current member.
        foreach ($this->members as $member) {
            // Now, we discard the godfather of the mafia for the big bosses search.
            if($member != $this->getGodfather()) {
                $totalSubordinates = $this->countSubordinatesRecursive($member, count($member->getSubordinates()));
                if($totalSubordinates >= $requiredSubordinates) {
                    $bigBosses[] = $member;
                }
            }
        }

        return $bigBosses;
    }
    
    // This method is for recursively count how many members are subordinates of a member.
    private function countSubordinatesRecursive(IMember $member, int $totalSubordinates): int
    {
        foreach ($member->getSubordinates() as $subordinate) {
            $totalSubordinates = count($subordinate->getSubordinates()) + $this->countSubordinatesRecursive($subordinate, $totalSubordinates);
        }
        return $totalSubordinates;

    }

    // This method and his recursion pretends to find, given two members, the first who has no boss, which will be the 
    // member with highest rank.
    public function compareMembers(IMember $memberA, IMember $memberB): ?IMember
    {
        return $this->compareMembersRecursive($memberA, $memberA->getBoss(), $memberB, $memberB->getBoss());
    }

    private function compareMembersRecursive(IMember $rootA, IMember $memberA, IMember $rootB, IMember $memberB): ?IMember
    {
        if($memberA->getBoss() != null && $memberB->getBoss() != null){
            return $this->compareMembersRecursive($rootA, $memberA->getBoss(), $rootB, $memberB->getBoss());
        }else if($memberA->getBoss() != null && $memberB->getBoss() == null){
            return $rootB;
        }else if($memberA->getBoss() == null && $memberB->getBoss() != null){
            return $rootA;
        }else{
            return null;
        }
    }
}