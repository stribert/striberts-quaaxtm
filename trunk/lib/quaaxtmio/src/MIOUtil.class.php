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
 * Provides utilities for serializing and deserializing topic maps.
 * 
 * @package io
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MIOUtil {

  const XSD_ANYTYPE = 'http://www.w3.org/2001/XMLSchema#anyType',
        XSD_STRING = 'http://www.w3.org/2001/XMLSchema#string',
        XSD_ANYURI = 'http://www.w3.org/2001/XMLSchema#anyURI',
        
        PSI_TYPE_INSTANCE = 'http://psi.topicmaps.org/iso13250/model/type-instance',
        PSI_TYPE = 'http://psi.topicmaps.org/iso13250/model/type',
        PSI_INSTANCE = 'http://psi.topicmaps.org/iso13250/model/instance';
  
  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct(){}
  
  /**
   * Reads given file.
   * 
   * @param string The file to read.
   * @return string The file content.
   * @static
   */
  public static function readFile($file) {
    $constituents = parse_url($file);
    if (isset($constituents['host']) && isset($constituents['scheme'])) {
      return self::_readRemoteFile($file);
    } else {
      return self::_readLocalFile($file);
    }
  }
  
  /**
   * Reads a local file.
   * 
   * @param string The file to read.
   * @return string
   * @static
   * @throws MIOException If file cannot be read.
   */
  private static function _readLocalFile($file) {
    // PHP does not recognize file:/, needs file:///
    $file = str_replace('file:/', 'file:///', $file);
    if ($fileHandle = @fopen($file, 'r')) {
      $content = fread($fileHandle, filesize($file));
      fclose($fileHandle);
      return $content;
    } else {
      throw new MIOException('Error in ' . __METHOD__ . ': Cannot read ' . $file . '!');
    }
  }
    
  /**
   * Reads a remote file using CURL.
   * 
   * @param string The file to read.
   * @return string
   * @static
   * @throws MIOException If file cannot be read.
   */
  private static function _readRemoteFile($file) {
    $ch = curl_init($file);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode != 200) {
      throw new MIOException(
      	'Error in ' . __METHOD__ . 
      	': Cannot read ' . $file . '! Retrieved HTTP status code ' . $httpCode . '.'
      );
    }
    return $content;
  }
}
?>