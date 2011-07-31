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
  'MIOException.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
	'..' . 
  DIRECTORY_SEPARATOR . 
  'MIOUtil.class.php'
);
require_once('Net/URL2.php');
require_once('PHPTMAPITopicMapHandler.interface.php');
require_once('MemoryRole.class.php');
require_once('MemoryConstruct.class.php');
require_once('MemoryScoped.class.php');
require_once('MemoryAssoc.class.php');
require_once('MemoryName.class.php');
require_once('MemoryVariant.class.php');
require_once('MemoryOccurrence.class.php');

/**
 * Receives serialized Topic Maps constructs data from a Topic Maps syntax parser and  
 * creates Topic Maps constructs via PHPTMAPI.
 * This API was originally invented by Lars Heuer <http://www.semagia.com/>.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPITopicMapHandler implements PHPTMAPITopicMapHandlerInterface
{
  /**
   * The topic map state.
   */
  const TOPICMAP = 'topicmap';
  
  /**
   * The topic state.
   */
  const TOPIC = 'topic';
  
  /**
   * The association state.
   */
  const ASSOCIATION = 'association';
  
  /**
   * The association role state.
   */
  const ROLE = 'role';
  
  /**
   * The occurrence state.
   */
  const OCCURRENCE = 'occurrence';
  
  /**
   * The topic name state.
   */
  const NAME = 'name';
  
  /**
   * The topic name variant state.
   */
  const VARIANT = 'variant';
  
  /**
   * The scope state.
   */
  const SCOPE = 'scope';
  
  /**
   * The reifier state.
   */
  const REIFIER = 'reifier';
  
  /**
   * The role player state.
   */
  const PLAYER = 'player';
  
  /**
   * The "isa-relationship" state.
   */
  const ISA = 'isa';
  
  /**
   * The type state.
   */
  const TYPE = 'type';
  
  /**
   * The state chain storing the states.
   * 
   * @var array
   */
  private $_stateChain;
  
  /**
   * The construct holder.
   * 
   * @var array
   */
  private $_constructs;
  
  /**
   * The Topic Maps system.
   * 
   * @var TopicMapSystem
   */
  private $_tmSystem;
  
  /**
   * The topic map which is deserialized.
   * 
   * @var TopicMap
   */
  private $_topicMap;
  
  /**
   * The topic map base locator object.
   * 
   * @var Net_URL2
   */
  private $_tmLocatorObj;
  
  /**
   * The topic map base locator (a URL).
   * The string representation of the topic map base locator object.
   * 
   * @var string
   */
  private $_tmLocator;
  
  /**
   * The allowed number of topic map mergings.
   * 
   * @var int
   */
  private $_maxMergeMapCount;
  
  /**
   * The current number of topic map mergings.
   * 
   * @var int
   */
  private $_mergeMapCount;
  
  /**
   * The base locators of merged topic maps.
   * 
   * @var array
   */
  private $_mergeMapLocators;
  
  /**
   * The associations index.
   * 
   * @var array
   */
  private $_assocsIndex;
  
  /**
   * The occurrences index.
   * 
   * @var array
   */
  private $_occsIndex;
  
  /**
   * The topic names index.
   * 
   * @var array
   */
  private $_namesIndex;
  
  /**
   * The topic name variants index.
   * 
   * @var array
   */
  private $_variantsIndex;
  
  /**
   * The default topic name type.
   * 
   * @var Topic
   */
  private $_defaultNameType;
  
  /**
   * Constructor.
   *  
   * @param TopicMapSystem The Topic Maps system instance.
   * @param string The topic map base locator.
   * @param int The max. allowed number of merge map processings.
   * @param int The merge map processings count. Will be set in startMergeMap().
   * @param array The base locators of the merged topic maps. Will be set in startMergeMap().
   * @return void
   * @throws MIOException If the topic map base locator is not absolute, or the base 
   * 				locator is in use.
   */
  public function __construct(
    TopicMapSystem $tmSystem, 
    $tmLocator, 
    $maxMergeMapCount=2, 
    $mergeMapCount=0, 
    array $mergeMapLocators=array()
    )
  {  
    $this->_tmLocatorObj = new Net_URL2($tmLocator);
    if (!$this->_tmLocatorObj->getScheme() && !$this->_tmLocatorObj->isAbsolute()) {
      throw new MIOException('Error in ' . __METHOD__ . ': Base locator must have 
      	a scheme and must be absolute!');
    }
    $this->_tmLocator = $this->_tmLocatorObj->getUrl();
    $this->_tmSystem = $tmSystem;
    $tmLocators = $this->_tmSystem->getLocators();
    if (in_array($this->_tmLocator, $tmLocators)) {
      throw new MIOException('Error in ' . __METHOD__ . ': Base locator "' . 
        $this->_tmLocator . '" is in use!');
    }
    $this->_maxMergeMapCount = $maxMergeMapCount;
    $this->_mergeMapCount = $mergeMapCount;
    $this->_mergeMapLocators = $mergeMapLocators;
    $this->_topicMap = 
    $this->_defaultNameType = null;
    $this->_stateChain = 
    $this->_constructs = 
    $this->_assocsIndex = 
    $this->_occsIndex = 
    $this->_namesIndex = 
    $this->_variantsIndex = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_tmLocatorObj);
    unset($this->_tmLocator);
    unset($this->_tmSystem);
    unset($this->_maxMergeMapCount);
    unset($this->_mergeMapCount);
    unset($this->_mergeMapLocators);
    unset($this->_topicMap);
    unset($this->_defaultNameType);
    unset($this->_stateChain);
    unset($this->_constructs);
    unset($this->_assocsIndex);
    unset($this->_occsIndex);
    unset($this->_namesIndex);
    unset($this->_variantsIndex);
  }
  
  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startTopicMap()
   */
  public function startTopicMap()
  {
    $topicMap = $this->_tmSystem->getTopicMap($this->_tmLocator);
    if (!is_null($topicMap)) {
      throw new MIOException('Error in ' . __METHOD__ . ': Topic map with ' . 
      	'base locator "' . $this->_tmLocator . '" already exists!');
    }
    $this->_topicMap = $this->_tmSystem->createTopicMap($this->_tmLocator);
    if (!$this->_topicMap instanceof TopicMap) {
      throw new MIOException('Error in ' . __METHOD__ . ': Topic map with ' . 
        'base locator "' . $this->_tmLocator . '" could not be created!'); 
    }
    $this->_enterStateNewConstruct(self::TOPICMAP, $this->_topicMap);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endTopicMap()
   */
  public function endTopicMap()
  {
    $this->_stateChain = 
    $this->_constructs = array();
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startTopic($identity)
   */
  public function startTopic(ReferenceInterface $identity)
  {
    $this->_enterStateNewConstruct(self::TOPIC, $this->_createTopic($identity));
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endTopic()
   */
  public function endTopic()
  {
    $topic = $this->_peekTopic();
    $this->_handleTopic($topic);
    $this->_leaveStatePopConstruct();
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startAssociation()
   */
  public function startAssociation()
  {
    $this->_enterStateNewConstruct(self::ASSOCIATION, new MemoryAssoc());
  }
  
  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endAssociation()
   */
  public function endAssociation()
  {
    $memoryAssoc = $this->_peekConstruct();
    $hash = $this->getAssocHash(
      $memoryAssoc->getType(), 
      $memoryAssoc->getScope(), 
      $memoryAssoc->getRoles()
    );
    if (!$this->hasAssoc($hash)) {
      $assoc = $this->_topicMap->createAssociation(
        $memoryAssoc->getType(), 
        $memoryAssoc->getScope()
      );
    } else {
      $id = $this->_assocsIndex[$hash];
      $assoc = $this->_topicMap->getConstructById($id);
    }
    $reifier = $memoryAssoc->getReifier();
    if ($reifier instanceof Topic && !$this->_hasEqualReifier($assoc, $reifier)) {
      $assoc->setReifier($reifier);
    }
    $iids = $memoryAssoc->getItemIdentifiers();
    $existingIids = $assoc->getItemIdentifiers();
    foreach ($iids as $iid) {
      if (!$this->_hasEqualIid($existingIids, $iid)) {
        $assoc->addItemIdentifier($iid);
      }
    }
    // the roles
    foreach ($memoryAssoc->getRoles() as $memoryRole) {
      $role = $assoc->createRole(
        $memoryRole->getType(), $memoryRole->getPlayer()
      );
      $reifier = $memoryRole->getReifier();
      if ($reifier instanceof Topic && !$this->_hasEqualReifier($role, $reifier)) {
        $role->setReifier($reifier);
      }
      $iids = $memoryRole->getItemIdentifiers();
      $existingIids = $role->getItemIdentifiers();
      foreach ($iids as $iid) {
        if (!$this->_hasEqualIid($existingIids, $iid)) {
          $role->addItemIdentifier($iid);
        }
      }
    }
    $this->_leaveStatePopConstruct();
    $this->_assocsIndex[$hash] = $assoc->getId();
    $memoryAssoc = 
    $assoc = null;
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startRole()
   */
  public function startRole()
  {
    $this->_enterStateNewConstruct(self::ROLE, new MemoryRole());
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endRole()
   */
  public function endRole()
  {
    $this->_peekConstruct(count($this->_constructs)-2)->addRole($this->_peekConstruct());
    $this->_leaveStatePopConstruct();
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startOccurrence()
   */
  public function startOccurrence()
  {
    $this->_enterStateNewConstruct(
      self::OCCURRENCE, 
      new MemoryOccurrence()
    );
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endOccurrence()
   */
  public function endOccurrence()
  {
    $memoryOcc = $this->_peekConstruct();
    $this->_leaveStatePopConstruct();
    $hash = $this->getOccurrenceHash(
      $this->_peekTopic(), 
      $memoryOcc->getType(), 
      $memoryOcc->getValue(), 
      $memoryOcc->getDatatype(), 
      $memoryOcc->getScope()
    );
    if (!$this->hasOccurrence($hash)) {
      $occ = $this->_peekTopic()->createOccurrence(
        $memoryOcc->getType(), 
        $memoryOcc->getValue(), 
        $memoryOcc->getDatatype(), 
        $memoryOcc->getScope()
      );
    } else {
      $id = $this->_occsIndex[$hash];
      $occ = $this->_topicMap->getConstructById($id);
    }
    $reifier = $memoryOcc->getReifier();
    if ($reifier instanceof Topic && !$this->_hasEqualReifier($occ, $reifier)) {
      $occ->setReifier($reifier);
    }
    $iids = $memoryOcc->getItemIdentifiers();
    $existingIids = $occ->getItemIdentifiers();
    foreach ($iids as $iid) {
      if (!$this->_hasEqualIid($existingIids, $iid)) {
        $occ->addItemIdentifier($iid);
      }
    }
    $this->_occsIndex[$hash] = $occ->getId();
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startName()
   */
  public function startName()
  {
    $this->_enterStateNewConstruct(self::NAME, new MemoryName());
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endName()
   */
  public function endName()
  {
    $memoryName = $this->_peekConstruct();
    $this->_leaveStatePopConstruct();
    $nameType = $memoryName->getType();
    if (is_null($nameType)) {
      if (is_null($this->_defaultNameType)) {
        $psi = 'http://psi.topicmaps.org/iso13250/model/topic-name';
        $this->_defaultNameType = $this->_topicMap->getTopicBySubjectIdentifier($psi);
        if (is_null($this->_defaultNameType)) {
          $this->_defaultNameType = $this->_topicMap->createTopicBySubjectIdentifier(
            $psi
          );
        }
      }
      $nameType = $this->_defaultNameType;
    }
    $hash = $this->getNameHash(
      $this->_peekTopic(), 
      $memoryName->getValue(), 
      $nameType, 
      $memoryName->getScope()
    );
    if (!$this->hasName($hash)) {
      $name = $this->_peekTopic()->createName(
        $memoryName->getValue(), 
        $memoryName->getType(), 
        $memoryName->getScope()
      );
    } else {
      $id = $this->_namesIndex[$hash];
      $name = $this->_topicMap->getConstructById($id);
    }
    $reifier = $memoryName->getReifier();
    if ($reifier instanceof Topic && !$this->_hasEqualReifier($name, $reifier)) {
      $name->setReifier($reifier);
    }
    $iids = $memoryName->getItemIdentifiers();
    $existingIids = $name->getItemIdentifiers();
    foreach ($iids as $iid) {
      if (!$this->_hasEqualIid($existingIids, $iid)) {
        $name->addItemIdentifier($iid);
      }
    }
    $this->_namesIndex[$hash] = $name->getId();
    $memoryVariants = $memoryName->getVariants();
    foreach ($memoryVariants as $memoryVariant) {
      $hash = $this->getVariantHash(
        $name, 
        $memoryVariant->getValue(), 
        $memoryVariant->getDatatype(), 
        $memoryVariant->getScope()
      );
      if (!$this->hasVariant($hash)) {
        $variant = $name->createVariant(
          $memoryVariant->getValue(), 
          $memoryVariant->getDatatype(), 
          $memoryVariant->getScope()
        );
      } else {
        $id = $this->_variantsIndex[$hash];
        $variant = $this->_topicMap->getConstructById($id);
      }
      $reifier = $memoryVariant->getReifier();
      if ($reifier instanceof Topic && !$this->_hasEqualReifier($variant, $reifier)) {
        $variant->setReifier($reifier);
      }
      $iids = $memoryVariant->getItemIdentifiers();
      $existingIids = $variant->getItemIdentifiers();
      foreach ($iids as $iid) {
        if (!$this->_hasEqualIid($existingIids, $iid)) {
          $variant->addItemIdentifier($iid);
        }
      }
      $this->_variantsIndex[$hash] = $variant->getId();
    }
    $memoryName = 
    $name = null;
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startVariant()
   */
  public function startVariant()
  {
    $this->_enterStateNewConstruct(
      self::VARIANT, 
      new MemoryVariant()
    );
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endVariant()
   */
  public function endVariant()
  {
    $memoryVariant = $this->_peekConstruct();
    $this->_leaveStatePopConstruct();
    $this->_peekConstruct()->addVariant($memoryVariant);
    $memoryVariant = null;
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startScope()
   */
  public function startScope()
  {
    $construct = $this->_peekConstruct();
    if ($construct instanceof Scoped || $construct instanceof MemoryScoped) {
      $this->_enterState(self::SCOPE);
    }
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endScope()
   */
  public function endScope()
  {
    $this->_leaveState();
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#nameValue($value)
   */
  public function nameValue($value)
  {
    $memoryName = $this->_peekConstruct();
    if ($this->_getCurrentState() === self::NAME && $memoryName instanceof MemoryName) {
      $memoryName->setValue(trim($value));
    }
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#value($value, $datatype)
   */
  public function value($value, $datatype)
  {
    if ($this->_getCurrentState() == self::OCCURRENCE) {
      $memoryOcc = $this->_peekConstruct();
      if ($memoryOcc instanceof MemoryOccurrence) {
        $memoryOcc->setValue(trim($value), $datatype);
      }
    } 
    if ($this->_getCurrentState() == self::VARIANT) {
      $memoryVariant = $this->_peekConstruct();
      if ($memoryVariant instanceof MemoryVariant) {
        $memoryVariant->setValue(trim($value), $datatype);
      }
    }
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#subjectIdentifier($sid)
   */
  public function subjectIdentifier($sid)
  {
    if ($this->_getCurrentState() === self::TOPIC) {
      $this->_peekConstruct()->addSubjectIdentifier($sid);
    }
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#subjectLocator($slo)
   */
  public function subjectLocator($slo)
  {
    if ($this->_getCurrentState() === self::TOPIC) {
      $this->_peekConstruct()->addSubjectLocator($slo);
    }
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#itemIdentifier($iid)
   */
  public function itemIdentifier($iid)
  {
    $uri = $this->_getUrl($iid);
    $this->_peekConstruct()->addItemIdentifier($uri);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startType()
   */
  public function startType()
  {
    $this->_enterState(self::TYPE);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endType()
   */
  public function endType()
  {
    $this->_leaveState(self::TYPE);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startReifier()
   */
  public function startReifier()
  {
    $this->_enterState(self::REIFIER);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endReifier()
   */
  public function endReifier()
  {
    $this->_leaveState(self::REIFIER);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#topicRef($identity)
   */
  public function topicRef(ReferenceInterface $identity)
  {
    $this->_handleTopic($this->_createTopic($identity));
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startIsa()
   */
  public function startIsa()
  {
    $this->_enterState(self::ISA);
  }

  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endIsa()
   */
  public function endIsa()
  {
    $this->_leaveState(self::ISA);
  }
  
  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startMergeMap($locator, $readerClassName)
   */
  public function startMergeMap($locator, $readerClassName)
  {
    if ($this->_mergeMapCount > $this->_maxMergeMapCount) {
      throw new MIOException('Error in ' . __METHOD__ . ': Exceeded merge map count!');
    }
    $tmLocator = $this->_tmLocatorObj->resolve($locator)->getUrl();
    if ($this->_mergeMapCount == 0) {
      $this->_mergeMapLocators[] = $this->_tmLocatorObj->getUrl();
    }
    if (in_array($tmLocator, $this->_mergeMapLocators)) {
      return;// prevent "merge ping pong"
    }
    $this->_mergeMapLocators[] = $tmLocator;
    $mapHandler = new self(
      $this->_tmSystem, 
      $tmLocator, 
      $this->_maxMergeMapCount, 
      $this->_mergeMapCount+1, 
      $this->_mergeMapLocators
    );
    $reader = new $readerClassName($mapHandler);
    $reader->readFile($tmLocator);
    $this->_topicMap->mergeIn($mapHandler->getTopicMap());
  }
  
  /**
   * (non-PHPDoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endMergeMap()
   */
  public function endMergeMap()
  {
    // no op.
  }
  
  /**
   * Returns the current topic map.
   * 
   * @return TopicMap The topic map.
   */
  public function getTopicMap()
  {
    return $this->_topicMap;
  }
  
  /**
   * Returns the topic map base locator.
   * 
   * @return string The topic map base locator.
   */
  public function getBaseLocator()
  {
    return $this->_tmLocator;
  }

  /**
   * Enters a new state.
   * 
   * @param string The state.
   * @return void
   */
  private function _enterState($state)
  {
    $this->_stateChain[] = $state;
  }
  
  /**
   * Enters a new state and registers a new Topic Maps construct.
   * 
   * @param string The state.
   * @param object The Topic Maps construct.
   * @return void
   */
  private function _enterStateNewConstruct($state, $construct)
  {
    $this->_stateChain[] = $state;
    $this->_constructs[] = $construct;
  }
  
  /**
   * Leaves a state.
   * 
   * @return void
   */
  private function _leaveState()
  {
    array_pop($this->_stateChain);
  }
  
  /**
   * Leaves a state and drops the last registered Topic Maps construct.
   * 
   * @return void
   */
  private function _leaveStatePopConstruct()
  {
    array_pop($this->_stateChain);
    array_pop($this->_constructs);
  }
  
  /**
   * Peeks a registered Topic Maps construct fro the stack.
   * 
   * @param mixed The stack index. If <var>null</var> the last registered Topic Maps 
   * 				construct is returned. Default <var>null</var>.
   * @return object A Topic Maps construct.
   */
  private function _peekConstruct($index=null)
  {
    return is_null($index)
      ? $this->_constructs[count($this->_constructs)-1]
      : $this->_constructs[$index];
  }
  
  /**
   * Peeks the last registered topic from the stack.
   * 
   * @return Topic The last registered topic.
   * @throws MIOException If the currently processed Topic Maps construct is not a topic.
   */
  private function _peekTopic()
  {
    $construct = $this->_peekConstruct();
    if (!$construct instanceof Topic) {
      throw new MIOException('Error in ' . __METHOD__ . 
      	': The Topic Maps construct is not a topic!'); 
    }
    return $construct;
  }
  
  /**
   * Gets the current state.
   * 
   * @return string The state name.
   */
  private function _getCurrentState()
  {
    return $this->_stateChain[count($this->_stateChain)-1];
  }
  
  /**
   * Gets the previous state.
   * 
   * @return string
   */
  private function _getPreviousState()
  {
    return $this->_stateChain[count($this->_stateChain)-2];
  }
  
  /**
   * Processes a topic.
   * 
   * @param Topic The topic to process.
   * @return void
   */
  private function _handleTopic(Topic $topic)
  {
    $state = $this->_getCurrentState();
    switch ($state) {
      case self::REIFIER:
        $this->_handleReifier($this->_peekConstruct(), $topic);
        break;
      case self::ISA:
        $this->_peekTopic()->addType($topic);
        break;
      case self::TYPE:
        $this->_handleType($this->_getPreviousState(), $topic);
        break;
      case self::SCOPE:
        $this->_peekConstruct()->addTheme($topic);
        break;
      case self::ROLE:
        $this->_peekConstruct()->setPlayer($topic);
        break;
      default:
        break;
    }
  }
  
  /**
   * Resolves the given reference against the topic map base locator.
   * 
   * @param string The reference.
   * @return string A valid URL.
   */
  private function _getUrl($ref)
  {
    $tmLocatorObj = new Net_URL2($this->_tmLocator);
    return $tmLocatorObj->resolve($ref)->getUrl();
  }
  
  /**
   * Creates a topic.
   * 
   * @param ReferenceInterface The topic reference.
   * @throws MIOException If the reference type is invalid.
   * @return Topic
   */
  private function _createTopic(ReferenceInterface $topicRef)
  {
    $type = $topicRef->getType();
    if ($type == ReferenceInterface::ITEM_IDENTIFIER) {
      return $this->_createTopicByItemIdentifier(
        $this->_getUrl($topicRef->getReference())
      );
    } elseif($type == ReferenceInterface::SUBJECT_IDENTIFIER) {
      return $this->_topicMap->createTopicBySubjectIdentifier(
        $topicRef->getReference()
      );
    } elseif($type == ReferenceInterface::SUBJECT_LOCATOR) {
      return $this->_topicMap->createTopicBySubjectLocator(
        $topicRef->getReference()
      );
    } else {
      throw new MIOException('Error in ' . __METHOD__ . 
      	': Unexpected reference type "' . $type . '"!');
    }
  }
  
  /**
   * Creates a topic with given item identifier.
   * 
   * @param string The item identifier.
   * @return Topic
   * @throws MIOException If a Topic Maps construct with the given item identifier - 
   * 				which does not refer to a topic - already exists in the topic map.
   */
  private function _createTopicByItemIdentifier($iid)
  {
    $construct = $this->_topicMap->getConstructByItemIdentifier($iid);
    if (!is_null($construct)) {
      if (!$construct instanceof Topic) {
        throw new MIOException('Error in ' . __METHOD__ . 
        	': Topic Maps construct is not a topic!');
      }
      return $topic = $construct;
    }
    // Prevent merging in TM engine and use possibly existing topic with given subject identitifier.
    $topic = $this->_topicMap->getTopicBySubjectIdentifier($iid);
    if (is_null($topic)) {
      return $this->_topicMap->createTopicByItemIdentifier($iid);
    } else {
      $topic->addItemIdentifier($iid);
      return $topic;
    }
  }
  
  /**
   * Handles Topic Maps construct's reifiers.
   * 
   * @param object The Topic Maps construct.
   * @param Topic The reifier.
   * @return void
   */
  private function _handleReifier($construct, Topic $reifier)
  {
    if ($this->_mergeMapCount > 0 && $construct instanceof TopicMap) {
      return;
    }
    $_reifier = $construct->getReifier();
    if ($_reifier instanceof Topic) {
      if (!$_reifier->equals($reifier)) {
        $_reifier->mergeIn($reifier);
      }
    } else {
      $construct->setReifier($reifier);
    }
  }
  
  /**
   * Handles Topic Maps contruct's types.
   * 
   * @param string The current state.
   * @param Topic The type.
   * @return void
   */
  private function _handleType($state, Topic $type)
  {
    $memoryConstruct = $this->_peekConstruct();
    if (
        ($state === self::NAME && $memoryConstruct instanceof MemoryName) || 
        ($state === self::OCCURRENCE && $memoryConstruct instanceof MemoryOccurrence) ||
        ($state === self::ASSOCIATION && $memoryConstruct instanceof MemoryAssoc) ||
        ($state === self::ROLE && $memoryConstruct instanceof MemoryRole)
    ) {
      $memoryConstruct->setType($type);
    }
  }
  
  /**
   * Checks if given Topic Maps construct is already reified by given reifier.
   * 
   * @param Construct The Topic Maps construct.
   * @param Topic|null The reifier or <var>null</var>.
   * @return boolean
   */
  private function _hasEqualReifier(Construct $construct, $reifier)
  {
    $_reifier = $construct->getReifier();
    if (!$_reifier instanceof Topic) {
      return false;
    }
    if ($reifier->equals($_reifier)) {
      return true;
    }
    return false;
  }
  
  /**
   * Checks if given item identifier is contained in given set of item identifiers.
   * 
   * @param array The set of item identifiers.
   * @param string The item identifier.
   * @return boolean
   */
  private function _hasEqualIid(array $iids, $iid)
  {
    return in_array($iid, $iids);
  }
  
  /**
   * Gets the association hash. 
   * 
   * Note: This function is also provided by QuaaxTM but in order to preserve 
   * library's independence this function is duplicated.
   * 
   * @param TopicImpl The association type.
   * @param array The scope.
   * @param array The roles.
   * @return string
   */
  public function getAssocHash(Topic $type, array $scope, array $roles)
  {
    $scopeIdsImploded = null;
    $roleIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) $ids[$theme->getId()] = $theme->getId();
      }
      ksort($ids);
      $scopeIdsImploded = implode('', $ids);
    }
    if (count($roles) > 0) {
      $ids = array();
      foreach ($roles as $role) {
        if ($role instanceof MemoryRole) {
          $ids[$role->getType()->getId() . $role->getPlayer()->getId()] = 
            $role->getType()->getId() . $role->getPlayer()->getId(); 
        }
      }
      ksort($ids);
      $roleIdsImploded = implode('', $ids);
    }
    return md5($type->getId() . $scopeIdsImploded . $roleIdsImploded);
  }
  
  /**
   * Checks if certain association has been created.
   * 
   * @param string The hash code.
   * @return boolean
   */
  public function hasAssoc($hash)
  {
    return array_key_exists($hash, $this->_assocsIndex);
  }
  
  /**
   * Gets an occurrence hash.
   * 
   * Note: This function is also provided by QuaaxTM but in order to preserve 
   * library's independence this function is duplicated.
   * 
   * @param TopicImpl The parent topic.
   * @param TopicImpl The occurrence type.
   * @param string The occurrence value.
   * @param string The occurrence datatype.
   * @param array The scope.
   * @return string
   */
  public function getOccurrenceHash(
    Topic $parent, 
    Topic $type, 
    $value, 
    $datatype, 
    array $scope
    )
  {
    if (count($scope) == 0) {
      return md5($parent->getId() . $value . $datatype . $type->getId());
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->getId()] = $theme->getId();
        }
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($parent->getId() . $value . $datatype . $type->getId() . $idsImploded);
    }
  }
  
  /**
   * Checks if certain occurrence has been created.
   * 
   * @param string The hash code.
   * @return boolean
   */
  public function hasOccurrence($hash)
  {
    return array_key_exists($hash, $this->_occsIndex);
  }
  
  /**
   * Gets a name hash.
   * 
   * Note: This function is also provided by QuaaxTM but in order to preserve 
   * library's independence this function is duplicated.
   * 
   * @param TopicImpl The parent topic.
   * @param string The name value.
   * @param TopicImpl The name type.
   * @param array The scope.
   * @return string
   */
  public function getNameHash(Topic $parent, $value, Topic $type, array $scope)
  {
    if (count($scope) == 0) {
      return md5($parent->getId() . $value . $type->getId());
    } else {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->getId()] = $theme->getId();
        }
      }
      ksort($ids);
      $idsImploded = implode('', $ids);
      return md5($parent->getId() . $value . $type->getId() . $idsImploded);
    }
  }
  
  /**
   * Checks if certain name has been created.
   * 
   * @param string The hash code.
   * @return boolean
   */
  public function hasName($hash)
  {
    return array_key_exists($hash, $this->_namesIndex);
  }
  
  /**
   * Gets the variant hash.
   * 
   * Note: This function is also provided by QuaaxTM but in order to preserve 
   * library's independence this function is duplicated.
   * 
   * @param string The value.
   * @param string The datatype.
   * @param array The scope.
   * @return string
   */
  public function getVariantHash(Name $parent, $value, $datatype, array $scope)
  {
    $scopeIdsImploded = null;
    if (count($scope) > 0) {
      $ids = array();
      foreach ($scope as $theme) {
        if ($theme instanceof Topic) {
          $ids[$theme->getId()] = $theme->getId();
        }
      }
      ksort($ids);
      $scopeIdsImploded = implode('', $ids);
    }
    return md5($parent->getId() . $value . $datatype . $scopeIdsImploded);
  }
  
  /**
   * Checks if certain variant has been created.
   * 
   * @param string The hash code.
   * @return boolean
   */
  public function hasVariant($hash)
  {
    return array_key_exists($hash, $this->_variantsIndex);
  }
}
?>