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
class QTMCacheTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  public function testTopics()
  {
    $tm = $this->_topicMap;
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
  
  public function testAssociations()
  {
    $tm = $this->_topicMap;
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
  
  public function testTopicsClearCache()
  {
    $tm = $this->_topicMap;
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
  
  public function testAssociationsClearCache()
  {
    $tm = $this->_topicMap;
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
  
  public function testTopicMapsLocator()
  {
    $loc = 'http://localhost/tm' . uniqid();
    $tm = $this->_sharedFixture->createTopicMap($loc);
    $this->assertTrue($tm instanceof TopicMap);
    $locFirst = $tm->getLocator();
    $this->assertEquals($loc, $locFirst, 'Expected identity!');
    $locSecond = $tm->getLocator();
    $this->assertEquals($loc, $locSecond, 'Expected identity!');
    $tm->remove();
  }
  
  public function testSeenConstructsCache()
  {
    $topic = $this->_topicMap->createTopic();
    $id = $topic->getId();
    $topic = $this->_topicMap->getConstructById($id);
    $this->assertTrue($topic instanceof Topic, 'Expected topic!');
    $this->assertEquals($topic->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $topic->remove();
    $topic = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($topic), 'Unexpected topic!');
    
    $topic = $this->_topicMap->createTopic();
    
    $name = $topic->createName('Name');
    $id = $name->getId();
    $name = $this->_topicMap->getConstructById($id);
    $this->assertTrue($name instanceof Name, 'Expected topic name!');
    $this->assertEquals($name->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $name->remove();
    $name = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($name), 'Unexpected topic name!');
    
    $name = $topic->createName('Name');
    $variant = $name->createVariant(
    	'Nom', 
      parent::$_dtString, 
      array($this->_topicMap->createTopic())
    );
    $id = $variant->getId();
    $variant = $this->_topicMap->getConstructById($id);
    $this->assertTrue($variant instanceof IVariant, 'Expected variant!');
    $this->assertEquals($variant->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $variant->remove();
    $variant = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($variant), 'Unexpected variant!');
    
    $occ = $topic->createOccurrence(
      $this->_topicMap->createTopic(), 
      'http://phptmapi.sourceforge.net/', 
      parent::$_dtUri
    );
    $id = $occ->getId();
    $occ = $this->_topicMap->getConstructById($id);
    $this->assertTrue($occ instanceof Occurrence, 'Expected occurrence!');
    $this->assertEquals($occ->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $occ->remove();
    $occ = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($occ), 'Unexpected occurrence!');
    
    $assoc = $this->_topicMap->createAssociation($this->_topicMap->createTopic());
    $id = $assoc->getId();
    $assoc = $this->_topicMap->getConstructById($id);
    $this->assertTrue($assoc instanceof Association, 'Expected association!');
    $this->assertEquals($assoc->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $assoc->remove();
    $assoc = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($assoc), 'Unexpected association!');
    
    $assoc = $this->_topicMap->createAssociation($this->_topicMap->createTopic());
    $role = $assoc->createRole(
      $this->_topicMap->createTopic(),
      $this->_topicMap->createTopic()
    );
    $id = $role->getId();
    $role = $this->_topicMap->getConstructById($id);
    $this->assertTrue($role instanceof Role, 'Expected association role!');
    $this->assertEquals($role->getTopicMap(), $this->_topicMap, 'Expected identity!');
    $role->remove();
    $role = $this->_topicMap->getConstructById($id);
    $this->assertTrue(is_null($role), 'Unexpected association role!');
  }
}
?>