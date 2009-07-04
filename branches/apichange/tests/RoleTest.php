<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

require_once('PHPTMAPITestCase.php');

/**
 * Role tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class RoleTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $tm = $this->topicMap;
    $parent = $this->createAssoc();
    $this->assertEquals(count($parent->getRoles()), 0, 
      'Expected new association to be created without roles!');
    $role = $parent->createRole($tm->createTopic(), $tm->createTopic());
    $this->assertEquals($parent->getId(), $role->getParent()->getId(), 
      'Unexpected parent after creation!');
    $this->assertEquals(count($parent->getRoles()), 1, 'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($parent->getRoles());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $role->remove();
    $this->assertEquals(count($parent->getRoles()), 0, 'Expected 0 roles after removal!');
  }
  
  public function testRolePlayerSetGet() {
    $tm = $this->topicMap;
    $assoc = $this->createAssoc();
    $this->assertEquals(count($assoc->getRoles()), 0, 
      'Expected new association to be created without roles!');
    $roleType = $tm->createTopic();
    $player = $tm->createTopic();
    $role = $assoc->createRole($roleType, $player);
    $this->assertEquals($roleType->getId(), $role->getType()->getId(), 
      'Unexpected role type!');
    $this->assertEquals($player->getId(), $role->getPlayer()->getId(), 
      'Unexpected role player!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $player2 = $tm->createTopic();
    $role->setPlayer($player2);
    $this->assertEquals($player2->getId(), $role->getPlayer()->getId(), 
      'Unexpected role player after setting another role player!');
    $ids = $this->getIdsOfConstructs($player2->getRolesPlayed());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed()), 0, 
      "'Player' should not play the role anymore!");
    $role->setPlayer($player);
    $this->assertEquals($player->getId(), $role->getPlayer()->getId(), 
      'Unexpected role player after setting the previous role player!');
  }
}
?>
