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
 * Literal index tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class LiteralIndexTest extends PHPTMAPITestCase {
  
  public function testGetNames() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('LiteralIndexImpl');
    $this->assertTrue($index instanceof LiteralIndexImpl);
    
    $topic = $tm->createTopic();
    
    $topic->createName('Name');
    $topic->createName('Süß');
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 2);
    try {
      $names = $index->getNames(null);
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    $names = $index->getNames('Name');
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertTrue($name instanceof Name);
    $this->assertEquals($name->getValue(), 'Name');
    $this->assertEquals($name->getParent()->getId(), $topic->getId());
    $names = $index->getNames('Süß');
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    $this->assertTrue($name instanceof Name);
    $this->assertEquals($name->getValue(), 'Süß');
    $this->assertEquals($name->getParent()->getId(), $topic->getId());
  }
  
  public function testGetOccurrences() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('LiteralIndexImpl');
    $this->assertTrue($index instanceof LiteralIndexImpl);
    
    $topic = $tm->createTopic();
    
    $topic->createOccurrence($tm->createTopic(), 'foo', parent::$dtString);
    $topic->createOccurrence($tm->createTopic(), 'http://example.org', parent::$dtUri);
    
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 2);
    try {
      $index->getOccurrences(null, 'foo');
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getOccurrences('foo', null);
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getOccurrences(null, null);
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    $occs = $index->getOccurrences('foo', 'bar');
    $this->assertEquals(count($occs), 0);
    $occs = $index->getOccurrences('foo', parent::$dtString);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertTrue($occ instanceof Occurrence);
    $this->assertEquals($occ->getValue(), 'foo');
    $this->assertEquals($occ->getDatatype(), parent::$dtString);
    $this->assertEquals($occ->getParent()->getId(), $topic->getId());
    $occs = $index->getOccurrences('http://example.org', parent::$dtUri);
    $this->assertEquals(count($occs), 1);
    $occ = $occs[0];
    $this->assertTrue($occ instanceof Occurrence);
    $this->assertEquals($occ->getValue(), 'http://example.org');
    $this->assertEquals($occ->getDatatype(), parent::$dtUri);
    $this->assertEquals($occ->getParent()->getId(), $topic->getId());
  }
  
  public function testGetVariants() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('LiteralIndexImpl');
    $this->assertTrue($index instanceof LiteralIndexImpl);
    
    $topic = $tm->createTopic();
    $name = $topic->createName('Name');
    
    $name->createVariant('foo', parent::$dtString, array($tm->createTopic()));
    $name->createVariant('http://example.org', parent::$dtUri, array($tm->createTopic()));
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 2);
    try {
      $index->getVariants(null, 'foo');
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getVariants('foo', null);
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getVariants(null, null);
      $this->fail('Null is not allowed.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    $variants = $index->getVariants('foo', 'bar');
    $this->assertEquals(count($variants), 0);
    $variants = $index->getVariants('foo', parent::$dtString);
    $this->assertEquals(count($variants), 1);
    $variant = $variants[0];
    $this->assertTrue($variant instanceof IVariant);
    $this->assertEquals($variant->getValue(), 'foo');
    $this->assertEquals($variant->getDatatype(), parent::$dtString);
    $this->assertEquals($variant->getParent()->getId(), $name->getId());
    $variants = $index->getVariants('http://example.org', parent::$dtUri);
    $this->assertEquals(count($variants), 1);
    $variant = $variants[0];
    $this->assertTrue($variant instanceof IVariant);
    $this->assertEquals($variant->getValue(), 'http://example.org');
    $this->assertEquals($variant->getDatatype(), parent::$dtUri);
    $this->assertEquals($variant->getParent()->getId(), $name->getId());
  }
}
?>