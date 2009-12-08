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
 * QuaaxTM children cache tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMCacheTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testTopics() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $topic3 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $topic4 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $topic1->remove();
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $topic2->remove();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
  }
  
  public function testAssociations() {
    $tm = $this->topicMap;
    $assoc1 = $tm->createAssociation($tm->createTopic());
    $assoc2 = $tm->createAssociation($tm->createTopic());
    $assoc3 = $tm->createAssociation($tm->createTopic());
    $this->assertEquals(count($tm->getAssociations()), 3, 'Expected 3 associations!');
    $assoc4 = $tm->createAssociation($tm->createTopic());
    $this->assertEquals(count($tm->getAssociations()), 4, 'Expected 4 associations!');
    $assoc1->remove();
    $this->assertEquals(count($tm->getAssociations()), 3, 'Expected 3 associations!');
    $assoc2->remove();
    $this->assertEquals(count($tm->getAssociations()), 2, 'Expected 2 associations!');
  }
  
  public function testTopicsClearCache() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $topic2 = $tm->createTopic();
    $topic3 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $tm->clearTopicsCache();
    $topic4 = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 4, 'Expected 4 topics!');
    $tm->clearTopicsCache();
    $topic1->remove();
    $this->assertEquals(count($tm->getTopics()), 3, 'Expected 3 topics!');
    $tm->clearTopicsCache();
    $topic2->remove();
    $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
  }
  
  public function testAssociationsClearCache() {
    $tm = $this->topicMap;
    $assoc1 = $tm->createAssociation($tm->createTopic());
    $assoc2 = $tm->createAssociation($tm->createTopic());
    $assoc3 = $tm->createAssociation($tm->createTopic());
    $this->assertEquals(count($tm->getAssociations()), 3, 'Expected 3 associations!');
    $tm->clearAssociationsCache();
    $assoc4 = $tm->createAssociation($tm->createTopic());
    $this->assertEquals(count($tm->getAssociations()), 4, 'Expected 4 associations!');
    $tm->clearAssociationsCache();
    $assoc1->remove();
    $this->assertEquals(count($tm->getAssociations()), 3, 'Expected 3 associations!');
    $tm->clearAssociationsCache();
    $assoc2->remove();
    $this->assertEquals(count($tm->getAssociations()), 2, 'Expected 2 associations!');
  }
}
?>