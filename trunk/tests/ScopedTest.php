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
 * Scoped tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ScopedTest extends PHPTMAPITestCase
{
  /**
   * Scoped tests: adding / removing themes.
   * 
   * @param Scoped The scoped Topic Maps construct to test.
   * @return void
   */
  private function _testScoped(Scoped $scoped)
  {
    $tm = $this->_topicMap;
    $scopeSize = $scoped instanceof IVariant ? count($scoped->getScope()) : 0;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $theme1 = $tm->createTopic();
    $scoped->addTheme($theme1);
    $scopeSize++;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->_getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $theme2 = $tm->createTopic();
    $ids = $this->_getIdsOfConstructs($scoped->getScope());
    $this->assertFalse(in_array($theme2->getId(), $ids, true), 
      'Theme is part of getScope()!');
    $scoped->addTheme($theme2);
    $scopeSize++;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->_getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $scoped->removeTheme($theme2);
    $scopeSize--;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->_getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertFalse(in_array($theme2->getId(), $ids, true), 
      'Theme is part of getScope()!');
    $scoped->removeTheme($theme1);
    $scopeSize--;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
  }
  
  public function testAssociation()
  {
    $this->_testScoped($this->_createAssoc());
  }
  
  public function testOccurrence()
  {
    $this->_testScoped($this->_createOcc());
  }
  
  public function testName()
  {
    $this->_testScoped($this->_createName());
  }
  
  public function testVariant()
  {
    $this->_testScoped($this->_createVariant());
  }
  
  /**
   * QuaaxTM specific test.
   */
  public function testCleanupBlocked()
  {
    $name1 = $this->_createName();
    $name2 = $this->_createName();
    $theme = $this->_topicMap->createTopic();
    $name1->addTheme($theme);
    $this->assertEquals(count($name1->getScope()), 1, 'Unexpected scope!');
    $name2->addTheme($theme);
    $this->assertEquals(count($name2->getScope()), 1, 'Unexpected scope!');
    $name1->removeTheme($theme);
    $this->assertEquals(count($name1->getScope()), 0, 'Unexpected scope!');
    $name2Scope = $name2->getScope();
    $this->assertEquals(count($name2Scope), 1, 'Unexpected scope!');
    $_theme = $name2Scope[0];
    $this->assertEquals($theme->getId(), $_theme->getId(), 'Expected identity!');
  }
}
?>
