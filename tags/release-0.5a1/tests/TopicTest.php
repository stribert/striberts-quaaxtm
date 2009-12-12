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
 * Topic tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $parent = $this->topicMap;
    $this->assertEquals(count($parent->getTopics()), 0, 
      'Expected new topic map to be created without topics!');
    $topic = $parent->createTopic();
    $this->assertEquals($parent->getId(), $topic->getParent()->getId(), 
      'Unexpected topic parent!');
    $this->assertEquals(count($parent->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->getIdsOfConstructs($parent->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $topic->remove();
    $this->assertEquals(count($parent->getTopics()), 0, 
      'Expected 0 topics after removal!');
  }
  
  public function testAddSubjectIdentifierIllegal() {
    $topic = $this->topicMap->createTopic();
    try {
      $topic->addSubjectIdentifier(null);
      $this->fail('null is not allowed as subject identifier!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testAddSubjectLocatorIllegal() {
    $topic = $this->topicMap->createTopic();
    try {
      $topic->addSubjectLocator(null);
      $this->fail('null is not allowed as subject locator!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testSubjectIdentifiers() {
    $sid1 = 'http://www.example.org/1';
    $sid2 = 'http://www.example.org/2';
    $topic = $this->topicMap->createTopicBySubjectIdentifier($sid1);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier');
    $this->assertTrue(in_array($sid1, $topic->getSubjectIdentifiers(), true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
    $topic->addSubjectIdentifier($sid2);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 2, 
      'Expected 2 subject identifiers');
    $this->assertTrue(in_array($sid1, $topic->getSubjectIdentifiers(), true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
    $this->assertTrue(in_array($sid2, $topic->getSubjectIdentifiers(), true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
    $topic->removeSubjectIdentifier($sid1);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier');
    $this->assertTrue(in_array($sid2, $topic->getSubjectIdentifiers(), true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
  }
  
  public function testSubjectLocators() {
    $slo1 = 'http://www.example.org/1';
    $slo2 = 'http://www.example.org/2';
    $topic = $this->topicMap->createTopicBySubjectLocator($slo1);
    $this->assertEquals(count($topic->getSubjectLocators()), 1, 
      'Expected 1 subject locator');
    $this->assertTrue(in_array($slo1, $topic->getSubjectLocators(), true), 
      'Subject locator is not part of getSubjectLocators()!');
    $topic->addSubjectLocator($slo2);
    $this->assertEquals(count($topic->getSubjectLocators()), 2, 
      'Expected 2 subject locators');
    $this->assertTrue(in_array($slo1, $topic->getSubjectLocators(), true), 
      'Subject locator is not part of getSubjectLocators()!');
    $this->assertTrue(in_array($slo2, $topic->getSubjectLocators(), true), 
      'Subject locator is not part of getSubjectLocators()!');
    $topic->removeSubjectLocator($slo1);
    $this->assertEquals(count($topic->getSubjectLocators()), 1, 
      'Expected 1 subject locator');
    $this->assertTrue(in_array($slo2, $topic->getSubjectLocators(), true), 
      'Subject locator is not part of getSubjectLocators()!');
  }
  
  public function testTopicTypes() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $this->assertEquals(count($topic->getTypes()), 0, 
      'Expected new topic to be created without types!');
    $topic->addType($type1);
    $this->assertEquals(count($topic->getTypes()), 1, 'Expected 1 topic type!');
    $ids = $this->getIdsOfConstructs($topic->getTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Topic is not part of getTypes()!');
    $topic->addType($type2);
    $this->assertEquals(count($topic->getTypes()), 2, 'Expected 2 topic types!');
    $ids = $this->getIdsOfConstructs($topic->getTypes());
    $this->assertTrue(in_array($type1->getId(), $ids, true), 
      'Topic is not part of getTypes()!');
    $this->assertTrue(in_array($type2->getId(), $ids, true), 
      'Topic is not part of getTypes()!');
    $topic->removeType($type1);
    $this->assertEquals(count($topic->getTypes()), 1, 'Expected 1 topic type!');
    $ids = $this->getIdsOfConstructs($topic->getTypes());
    $this->assertTrue(in_array($type2->getId(), $ids, true), 
      'Topic is not part of getTypes()!');
    $topic->removeType($type2);
    $this->assertEquals(count($topic->getTypes()), 0, 'Expected 0 topic types!');
  }
  
  public function testRoleFilter() {
    $tm = $this->topicMap;
    $player = $tm->createTopic();
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $unusedType = $tm->createTopic();
    $assoc = $this->createAssoc();
    $this->assertEquals(count($player->getRolesPlayed($type1)), 0, 
      'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed($type2)), 0, 
      'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed($unusedType)), 0, 
      'Expected new topic to be created without playing roles!');
    $role = $assoc->createRole($type1, $player);
    $this->assertEquals(count($player->getRolesPlayed($type1)), 1, 
      'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($type1));
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed($type2)), 0, 
      'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($unusedType)), 0, 
      'Expected topic not to play this role!');
    $role->setType($type2);
    $this->assertEquals(count($player->getRolesPlayed($type2)), 1, 
      'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($type2));
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed($type1)), 0, 
      'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($unusedType)), 0, 
      'Expected topic not to play this role!');
    $role->remove();
    $this->assertEquals(count($player->getRolesPlayed($type1)), 0, 
      'Expected topic to play a role!');
    $this->assertEquals(count($player->getRolesPlayed($type2)), 0, 
      'Expected topic to play a role!');
    $this->assertEquals(count($player->getRolesPlayed($unusedType)), 0, 
      'Expected topic to play a role!');
  }
  
  public function testRoleAssociationFilter() {
    $tm = $this->topicMap;
    $player = $tm->createTopic();
    $assocType1 = $tm->createTopic();
    $assocType2 = $tm->createTopic();
    $roleType1 = $tm->createTopic();
    $roleType2 = $tm->createTopic();
    $assoc = $tm->createAssociation($assocType1);
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      0, 'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType1)), 
      0, 'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType2)), 
      0, 'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      0, 'Expected new topic to be created without playing roles!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected new topic to be created without playing roles!');
    
    $role1 = $assoc->createRole($roleType1, $player);
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      1, 'Expected topic to play this role!');
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType1)), 
      1, 'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($roleType1, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed(null, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected topic not to play this role!');
    
    $role2 = $assoc->createRole($roleType2, $player);
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      1, 'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($roleType1, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      1, 'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($roleType2, $assocType1));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
      
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType1)), 
      2, 'Expected topic to play these roles!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed(null, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
      
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $role2->setType($roleType1);
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      2, 'Expected topic to play these roles!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($roleType1, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
      
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType1)), 
      2, 'Expected topic to play these roles!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed(null, $assocType1));
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Topic is not part of getRolesPlayed()!');
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!'); 
      
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed(null, $assocType2)), 
      0, 'Expected topic not to play this role!');
    
    $role1->remove();
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      1, 'Expected topic to play this role!');
    $ids = $this->getIdsOfConstructs($player->getRolesPlayed($roleType1, $assocType1));
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $assoc->remove();
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType1)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType1, $assocType2)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType1)), 
      0, 'Expected topic not to play this role!');
    $this->assertEquals(count($player->getRolesPlayed($roleType2, $assocType2)), 
      0, 'Expected topic not to play this role!');
  }
  
  public function testOccurrenceFilter() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $unusedType = $tm->createTopic();
    $this->assertEquals(count($topic->getOccurrences($type1)), 0, 
      'Expected new topic to be created without occurrences!');
    $this->assertEquals(count($topic->getOccurrences($type2)), 0, 
      'Expected new topic to be created without occurrences!');
    $this->assertEquals(count($topic->getOccurrences($unusedType)), 0, 
      'Expected new topic to be created without occurrences!');
    $occ = $topic->createOccurrence($type1, 'Occurrence', self::$dtString);
    $this->assertEquals(count($topic->getOccurrences($type1)), 1, 
      'Expected topic to gain this occurrence!');
    $ids = $this->getIdsOfConstructs($topic->getOccurrences($type1));
    $this->assertTrue(in_array($occ->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertEquals(count($topic->getOccurrences($type2)), 0, 
      'Expected topic not to gain this occurrence!');
    $this->assertEquals(count($topic->getOccurrences($unusedType)), 0, 
      'Expected topic not to gain this occurrence!');
    $occ->setType($type2);
    $this->assertEquals(count($topic->getOccurrences($type2)), 1, 
      'Expected topic to gain this occurrence!');
    $ids = $this->getIdsOfConstructs($topic->getOccurrences($type2));
    $this->assertTrue(in_array($occ->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertEquals(count($topic->getOccurrences($type1)), 0, 
      'Expected topic not to gain this occurrence!');
    $this->assertEquals(count($topic->getOccurrences($unusedType)), 0, 
      'Expected topic not to gain this occurrence!');
    $occ->remove();
    $this->assertEquals(count($topic->getOccurrences($type1)), 0, 
      'Expected topic not to gain this occurrence!');
    $this->assertEquals(count($topic->getOccurrences($type2)), 0, 
      'Expected topic not to gain this occurrence!');
    $this->assertEquals(count($topic->getOccurrences($unusedType)), 0, 
      'Expected topic not to gain this occurrence!');
  }
  
  public function testNameFilter() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $unusedType = $tm->createTopic();
    $this->assertEquals(count($topic->getNames($type1)), 0, 
      'Expected new topic to be created without names!');
    $this->assertEquals(count($topic->getNames($type2)), 0, 
      'Expected new topic to be created without names!');
    $this->assertEquals(count($topic->getNames($unusedType)), 0, 
      'Expected new topic to be created without names!');
    $name = $topic->createName('Name', $type1);
    $this->assertEquals(count($topic->getNames($type1)), 1, 
      'Expected topic to gain this name!');
    $ids = $this->getIdsOfConstructs($topic->getNames($type1));
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic->getNames($type2)), 0, 
      'Expected topic not to gain this name!');
    $this->assertEquals(count($topic->getNames($unusedType)), 0, 
      'Expected topic not to gain this name!');
    $name->setType($type2);
    $this->assertEquals(count($topic->getNames($type2)), 1, 
      'Expected topic to gain this name!');
    $ids = $this->getIdsOfConstructs($topic->getNames($type2));
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic->getNames($type1)), 0, 
      'Expected topic not to gain this name!');
    $this->assertEquals(count($topic->getNames($unusedType)), 0, 
      'Expected topic not to gain this name!');
    $name->remove();
    $this->assertEquals(count($topic->getNames($type1)), 0, 
      'Expected topic not to gain this name!');
    $this->assertEquals(count($topic->getNames($type2)), 0, 
      'Expected topic not to gain this name!');
    $this->assertEquals(count($topic->getNames($unusedType)), 0, 
      'Expected topic not to gain this name!');
  }
  
  public function testOccurrenceCreation() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $value = 'Occurrence';
    $dt = self::$dtString;
    $this->assertEquals(count($topic->getOccurrences()), 0, 
      'Expected new topic to be created without occurrences!');
    $occ = $topic->createOccurrence($type, $value, $dt);
    $this->assertEquals(count($topic->getOccurrences()), 1, 'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($topic->getOccurrences());
    $this->assertTrue(in_array($occ->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertEquals(count($occ->getScope()), 0, 'Unexpected scope!');
    $this->assertEquals($occ->getValue(), $value, 'Unexpected value!');
    $this->assertEquals($occ->getDatatype(), $dt, 'Unexpected datatype!');
    $this->assertEquals(count($occ->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
  }
  
  public function testOccurrenceCreationScope() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $value = 'Occurrence';
    $dt = self::$dtUri;
    $this->assertEquals(count($topic->getOccurrences()), 0, 
      'Expected new topic to be created without occurrences!');
    $occ = $topic->createOccurrence($type, $value, $dt, array($theme1, $theme2));
    $this->assertEquals(count($topic->getOccurrences()), 1, 'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($topic->getOccurrences());
    $this->assertTrue(in_array($occ->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertEquals(count($occ->getScope()), 2, 'Unexpected scope!');
    $ids = $this->getIdsOfConstructs($occ->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertEquals($occ->getValue(), $value, 'Unexpected value!');
    $this->assertEquals($occ->getDatatype(), $dt, 'Unexpected datatype!');
    $this->assertEquals(count($occ->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
  }
  
  public function testOccurrenceCreationIllegal() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $value = null;
    $dt = null;
    try {
      $occ = $topic->createOccurrence($type, $value, $dt);
      $this->fail('Value and datatype must not be null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testOccurrenceCreationIllegalValue() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $value = null;
    $dt = self::$dtString;
    try {
      $occ = $topic->createOccurrence($type, $value, $dt);
      $this->fail('null is not allowed as value!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testOccurrenceCreationIllegalDatatype() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $value = 'Occurrence';
    $dt = null;
    try {
      $occ = $topic->createOccurrence($type, $value, $dt);
      $this->fail('null is not allowed as datatype!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testNameCreationType() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $value = 'Name';
    $this->assertEquals(count($topic->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $topic->createName($value, $type);
    $this->assertEquals(count($topic->getNames()), 1, 'Expected 1 name!');
    $ids = $this->getIdsOfConstructs($topic->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($name->getScope()), 0, 'Unexpected scope!');
    $this->assertEquals($name->getValue(), $value, 'Unexpected value!');
    $this->assertEquals($name->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($name->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
  }
  
  public function testNameCreationTypeScope() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $value = 'Name';
    $this->assertEquals(count($topic->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $topic->createName($value, $type, array($theme1, $theme2));
    $this->assertEquals(count($topic->getNames()), 1, 'Expected 1 name!');
    $ids = $this->getIdsOfConstructs($topic->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($name->getScope()), 2, 'Unexpected scope!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertEquals($name->getValue(), $value, 'Unexpected value!');
    $this->assertEquals($name->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($name->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
  }
  
  public function testNameCreationDefaultType() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $psi = 'http://psi.topicmaps.org/iso13250/model/topic-name';
    $value = 'Name';
    $this->assertEquals(count($topic->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $topic->createName($value);
    $this->assertEquals(count($topic->getNames()), 1, 'Expected 1 name!');
    $ids = $this->getIdsOfConstructs($topic->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($name->getScope()), 0, 'Unexpected scope!');
    $this->assertEquals($name->getValue(), $value, 'Unexpected value!');
    $this->assertEquals(count($name->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
    $defaultType = $name->getType();
    $sids = $defaultType->getSubjectIdentifiers();
    $this->assertTrue(in_array($psi, $sids, true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
  }
  
  public function testNameCreationDefaultTypeScope() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $psi = 'http://psi.topicmaps.org/iso13250/model/topic-name';
    $value = 'Name';
    $this->assertEquals(count($topic->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $topic->createName($value, null, array($theme1, $theme2));
    $this->assertEquals(count($topic->getNames()), 1, 'Expected 1 name!');
    $ids = $this->getIdsOfConstructs($topic->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($name->getScope()), 2, 'Unexpected scope!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertEquals($name->getValue(), $value, 'Unexpected value!');
    $this->assertEquals(count($name->getItemIdentifiers()), 0, 
      'Unexpected number of item identifiers!');
    $defaultType = $name->getType();
    $sids = $defaultType->getSubjectIdentifiers();
    $this->assertTrue(in_array($psi, $sids, true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
  }
  
  public function testNameCreationIllegal() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $value = null;
    try {
      $name = $topic->createName($value);
      $this->fail('null is not allowed as value!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
}
?>
