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

require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'lib' . 
  DIRECTORY_SEPARATOR . 
  'phptmapi2.0' . 
  DIRECTORY_SEPARATOR . 
  'core' . 
  DIRECTORY_SEPARATOR . 
  'TopicMapSystemFactory.class.php'
);
require_once('BasicRunTest.php');
require_once('AssociationTest.php');
require_once('ConstructTest.php');
require_once('FeatureStringTest.php');
require_once('ItemIdentifierConstraintTest.php');
require_once('NameTest.php');
require_once('OccurrenceTest.php');
require_once('ReifiableTest.php');
require_once('RoleTest.php');
require_once('SameTopicMapTest.php');
require_once('ScopedTest.php');
require_once('TopicTest.php');
require_once('TopicMapTest.php');
require_once('TopicMapSystemFactoryTest.php');
require_once('TopicMapSystemTest.php');
require_once('TopicMapMergeTest.php');
require_once('TopicMergeTest.php');
require_once('TopicRemovableConstraintTest.php');
require_once('TypedTest.php');
require_once('TopicMergeDetectionAutomergeEnabledTest.php');
require_once('VariantTest.php');
// index tests
require_once('IndexTest.php');
require_once('TypeInstanceIndexTest.php');
require_once('LiteralIndexTest.php');
require_once('ScopedIndexTest.php');
// QuaaxTM specific tests
require_once('QTMDuplicateRemovalTest.php');
require_once('QTMDuplicateAutoRemovalTest.php');
require_once('QTMGetConstructTest.php');
require_once('QTMPropertyHolderTest.php');
require_once('QTMCacheTest.php');
require_once('QTMMySQLTest.php');
require_once('QTMFeatureStringTest.php');
require_once('QTMScopeTest.php');
require_once('QTMResultCacheTest.php');

/**
 * QuaaxTM test suite.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class AllTests extends PHPUnit_Framework_TestSuite {
  
  protected $sharedFixture;
  
  public static function suite() {
    $suite = new AllTests();
    $suite->addTestSuite('BasicRunTest');
    $suite->addTestSuite('AssociationTest');
    $suite->addTestSuite('ConstructTest');
    $suite->addTestSuite('FeatureStringTest');
    $suite->addTestSuite('ItemIdentifierConstraintTest');
    $suite->addTestSuite('NameTest');
    $suite->addTestSuite('OccurrenceTest');
    $suite->addTestSuite('ReifiableTest');
    $suite->addTestSuite('RoleTest');
    $suite->addTestSuite('SameTopicMapTest');
    $suite->addTestSuite('ScopedTest');
    $suite->addTestSuite('TopicTest');
    $suite->addTestSuite('TopicMapTest');
    $suite->addTestSuite('TopicMapSystemFactoryTest');
    $suite->addTestSuite('TopicMapSystemTest');
    $suite->addTestSuite('TopicMapMergeTest');
    $suite->addTestSuite('TopicMergeTest');
    $suite->addTestSuite('TopicRemovableConstraintTest');
    $suite->addTestSuite('TypedTest');
    $suite->addTestSuite('TopicMergeDetectionAutomergeEnabledTest');
    $suite->addTestSuite('VariantTest');
    // index tests
    $suite->addTestSuite('IndexTest');
    $suite->addTestSuite('TypeInstanceIndexTest');
    $suite->addTestSuite('LiteralIndexTest');
    $suite->addTestSuite('ScopedIndexTest');
    // QuaaxTM specific tests
    $suite->addTestSuite('QTMDuplicateRemovalTest');
    $suite->addTestSuite('QTMDuplicateAutoRemovalTest');
    $suite->addTestSuite('QTMGetConstructTest');
    $suite->addTestSuite('QTMPropertyHolderTest');
    $suite->addTestSuite('QTMCacheTest');
    $suite->addTestSuite('QTMMySQLTest');
    $suite->addTestSuite('QTMFeatureStringTest');
    $suite->addTestSuite('QTMScopeTest');
    $suite->addTestSuite('QTMResultCacheTest');
    return $suite;
  }
}
?>