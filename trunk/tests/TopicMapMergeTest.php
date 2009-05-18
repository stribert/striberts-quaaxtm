<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
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
 * Topic map merge tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapMergeTest extends PHPTMAPITestCase {
  
  protected static $tmLocator2 = 'http://localhost/tm/2';
  
  private $tm1,
          $tm2;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->tm1 = $this->topicMap;
    $this->tm2 = $this->sharedFixture->createTopicMap(self::$tmLocator2);
  }
  
  /**
   * @override
   */
  public function tearDown() {
    parent::tearDown();
    $this->tm2 = null;
  }

  public function testTopicMap() {
    $this->assertTrue($this->tm1 instanceof TopicMap);
    $this->assertTrue($this->tm2 instanceof TopicMap);
  }
  
  /**
   * Tests if $tm->mergeIn($tm) is ignored.
   */
  public function testTopicMapMergeNoop() {
    $sys = $this->sharedFixture;
    $locator = 'http://localhost/tm/3';
    $tm = $sys->createTopicMap($locator);
    $this->assertEquals($tm->getId(), $sys->getTopicMap($locator)->getId(), 
      'Expected identity!');
    $tm->mergeIn($sys->getTopicMap($locator));
    $this->assertEquals($tm->getId(), $sys->getTopicMap($locator)->getId(), 
      'Expected identity!');
  }
  
  /**
   * Tests merging of topics by equal item identifiers.
   */
  public function testMergeByItemIdentifier() {
    $iid = 'http://localhost/t/1';
    $topic1 = $this->tm1->createTopicByItemIdentifier($iid);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicByItemIdentifier($iid);
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getConstructByItemIdentifier($iid)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic1->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $iids = $topic1->getItemIdentifiers();
    $_iid = $iids[0];
    $this->assertEquals($_iid, $iid, 'Expected identity!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
    
    // merge must not have any side effects on tm2
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getConstructByItemIdentifier($iid)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic2->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $iids = $topic2->getItemIdentifiers();
    $_iid = $iids[0];
    $this->assertEquals($_iid, $iid, 'Expected identity!');
    $this->assertEquals(count($topic2->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic2->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
  }
  
  /**
   * Tests merging of topics by equal subject identifiers.
   */
  public function testMergeBySubjectIdentifier() {
    $sid = 'http://phptmapi.sourceforge.net/';
    $topic1 = $this->tm1->createTopicBySubjectIdentifier($sid);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicBySubjectIdentifier($sid);
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getTopicBySubjectIdentifier($sid)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $sids = $topic1->getSubjectIdentifiers();
    $_sid = $sids[0];
    $this->assertEquals($_sid, $sid, 'Expected identity!');
    $this->assertEquals(count($topic1->getItemIdentifiers()), 0, 
      'Expected 0 item identifiers!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
    
    // merge must not have any side effects on tm2
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getTopicBySubjectIdentifier($sid)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic2->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $sids = $topic2->getSubjectIdentifiers();
    $_sid = $sids[0];
    $this->assertEquals($_sid, $sid, 'Expected identity!');
    $this->assertEquals(count($topic2->getItemIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic2->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
  }
  
  /**
   * Tests merging of topics by equal subject locators.
   */
  public function testMergeBySubjectLocator() {
    $slo = 'http://phptmapi.sourceforge.net/';
    $topic1 = $this->tm1->createTopicBySubjectLocator($slo);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicBySubjectLocator($slo);
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getTopicBySubjectLocator($slo)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 1, 
      'Expected 1 subject locator!');
    $slos = $topic1->getSubjectLocators();
    $_slo = $slos[0];
    $this->assertEquals($_slo, $slo, 'Expected identity!');
    $this->assertEquals(count($topic1->getItemIdentifiers()), 0, 
      'Expected 0 item identifiers!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    
    // merge must not have any side effects on tm2
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getTopicBySubjectLocator($slo)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic2->getSubjectLocators()), 1, 
      'Expected 1 subject locator!');
    $slos = $topic2->getSubjectLocators();
    $_slo = $slos[0];
    $this->assertEquals($_slo, $slo, 'Expected identity!');
    $this->assertEquals(count($topic2->getItemIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic2->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
  }
  
  /**
   * Tests merging of topics by existing topic with item identifier equals 
   * to a topic's subject identifier from the other map.
   */
  public function testMergeItemIdentifierEqualSubjectIdentifier() {
    $loc = 'http://phptmapi.sourceforge.net/';
    $topic1 = $this->tm1->createTopicByItemIdentifier($loc);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicBySubjectIdentifier($loc);
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getConstructByItemIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertNull($this->tm1->getTopicBySubjectIdentifier($loc), 'Unexpected topic!');
    
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getConstructByItemIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getTopicBySubjectIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic1->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $sids = $topic1->getSubjectIdentifiers();
    $this->assertTrue(in_array($loc, $sids), 'Expected certain subject identifier!');
    $iids = $topic1->getItemIdentifiers();
    $this->assertTrue(in_array($loc, $iids), 'Expected certain item identifier!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
    
    // merge must not have any side effects on tm2
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getTopicBySubjectIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertNull($this->tm2->getConstructByItemIdentifier($loc), 
      'Unexpected construct!');
    $this->assertEquals(count($topic2->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $sids = $topic2->getSubjectIdentifiers();
    $this->assertTrue(in_array($loc, $sids), 'Expected certain subject identifier!');
    $this->assertEquals(count($topic2->getItemIdentifiers()), 0, 
      'Expected 0 item identifiers!');
    $this->assertEquals(count($topic2->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
  }
  
  /**
   * Tests merging of topics by existing topic with subject identifier equals 
   * to a topic's item identifier from the other map.
   */
  public function testMergeSubjectIdentifierEqualItemIdentifier() {
    $loc = 'http://phptmapi.sourceforge.net/';
    $topic1 = $this->tm1->createTopicBySubjectIdentifier($loc);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicByItemIdentifier($loc);
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getTopicBySubjectIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertNull($this->tm1->getConstructByItemIdentifier($loc), 
      'Unexpected construct!');
    
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getConstructByItemIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getTopicBySubjectIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertEquals(count($topic1->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $sids = $topic1->getSubjectIdentifiers();
    $this->assertTrue(in_array($loc, $sids), 'Expected certain subject identifier!');
    $iids = $topic1->getItemIdentifiers();
    $this->assertTrue(in_array($loc, $iids), 'Expected certain item identifier!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
    
    // merge must not have any side effects on tm2
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getConstructByItemIdentifier($loc)->getId(), 'Expected identity!');
    $this->assertNull($this->tm2->getTopicBySubjectIdentifier($loc), 'Unexpected topic!');
    $this->assertEquals(count($topic2->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $iids = $topic2->getItemIdentifiers();
    $this->assertTrue(in_array($loc, $iids), 'Expected certain item identifier!');
    $this->assertEquals(count($topic2->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic2->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
  }
  
  /**
   * Tests if topics are added to a topic map from another topic map.
   */
  public function testAddTopicsFromOtherMap() {
    $loc1 = 'http://phptmapi.sourceforge.net/#iid-1';
    $loc2 = 'http://phptmapi.sourceforge.net/#iid-2';
    $topic1 = $this->tm1->createTopicByItemIdentifier($loc1);
    $topic2 = $this->tm2->createTopicByItemIdentifier($loc2);
    
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic1->getId(), 
      $this->tm1->getConstructByItemIdentifier($loc1)->getId(), 'Expected identity!');
    $this->assertNull($this->tm1->getConstructByItemIdentifier($loc2), 
      'Unexpected construct!');
      
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals($topic2->getId(), 
      $this->tm2->getConstructByItemIdentifier($loc2)->getId(), 'Expected identity!');
    $this->assertNull($this->tm2->getConstructByItemIdentifier($loc1), 
      'Unexpected construct!');
      
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 2, 'Expected 2 topics!');
    $iids = $topic1->getItemIdentifiers();
    $this->assertEquals(count($iids), 1, 'Expected 1 item identifier!');
    $this->assertTrue(in_array($loc1, $iids), 'Expected certain item identifier!');
    $this->assertEquals(count($topic1->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($topic1->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
    
    $newTopic = $this->tm1->getConstructByItemIdentifier($loc2);
    $this->assertTrue($newTopic instanceof Topic, 'Expected topic!');
    $this->assertTrue(in_array($loc2, $newTopic->getItemIdentifiers()), 
      'Expected certain item identifier!');
    $this->assertEquals(count($newTopic->getSubjectIdentifiers()), 0, 
      'Expected 0 subject identifiers!');
    $this->assertEquals(count($newTopic->getSubjectLocators()), 0, 
      'Expected 0 subject locators!');
  }
  
  public function testMergeTypes() {
    $topic1 = $this->tm1->createTopic();
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopic();
    $topic3 = $this->tm2->createTopic();
    $type = $this->tm2->createTopicBySubjectIdentifier('http://google.com');
    $topic2->addType($type);
    $topic3->addType($type);
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($topic2->getTypes()), 1, 'Expected 1 type!');
    $this->assertEquals(count($topic3->getTypes()), 1, 'Expected 1 type!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 4, 'Expected 4 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($topic2->getTypes()), 1, 'Expected 1 type!');
    $this->assertEquals(count($topic3->getTypes()), 1, 'Expected 1 type!');
    $types = array();
    $topics = $this->tm1->getTopics();
    foreach ($topics as $topic) {
      $_types = $topic->getTypes();
      $types = array_merge($types, $_types);
    }
    $this->assertEquals(count($types), 2, 'Expected 2 types!');
  }
  
  public function testMergeTypes2() {
    $topic1 = $this->tm1->createTopic();
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopic();
    $type = $this->tm2->createTopic();
    $topic2->addType($type);
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $this->assertEquals(count($topic2->getTypes()), 1, 'Expected 1 type!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $this->assertEquals(count($topic2->getTypes()), 1, 'Expected 1 type!');
    $types = array();
    $topics = $this->tm1->getTopics();
    foreach ($topics as $topic) {
      $_types = $topic->getTypes();
      $types = array_merge($types, $_types);
    }
    $this->assertEquals(count($types), 1, 'Expected 1 type!');
  }
  
  public function testMergeName() {
    $topic1 = $this->tm1->createTopic();
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopic();
    $topic2->createName('Name');// creates default name type
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $this->assertEquals(count($topic2->getNames()), 1, 'Expected 1 name!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $names = array();
    $topics = $this->tm1->getTopics();
    foreach ($topics as $topic) {
      $_names = $topic->getNames();
      $names = array_merge($names, $_names);
    }
    $this->assertEquals(count($names), 1, 'Expected 1 name!');
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Name', 'Unexpected name value!');
  }
  
  public function testMergeOccurrence() {
    $topic1 = $this->tm1->createTopic();
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopic();
    $type = $this->tm2->createTopic();
    $reifier = $this->tm2->createTopic();
    $occ = $topic2->createOccurrence($type, 'Occurrence', parent::$dtString);
    $occ->setReifier($reifier);
    $this->assertEquals(count($topic2->getOccurrences()), 1, 'Expected 1 occurrence!');
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 4, 'Expected 4 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $occs = array();
    $topics = $this->tm1->getTopics();
    foreach ($topics as $topic) {
      $_occs = $topic->getOccurrences();
      $occs = array_merge($occs, $_occs);
    }
    $this->assertEquals(count($occs), 1, 'Expected 1 occurrence!');
    $occ = $occs[0];
    $this->assertEquals($occ->getValue(), 'Occurrence', 'Unexpected occurrence value!');
    $this->assertEquals($occ->getDatatype(), parent::$dtString, 
      'Unexpected occurrence datatype!');
    $this->assertTrue($occ->getReifier() instanceof Topic, 'Expected reifier!');
  }
  
  public function testMergeOccurrence2() {
    $topic1 = $this->tm1->createTopic();
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $topic2 = $this->tm2->createTopicBySubjectIdentifier('http://google.com');
    $type = $this->tm2->createTopicByItemidentifier('http://localhost/t/1');
    $topic2->createOccurrence($type, 'Occurrence', parent::$dtString);
    $topic2->createOccurrence($type, 'Occurrence 2', parent::$dtString);
    $this->assertEquals(count($topic2->getOccurrences()), 2, 'Expected 2 occurrences!');
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 2, 'Expected 2 topics!');
    $occs = array();
    $topics = $this->tm1->getTopics();
    foreach ($topics as $topic) {
      $_occs = $topic->getOccurrences();
      $occs = array_merge($occs, $_occs);
    }
    $this->assertEquals(count($occs), 2, 'Expected 2 occurrences!');
    $formerTopic2 = $this->tm1->getTopicBySubjectIdentifier('http://google.com');
    $this->assertTrue($formerTopic2 instanceof Topic, 'Expected a topic!');
    $occs = array();
    $occs = $formerTopic2->getOccurrences();
    $this->assertEquals(count($occs), 2, 'Expected 2 occurrences!');
    $occ1 = $occs[0];
    $occ2 = $occs[1];
    $type1 = $occ1->getType();
    $type2 = $occ2->getType();
    $this->assertEquals($type1->getId(), $type2->getId(), 'Expected identity!');
    $this->assertEquals($type1->getParent()->getId(), $type2->getParent()->getId(), 
      'Expected identity!');
    $this->assertTrue(in_array('http://localhost/t/1', $type1->getItemIdentifiers()), 
      'Expected certain item identifier!');
    $this->assertTrue(in_array('http://localhost/t/1', $type2->getItemIdentifiers()), 
      'Expected certain item identifier!');
    $values = array($occ1->getValue(), $occ2->getValue());
    $this->assertTrue(in_array('Occurrence', $values), 'Expected certain value!');
    $this->assertTrue(in_array('Occurrence 2', $values), 'Expected certain value!');
    $this->assertEquals($occ1->getDatatype(), parent::$dtString, 
      'Unexpected occurrence datatype!');
    $this->assertEquals($occ2->getDatatype(), parent::$dtString, 
      'Unexpected occurrence datatype!');
      
    // merge has no effect on merged tm 2
    $topic2 = $this->tm2->getTopicBySubjectIdentifier('http://google.com');
    $this->assertTrue($topic2 instanceof Topic, 'Expected a topic!');
    $occs = array();
    $occs = $topic2->getOccurrences();
    $this->assertEquals(count($occs), 2, 'Expected 2 occurrences!');
    $occ1 = $occs[0];
    $occ2 = $occs[1];
    $type1 = $occ1->getType();
    $type2 = $occ2->getType();
    $this->assertEquals($type1->getId(), $type2->getId(), 'Expected identity!');
    $this->assertEquals($type1->getParent()->getId(), $type2->getParent()->getId(), 
      'Expected identity!');
    $this->assertTrue(in_array('http://localhost/t/1', $type1->getItemIdentifiers()), 
      'Expected certain item identifier!');
    $this->assertTrue(in_array('http://localhost/t/1', $type2->getItemIdentifiers()), 
      'Expected certain item identifier!');
    $values = array($occ1->getValue(), $occ2->getValue());
    $this->assertTrue(in_array('Occurrence', $values), 'Expected certain value!');
    $this->assertTrue(in_array('Occurrence 2', $values), 'Expected certain value!');
    $this->assertEquals($occ1->getDatatype(), parent::$dtString, 
      'Unexpected occurrence datatype!');
    $this->assertEquals($occ2->getDatatype(), parent::$dtString, 
      'Unexpected occurrence datatype!');
  }
  
  public function testMergeReifier() {
    $reifier1 = $this->tm1->createTopicByItemidentifier('http://localhost/t/1');
    $reifier2 = $this->tm2->createTopicByItemidentifier('http://localhost/t/2');
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $this->tm1->setReifier($reifier1);
    $this->tm2->setReifier($reifier2);
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 1, 'Expected 1 topic!');//reifiers are merged
    $this->assertEquals(count($this->tm2->getTopics()), 1, 'Expected 1 topic!');
    $_reifier1 = $this->tm1->getReifier();
    $this->assertTrue($_reifier1 instanceof Topic, 'Expected reifier!');
    $_reifier2 = $this->tm2->getReifier();
    $this->assertTrue($_reifier2 instanceof Topic, 'Expected reifier!');
    $iids1 = $_reifier1->getItemIdentifiers();
    $this->assertTrue(in_array('http://localhost/t/1', $iids1), 
      'Expected certain item identifier!');
    $this->assertTrue(in_array('http://localhost/t/2', $iids1), 
      'Expected certain item identifier!');
    $iids2 = $_reifier2->getItemIdentifiers();
    $this->assertTrue(in_array('http://localhost/t/2', $iids2), 
      'Expected certain item identifier!');
  }
  
  public function testMergeAssociation() {
    $type = $this->tm2->createTopic();
    $scope = array($this->tm2->createTopic(), $this->tm2->createTopic());
    $assoc = $this->tm2->createAssociation($type, $scope);
    $this->assertEquals(count($this->tm1->getTopics()), 0, 'Expected 0 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm2->getAssociations()), 1, 'Expected 1 association!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm1->getAssociations()), 1, 'Expected 1 association!');
    $assocs = $this->tm1->getAssociations();
    $assoc = $assocs[0];
    $this->assertTrue($assoc->getType() instanceof Topic, 'Expected association type!');
    $this->assertEquals(count($assoc->getScope()), 2, 'Expected 2 themes!');
    
    // merge has no effect on merged tm 2
    $this->assertEquals(count($this->tm2->getTopics()), 3, 'Expected 3 topics!');
    $this->assertEquals(count($this->tm2->getAssociations()), 1, 'Expected 1 association!');
    $_assocs = $this->tm2->getAssociationsByType($type);
    $_assoc = $_assocs[0];
    $this->assertTrue($_assoc->getType() instanceof Topic, 'Expected association type!');
    $this->assertEquals(count($_assoc->getScope()), 2, 'Expected 2 themes!');
  }
  
  public function testMergeAssociationRole() {
    $assocType = $this->tm2->createTopic();
    $scope = array($this->tm2->createTopic(), $this->tm2->createTopic());
    $assoc = $this->tm2->createAssociation($assocType, $scope);
    $role = $assoc->createRole($this->tm2->createTopic(), $this->tm2->createTopic());
    $this->assertEquals(count($this->tm1->getTopics()), 0, 'Expected 0 topics!');
    $this->assertEquals(count($this->tm2->getTopics()), 5, 'Expected 5 topics!');
    $this->assertEquals(count($this->tm2->getAssociations()), 1, 'Expected 1 association!');
    $this->assertEquals(count($assoc->getRoles()), 1, 'Expected 1 association role!');
    $this->tm1->mergeIn($this->tm2);
    $this->assertEquals(count($this->tm1->getTopics()), 5, 'Expected 5 topics!');
    $this->assertEquals(count($this->tm1->getAssociations()), 1, 'Expected 1 association!');
    $assoc = null;
    $assocs = $this->tm1->getAssociations();
    $assoc = $assocs[0];
    $this->assertTrue($assoc->getType() instanceof Topic, 'Expected association type!');
    $this->assertEquals(count($assoc->getScope()), 2, 'Expected 2 themes!');
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1, 'Expected 1 association role!');
    $role = $roles[0];
    $this->assertTrue($role->getType() instanceof Topic, 'Expected role type!');
    $this->assertTrue($role->getPlayer() instanceof Topic, 'Expected role player!');
    
    // merge has no effect on merged tm 2
    $this->assertEquals(count($this->tm2->getTopics()), 5, 'Expected 5 topics!');
    $this->assertEquals(count($this->tm2->getAssociations()), 1, 'Expected 1 association!');
    $_assocs = $this->tm2->getAssociationsByType($assocType);
    $_assoc = $_assocs[0];
    $this->assertTrue($_assoc->getType() instanceof Topic, 'Expected association type!');
    $this->assertEquals(count($_assoc->getScope()), 2, 'Expected 2 themes!');
    $this->assertEquals(count($_assoc->getRoles()), 1, 'Expected 1 association role!');
  }
}
?>
