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
 * Topic map merge tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapMergeTest extends PHPTMAPITestCase {
  
  protected static $tmLocator2 = 'http://localhost/tm/2';
  
  private $tm1,
          $tm2;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->tm1 = $this->topicMap;
    $this->tm2 = $this->sharedFixture->createTopicMap(self::$tmLocator2);
  }

  public function testTopicMap() {
    $this->assertTrue($this->tm1 instanceof TopicMap);
    $this->assertTrue($this->tm2 instanceof TopicMap);
  }
  

}
?>
