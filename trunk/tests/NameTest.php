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
 * Name tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class NameTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  public function testParent()
  {
    $parent = $this->_topicMap->createTopic();
    $this->assertEquals(count($parent->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $parent->createName('Name');
    $this->assertEquals($name->getParent()->getId(), $parent->getId(), 
      'Unexpected name parent after creation!');
    $this->assertEquals(count($parent->getNames()), 1, 'Expected 1 name!');
    $ids = $this->_getIdsOfConstructs($parent->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $name->remove();
    $this->assertEquals(count($parent->getNames()), 0, 'Expected 0 names after removal!');
  }
  
  public function testCreateName()
  {
    $values = array("'s Hertogenbosch", 'äüß', "foo\n", 'bar');
    foreach ($values as $value) {
      $parent = $this->_topicMap->createTopic();
      $name = $parent->createName($value);
      $this->assertEquals($name->getValue(), $value);
      $names = $parent->getNames();
      $this->assertEquals(count($names), 1);
      $name = $names[0];
      $this->assertEquals($name->getValue(), $value);
    }
  }
  
  public function testDefaultNameType()
  {
    $name = $this->_createName();
    $defaultType = $name->getType();
    $sids = $defaultType->getSubjectIdentifiers();
    $this->assertTrue(in_array('http://psi.topicmaps.org/iso13250/model/topic-name', 
      $sids));
  }
  
  public function testType()
  {
    $name = $this->_createName();
    $type1 = $this->_topicMap->createTopic();
    $type2 = $this->_topicMap->createTopic();
    $name->setType($type1);
    $nameType = $name->getType();
    $this->assertEquals($nameType->getId(), $type1->getId());
    $name->setType($type2);
    $nameType = $name->getType();
    $this->assertEquals($nameType->getId(), $type2->getId());
    $name->setType($nameType);
    $nameType = $name->getType();
    $this->assertEquals($nameType->getId(), $type2->getId());
  }
  
  public function testValue()
  {
    $values = array("'s Hertogenbosch", 'äüß', "foo\n", 'bar');
    foreach ($values as $value) {
      $name = $this->_createName();
      $this->assertTrue($name instanceof Name);
      $name->setValue($value);
      $this->assertEquals($name->getValue(), $value);
      try {
        $name->setValue(null);
        $this->fail('setValue(null) is not allowed!');
      } catch (ModelConstraintException $e) {
        $msg = $e->getMessage();
        $this->assertTrue(!empty($msg));
      }
      $this->assertEquals($name->getValue(), $value);
    }
  }
  
  public function testScope()
  {
    $name = $this->_createName();
    $this->assertTrue($name instanceof Name);
    $tm = $this->_topicMap;
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $name->addTheme($theme1);
    $name->addTheme($theme2);
    $this->assertEquals(count($name->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->_getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $name = $tm->createTopic()->createName('Name', $type, array($theme1, $theme2));
    $this->assertEquals(count($name->getScope()), 2);
    $ids = $this->_getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
  }
  
  public function testDuplicates()
  {
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $nameTheme = $tm->createTopic();
    $topic->createName('Name', $type, array($nameTheme));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 name');
    $name = $names[0];
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->_getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertEquals($name->getValue(), 'Name', 'Expected identity!');
    $this->assertEquals(count($topic->getNames()), 1, 'Expected 1 name');
    $ids = $this->_getIdsOfConstructs($topic->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $duplName = $topic->createName('Name', $type, array($nameTheme));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 name');
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Name', 'Expected identity!');
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->_getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme is not part of getScope()!');
  }
  
  public function testDuplicatesEscapedValues()
  {
    $values = array("'s Hertogenbosch", 'äüß', "foo\n", 'bar');
    foreach ($values as $value) {
      $parent = $this->_topicMap->createTopic();
      $parent->createName($value);
      // create duplicate
      $parent->createName($value);
      $names = $parent->getNames();
      $this->assertEquals(count($names), 1);
    }
  }
  
  public function testMergeScope()
  {
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $type = $tm->createTopic();
    $nameTheme = $tm->createTopicByItemIdentifier('#en');
    $topic->createName('Name', $type, array($nameTheme));
    $nameTheme = $tm->createTopicByItemIdentifier('#english');
    $topic->createName('Name', $type, array($nameTheme));
    $mergeTopic = $tm->createTopicByItemIdentifier('#en');
    $mergeTopic->addItemIdentifier('#english');
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 name');
  }
  
  public function testGainVariantsUsingFinished()
  {
    $topic = $this->_topicMap->createTopic();
    $theme = $this->_topicMap->createTopic();
    $name1 = $topic->createName('foo');
    $variant1 = $name1->createVariant('bar', parent::$_dtString, array($theme));
    $reifier = $this->_topicMap->createTopic();
    $variant1->setReifier($reifier);
    $iid = 'http://localhost' . uniqid();
    $variant1->addItemIdentifier($iid);
    $name2 = $topic->createName('baz');
    $variant2 = $name2->createVariant('bar', parent::$_dtString, array($theme));
    $this->assertEquals(count($name1->getVariants()), 1);
    $this->assertEquals(count($name2->getVariants()), 1);
    $name2->setValue('foo');
    $topic->finished($name2);
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'foo');
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), 'bar');
    $this->assertEquals($variant->getDatatype(), parent::$_dtString);
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 1);
    $this->assertEquals($scope[0]->getId(), $theme->getId());
    if ($variant->getReifier() instanceof Topic) {
      $this->assertEquals($variant->getReifier()->getId(), $reifier->getId());
    } else {
      $this->fail('Expected a reifier.');
    }
    $this->assertTrue(in_array($iid, $variant->getItemIdentifiers()));
  }
}
?>
