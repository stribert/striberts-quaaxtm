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
 * Occurrence tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class OccurrenceTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $tm = $this->topicMap;
    $parent = $tm->createTopic();
    $this->assertEquals(count($parent->getOccurrences()), 0, 
      'Expected new topic to be created without occurrences!');
    $occ = $parent->createOccurrence($tm->createTopic(), 
      'http://phptmapi.sourceforge.net/', parent::$dtUri);
    $this->assertEquals($occ->getParent()->getId(), $parent->getId());
    $this->assertEquals(count($parent->getOccurrences()), 1, 
      'Expected 1 occurrence!');
    $ids = $this->getIdsOfConstructs($parent->getOccurrences());
    $this->assertTrue(in_array($occ->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $occ->remove();
    $this->assertEquals(count($parent->getOccurrences()), 0, 
      'Expected 0 occurrences after removal!');
  }
  
  public function testType() {
    $occ = $this->createOcc();
    $this->assertTrue($occ instanceof Occurrence);
    $type1 = $this->topicMap->createTopic();
    $type2 = $this->topicMap->createTopic();
    $occ->setType($type1);
    $occType = $occ->getType();
    $this->assertEquals($occType->getId(), $type1->getId());
    $occ->setType($type2);
    $occType = $occ->getType();
    $this->assertEquals($occType->getId(), $type2->getId());
  }
  
  public function testValueDatatype() {
    $tm = $this->topicMap;
    $parent = $tm->createTopic();
    try {
      $parent->createOccurrence($tm->createTopic(), null, parent::$dtUri);
      $this->fail('null is not allowed as value!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $parent->createOccurrence($tm->createTopic(), 
        'http://phptmapi.sourceforge.net/', null);
      $this->fail('null is not allowed as datatype!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $parent->createOccurrence($tm->createTopic(), null, null);
      $this->fail('Occurrences have a value and a datatype != null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    $occ = $parent->createOccurrence($tm->createTopic(), 
      'http://phptmapi.sourceforge.net/', parent::$dtUri);
    $this->assertEquals($occ->getValue(), 'http://phptmapi.sourceforge.net/', 
      'Values are different!');
    $this->assertEquals($occ->getDatatype(), parent::$dtUri, 'Datatypes are different!');
    $occ->setValue('http://localhost/', parent::$dtUri);
    $this->assertEquals($occ->getValue(), 'http://localhost/', 'Values are different!');
  }
  
  public function testScope() {
    $occ = $this->createOcc();
    $this->assertTrue($occ instanceof Occurrence);
    $tm = $this->topicMap;
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $occ->addTheme($theme1);
    $occ->addTheme($theme2);
    $this->assertEquals(count($occ->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($occ->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $occ = $tm->createTopic()->createOccurrence($tm->createTopic(), 
      'http://phptmapi.sourceforge.net/', parent::$dtUri, array($theme1, $theme2));
    $this->assertEquals(count($occ->getScope()), 2);
    $ids = $this->getIdsOfConstructs($occ->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
  }
  
  public function testDuplicates() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $occTheme = $tm->createTopic();
    $occType = $tm->createTopic();
    $topic->createOccurrence($occType, 'Occurrence', parent::$dtString, 
      array($occTheme));
    $occurrences = $topic->getOccurrences();
    $this->assertEquals(count($occurrences), 1, 'Expected 1 occurrence!');
    $occ = $occurrences[0];
    $this->assertEquals(count($occ->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($occ->getScope());
    $this->assertTrue(in_array($occTheme->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertEquals($occ->getValue(), 'Occurrence', 'Expected identity!');
    $this->assertEquals($occ->getType()->getId(), $occType->getId(), 
      'Expected identity!');
    $this->assertEquals($occ->getDataType(), parent::$dtString, 'Expected identity!');
    $duplOcc = $topic->createOccurrence($occType, 'Occurrence', parent::$dtString, 
      array($occTheme));
    $occurrences = $topic->getOccurrences();
    $this->assertEquals(count($occurrences), 1, 'Expected 1 occurrence!');
    $occ = $occurrences[0];
    $this->assertEquals(count($occ->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($occ->getScope());
    $this->assertTrue(in_array($occTheme->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertEquals($occ->getValue(), 'Occurrence', 'Expected identity!');
    $this->assertEquals($occ->getType()->getId(), $occType->getId(), 
      'Expected identity!');
    $this->assertEquals($occ->getDataType(), parent::$dtString, 'Expected identity!');
  }
}
?>
