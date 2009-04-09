<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

define('PATH_TO_FACTORY_IMPL', '/home/johannes/workspace/quaaxtm0.5_svn_local/' .
  'src/phptmapi/core/TopicMapSystemFactoryImpl.class.php');
define('FACTORY_IMPL', 'TopicMapSystemFactoryImpl');
// extension for implementation
define('_db_host', 'localhost');
define('_db_name', 'quaaxtm_new');
define('_db_user', 'root');
define('_db_pass', '');
define('_db_port', '3306');
$config['table']['association'] = 'qtm_association';
$config['table']['association_scope'] = 'qtm_association_scope';
$config['table']['assocrole'] = 'qtm_assocrole';
$config['table']['instanceof'] = 'qtm_instanceof';
$config['table']['occurrence'] = 'qtm_occurrence';
$config['table']['occurrence_scope'] = 'qtm_occurrence_scope';
$config['table']['scope'] = 'qtm_scope';
$config['table']['theme'] = 'qtm_theme';
$config['table']['subjectidentifier'] = 'qtm_subjectidentifier';
$config['table']['subjectlocator'] = 'qtm_subjectlocator';
$config['table']['topic'] = 'qtm_topic';
$config['table']['topicmap'] = 'qtm_topicmap';
$config['table']['topicmapconstruct'] = 'qtm_topicmapconstruct';
$config['table']['itemidentifier'] = 'qtm_itemidentifier';
$config['table']['topicname'] = 'qtm_topicname';
$config['table']['topicname_scope'] = 'qtm_topicname_scope';
$config['table']['variant'] = 'qtm_variant';
$config['table']['variant_scope'] = 'qtm_variant_scope';
?>
