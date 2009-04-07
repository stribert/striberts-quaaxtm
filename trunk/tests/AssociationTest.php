<?php
require_once('PHPTMAPITestCase.php');

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
    $this->assertTrue(count($assoc->getRolesByType($type1)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRolesByType($unusedType)) == 0, 
      'Expected 0 roles!');
    $role1 = $assoc->createRole($type1, $tm->createTopic());
    $this->assertTrue(count($assoc->getRolesByType($type1)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRolesByType($type1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Role is not part of getRolesByType()!');
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRolesByType($unusedType)) == 0, 
      'Expected 0 roles!');
    $role2 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRolesByType($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesByType()!');
    $role3 = $assoc->createRole($type2, $tm->createTopic());
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 2, 
      'Expected 2 roles!');
    $ids = $this->getIdsOfConstructs($assoc->getRolesByType($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesByType()!');
    $this->assertTrue(in_array($role3->getId(), $ids, true), 
      'Role is not part of getRolesByType()!');
    $this->assertTrue(count($assoc->getRolesByType($unusedType)) == 0, 
      'Expected 0 roles!');
    $role3->remove();
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 1, 
      'Expected 1 role!');
    $ids = $this->getIdsOfConstructs($assoc->getRolesByType($type2));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesByType()!');
    $role2->remove();
    $this->assertTrue(count($assoc->getRolesByType($type2)) == 0, 
      'Expected 0 roles!');
    $role1->remove();
    $this->assertTrue(count($assoc->getRolesByType($type1)) == 0, 
      'Expected 0 roles!');
    $this->assertTrue(count($assoc->getRolesByType($unusedType)) == 0, 
      'Expected 0 roles!');
  }
}
?>