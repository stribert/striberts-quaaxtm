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
require_once('Reference.class.php');

/**
 * Parses JTM 1.0 and JTM 1.1 - and passes the results to a topic map handler.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class JTM101TopicMapReader {
  
  private $tmHandler,
          $jtm11,
          $prefixes;
  
  /**
   * Constructor.
   * 
   * @param PHPTMAPITopicMapHandlerInterface The topic map handler.
   * @return void
   */
  public function __construct(PHPTMAPITopicMapHandlerInterface $tmHandler) {
    $this->tmHandler = $tmHandler;
    $this->jtm11 = false;
    $this->prefixes = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    $this->tmHandler = null;
  }

  /**
   * Reads given JTM file and parses contained JTM via {@link read()}.
   * 
   * @param string The JTM file locator.
   * @return void
   * @throws MIOException If file cannot be read.
   * @throws MIOException If json_decode() returns <var>null</var>.
   * @see read()
   */
  public function readFile($file) {
    $jtm = MIOUtil::readFile($file);
    $this->read($jtm);
  }
  
  /**
   * Parses JTM.
   * 
   * @param string The JTM.
   * @return void
   * @throws MIOException If json_decode() returns <var>null</var>.
   * @throws MIOException If key "version" is missing.
   * @throws MIOException If version is neither 1.0 nor 1.1.
   */
  public function read($jtm) {
    $struct = json_decode($jtm, true);
    if (is_null($struct)) {
      throw new MIOException('Error in ' . __METHOD__ . ': JSON could not been decoded.');
    }
    if (!isset($struct['version'])) {
      throw new MIOException('Error in ' . __METHOD__ . ': Missing key "version".');
    }
    $version = $struct['version'];
    if ($version != '1.0' && $version != '1.1') {
      throw new MIOException(
      	'Error in ' . __METHOD__ . ': JTM version is neither "1.0" nor "1.1".'
      );
    }
    
    if ($version == '1.0' && isset($struct['prefixes'])) {
      throw new MIOException(
      	'Error in ' . __METHOD__ . ': Prefixes are not allowed in JTM 1.0.'
      );
    }
    
    if ($version == '1.1') {
      $this->jtm11 = true;
      $this->prefixes['xsd'] = 'http://www.w3.org/2001/XMLSchema#';
      if (isset($struct['prefixes']) && is_array($struct['prefixes'])) {
        foreach ($struct['prefixes'] as $prefix => $uri) {
          if ($prefix != 'xsd') {
            $this->prefixes[$prefix] = $uri;
          }
        }
      }
    }
    
    if (!isset($struct['item_type'])) {
      throw new MIOException('Error in ' . __METHOD__ . ': Missing key "item_type".');
    }
    
    $struct['item_type'] = strtolower($struct['item_type']);
    
    switch ($struct['item_type']) {
      case 'topicmap':
        $this->readTopicMap($struct);
        break;
      case 'association':
        $this->readDetachedTopicMapProperty($struct,'association');
        break;
      case 'topic':
        $this->readDetachedTopicMapProperty($struct,'topic');
        break;
      case 'name':
        $this->readDetachedTopicProperty($struct, 'name');
        break;
      case 'occurrence':
        $this->readDetachedTopicProperty($struct, 'occurrence');
        break;
      default:
        return;
    }
  }
  
  /**
   * Parses detached topic properties.
   * 
   * @param array The topic property.
   * @param string The item type.
   * @return void
   * @throws MIOException If the property has no parent.
   */
  private function readDetachedTopicProperty(array $property, $itemType) {
    if (!isset($property['parent'])) {
      throw new MIOException('Error in ' . __METHOD__ . ': Topic property has no parent.');
    }
    $this->tmHandler->startTopicMap();
    $this->readParentTopic($property['parent']);
    if ($itemType == 'name') {
      $this->readNames(array($property));
    } else {
      $this->readOccurrences(array($property));
    }
    $this->tmHandler->endTopicMap();
  }
  
  /**
   * Parses the parent topic.
   * 
   * @param array The topic's identifiers.
   * @return void
   * @throws MIOException If the topic has no identity.
   */
  private function readParentTopic(array $ids) {
    $i = 0;
    foreach ($ids as $id) {
      $struct = $this->deconstructTopicReference($id);
      switch ($struct[1]) {
        case ReferenceInterface::SUBJECT_IDENTIFIER:
          if ($i == 0) {
            $this->tmHandler->startTopic(
              new Reference(
                $struct[0], 
                Reference::SUBJECT_IDENTIFIER
              )
            );
          } else {
            $this->tmHandler->subjectIdentifier($struct[0]);
          }
          break;
        case ReferenceInterface::SUBJECT_LOCATOR:
          if ($i == 0) {
            $this->tmHandler->startTopic(
              new Reference(
                $struct[0], 
                Reference::SUBJECT_LOCATOR
              )
            );
          } else {
            $this->tmHandler->subjectLocator($struct[0]);
          }
          break;
        case ReferenceInterface::ITEM_IDENTIFIER:
          if ($i == 0) {
            $this->tmHandler->startTopic(
              new Reference(
                $struct[0], 
                Reference::ITEM_IDENTIFIER
              )
            );
          } else {
            $this->tmHandler->itemIdentifier($struct[0]);
          }
          break;
        default:
          throw new MIOException('Error in ' . __METHOD__ . ': Topic has no identity.');
          break;
      }
      $i++;
    }
  }
  
  /**
   * Parses topic map properties.
   * 
   * @param array The topic map property.
   * @param string The item type.
   * @return void
   */
  private function readDetachedTopicMapProperty(array $property, $itemType) {
    $this->tmHandler->startTopicMap();
    if ($itemType == 'association') {
      $this->readAssociations(array($property));
    } else {
      $this->readTopics(array($property));
    }
    $this->tmHandler->endTopicMap();
  }
  
  /**
   * Parses a topic map.
   * 
   * @param array The topic map.
   * @return void
   */
  private function readTopicMap(array $topicMap) {
    $this->tmHandler->startTopicMap();
    
    if (isset($topicMap['reifier'])) {
      $this->handleReifier($topicMap['reifier']);
    }
    
    if (isset($topicMap['item_identifiers'])) {
      $this->handleIids($topicMap['item_identifiers']);
    }
    
    if (isset($topicMap['topics'])) {
      $this->readTopics($topicMap['topics']);
    }
    
    if (isset($topicMap['associations'])) {
      $this->readAssociations($topicMap['associations']);
    }
    
    $this->tmHandler->endTopicMap();
  }
  
  /**
   * Parses topics.
   * 
   * @param array The topics.
   * @return void
   * @throws MIOException If the topic has no identity.
   */
  private function readTopics(array $topics) {
    foreach ($topics as $topic) {
      if (isset($topic['subject_identifiers']) && !empty($topic['subject_identifiers'])) {
        $ref = $this->getReference($topic['subject_identifiers'][0]);
        $this->tmHandler->startTopic(new Reference($ref, Reference::SUBJECT_IDENTIFIER));
        unset($topic['subject_identifiers'][0]);
      } else if (isset($topic['subject_locators']) && !empty($topic['subject_locators'])) {
        $ref = $this->getReference($topic['subject_locators'][0]);
        $this->tmHandler->startTopic(new Reference($ref, Reference::SUBJECT_LOCATOR));
        unset($topic['subject_locators'][0]);
      } else if (isset($topic['item_identifiers']) && !empty($topic['item_identifiers'])) {
        $ref = $this->getReference($topic['item_identifiers'][0]);
        $this->tmHandler->startTopic(new Reference($ref, Reference::ITEM_IDENTIFIER));
        unset($topic['item_identifiers'][0]);
      } else {
        throw new MIOException('Error in ' . __METHOD__ . ': Topic has no identity.');
      }
      
      if (isset($topic['subject_identifiers'])) {
        foreach ($topic['subject_identifiers'] as $sid) {
          $sid = $this->getReference($sid);
          $this->tmHandler->subjectIdentifier($sid);
        }
      }
      
      if (isset($topic['subject_locators'])) {
        foreach ($topic['subject_locators'] as $slo) {
          $slo = $this->getReference($slo);
          $this->tmHandler->subjectLocator($slo);
        }
      }
      
      if (isset($topic['item_identifiers'])) {
        $this->handleIids($topic['item_identifiers']);
      }
      
      if (!$this->jtm11 && isset($topic['instance_of'])) {
        throw new MIOException(
        	'Error in ' . __METHOD__ . ': "instance_of" is not allowed in JTM 1.0.'
        );
      }
      
      if (isset($topic['instance_of'])) {
        $this->tmHandler->startIsa();
        foreach ($topic['instance_of'] as $topicRef) {
          $struct = $this->deconstructTopicReference($topicRef);
          $this->tmHandler->topicRef(new Reference($struct[0], $struct[1]));
        }
        $this->tmHandler->endIsa();
      }
      
      if (isset($topic['names'])) {
        $this->readNames($topic['names']);
      }
      
      if (isset($topic['occurrences'])) {
        $this->readOccurrences($topic['occurrences']);
      }
      
      $this->tmHandler->endTopic();
    }
  }
  
  /**
   * Parses occurrences.
   * 
   * @param array The occurrences.
   * @return void
   * @throws MIOException If required type is missing.
   * @throws MIOException If required value is missing.
   */
  private function readOccurrences(array $occs) {
    foreach ($occs as $occ) {
      if (!isset($occ['type'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "type".');
      }
      if (!isset($occ['value'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "value".');
      }
      $this->tmHandler->startOccurrence();
      
      if (isset($occ['reifier'])) {
        $this->handleReifier($occ['reifier']);
      }
      
      if (isset($occ['item_identifiers'])) {
        $this->handleIids($occ['item_identifiers']);
      }
      
      $this->handleType($occ['type']);
      
      $datatype = isset($occ['datatype']) 
        ? $this->getReference($occ['datatype']) 
        : MIOUtil::XSD_STRING;
      $this->tmHandler->value($occ['value'], $datatype);
      
      if (isset($occ['scope'])) {
        $this->handleScope($occ['scope']);
      }
      
      $this->tmHandler->endOccurrence();
    }
  }
  
  /**
   * Parses topic names.
   * 
   * @param array The topic names.
   * @return void
   * @throws MIOException If required value is missing.
   */
  private function readNames(array $names) {
    foreach ($names as $name) {
      if (!isset($name['value'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "value".');
      }
      $this->tmHandler->startName();
      
      if (isset($name['reifier'])) {
        $this->handleReifier($name['reifier']);
      }
      
      if (isset($name['item_identifiers'])) {
        $this->handleIids($name['item_identifiers']);
      }
      
      if (isset($name['type'])) {
        $this->handleType($name['type']);
      }
      
      $this->tmHandler->nameValue($name['value'], MIOUtil::XSD_STRING);
      
      if (isset($name['scope'])) {
        $this->handleScope($name['scope']);
      }
      
      if (isset($name['variants'])) {
        $this->readVariants($name['variants']);
      }
      
      $this->tmHandler->endName();
    }
  }
  
  /**
   * Parses name variants.
   * 
   * @param array The name variants.
   * @return void
   * @throws MIOException If required scope is missing.
   * @throws MIOException If required value is missing.
   */
  private function readVariants(array $variants) {
    foreach ($variants as $variant) {
      if (!isset($variant['scope'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "scope".');
      }
      if (!isset($variant['value'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "value".');
      }
      $this->tmHandler->startVariant();
      
      if (isset($variant['reifier'])) {
        $this->handleReifier($variant['reifier']);
      }
      
      if (isset($variant['item_identifiers'])) {
        $this->handleIids($variant['item_identifiers']);
      }
      
      $this->handleScope($variant['scope']);
      
      $datatype = isset($variant['datatype']) 
        ? $this->getReference($variant['datatype']) 
        : MIOUtil::XSD_STRING;
      $this->tmHandler->value($variant['value'], $datatype);
      
      $this->tmHandler->endVariant();
    }
  }
  
  /**
   * Parses associations.
   * 
   * @param array The associations.
   * @return void
   * @throws MIOException If required type is missing.
   * @throws MIOException If required roles are missing.
   */
  private function readAssociations(array $assocs) {
    foreach ($assocs as $assoc) {
      if (!isset($assoc['type'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "type".');
      }
      if (!isset($assoc['roles'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "roles".');
      }
      if (empty($assoc['roles'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Roles must not be empty.');
      }
      $this->tmHandler->startAssociation();
      
      if (isset($assoc['reifier'])) {
        $this->handleReifier($assoc['reifier']);
      }
      
      if (isset($assoc['item_identifiers'])) {
        $this->handleIids($assoc['item_identifiers']);
      }
      
      $this->handleType($assoc['type']);
      
      if (isset($assoc['scope'])) {
        $this->handleScope($assoc['scope']);
      }
      
      $this->readRoles($assoc['roles']);
      
      $this->tmHandler->endAssociation();
    }
  }
  
  /**
   * Parses association roles.
   * 
   * @param array The association roles.
   * @return void
   * @throws MIOException If required player is missing.
   * @throws MIOException If required type is missing.
   */
  private function readRoles(array $roles) {
    foreach ($roles as $role) {
      if (!isset($role['player'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "player".');
      }
      if (!isset($role['type'])) {
        throw new MIOException('Error in ' . __METHOD__ . ': Missing required "type".');
      }
      $this->tmHandler->startRole();
      
      if (isset($role['reifier'])) {
        $this->handleReifier($role['reifier']);
      }
      
      if (isset($role['item_identifiers'])) {
        $this->handleIids($role['item_identifiers']);
      }
      
      $this->handleType($role['type']);
      
      $struct = $this->deconstructTopicReference($role['player']);
      $this->tmHandler->topicRef(new Reference($struct[0], $struct[1]));
      
      $this->tmHandler->endRole();
    }
  }
  
  /**
   * Handles a scope.
   * 
   * @param array The scope.
   * @return void
   */
  private function handleScope(array $scope) {
    $this->tmHandler->startScope();
    foreach ($scope as $topicRef) {
      $struct = $this->deconstructTopicReference($topicRef);
      $this->tmHandler->topicRef(new Reference($struct[0], $struct[1]));
    }
    $this->tmHandler->endScope();
  }
  
  /**
   * Handles a type.
   * 
   * @param string The type (topic) reference.
   * @return void
   */
  private function handleType($topicRef) {
    $struct = $this->deconstructTopicReference($topicRef);
    $this->tmHandler->startType();
    $this->tmHandler->topicRef(new Reference($struct[0], $struct[1]));
    $this->tmHandler->endType();
  }
  
  /**
   * Handles item identifiers.
   * 
   * @param array The item identifiers.
   * @return void
   */
  private function handleIids(array $iids) {
    foreach ($iids as $iid) {
      $iid = $this->getReference($iid);
      $this->tmHandler->itemIdentifier($iid);
    }
  }
  
  /**
   * Handles a reifier.
   * 
   * @param string The reifier (topic) reference.
   * @return void
   */
  private function handleReifier($topicRef) {
    if ($topicRef === 'NULL') {
      return;
    }
    $struct = $this->deconstructTopicReference($topicRef);
    $this->tmHandler->startReifier();
    $this->tmHandler->topicRef(new Reference($struct[0], $struct[1]));
    $this->tmHandler->endReifier();
  }
  
  /**
   * Deconstructs a topic reference.
   * 
   * @param string The topic reference.
   * @return void
   */
  private function deconstructTopicReference($topicRef) {
    $pos = stripos($topicRef, ':');
    $refType = substr($topicRef, 0, $pos);
    $ref = substr($topicRef, $pos+1, strlen($topicRef));
    switch ($refType) {
      case 'ii':
        return array($ref, Reference::ITEM_IDENTIFIER);
        break;
      case 'si':
        return array($ref, Reference::SUBJECT_IDENTIFIER);
        break;
      case 'sl':
        return array($ref, Reference::SUBJECT_LOCATOR);
        break;
      default:
        throw new MIOException(
        	'Error in ' . __METHOD__ . ': Invalid reference type: ' . $refType . '.'
        );
        break;
    }
  }
  
  /**
   * Gets the reference (assume URI).
   * Handles Safe_CURIEs.
   * 
   * @param string A reference (assume URI) or a Safe_CURIE declaration.
   * @return string The reference (assume URI).
   * @throws MIOException If "]" is missing in Safe_CURIE declaration.
   * @throws MIOException If colon is missing in Safe_CURIE declaration.
   * @throws MIOException If prefix is not registered.
   */
  private function getReference($str) {
    $firstChar = $str{0};
    if ($firstChar != '[') {
      return $str;
    } else {// detected Safe_CURIE, have to verify
      $lastChar = $str{strlen($str)-1};
      if ($lastChar != ']') {
        throw new MIOException(
        	'Error in ' . __METHOD__ . ': Missing trailing "]" in Safe_CURIE declaration.'
        );
      }
      $str = substr($str, 1, strlen($str)-2);
      $pos = stripos($str, ':');
      if ($pos === false) {
        throw new MIOException(
        	'Error in ' . __METHOD__ . ': Missing colon in Safe_CURIE declaration.'
        );
      }
      $prefix = substr($str, 0, $pos);
      if (!array_key_exists($prefix, $this->prefixes)) {
        throw new MIOException('Error in ' . __METHOD__ . ': CURIE prefix is not registered.');
      }
      $baseUri = $this->prefixes[$prefix];
      $pathFragment = substr($str, $pos+1, strlen($str));
      return $baseUri . $pathFragment;
    }
  }
}
?>