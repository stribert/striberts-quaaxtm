<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Association tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class AssociationTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $parent = $this->topicMap;
    $this->assertTrue(count($parent->getAssociations()) == 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $parent->createAssociation($parent->createTopic());
    $this->assertEquals($parent, $assoc->getParent(), 
      'Unexpected association parent after creation!');
    $this->assertTrue(count($parent->getAssociations()) == 1, 
      'Unexpected number of associations in topic map!');
    $assocs = $parent->getAssociations();
    $ids = $this->getIdsOfConstructs($assocs);
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $assoc->remove();
    $this->assertTrue(count($parent->getAssociations()) == 0, 
      'Expected no association in topic map after removal!');
    $assoc1 = $parent->createAssociation($parent->createTopic());
    $assoc2 = $parent->createAssociation($parent->createTopic());
    $this->assertTrue(count($parent->getAssociations()) == 2, 
      'Unexpected number of associations in topic map!');
    $assocs = $parent->getAssociations();
    $ids = $this->getIdsOfConstructs($assocs);
    $this->assertTrue(in_array($assoc1->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertTrue(in_array($assoc2->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $assoc1->remove();
    $this->assertTrue(count($parent->getAssociations()) == 1, 
      'Unexpected number of associations in topic map after removal!');
  }
  
  public function testRoleCreation() {
    $tm = $this->topicMap;
    $this->assertTrue(count($tm->getAssociations()) == 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($tm->createTopic());
    $this->assertTrue(count($assoc->getRoles()) == 0, 
      'Expected new association to be created without roles!');
    $roleType = $tm->createTopic();
    $player = $tm->createTopic();
    $this->assertEquals(0, count($player->getRolesPlayed()), 
      'Expected number of roles played to be 0 for newly created topic!');
    $role = $assoc->createRole($roleType, $player);
    $this->assertEquals($roleType, $role->getType(), 
      'Unexpected role type!');
    $this->assertEquals($player, $role->getPlayer(), 
      'Unexpected role player!');
    $this->assertEquals(1, count($player->getRolesPlayed()), 
      'Expected number of roles played to be 1 for topic!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
  }
  
  public function testRoleTypes() {
    $tm = $this->topicMap;
    $this->assertTrue(count($tm->getAssociations()) == 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($tm->createTopic());
    $this->assertTrue(count($assoc->getRoles()) == 0, 
      'Expected new association to be created without roles!');
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $this->assertTrue(count($assoc->getRoleTypes()) == 0, 
      'Expected new association to be created without role types!');
    $role1 = $assoc->createRole($type1, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoleTypes()) == 1, 
      'Expected 1 role type!');
    $ids = $this->getIdsOfConstructs($assoc->getRoleTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $role2 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoleTypes()) == 2, 
      'Expected 2 role types!');
    $ids = $this->getIdsOfConstructs($assoc->getRoleTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $this->assertTrue(in_array($type2->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $role3 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoleTypes()) == 2, 
      'Expected 2 role types!');
    $ids = $this->getIdsOfConstructs($assoc->getRoleTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $this->assertTrue(in_array($type2->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $role3->remove();
    $this->assertTrue(count($assoc->getRoleTypes()) == 2, 
      'Expected 2 role types!');
    $ids = $this->getIdsOfConstructs($assoc->getRoleTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $this->assertTrue(in_array($type2->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $role2->remove();
    $this->assertTrue(count($assoc->getRoleTypes()) == 1, 
      'Expected 1 role type!');
    $ids = $this->getIdsOfConstructs($assoc->getRoleTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $this->assertFalse(in_array($type2->getId(), $ids, true), 
      'Role type is not part of getRoleTypes()!');
    $role1->remove();
    $this->assertTrue(count($assoc->getRoleTypes()) == 0, 
      'Expected 0 role types!');
  }
  
  public function testRoleFilter() {
    $tm = $this->topicMap;
    $this->assertTrue(count($tm->getAssociations()) == 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($tm->createTopic());
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $unusedType = $tm->createTopic();
    $this->assertTrue(count($assoc->getRoles($type1)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRoles($type2)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRoles($unusedType)) == 0, 
      'Expected 0 roles!');
    $role1 = $assoc->createRole($type1, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoles($type1)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRoles($type1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $this->assertTrue(count($assoc->getRoles($type2)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRoles($unusedType)) == 0, 
      'Expected 0 roles!');
    $role2 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoles($type2)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRoles($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $role3 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRoles($type2)) == 2, 
      'Expected 2 roles!');
    $ids = $this->getIdsOfConstructs($assoc->getRoles($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $this->assertTrue(in_array($role3->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $this->assertTrue(count($assoc->getRoles($unusedType)) == 0, 
      'Expected 0 roles!');
    $role3->remove();
    $this->assertTrue(count($assoc->getRoles($type2)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRoles($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRoles()!');
    $role2->remove();
    $this->assertTrue(count($assoc->getRoles($type2)) == 0, 
      'Expected 0 roles!');
    $role1->remove();
    $this->assertTrue(count($assoc->getRoles($type1)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRoles($unusedType)) == 0, 
      'Expected 0 roles!');
  }
  
  public function testDuplicates() {
    $tm = $this->topicMap;
    $player = $tm->createTopic();
    $assocType = $tm->createTopic();
    $roleType1 = $tm->createTopic();
    $roleType2 = $tm->createTopic();
    
    $assoc = $tm->createAssociation($assocType);
    $role1 = $assoc->createRole($roleType1, $player);
    $role2 = $assoc->createRole($roleType2, $player);
    
    $assocDupl = $tm->createAssociation($assocType);
    $role1 = $assocDupl->createRole($roleType1, $player);
    $role2 = $assocDupl->createRole($roleType2, $player);
    
    $rolesPlayed = $player->getRolesPlayed();
    $this->assertEquals(count($rolesPlayed), 2);
    $rolesPlayed = $player->getRolesPlayed(null, $assocType);
    $this->assertEquals(count($rolesPlayed), 2);
    $rolesPlayed = $player->getRolesPlayed($roleType1, $assocType);
    $this->assertEquals(count($rolesPlayed), 1);
    $rolesPlayed = $player->getRolesPlayed($roleType2, $assocType);
    $this->assertEquals(count($rolesPlayed), 1);
    $rolesPlayed = $player->getRolesPlayed($roleType1);
    $this->assertEquals(count($rolesPlayed), 1);
    $rolesPlayed = $player->getRolesPlayed($roleType2);
    $this->assertEquals(count($rolesPlayed), 1);
  }
  
  public function testMergeScope() {
    $tm = $this->topicMap;
    $player = $tm->createTopic();
    $assocType = $tm->createTopic();
    $roleType = $tm->createTopic();
    
    $assocTheme = $tm->createTopicByItemIdentifier('#en');
    $assoc = $tm->createAssociation($assocType, array($assocTheme));
    $role = $assoc->createRole($roleType, $player);
    
    $assocTheme = $tm->createTopicByItemIdentifier('#english');
    $assoc = $tm->createAssociation($assocType, array($assocTheme));
    $role = $assoc->createRole($roleType, $player);
    
    $mergeTopic = $tm->createTopicByItemIdentifier('#en');
    $mergeTopic->addItemIdentifier('#english');
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1, 'Expected 1 association!');
  }
}
?>