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
require_once('BasicRunTest.php');
require_once('AssociationTest.php');
require_once('ConstructTest.php');
require_once('ItemIdentifierConstraintTest.php');
require_once('NameTest.php');
require_once('OccurrenceTest.php');
require_once('ReifiableTest.php');
require_once('RoleTest.php');
require_once('ScopedTest.php');
require_once('TopicTest.php');
require_once('TopicMapTest.php');
require_once('TopicMapSystemTest.php');
require_once('TopicMapMergeTest.php');
require_once('TopicMergeTest.php');
require_once('TopicRemovableConstraintTest.php');

/**
 * Core test suite.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class AllCoreTestsSuite extends PHPUnit_Framework_TestSuite {
  
  protected $sharedFixture;
  
  public static function suite() {
    $suite = new AllCoreTestsSuite();
    $suite->addTestSuite('BasicRunTest');
    $suite->addTestSuite('AssociationTest');
    $suite->addTestSuite('ConstructTest');
    $suite->addTestSuite('ItemIdentifierConstraintTest');
    $suite->addTestSuite('NameTest');
    $suite->addTestSuite('OccurrenceTest');
    $suite->addTestSuite('ReifiableTest');
    $suite->addTestSuite('RoleTest');
    $suite->addTestSuite('ScopedTest');
    $suite->addTestSuite('TopicTest');
    $suite->addTestSuite('TopicMapTest');
    $suite->addTestSuite('TopicMapSystemTest');
    $suite->addTestSuite('TopicMapMergeTest');
    $suite->addTestSuite('TopicMergeTest');
    $suite->addTestSuite('TopicRemovableConstraintTest');
    return $suite;
  }
 
  protected function setUp() {
    $tmSystemFactory = TopicMapSystemFactory::newInstance();
    $tmSystem = $tmSystemFactory->newTopicMapSystem();
    $this->sharedFixture = $tmSystem;
  }
 
  protected function tearDown() {
    $this->sharedFixture->close();
    $this->sharedFixture = null;
  }
}
?>