<?php
require_once('PHPTMAPITestCase.php');

class ConstructTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
}
?>
