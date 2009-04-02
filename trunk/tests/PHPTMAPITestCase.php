<?php
require_once('/home/johannes/workspace/phptmapi2.0_svn/core/TopicMapSystemFactory.class.php');

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
  
  protected function getIdsOfChildren(array $children) {
    $ids = array();
    foreach ($children as $child) {
      $ids[] = $child->getId();
    }
    return $ids;
  }
}
?>
