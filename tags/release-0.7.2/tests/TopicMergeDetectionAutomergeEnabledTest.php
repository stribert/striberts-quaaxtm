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
 * Tests if merging situations are detected.
 * 
 * These tests assume that http://tmapi.org/features/automerge/ is true. 
 * If set to false no assertions are made.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMergeDetectionAutomergeEnabledTest extends PHPTMAPITestCase
{
  private $_automerge;
  
  /**
   * @override
   */
  protected function setUp()
  {
    parent::setUp();
    try {
      $sys = $this->_sharedFixture;
      $this->_automerge = (boolean) $sys->getFeature('http://tmapi.org/features/automerge/');
    } catch (FeatureNotRecognizedException $e) {
      $this->_automerge = false;
    }
  }
  
  /**
   * @override
   */
  protected function tearDown()
  {
    parent::tearDown();
    $this->_automerge = false;
  }

  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  /**
   * Tests if adding a duplicate subject identifier is detected.
   */
  public function testExistingSubjectIdentifier()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $sid = 'http://phptmapi.sourceforge.net/';
    $topic1->addSubjectIdentifier($sid);
    $this->assertTrue(in_array($sid, $topic1->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertEquals($topic1->getId(), $tm->getTopicBySubjectIdentifier($sid)->getId(), 
      'Expected identity!');
    $topic2->addSubjectIdentifier($sid);// merging takes place
    $topics = $tm->getTopics();
    if (count($topics) === 1) {
      $topic = $topics[0];
      $this->assertTrue(in_array($sid, $topic->getSubjectIdentifiers()), 
        'Expected a certain subject identifier!');
    } else {
      $this->fail('Expected 1 topic!');
    }
  }
  
  /**
   * Tests if adding a duplicate subject identifier on the same topic is ignored.
   */
  public function testExistingSubjectIdentifierLegal()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $sid = 'http://phptmapi.sourceforge.net/';
    $topic->addSubjectIdentifier($sid);
    $this->assertTrue(in_array($sid, $topic->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertEquals($topic->getId(), $tm->getTopicBySubjectIdentifier($sid)->getId(), 
      'Expected identity!');
    $topic->addSubjectIdentifier($sid);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
  }
  
  /**
   * Tests if adding a duplicate subject locator is detected.
   */
  public function testExistingSubjectLocator()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $slo = 'http://phptmapi.sourceforge.net/';
    $topic1->addSubjectLocator($slo);
    $this->assertTrue(in_array($slo, $topic1->getSubjectLocators()), 
      'Expected a certain subject locator!');
    $this->assertEquals($topic1->getId(), $tm->getTopicBySubjectLocator($slo)->getId(), 
      'Expected identity!');
    $topic2->addSubjectLocator($slo);// merging takes place
    $topics = $tm->getTopics();
    if (count($topics) === 1) {
      $topic = $topics[0];
      $this->assertTrue(in_array($slo, $topic->getSubjectLocators()), 
        'Expected a certain subject locator!');
    } else {
      $this->fail('Expected 1 topic!');
    }
  }
  
  /**
   * Tests if adding a duplicate subject locator at the same topic is ignored.
   */
  public function testExistingSubjectLocatorLegal()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $slo = 'http://phptmapi.sourceforge.net/';
    $topic->addSubjectLocator($slo);
    $this->assertTrue(in_array($slo, $topic->getSubjectLocators()), 
      'Expected a certain subject locator!');
    $this->assertEquals($topic->getId(), $tm->getTopicBySubjectLocator($slo)->getId(), 
      'Expected identity!');
    $topic->addSubjectLocator($slo);
    $this->assertEquals(count($topic->getSubjectLocators()), 1, 
      'Expected 1 subject locator!');
  }
  
  /**
   * Tests if adding an item identifier equals to a subject identifier is detected.
   */
  public function testExistingSubjectIdentifierAddItemIdentifier()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $loc = 'http://phptmapi.sourceforge.net/';
    $topic1->addSubjectIdentifier($loc);
    $this->assertTrue(in_array($loc, $topic1->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertEquals($topic1->getId(), $tm->getTopicBySubjectIdentifier($loc)->getId(), 
      'Expected identity!');
    $topic2->addItemIdentifier($loc);// merging takes place
    $topics = $tm->getTopics();
    if (count($topics) === 1) {
      $topic = $topics[0];
      $this->assertTrue(in_array($loc, $topic->getSubjectIdentifiers()), 
        'Expected a certain subject identifier!');
    } else {
      $this->fail('Expected 1 topic!');
    }
  }
  
  /**
   * Tests if adding an item identifier equals to a subject identifier 
   * on the same topic is accepted.
   */
  public function testExistingSubjectIdentifierAddItemIdentifierLegal()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $sid = 'http://phptmapi.sourceforge.net/';
    $topic = $tm->createTopicBySubjectIdentifier($sid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $this->assertTrue(in_array($sid, $topic->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 0, 
      'Unexpected item identifier!');
    $this->assertEquals($topic->getId(), $tm->getTopicBySubjectIdentifier($sid)->getId(), 
      'Expected identity!');
    $this->assertNull($tm->getConstructByItemIdentifier($sid), 'Unexpected topic!');
    $topic->addItemIdentifier($sid);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $this->assertTrue(in_array($sid, $topic->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertTrue(in_array($sid, $topic->getItemIdentifiers()), 
      'Expected a certain item identifier!');
    $this->assertEquals($topic->getId(), $tm->getTopicBySubjectIdentifier($sid)->getId(), 
      'Expected identity!');
    $this->assertEquals($topic->getId(), $tm->getConstructByItemIdentifier($sid)->getId(), 
      'Expected identity!');
  }
  
  /**
   * Tests if adding a subject identifier equals to an item identifier is detected.
   */
  public function testExistingItemIdentifierAddSubjectIdentifier()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $loc = 'http://phptmapi.sourceforge.net/';
    $topic1->addItemIdentifier($loc);
    $this->assertTrue(in_array($loc, $topic1->getItemIdentifiers()), 
      'Expected a certain item identifier!');
    $this->assertEquals($topic1->getId(), $tm->getConstructByItemIdentifier($loc)->getId(), 
      'Expected identity!');
    $topic2->addSubjectIdentifier($loc);// merging takes place
    $topics = $tm->getTopics();
    if (count($topics) === 1) {
      $topic = $topics[0];
      $this->assertTrue(in_array($loc, $topic->getItemIdentifiers()), 
        'Expected a certain item identifier!');
    } else {
      $this->fail('Expected 1 topic!');
    }
  }
  
  /**
   * Tests if adding a subject identifier equals to an item identifier 
   * on the same topic is accepted.
   */
  public function testExistingItemIdentifierAddSubjectIdentifierLegal()
  {
    if (!$this->_automerge) return;
    $tm = $this->_topicMap;
    $iid = 'http://phptmapi.sourceforge.net/';
    $topic = $tm->createTopicByItemIdentifier($iid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $this->assertTrue(in_array($iid, $topic->getItemIdentifiers()), 
      'Expected a certain item identifier!');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 0, 
      'Unexpected subject identifier!');
    $this->assertEquals($topic->getId(), $tm->getConstructByItemIdentifier($iid)->getId(), 
      'Expected identity!');
    $this->assertNull($tm->getTopicBySubjectIdentifier($iid), 'Unexpected topic!');
    $topic->addSubjectIdentifier($iid);
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier!');
    $this->assertTrue(in_array($iid, $topic->getSubjectIdentifiers()), 
      'Expected a certain subject identifier!');
    $this->assertTrue(in_array($iid, $topic->getItemIdentifiers()), 
      'Expected a certain item identifier!');
    $this->assertEquals($topic->getId(), $tm->getTopicBySubjectIdentifier($iid)->getId(), 
      'Expected identity!');
    $this->assertEquals($topic->getId(), $tm->getConstructByItemIdentifier($iid)->getId(), 
      'Expected identity!');
  }
}
?>
