<?php
require_once('PHPTMAPITestCase.php');

class ItemIdentifierConstraintTest extends PHPTMAPITestCase {
  
  /**
   * Item identifier constraint test.
   *
   * @param Construct The Topic Maps construct to test.
   * @return void
   */
  private function _testConstraint(Construct $construct) {
    $tm = $this->topicMap;
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of iids to be 0 for newly created construct!');
    $locator1 = 'http://tmapi.org/test#test1';
    $locator2 = 'http://tmapi.org/test#test2';
    $assoc = $this->createAssoc();
    $assoc->addItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    try {
      $construct->addItemIdentifier($locator1);
      $this->fail('Topic Maps constructs with the same iid are not allowed!');
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($construct->getId(), $e->getReporter()->getId());
      $this->assertEquals($assoc->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator1, $e->getLocator());
    }
    $construct->addItemIdentifier($locator2);
    $this->assertTrue(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    $construct->removeItemIdentifier($locator2);
    $assoc->removeItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $assoc->getItemIdentifiers(), true), 
      'Unexpected iid!');
    $construct->addItemIdentifier($locator1);
    $this->assertTrue(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    if (!$construct instanceof TopicMap) {
      // removal should free the iid
      $construct->remove();
      $assoc->addItemIdentifier($locator1);
      $this->assertTrue(in_array($locator1, $assoc->getItemIdentifiers(), true), 
        'Unexpected iid!');
    }
  }
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
    $this->_testConstraint($this->topicMap);
  }
  
  public function testAssociation() {
    $this->_testConstraint($this->createAssoc());
  }
}
?>
