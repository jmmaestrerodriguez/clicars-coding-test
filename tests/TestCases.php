<?php

namespace Clicars\Tests;

use Clicars\Interfaces\IMafia;
use Clicars\Interfaces\IMember;
use Clicars\Models\Mafia;
use Clicars\Models\Member;
use PHPUnit\Framework\TestCase;

class TestCases extends TestCase
{
    /**
     * @return IMafia
     */
    private function populate(): IMafia
    {
        $id = 1;

        // Define godfather
        $boss1 = new Member($id++, 80);
        $mafia = new Mafia($boss1);

        // Set members in different levels
        $boss2 = $mafia->addMember((new Member($id++, 74))->setBoss($boss1));
        $mafia->addMember((new Member($id++, 70))->setBoss($boss1));
        $boss4 = $mafia->addMember((new Member($id++, 73))->setBoss($boss1));

        $boss5 = $mafia->addMember((new Member($id++, 68))->setBoss($boss2));
        $mafia->addMember((new Member($id++, 52))->setBoss($boss5));
        $mafia->addMember((new Member($id++, 64))->setBoss($boss2));
        $mafia->addMember((new Member($id++, 63))->setBoss($boss2));
        $mafia->addMember((new Member($id++, 65))->setBoss($boss2));

        $mafia->addMember((new Member($id++, 54))->setBoss($boss5));
        $mafia->addMember((new Member($id++, 56))->setBoss($boss5));

        $boss12 = $mafia->addMember((new Member($id++, 48))->setBoss($boss4));
        $mafia->addMember((new Member($id++, 61))->setBoss($boss12));
        $mafia->addMember((new Member($id++, 55))->setBoss($boss12));
        $mafia->addMember((new Member($id++, 69))->setBoss($boss12));

        return $mafia;
    }

    /**
     * Create the organization correctly and test getGodfather
     */
    public function testCreateMafia()
    {
        $mafia = $this->populate();

        // Test godfather
        $this->assertEquals(1, $mafia->getGodfather()->getId());
        $this->assertEquals(80, $mafia->getGodfather()->getAge());
    }

    /**
     * Print the graph info to view it more easily
     */
    public function testPrintGraph(): void
    {
        $mafia = $this->populate();
        foreach ($mafia->getMembers() as $member){
            $subordinates = [];
            foreach($member->getSubordinates() as $subordinate) {
                $subordinates[] = $subordinate->getId() ."";
            }
            echo("Miembro: " . $member->getId() . ", Jefe: " . $member->getBoss()?->getId() . ", Edad: ". $member->getAge() . ", Hijos: " . implode(", ", $subordinates) . "\n");
        }
        $this->assertEquals(true, true);
    }

    /**
     * Test getMember method
     */
    public function testGetMember()
    {
        $mafia = $this->populate();

        // Test a middle range member
        $member5 = $mafia->getMember(5);
        $this->assertInstanceOf(IMember::class, $member5);
        $this->assertEquals(68, $member5->getAge());
        $this->assertEquals($mafia->getMember(2), $member5->getBoss());
        $this->assertCount(3, $member5->getSubordinates());
    }

    /**
     * From a middle range member, test his boss and his subordinates get methods
     */
    public function testGetNearMembers()
    {
        $mafia = $this->populate();

        // Test a middle range member
        $member5 = $mafia->getMember(5);
        $this->assertEquals($mafia->getMember(2), $member5->getBoss());
        $subordinates = $member5->getSubordinates();
        $this->assertCount(3, $subordinates);
        $this->assertEquals($member5, array_pop($subordinates)->getBoss());
    }

    /**
     * Test send a member to the prison
     */
    public function testSendToPrison()
    {
        $mafia = $this->populate();

        // Send a middle range member to the prison
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(5)));

        // Check if the member is still in the organization
        $this->assertNull($mafia->getMember(5));

        // Check moved members
        $this->assertEquals(9, $mafia->getMember(10)->getBoss()->getId());
        $this->assertCount(3, $mafia->getMember(9)->getSubordinates());
    }

    /**
     * Test send a member to the prison
     */
    public function testSendToPrisonPromoted()
    {
        $mafia = $this->populate();

        // Send all the members in that level to the prison
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(5)));
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(7)));
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(9)));
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(8)));

        // Check moved members
        $this->assertEquals(11, $mafia->getMember(10)->getBoss()->getId());
        $this->assertEquals(11, $mafia->getMember(6)->getBoss()->getId());
        $this->assertEquals(2, $mafia->getMember(11)->getBoss()->getId());
        $this->assertCount(2, $mafia->getMember(11)->getSubordinates());
    }

    /**
     * Test send the godfather to the prison
     */
    public function testSendGodfatherToPrison()
    {
        $mafia = $this->populate();

        // Send godfather to the prison
        $this->assertTrue($mafia->sendToPrison($mafia->getMember(1)));

        // Check moved members
        $this->assertEquals(2, $mafia->getGodfather()->getId());
    }

    /**
     * Test release a member from the prison
     */
    public function testReleaseFromPrison()
    {
        $mafia = $this->populate();
        $member = $mafia->getMember(5);
        $mafia->sendToPrison($member);

        // Release him from prison
        $this->assertTrue($mafia->releaseFromPrison($member));
        $this->assertEquals($member, $mafia->getMember(5));

        // Check near members moved again
        $this->assertEquals($mafia->getMember(2), $member->getBoss());
        $subordinates = $member->getSubordinates();
        $this->assertCount(3, $subordinates);
        $this->assertEquals($member, array_pop($subordinates)->getBoss());
        $this->assertEquals(5, $mafia->getMember(10)->getBoss()->getId());
        $this->assertCount(0, $mafia->getMember(9)->getSubordinates());
    }

    /**
     * Test release a member from the prison
     */
    public function testReleaseFromPrisonPromoted()
    {
        $mafia = $this->populate();
        $member = $mafia->getMember(12);
        $mafia->sendToPrison($member);

        // Release him from prison
        $this->assertTrue($mafia->releaseFromPrison($member));
        $this->assertEquals($member, $mafia->getMember(12));

        // Check near members moved again
        $this->assertEquals($mafia->getMember(4), $member->getBoss());
        $subordinates = $member->getSubordinates();
        $this->assertCount(3, $subordinates);
        $this->assertEquals($member, array_pop($subordinates)->getBoss());
        $this->assertEquals(12, $mafia->getMember(15)->getBoss()->getId());
    }

    /**
     * Test find big bosses
     */
    public function testFindBigBosses()
    {
        $mafia = $this->populate();

        // Bosses with more than 4 subordinates
        $this->assertCount(2, $mafia->findBigBosses(4));
    }

    /**
     * Test find big bosses (default)
     */
    public function testFindBigBossesDefault()
    {
        $mafia = $this->populate();

        // Bosses with minimum 50 subordinates (method call with no params, default)
        $this->assertCount(0, $mafia->findBigBosses());
    }

    /**
     * Test compare members
     */
    public function testCompareMembers()
    {
        $mafia = $this->populate();

        // Compare two mafia members
        $memberA = $mafia->getMember(6);
        $memberB = $mafia->getMember(8);
        $this->assertEquals($memberB, $mafia->compareMembers($memberA, $memberB));
    }

    /**
     * Test compare two mafia members with same rank
     */
    public function testCompareEqualsMembers()
    {
        $mafia = $this->populate();

        // Compare two mafia members with same rank
        $memberA = $mafia->getMember(2);
        $memberB = $mafia->getMember(3);
        $this->assertNull($mafia->compareMembers($memberA, $memberB));
    }
}
