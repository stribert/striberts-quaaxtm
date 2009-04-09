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

require_once('/home/johannes/workspace/phptmapi2.0_svn/core/TopicMapSystemFactory.class.php');

/**
 * Provides common functions for tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPITestCase extends PHPUnit_Framework_TestCase {
  
  protected static $tmLocator = 'http://localhost/tm/1';
  protected $sharedFixture;
  protected $topicMap;
  
  protected function setUp() {
    if ($this->sharedFixture instanceof TopicMapSystem) {// called from suite
      $this->topicMap = $this->sharedFixture->createTopicMap(self::$tmLocator);
    } else {// allow all extending tests being stand alone
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
      $this->sharedFixture = $tmSystem;
      $this->topicMap = $tmSystem->createTopicMap(self::$tmLocator);
    }
  }
  
  protected function tearDown() {
    $this->topicMap->close();
    $this->topicMap->remove();
    $this->topicMap = null;
  }
  
  protected function getIdsOfConstructs(array $constructs) {
    $ids = array();
    foreach ($constructs as $construct) {
      $ids[] = $construct->getId();
    }
    return $ids;
  }
  
  protected function createAssoc() {
    return $this->topicMap->createAssociation($this->topicMap->createTopic());
  }
  
  protected function createRole() {
    return $this->createAssoc()->createRole($this->topicMap->createTopic(), 
      $this->topicMap->createTopic());
  }
  
  protected function createOcc() {
    return $this->topicMap->createTopic()->createOccurrence($this->topicMap->createTopic(), 
      'http://www.google.com/', 'http://www.w3.org/2001/XMLSchema#anyURI');
  }
  
  protected function createName() {
    return $this->topicMap->createTopic()->createName('Testname');
  }
  
  protected function createVariant() {
    return $this->createName()->createVariant('Testvariant', 
      'http://www.w3.org/2001/XMLSchema#string', array($this->topicMap->createTopic()));
  }
}
?>
