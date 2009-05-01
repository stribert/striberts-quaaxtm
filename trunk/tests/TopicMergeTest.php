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
 * Topic merge tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMergeTest extends PHPTMAPITestCase {
  
  private $tm;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->tm = $this->topicMap;
  }
  
  /**
   * @override
   */
  public function tearDown() {
    parent::tearDown();
    $this->tm = null;
  }

  public function testTopicMap() {
    $this->assertTrue($this->tm instanceof TopicMap);
  }
  
  /**
   * Test if $t->mergeIn($t) is ignored.
   */
  public function testTopicMergeNoop() {
    $sys = $this->sharedFixture;
    $locator = 'http://localhost/tm/3';
    $tm = $sys->createTopicMap($locator);
    $this->assertEquals($tm->getId(), $sys->getTopicMap($locator)->getId(), 
      'Expected identity!');
    $tm->mergeIn($sys->getTopicMap($locator));
    $this->assertEquals($tm->getId(), $sys->getTopicMap($locator)->getId(), 
      'Expected identity!');
  }
}
?>
