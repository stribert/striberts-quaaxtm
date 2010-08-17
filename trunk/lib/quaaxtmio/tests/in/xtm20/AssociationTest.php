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

require_once('TestCase.php');

/**
 * Basic run tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class AssociationTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testAssocBinary() {
    $file = $this->xtmIncPath . 'association-binary.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $this->_testAssocBinary();
  }
  
  public function testAssocBinaryDupl() {
    $file = $this->xtmIncPath . 'association-binary-duplicate.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 5);
    
    $iids = array(
      '#assoctype'=>'#assoctype', 
      '#roletype1'=>'#roletype1', 
      '#roletype2'=>'#roletype2', 
      '#topic1'=>'#topic1', 
      '#topic2'=>'#topic2',
      '#expert1'=>'#expert1'
    );
    foreach ($topics as $topic) {
      $_iids = $topic->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    
    $assoc = $assocs[0];
    
    $assocType = $assoc->getType();
    $this->assertTrue($assocType instanceof Topic);
    
    $iids = $assocType->getItemIdentifiers();
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#assoctype');
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 2);
    
    $iids = array('#roletype1'=>'#roletype1', '#roletype2'=>'#roletype2');
    foreach ($roles as $role) {
      $type = $role->getType();
      $_iids = $type->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $iids = array('#topic1'=>'#topic1', '#topic2'=>'#topic2');
    foreach ($roles as $role) {
      $player = $role->getPlayer();
      $_iids = $player->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $count = 2;
    
    try {
      $duplRemove = $this->sharedFixture->getFeature(
        VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL
      );
      if ($duplRemove) {
        $count = 1;
      }
    } catch (FeatureNotRecognizedException $e) {
      // no op.
    }
    
    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic1');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);

    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic2');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);
  }
  
  public function testAssocDuplRole() {
    $file = $this->xtmIncPath . 'association-duplicate-role.xtm';
    $this->readAndParse($file);

    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 7);
    
    $iids = array(
      '#assoctype', 
      '#roletype1', 
      '#roletype2', 
      '#roletype3',
      '#topic1', 
      '#topic2',
      '#topic3'
    );
    foreach ($topics as $topic) {
      $_iids = $topic->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
    }
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    
    $assoc = $assocs[0];
    
    $assocType = $assoc->getType();
    $this->assertTrue($assocType instanceof Topic);
    
    $iids = $assocType->getItemIdentifiers();
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#assoctype');
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 3);
    
    $iids = array(
      '#roletype1'=>'#roletype1', 
      '#roletype2'=>'#roletype2', 
      '#roletype3'=>'#roletype3'
    );
    foreach ($roles as $role) {
      $type = $role->getType();
      $_iids = $type->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $iids = array('#topic1'=>'#topic1', '#topic2'=>'#topic2', '#topic3'=>'#topic3');
    foreach ($roles as $role) {
      $player = $role->getPlayer();
      $_iids = $player->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic1');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);

    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic2');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);
    
    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic3');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);
  }
  
  public function testAssocUnary() {
    $file = $this->xtmIncPath . 'association-unary.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    
    $assoc = $assocs[0];
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    $role = $roles[0];
    $riids = $role->getItemIdentifiers();
    $this->assertEquals(count($riids), 2);
    $reifier = $role->getReifier();
    $this->assertTrue($reifier instanceof Topic);
    
    $iids = array(
      '#assoctype'=>'#assoctype', 
      '#roletype1'=>'#roletype1', 
      '#topic1'=>'#topic1', 
      '#the-role-1'=>'#the-role-1',
      '#the-role-2'=>'#the-role-2',
      '#reifier'=>'#reifier'
    );
    
    foreach ($riids as $riid) {
      $fragment = $this->getIidFragment($riid);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $_iids = $reifier->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    foreach ($_iids as $_iid) {
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $roleType = $role->getType();
    $_iids = $roleType->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    foreach ($_iids as $_iid) {
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $player = $role->getPlayer();
    $_iids = $player->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    foreach ($_iids as $_iid) {
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $assocType = $assoc->getType();
    $_iids = $assocType->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    foreach ($_iids as $_iid) {
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $this->assertEquals(count($iids), 0);
  }
  
  public function testAssocInstanceofDupl() {
    $file = $this->xtmIncPath . 'association-instanceof-duplicate.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $this->_testAssocBinary();
    
    $this->_testAssocInstanceof();
  }
  
  public function testAssocInstanceofScope() {
    $file = $this->xtmIncPath . 'association-instanceof-scope.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $this->_testAssocBinary(6);
    
    $this->_testAssocInstanceof();
    
    $assocs = $tm->getAssociations();
    $assoc = $assocs[0];
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#expert1', $fragment);
  }
  
  public function testAssocReifier() {
    $file = $this->xtmIncPath . 'association-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    $reifier = $assoc->getReifier();
    $this->assertTrue($reifier instanceof Topic);
    $iids = $reifier->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#reifier', $fragment);
  }
  
  public function testAssocScope() {
    $file = $this->xtmIncPath . 'association-scope.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 6);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 1);
    $theme = $scope[0];
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#scopingtopic', $fragment);
    
    $type = $assoc->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#assoctype', $fragment);
    
    $rtids = array(
      '#roletype1'=>'#roletype1',
      '#roletype2'=>'#roletype2'
    );
    
    $rpids = array(
      '#topic1'=>'#topic1',
      '#topic2'=>'#topic2'
    );
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 2);
    foreach ($roles as $role) {
      $this->assertTrue($role instanceof Role);
      $type = $role->getType();
      $this->assertTrue($type instanceof Topic);
      $iids = $type->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $rtids));
      unset($rtids[$iid]);
      
      $player = $role->getPlayer();
      $this->assertTrue($player instanceof Topic);
      $iids = $player->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $rpids));
      unset($rtids[$iid]);
    }
  }
  
  public function testAssocTernary() {
    $file = $this->xtmIncPath . 'association-ternary.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 7);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 0);
    
    $type = $assoc->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#assoctype', $fragment);
    
    $rtids = array(
      '#roletype1'=>'#roletype1',
      '#roletype2'=>'#roletype2', 
      '#roletype3'=>'#roletype3'
    );
    
    $rpids = array(
      '#topic1'=>'#topic1',
      '#topic2'=>'#topic2',
      '#topic3'=>'#topic3' 
    );
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 3);
    foreach ($roles as $role) {
      $this->assertTrue($role instanceof Role);
      $parent = $role->getParent();
      $this->assertTrue($parent instanceof Association);
      $tm = $role->getTopicMap();
      $this->assertTrue($tm instanceof TopicMap);
      $reifier = $role->getReifier();
      $this->assertTrue(is_null($reifier));
      $type = $role->getType();
      $this->assertTrue($type instanceof Topic);
      $iids = $type->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $rtids));
      unset($rtids[$iid]);
      
      $player = $role->getPlayer();
      $this->assertTrue($player instanceof Topic);
      $iids = $player->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $rpids));
      unset($rtids[$iid]);
    }
  }
  
  public function testAssoc() {
    $file = $this->xtmIncPath . 'association.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 0);
    
    $type = $assoc->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#assoctype', $fragment);
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    $role = $roles[0];
    $parent = $role->getParent();
    $this->assertTrue($parent instanceof Association);
    $tm = $role->getTopicMap();
    $this->assertTrue($tm instanceof TopicMap);
    $reifier = $role->getReifier();
    $this->assertTrue(is_null($reifier));
    $type = $role->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#roletype', $fragment);
    $player = $role->getPlayer();
    $this->assertTrue($player instanceof Topic);
    $iids = $player->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#topic', $fragment);
  }
  
  public function testRoleReifier() {
    $file = $this->xtmIncPath . 'role-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);

    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 0);
    
    $type = $assoc->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#assoctype', $fragment);
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    $role = $roles[0];
    
    $parent = $role->getParent();
    $this->assertTrue($parent instanceof Association);
    
    $tm = $role->getTopicMap();
    $this->assertTrue($tm instanceof TopicMap);
    
    $reifier = $role->getReifier();
    $this->assertTrue($reifier instanceof Topic);
    $_reifier = $tm->getConstructByItemIdentifier($this->tmLocator . '#reifier');
    $this->assertEquals($reifier->getId(), $_reifier->getId());
    
    $type = $role->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#roletype', $fragment);
    
    $player = $role->getPlayer();
    $this->assertTrue($player instanceof Topic);
    $iids = $player->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals('#topic', $fragment);
  }
  
  private function _testAssocBinary($topicsCount=5) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), $topicsCount);
    
    $iids = array(
      '#assoctype'=>'#assoctype', 
      '#roletype1'=>'#roletype1', 
      '#roletype2'=>'#roletype2', 
      '#topic1'=>'#topic1', 
      '#topic2'=>'#topic2',
      '#expert1'=>'#expert1'
    );
    foreach ($topics as $topic) {
      $_iids = $topic->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 1);
    
    $assoc = $assocs[0];
    
    $assocType = $assoc->getType();
    $this->assertTrue($assocType instanceof Topic);
    
    $iids = $assocType->getItemIdentifiers();
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#assoctype');
    
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 2);
    
    $iids = array('#roletype1'=>'#roletype1', '#roletype2'=>'#roletype2');
    foreach ($roles as $role) {
      $type = $role->getType();
      $_iids = $type->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $iids = array('#topic1'=>'#topic1', '#topic2'=>'#topic2');
    foreach ($roles as $role) {
      $player = $role->getPlayer();
      $_iids = $player->getItemIdentifiers();
      $this->assertEquals(count($_iids), 1);
      $fragment = $this->getIidFragment($_iids[0]);
      $this->assertTrue(in_array($fragment, $iids));
      unset($iids[$fragment]);
    }
    
    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic1');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);

    $construct = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic2');
    $this->assertTrue($construct instanceof Topic);
    $roles = $construct->getRolesPlayed();
    $this->assertEquals(count($roles), 1);
  }
  
  private function _testAssocInstanceof() {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $assocType = $tm->getTopicBySubjectIdentifier(
      'http://psi.topicmaps.org/iso13250/model/type-instance'
    );
    $this->assertTrue($assocType instanceof Topic);
    $roleType1 = $tm->getTopicBySubjectIdentifier(
      'http://psi.topicmaps.org/iso13250/model/instance'
    );
    $this->assertTrue($assocType instanceof Topic);
    $roleType2 = $tm->getTopicBySubjectIdentifier(
      'http://psi.topicmaps.org/iso13250/model/type'
    );
    $this->assertTrue($assocType instanceof Topic);
    
    $iids = array(
      '#assoctype'=>'#assoctype', 
      '#roletype1'=>'#roletype1', 
      '#roletype2'=>'#roletype2'
    );
    
    $_iids = $assocType->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    $_iid = $_iids[0];
    $fragment = $this->getIidFragment($_iid);
    $this->assertTrue(in_array($fragment, $iids));
    unset($iids[$fragment]);
    
    $_iids = $roleType1->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    $_iid = $_iids[0];
    $fragment = $this->getIidFragment($_iid);
    $this->assertTrue(in_array($fragment, $iids));
    unset($iids[$fragment]);
    
    $_iids = $roleType2->getItemIdentifiers();
    $this->assertEquals(count($_iids), 1);
    $_iid = $_iids[0];
    $fragment = $this->getIidFragment($_iid);
    $this->assertTrue(in_array($fragment, $iids));
    unset($iids[$fragment]);
    
    $this->assertEquals(count($iids), 0);
  }
  
}
?>