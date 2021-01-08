<?php
/**
 * DOTAS Sistemas de Gerenciamento
 *
 * @copyright       DOTAS SISTEMAS
 * @license         Direito de uso
 * @package         Basico(Administrador)
 * @since           1.0
 * @version         2.0
 *
 */
//Configurações gerais
//////////////////////////////////////////////////////////
set_time_limit(60);
ini_set('memory_limit', '-1');
date_default_timezone_set("Brazil/East");
ini_set('display_errors', true);

//Sessao
//////////////////////////////////////////////////////////
session_start();

//SERVERS
//////////////////////////////////////////////////////////
$_SERVER['DB_NAME'] = 'bancoDB';
$_SERVER['DB_HOST'] = 'localhost';
$_SERVER['DB_LOGIN'] = 'root';
$_SERVER['DB_SENHA'] = '';

$_SERVER['root'] = $_SERVER[DOCUMENT_ROOT] . $_SERVER['pasta'];
//$_SERVER['home'] = 'http://dotas.com.br/trackauto';
//$_SERVER['url'] = $_SERVER[HTTP_HOST] . $_SERVER[SCRIPT_NAME];

?>