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

require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
	'..' . 
  DIRECTORY_SEPARATOR . 
  'MIOUtil.class.php'
);

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
abstract class PHPTMAPIGenericWriter
{  
  /**
   * The struct all Topic Maps constructs and their properties are appended to.
   * 
   * @var array
   */
  protected $_struct;
  
  /**
   * The setup data.
   * 
   * @var array
   */
  protected $_setup;
  
  /**
   * The topic map base locator.
   * 
   * @var string
   */
  protected $_tmLocator;
  
  /**
   * The topics item identifiers index.
   * 
   * @var array
   */
  protected $_topicsIidsIdx;
  
  /**
   * The topics subject identifiers index.
   * 
   * @var array
   */
  protected $_sidsIdx;
  
  /**
   * The topics subject locators index.
   * 
   * @var array
   */
  protected $_slosIdx;
  
  /**
   * The type-instance associations.
   * 
   * @var array
   */  
  protected $_typeInstanceAssocs;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    $this->_struct = 
    $this->_setup = 
    $this->_topicsIidsIdx = 
    $this->_sidsIdx = 
    $this->_slosIdx = 
    $this->_typeInstanceAssocs = array();
    $this->_tmLocator = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_struct);
    unset($this->_setup);
    unset($this->_topicsIidsIdx);
    unset($this->_sidsIdx);
    unset($this->_slosIdx);
    unset($this->_typeInstanceAssocs);
    unset($this->_tmLocator);
  }
  
  /**
   * Creates the serialization.
   * 
   * @param TopicMap The topic map to serialize.
   * @return void
   */
  public function write(TopicMap $topicMap)
  {
    $this->_tmLocator = $topicMap->getLocator();
    $this->_struct['reifier'] = $this->_getReifierReference($topicMap);
    
    $iids = $topicMap->getItemIdentifiers();
    if (!empty($iids)) {
      $this->_struct['item_identifiers'] = $iids;
    }
    
    $topics = $topicMap->getTopics();
    if (!empty($topics)) {
      $this->_struct['topics'] = $this->_writeTopics($topics);
    }
    
    $assocs = $topicMap->getAssociations();
    if (!empty($assocs) || !empty($this->_typeInstanceAssocs)) {
      $domainAssocs = $this->_writeAssociations($assocs);
      $this->_struct['associations'] = array_merge($domainAssocs, $this->_typeInstanceAssocs);
    }
  }
  
  /**
   * Serializes topics.
   * 
   * @param array The topics to serialize.
   * @return array
   */
  private function _writeTopics(array $topics)
  {
    $topicsStruct = array();
    foreach ($topics as $topic) {
      $itemStruct = array();
      $topicId = $topic->getId();
      // "id" is syntax specific; check setup
      if ($this->_isSetup('topics', 'id')) {
        $itemStruct['id'] = $topicId;
      }
      
      $iids = $topic->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
        $this->_topicsIidsIdx[$topicId] = $iids;
      }
      $sids = $topic->getSubjectIdentifiers();
      if (!empty($sids)) {
        $itemStruct['subject_identifiers'] = $sids;
        $this->_sidsIdx[$topicId] = $sids;
      }
      $slos = $topic->getSubjectLocators();
      if (!empty($slos)) {
        $itemStruct['subject_locators'] = $slos;
        $this->_slosIdx[$topicId] = $slos;
      }
      
      $types = $topic->getTypes();
      if (!empty($types)) {
        $this->_typeInstanceAssocs = $this->_writeTypeInstanceAssocs($types, $topic);
      }
      
      $names = $topic->getNames();
      if (!empty($names)) {
        $itemStruct['names'] = $this->_writeNames($names);
      }
      
      $occs = $topic->getOccurrences();
      if (!empty($occs)) {
        $itemStruct['occurrences'] = $this->_writeDatatypeAware($occs);
      }
      
      $topicsStruct[] = $itemStruct;
    }
    return $topicsStruct;
  }
  
  /**
   * Writes type instance associations for each topic type.
   * 
   * @param array The topic types.
   * @param Topic The typed topic.
   * @return array The type instance associations.
   */
  private function _writeTypeInstanceAssocs(array $types, Topic $topic)
  {
    $assocs = array();
    foreach ($types as $topicType) {
      $assoc = array();
      $assoc['type'] = 'si:' . MIOUtil::PSI_TYPE_INSTANCE;
      
      $roles = array();
      
      $instance = array();
      $instance['type'] = 'si:' . MIOUtil::PSI_INSTANCE;
      $instance['player'] = $this->_getTopicReference($topic);
      $roles[] = $instance;
      
      $type = array();
      $type['type'] = 'si:' . MIOUtil::PSI_TYPE;
      $type['player'] = $this->_getTopicReference($topicType);
      $roles[] = $type;
      
      $assoc['roles'] = $roles;
      
      $assocs[] = $assoc;
    }
    return $assocs;
  }
  
  /**
   * Serializes topic names.
   * 
   * @param array The topic names to serialize.
   * @return array
   */
  private function _writeNames(array $names)
  {
    $namesStruct = array();
    foreach ($names as $name) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->_getReifierReference($name);
      
      $iids = $name->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['value'] = $name->getValue();
      
      $type = $name->getType();
      if ($type instanceof Topic) {
        $itemStruct['type'] = $this->_getTopicReference($type);
      }
      
      $scope = $name->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->_getThemesReferences($scope);
      }
      
      $variants = $name->getVariants();
      if (!empty($variants)) {
        $itemStruct['variants'] = $this->_writeDatatypeAware($variants);
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
  private function _writeDatatypeAware(array $datatypeAwares)
  {
    $daStruct = array();
    foreach ($datatypeAwares as $da) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->_getReifierReference($da);
      
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
        $itemStruct['type'] = $this->_getTopicReference($da->getType());
      }
      
      $scope = $da->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->_getThemesReferences($scope);
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
  private function _writeAssociations(array $assocs)
  {
    $assocsStruct = array();
    foreach ($assocs as $assoc) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->_getReifierReference($assoc);
      
      $iids = $assoc->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['type'] = $this->_getTopicReference($assoc->getType());
      
      $scope = $assoc->getScope();
      if (!empty($scope)) {
        $itemStruct['scope'] = $this->_getThemesReferences($scope);
      }
      
      $itemStruct['roles'] = $this->_writeRoles($assoc->getRoles());
      
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
  private function _writeRoles(array $roles)
  {
    $rolesStruct = array();
    foreach ($roles as $role) {
      $itemStruct = array();
      $itemStruct['reifier'] = $this->_getReifierReference($role);
      
      $iids = $role->getItemIdentifiers();
      if (!empty($iids)) {
        $itemStruct['item_identifiers'] = $iids;
      }
      
      $itemStruct['type'] = $this->_getTopicReference($role->getType());
      $itemStruct['player'] = $this->_getTopicReference($role->getPlayer());
      
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
  private function _getThemesReferences(array $scope)
  {
    $themesStruct = array();
    foreach ($scope as $theme) {
      $themesStruct[] = $this->_getTopicReference($theme);
    }
    return $themesStruct;
  }
  
  /**
   * Gets the reifier reference.
   * 
   * @param Construct The Topic Maps construct.
   * @return string|null
   */
  private function _getReifierReference(Construct $construct)
  {
    $reifier = $construct->getReifier();
    return $reifier instanceof Topic 
      ? $this->_getTopicReference($reifier)
      : null;
  }
  
  /**
   * Gets a topic reference.
   * 
   * @param Topic The topic.
   * @return string
   */
  private function _getTopicReference(Topic $topic)
  {
    $topicId = $topic->getId();
    // XTM: topicRef by topic id as URI fragment
    if ($this->_isSetup('topics', 'id')) {
      return 'ii:#' . $topicId;
    }
    $sids = isset($this->_sidsIdx[$topicId]) 
      ? $this->_sidsIdx[$topicId]
      : $topic->getSubjectIdentifiers();
    if (count($sids) > 0) {
      return 'si:' . $sids[0];
    }
    $slos = isset($this->_slosIdx[$topicId]) 
      ? $this->_slosIdx[$topicId]
      : $topic->getSubjectLocators();
    if (count($slos) > 0) {
      return 'sl:' . $slos[0];
    }
    $iids = isset($this->_topicsIidsIdx[$topicId])
      ? $this->_topicsIidsIdx[$topicId]
      : $topic->getItemIdentifiers();
    foreach ($iids as $iid) {
      $pos = strpos($iid, $this->_tmLocator);
      if ($pos === 0) {
        return 'ii:' . $iid;
      }
    }
    return count($iids > 0) 
      ? 'ii:' . $iids[0]
    	: 'ii:' . $this->_tmLocator . '#' . $topicId;
  }
  
  /**
   * Provides set up.
   * 
   * @param string The parent key.
   * @param string The child key.
   * @param boolean The setup (true or false).
   * @return void
   */
  protected function _setup($parentKey, $childKey, $value)
  {
    $this->_setup[$parentKey][$childKey] = (boolean) $value;
  }
  
  /**
   * Checks given setup.
   * 
   * @param string The parent key.
   * @param string The child key.
   * @return boolean
   */
  protected function _isSetup($parentKey, $childKey)
  {
    return !isset($this->_setup[$parentKey][$childKey])
      ? false
      : $this->_setup[$parentKey][$childKey];
  }
}
?>