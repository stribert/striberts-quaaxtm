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
 * Topic map system tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapSystemTest extends PHPTMAPITestCase {
  
  private $sys;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->sys = $this->sharedFixture;
  }
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sys instanceof TopicMapSystem);
  }

  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testLoad() {
    $tm = $this->sys->getTopicMap(self::$tmLocator);
    $this->assertNotNull($tm, 'Expected topic map!');
    $id = $tm->getId();
    $this->assertTrue(!empty($id), 'Expected internal identifier for topic map!');
    $this->assertEquals($id, $this->topicMap->getId(), 'Expected identity!');
  }
  
  public function testSameLocator() {
    try {
      $tm = $this->sys->createTopicMap(self::$tmLocator);
      $this->fail('A topic map under the same storage address already exists!');
    } catch (TopicMapExistsException $e) {
      // no op.
    }
  }
  
  public function testCreateTopicMaps() {
    $base = 'http://localhost/topicmaps/';
    $tm1 = $this->sys->createTopicMap($base . 'map1');
    $tm2 = $this->sys->createTopicMap($base . 'map2');
    $tm3 = $this->sys->createTopicMap($base . 'map3');
    $this->assertNotNull($tm1, 'Expected topic map!');
    $this->assertNotNull($tm2, 'Expected topic map!');
    $this->assertNotNull($tm3, 'Expected topic map!');
    $id1 = $tm1->getId();
    $id2 = $tm2->getId();
    $id3 = $tm3->getId();
    $this->assertTrue(!empty($id1), 'Expected internal identifier for topic map!');
    $this->assertTrue(!empty($id2), 'Expected internal identifier for topic map!');
    $this->assertTrue(!empty($id3), 'Expected internal identifier for topic map!');
    $this->assertNotEquals($id1, $id2, 'Unexpected identity!');
    $this->assertNotEquals($id1, $id3, 'Unexpected identity!');
    $this->assertNotEquals($id2, $id3, 'Unexpected identity!');
  }
  
  public function testRemoveTopicMaps() {
    $base = 'http://localhost/topicmaps/';
    $tm1 = $this->sys->createTopicMap($base . 'map1');
    $tm2 = $this->sys->createTopicMap($base . 'map2');
    $tm3 = $this->sys->createTopicMap($base . 'map3');
    $this->assertNotNull($tm1, 'Expected topic map!');
    $this->assertNotNull($tm2, 'Expected topic map!');
    $this->assertNotNull($tm3, 'Expected topic map!');
    $countBefore = count($this->sys->getLocators());
    $tm3->remove();
    $countAfter = count($this->sys->getLocators());
    $this->assertEquals($countBefore-1, $countAfter, 'Expected ' . $countBefore-1 . 
      ' topic maps!');
  }
}
?>
