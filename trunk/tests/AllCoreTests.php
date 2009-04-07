<?php
require_once('/home/johannes/workspace/phptmapi2.0_svn/core/TopicMapSystemFactory.class.php');
require_once('tests.php');

class AllCoreTestsSuite extends PHPUnit_Framework_TestSuite {
  
  protected $sharedFixture;
  
  public static function suite() {
    $suite = new AllCoreTestsSuite();
    $suite->addTestSuite('BasicRunTest');
    $suite->addTestSuite('AssociationTest');
    $suite->addTestSuite('ConstructTest');
    $suite->addTestSuite('ItemIdentifierConstraintTest');
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