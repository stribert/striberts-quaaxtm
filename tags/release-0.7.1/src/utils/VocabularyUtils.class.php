<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Provides vocabulary/terminology and feature URIs.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class VocabularyUtils
{
  /**
   * The PSI for the default name type.
   */
  const TMDM_PSI_DEFAULT_NAME_TYPE = 'http://psi.topicmaps.org/iso13250/model/topic-name';
  
  /**
   * The identifier for the XSD datatype "string".
   */
  const XSD_STRING = 'http://www.w3.org/2001/XMLSchema#string';
  
  /**
   * The identifier for the XSD datatype "anyURI".
   */
  const XSD_ANYURI = 'http://www.w3.org/2001/XMLSchema#anyURI';

  /**
   * The identifier (aka feature string) for the TMAPI 2.0 feature "automerge".
   */
  const TMAPI_FEATURE_AUTOMERGE = 'http://tmapi.org/features/automerge/';
  
  /**
   * The identifier (aka feature string) for the TMAPI 2.0 feature "read only".
   */
  const TMAPI_FEATURE_READONLY = 'http://tmapi.org/features/readOnly/';
  
  /**
   * The identifier (aka feature string) for the TMAPI 2.0 feature "type-instance 
   * association".
   */
  const TMAPI_FEATURE_TYPE_INST_ASSOC = 'http://tmapi.org/features/type-instance-associations';
        
  /**
   * The identifier (aka feature string) for the QuaaxTM feature "auto duplicate removal".
   */
  const QTM_FEATURE_AUTO_DUPL_REMOVAL = 'http://quaaxtm.sourceforge.net/features/auto-duplicate-removal/';
  
  /**
   * The identifier (aka feature string) for the QuaaxTM feature "(MySQL) result cache".
   */
  const QTM_FEATURE_RESULT_CACHE = 'http://quaaxtm.sourceforge.net/features/result-cache/';
  
  /**
   * The optional MySQL property. This enables developers to replace the default used 
   * /src/utils/Mysql.class.php by a custom class which extends Mysql. Extension is a 
   * mandatory class connection which must be considered.
   * This is e.g. used to inject /src/utils/MysqlMock.class.php in the unit tests to test 
   * the memcached based result cache.
   * 
   * See /src/phptmapi/core/TopicMapSystemFactoryImpl::newTopicMapSystem() how this 
   * property is taken into account.
   */
  const QTM_PROPERTY_MYSQL = 'Mysql';

  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct(){}
}
?>