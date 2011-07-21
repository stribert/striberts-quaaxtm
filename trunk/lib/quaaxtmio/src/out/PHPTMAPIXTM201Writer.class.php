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
require_once('Net/URL2.php');

/**
 * Writes XTM 2.0 according to {@link http://www.isotopicmaps.org/sam/sam-xtm/} and 
 * XTM 2.1 according to {@link http://www.itscj.ipsj.or.jp/sc34/open/1378.htm}.
 * 
 * Works against PHPTMAPI (see {@link http://phptmapi.sourceforge.net}). 
 * 
 * @package out
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPIXTM201Writer
{  
  private $_baseLocator,
          $_topicMap,
          $_writer,
          $_iidIdx,
          $_sloIdx,
          $_sidIdx,
          $_xtm21;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct()
  {
    $this->_baseLocator = 
    $this->_writer = null;
    $this->_iidIdx = 
    $this->_sloIdx = 
    $this->_sidIdx = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct()
  {
    unset($this->_baseLocator);
    unset($this->_writer);
    unset($this->_iidIdx);
    unset($this->_sloIdx);
    unset($this->_sidIdx);
  }
  
  /**
   * Creates and returns the XTM 2.0 or 2.1.
   * 
   * @param TopicMap The topic map to write.
   * @param string The base locator. Default <var>null</var>.
   * @param string The XTM version. Default <var>2.1</var>.
   * @param boolean Enable or disable XML indentation. Default <var>true</var>.
   * @return string The XTM 2.0 or 2.1.
   * @throws MIOException If provided version is invalid (i.e neither 2.0 nor 2.1).
   */
  public function write(TopicMap $topicMap, $baseLocator=null, $version='2.1', $indent=true)
  {
    $this->xtm21 = true;
    if ($version !== '2.1') {
      if ($version !== '2.0') {
        throw new MIOException(
        	'Error in ' . __METHOD__ . ': Version ' . $version . ' is not supported.'
        );
      } else {
        $this->xtm21 = false;
      }
    }
    $this->_baseLocator = !is_null($baseLocator) 
      ? $baseLocator 
      : $topicMap->getLocator();
    $this->_baseLocator = $this->_normalizeLocator($this->_baseLocator);
      
    $this->_writer = new XMLWriter();
    $this->_writer->openMemory();
    $this->_writer->setIndent($indent);
    
    $this->_writer->startElement('topicMap');
    $this->_writer->writeAttribute('xmlns', 'http://www.topicmaps.org/xtm/');
    $this->_writer->writeAttribute('version', $version);
    $this->_writeReifier($topicMap);
    $this->_writeItemIdentifiers($topicMap);
    
    $topics = $topicMap->getTopics();
    foreach ($topics as $topic) {
      $this->_writeTopic($topic);
    }
    
    $assocs = $topicMap->getAssociations();
    foreach ($assocs as $assoc) {
      $this->_writeAssociation($assoc);
    }
    
    $this->_writer->endElement();
    $xtm = $this->_writer->outputMemory();
    
    return trim($xtm);
  }
  
  /**
   * Writes a <topic> element.
   * 
   * @param Topic The topic to write.
   * @return void
   */
  private function _writeTopic(Topic $topic)
  {
    $topicId = $topic->getId();
    
    $sids = $topic->getSubjectIdentifiers();
    sort($sids);
    $this->_sidIdx[$topicId] = $sids;
    
    $slos = $topic->getSubjectLocators();
    sort($slos);
    $this->_sloIdx[$topicId] = $slos;
    
    $iids = $topic->getItemIdentifiers();
    sort($iids);
    $this->_iidIdx[$topicId] = $iids;
    
    $this->_writer->startElement('topic');
    
    if ($this->xtm21) {
      if (empty($sids) && empty($slos) && empty($iids)) {
        $this->_writer->writeAttribute('id', $this->_getXtmTopicId($topic));
      }
    } else {
      $this->_writer->writeAttribute('id', $this->_getXtmTopicId($topic));
    }
    
    if (!empty($iids)) {
      $this->_writeItemIdentifiers($topic);
    }
    
    foreach ($slos as $slo) {
      $this->_writer->startElement('subjectLocator');
      $this->_writer->writeAttribute('href', $slo);
      $this->_writer->endElement();
    }
    
    foreach ($sids as $sid) {
      $this->_writer->startElement('subjectIdentifier');
      $this->_writer->writeAttribute('href', $sid);
      $this->_writer->endElement();
    }
    
    $types = $topic->getTypes();
    if (count($types) > 0) {
      $this->_writer->startElement('instanceOf');
      foreach ($types as $type) {
        $this->_writeTopicRef($type);
      }
      $this->_writer->endElement();
    }
    
    $names = $topic->getNames();
    foreach ($names as $name) {
      $this->_writeName($name);
    }
    
    $occs = $topic->getOccurrences();
    foreach ($occs as $occ) {
      $this->_writeOccurrence($occ);
    }
    
    $this->_writer->endElement();
  }
  
  /**
   * Writes a <name> element as well as possible <variant> element(s).
   * 
   * @param Name The name to write.
   * @return void
   */
  private function _writeName(Name $name)
  {
    $this->_writer->startElement('name');
    $this->_writeReifier($name);
    $this->_writeItemIdentifiers($name);
    $this->_writeType($name);
    $this->_writeScope($name);
    $this->_writer->writeElement('value', $name->getValue());
    
    $variants = $name->getVariants();
    foreach ($variants as $variant) {
      $this->_writer->startElement('variant');
      $this->_writeReifier($variant);
      $this->_writeItemIdentifiers($variant);
      $this->_writeScope($variant);
      $this->_writeValueDatatype($variant);
      $this->_writer->endElement();
    }
    
    $this->_writer->endElement();
  }
  
  /**
   * Writes an <occurrence> element.
   * 
   * @param Occurrence The occurrence to write.
   * @return void.
   */
  private function _writeOccurrence(Occurrence $occ)
  {
    $this->_writer->startElement('occurrence');
    $this->_writeReifier($occ);
    $this->_writeItemIdentifiers($occ);
    $this->_writeType($occ);
    $this->_writeScope($occ);
    $this->_writeValueDatatype($occ);
    $this->_writer->endElement();
  }
  
  /**
   * Writes an <association> element including <role> element(s).
   * 
   * @param Association The association to write.
   * @return void
   */
  private function _writeAssociation(Association $assoc)
  {
    $this->_writer->startElement('association');
    $this->_writeReifier($assoc);
    $this->_writeItemIdentifiers($assoc);
    $this->_writeType($assoc);
    $this->_writeScope($assoc);

    $roles = $assoc->getRoles();
    foreach ($roles as $role) {
      $this->_writer->startElement('role');
      $this->_writeReifier($role);
      $this->_writeItemIdentifiers($role);
      $this->_writeType($role);
      $this->_writeTopicRef($role->getPlayer());
      $this->_writer->endElement();
    }
    
    $this->_writer->endElement();
  }
  
  /**
   * Writes a <resourceRef> or a <resourceData> element.
   * 
   * @param DatatypeAware The datatype aware Topic Maps construct having the value 
   * 				and the datatype to write.
   * @return void
   */
  private function _writeValueDatatype(DatatypeAware $da)
  {
    $datatype = $da->getDatatype();
    if ($datatype == MIOUtil::XSD_ANYURI) {
      $this->_writer->startElement('resourceRef');
      $this->_writer->writeAttribute('href', $da->getValue());
      $this->_writer->endElement();
    } else {
      if ($datatype == MIOUtil::XSD_STRING) {
        $this->_writer->writeElement('resourceData', $da->getValue());
      } else {
        $this->_writer->startElement('resourceData');
        $this->_writer->writeAttribute('datatype', $datatype);
        $this->_writer->text($da->getValue());
        $this->_writer->endElement();
      }
    }
  }
  
  /**
   * Writes a <scope> element.
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
    $this->_writer->startElement('scope');
    foreach ($scope as $theme) {
      $this->_writeTopicRef($theme);
    }
    $this->_writer->endElement();
  }
  
  /**
   * Writes a <type> element.
   * 
   * @param Typed The typed Topic Maps construct having the type to write.
   * @return void
   */
  private function _writeType(Typed $typed)
  {
    $type = $typed->getType();
    if (empty($type)) {
      return;
    }
    $this->_writer->startElement('type');
    $this->_writeTopicRef($type);
    $this->_writer->endElement();
  }
  
  /**
   * Writes <itemIdentity> element(s).
   * 
   * @param Construct The Topic Maps construct having the item identifiers to write.
   * @return void
   */
  private function _writeItemIdentifiers(Construct $construct)
  {
    if ($construct instanceof Topic && isset($this->_iidIdx[$construct->getId()])) {
      $iids = $this->_iidIdx[$construct->getId()];
    } else {
      $iids = $construct->getItemIdentifiers();
      if ($construct instanceof Topic) {
        $this->_iidIdx[$construct->getId()] = $iids;
      }
    }
    foreach ($iids as $iid) {
      $this->_writer->startElement('itemIdentity');
      $this->_writer->writeAttribute('href', $this->_getHref($iid));
      $this->_writer->endElement();
    }
  }
  
  /**
   * Writes a reifier.
   * 
   * @param Reifiable The reifiable Topic Maps construct having the reifier to write.
   * @return void
   */
  private function _writeReifier(Reifiable $reifiable)
  {
    $reifier = $reifiable->getReifier();
    if (!$reifier instanceof Topic) {
      return; 
    }
    if ($this->xtm21) {
      $this->_writer->startElement('reifier');
      $this->_writeTopicRef($reifier);
      $this->_writer->endElement();
    } else {
      $ref = $this->_getXtmTopicId($reifier, true);
      $this->_writer->writeAttribute('reifier', $ref);
    }
  }
  
  /**
   * Writes a topic reference.
   * 
   * @param Topic The topic.
   * @return void
   */
  private function _writeTopicRef(Topic $topic)
  {
    if ($this->xtm21) {
      if (isset($this->_sidIdx[$topic->getId()])) {
        $sids = $this->_sidIdx[$topic->getId()];
      } else {
        $sids = $topic->getSubjectIdentifiers();
      }
      if (!empty($sids)) {
        sort($sids);
        $this->_writer->startElement('subjectIdentifierRef');
        $this->_writer->writeAttribute('href', $sids[0]);
        $this->_writer->endElement();
      } else {
        if (isset($this->_sloIdx[$topic->getId()])) {
          $slos = $this->_sloIdx[$topic->getId()];
        } else {
          $slos = $topic->getSubjectLocators();
        }
        if (!empty($slos)) {
          sort($slos);
          $this->_writer->startElement('subjectLocatorRef');
          $this->_writer->writeAttribute('href', $slos[0]);
          $this->_writer->endElement();
        } else {
          if (isset($this->_iidIdx[$topic->getId()])) {
            $iids = $this->_iidIdx[$topic->getId()];
          } else {
            $iids = $topic->getItemIdentifiers();
          }
          if (!empty($iids)) {
            sort($iids);
            $this->_writer->startElement('topicRef');
            $this->_writer->writeAttribute('href', $iids[0]);
            $this->_writer->endElement();
          } else {
            $this->_writer->startElement('topicRef');
            $this->_writer->writeAttribute('href', $this->_getXtmTopicId($topic, true));
            $this->_writer->endElement();
          }
        }
      }
    } else {
      $this->_writer->startElement('topicRef');
      $this->_writer->writeAttribute('href', $this->_getXtmTopicId($topic, true));
      $this->_writer->endElement();
    }
  }
  
  /**
   * Gets the content for the <var>href</var> attribute (a relative or an absolute URI).
   * 
   * @param string The raw locator.
   * @return string A relative or an absolute URI.
   */
  private function _getHref($loc)
  {
    $loc = $this->_normalizeLocator($loc);
    if (strpos($loc, $this->_baseLocator . '#') !== false) {
      $href = substr($loc, strlen($this->_baseLocator . '#'), strlen($loc));
      if (!empty($href)) {
        return '#' . $href;
      }
    }
    return $loc;
  }
  
  /**
   * Gets the content for the <var>id</var> attribute of the <topic> element.
   * 
   * @param Topic The topic.
   * @param boolean Indicator if a URI fragment must be created. Default <var>false</var>.
   * @return string The XTM topic id.
   */
  private function _getXtmTopicId(Topic $topic, $fragment=false)
  {
    if (isset($this->_iidIdx[$topic->getId()])) {
      $iids = $this->_iidIdx[$topic->getId()];
    } else {
      $iids = $topic->getItemIdentifiers();
      $this->_iidIdx[$topic->getId()] = $iids;
    }
    if (count($iids) > 0) {
      sort($iids);
      foreach ($iids as $iid) {
        $iid = $this->_normalizeLocator($iid);
        if (strpos($iid, $this->_baseLocator . '#') !== false) {
          $id = substr($iid, strlen($this->_baseLocator . '#'), strlen($iid));
          if (!empty($id)) {
            return !$fragment ? $id : '#' . $id;
          }
        }
      }
      $id = $topic->getId();
    } else {
      $id = $topic->getId();
    }
    return !$fragment ? $id : '#' . $id;
  }
  
  /**
   * Normalizes a locator.
   * 
   * @param string The locator to normalize.
   * @return string A normalized locator.
   */
  private function _normalizeLocator($loc)
  {
    $locObj = new Net_URL2($loc);
    return $locObj->getNormalizedURL();
  }
}
?>