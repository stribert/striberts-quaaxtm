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
interface PHPTMAPITopicMapHandlerInterface
{
  /**
   * Notifies the start of a topic map.
   *
   * @return void
   * @throws MIOException If the topic map's base locator is already used or
   * 				the topic map cannot be created for an unknown reason.
   */
  public function startTopicMap();

  /**
   * Notifies the end of a topic map.
   *
   * @return void
   */
  public function endTopicMap();

  /**
   * Notifies the start of a topic.
   *
   * @param ReferenceInterface A reference representing an item identifier.
   * @return void
   * @throws MIOException If the reference type is unknown.
   */
  public function startTopic(ReferenceInterface $identity);

  /**
   * Notifies the end of a topic.
   *
   * @return void
   */
  public function endTopic();

  /**
   * Notifies the start of an association.
   *
   * @return void
   */
  public function startAssociation();

  /**
   * Notifies the end of an association.
   *
   * @return void
   */
  public function endAssociation();

  /**
   * Notifies the start of an association role.
   *
   * @return void
   */
  public function startRole();

  /**
   * Notifies the end of an association role.
   *
   * @return void
   */
  public function endRole();

  /**
   * Notifies the start of an occurrence.
   *
   * @return void
   */
  public function startOccurrence();

  /**
   * Notifies the end of an occurrence.
   *
   * @return void
   */
  public function endOccurrence();

  /**
   * Notifies the start of a topic name.
   *
   * @return void
   */
  public function startName();

  /**
   * Notifies the end of a topic name.
   *
   * @return void
   */
  public function endName();

  /**
   * Notifies the start of a name variant.
   *
   * @return void
   */
  public function startVariant();

  /**
   * Notifies the end of a name variant.
   *
   * @return void
   */
  public function endVariant();

  /**
   * Notifies the start of a scope.
   * 
   * @return void
   */
  public function startScope();

  /**
   * Notifies the end of a scope.
   *
   * @return void
   */
  public function endScope();

  /**
   * Notifies a topic name value
   *
   * @param string The name value
   * @return void
   */
  public function nameValue($value);

  /**
   * Notifies a variant name or an occurrence value.
   *
   * @param string The value.
   * @param string The datatype of the value.
   * @return void
   */
  public function value($value, $datatype);

  /**
   * Notifies a subject identifier.
   *
   * @param string The URI of the subject identifier.
   * @return void
   */
  public function subjectIdentifier($sid);

  /**
   * Notifies a subject locator.
   *
   * @param string The URI of the subject locator.
   * @return void
   */
  public function subjectLocator($slo);

  /**
   * Notifies an item identifier.
   *
   * @param string The URI of the item identifier.
   * @return void
   */
  public function itemIdentifier($iid);

  /**
   * Notifies the start of a type.
   *
   * @return void
   */
  public function startType();

  /**
   * Notifies the end of a type.
   *
   * @return void
   */
  public function endType();

  /**
   * Notifies the start of a reifier.
   *
   * @return void
   */
  public function startReifier();

  /**
   * Notifies the end of a reifier.
   *
   * @return void
   */
  public function endReifier();
  
  /**
   * Notifies the start of a merge map.
   *
   * @param string The XTM document's locator.
   * @param string The reader class name.
   * @return void
   * @throws MIOException If the max. allowed count of merge map operations is exceeded.
   */
  public function startMergeMap($locator, $readerClassName);
  
  /**
   * Notifies the end of a merge map.
   *
   * @return void
   */
  public function endMergeMap();

  /**
   * Notifies a topic reference.
   *
   * @param ReferenceInterface A URI which is the topic reference.
   * @return void
   * @throws MIOException If the reference type is unknown.
   */
  public function topicRef(ReferenceInterface $identity);

  /**
   * Notifies the start of an <code>instance of</code> releationship.
   * 
   * @return void
   */
  public function startIsa();

  /**
   * Notifies the end of an <code>instance of</code> releationship.
   *
   * @return void
   */
  public function endIsa();
}
?>