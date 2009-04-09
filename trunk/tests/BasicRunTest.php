<?php
require_once('PHPTMAPITestCase.php');

class BasicRunTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
    $tm = $this->topicMap;
    $this->assertEquals($tm->getLocator(), self::$tmLocator);
    $tmSystem = $this->sharedFixture;
    $this->assertTrue(count($tmSystem->getLocators()) > 0);
    $this->assertTrue(in_array($tm->getLocator(), $tmSystem->getLocators()));
  }
  
}
?>