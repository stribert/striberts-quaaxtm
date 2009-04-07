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
