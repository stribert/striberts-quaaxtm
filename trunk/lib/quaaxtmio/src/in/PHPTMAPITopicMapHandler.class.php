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
 * Receives serialized Topic Maps constructs data from an XTM parser and 
 * creates Topic Maps constructs via PHPTMAPI.
 * This API was originally invented by Lars Heuer <http://www.semagia.com/>.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPITopicMapHandler implements PHPTMAPITopicMapHandlerInterface {
  
  const TOPICMAP = 'topicmap',
        TOPIC = 'topic',
        ASSOCIATION = 'association',
        ROLE = 'role',
        OCCURRENCE = 'occurrence',
        NAME = 'name',
        VARIANT = 'variant',
        SCOPE = 'scope',
        REIFIER = 'reifier',
        PLAYER = 'player',
        ISA = 'isa',
        TYPE = 'type';
  
  private $stateChain,
          $constructs,
          $tmSystem,
          $topicMap,
          $locator,
          $baseLocatorRef,
          $maxMergeMapCount,
          $mergeMapCount;
  
  /**
   * Constructor.
   *  
   * @param TopicMapSystem The Topic Maps system instance.
   * @param string The topic map's base locator.
   * @param int The max. allowed number of merge map processing.
   * @param int The merge map processing count.
   * @return void
   * @throws MIOException If topic map's base locator is not absolute or base 
   * 				locator in use.
   */
  public function __construct(
    TopicMapSystem $tmSystem, 
    $baseLocator, 
    $maxMergeMapCount=2, 
    $mergeMapCount=0
  ) {
    
    $this->locator = new Net_URL2($baseLocator);
    if (!$this->locator->getScheme() && !$this->locator->isAbsolute()) {
      throw new MIOException('Error in ' . __METHOD__ . ': Base locator must have 
      	a scheme and must be absolute!');
    }
    $this->baseLocatorRef = $this->locator->getUrl();
    $this->tmSystem = $tmSystem;
    $baseLocators = $this->tmSystem->getLocators();
    if (in_array($this->baseLocatorRef, $baseLocators)) {
      throw new MIOException('Error in ' . __METHOD__ . ': Base locator "' . 
        $this->baseLocatorRef . '" is in use!');
    }
    $this->maxMergeMapCount = $maxMergeMapCount;
    $this->mergeMapCount = $mergeMapCount;
    $this->topicMap = null;
    $this->stateChain = 
    $this->constructs = array();
  }
  
  /**
   * Enters a new state.
   * 
   * @param string The state.
   * @return void
   */
  private function enterState($state) {
    $this->stateChain[] = $state;
  }
  
  /**
   * Enters a new state and registers a new Topic Maps construct.
   * 
   * @param string The state.
   * @param object The Topic Maps construct.
   * @return void
   */
  private function enterStateNewConstruct($state, $construct) {
    $this->stateChain[] = $state;
    $this->constructs[] = $construct;
  }
  
  /**
   * Leaves a state.
   * 
   * @return void
   */
  private function leaveState() {
    array_pop($this->stateChain);
  }
  
  /**
   * Leaves a state and drops the last registered Topic Maps construct.
   * 
   * @return void
   */
  private function leaveStatePopConstruct() {
    array_pop($this->stateChain);
    array_pop($this->constructs);
  }
  
  /**
   * Peeks a registered Topic Maps construct fro the stack.
   * 
   * @param mixed The stack index. If <var>null</var> the last registered Topic Maps 
   * 				construct is returned. Default <var>null</var>.
   * @return object A Topic Maps construct.
   */
  private function peekConstruct($index=null) {
    if (is_null($index)) {
      return $this->constructs[count($this->constructs)-1];
    } else {
      return $this->constructs[$index];
    }
  }
  
  /**
   * Peeks the last registered topic from the stack.
   * 
   * @return Topic The last registered topic.
   * @throws MIOException If the currently processed Topic Maps construct is not a topic.
   */
  private function peekTopic() {
    $construct = $this->peekConstruct();
    if (!$construct instanceof Topic) {
      throw new MIOException('Error in ' . __METHOD__ . 
      	': Topic maps construct is not Topic!'); 
    }
    return $construct;
  }
  
  /**
   * Gets the current state.
   * 
   * @return string The state name.
   */
  private function getCurrentState() {
    return $this->stateChain[count($this->stateChain)-1];
  }
  
  /**
   * Gets the previous state.
   * 
   * @return string
   */
  private function getPreviousState() {
    return $this->stateChain[count($this->stateChain)-2];
  }
  
  /**
   * Processes a topic.
   * 
   * @param Topic The topic to process.
   * @return void
   */
  private function handleTopic(Topic $topic) {
    $state = $this->getCurrentState();
    switch ($state) {
      case self::REIFIER:
        $this->handleReifier($this->peekConstruct(), $topic);
        break;
      case self::ISA:
        $this->peekTopic()->addType($topic);
        break;
      case self::TYPE:
        $this->handleType($this->getPreviousState(), $topic);
        break;
      case self::SCOPE:
        $this->peekConstruct()->addTheme($topic);
        break;
      case self::ROLE:
        $this->peekConstruct()->setPlayer($topic);
        break;
      default:
        break;
    }
  }
  
  /**
   * Resolves given reference against base locator.
   * 
   * @param string The reference.
   * @return string A valid URI.
   */
  private function getUri($ref) {
    $baseLocator = new Net_URL2($this->baseLocatorRef);
    return $baseLocator->resolve($ref)->getUrl();
  }
  
  /**
   * Creates a topic.
   * 
   * @param ReferenceInterface The topic reference.
   * @throws MIOException If the reference type is invalid.
   * @return Topic
   */
  private function createTopic(ReferenceInterface $topicRef) {
      $type = $topicRef->getType();
      if ($type == Reference::ITEM_IDENTIFIER) {
        return $this->createTopicByItemIdentifier(
          $this->getUri($topicRef->getReference())
        );
      } else if($type == Reference::SUBJECT_IDENTIFIER) {
        // no op.
      } else if($type == Reference::SUBJECT_LOCATOR) {
        // no op.
      } else {
        throw new MIOException('Error in ' . __METHOD__ . 
        	': Provided unexpected reference type!');
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
  private function createTopicByItemIdentifier($iid) {
    $construct = $this->topicMap->getConstructByItemIdentifier($iid);
    if (!is_null($construct)) {
      if (!$construct instanceof Topic) {
        throw new MIOException('Error in ' . __METHOD__ . 
        	': Topic Maps construct is not a topic!');
      }
      return $topic = $construct;
    }
    // Prevent merging in TM engine and use possibly existing topic with given subject identitifier.
    $topic = $this->topicMap->getTopicBySubjectIdentifier($iid);
    if (is_null($topic)) {
      return $this->topicMap->createTopicByItemIdentifier($iid);
    } else {
      $topic->addItemIdentifier($iid);
      return $topic;
    }
  }
  
  /**
   * Creates a topic with given subject identifier.
   * 
   * @param string The subject identifier.
   * @return Topic
   */
  private function createTopicBySubjectIdentifier($sid){}
  
  /**
   * Creates a topic with given subject locator.
   * 
   * @param The subject locator.
   * @return Topic
   */
  private function createTopicBySubjectLocator($slo){}
  
  /**
   * Handles Topic Map construct's reifiers.
   * 
   * @param object The Topic Maps construct.
   * @param Topic The reifier.
   * @return void
   */
  private function handleReifier($construct, Topic $reifier) {
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
  private function handleType($state, Topic $type) {
    $memoryConstruct = $this->peekConstruct();
    if (
        ($state === self::NAME && $memoryConstruct instanceof MemoryName) || 
        ($state === self::OCCURRENCE && $memoryConstruct instanceof MemoryOccurrence) ||
        ($state === self::ASSOCIATION && $memoryConstruct instanceof MemoryAssoc) ||
        ($state === self::ROLE && $memoryConstruct instanceof MemoryRole)
      ) 
    {
      $memoryConstruct->setType($type);
    }
  }
  
  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startTopicMap()
   */
  public function startTopicMap() {
    $topicMap = $this->tmSystem->getTopicMap($this->baseLocatorRef);
    if (!is_null($topicMap)) {
      throw new MIOException('Error in ' . __METHOD__ . ': Topic map with ' . 
      	'base locator "' . $this->baseLocatorRef . '" already exists!');
    }
    $this->topicMap = $this->tmSystem->createTopicMap($this->baseLocatorRef);
    if (!$this->topicMap instanceof TopicMap) {
      throw new MIOException('Error in ' . __METHOD__ . ': Topic map with ' . 
        'base locator "' . $this->baseLocatorRef . '" could not be created!'); 
    }
    $this->enterStateNewConstruct(self::TOPICMAP, $this->topicMap);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endTopicMap()
   */
  public function endTopicMap() {
    $this->stateChain = 
    $this->constructs = array();
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startTopic($identity)
   */
  public function startTopic(ReferenceInterface $identity) {
    $this->enterStateNewConstruct(self::TOPIC, $this->createTopic($identity));
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endTopic()
   */
  public function endTopic() {
    $topic = $this->peekTopic();
    $this->handleTopic($topic);
    $this->leaveStatePopConstruct();
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startAssociation()
   */
  public function startAssociation() {
    $this->enterStateNewConstruct(self::ASSOCIATION, new MemoryAssoc());
  }
  
  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endAssociation()
   */
  public function endAssociation() {
    $memoryAssoc = $this->peekConstruct();
    $assoc = $this->topicMap->createAssociation(
      $memoryAssoc->getType(), 
      $memoryAssoc->getScope()
    );
    $reifier = $memoryAssoc->getReifier();
    if ($reifier instanceof Topic) {
      $assoc->setReifier($reifier);
    }
    $iids = $memoryAssoc->getItemIdentifiers();
    foreach ($iids as $iid) {
      $assoc->addItemIdentifier($iid);
    }
    // the roles
    foreach ($memoryAssoc->getRoles() as $memoryRole) {
      $role = $assoc->createRole(
        $memoryRole->getType(), $memoryRole->getPlayer()
      );
      $reifier = $memoryRole->getReifier();
      if ($reifier instanceof Topic) {
        $role->setReifier($reifier);
      }
      $iids = $memoryRole->getItemIdentifiers();
      foreach ($iids as $iid) {
        $role->addItemIdentifier($iid);
      }
    }
    $this->leaveStatePopConstruct();
    $memoryAssoc = 
    $assoc = null;
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startRole()
   */
  public function startRole() {
    $this->enterStateNewConstruct(self::ROLE, new MemoryRole());
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endRole()
   */
  public function endRole() {
    $this->peekConstruct(count($this->constructs)-2)->addRole($this->peekConstruct());
    $this->leaveStatePopConstruct();
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startOccurrence()
   */
  public function startOccurrence() {
    $this->enterStateNewConstruct(
      self::OCCURRENCE, 
      new MemoryOccurrence()
    );
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endOccurrence()
   */
  public function endOccurrence() {
    $memoryOcc = $this->peekConstruct();
    $this->leaveStatePopConstruct();
    $occ = $this->peekTopic()->createOccurrence(
      $memoryOcc->getType(), 
      $memoryOcc->getValue(), 
      $memoryOcc->getDatatype(), 
      $memoryOcc->getScope()
    );
    $reifier = $memoryOcc->getReifier();
    if ($reifier instanceof Topic) {
      $occ->setReifier($reifier);
    }
    $iids = $memoryOcc->getItemIdentifiers();
    foreach ($iids as $iid) {
      $occ->addItemIdentifier($iid);
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startName()
   */
  public function startName() {
    $this->enterStateNewConstruct(self::NAME, new MemoryName());
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endName()
   */
  public function endName() {
    $memoryName = $this->peekConstruct();
    $this->leaveStatePopConstruct();
    $name = $this->peekTopic()->createName(
      $memoryName->getValue(), 
      $memoryName->getType(), 
      $memoryName->getScope()
    );
    $reifier = $memoryName->getReifier();
    if ($reifier instanceof Topic) {
      $name->setReifier($reifier);
    }
    $iids = $memoryName->getItemIdentifiers();
    foreach ($iids as $iid) {
      $name->addItemIdentifier($iid);
    }
    $memoryVariants = $memoryName->getVariants();
    foreach ($memoryVariants as $memoryVariant) {
      $variant = $name->createVariant(
        $memoryVariant->getValue(), 
        $memoryVariant->getDatatype(), 
        $memoryVariant->getScope()
      );
      $reifier = $memoryVariant->getReifier();
      if ($reifier instanceof Topic) {
        $variant->setReifier($reifier);
      }
    }
    $memoryName = 
    $name = null;
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startVariant()
   */
  public function startVariant() {
    $this->enterStateNewConstruct(
      self::VARIANT, 
      new MemoryVariant()
    );
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endVariant()
   */
  public function endVariant() {
    $memoryVariant = $this->peekConstruct();
    $this->leaveStatePopConstruct();
    $this->peekConstruct()->addVariant($memoryVariant);
    $memoryVariant = null;
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startScope()
   */
  public function startScope() {
    $construct = $this->peekConstruct();
    if ($construct instanceof Scoped || $construct instanceof MemoryScoped) {
      $this->enterState(self::SCOPE);
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endScope()
   */
  public function endScope() {
    $this->leaveState();
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#nameValue($value)
   */
  public function nameValue($value) {
    $memoryName = $this->peekConstruct();
    if ($this->getCurrentState() === self::NAME && $memoryName instanceof MemoryName) {
      $memoryName->setValue(trim($value));
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#value($value, $datatype)
   */
  public function value($value, $datatype) {
    if ($this->getCurrentState() == self::OCCURRENCE) {
      $memoryOcc = $this->peekConstruct();
      if ($memoryOcc instanceof MemoryOccurrence) {
        $memoryOcc->setValue(trim($value), $datatype);
      }
    } 
    if ($this->getCurrentState() == self::VARIANT) {
      $memoryVariant = $this->peekConstruct();
      if ($memoryVariant instanceof MemoryVariant) {
        $memoryVariant->setValue(trim($value), $datatype);
      }
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#subjectIdentifier($sid)
   */
  public function subjectIdentifier($sid) {
    if ($this->getCurrentState() === self::TOPIC) {
      $this->peekConstruct()->addSubjectIdentifier($sid);
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#subjectLocator($slo)
   */
  public function subjectLocator($slo) {
    if ($this->getCurrentState() === self::TOPIC) {
      $this->peekConstruct()->addSubjectLocator($slo);
    }
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#itemIdentifier($iid)
   */
  public function itemIdentifier($iid) {
    $_iid = $this->getUri($iid);
    $this->peekConstruct()->addItemIdentifier($_iid);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startType()
   */
  public function startType() {
    $this->enterState(self::TYPE);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endType()
   */
  public function endType() {
    $this->leaveState(self::TYPE);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startReifier()
   */
  public function startReifier() {
    $this->enterState(self::REIFIER);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endReifier()
   */
  public function endReifier() {
    $this->leaveState(self::REIFIER);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#topicRef($identity)
   */
  public function topicRef(ReferenceInterface $identity) {
    $this->handleTopic($this->createTopic($identity));
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startIsa()
   */
  public function startIsa() {
    $this->enterState(self::ISA);
  }

  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endIsa()
   */
  public function endIsa() {
    $this->leaveState(self::ISA);
  }
  
  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#startMergeMap($document)
   */
  public function startMergeMap($locator) {
    if ($this->mergeMapCount > $this->maxMergeMapCount) {
      throw new MIOException('Error in ' . __METHOD__ . ': Exceeded merge map count!');
    }
    $currentTopicMap = $this->topicMap;
    $baseLocator = $this->locator->resolve($locator)->getUrl();
    $mapHandler = new self(
      $this->tmSystem, 
      $baseLocator, 
      $this->maxMergeMapCount, 
      $this->mergeMapCount+1
    );
    $parser = new XTM20TopicMapReader($mapHandler);
    $parser->readXtmFile($baseLocator);
    $currentTopicMap->mergeIn($mapHandler->getTopicMap());
  }
  
  /**
   * (non-PHPdoc)
   * @see src/in/PHPTMAPITopicMapHandlerInterface#endMergeMap()
   */
  public function endMergeMap() {
    // no op.
  }
  
  /**
   * Returns the current topic map.
   * 
   * @return TopicMap The topic map.
   */
  public function getTopicMap() {
    return $this->topicMap;
  }
  
  /**
   * Returns the base locator.
   * 
   * @return string The base locator.
   */
  public function getBaseLocator() {
    return $this->baseLocatorRef;
  }

}
?>