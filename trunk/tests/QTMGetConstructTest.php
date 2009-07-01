<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * QuaaxTM get construct tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMGetConstructTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testGetConstructById() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $retrievedTopic = $tm->getConstructById($topic->getId());
    $this->assertEquals($topic->getId(), $retrievedTopic->getId(), 'Expected identity!');
    
  }
  
  public function testGetConstructByIdInvalid() {
    $unknown = $this->topicMap->getConstructById(uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $this->topicMap->getConstructById('TopicImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('TopicMapImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('AssociationImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('RoleImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('NameImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('OccurrenceImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('VariantImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $this->topicMap->getConstructById('TopicImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('TopicMapImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('AssociationImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('RoleImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('NameImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('OccurrenceImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $this->topicMap->getConstructById('VariantImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
  }
}
?>