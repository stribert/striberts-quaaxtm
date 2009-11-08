<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Provides vocabulary/terminology.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class VocabularyUtils {

  const TMDM_PSI_DEFAULT_NAME_TYPE = 'http://psi.topicmaps.org/iso13250/model/topic-name',
  
        XSD_STRING = 'http://www.w3.org/2001/XMLSchema#string',
        XSD_ANYURI = 'http://www.w3.org/2001/XMLSchema#anyURI',
        
        TMAPI_FEATURE_AUTOMERGE = 'http://tmapi.org/features/automerge/',
        TMAPI_FEATURE_READONLY = 'http://tmapi.org/features/readOnly/',
        TMAPI_FEATURE_TYPE_INST_ASSOC = 'http://tmapi.org/features/type-instance-associations',
        QTM_FEATURE_AUTO_DUPL_REMOVAL = 'http://quaaxtm.sourceforge.net/features/auto-duplicate-removal/';

  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct() {}
}
?>