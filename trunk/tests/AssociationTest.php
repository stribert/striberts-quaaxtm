<?php
require_once('PHPTMAPITestCase.php');

class AssociationTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $parent = $this->topicMap;
    $this->assertTrue(count($parent->getAssociations()) == 0, 
      'Expected new topic maps to be created with no associations!');
    $assoc = $parent->createAssociation($parent->createTopic());
    $this->assertEquals($parent, $assoc->getParent(), 
      'Unexpected association parent after creation!');
    $this->assertTrue(count($parent->getAssociations()) == 1, 
      'Unexpected number of associations in topic map!');
    $assocs = $parent->getAssociations();
    $ids = $this->getIdsOfChildren($assocs);
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $assoc->remove();
    $this->assertTrue(count($parent->getAssociations()) == 0, 
      'Expected no association in topic map after removal!');
    $assoc1 = $parent->createAssociation($parent->createTopic());
    $assoc2 = $parent->createAssociation($parent->createTopic());
    $this->assertTrue(count($parent->getAssociations()) == 2, 
      'Unexpected number of associations in topic map!');
    $assocs = $parent->getAssociations();
    $ids = $this->getIdsOfChildren($assocs);
    $this->assertTrue(in_array($assoc1->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertTrue(in_array($assoc2->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $assoc1->remove();
    $this->assertTrue(count($parent->getAssociations()) == 1, 
      'Unexpected number of associations in topic map after removal!');
  }
  
  public function testRoleCreation() {
    
  }
}
?>