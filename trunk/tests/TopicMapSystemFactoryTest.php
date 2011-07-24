<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2011 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Topic map system factory tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapSystemFactoryTest extends PHPTMAPITestCase
{
  private $_tmSystemFactory;
  
  /**
   * @override
   */
  protected function setUp()
  {
    parent::setUp();
    $this->_tmSystemFactory = TopicMapSystemFactory::newInstance();
  }
  
  /**
   * @override
   */
  protected function tearDown() 
  {
    unset($this->_tmSystemFactory);
    parent::tearDown();
  }
  
  public function testSetFeature()
  {
    try {
      $this->_tmSystemFactory->setFeature('http://localhost/' . uniqid(), true);
      $this->fail('Setting an unknown feature must raise a FeatureNotRecognizedException.');
    } catch (FeatureNotRecognizedException $e) {
      // no op.
    }
  }
  
  public function testGetFeature()
  {
    try {
      $this->_tmSystemFactory->getFeature('http://localhost/' . uniqid());
      $this->fail('Getting an unknown feature must raise a FeatureNotRecognizedException.');
    } catch (FeatureNotRecognizedException $e) {
      // no op.
    }
    $feature = $this->_tmSystemFactory->getFeature(VocabularyUtils::TMAPI_FEATURE_AUTOMERGE);
    $this->assertTrue($feature);
    $feature = $this->_tmSystemFactory->getFeature(VocabularyUtils::TMAPI_FEATURE_READONLY);
    $this->assertFalse($feature);
  }
  
  public function testHasFeature()
  {
    $hasFeature = $this->_tmSystemFactory->hasFeature('http://localhost/' . uniqid());
    $this->assertFalse($hasFeature);
  }
  
  public function testProperty()
  {
    $property = $this->_tmSystemFactory->getProperty(uniqid());
    $this->assertNull($property);
    $this->_tmSystemFactory->setProperty('foo', new stdClass());
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertTrue($property instanceof stdClass);
    $this->_tmSystemFactory->setProperty('foo', null);
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertNull($property);
    $this->_tmSystemFactory->setProperty('foo', 'bar');
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertEquals($property, 'bar');
    $this->_tmSystemFactory->setProperty('foo', null);
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertNull($property);
    $this->_tmSystemFactory->setProperty('foo', array(1,2));
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertEquals($property, array(1,2));
    $this->_tmSystemFactory->setProperty('foo', null);
    $property = $this->_tmSystemFactory->getProperty('foo');
    $this->assertNull($property);
    $this->_tmSystemFactory->setProperty('baz', null);
  }
}
?>