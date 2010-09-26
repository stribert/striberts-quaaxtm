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

/**
 * Creates a topic map serialization which corresponds to decoded JTM 1.0 
 * (see {@link http://www.cerny-online.com/jtm/1.0/}). 
 * Works against PHPTMAPI (see {@link http://phptmapi.sourceforge.net}).
 * 
 * @package out
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
abstract class PHPTMAPIGenericWriter {
  
  protected $struct,
            $setup,
            $tmLocator,
            $topicsIidsIdx,
            $sidsIdx,
            $slosIdx;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    $this->struct = 
    $this->setup = 
    $this->topicsIidsIdx = 
    $this->sidsIdx = 
    $this->slosIdx = array();
    $this->tmLocator = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   * @see __construct()
   */
  public function __destruct() {
    $this->__construct();
  }
  
  /**
   * Creates the serialization.
   * 
   * @param TopicMap The topic map to serialize.
   * @return void
   */
  public function write(TopicMap $topicMap) {
    $this->tmLocator = $topicMap->getLocator();
    $this->struct['reifier'] = $this->getReifierReference($topicMap);
    
    $iids = $topicMap->getItemIdentifiers();
    if (!empty($iids)) {
      $this->struct['item_identifiers'] = $iids;
    }
    
    $topics = $topicMap->getTopics();
    if (!empty($topics)) {
      $this->struct['topics'] = $this->writeTopics($topics);
    }
    
    $assocs = $topicMap->getAssociations();
    if (!empty($assocs)) {
      $this->struct['associations'] = $this->writeAssociations($assocs);
    }
  }
  
  /**
   * Serializes topics.
   * 
   * @param array The topics to serialize.
   * @return array
   */
  private function writeTopics(array $topics) {
    $topicsStruct = array();
    foreach ($topics as $topic) {
      $itemStruct = array();
      $topicId = $topic->getId();
      // "id" is syntax specific; check setup
      if ($this->isSetup('topics', 'id')) {
        $itemStruct['id'] = $topicId;
      }
      
      $iids = $topic->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
        $this->topicsIidsIdx[$topicId] = $iids;
      }
      $sids = $topic->getSubjectIdentifiers();
      if (!empty($sids)) {
        $itemStruct['subject_identifiers'] = $sids;
        $this->sidsIdx[$topicId] = $sids;
      }
      $slos = $topic->getSubjectLocators();
      if (!empty($slos)) {
        $itemStruct['subject_locators'] = $slos;
        $this->slosIdx[$topicId] = $slos;
      }
      
      $names = $topic->getNames();
      if (!empty($names)) {
        $itemStruct['names'] = $this->writeNames($names);
      }
      
      $occs = $topic->getOccurrences();
      if (!empty($occs)) {
        $itemStruct['occurrences'] = $this->writeDatatypeAware($occs);
      }
      
      $topicsStruct[] = $itemStruct;
    }
    return $topicsStruct;
  }
  
  /**
   * Serializes topic names.
   * 
   * @param array The topic names to serialize.
   * @return array
   */
  private function writeNames(array $names) {
    $namesStruct = array();
    foreach ($names as $name) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->getReifierReference($name);
      
      $iids = $name->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['value'] = $name->getValue();
      
      $type = $name->getType();
      if ($type instanceof Topic) {
        $itemStruct['type'] = $this->getTopicReference($type);
      }
      
      $scope = $name->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->getThemesReferences($scope);
      }
      
      $variants = $name->getVariants();
      if (!empty($variants)) {
        $itemStruct['variants'] = $this->writeDatatypeAware($variants);
      }
      
      $namesStruct[] = $itemStruct;
    }
    return $namesStruct;
  }
  
  /**
   * Serializes data type aware Topic Maps constructs.
   * 
   * @param array The data type aware Topic Maps constructs to serialize.
   * @return array
   */
  private function writeDatatypeAware(array $datatypeAwares) {
    $daStruct = array();
    foreach ($datatypeAwares as $da) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->getReifierReference($da);
      
      $iids = $da->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['value'] = $da->getValue();
      
      $datatype = $da->getDatatype();
      if (!empty($datatype)) {
        $itemStruct['datatype'] = $datatype;
      }
      
      if ($da instanceof Occurrence) {
        $itemStruct['type'] = $this->getTopicReference($da->getType());
      }
      
      $scope = $da->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->getThemesReferences($scope);
      }
      
      $daStruct[] = $itemStruct;
    }
    return $daStruct;
  }
  
  /**
   * Serializes associations.
   * 
   * @param array The associations to serialize.
   * @return void
   */
  private function writeAssociations(array $assocs) {
    $assocsStruct = array();
    foreach ($assocs as $assoc) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->getReifierReference($assoc);
      
      $iids = $assoc->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['type'] = $this->getTopicReference($assoc->getType());
      
      $scope = $assoc->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->getThemesReferences($scope);
      }
      
      $itemStruct['roles'] = $this->writeRoles($assoc->getRoles());
      
      $assocsStruct[] = $itemStruct;
    }
    return $assocsStruct;
  }
  
  /**
   * Serializes association roles.
   * 
   * @param array The association roles to serialize.
   * @return void
   */
  private function writeRoles(array $roles) {
    $rolesStruct = array();
    foreach ($roles as $role) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->getReifierReference($role);
      
      $iids = $role->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['type'] = $this->getTopicReference($role->getType());
      $itemStruct['player'] = $this->getTopicReference($role->getPlayer());
      
      $rolesStruct[] = $itemStruct;
    }
    return $rolesStruct;
  }
  
  /**
   * Gets themes references.
   * 
   * @param array The scope containing themes.
   * @return array
   */
  private function getThemesReferences(array $scope) {
    $themesStruct = array();
    foreach ($scope as $theme) {
      $themesStruct[] = $this->getTopicReference($theme);
    }
    return $themesStruct;
  }
  
  /**
   * Gets the reifier reference.
   * 
   * @param Construct The Topic Maps construct.
   * @return string|null
   */
  private function getReifierReference(Construct $construct) {
    $reifier = $construct->getReifier();
    return $reifier instanceof Topic 
      ? $this->getTopicReference($reifier)
      : null;
  }
  
  /**
   * Gets a topic reference.
   * 
   * @param Topic The topic.
   * @return string
   */
  private function getTopicReference(Topic $topic) {
    $topicId = $topic->getId();
    // XTM: topicRef by topic id as URI fragment
    if ($this->isSetup('topics', 'id')) {
      return 'ii:#' . $topicId;
    }
    $sids = isset($this->sidsIdx[$topicId]) 
      ? $this->sidsIdx[$topicId]
      : $topic->getSubjectIdentifiers();
    if (count($sids) > 0) {
      return 'si:' . $sids[0];
    }
    $slos = isset($this->slosIdx[$topicId]) 
      ? $this->slosIdx[$topicId]
      : $topic->getSubjectLocators();
    if (count($slos) > 0) {
      return 'sl:' . $slos[0];
    }
    $iids = isset($this->topicsIidsIdx[$topicId])
      ? $this->topicsIidsIdx[$topicId]
      : $topic->getItemIdentifiers();
    foreach ($iids as $iid) {
      $pos = strpos($iid, $this->tmLocator);
      if ($pos === 0) {
        return 'ii:' . $iid;
      }
    }
    return count($iids > 0) 
      ? 'ii:' . $iids[0]
    	: 'ii:' . $this->tmLocator . '#' . $topicId;
  }
  
  /**
   * Provides set up.
   * 
   * @param string The parent key.
   * @param string The child key.
   * @param boolean The setup (true or false).
   * @return void
   */
  protected function setup($parentKey, $childKey, $value) {
    $this->setup[$parentKey][$childKey] = (boolean) $value;
  }
  
  /**
   * Checks given setup.
   * 
   * @param string The parent key.
   * @param string The child key.
   * @return boolean
   */
  protected function isSetup($parentKey, $childKey) {
    return !isset($this->setup[$parentKey][$childKey])
      ? false
      : $this->setup[$parentKey][$childKey];
  }
}
?>