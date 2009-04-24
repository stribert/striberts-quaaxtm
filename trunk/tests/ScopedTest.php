<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
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
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ScopedTest extends PHPTMAPITestCase {
  
  /**
   * Scoped tests: adding / removing themes.
   * 
   * @param Scoped The scoped Topic Maps construct to test.
   * @return void
   */
  protected function _testScoped(Scoped $scoped) {
    $tm = $this->topicMap;
    $scopeSize = $scoped instanceof Variant ? count($scoped->getScope()) : 0;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $theme1 = $tm->createTopic();
    $scoped->addTheme($theme1);
    $scopeSize++;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $theme2 = $tm->createTopic();
    $ids = $this->getIdsOfConstructs($scoped->getScope());
    $this->assertFalse(in_array($theme2->getId(), $ids, true), 
      'Theme is part of getScope()!');
    $scoped->addTheme($theme2);
    $scopeSize++;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $scoped->removeTheme($theme2);
    $scopeSize--;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
    $ids = $this->getIdsOfConstructs($scoped->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertFalse(in_array($theme2->getId(), $ids, true), 
      'Theme is part of getScope()!');
    $scoped->removeTheme($theme1);
    $scopeSize--;
    $this->assertEquals($scopeSize, count($scoped->getScope()), 
      'Unexpected count of themes!');
  }
  
  public function testAssociation() {
    $this->_testScoped($this->createAssoc());
  }
  
  public function testOccurrence() {
    $this->_testScoped($this->createOcc());
  }
  
  public function testName() {
    $this->_testScoped($this->createName());
  }
  
  public function testVariant() {
    $this->_testScoped($this->createVariant());
  }
}
?>
