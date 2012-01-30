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
 * Type instance index tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TypeInstanceIndexTest extends PHPTMAPITestCase
{
  public function testGetTopics()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);

    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $instance1 = $tm->createTopic();
    $instance2 = $tm->createTopic();
    $instance3 = $tm->createTopic();
    
    $instance1->addtype($type1);
    $instance1->addtype($type2);
    $instance2->addtype($type1);
    $instance2->addtype($type2);
    $instance3->addtype($type1);
    
    $topics = $index->getTopics(array(), true);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1), true);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1), false);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1, $type2), false);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1, $type2), true);
    $this->assertEquals(count($topics), 2);
    $this->assertFalse($topics[0]->equals($topics[1]));
    
    $topics = $index->getTopics(array($tm->createTopic()), true);
    $this->assertEquals(count($topics), 0);
    
    $tm2 = $this->_sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
    $tm2Type1 = $tm2->createTopic();
    $tm2Type2 = $tm2->createTopic();
    $tm2Instance1 = $tm2->createTopic();
    $tm2Instance2 = $tm2->createTopic();
    
    $tm2Instance1->addtype($tm2Type1);
    $tm2Instance2->addtype($tm2Type1);
    $tm2Instance2->addtype($tm2Type2);
    
    $tm2Topics = $tm2->getTopics();
    $this->assertEquals(count($tm2Topics), 4);
    $tm1Topics = $index->getTopics(array($tm2Type1), true);
    $this->assertEquals(count($tm1Topics), 0);
    $tm1Topics = $index->getTopics(array($tm2Type1), false);
    $this->assertEquals(count($tm1Topics), 0);
    $tm1Topics = $index->getTopics(array($tm2Type1, $tm2Type2), true);
    $this->assertEquals(count($tm1Topics), 0);
    $tm1Topics = $index->getTopics(array($tm2Type1, $tm2Type2), false);
    $this->assertEquals(count($tm1Topics), 0);
    
    $tm2->remove();
    
    try {
      $index->getTopics(array('foo'), true);
      $this->fail('Expected InvalidArgumentException!');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getTopics(array($type1, 'foo'), true);
      $this->fail('Expected InvalidArgumentException!');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
  }
  
  public function testGetTopicTypes()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);

    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $instance1 = $tm->createTopic();
    $instance2 = $tm->createTopic();
    
    $instance1->addtype($type1);
    $instance1->addtype($type2);
    $instance2->addtype($type1);
    
    $types = $index->getTopicTypes();
    $this->assertEquals(count($types), 2);
  }
  
  public function testGetAssociations()
  {
    $tm1 = $this->_topicMap;
    $this->assertTrue($tm1 instanceof TopicMap);
    $index = $tm1->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $assocType = $tm1->createTopic();
    
    $tm1->createAssociation($assocType);
    $tm1->createAssociation($assocType);// duplicate
    $tm1->createAssociation($tm1->createTopic());
    
    $assocs = $tm1->getAssociations();
    $this->assertEquals(count($assocs), 2);
    $assocs = $index->getAssociations($assocType);
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    $this->assertTrue($assoc instanceof Association);
    $this->assertEquals($assoc->getType()->getId(), $assocType->getId());
    $this->assertEquals($tm1->getId(), $assoc->getParent()->getId());
    $assocs = $index->getAssociations($tm1->createTopic());
    $this->assertEquals(count($assocs), 0);
    
    $tm2 = $this->_sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
    $tm2AssocType = $tm2->createTopic();
    $tm2->createAssociation($tm2AssocType);
    $tm2Assocs = $tm2->getAssociationsByType($tm2AssocType);
    $this->assertEquals(count($tm2Assocs), 1);
    $tm1Assocs = $index->getAssociations($tm2AssocType);
    $this->assertEquals(count($tm1Assocs), 0);
    $tm2->remove();
  }
  
  public function testGetAssociationTypes()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $assocType1 = $tm->createTopic();
    $assocType2 = $tm->createTopic();
    $assocType3 = $tm->createTopic();
    $assocTypes = array(
      $assocType1->getId() => $assocType1, 
      $assocType2->getId() => $assocType2, 
      $assocType3->getId() => $assocType3 
    );
    
    $tm->createAssociation($assocType1);
    $tm->createAssociation($assocType1);// duplicate
    $tm->createAssociation($assocType2);
    $tm->createAssociation($assocType3);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 3);

    $types = $index->getAssociationTypes();
    $this->assertEquals(count($types), 3);
    foreach ($types as $type) {
      $this->assertTrue($type instanceof Topic);
      $this->assertTrue(array_key_exists($type->getId(), $assocTypes));
      unset($assocTypes[$type->getId()]);
    }
    $this->assertTrue(empty($assocTypes));
  }
  
  public function testGetRoles()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $roleType1 = $tm->createTopic();
    $roleType2 = $tm->createTopic();
    $rolePlayer1 = $tm->createTopic();
    $rolePlayer2 = $tm->createTopic();
    $rolePlayer3 = $tm->createTopic();
    $twoPlayers = array(
      $rolePlayer2->getId() => $rolePlayer2,
      $rolePlayer3->getId() => $rolePlayer3,
    );
    
    $assoc = $tm->createAssociation($tm->createTopic());
    $assoc->createRole($roleType1, $rolePlayer1);
    $assoc->createRole($roleType2, $rolePlayer2);
    $assoc->createRole($roleType2, $rolePlayer3);
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 3);
    $roles = $index->getRoles($roleType1);
    $this->assertEquals(count($roles), 1);
    $role = $roles[0];
    $this->assertEquals($role->getType()->getId(), $roleType1->getId());
    $this->assertEquals($role->getPlayer()->getId(), $rolePlayer1->getId());
    $this->assertEquals($role->getParent()->getId(), $assoc->getId());
    $roles = $index->getRoles($roleType2);
    $this->assertEquals(count($roles), 2);
    foreach ($roles as $role) {
      $this->assertEquals($role->getType()->getId(), $roleType2->getId());
      $this->assertTrue(array_key_exists($role->getPlayer()->getId(), $twoPlayers));
      $this->assertEquals($role->getParent()->getId(), $assoc->getId());
      unset($twoPlayers[$role->getPlayer()->getId()]);
    }
    $roles = $index->getRoles($tm->createTopic());
    $this->assertEquals(count($roles), 0);
    
    $tm2 = $this->_sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
    $tm2AssocType = $tm2->createTopic();
    $tm2Assoc = $tm2->createAssociation($tm2AssocType);
    $tm2RoleType = $tm2->createTopic();
    $tm2Assoc->createRole($tm2RoleType, $tm2->createTopic());
    
    $tm2Assocs = $tm2->getAssociationsByType($tm2AssocType);
    $this->assertEquals(count($tm2Assocs), 1);
    $tm2Assoc = $tm2Assocs[0];
    $tm2Roles = $tm2Assoc->getRoles($tm2RoleType);
    $this->assertEquals(count($tm2Roles), 1);
    $tm1Roles = $index->getRoles($tm2RoleType);
    $this->assertEquals(count($tm1Roles), 0);
    $tm2->remove();
  }
  
  public function testGetRoleTypes()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $roleType1 = $tm->createTopic();
    $roleType2 = $tm->createTopic();
    $roleType3 = $tm->createTopic();
    $roleTypes = array(
      $roleType1->getId() => $roleType1, 
      $roleType2->getId() => $roleType2, 
      $roleType3->getId() => $roleType3
    );
    
    $assoc = $tm->createAssociation($tm->createTopic());
    $assoc->createRole($roleType1, $tm->createTopic());
    $assoc->createRole($roleType2, $tm->createTopic());
    $assoc->createRole($roleType3, $tm->createTopic());
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 3);

    $types = $index->getRoleTypes();
    $this->assertEquals(count($types), 3);
    foreach ($types as $type) {
      $this->assertTrue($type instanceof Topic);
      $this->assertTrue(array_key_exists($type->getId(), $roleTypes));
      unset($roleTypes[$type->getId()]);
    }
    $this->assertTrue(empty($roleTypes));
  }
  
  public function testGetNames()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $topic = $tm->createTopic();
    $nameType1 = $tm->createTopic();
    $nameType2 = $tm->createTopic();
    
    $topic->createName('foo', $nameType1);
    $topic->createName('bar', $nameType2);
    $topic->createName('baz');
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 3);
    $names = $index->getNames($nameType1);
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'foo');
    $this->assertEquals($name->getParent()->getId(), $topic->getId());
    $names = $index->getNames($nameType2);
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'bar');
    $this->assertEquals($name->getParent()->getId(), $topic->getId());
    $type = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $names = $index->getNames($type);
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'baz');
    $this->assertEquals($name->getParent()->getId(), $topic->getId());
    
    $tm2 = $this->_sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
    $tm2Topic = $tm2->createTopic();
    $tm2NameType = $tm2->createTopic();
    $tm2Topic->createName('baz', $tm2NameType);
    
    $tm2TopicNames = $tm2Topic->getNames($tm2NameType);
    $this->assertEquals(count($tm2TopicNames), 1);
    $tm1TopicNames = $index->getNames($tm2NameType);
    $this->assertEquals(count($tm1TopicNames), 0);
  }
  
  public function testGetNameTypes()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $topic = $tm->createTopic();
    $nameType1 = $tm->createTopic();
    $nameType2 = $tm->createTopic();
    $nameTypes = array(
      $nameType1->getId() => $nameType1, 
      $nameType2->getId() => $nameType2
    );
    
    $topic->createName('Name', $nameType1);
    $topic->createName('Name', $nameType2);
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 2);
    $types = $index->getNameTypes();
    $this->assertEquals(count($types), 2);
    foreach ($types as $type) {
      $this->assertTrue($type instanceof Topic);
      $this->assertTrue(array_key_exists($type->getId(), $nameTypes));
      unset($nameTypes[$type->getId()]);
    }
    $this->assertTrue(empty($nameTypes));
  }
  
  public function testGetOccurrences()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $topic = $tm->createTopic();
    $occType1 = $tm->createTopic();
    $occType2 = $tm->createTopic();
    
    $topic->createOccurrence($occType1, 'foo', parent::$_dtString);
    $topic->createOccurrence($occType2, 'http://example.org', parent::$_dtUri);
    
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 2);
    $occs = $index->getOccurrences($occType1);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertEquals($occ->getType()->getId(), $occType1->getId());
    $this->assertEquals($occ->getValue(), 'foo');
    $this->assertEquals($occ->getDatatype(), parent::$_dtString);
    $occs = $index->getOccurrences($occType2);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertEquals($occ->getType()->getId(), $occType2->getId());
    $this->assertEquals($occ->getValue(), 'http://example.org');
    $this->assertEquals($occ->getDatatype(), parent::$_dtUri);
    
    $occs = $topic->getOccurrences($occType1);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertEquals($occ->getType()->getId(), $occType1->getId());
    $this->assertEquals($occ->getValue(), 'foo');
    $this->assertEquals($occ->getDatatype(), parent::$_dtString);
    $occs = $index->getOccurrences($occType2);
    $this->assertEquals(count($occs), 1);
    $occs = $topic->getOccurrences($occType2);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertEquals($occ->getType()->getId(), $occType2->getId());
    $this->assertEquals($occ->getValue(), 'http://example.org');
    $this->assertEquals($occ->getDatatype(), parent::$_dtUri);
    
    $tm2 = $this->_sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
    $tm2Topic = $tm2->createTopic();
    $tm2OccType = $tm2->createTopic();
    $tm2Topic->createOccurrence($tm2OccType, 'http://example.org', parent::$_dtUri);
    
    $tm2Occs = $tm2Topic->getOccurrences($tm2OccType);
    $this->assertEquals(count($tm2Occs), 1);
    $tm1Occs = $index->getOccurrences($tm2OccType);
    $this->assertEquals(count($tm1Occs), 0);
  }
  
  public function testGetOccurrenceTypes()
  {
    $tm = $this->_topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof TypeInstanceIndexImpl);
    
    $topic = $tm->createTopic();
    $occType1 = $tm->createTopic();
    $occType2 = $tm->createTopic();
    $occTypes = array(
      $occType1->getId() => $occType1, 
      $occType2->getId() => $occType2
    );
    
    $topic->createOccurrence($occType1, 'foo', parent::$_dtString);
    $topic->createOccurrence($occType2, 'bar', parent::$_dtString);
    
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 2);
    $types = $index->getOccurrenceTypes();
    $this->assertEquals(count($types), 2);
    foreach ($types as $type) {
      $this->assertTrue($type instanceof Topic);
      $this->assertTrue(array_key_exists($type->getId(), $occTypes));
      unset($occTypes[$type->getId()]);
    }
    $this->assertTrue(empty($occTypes));
  }
}
?>