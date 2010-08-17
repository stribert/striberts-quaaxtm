<?php
/*
 * QuaaxTMIO is the Topic Maps syntaxes serializer/deserializer library for QuaaxTM.
 * 
 * Copyright (C) 2010 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
  '..' . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'phptmapi2.0' . 
  DIRECTORY_SEPARATOR . 
  'core' . 
  DIRECTORY_SEPARATOR . 
  'TopicMapSystemFactory.class.php'
);
require_once('BasicRunTest.php');
require_once('AssociationTest.php');
require_once('MergeMapTest.php');
require_once('NameTest.php');
require_once('OccurrenceTest.php');
require_once('RemoteTopicMapTest.php');
require_once('TopicTest.php');
require_once('TopicMapTest.php');
require_once('VariantTest.php');

/**
 * XTM 2.0 deserializer test suite.
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
    $suite->addTestSuite('MergeMapTest');
    $suite->addTestSuite('NameTest');
    $suite->addTestSuite('OccurrenceTest');
    //$suite->addTestSuite('RemoteTopicMapTest');
    $suite->addTestSuite('TopicTest');
    $suite->addTestSuite('TopicMapTest');
    $suite->addTestSuite('VariantTest');
    return $suite;
  }
 
  protected function setUp() {
    $tmSystemFactory = TopicMapSystemFactory::newInstance();
    // QuaaxTM specific feature
    try {
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
    } catch (FeatureNotRecognizedException $e) {
      // no op.
    }
    $tmSystem = $tmSystemFactory->newTopicMapSystem();
    $this->sharedFixture = $tmSystem;
  }
 
  protected function tearDown() {
    $this->sharedFixture->close();
    $this->sharedFixture = null;
  }
}
?>