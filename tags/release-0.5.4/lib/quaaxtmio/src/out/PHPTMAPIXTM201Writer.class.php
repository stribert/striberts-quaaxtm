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
class PHPTMAPIXTM201Writer {
  
  private $baseLocator,
          $topicMap,
          $writer,
          $iidIdx,
          $sloIdx,
          $sidIdx,
          $xtm21;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    $this->baseLocator = 
    $this->writer = null;
    $this->iidIdx = 
    $this->sloIdx = 
    $this->sidIdx = array();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    unset($this->baseLocator);
    unset($this->writer);
    unset($this->iidIdx);
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
  public function write(TopicMap $topicMap, $baseLocator=null, $version='2.1', $indent=true) {
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
    $this->baseLocator = !is_null($baseLocator) 
      ? $baseLocator 
      : $topicMap->getLocator();
    $this->baseLocator = $this->normalizeLocator($this->baseLocator);
      
    $this->writer = new XMLWriter();
    $this->writer->openMemory();
    $this->writer->setIndent($indent);
    
    $this->writer->startElement('topicMap');
    $this->writer->writeAttribute('xmlns', 'http://www.topicmaps.org/xtm/');
    $this->writer->writeAttribute('version', $version);
    $this->writeReifier($topicMap);
    $this->writeItemIdentifiers($topicMap);
    
    $topics = $topicMap->getTopics();
    foreach ($topics as $topic) {
      $this->writeTopic($topic);
    }
    
    $assocs = $topicMap->getAssociations();
    foreach ($assocs as $assoc) {
      $this->writeAssociation($assoc);
    }
    
    $this->writer->endElement();
    $xtm = $this->writer->outputMemory();
    
    return trim($xtm);
  }
  
  /**
   * Writes a <topic> element.
   * 
   * @param Topic The topic to write.
   * @return void
   */
  private function writeTopic(Topic $topic) {
    $topicId = $topic->getId();
    
    $sids = $topic->getSubjectIdentifiers();
    sort($sids);
    $this->sidIdx[$topicId] = $sids;
    
    $slos = $topic->getSubjectLocators();
    sort($slos);
    $this->sloIdx[$topicId] = $slos;
    
    $iids = $topic->getItemIdentifiers();
    sort($iids);
    $this->iidIdx[$topicId] = $iids;
    
    $this->writer->startElement('topic');
    
    if ($this->xtm21) {
      if (empty($sids) && empty($slos) && empty($iids)) {
        $this->writer->writeAttribute('id', $this->getXtmTopicId($topic));
      }
    } else {
      $this->writer->writeAttribute('id', $this->getXtmTopicId($topic));
    }
    
    if (!empty($iids)) {
      $this->writeItemIdentifiers($topic);
    }
    
    foreach ($slos as $slo) {
      $this->writer->startElement('subjectLocator');
      $this->writer->writeAttribute('href', $slo);
      $this->writer->endElement();
    }
    
    foreach ($sids as $sid) {
      $this->writer->startElement('subjectIdentifier');
      $this->writer->writeAttribute('href', $sid);
      $this->writer->endElement();
    }
    
    $types = $topic->getTypes();
    if (count($types) > 0) {
      $this->writer->startElement('instanceOf');
      foreach ($types as $type) {
        $this->writeTopicRef($type);
      }
      $this->writer->endElement();
    }
    
    $names = $topic->getNames();
    foreach ($names as $name) {
      $this->writeName($name);
    }
    
    $occs = $topic->getOccurrences();
    foreach ($occs as $occ) {
      $this->writeOccurrence($occ);
    }
    
    $this->writer->endElement();
  }
  
  /**
   * Writes a <name> element as well as possible <variant> element(s).
   * 
   * @param Name The name to write.
   * @return void
   */
  private function writeName(Name $name) {
    $this->writer->startElement('name');
    $this->writeReifier($name);
    $this->writeItemIdentifiers($name);
    $this->writeType($name);
    $this->writeScope($name);
    $this->writer->writeElement('value', $name->getValue());
    
    $variants = $name->getVariants();
    foreach ($variants as $variant) {
      $this->writer->startElement('variant');
      $this->writeReifier($variant);
      $this->writeItemIdentifiers($variant);
      $this->writeScope($variant);
      $this->writeValueDatatype($variant);
      $this->writer->endElement();
    }
    
    $this->writer->endElement();
  }
  
  /**
   * Writes an <occurrence> element.
   * 
   * @param Occurrence The occurrence to write.
   * @return void.
   */
  private function writeOccurrence(Occurrence $occ) {
    $this->writer->startElement('occurrence');
    $this->writeReifier($occ);
    $this->writeItemIdentifiers($occ);
    $this->writeType($occ);
    $this->writeScope($occ);
    $this->writeValueDatatype($occ);
    $this->writer->endElement();
  }
  
  /**
   * Writes an <association> element including <role> element(s).
   * 
   * @param Association The association to write.
   * @return void
   */
  private function writeAssociation(Association $assoc) {
    $this->writer->startElement('association');
    $this->writeReifier($assoc);
    $this->writeItemIdentifiers($assoc);
    $this->writeType($assoc);
    $this->writeScope($assoc);

    $roles = $assoc->getRoles();
    foreach ($roles as $role) {
      $this->writer->startElement('role');
      $this->writeReifier($role);
      $this->writeItemIdentifiers($role);
      $this->writeType($role);
      $this->writeTopicRef($role->getPlayer());
      $this->writer->endElement();
    }
    
    $this->writer->endElement();
  }
  
  /**
   * Writes a <resourceRef> or a <resourceData> element.
   * 
   * @param DatatypeAware The datatype aware Topic Maps construct having the value 
   * 				and the datatype to write.
   * @return void
   */
  private function writeValueDatatype(DatatypeAware $da) {
    $datatype = $da->getDatatype();
    if ($datatype == MIOUtil::XSD_ANYURI) {
      $this->writer->startElement('resourceRef');
      $this->writer->writeAttribute('href', $da->getValue());
      $this->writer->endElement();
    } else {
      if ($datatype == MIOUtil::XSD_STRING) {
        $this->writer->writeElement('resourceData', $da->getValue());
      } else {
        $this->writer->startElement('resourceData');
        $this->writer->writeAttribute('datatype', $datatype);
        $this->writer->text($da->getValue());
        $this->writer->endElement();
      }
    }
  }
  
  /**
   * Writes a <scope> element.
   * 
   * @param Scoped The scoped Topic Maps construct having the scope to write.
   * @return void
   */
  private function writeScope(Scoped $scoped) {
    $scope = $scoped->getScope();
    if (empty($scope)) {
      return;
    }
    $this->writer->startElement('scope');
    foreach ($scope as $theme) {
      $this->writeTopicRef($theme);
    }
    $this->writer->endElement();
  }
  
  /**
   * Writes a <type> element.
   * 
   * @param Typed The typed Topic Maps construct having the type to write.
   * @return void
   */
  private function writeType(Typed $typed) {
    $type = $typed->getType();
    if (empty($type)) {
      return;
    }
    $this->writer->startElement('type');
    $this->writeTopicRef($type);
    $this->writer->endElement();
  }
  
  /**
   * Writes <itemIdentity> element(s).
   * 
   * @param Construct The Topic Maps construct having the item identifiers to write.
   * @return void
   */
  private function writeItemIdentifiers(Construct $construct) {
    if ($construct instanceof Topic && isset($this->iidIdx[$construct->getId()])) {
      $iids = $this->iidIdx[$construct->getId()];
    } else {
      $iids = $construct->getItemIdentifiers();
      if ($construct instanceof Topic) {
        $this->iidIdx[$construct->getId()] = $iids;
      }
    }
    foreach ($iids as $iid) {
      $this->writer->startElement('itemIdentity');
      $this->writer->writeAttribute('href', $this->getHref($iid));
      $this->writer->endElement();
    }
  }
  
  /**
   * Writes a reifier.
   * 
   * @param Reifiable The reifiable Topic Maps construct having the reifier to write.
   * @return void
   */
  private function writeReifier(Reifiable $reifiable) {
    $reifier = $reifiable->getReifier();
    if (!$reifier instanceof Topic) {
      return; 
    }
    if ($this->xtm21) {
      $this->writer->startElement('reifier');
      $this->writeTopicRef($reifier);
      $this->writer->endElement();
    } else {
      $ref = $this->getXtmTopicId($reifier, true);
      $this->writer->writeAttribute('reifier', $ref);
    }
  }
  
  private function writeTopicRef(Topic $topic) {
    if ($this->xtm21) {
      if (isset($this->sidIdx[$topic->getId()])) {
        $sids = $this->sidIdx[$topic->getId()];
      } else {
        $sids = $topic->getSubjectIdentifiers();
      }
      if (!empty($sids)) {
        sort($sids);
        $this->writer->startElement('subjectIdentifierRef');
        $this->writer->writeAttribute('href', $sids[0]);
        $this->writer->endElement();
      } else {
        if (isset($this->sloIdx[$topic->getId()])) {
          $slos = $this->sloIdx[$topic->getId()];
        } else {
          $slos = $topic->getSubjectLocators();
        }
        if (!empty($slos)) {
          sort($slos);
          $this->writer->startElement('subjectLocatorRef');
          $this->writer->writeAttribute('href', $slos[0]);
          $this->writer->endElement();
        } else {
          if (isset($this->iidIdx[$topic->getId()])) {
            $iids = $this->iidIdx[$topic->getId()];
          } else {
            $iids = $topic->getItemIdentifiers();
          }
          if (!empty($iids)) {
            sort($iids);
            $this->writer->startElement('topicRef');
            $this->writer->writeAttribute('href', $iids[0]);
            $this->writer->endElement();
          } else {
            $this->writer->startElement('topicRef');
            $this->writer->writeAttribute('href', $this->getXtmTopicId($topic, true));
            $this->writer->endElement();
          }
        }
      }
    } else {
      $this->writer->startElement('topicRef');
      $this->writer->writeAttribute('href', $this->getXtmTopicId($topic, true));
      $this->writer->endElement();
    }
  }
  
  /**
   * Gets the content for the <var>href</var> attribute (a relative or an absolute URI).
   * 
   * @param string The raw locator.
   * @return string A relative or an absolute URI.
   */
  private function getHref($loc) {
    $loc = $this->normalizeLocator($loc);
    if (strpos($loc, $this->baseLocator . '#') !== false) {
      $href = substr($loc, strlen($this->baseLocator . '#'), strlen($loc));
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
  private function getXtmTopicId(Topic $topic, $fragment=false) {
    if (isset($this->iidIdx[$topic->getId()])) {
      $iids = $this->iidIdx[$topic->getId()];
    } else {
      $iids = $topic->getItemIdentifiers();
      $this->iidIdx[$topic->getId()] = $iids;
    }
    if (count($iids) > 0) {
      sort($iids);
      foreach ($iids as $iid) {
        $iid = $this->normalizeLocator($iid);
        if (strpos($iid, $this->baseLocator . '#') !== false) {
          $id = substr($iid, strlen($this->baseLocator . '#'), strlen($iid));
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
  private function normalizeLocator($loc) {
    $locObj = new Net_URL2($loc);
    return $locObj->getNormalizedURL();
  }
}
?>