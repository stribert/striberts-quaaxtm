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
require_once('QTMIOXMLWriter.class.php');
require_once('Net/URL2.php');

/**
 * Writes CXTM according to {@link http://www.isotopicmaps.org/cxtm/cxtm.html}.
 * Works against PHPTMAPI (see {@link http://phptmapi.sourceforge.net}). 
 * 
 * @package out
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPICXTMWriter
{ 
  /**
   * The "players to associations" index.
   * 
   * @var array
   */
  private $_playersToAssocsIndex;
  
  /**
   * The "topics to numbers" index.
   * 
   * @var array
   */
  private $_topicsToNumbersIndex;
  
  /**
   * The topic representing the "type-instance" association type.
   * 
   * @var Topic
   */
  private $_typeInstance;
  
  /**
   * The topic representing the "type" role type in a type-instance association.
   * 
   * @var Topic
   */
  private $_type;
  
  /**
   * The topic representing the "instance" role type in a type-instance association.
   * 
   * @var Topic
   */
  private $_instance;
  
  /**
   * The type-instance associations.
   * 
   * @var array
   */
  private $_typeInstanceAssocs;
  
  /**
   * The topic map to be serialized to CXTM.
   * 
   * @var TopicMap
   */
  private $_topicMap;
  
  /**
   * The XML writer.
   * 
   * @var QTMIOXMLWriter
   */
  private $_writer;
  
  /**
   * The topic map's topic item identifier fragment pattern to be filtered.
   * The fragment is resolved against the topic map's base locator.
   * 
   * @var string
   */
  private $_filterTopicIidPattern;
          
  /**
   * The normalized locators of a Topic Maps construct property.
   * 
   * @var array
   * @static
   */
  private static $_normLocs = array();
  
  /**
   * The normalized topic map base locator.
   * 
   * @var string
   * @static
   */
  private static $_normBaseLoc = '';
  
  /**
   * The indicator if the source serialization format is XTM.
   * 
   * @var boolean
   * @static
   */
  private static $_srcXml = true;
  
  /**
   * The XSD "anyURI" datatype identifier.
   * 
   * @var string
   * @static
   */
  private static $_xsdUri = 'http://www.w3.org/2001/XMLSchema#anyURI';
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    $this->_playersToAssocsIndex = 
    $this->_typeInstanceAssocs = 
    $this->_topicsToNumbersIndex = array();
    $this->_typeInstance = 
    $this->_type = 
    $this->_instance = 
    $this->_topicMap = 
    $this->_writer = 
    $this->_filterTopicIidPattern = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_playersToAssocsIndex);
    unset($this->_typeInstanceAssocs);
    unset($this->_topicsToNumbersIndex);
    unset($this->_typeInstance);
    unset($this->_type);
    unset($this->_instance);
    unset($this->_topicMap);
    unset($this->_writer);
  }
  
  /**
   * Creates and returns the CXTM.
   * 
   * @param TopicMap The topic map to write.
   * @param string The base locator. Default <var>null</var>.
   * @param boolean XML source indication. Default <var>true</var>.
   * @param string Pattern which allows filtering of Topic Maps engine specific item 
   * 				identifiers attached to topics if union of subject identifiers and subject locators 
   * 				is > 0. E.g. QuaaxTM's item identifier pattern for topics is "TopicImpl-". 
   * @return string The CXTM.
   */
  public function write(
    TopicMap $topicMap, 
    $baseLocator=null, 
    $srcXml=true, 
    $filterTopicIidPattern=null
    )
  {
    $this->_topicMap = $topicMap;
    $baseLocator = !is_null($baseLocator) 
      ? $baseLocator 
      : $topicMap->getLocator();
    self::$_normBaseLoc = $this->_normalizeBaseLocator($baseLocator);
    self::$_srcXml = $srcXml;
    $this->_filterTopicIidPattern = $filterTopicIidPattern;
    
    $topics = $this->_prepareTopics();
    $assocs = $this->_prepareAssociations();
    
    $this->_writer = new QTMIOXMLWriter();
    $this->_writer->openMemory();
    
    $this->_writer->startElement('topicMap');
    $reifier = $topicMap->getReifier();
    if ($reifier instanceof Topic) {
      $this->_writer->writeAttribute(
      	'reifier', 
        $this->_topicsToNumbersIndex[$reifier->getId()]
      );
    }
    $this->_writer->writeNewLine();
    $this->_writeItemIdentifiers($topicMap);
    
    $number = 1;
    foreach ($topics as $topic) {
      $this->_writeTopic($topic, $number);
      $number++;
    }
    
    $number = 1;
    foreach ($assocs as $assoc) {
      $this->_writeAssociation($assoc, $number);
      $number++;
    }
    
    $this->_writer->endElement();
    $cxtm = $this->_writer->outputMemory();
    
    $this->_cleanUp();
    
    return trim($cxtm);
  }
  
  /**
   * Writes a <topic> element.
   * 
   * @param Topic The topic to write.
   * @param int The element number.
   * @return void
   */
  private function _writeTopic(Topic $topic, $number)
  {
    $this->_writer->startElement('topic');
    $this->_writer->writeAttribute('number', $number);
    $this->_writer->writeNewLine();
    
    $this->_writeLocators('subjectIdentifiers', $topic->getSubjectIdentifiers());
    $this->_writeLocators('subjectLocators', $topic->getSubjectLocators());
    $this->_writeItemIdentifiers($topic);
      
    $names = $topic->getNames();
    usort($names, array(__CLASS__, '_compareNamesIgnoreParent'));
    $number = 1;
    foreach ($names as $name) {
      $this->_writeName($name, $number);
      $number++;
    }
    
    $occs = $topic->getOccurrences();
    usort($occs, array(__CLASS__, '_compareDatatypeAwareIgnoreParent'));
    $number = 1;
    foreach ($occs as $occ) {
      $this->_writeOccurrence($occ, $number);
      $number++;
    }
    
    if (isset($this->_playersToAssocsIndex[$topic->getId()])) {
      $this->_writeRolesPlayed($this->_playersToAssocsIndex[$topic->getId()]);
    }
    
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes <rolePlayed> elements.
   * 
   * @param array The played roles.
   * @return void
   */
  private function _writeRolesPlayed(array $rolesPlayed)
  {
    foreach ($rolesPlayed as $rolePlayed) {
      $constituents = explode('-', $rolePlayed);
      $this->_writer->startElement('rolePlayed');
      $this->_writer->writeAttribute(
      	'ref', 
      	'association' . '.' . $constituents[0] . '.' . 'role' . '.' . $constituents[1]
      );
      $this->_writer->text(null);// force </rolePlayed>
      $this->_writer->endElement();
      $this->_writer->writeNewLine();
    }
  }
  
  /**
   * Writes a <name> element.
   * 
   * @param Name The name to write.
   * @param int The element number.
   * @return void
   */
  private function _writeName(Name $name, $number)
  {
    $this->_writer->startElement('name');
    $this->_writeNumberReifierAttributes($number, $name);
    $this->_writer->writeNewLine();
    
    $this->_writer->writeElement('value', $this->_escapeString($name->getValue()));
    $this->_writer->writeNewLine();
    
    $this->_writeType($name);
    $this->_writeScope($name);
    
    $variants = $name->getVariants();
    usort($variants, array(__CLASS__, '_compareDatatypeAwareIgnoreParent'));
    $number = 1;
    foreach ($variants as $variant) {
      $this->_writer->startElement('variant');
      $this->_writeNumberReifierAttributes($number, $variant);
      $this->_writer->writeNewLine();
      $this->_writeDatatyped($variant);
      $this->_writeScope($variant);
      $this->_writeItemIdentifiers($variant);
      $this->_writer->endElement();
      $this->_writer->writeNewLine();
      $number++;
    }
    
    $this->_writeItemIdentifiers($name);
    
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes an <occurrence> element.
   * 
   * @param Occurrence The occurrence to write.
   * @param int The element number.
   * @return void
   */
  private function _writeOccurrence(Occurrence $occ, $number)
  {
    $this->_writer->startElement('occurrence');
    $this->_writeNumberReifierAttributes($number, $occ);
    $this->_writer->writeNewLine();
    
    $this->_writeDatatyped($occ);
    $this->_writeType($occ);
    $this->_writeScope($occ);
    $this->_writeItemIdentifiers($occ);
    
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes an association. 
   * (Includes <association>, <type>, <role>, and <player> elements.)
   * 
   * @param Association The association to write.
   * @param int The element number.
   * @return void
   */
  private function _writeAssociation(Association $assoc, $number)
  {
    $this->_writer->startElement('association');
    $this->_writeNumberReifierAttributes($number, $assoc);
    $this->_writer->writeNewLine();
    $this->_writeType($assoc);
    
    $roles = $assoc->getRoles();
    usort($roles, array(__CLASS__, '_compareRolesIgnoreParent'));
    $number = 1;
    foreach ($roles as $role) {
      $this->_writer->startElement('role');
      $this->_writeNumberReifierAttributes($number, $role);
      $this->_writer->writeNewLine();
      
      $this->_writer->startElement('player');
      $this->_writer->writeAttribute(
      	'topicref', 
        $this->_topicsToNumbersIndex[$role->getPlayer()->getId()]
      );
      $this->_writer->text(null);// force </player>
      $this->_writer->endElement();
      $this->_writer->writeNewLine();
      
      $this->_writeType($role);
      $this->_writeItemIdentifiers($role);
      
      $this->_writer->endElement();
      $this->_writer->writeNewLine();
      $number++;
    }
    
    $this->_writeScope($assoc);
    
    $this->_writeItemIdentifiers($assoc);
    
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes a <value> and a <datatype> element.
   * 
   * @param DatatypeAware The datatype aware Topic Maps construct having 
   * 				the value and the datatype to write.
   * @return void
   */
  private function _writeDatatyped(DatatypeAware $da)
  {
    $loc = self::_normalizeLocator($da->getDatatype());
    $value = $da->getValue();
    if ($loc == self::$_xsdUri) {
      $this->_writer->writeElement('value', self::_normalizeLocator($value));
    } else {
      $this->_writer->writeElement('value', $this->_escapeString($value));
    }
    $this->_writer->writeNewLine();
    $this->_writer->writeElement('datatype', $loc);
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes a <scope> element including <scopingTopic> elements.
   * 
   * @param Scoped The scoped Topic Maps construct having the scope to write.
   * @return void
   */
  private function _writeScope(Scoped $scoped)
  {
    $scope = $scoped->getScope();
    if (empty($scope)) {
      return;
    }
    
    usort($scope, array(__CLASS__, '_compareTopics'));
    
    $this->_writer->startElement('scope');
    $this->_writer->writeNewLine();
    
    foreach ($scope as $theme) {
      $this->_writer->startElement('scopingTopic');
      $this->_writer->writeAttribute(
      	'topicref', 
        $this->_topicsToNumbersIndex[$theme->getId()]
      );
      $this->_writer->text(null);// force </scopingTopic>
      $this->_writer->endElement();
      $this->_writer->writeNewLine();
    }
    
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes a <type> element.
   * 
   * @param Typed The typed Topic Maps construct having the type to write.
   * @return void
   */
  private function _writeType(Typed $typed)
  {
    $this->_writer->startElement('type');
    $this->_writer->writeAttribute(
    	'topicref', 
      $this->_topicsToNumbersIndex[$typed->getType()->getId()]
    );
    $this->_writer->text(null);// force </type>
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes a number and a reifier attribute.
   * 
   * @param int The number.
   * @param Reifiable The reifiable.
   * @return void
   */
  private function _writeNumberReifierAttributes($number, Reifiable $reifiable)
  {
    $this->_writer->writeAttribute('number', $number);
    $reifier = $reifiable->getReifier();
    if (!$reifier instanceof Topic) {
      return;
    }
    $this->_writer->writeAttribute(
    	'reifier', 
      $this->_topicsToNumbersIndex[$reifier->getId()]
    );
  }
  
  /**
   * Writes locators.
   * 
   * @param string The locator element name (e.g. "subjectIdentifiers").
   * @param array The locators to write.
   * @return void
   */
  private function _writeLocators($elementName, array $locs)
  {
    if (empty($locs)) {
      return;
    }
    usort($locs, array(__CLASS__, '_compareLocators'));
    $this->_writer->startElement($elementName);
    $this->_writer->writeNewLine();
    foreach ($locs as $loc) {
      $this->_writeLocator($loc);
    }
    $this->_writer->endElement();
    $this->_writer->writeNewLine();
  }
  
  /**
   * Writes an <itemIdentifiers> element.
   * 
   * @param Construct The construct having item identifiers.
   * @return void
   */
  private function _writeItemIdentifiers(Construct $construct)
  {
    if (!is_null($this->_filterTopicIidPattern) && $construct instanceof Topic) {
      $iids = $this->_getFilteredTopicIids($construct);
    } else {
      $iids = $construct->getItemIdentifiers();
    }
    $this->_writeLocators('itemIdentifiers', $iids);
  }
  
  /**
   * Writes a <locator> element.
   * 
   * @param string The locator.
   * @return void
   */
  private function _writeLocator($loc)
  {
    $this->_writer->writeElement(
    	'locator', 
      rawurldecode(self::_normalizeLocator($loc))
    );
    $this->_writer->writeNewLine();
  }
  
  /**
   * Prepares the topics: Sorts the topics in canonical sort order and builds 
   * the topics to numbers index.
   * 
   * @return void
   * @throws MIOException If PHPTMAPI <var>TypeInstanceIndex</var> is not available.
   */
  private function _prepareTopics()
  {
    try {
      $index = $this->_topicMap->getIndex('TypeInstanceIndexImpl');
    } catch (FeatureNotSupportedException $e) {
      throw new MIOException('Error in ' . __METHOD__ . ': TypeInstanceIndex is not available!');
    }
    // get all topics which have a type
    $instances = $index->getTopics(array(), true);
    // as type-instance relationships are modelled as properties 
    // associations have to be created which will be removed afterwards
    foreach ($instances as $instance) {
      $this->_createTypeInstanceAssociation($instance);
    }
    unset($instances);
    $topics = $this->_topicMap->getTopics();
    usort($topics, array(__CLASS__, '_compareTopics'));
    $i=1;
    foreach ($topics as $topic) {
      $this->_topicsToNumbersIndex[$topic->getId()] = $i;
      $i++;
    }
    return $topics;
  }
  
  /**
   * Prepares the associations: Sorts the associations in canonical sort order and builds 
   * the players to associations index.
   * 
   * @return void
   */
  private function _prepareAssociations()
  {
    $assocs = $this->_topicMap->getAssociations();
    usort($assocs, array(__CLASS__, '_compareAssociations'));
    // build $playersToAssocsIndex (example in JSON):
    // {
    //   "TopicImpl-1": [
    //      "1-2",
    //      "2-1"
    //   ]
    // }
    $i=1;
    foreach ($assocs as $assoc) {
      $roles = $assoc->getRoles();
      usort($roles, array(__CLASS__, '_compareRolesIgnoreParent'));
      $z=1;
      foreach ($roles as $role) {
        $playerId = $role->getPlayer()->getId();
        if (!isset($this->_playersToAssocsIndex[$playerId])) {
          $this->_playersToAssocsIndex[$playerId] = array($i . '-' . $z);
        } else {
          $val = $this->_playersToAssocsIndex[$playerId];
          $val[] = $i . '-' . $z;
          $this->_playersToAssocsIndex[$playerId] = $val;
        }
        $z++;
      }
      $i++;
    }
    return $assocs;
  }
  
  /**
   * Compares two topics.
   * 
   * @param Topic The first topic.
   * @param Topic The second topic.
   * @return int The comparison result.
   * @static
   */
  private static function _compareTopics(Topic $topic1, Topic $topic2)
  {
    if ($topic1->equals($topic2)) {
      return 0;
    }
    $res = 0;
    $res = self::_compareArrays(
      $topic1->getSubjectIdentifiers(),
      $topic2->getSubjectIdentifiers(),
      '_compareLocatorContent',
      true
    );
    if ($res == 0) {
      $res = self::_compareArrays(
        $topic1->getSubjectLocators(),
        $topic2->getSubjectLocators(), 
        '_compareLocatorContent',
        true
      );
      if ($res == 0) {
        $res = self::_compareArrays(
          $topic1->getItemIdentifiers(),
          $topic2->getItemIdentifiers(), 
          '_compareLocatorContent',
          true
        );
      }
    }
    return $res;
  }
  
  /**
   * Compares two associations.
   * 
   * @param Association The first association.
   * @param Association The second association.
   * @return int The comparison result.
   * @static
   */
  private static function _compareAssociations(Association $assoc1, Association $assoc2)
  {
    if ($assoc1->equals($assoc2)) {
      return 0;
    }
    $res = 0;
    $res = self::_compareTopics($assoc1->getType(), $assoc2->getType());
    if ($res == 0) {
      $res = self::_compareArrays(
        $assoc1->getRoles(), 
        $assoc2->getRoles(), 
        '_compareRolesContentIgnoreParent'
      );
      if ($res == 0) {
        $res = self::_compareArrays(
          $assoc1->getScope(), 
          $assoc2->getScope(), 
          '_compareScopeContent'
        );
      }
    }
    return $res;
  }
  
  /**
   * Compares two arrays.
   * 
   * @param array The first array.
   * @param array The second array.
   * @param string The name of the compare function to call if the two arrays have an 
   * 				equal number of items.
   * @param boolean Indicator for making the arrays unique or not.
   * @return int The comparison result.
   * @static
   */
  private static function _compareArrays(
    array $arr1, 
    array $arr2, 
    $functionName, 
    $makeUnique=false
    )
  {
    if ((boolean)$makeUnique) {
      $arr1 = array_unique($arr1);
      $arr2 = array_unique($arr2);
    }
    $count1 = count($arr1);
    $count2 = count($arr2);
    if ($count1 == $count2) {
      return call_user_func(array(__CLASS__, $functionName), $arr1, $arr2, $count1);
    } else {
      return $count1 < $count2 ? -1 : 1;
    }
  }
  
  /**
   * Compares two arrays equal in size containing locators.
   * 
   * @param array The first array.
   * @param array The second array.
   * @param int The array's items count.
   * @return int The comparison result.
   * @static
   */
  private static function _compareLocatorContent(array $locs1, array $locs2, $count)
  {
    $res = 0;
    usort($locs1, array(__CLASS__, '_compareLocators'));
    usort($locs2, array(__CLASS__, '_compareLocators'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::_compareLocators($locs1[$i], $locs2[$i]);
    }
    return $res;
  }
  
  /**
   * Compares two arrays equal in size containing themes ({@link TopicImpl}s).
   * 
   * @param array The first array.
   * @param array The second array.
   * @param int The array's items count.
   * @return int The comparison result.
   * @static
   */
  private static function _compareScopeContent(array $themes1, array $themes2, $count)
  {
    $res = 0;
    usort($themes1, array(__CLASS__, '_compareTopics'));
    usort($themes2, array(__CLASS__, '_compareTopics'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::_compareTopics($themes1[$i], $themes2[$i]);
    }
    return $res;
  }
  
  /**
   * Compares two arrays equal in size containing {@link RoleImpl}s.
   * 
   * @param array The first array.
   * @param array The second array.
   * @param int The array's items count.
   * @return int The comparison result.
   * @static
   */
  private static function _compareRolesContentIgnoreParent(
    array $roles1, 
    array $roles2, 
    $count
    ) 
  {
    $res = 0;
    usort($roles1, array(__CLASS__, '_compareRolesIgnoreParent'));
    usort($roles2, array(__CLASS__, '_compareRolesIgnoreParent'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::_compareRolesIgnoreParent($roles1[$i], $roles2[$i]);
    }
    return $res;
  }
  
  /**
   * Compares two {@link RoleImpl}s ignoring the parent.
   * 
   * @param Role The first role.
   * @param Role The second role.
   * @return int The comparison result.
   * @static
   */
  private static function _compareRolesIgnoreParent(Role $role1, Role $role2)
  {
    if ($role1->equals($role2)) {
      return 0;
    }
    $res = 0;
    $res = self::_compareTopics($role1->getPlayer(), $role2->getPlayer());
    if ($res == 0) {
      $res = self::_compareTopics($role1->getType(), $role2->getType());
    }
    return $res;
  }
  
  /**
   * Compares two {@link NameImpl}s ignoring the parent.
   * 
   * @param Name The first name.
   * @param Name The second name.
   * @return int The comparison result.
   */
  private function _compareNamesIgnoreParent(Name $name1, Name $name2)
  {
    if ($name1->equals($name2)) {
      return 0;
    }
    $res = 0;
    $res = self::_compareStrings($name1->getValue(), $name2->getValue());
    if ($res == 0) {
      $res = self::_compareTopics($name1->getType(), $name2->getType());
      if ($res == 0) {
        $res = self::_compareArrays(
          $name1->getScope(), 
          $name2->getScope(), 
          '_compareScopeContent'
        );
      }
    }
    return $res;
  }
  
  /**
   * Compares two {@link DatatypeAware}s ignoring the parent.
   * 
   * @param DatatypeAware The first datatype aware Topic Maps construct.
   * @param DatatypeAware The second datatype aware Topic Maps construct.
   * @return int The comparison result.
   */
  private function _compareDatatypeAwareIgnoreParent(DatatypeAware $da1, DatatypeAware $da2)
  {
    if ($da1->equals($da2)) {
      return 0;
    }
    $res = 0;
    $res = self::_compareStrings($da1->getValue(), $da2->getValue());
    if ($res == 0) {
      $res = self::_compareLocators($da1->getDatatype(), $da2->getDatatype());
      if ($res == 0) {
        if ($da1 instanceof Occurrence && $da2 instanceof Occurrence) {
          $res = self::_compareTopics($da1->getType(), $da2->getType());
          if ($res == 0) {
            $res = self::_compareArrays(
              $da1->getScope(), 
              $da2->getScope(), 
              '_compareScopeContent'
            );
          }
        } else {// Variant
          $res = self::_compareArrays(
            $da1->getScope(), 
            $da2->getScope(), 
            '_compareScopeContent'
          );
        }
      }
    }
    return $res;
  }
  
  /**
   * Compares two locators.
   * 
   * @param string The first locator.
   * @param string The second locator.
   * @return int The comparison result.
   * @static
   */
  private static function _compareLocators($loc1, $loc2)
  {
    return self::_compareStrings(
      self::_normalizeLocator($loc1), 
      self::_normalizeLocator($loc2)
    );
  }
  
  /**
   * Compares two strings.
   * 
   * @param string The first string.
   * @param string The second string.
   * @return int The comparison result.
   * @static
   */
  private static function _compareStrings($str1, $str2)
  {
    return strcmp(self::_normalizeString($str1), self::_normalizeString($str2));
  }
  
  /**
   * Normalizes a string according to Unicode Normalization Form C.
   * 
   * @param string The string to normalize.
   * @return string The normalized string.
   * @static
   */
  private static function _normalizeString($str)
  {
    return Normalizer::normalize($str, Normalizer::FORM_C);
  }
  
  /**
   * Normalizes a locator according to CXTM 
   * (see {@link http://www.isotopicmaps.org/cxtm/cxtm.html#d0e1334}).
   * 
   * @param string The locator to normalize.
   * @return string The normalized locator.
   * @static
   */
  private static function _normalizeLocator($loc)
  {
    $locObj = new Net_URL2($loc);
    $loc = $locObj->getUrl();
    if (isset(self::$_normLocs[$loc])) {
      return self::$_normLocs[$loc];
    }
    $normLoc = '';
    $baseLoc = self::$_normBaseLoc;
    if (strpos($loc, $baseLoc) !== false && strlen($loc) > strlen($baseLoc)) {
      $normLoc = self::_removeLeadingSlash(
        substr($loc, strlen($baseLoc), strlen($loc))
      );
    } else {
      $baseLocObj = new Net_URL2($baseLoc);
      $path = $baseLocObj->getPath();
      if (strlen($path) > 0 && $path != '/') {
        $path = self::_removeTrailingSlash($path);
        $slashCount = substr_count($path, '/');
        for ($i=0; $i < $slashCount; $i++) {
          // cut the last path and compare to locator
          $path = substr($path, 0, strrpos($path, '/'));
          $baseLoc = self::_createLocator(
            $baseLocObj->getScheme(), $baseLocObj->getHost(), $path
          );
          if (strpos($loc, $baseLoc) !== false && strlen($loc) > strlen($baseLoc)) {
            $normLoc = self::_removeLeadingSlash(
              substr($loc, strlen($baseLoc), strlen($loc))
            );
            break;
          }
        }
      }
    }
    if (strlen($normLoc) == 0) {
      $normLoc = $loc;
    }
    $normLoc = self::_removeTrailingSlash($normLoc);
    self::$_normLocs[$loc] = $normLoc;
    unset($locObj);
    unset($baseLocObj);
    return (string) $normLoc;
  }
  
  /**
   * Creates a locator from URI constituents.
   * 
   * @param string The scheme part.
   * @param string The host part.
   * @param string The path part.
   * @return string The created locator (a valid URI).
   * @static
   */
  private static function _createLocator($scheme, $host, $path)
  {
    switch ($scheme) {
      case 'file':
        return $scheme . ':' . $path;
        break;
      default:
        return $scheme . '://' . $host . $path;
        break;
    }
  }
  
  /**
   * Removes a trailing "/" from a string. 
   * Needed for locator normalization.
   * 
   * @param string The string to manipulate.
   * @return string A string without trailing "/".
   * @static
   */
  private static function _removeTrailingSlash($str)
  {
    $lastChar = substr($str, -1);
    if ($lastChar == '/') {
      $str = substr($str, 0, strlen($str)-1);
    }
    return $str;
  }
  
  /**
   * Removes a leading "/" from a string. 
   * Needed for locator normalization.
   * 
   * @param string The string to manipulate.
   * @return string A string without leading "/".
   * @static
   */
  private static function _removeLeadingSlash($str)
  {
    $firstChar = $str[0];
    if ($firstChar == '/') {
      $str = substr($str, 1, strlen($str));
    }
    return $str;
  }
  
  /**
   * Converts '&', '"', ''', '<', and '>' to the resp. HTML/XML entities.
   * 
   * @param string The string to escape.
   * @return string An escaped string.
   * @static
   */
  private static function _escapeString($str)
  {
    return self::$_srcXml ? $str : htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }
  
  /**
   * Normalizes the base locator.
   * 
   * @param string The base locator to normalize.
   * @return string A normalized base locator.
   */
  private function _normalizeBaseLocator($loc)
  {
    $loc = new Net_URL2($loc);
    switch ($loc->getScheme()) {
      case 'file':
        return self::_removeTrailingSlash(
          $loc->getScheme() . ':' . $loc->getHost() . $loc->getPath()
        );
        break;
      default:
        return self::_removeTrailingSlash(
          $loc->getScheme() . '://' . $loc->getHost() . $loc->getPath()
        );
        break;
    }
  }
  
  /**
   * Creates a type instance association for a typed {@link TopicImpl} (the instance).
   * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-types}.
   * 
   * @param Topic The instance.
   * @return void
   */
  private function _createTypeInstanceAssociation(Topic $instance)
  {
    $types = $instance->getTypes();
    foreach ($types as $type) {
      if (is_null($this->_typeInstance)) {
        $this->_typeInstance = $this->_topicMap->getTopicBySubjectIdentifier(
          MIOUtil::PSI_TYPE_INSTANCE
        );
        if (is_null($this->_typeInstance)) {
          $this->_typeInstance = $this->_topicMap->createTopicBySubjectIdentifier(
            MIOUtil::PSI_TYPE_INSTANCE
          );
        }
      }
      if (is_null($this->_type)) {
        $this->_type = $this->_topicMap->getTopicBySubjectIdentifier(
          MIOUtil::PSI_TYPE
        );
        if (is_null($this->_type)) {
          $this->_type = $this->_topicMap->createTopicBySubjectIdentifier(
            MIOUtil::PSI_TYPE
          );
        }
      }
      if (is_null($this->_instance)) {
        $this->_instance = $this->_topicMap->getTopicBySubjectIdentifier(
          MIOUtil::PSI_INSTANCE
        );
        if (is_null($this->_instance)) {
          $this->_instance = $this->_topicMap->createTopicBySubjectIdentifier(
            MIOUtil::PSI_INSTANCE
          );
        }
      }
      $assoc = $this->_topicMap->createAssociation($this->_typeInstance);
      $assoc->createRole($this->_type, $type);
      $assoc->createRole($this->_instance, $instance);
      $this->_typeInstanceAssocs[] = $assoc;
    }
  }
  
  /**
   * Cleans up: Removes all associations created in 
   * {@link _createTypeInstanceAssociation()}.
   * 
   * @return void
   */
  private function _cleanUp()
  {
    foreach ($this->_typeInstanceAssocs as $assoc) {
      $assoc->remove();
    }
  }
  
  /**
   * Returns the topic's item identifiers filtered of the Topic Maps engine specific 
   * item identifiers if pattern is defined. See also {@link write()}.
   * This is needed to pass the CXTM-tests suite in an XTM import-export-import procedure.
   * 
   * @param Topic The topic to be filtered.
   * @return array An array containing the topic's item identifiers. The array may also
   * 				be empty.
   */
  private function _getFilteredTopicIids(Topic $topic)
  {
    $iids = $topic->getItemIdentifiers();
    $filteredIids = array();
    foreach ($iids as $iid) {
      if (strpos($iid, $this->_filterTopicIidPattern) === false) {
        $filteredIids[] = $iid;
      }
    }
    if (count($filteredIids > 0)) {
      $iids = $filteredIids;
    } else {// $filteredIids is an empty array
      $sids = $topic->getSubjectIdentifiers();
      $slos = $topic->getSubjectLocators();
      if (count(array_merge($sids, $slos)) > 0) {
        $iids = array();
      }
    }
    return $iids;
  }
}
?>