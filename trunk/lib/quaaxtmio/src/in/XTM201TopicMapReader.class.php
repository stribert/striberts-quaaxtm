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
require_once('Reference.class.php');

/**
 * Parses XTM 2.0 and XTM 2.1. Passes the results to a topic map handler.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @author Lars Heuer <mail@semagia.com>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class XTM201TopicMapReader {
  
  const TAG_TOPIC_MAP = 'topicMap',
        TAG_TOPIC = 'topic',
        TAG_ASSOCIATION = 'association',
        TAG_ROLE = 'role',
        TAG_OCCURRENCE = 'occurrence',
        TAG_NAME = 'name',
        TAG_VARIANT = 'variant',
        
        TAG_INSTANCE_OF = 'instanceOf',
        TAG_TYPE = 'type',
        
        TAG_VALUE = 'value',
        TAG_RESOURCE_REF = 'resourceRef',
        TAG_RESOURCE_DATA = 'resourceData',
        
        TAG_SCOPE = 'scope',
        
        TAG_TOPIC_REF = 'topicRef',
        
        TAG_SUBJECT_IDENTIFIER = 'subjectIdentifier',
        TAG_SUBJECT_LOCATOR = 'subjectLocator',
        TAG_ITEM_IDENTITY = 'itemIdentity',
        
        TAG_MERGEMAP = 'mergeMap',
        
        TAG_REIFIER = 'reifier',
        TAG_SUBJECT_IDENTIFIER_REF = 'subjectIdentifierRef',
        TAG_SUBJECT_LOCATOR_REF = 'subjectLocatorRef',
        
        
        ATTR_ID = 'id',
        ATTR_HREF = 'href',
        ATTR_REIFIER = 'reifier',
        ATTR_DATATYPE = 'datatype',
        ATTR_VERSION = 'version',
        
        STATE_INITIAL = 0,
        STATE_TOPIC_MAP = 1,
        STATE_TOPIC = 2,
        STATE_ASSOCIATION = 3,
        STATE_ROLE = 4,
        STATE_TYPE = 5,
        STATE_INSTANCE_OF = 6,
        STATE_SCOPE = 7,
        STATE_OCCURRENCE = 8,
        STATE_NAME = 9,
        STATE_VARIANT = 10,
        STATE_REIFIER = 11;
        
  private $_sax,
          $_tmHandler,
          $_state,
          $_nextState,
          $_data,
          $_datatype,
          $_xtm20,
          $_seenReifier,
          $_seenIdentity;
          
  private static $_supportedEncodings = array('UTF-8', 'ISO-8859-1', 'US-ASCII');

  /**
   * Constructor.
   * 
   * @param PHPTMAPITopicMapHandlerInterface The topic map handler.
   * @param string The source XTM encoding. Default <code>UTF-8</code>.
   * @return void
   * @throws MIOException If the provided encoding is not supported. Supported encodings 
   * 				are UTF-8, ISO-8859-1, or US-ASCII.
   */
  public function __construct(PHPTMAPITopicMapHandlerInterface $tmHandler, $encoding='UTF-8') {
    if (!in_array($encoding, self::$_supportedEncodings)) {
      throw new MIOException(
      	'Error in ' . __METHOD__ . ': Encoding "' . $encoding . '" is not supported.'
      );
    }
    $this->_tmHandler = $tmHandler;
    $this->_sax = xml_parser_create($encoding);
    xml_set_object($this->_sax, $this);
    xml_parser_set_option($this->_sax, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($this->_sax, '_open', '_close');
    xml_set_character_data_handler($this->_sax, '_data');
    $this->_state = 
    $this->_nextState = self::STATE_INITIAL;
    $this->_data = 
    $this->_datatype = null;
    $this->_seenReifier = 
    $this->_seenIdentity = 
    $this->_xtm20 = false;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    xml_parser_free($this->_sax);
    unset($this->_sax);
    unset($this->_tmHandler);
    unset($this->_data);
    unset($this->_datatype);
  }

  /**
   * Reads given XTM file and parses contained XTM.
   * 
   * @param string The XTM file locator.
   * @return void
   * @throws MIOException If parse error occurs.
   */
  public function readFile($file) {
    $xtm = MIOUtil::readFile($file);
    $this->read($xtm);
  }
  
  /**
   * Parses given XTM.
   * 
   * @param string The XTM.
   * @return void
   * @throws MIOException If parse error occurs.
   */
  public function read($xtm) {
    if (!xml_parse($this->_sax, $xtm)) { 
      $error = xml_error_string (xml_get_error_code($this->_sax));
      $line = xml_get_current_line_number($this->_sax);
      throw new MIOException(
      	'Error in ' . __METHOD__ . ': Parse error "' . $error . '" on line ' . $line . '.'
      );
    }
  }

  /**
   * Processes XML opening tags.
   * 
   * @param resource The PHP XML parser.
   * @param string The XML element name.
   * @param array XML element attributes.
   * @return void
   * @throws MIOException
   */
  private function _open($sax, $element, array $attributes) {
    switch ($element) {
      case self::TAG_TOPIC:
        $this->_state = self::STATE_TOPIC;
        $id = isset($attributes[self::ATTR_ID]) ? $attributes[self::ATTR_ID] : null;
        if (!is_null($id)) {
          $this->_tmHandler->startTopic(
            new Reference('#' . $id, ReferenceInterface::ITEM_IDENTIFIER)
          );
        } else if ($this->_xtm20) {
          throw new MIOException(
          	'Illegal XTM 2.0 instance: The id attribute is missing from the <topic/> element.'
          );
        }
        $this->_seenIdentity = !is_null($id);
        break;
      case self::TAG_SUBJECT_IDENTIFIER:
        if (!$this->_seenIdentity) {
          $this->_tmHandler->startTopic(
            new Reference(
              $this->_handleHref($attributes[self::ATTR_HREF]), 
              ReferenceInterface::SUBJECT_IDENTIFIER
            )
          );
          $this->_seenIdentity = true;
        } else {
          $this->_tmHandler->subjectIdentifier(
            $this->_handleHref($attributes[self::ATTR_HREF])
          );
        }
        break;
      case self::TAG_SUBJECT_LOCATOR:
        if (!$this->_seenIdentity) {
          $this->_tmHandler->startTopic(
            new Reference(
              $this->_handleHref($attributes[self::ATTR_HREF]), 
              ReferenceInterface::SUBJECT_LOCATOR
            )
          );
          $this->_seenIdentity = true;
        } else {
          $this->_tmHandler->subjectLocator(
            $this->_handleHref($attributes[self::ATTR_HREF])
          );
        }
        break;
      case self::TAG_ITEM_IDENTITY:
        if ($this->_state == self::STATE_TOPIC && !$this->_seenIdentity) {
          $this->_tmHandler->startTopic(
            new Reference(
              $this->_handleHref($attributes[self::ATTR_HREF]), 
              ReferenceInterface::ITEM_IDENTIFIER
            )
          );
          $this->_seenIdentity = true;
        }
        $this->_tmHandler->itemIdentifier($this->_handleHref($attributes[self::ATTR_HREF]));
        break;
      case self::TAG_INSTANCE_OF:
        $this->_state = self::STATE_INSTANCE_OF;
        $this->_tmHandler->startIsa();
        break;
      case self::TAG_TOPIC_REF:
        $href = $attributes[self::ATTR_HREF];
        if ($this->_xtm20 && strrpos($href, "#") === false) {
          throw new MIOException(
  					'Invalid topic reference "' . $href . '". does not contain a fragment identifier.'
	        );
        }
        $this->_handleTopicReference(
          new Reference($this->_handleHref($href), ReferenceInterface::ITEM_IDENTIFIER)
        );
        break;
      case self::TAG_SUBJECT_IDENTIFIER_REF:
        if ($this->_xtm20) {
          throw new MIOException('The <subjectIdentifierRef/> element is disallowed in XTM 2.0.');
        }
        $this->_handleTopicReference(
          new Reference(
            $this->_handleHref($attributes[self::ATTR_HREF]), 
            ReferenceInterface::SUBJECT_IDENTIFIER
          )
        );
        break;
      case self::TAG_SUBJECT_LOCATOR_REF:
        if ($this->_xtm20) {
          throw new MIOException('The <subjectLocatorRef/> element is disallowed in XTM 2.0.');
        }
        $this->_handleTopicReference(
          new Reference(
            $this->_handleHref($attributes[self::ATTR_HREF]), 
            ReferenceInterface::SUBJECT_LOCATOR
          )
        );
        break;
      case self::TAG_NAME:
        $this->_state = self::STATE_NAME;
        $this->_tmHandler->startName();
        $this->_handleReifier($attributes);
        break;
      case self::TAG_VALUE:
        //no op.
        break;
      case self::TAG_TYPE:
        $this->_nextState = $this->_state;
        $this->_state = self::STATE_TYPE;
        $this->_tmHandler->startType();
        break;
      case self::TAG_SCOPE:
        $this->_nextState = $this->_state;
        $this->_state = self::STATE_SCOPE;
        $this->_tmHandler->startScope();
        break;
      case self::TAG_REIFIER:
        if ($this->_xtm20) {
          throw new MIOException('The <reifier/> element is disallowed in XTM 2.0.');
        } else if ($this->_seenReifier) {
          throw new MIOException('Found a reifier attribute and reifier element.');
        }
        $this->_nextState = $this->_state;
        $this->_state = self::STATE_REIFIER;
        $this->_tmHandler->startReifier();
        break;
      case self::TAG_VARIANT:
        $this->_state = self::STATE_VARIANT;
        $this->_tmHandler->startVariant();
        $this->_handleReifier($attributes);
        break;
      case self::TAG_RESOURCE_DATA:
        $datatype = $this->_getAttributeValue($attributes, self::ATTR_DATATYPE);
        $this->_datatype = $datatype == null ? MIOUtil::XSD_STRING : $datatype;
        break;
      case self::TAG_RESOURCE_REF:
        $data = $this->_handleHref($attributes[self::ATTR_HREF]);
        $this->_tmHandler->value($data, MIOUtil::XSD_ANYURI);
        break;
      case self::TAG_OCCURRENCE:
        $this->_state = self::STATE_OCCURRENCE;
        $this->_tmHandler->startOccurrence();
        $this->_handleReifier($attributes);
        break;
      case self::TAG_ASSOCIATION:
        $this->_state = self::STATE_ASSOCIATION;
        $this->_tmHandler->startAssociation();
        $this->_handleReifier($attributes);
        break;
      case self::TAG_ROLE:
        $this->_state = self::STATE_ROLE;
        $this->_tmHandler->startRole();
        $this->_handleReifier($attributes);
        break;
      case self::TAG_MERGEMAP:
        $this->_handleMergeMap($attributes);
        break;
      case self::TAG_TOPIC_MAP:
        if (!isset($attributes[self::ATTR_VERSION])) {
          throw new MIOException(
          	'Error in ' . __METHOD__ . ': Missing attribute "version".'
          );
        }
        if (!self::_isXtm21($attributes[self::ATTR_VERSION])) {
          if (!self::_isXtm2($attributes[self::ATTR_VERSION])) {
            throw new MIOException(
            	'Error in ' . __METHOD__ . 
              	': Expect version 2.0 or 2.1! Received version ' . 
                  $attributes[self::ATTR_VERSION] . '.'
            );
          } else {
            $this->_xtm20 = true;
          }
        }
        $this->_state = self::STATE_TOPIC_MAP;
        $this->_tmHandler->startTopicMap();
        $this->_handleReifier($attributes);
        break;
      default:
        return;
    }
  }

  /**
   * Processes XML closing tags.
   * 
   * @param resource The PHP XML parser.
   * @param string The XML element name.
   * @return void
   * @access private
   */
  private function _close($sax, $element) {
    switch ($element) {
      case self::TAG_TOPIC:
        $this->_state = self::STATE_TOPIC_MAP;
        $this->_tmHandler->endTopic();
        break;
      case self::TAG_INSTANCE_OF:
        $this->_state = self::STATE_TOPIC;
        $this->_tmHandler->endIsa();
        break;
      case self::TAG_NAME:
        $this->_state = self::STATE_TOPIC;
        $this->_tmHandler->endName();
        break;
      case self::TAG_VALUE:
        $this->_tmHandler->nameValue($this->_data);
        $this->_data = null;
        break;
      case self::TAG_TYPE:
        $this->_state = $this->_nextState;
        $this->_tmHandler->endType();
        break;
      case self::TAG_SCOPE:
        $this->_state = $this->_nextState;
        $this->_tmHandler->endScope();
        break;
      case self::TAG_REIFIER:
        $this->_state = $this->_nextState;
        $this->_tmHandler->endReifier();
        break;
      case self::TAG_VARIANT:
        $this->_state = self::STATE_NAME;
        $this->_tmHandler->endVariant();
        break;
      case self::TAG_RESOURCE_DATA:
        $this->_tmHandler->value($this->_data, $this->_datatype);
        $this->_data = null;
        break;
      case self::TAG_OCCURRENCE:
        $this->_state = self::STATE_TOPIC;
        $this->_tmHandler->endOccurrence();
        break;
      case self::TAG_ASSOCIATION:
        $this->_state = self::STATE_TOPIC_MAP;
        $this->_tmHandler->endAssociation();
        break;
      case self::TAG_ROLE:
        $this->_state = self::STATE_ASSOCIATION;
        $this->_tmHandler->endRole();
        break;
      case self::TAG_TOPIC_MAP:
        $this->_state = self::STATE_INITIAL;
        $this->_tmHandler->endTopicMap();
        break;
      default:
        return;
    }
  }

  /**
   * Processes XML data.
   * 
   * @param resource The PHP XML parser.
   * @param string The XML data.
   * @return void
   */
  private function _data($sax, $data) { 
    $this->_data .= $data;
  }
  
  /**
   * Returns if the provided version is '2.0'.
   * 
   * @param string $version
   * @return boolean If $version is '2.0'.
   */
  private static function _isXtm2($version) {
    return !empty($version) && $version === '2.0';
  }

  /**
   * Returns if the provided version is '2.1'.
   * 
   * @param string $version
   * @return boolean If $version is '2.1'.
   */
  private static function _isXtm21($version) {
    return !empty($version) && $version === '2.1';
  }
  
  /**
   * Processes a reifier.
   * 
   * @param array The XML element attributes.
   * @return void
   */
  private function _handleReifier(array $attributes) {
    if (isset($attributes[self::ATTR_REIFIER])) {
      $this->_tmHandler->startReifier();
      $this->_tmHandler->topicRef(
        new Reference(
          $this->_handleHref($attributes[self::ATTR_REIFIER]), 
          ReferenceInterface::ITEM_IDENTIFIER
        )
      );
      $this->_tmHandler->endReifier();
      $this->_seenReifier = true;
    } else {
      $this->_seenReifier = false;
    }
  }
   
  /**
   * Processes a topic reference.
   * 
   * @param ReferenceInterface The topic reference.
   * @return void
   * @throws MIOException If <topicRef> occurs in unexpected state. 
   */
  private function _handleTopicReference(ReferenceInterface $topicRef) {
    if (
        $this->_state === self::STATE_INSTANCE_OF ||
        $this->_state === self::STATE_TYPE ||
        $this->_state === self::STATE_SCOPE ||
        $this->_state === self::STATE_ROLE ||
        $this->_state === self::STATE_REIFIER
      ) 
    {
      $this->_tmHandler->topicRef($topicRef);  
    } else {
      throw new MIOException('Error in ' . __METHOD__ . ': Unexpected "topicRef" element!');
    }
  }
  
  /**
   * Gets an XML element attribute value.
   * 
   * @param array The XML element attributes.
   * @param string The XML element attribute name.
   * @return string|null The XML element attribute value or <var>null</var> if
   * 				attribute name is wrong.
   */
  private function _getAttributeValue(array $attributes, $value) {
    return isset($attributes[$value]) ? $attributes[$value] : null;
  }
  
  /**
   * Handles an href value.
   * 
   * @param string The reference value.
   * @return string A URI.
   */
  private function _handleHref($href) {
    $baseLocObj = new Net_URL2($this->_tmHandler->getBaseLocator());
    return $baseLocObj->resolve($href)->getUrl();
  }
  
  /**
   * Handles a <mergeMap> event.
   * 
   * @param array The XML element attributes.
   * @return void
   */
  private function _handleMergeMap(array $attributes) {
    $this->_tmHandler->startMergeMap($attributes[self::ATTR_HREF], __CLASS__);
  }
}
?>