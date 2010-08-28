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
class PHPTMAPICXTMWriter {
  
  const PSI_TYPE_INSTANCE = 'http://psi.topicmaps.org/iso13250/model/type-instance',
        PSI_TYPE = 'http://psi.topicmaps.org/iso13250/model/type',
        PSI_INSTANCE = 'http://psi.topicmaps.org/iso13250/model/instance';
  
  private static  $normLocs = array(),
                  $normBaseLoc = '',
                  $srcXml = true;
  
  private $playersToAssocsIndex,
          $topicsToNumbersIndex,
          $typeInstance,
          $type,
          $instance,
          $typeInstanceAssocs,
          $topicMap,
          $writer,
          $filterTopicIidPattern;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    $this->playersToAssocsIndex = 
    $this->typeInstanceAssocs = 
    $this->topicsToNumbersIndex = array();
    $this->typeInstance = 
    $this->type = 
    $this->instance = 
    $this->topicMap = 
    $this->writer = 
    $this->filterTopicIidPattern = null;
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    unset($this->playersToAssocsIndex);
    unset($this->typeInstanceAssocs);
    unset($this->topicsToNumbersIndex);
    unset($this->typeInstance);
    unset($this->type);
    unset($this->instance);
    unset($this->topicMap);
    unset($this->writer);
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
  ) {
    $this->topicMap = $topicMap;
    $baseLocator = !is_null($baseLocator) 
      ? $baseLocator 
      : $topicMap->getLocator();
    self::$normBaseLoc = $this->normalizeBaseLocator($baseLocator);
    self::$srcXml = $srcXml;
    $this->filterTopicIidPattern = $filterTopicIidPattern;
    
    $topics = $this->prepareTopics();
    $assocs = $this->prepareAssociations();
    
    $this->writer = new QTMIOXMLWriter();
    $this->writer->openMemory();
    
    $this->writer->startElement('topicMap');
    $reifier = $topicMap->getReifier();
    if ($reifier instanceof Topic) {
      $this->writer->writeAttribute(
      	'reifier', 
        $this->topicsToNumbersIndex[$reifier->getId()]
      );
    }
    $this->writer->writeNewLine();
    $this->writeItemIdentifiers($topicMap);
    
    $number = 1;
    foreach ($topics as $topic) {
      $this->writeTopic($topic, $number);
      $number++;
    }
    
    $number = 1;
    foreach ($assocs as $assoc) {
      $this->writeAssociation($assoc, $number);
      $number++;
    }
    
    $this->writer->endElement();
    $cxtm = $this->writer->outputMemory();
    
    $this->cleanUp();
    
    return trim($cxtm);
  }
  
  /**
   * Writes a <topic> element.
   * 
   * @param Topic The topic to write.
   * @param int The element number.
   * @return void
   */
  private function writeTopic(Topic $topic, $number) {
    $this->writer->startElement('topic');
    $this->writer->writeAttribute('number', $number);
    $this->writer->writeNewLine();
    
    $this->writeLocators('subjectIdentifiers', $topic->getSubjectIdentifiers());
    $this->writeLocators('subjectLocators', $topic->getSubjectLocators());
    $this->writeItemIdentifiers($topic);
      
    $names = $topic->getNames();
    usort($names, array(__CLASS__, 'compareNamesIgnoreParent'));
    $number = 1;
    foreach ($names as $name) {
      $this->writeName($name, $number);
      $number++;
    }
    
    $occs = $topic->getOccurrences();
    usort($occs, array(__CLASS__, 'compareDatatypeAwareIgnoreParent'));
    $number = 1;
    foreach ($occs as $occ) {
      $this->writeOccurrence($occ, $number);
      $number++;
    }
    
    if (isset($this->playersToAssocsIndex[$topic->getId()])) {
      $this->writeRolesPlayed($this->playersToAssocsIndex[$topic->getId()]);
    }
    
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes <rolePlayed> elements.
   * 
   * @param array The played roles.
   * @return void
   */
  private function writeRolesPlayed(array $rolesPlayed) {
    foreach ($rolesPlayed as $rolePlayed) {
      $constituents = explode('-', $rolePlayed);
      $this->writer->startElement('rolePlayed');
      $this->writer->writeAttribute(
      	'ref', 
      	'association' . '.' . $constituents[0] . '.' . 'role' . '.' . $constituents[1]
      );
      $this->writer->text(null);// force </rolePlayed>
      $this->writer->endElement();
      $this->writer->writeNewLine();
    }
  }
  
  /**
   * Writes a <name> element.
   * 
   * @param Name The name to write.
   * @param int The element number.
   * @return void
   */
  private function writeName(Name $name, $number) {
    $this->writer->startElement('name');
    $this->writeNumberReifierAttributes($number, $name);
    $this->writer->writeNewLine();
    
    $this->writer->writeElement('value', $this->escapeString($name->getValue()));
    $this->writer->writeNewLine();
    
    $this->writeType($name);
    $this->writeScope($name);
    
    $variants = $name->getVariants();
    usort($variants, array(__CLASS__, 'compareDatatypeAwareIgnoreParent'));
    $number = 1;
    foreach ($variants as $variant) {
      $this->writer->startElement('variant');
      $this->writeNumberReifierAttributes($number, $variant);
      $this->writer->writeNewLine();
      $this->writeDatatyped($variant);
      $this->writeScope($variant);
      $this->writeItemIdentifiers($variant);
      $this->writer->endElement();
      $this->writer->writeNewLine();
      $number++;
    }
    
    $this->writeItemIdentifiers($name);
    
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes an <occurrence> element.
   * 
   * @param Occurrence The occurrence to write.
   * @param int The element number.
   * @return void
   */
  private function writeOccurrence(Occurrence $occ, $number) {
    $this->writer->startElement('occurrence');
    $this->writeNumberReifierAttributes($number, $occ);
    $this->writer->writeNewLine();
    
    $this->writeDatatyped($occ);
    $this->writeType($occ);
    $this->writeScope($occ);
    $this->writeItemIdentifiers($occ);
    
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes an association. 
   * (Includes <association>, <type>, <role>, and <player> elements.)
   * 
   * @param Association The association to write.
   * @param int The element number.
   * @return void
   */
  private function writeAssociation(Association $assoc, $number) {
    $this->writer->startElement('association');
    $this->writeNumberReifierAttributes($number, $assoc);
    $this->writer->writeNewLine();
    $this->writeType($assoc);
    
    $roles = $assoc->getRoles();
    usort($roles, array(__CLASS__, 'compareRolesIgnoreParent'));
    $number = 1;
    foreach ($roles as $role) {
      $this->writer->startElement('role');
      $this->writeNumberReifierAttributes($number, $role);
      $this->writer->writeNewLine();
      
      $this->writer->startElement('player');
      $this->writer->writeAttribute(
      	'topicref', 
        $this->topicsToNumbersIndex[$role->getPlayer()->getId()]
      );
      $this->writer->text(null);// force </player>
      $this->writer->endElement();
      $this->writer->writeNewLine();
      
      $this->writeType($role);
      $this->writeItemIdentifiers($role);
      
      $this->writer->endElement();
      $this->writer->writeNewLine();
      $number++;
    }
    
    $this->writeScope($assoc);
    
    $this->writeItemIdentifiers($assoc);
    
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes a <value> and a <datatype> element.
   * 
   * @param DatatypeAware The datatype aware Topic Maps construct having 
   * 				the value and the datatype to write.
   * @return void
   */
  private function writeDatatyped(DatatypeAware $da) {
    $this->writer->writeElement('value', $this->escapeString($da->getValue()));
    $this->writer->writeNewLine();
    $loc = self::normalizeLocator($da->getDatatype());
    $this->writer->writeElement('datatype', $loc);
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes a <scope> element including <scopingTopic> elements.
   * 
   * @param Scoped The scoped Topic Maps construct having the scope to write.
   * @return void
   */
  private function writeScope(Scoped $scoped) {
    $scope = $scoped->getScope();
    if (empty($scope)) {
      return;
    }
    
    usort($scope, array(__CLASS__, 'compareTopics'));
    
    $this->writer->startElement('scope');
    $this->writer->writeNewLine();
    
    foreach ($scope as $theme) {
      $this->writer->startElement('scopingTopic');
      $this->writer->writeAttribute(
      	'topicref', 
        $this->topicsToNumbersIndex[$theme->getId()]
      );
      $this->writer->text(null);// force </scopingTopic>
      $this->writer->endElement();
      $this->writer->writeNewLine();
    }
    
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes a <type> element.
   * 
   * @param Typed The typed Topic Maps construct having the type to write.
   * @return void
   */
  private function writeType(Typed $typed) {
    $this->writer->startElement('type');
    $this->writer->writeAttribute(
    	'topicref', 
      $this->topicsToNumbersIndex[$typed->getType()->getId()]
    );
    $this->writer->text(null);// force </type>
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes a number and a reifier attribute.
   * 
   * @param int The number.
   * @param Reifiable The reifiable.
   * @return void
   */
  private function writeNumberReifierAttributes($number, Reifiable $reifiable) {
    $this->writer->writeAttribute('number', $number);
    $reifier = $reifiable->getReifier();
    if (!$reifier instanceof Topic) {
      return;
    }
    $this->writer->writeAttribute(
    	'reifier', 
      $this->topicsToNumbersIndex[$reifier->getId()]
    );
  }
  
  /**
   * Writes locators.
   * 
   * @param string The locator element name (e.g. "subjectIdentifiers").
   * @param array The locators to write.
   * @return void
   */
  private function writeLocators($elementName, array $locs) {
    if (empty($locs)) {
      return;
    }
    usort($locs, array(__CLASS__, 'compareLocators'));
    $this->writer->startElement($elementName);
    $this->writer->writeNewLine();
    foreach ($locs as $loc) {
      $this->writeLocator($loc);
    }
    $this->writer->endElement();
    $this->writer->writeNewLine();
  }
  
  /**
   * Writes an <itemIdentifiers> element.
   * 
   * @param Construct The construct having item identifiers.
   * @return void
   */
  private function writeItemIdentifiers(Construct $construct) {
    if (!is_null($this->filterTopicIidPattern) && $construct instanceof Topic) {
      $iids = $this->getFilteredTopicIids($construct);
    } else {
      $iids = $construct->getItemIdentifiers();
    }
    $this->writeLocators('itemIdentifiers', $iids);
  }
  
  /**
   * Writes a <locator> element.
   * 
   * @param string The locator.
   * @return void
   */
  private function writeLocator($loc) {
    $this->writer->writeElement(
    	'locator', 
      rawurldecode(self::normalizeLocator($loc))
    );
    $this->writer->writeNewLine();
  }
  
  /**
   * Prepares the topics: Sorts the topics in canonical sort order and builds 
   * the topics to numbers index.
   * 
   * @return void
   * @throws MIOException If PHPTMAPI <var>TypeInstanceIndex</var> is not available.
   */
  private function prepareTopics() {
    try {
      $index = $this->topicMap->getIndex('TypeInstanceIndexImpl');
    } catch (FeatureNotSupportedException $e) {
      throw new MIOException('Error in ' . __METHOD__ . ': TypeInstanceIndex is not available!');
    }
    // get all topics which have a type
    $instances = $index->getTopics(array(), true);
    // as type-instance relationships are modelled as properties 
    // associations have to be created which will be removed afterwards
    foreach ($instances as $instance) {
      $this->createTypeInstanceAssociation($instance);
    }
    unset($instances);
    $topics = $this->topicMap->getTopics();
    usort($topics, array(__CLASS__, 'compareTopics'));
    $i=1;
    foreach ($topics as $topic) {
      $this->topicsToNumbersIndex[$topic->getId()] = $i;
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
  private function prepareAssociations() {
    $assocs = $this->topicMap->getAssociations();
    usort($assocs, array(__CLASS__, 'compareAssociations'));
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
      usort($roles, array(__CLASS__, 'compareRolesIgnoreParent'));
      $z=1;
      foreach ($roles as $role) {
        $playerId = $role->getPlayer()->getId();
        if (!isset($this->playersToAssocsIndex[$playerId])) {
          $this->playersToAssocsIndex[$playerId] = array($i . '-' . $z);
        } else {
          $val = $this->playersToAssocsIndex[$playerId];
          $val[] = $i . '-' . $z;
          $this->playersToAssocsIndex[$playerId] = $val;
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
  private static function compareTopics(Topic $topic1, Topic $topic2) {
    if ($topic1->equals($topic2)) {
      return 0;
    }
    $res = 0;
    $res = self::compareArrays(
      $topic1->getSubjectIdentifiers(),
      $topic2->getSubjectIdentifiers(),
      'compareLocatorContent',
      true
    );
    if ($res == 0) {
      $res = self::compareArrays(
        $topic1->getSubjectLocators(),
        $topic2->getSubjectLocators(), 
        'compareLocatorContent',
        true
      );
      if ($res == 0) {
        $res = self::compareArrays(
          $topic1->getItemIdentifiers(),
          $topic2->getItemIdentifiers(), 
          'compareLocatorContent',
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
  private static function compareAssociations(Association $assoc1, Association $assoc2) {
    if ($assoc1->equals($assoc2)) {
      return 0;
    }
    $res = 0;
    $res = self::compareTopics($assoc1->getType(), $assoc2->getType());
    if ($res == 0) {
      $res = self::compareArrays(
        $assoc1->getRoles(), 
        $assoc2->getRoles(), 
        'compareRolesContentIgnoreParent'
      );
      if ($res == 0) {
        $res = self::compareArrays(
          $assoc1->getScope(), 
          $assoc2->getScope(), 
          'compareScopeContent'
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
  private static function compareArrays(array $arr1, array $arr2, $functionName, $makeUnique=false) {
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
  private static function compareLocatorContent(array $locs1, array $locs2, $count) {
    $res = 0;
    usort($locs1, array(__CLASS__, 'compareLocators'));
    usort($locs1, array(__CLASS__, 'compareLocators'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::compareLocators($locs1[$i], $locs2[$i]);
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
  private static function compareScopeContent(array $themes1, array $themes2, $count) {
    $res = 0;
    usort($themes1, array(__CLASS__, 'compareTopics'));
    usort($themes2, array(__CLASS__, 'compareTopics'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::compareTopics($themes1[$i], $themes2[$i]);
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
  private static function compareRolesContentIgnoreParent(array $roles1, array $roles2, $count) {
    $res = 0;
    usort($roles1, array(__CLASS__, 'compareRolesIgnoreParent'));
    usort($roles2, array(__CLASS__, 'compareRolesIgnoreParent'));
    for ($i=0; $i < $count && $res == 0; $i++) {
      $res = self::compareRolesIgnoreParent($roles1[$i], $roles2[$i]);
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
  private static function compareRolesIgnoreParent(Role $role1, Role $role2) {
    if ($role1->equals($role2)) {
      return 0;
    }
    $res = 0;
    $res = self::compareTopics($role1->getPlayer(), $role2->getPlayer());
    if ($res == 0) {
      $res = self::compareTopics($role1->getType(), $role2->getType());
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
  private function compareNamesIgnoreParent(Name $name1, Name $name2) {
    if ($name1->equals($name2)) {
      return 0;
    }
    $res = 0;
    $res = self::compareStrings($name1->getValue(), $name2->getValue());
    if ($res == 0) {
      $res = self::compareTopics($name1->getType(), $name2->getType());
      if ($res == 0) {
        $res = self::compareArrays(
          $name1->getScope(), 
          $name2->getScope(), 
          'compareScopeContent'
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
  private function compareDatatypeAwareIgnoreParent(DatatypeAware $da1, DatatypeAware $da2) {
    if ($da1->equals($da2)) {
      return 0;
    }
    $res = 0;
    $res = self::compareStrings($da1->getValue(), $da2->getValue());
    if ($res == 0) {
      $res = self::compareLocators($da1->getDatatype(), $da2->getDatatype());
      if ($res == 0) {
        if ($da1 instanceof Occurrence && $da2 instanceof Occurrence) {
          $res = self::compareTopics($da1->getType(), $da2->getType());
          if ($res == 0) {
            $res = self::compareArrays(
              $da1->getScope(), 
              $da2->getScope(), 
              'compareScopeContent'
            );
          }
        } else {// Variant
          $res = self::compareArrays(
            $da1->getScope(), 
            $da2->getScope(), 
            'compareScopeContent'
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
  private static function compareLocators($loc1, $loc2) {
    return self::compareStrings(
      self::normalizeLocator($loc1), 
      self::normalizeLocator($loc2)
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
  private static function compareStrings($str1, $str2) {
    return strcmp(self::normalizeString($str1), self::normalizeString($str2));
  }
  
  /**
   * Normalizes a string according to Unicode Normalization Form C.
   * 
   * @param string The string to normalize.
   * @return string The normalized string.
   * @static
   */
  private static function normalizeString($str) {
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
  private static function normalizeLocator($loc) {
    $locObj = new Net_URL2($loc);
    $loc = $locObj->getUrl();
    if (isset(self::$normLocs[$loc])) {
      return self::$normLocs[$loc];
    }
    $normLoc = '';
    $baseLoc = self::$normBaseLoc;
    if (strpos($loc, $baseLoc) !== false && strlen($loc) > strlen($baseLoc)) {
      $normLoc = self::removeLeadingSlash(
        substr($loc, strlen($baseLoc), strlen($loc))
      );
    } else {
      $baseLocObj = new Net_URL2($baseLoc);
      $path = $baseLocObj->getPath();
      if (strlen($path) > 0 && $path != '/') {
        $path = self::removeTrailingSlash($path);
        $slashCount = substr_count($path, '/');
        for ($i=0; $i < $slashCount; $i++) {
          // cut the last path and compare to locator
          $path = substr($path, 0, strrpos($path, '/'));
          $baseLoc = self::createLocator(
            $baseLocObj->getScheme(), $baseLocObj->getHost(), $path
          );
          if (strpos($loc, $baseLoc) !== false && strlen($loc) > strlen($baseLoc)) {
            $normLoc = self::removeLeadingSlash(
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
    $normLoc = self::removeTrailingSlash($normLoc);
    self::$normLocs[$loc] = $normLoc;
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
  private static function createLocator($scheme, $host, $path) {
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
  private static function removeTrailingSlash($str) {
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
  private static function removeLeadingSlash($str) {
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
  private static function escapeString($str) {
    return self::$srcXml ? $str : htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }
  
  /**
   * Normalizes the base locator.
   * 
   * @param string The base locator to normalize.
   * @return string A normalized base locator.
   */
  private function normalizeBaseLocator($loc) {
    $loc = new Net_URL2($loc);
    switch ($loc->getScheme()) {
      case 'file':
        return self::removeTrailingSlash(
          $loc->getScheme() . ':' . $loc->getHost() . $loc->getPath()
        );
        break;
      default:
        return self::removeTrailingSlash(
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
  private function createTypeInstanceAssociation(Topic $instance) {
    $types = $instance->getTypes();
    foreach ($types as $type) {
      if (is_null($this->typeInstance)) {
        $this->typeInstance = $this->topicMap->getTopicBySubjectIdentifier(
          self::PSI_TYPE_INSTANCE
        );
        if (is_null($this->typeInstance)) {
          $this->typeInstance = $this->topicMap->createTopicBySubjectIdentifier(
            self::PSI_TYPE_INSTANCE
          );
        }
      }
      if (is_null($this->type)) {
        $this->type = $this->topicMap->getTopicBySubjectIdentifier(
          self::PSI_TYPE
        );
        if (is_null($this->type)) {
          $this->type = $this->topicMap->createTopicBySubjectIdentifier(
            self::PSI_TYPE
          );
        }
      }
      if (is_null($this->instance)) {
        $this->instance = $this->topicMap->getTopicBySubjectIdentifier(
          self::PSI_INSTANCE
        );
        if (is_null($this->instance)) {
          $this->instance = $this->topicMap->createTopicBySubjectIdentifier(
            self::PSI_INSTANCE
          );
        }
      }
      $assoc = $this->topicMap->createAssociation($this->typeInstance);
      $assoc->createRole($this->type, $type);
      $assoc->createRole($this->instance, $instance);
      $this->typeInstanceAssocs[] = $assoc;
    }
  }
  
  /**
   * Cleans up: Removes all associations created in 
   * {@link createTypeInstanceAssociation()}.
   * 
   * @return void
   */
  private function cleanUp() {
    foreach ($this->typeInstanceAssocs as $assoc) {
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
  private function getFilteredTopicIids(Topic $topic) {
    $iids = $topic->getItemIdentifiers();
    $filteredIids = array();
    foreach ($iids as $iid) {
      if (strpos($iid, $this->filterTopicIidPattern) === false) {
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