<?php
require_once('PHPTMAPITestCase.php');

class BasicRunTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
}
?>