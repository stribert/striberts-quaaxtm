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
 * Tests if the engine respects the constraint if a {@link Topic} is removable 
 * or not.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicRemovableConstraintTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  /**
   * Tests if the topic removable constraint is respected if a topic 
   * is used as type.
   * 
   * @param Typed A typed construct.
   * @return void
   */
  private function _testTyped(Typed $typed)
  {
    $tm = $this->_topicMap;
    $topicCount = count($tm->getTopics());
    $formerType = $typed->getType();
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $typed->setType($topic);
    try {
      $topic->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($topic->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $typed->setType($formerType);
    $topic->remove();
    $this->assertEquals(count($tm->getTopics()), $topicCount, 
      'Unexpected topics count!');
  }
  
  /**
   * Tests if the topic removable constraint is respected if a topic 
   * is used as theme.
   * 
   * @param Scoped A scoped construct.
   * @return void
   */
  private function _testScoped(Scoped $scoped)
  {
    $tm = $this->_topicMap;
    $topicCount = count($tm->getTopics());
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $scoped->addTheme($topic);
    try {
      $topic->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($topic->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $scoped->removeTheme($topic);
    $topic->remove();
    $this->assertEquals(count($tm->getTopics()), $topicCount, 
      'Unexpected topics count!');
  }
  
  /**
   * Tests if the topic removable constraint is respected if a topic 
   * is used as reifier.
   * 
   * @param Reifiable A reifiable that is not reified.
   */
  private function _testReifiable(Reifiable $reifiable)
  {
    $tm = $this->_topicMap;
    $this->assertNull($reifiable->getReifier(), 'Unexpected reifier!');
    $topicCount = count($tm->getTopics());
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $reifiable->setReifier($topic);
    try {
      $topic->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($topic->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $this->assertEquals(count($tm->getTopics()), $topicCount+1, 
      'Unexpected topics count!');
    $reifiable->setReifier(null);
    $topic->remove();
    $this->assertEquals(count($tm->getTopics()), $topicCount, 
      'Unexpected topics count!');
  }
  
  public function testUsedAsTopicMapReifier()
  {
    $this->_testReifiable($this->_topicMap);
  }
  
  public function testUsedAsAssociationType()
  {
    $this->_testTyped($this->_createAssoc());
  }
  
  public function testUsedAsAssociationTheme()
  {
    $this->_testScoped($this->_createAssoc());
  }
  
  public function testUsedAsAssociationReifier()
  {
    $this->_testReifiable($this->_createAssoc());
  }
  
  public function testUsedAsRoleType()
  {
    $this->_testTyped($this->_createRole());
  }
  
  public function testUsedAsRoleReifier()
  {
    $this->_testReifiable($this->_createRole());
  }
  
  public function testUsedAsOccurrenceType()
  {
    $this->_testTyped($this->_createOcc());
  }
  
  public function testUsedAsOccurrenceTheme()
  {
    $this->_testScoped($this->_createOcc());
  }
  
  public function testUsedAsOccurrenceReifier()
  {
    $this->_testReifiable($this->_createOcc());
  }
  
  public function testUsedAsNameType()
  {
    $this->_testTyped($this->_createName());
  }
  
  public function testUsedAsNameTheme()
  {
    $this->_testScoped($this->_createName());
  }
  
  public function testUsedAsNameReifier()
  {
    $this->_testReifiable($this->_createName());
  }
  
  public function testUsedAsVariantTheme()
  {
    $this->_testScoped($this->_createVariant());
  }
  
  public function testUsedAsVariantReifier()
  {
    $this->_testReifiable($this->_createVariant());
  }
  
  /**
   * Tests if the removable constraint is respected if a topic is 
   * used as topic type.
   */
  public function testUsedAsTopicType()
  {
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $topic2->addType($topic1);
    try {
      $topic1->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($topic1->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $topic2->removeType($topic1);
    $topic1->remove();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
  }
  
  /**
   * Tests if the removable constraint is respected if a topic is 
   * used as player.
   */
  public function testUsedAsPlayer()
  {
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $topic->remove();
    $this->assertEquals(count($tm->getTopics()), 0, 'Expected 0 topics!');
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $assoc = $this->_createAssoc();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
    $role = $assoc->createRole($tm->createTopic(), $topic);
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    try {
      $topic->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($topic->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $role->setPlayer($tm->createTopic());
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $topic->remove();
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
  }
  
  public function testUsedAsThemeScopeUnused()
  {
    $tm = $this->_topicMap;
    $theme = $tm->createTopic();
    $variant = $this->_createVariant();
    $scope = $variant->getScope();
    $countThemes = count($scope);
    $variant->addTheme($theme);
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), $countThemes+1, 'Expected 2 themes!');
    try {
      $theme->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    $variant->remove();
    try {
      $theme->remove();
      $this->assertTrue(is_null($theme->getId()), 'Topic must be removed!');
    } catch (TopicInUseException $e) {
      $this->fail('Removal of topic must be allowed!');
    }
  }
}
?>
