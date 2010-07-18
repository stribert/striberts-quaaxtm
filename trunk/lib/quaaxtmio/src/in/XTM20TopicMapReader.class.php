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
 * Parses XTM 2.0 and passes the results to a topic map handler.
 * 
 * @package in
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class XTM20TopicMapReader {
  
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
        STATE_VARIANT = 10;
        
  private $sax,
          $tmHandler,
          $state,
          $nextState,
          $data,
          $datatype;

  /**
   * Constructor.
   * 
   * @param PHPTMAPITopicMapHandlerInterface The topic map handler.
   * @param string The encoding. Default <code>UTF-8</code>.
   * @return void
   */
  public function __construct(PHPTMAPITopicMapHandlerInterface $tmHandler, $encoding='UTF-8') {
    $this->tmHandler = $tmHandler;
    $this->sax = xml_parser_create($encoding);
    xml_set_object($this->sax, $this);
    xml_parser_set_option($this->sax, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($this->sax, 'open', 'close');
    xml_set_character_data_handler($this->sax, 'data');
    $this->state = self::STATE_INITIAL;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    xml_parser_free($this->sax);
    $this->tmHandler = null;
  }

  /**
   * Reads and parses given XTM file.
   * 
   * @param string The XTM file.
   * @return void
   * @throws MIOException If parse error occurs.
   */
  public function readXtmFile($file) {
    $xtm = MIOUtil::readFile($file);
    if (!xml_parse($this->sax, $xtm)) { 
      $error = xml_error_string (xml_get_error_code($this->sax));
      $line = xml_get_current_line_number($this->sax);
      throw new MIOException('Error in ' . __METHOD__ . 
      	': Parse error "' . $error . '" on line ' . $line . '.');
    }
  }

  /**
   * Processes XML opening tags.
   * 
   * @param resource The PHP XML parser.
   * @param string The XML element name.
   * @param array XML element attributes.
   * @return void
   * @throws MIOException If XTM version is not 2.0.
   */
  private function open($sax, $element, array $attributes) {
    switch ($element) {
      case self::TAG_TOPIC:
        $this->state = self::STATE_TOPIC;
        $this->tmHandler->startTopic(
          new Reference('#' . $attributes[self::ATTR_ID], Reference::ITEM_IDENTIFIER)
        );
        break;
      case self::TAG_SUBJECT_IDENTIFIER:
        $this->tmHandler->subjectIdentifier($this->handleHref($attributes[self::ATTR_HREF]));
        break;
      case self::TAG_SUBJECT_LOCATOR:
        $this->tmHandler->subjectLocator($this->handleHref($attributes[self::ATTR_HREF]));
        break;
      case self::TAG_ITEM_IDENTITY:
        $this->tmHandler->itemIdentifier($this->handleHref($attributes[self::ATTR_HREF]));
        break;
      case self::TAG_INSTANCE_OF:
        $this->state = self::STATE_INSTANCE_OF;
        $this->tmHandler->startIsa();
        break;
      case self::TAG_TOPIC_REF:
        $this->handleTopicReference(new Reference($attributes[self::ATTR_HREF], Reference::ITEM_IDENTIFIER));
        break;
      case self::TAG_NAME:
        $this->state = self::STATE_NAME;
        $this->tmHandler->startName();
        $this->handleReifier($attributes);
        break;
      case self::TAG_VALUE:
        //no op.
        break;
      case self::TAG_TYPE:
        $this->nextState = $this->state;
        $this->state = self::STATE_TYPE;
        $this->tmHandler->startType();
        break;
      case self::TAG_SCOPE:
        $this->nextState = $this->state;
        $this->state = self::STATE_SCOPE;
        $this->tmHandler->startScope();
        break;
      case self::TAG_VARIANT:
        $this->state = self::STATE_VARIANT;
        $this->tmHandler->startVariant();
        $this->handleReifier($attributes);
        break;
      case self::TAG_RESOURCE_DATA:
        $datatype = $this->getAttributeValue($attributes, self::ATTR_DATATYPE);
        $this->datatype = $datatype == null ? MIOUtil::XSD_STRING : $datatype;
        break;
      case self::TAG_RESOURCE_REF:
        $data = $this->handleHref($attributes[self::ATTR_HREF]);
        $this->tmHandler->value($data, MIOUtil::XSD_ANYURI);
        break;
      case self::TAG_OCCURRENCE:
        $this->state = self::STATE_OCCURRENCE;
        $this->tmHandler->startOccurrence();
        $this->handleReifier($attributes);
        break;
      case self::TAG_ASSOCIATION:
        $this->state = self::STATE_ASSOCIATION;
        $this->tmHandler->startAssociation();
        $this->handleReifier($attributes);
        break;
      case self::TAG_ROLE:
        $this->state = self::STATE_ROLE;
        $this->tmHandler->startRole();
        $this->handleReifier($attributes);
        break;
      case self::TAG_MERGEMAP:
        $this->handleMergeMap($attributes);
        break;
      case self::TAG_TOPIC_MAP:
        if ($this->isXtm2($attributes[self::ATTR_VERSION])) {
          $this->state = self::STATE_TOPIC_MAP;
          $this->tmHandler->startTopicMap();
          $this->handleReifier($attributes);
        } else {
          throw new MIOException('Error in ' . __METHOD__ . 
          	': Expect version 2.0! Received version ' . $attributes[self::ATTR_VERSION] . '.');
        }
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
  private function close($sax, $element) {
    switch ($element) {
      case self::TAG_TOPIC:
        $this->state = self::STATE_TOPIC_MAP;
        $this->tmHandler->endTopic();
        break;
      case self::TAG_INSTANCE_OF:
        $this->state = self::STATE_TOPIC;
        $this->tmHandler->endIsa();
        break;
      case self::TAG_NAME:
        $this->state = self::STATE_TOPIC;
        $this->tmHandler->endName();
        break;
      case self::TAG_VALUE:
        $this->tmHandler->nameValue($this->data);
        $this->data = null;
        break;
      case self::TAG_TYPE:
        $this->state = $this->nextState;
        $this->tmHandler->endType();
        break;
      case self::TAG_SCOPE:
        $this->state = $this->nextState;
        $this->tmHandler->endScope();
        break;
      case self::TAG_VARIANT:
        $this->state = self::STATE_NAME;
        $this->tmHandler->endVariant();
        break;
      case self::TAG_RESOURCE_DATA:
        $this->tmHandler->value($this->data, $this->datatype);
        $this->data = null;
        break;
      case self::TAG_OCCURRENCE:
        $this->state = self::STATE_TOPIC;
        $this->tmHandler->endOccurrence();
        break;
      case self::TAG_ASSOCIATION:
        $this->state = self::STATE_TOPIC_MAP;
        $this->tmHandler->endAssociation();
        break;
      case self::TAG_ROLE:
        $this->state = self::STATE_ASSOCIATION;
        $this->tmHandler->endRole();
        break;
      case self::TAG_TOPIC_MAP:
        $this->state = self::STATE_INITIAL;
        $this->tmHandler->endTopicMap();
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
  private function data($sax, $data) { 
    $this->data .= $data;
  }
  
  /**
   * Checks XTM version.
   * 
   * @param string $version
   * @return boolean
   */
  private function isXtm2($version) {
    return !empty($version) && $version === '2.0';
  }
  
  /**
   * Processes a reifier
   * 
   * @param array The XML element attributes.
   * @return void
   */
  private function handleReifier(array $attributes) {
    if (isset($attributes[self::ATTR_REIFIER])) {
      $this->tmHandler->startReifier();
      $this->tmHandler->topicRef(new Reference($attributes[self::ATTR_REIFIER], Reference::ITEM_IDENTIFIER));
      $this->tmHandler->endReifier();
    }
  }
   
  /**
   * Processes a topic reference.
   * 
   * @param ReferenceInterface The topic reference.
   * @return void
   * @throws MIOException If <topicRef> occurs in unexpected state. 
   */
  private function handleTopicReference(ReferenceInterface $topicRef) {
    if (
        $this->state === self::STATE_INSTANCE_OF ||
        $this->state === self::STATE_TYPE ||
        $this->state === self::STATE_SCOPE ||
        $this->state === self::STATE_ROLE
      ) 
    {
      $this->tmHandler->topicRef($topicRef);  
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
  private function getAttributeValue(array $attributes, $value) {
    return isset($attributes[$value]) ? $attributes[$value] : null;
  }
  
  /**
   * Handles an href value.
   * 
   * @param string The reference value.
   * @return string A URI.
   */
  private function handleHref($href) {
    $locator = new Net_URL2($href);
    return $locator->getUrl();
  }
  
  /**
   * Handles a <mergeMap> event.
   * 
   * @param array The XML element attributes.
   * @return void
   */
  private function handleMergeMap(array $attributes) {
    $this->tmHandler->startMergeMap($attributes[self::ATTR_HREF]);
  }
}
?>