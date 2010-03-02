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
    $retrievedTm = $tm->getConstructById($tm->getId());
    $this->assertEquals($tm->getId(), $retrievedTm->getId(), 'Expected identity!');
    $assoc = $this->createAssoc();
    $retrievedAssoc = $tm->getConstructById($assoc->getId());
    $this->assertEquals($assoc->getId(), $retrievedAssoc->getId(), 'Expected identity!');
    $role = $this->createRole();
    $retrievedRole = $tm->getConstructById($role->getId());
    $this->assertEquals($role->getId(), $retrievedRole->getId(), 'Expected identity!');
    $occ = $this->createOcc();
    $retrievedOcc = $tm->getConstructById($occ->getId());
    $this->assertEquals($occ->getId(), $retrievedOcc->getId(), 'Expected identity!');
    $name = $this->createName();
    $retrievedName = $tm->getConstructById($name->getId());
    $this->assertEquals($name->getId(), $retrievedName->getId(), 'Expected identity!');
    $variant = $this->createVariant();
    $retrievedVariant = $tm->getConstructById($variant->getId());
    $this->assertEquals($variant->getId(), $retrievedVariant->getId(), 'Expected identity!');
  }
  
  public function testGetConstructByIdInvalid() {
    $tm = $this->topicMap;
    $tm->createTopic();
    $this->createAssoc();
    $this->createName();
    $this->createOcc();
    $this->createRole();
    $this->createVariant();
    
    $unknown = $tm->getConstructById(uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $tm->getConstructById('TopicImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('TopicMapImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('AssociationImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('RoleImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('NameImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('OccurrenceImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('VariantImpl-' . uniqid());
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $tm->getConstructById('TopicImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('TopicMapImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('AssociationImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('RoleImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('NameImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('OccurrenceImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('VariantImpl-' . null);
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $tm->getConstructById('TopicImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('TopicMapImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('AssociationImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('RoleImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('NameImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('OccurrenceImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('VariantImpl-0');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $tm->getConstructById('TopicImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('TopicMapImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('AssociationImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('RoleImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('NameImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('OccurrenceImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById('VariantImpl-a-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
    $unknown = $tm->getConstructById(uniqid() . '-1');
    $this->assertTrue(is_null($unknown), 'Unexpected construct!');
  }
}
?>