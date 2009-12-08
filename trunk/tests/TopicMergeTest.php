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
 * Topic merge tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMergeTest extends PHPTMAPITestCase {

  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  /**
   * Tests if $t->mergeIn($t) is ignored.
   */
  public function testTopicMergeNoop() {
    $tm = $this->topicMap;
    $locator = 'http://localhost/t/3';
    $topic = $tm->createTopicBySubjectIdentifier($locator);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic->getId(), 
      $tm->getTopicBySubjectIdentifier($locator)->getId(), 'Expected identity!');
    $topic->mergeIn($tm->getTopicBySubjectIdentifier($locator));
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic->getId(), 
      $tm->getTopicBySubjectIdentifier($locator)->getId(), 'Expected identity!');
  }
  
  public function testTypesMerged() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $topic1->addType($type);
    $this->assertEquals(count($topic1->getTypes()), 1, 'Expected 1 topic type!');
    $this->assertEquals(count($topic2->getTypes()), 0, 'Unexpected topic type!');
    $topic2->mergeIn($topic1);
    $this->assertEquals(count($topic2->getTypes()), 1, 'Expected 1 topic type!');
    $ids = $this->getIdsOfConstructs($topic2->getTypes());
    $this->assertTrue(in_array($type->getId(), $ids, true), 
      'Topic is not part of getTypes()!');
  }
  
  /**
   * If topics reify different Topic Maps constructs they cannot be merged.
   */
  public function testReifiedClash() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $assoc1 = $this->createAssoc();
    $assoc2 = $this->createAssoc();
    $assoc1->setReifier($topic1);
    $assoc2->setReifier($topic2);
    $this->assertEquals($assoc1->getReifier()->getId(), $topic1->getId(), 
      'Expected identity!');
    $this->assertEquals($assoc2->getReifier()->getId(), $topic2->getId(), 
      'Expected identity!');
    try {
      $topic1->mergeIn($topic2);
      $this->fail('The topics reify different Topic Maps constructs and cannot be merged!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  /**
   * Tests if a topic overtakes all roles played of the other topic.
   */
  public function testRolePlaying() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $assoc = $this->createAssoc();
    $role = $assoc->createRole($tm->createTopic(), $topic2);
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $this->assertEquals(count($topic1->getRolesPlayed()), 0, 'Unexpected role player!');
    $this->assertEquals(count($topic2->getRolesPlayed()), 1, 'Expected topic to play 1 role!');
    $ids = $this->getIdsOfConstructs($topic2->getRolesPlayed());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($topic1->getRolesPlayed()), 1, 'Expected topic to play 1 role!');
    $ids = $this->getIdsOfConstructs($topic1->getRolesPlayed());
    $this->assertTrue(in_array($role->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
  }
  
  /**
   * Tests if the subject identifiers are overtaken.
   */
  public function testIdentitySubjectIdentifier() {
    $tm = $this->topicMap;
    $sid1 = 'http://psi.example.org/sid-1';
    $sid2 = 'http://psi.example.org/sid-2';
    $topic1 = $tm->createTopicBySubjectIdentifier($sid1);
    $topic2 = $tm->createTopicBySubjectIdentifier($sid2);
    $this->assertTrue(in_array($sid1, $topic1->getSubjectIdentifiers()), 
      'Expected subject identifier not found!');
    $this->assertFalse(in_array($sid1, $topic2->getSubjectIdentifiers()), 
      'Unexpected subject identifier!');
    $this->assertTrue(in_array($sid2, $topic2->getSubjectIdentifiers()), 
      'Expected subject identifier not found!');
    $this->assertFalse(in_array($sid2, $topic1->getSubjectIdentifiers()), 
      'Unexpected subject identifier!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 2, 
      'Expected 2 subject identifiers!');
    $this->assertTrue(in_array($sid1, $topic1->getSubjectIdentifiers()), 
      'Expected subject identifier not found!');
    $this->assertTrue(in_array($sid2, $topic1->getSubjectIdentifiers()), 
      'Expected subject identifier not found!');
  }
  
  /**
   * Tests if the subject locators are overtaken.
   */
  public function testIdentitySubjectLocator() {
    $tm = $this->topicMap;
    $slo1 = 'http://phptmapi.sourceforge.net/';
    $slo2 = 'http://phptmapi.sf.net/';
    $topic1 = $tm->createTopicBySubjectLocator($slo1);
    $topic2 = $tm->createTopicBySubjectLocator($slo2);
    $this->assertTrue(in_array($slo1, $topic1->getSubjectLocators()), 
      'Expected subject locator not found!');
    $this->assertFalse(in_array($slo1, $topic2->getSubjectLocators()), 
      'Unexpected subject locator!');
    $this->assertTrue(in_array($slo2, $topic2->getSubjectLocators()), 
      'Expected subject locator not found!');
    $this->assertFalse(in_array($slo2, $topic1->getSubjectLocators()), 
      'Unexpected subject locator!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($topic1->getSubjectLocators()), 2, 
      'Expected 2 subject locators!');
    $this->assertTrue(in_array($slo1, $topic1->getSubjectLocators()), 
      'Expected subject locator not found!');
    $this->assertTrue(in_array($slo2, $topic1->getSubjectLocators()), 
      'Expected subject locator not found!');
  }
  
  /**
   * Tests if the item identifiers are overtaken.
   */
  public function testIdentityItemIdentifier() {
    $tm = $this->topicMap;
    $iid1 = 'http://localhost/t/1';
    $iid2 = 'http://localhost/t/2';
    $topic1 = $tm->createTopicByItemIdentifier($iid1);
    $topic2 = $tm->createTopicByItemIdentifier($iid2);
    $this->assertTrue(in_array($iid1, $topic1->getItemIdentifiers()), 
      'Expected item identifier not found!');
    $this->assertFalse(in_array($iid1, $topic2->getItemIdentifiers()), 
      'Unexpected item identifier!');
    $this->assertTrue(in_array($iid2, $topic2->getItemIdentifiers()), 
      'Expected item identifier not found!');
    $this->assertFalse(in_array($iid2, $topic1->getItemIdentifiers()), 
      'Unexpected item identifier!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($topic1->getItemIdentifiers()), 2, 
      'Expected 2 item identifiers!');
    $this->assertTrue(in_array($iid1, $topic1->getItemIdentifiers()), 
      'Expected item identifier not found!');
    $this->assertTrue(in_array($iid2, $topic1->getItemIdentifiers()), 
      'Expected item identifier not found!');
  }
  
  /**
   * Tests if merging detects duplicates and that the reifier is preserved.
   */
  public function testDuplicateDetectionReifier() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $reifier = $tm->createTopic();
    $name1 = $topic1->createName('Name', $type);
    $name2 = $topic2->createName('Name', $type);
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $name1->setReifier($reifier);
    $this->assertEquals($name1->getReifier()->getId(), $reifier->getId(), 
      'Expected identity!');
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name');
    $ids = $this->getIdsOfConstructs($topic1->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic2->getNames()), 1, 'Expected 1 topic name');
    $ids = $this->getIdsOfConstructs($topic2->getNames());
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $names = $topic1->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $this->assertEquals($name->getValue(), 'Name', 'Unexpected name value!');
      $this->assertEquals($name->getReifier()->getId(), $reifier->getId(), 
        'Expected identity!');
    } else {
      $this->fail('Expected only 1 name!');
    }
  }
  
  /**
   * Tests if merging detects duplicates and merges the reifiers of the duplicates.
   */
  public function testDuplicateDetectionReifierMerge() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $reifier1 = $tm->createTopic();
    $reifier2 = $tm->createTopic();
    $reifier1->createName('Reifier1', $type);
    $reifier2->createName('Reifier2', $type);
    $name1 = $topic1->createName('Name', $type);
    $name2 = $topic2->createName('Name', $type);
    $this->assertEquals(count($tm->getTopics()), 5, 'Expected 5 topics!');
    $name1->setReifier($reifier1);
    $name2->setReifier($reifier2);
    $this->assertEquals($name1->getReifier()->getId(), $reifier1->getId(), 
      'Expected identity!');
    $this->assertEquals($name2->getReifier()->getId(), $reifier2->getId(), 
      'Expected identity!');
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name');
    $ids = $this->getIdsOfConstructs($topic1->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic2->getNames()), 1, 'Expected 1 topic name');
    $ids = $this->getIdsOfConstructs($topic2->getNames());
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $names = $topic1->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $this->assertEquals($name->getValue(), 'Name', 'Unexpected name value!');
      $reifier = null;
      foreach ($tm->getTopics() as $topic) {
        if (!$topic->equals($topic1) && !$topic->equals($type)) {
          $reifier = $topic;
          break;
        }
      }
      $this->assertEquals($reifier->getId(), $name->getReifier()->getId(), 
        'Expected identity!');
      $this->assertEquals(count($reifier->getNames()), 2, 'Expected 2 topic names!');
      $values = array();
      foreach ($reifier->getNames() as $_name) {
        $values[] = $_name->getValue();
      }
      $this->assertTrue(in_array('Reifier1', $values), 'Expected a name value!');
      $this->assertTrue(in_array('Reifier2', $values), 'Expected a name value!');
    } else {
      $this->fail('Expected only 1 name!');
    }
  }
  
  /**
   * Tests if merging detects duplicate associations.
   */
  public function testDuplicateSuppressionAssociation() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $roleType = $tm->createTopic();
    $type = $tm->createTopic();
    $assoc1 = $tm->createAssociation($type);
    $assoc2 = $tm->createAssociation($type);
    $role1 = $assoc1->createRole($roleType, $topic1);
    $role2 = $assoc2->createRole($roleType, $topic2);
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $this->assertEquals(count($tm->getAssociations()), 1);
    $this->assertEquals(count($topic1->getRolesPlayed()), 1, 
      'Expected topic to play 1 role!');
    $this->assertEquals(count($topic2->getRolesPlayed()), 1, 
      'Expected topic to play 1 role!');
    $ids = $this->getIdsOfConstructs($topic1->getRolesPlayed());
    $this->assertTrue(in_array($role1->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $ids = $this->getIdsOfConstructs($topic2->getRolesPlayed());
    $this->assertTrue(in_array($role2->getId(), $ids, true), 
      'Role is not part of getRolesPlayed()!');
    $topic1->mergeIn($topic2);
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($tm->getAssociations()), 1, 'Expected 1 association!');
    $roles = $topic1->getRolesPlayed();
    $this->assertEquals(count($roles), 1, 'Expected 1 role!');
    if (count($roles) === 1) {
      $role = $roles[0];
      $this->assertEquals($roleType->getId(), $role->getType()->getId(), 
        'Expected identity!');
    } else {
      $this->fail('Expected 1 role!');
    }
  }
  
  /**
   * Tests if merging detects duplicate names.
   */
  public function testDuplicateSuppressionName() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $name1 = $topic1->createName('PHPTMAPI');
    $name2 = $topic2->createName('PHPTMAPI');
    $name3 = $topic2->createName('TMAPI');
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name!');
    $ids = $this->getIdsOfConstructs($topic1->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic2->getNames()), 2, 'Expected 2 topic names!');
    $ids = $this->getIdsOfConstructs($topic2->getNames());
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertTrue(in_array($name3->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $topic1->mergeIn($topic2);
    $names = $topic1->getNames();
    $this->assertEquals(count($names), 2, 'Expected 2 topic names!');
    if (count($names) === 2) {
      $values = array();
      foreach ($names as $name) {
        $values[] = $name->getValue();
      }
      $this->assertTrue(in_array('PHPTMAPI', $values), 'Expected a name value!');
      $this->assertTrue(in_array('TMAPI', $values), 'Expected a name value!');
    } else {
      $this->fail('Expected 2 topic names!');
    }
  }
  
  /**
   * Tests if merging detects duplicate names and moves the variants.
   */
  public function testDuplicateSuppressionNameVariant() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $name1 = $topic1->createName('PHPTMAPI');
    $name2 = $topic2->createName('PHPTMAPI');
    $scope = array($tm->createTopic());
    $variant = $name2->createVariant('Variant', parent::$dtString, $scope);
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name!');
    $ids = $this->getIdsOfConstructs($topic1->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic2->getNames()), 1, 'Expected 1 topic name!');
    $ids = $this->getIdsOfConstructs($topic2->getNames());
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($name2->getVariants()), 1, 'Expected 1 variant name!');
    $ids = $this->getIdsOfConstructs($name2->getVariants());
    $this->assertTrue(in_array($variant->getId(), $ids, true), 
      'Variant is not part of getVariants()!');
    $topic1->mergeIn($topic2);
    $names = $topic1->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $variants = $name->getVariants();
      $this->assertEquals(count($variants), 1, 'Expected 1 variant name!');
      if (count($variants) === 1) {
        $variant = $variants[0];
        $this->assertEquals($variant->getValue(), 'Variant', 'Unexpected value!');
      } else {
        $this->fail('Expected 1 variant name!');
      }
    } else {
      $this->fail('Expected 1 topic name!');
    }
  }
  
  /**
   * Tests if merging detects duplicate names 
   * and sets the item identifier to the union of both names.
   */
  public function testDuplicateSuppressionNameMoveItemIdentifiers() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $name1 = $topic1->createName('PHPTMAPI');
    $name2 = $topic2->createName('PHPTMAPI');
    $iid1 = 'http://example.org/iid-1';
    $iid2 = 'http://example.org/iid-2';
    $name1->addItemIdentifier($iid1);
    $name2->addItemIdentifier($iid2);
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name!');
    $ids = $this->getIdsOfConstructs($topic1->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertEquals(count($topic2->getNames()), 1, 'Expected 1 topic name!');
    $ids = $this->getIdsOfConstructs($topic2->getNames());
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertTrue(in_array($iid1, $name1->getItemIdentifiers()), 
      'Expected item identifier!');
    $this->assertTrue(in_array($iid2, $name2->getItemIdentifiers()), 
      'Expected item identifier!');
    $topic1->mergeIn($topic2);
    $names = $topic1->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $this->assertEquals(count($name->getItemIdentifiers()), 2, 
        'Expected 2 item identifiers!');
      $this->assertTrue(in_array($iid1, $name->getItemIdentifiers()), 
        'Expected item identifier!');
      $this->assertTrue(in_array($iid2, $name->getItemIdentifiers()), 
        'Expected item identifier!');
      $this->assertEquals($name->getValue(), 'PHPTMAPI', 'Unexpected value!');
    } else {
      $this->fail('Expected 1 topic name!');
    }
  }
  
  public function testNamesMerged() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $reifier = $tm->createTopic();
    $scope = array($tm->createTopic(), $tm->createTopic());
    $this->assertEquals(count($tm->getTopics()), 6, 'Expected 6 topics!');
    $name = $topic1->createName('Name', $type, $scope);
    $name->setReifier($reifier);
    $name->addItemIdentifier('http://localhost/n/1');
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name!');
    $this->assertEquals(count($topic2->getNames()), 0, 'Unexpected topic name!');
    $this->assertEquals(count($name->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
    $topic2->mergeIn($topic1);
    $this->assertEquals(count($tm->getTopics()), 5, 'Expected 5 topics!');
    $names = $topic2->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $this->assertEquals($name->getValue(), 'Name', 'Unexpected name value!');
      $this->assertEquals(count($name->getScope()), 2, 'Unexpected scope!');
      $this->assertTrue($name->getReifier() instanceof Topic, 'Expected a reifier!');
      $this->assertEquals(count($name->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
      $this->assertTrue(in_array('http://localhost/n/1', $name->getItemIdentifiers()), 
        'Expected item identifier not found!');
    } else {
      $this->fail('Expected only 1 name!');
    }
  }
  
  public function testNamesVariantMerged() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $nameReifier = $tm->createTopic();
    $variantReifier = $tm->createTopic();
    $nameScope = array($tm->createTopic(), $tm->createTopic());
    $name = $topic1->createName('Name', $type, $nameScope);
    $name->setReifier($nameReifier);
    $nameScope[] = $tm->createTopic();
    $variantScope = $nameScope;
    $variant = $name->createVariant('Variant', parent::$dtString, $variantScope);
    $variant->setReifier($variantReifier);
    $variant->addItemIdentifier('http://localhost/v/1');
    $this->assertEquals(count($tm->getTopics()), 8, 'Expected 8 topics!');
    $this->assertEquals(count($topic1->getNames()), 1, 'Expected 1 topic name!');
    $this->assertEquals(count($name->getVariants()), 1, 'Expected 1 variant name!');
    $this->assertEquals(count($topic2->getNames()), 0, 'Unexpected topic name!');
    $this->assertEquals(count($variant->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
    $topic2->mergeIn($topic1);
    $this->assertEquals(count($tm->getTopics()), 7, 'Expected 7 topics!');
    $names = $topic2->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 topic name!');
    if (count($names) === 1) {
      $name = $names[0];
      $this->assertEquals($name->getValue(), 'Name', 'Unexpected name value!');
      $this->assertEquals(count($name->getScope()), 2, 'Unexpected scope!');
      $this->assertTrue($name->getReifier() instanceof Topic, 'Expected a reifier!');
      $variants = $name->getVariants();
      $this->assertEquals(count($variants), 1, 'Expected 1 variant name!');
      if (count($variants) === 1) {
        $variant = $variants[0];
        $this->assertEquals($variant->getValue(), 'Variant', 'Unexpected variant name value!');
        $this->assertEquals(count($variant->getScope()), 3, 'Unexpected scope!');
        $this->assertTrue($variant->getReifier() instanceof Topic, 'Expected a reifier!');
        $this->assertEquals(count($variant->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
        $this->assertTrue(in_array('http://localhost/v/1', $variant->getItemIdentifiers()), 
          'Expected item identifier not found!');
      } else {
        $this->fail('Expected only 1 variant name!');
      }
    } else {
      $this->fail('Expected only 1 name!');
    }
  }
    
  public function testOccurrencesMerged() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $reifier = $tm->createTopic();
    $scope = array($tm->createTopic(), $tm->createTopic());
    $this->assertEquals(count($tm->getTopics()), 6, 'Expected 6 topics!');
    $occ = $topic1->createOccurrence($type, 'Occurrence', parent::$dtString, $scope);
    $occ->setReifier($reifier);
    $occ->addItemIdentifier('http://localhost/o/1');
    $this->assertEquals(count($topic1->getOccurrences()), 1, 'Expected 1 occurrence!');
    $this->assertEquals(count($topic2->getOccurrences()), 0, 'Unexpected occurrence!');
    $this->assertEquals(count($occ->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
    $topic2->mergeIn($topic1);
    $this->assertEquals(count($tm->getTopics()), 5, 'Expected 5 topics!');
    $occs = $topic2->getOccurrences();
    $this->assertEquals(count($occs), 1, 'Expected 1 occurrence!');
    if (count($occs) === 1) {
      $occ = $occs[0];
      $this->assertEquals($occ->getValue(), 'Occurrence', 'Unexpected occurrence value!');
      $this->assertEquals(count($occ->getScope()), 2, 'Unexpected scope!');
      $this->assertTrue($occ->getReifier() instanceof Topic, 'Expected a reifier!');
      $this->assertEquals(count($occ->getItemIdentifiers()), 1, 'Expected 1 item identifier!');
      $this->assertTrue(in_array('http://localhost/o/1', $occ->getItemIdentifiers()), 
        'Expected item identifier not found!');
    } else {
      $this->fail('Expected only 1 occurrence!');
    }
  }
  
  /**
   * Tests if merging detects duplicate occurrences.
   */
  public function testDuplicateSuppressionOccurrence() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $occ1 = $topic1->createOccurrence($type, 'PHPTMAPI', parent::$dtString);
    $occ2 = $topic2->createOccurrence($type, 'PHPTMAPI', parent::$dtString);
    $occ3 = $topic2->createOccurrence($type, 'Occurrence', parent::$dtString);
    $this->assertEquals(count($topic1->getOccurrences()), 1, 'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($topic1->getOccurrences());
    $this->assertTrue(in_array($occ1->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertEquals(count($topic2->getOccurrences()), 2, 'Expected 2 occurrences!');
    $ids = $this->getIdsOfConstructs($topic2->getOccurrences());
    $this->assertTrue(in_array($occ2->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertTrue(in_array($occ3->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $topic1->mergeIn($topic2);
    $occurrences = $topic1->getOccurrences();
    $this->assertEquals(count($topic1->getOccurrences()), 2, 'Expected 2 occurrences!');
    if (count($occurrences === 2)) {
      $values = array();
      foreach ($occurrences as $occurrence) {
        $values[] = $occurrence->getValue();
      }
      $this->assertTrue(in_array('PHPTMAPI', $values, 'Unexpected value!'));
      $this->assertTrue(in_array('Occurrence', $values, 'Unexpected value!'));
    } else {
      $this->fail('Expected 2 occurrences!');
    }
  }
  
  /**
   * Tests if merging detects duplicate occurrences and sets the item 
   * identifier to the union of both occurrences.
   */
  public function testDuplicateSuppressionOccurrenceMoveItemIdentifiers() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $type = $tm->createTopic();
    $iid1 = 'http://example.org/iid-1';
    $iid2 = 'http://example.org/iid-2';
    $occ1 = $topic1->createOccurrence($type, 'PHPTMAPI', parent::$dtString);
    $occ1->addItemIdentifier($iid1);
    $this->assertEquals(count($topic1->getOccurrences()), 1, 'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($topic1->getOccurrences());
    $this->assertTrue(in_array($occ1->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertTrue(in_array($iid1, $occ1->getItemIdentifiers()), 
      'Expected item identifier!');
    $occ2 = $topic2->createOccurrence($type, 'PHPTMAPI', parent::$dtString);
    $occ2->addItemIdentifier($iid2);
    $this->assertEquals(count($topic2->getOccurrences()), 1, 'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($topic2->getOccurrences());
    $this->assertTrue(in_array($occ2->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertTrue(in_array($iid2, $occ2->getItemIdentifiers()), 
      'Expected item identifier!');
    $topic1->mergeIn($topic2);
    $occs = $topic1->getOccurrences();
    $this->assertEquals(count($occs), 1, 'Expected 1 occurrence!');
    if (count($occs) === 1) {
      $occ = $occs[0];
      $this->assertEquals(count($occ->getItemIdentifiers()), 2, 
        'Expected 2 item identifiers!');
      $this->assertTrue(in_array($iid1, $occ->getItemIdentifiers()), 
        'Expected item identifier!');
      $this->assertTrue(in_array($iid2, $occ->getItemIdentifiers()), 
        'Expected item identifier!');
      $this->assertEquals($occ->getValue(), 'PHPTMAPI', 'Unexpected value!');
    } else {
      $this->fail('Expected 1 occurrence!');
    }
  }
}
?>
