<?php
/*
 * Dotas Sistemas
 * Carlos Henrique Silva Dotas < carlosdotas@gmail.com >
 * fone: 62 996157340
 * http://www.dotas.com.br/
 *//////////////////////////////

//Inclui Configurações
//////////////////////////////////////////////////////////////////////////////////
include_once 'config.php';

//Conecta ao DB
//////////////////////////////////////////////////////////////////////////////////
$conect = conectar_mysql();

////////////////////////////////////Nova Configuração de MYSQL
//Conecta ao banco de dados
function conectar_mysql($db = '', $host = '', $login = '', $senha = '') {

	if (!$db) {
		$db = $_SERVER['DB_NAME'];
	}

	if (!$host) {
		$host = $_SERVER['DB_HOST'];
	}

	if (!$login) {
		$login = $_SERVER['DB_LOGIN'];
	}

	if (!$senha) {
		$senha = $_SERVER['DB_SENHA'];
	}

	# PHP 7
	$conexao = mysqli_connect($host, $login, $senha);

	if (!mysqli_select_db($conexao, $db)) {
		mysqli_query($conexao, "CREATE DATABASE " . $db);
		mysqli_select_db($conexao, $db, $conexao);
		echo 'Tabela Criada: ' . $db;
		header('Location: /');
		die;
	}

	$banco = mysqli_select_db($conexao, $db);
	mysqli_set_charset($conexao, 'utf8');
	return $conexao;
}

function alterar($tabela, $array, $id, $campo = "") {
	///Versão 1
	if (!$campo) {
		$campo = $tabela . '_id';
	}

	foreach ($array as $key => $value) {$saida[] = $key . "='" . $value . "'";}
	$saida = "UPDATE " . $tabela . " SET " . implode('´,', $saida) . " WHERE " . $campo . "=$id;";
	return mysqli_query($saida);
}

//Salvamento Inteligente
function salvar_mysql($tabela, $dados, $id = "", $campo = "") {
	global $conect;

	if ($dados[$tabela . '_id'] == '') {unset($dados[$tabela . '_id']);}

	//Cria campo caso nao exista
	$colunas = lista_colunas($tabela);

	//Clia tabela caso nao exista
	if (!$colunas) {
		mysqli_query($conect, 'CREATE TABLE ' . $tabela . ' ( ' . $tabela . '_id INT( 15 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE = MyISAM;');
		$colunas = lista_colunas($tabela);
	}

	foreach ($dados as $k => $v) {

		$colunas = array();

		$type = explode('|', $k);

		if ($type[1]) {
			unset($dados[$k]);
			$dados[$type[0]] = $v;
		}

		$k = $type[0];

		//cONVERSOR DE DATAS PARA mkTIME
		unset($coment);
		if ($type[1] == data or $type[1] == date) {

			$type[1] = 'INT(20)';
			$coment = 'date';
			$data = explode('/', $v);
			if ($data[2]) {
				$dados[$k] = mktime(0, 0, 0, $data[1], $data[0], $data[2]);
			}
			unset($data);

			$data = explode('-', $v);
			if ($data[2]) {
				$dados[$k] = mktime(0, 0, 0, $data[1], $data[2], $data[0]);
			}

		}

		if (!$type[1]) {
			$type[1] = 'TEXT';
		}

		if (!array_search($k, $colunas)) {

			mysqli_query($conect, 'ALTER TABLE `' . $tabela . '` ADD `' . $k . "` " . $type[1] . " COMMENT '" . $coment . "' ");
		}
		;
	}

//Verifica Existencia de valor
	if ($id) {
		$id_db = buscar_mysql($tabela, $id, $campo);
	}

	//Grava Resutados
	if ($id_db) {
		altera_mysql($tabela, $campo, $id, $dados);
		$result[id] = $id_db[$tabela . '_id'];
		$result[tipo] = a;
		$result[sucess] = true;
	} else {
		$result[id] = inserir_mysql($tabela, $dados);
		$result[tipo] = s;
		$result[sucess] = true;
	}

	//Grava logs no sistema
	if ($tabela != logs_users) {
		$result[entrada][$tabela . '_id'] = $result[id];
		$result[tabela] = $tabela;
		$result[entrada] = json_encode($dados);
		if ($id_db) {
			$result[anterior] = json_encode($id_db);
		}

		// salvar_mysql(logs_users,$result);
	}

//mysqli_close($conect);
	return $result;

}

function listar_tabelas() {
	global $conect;
	return array_column(mysqli_fetch_all($conect->query('SHOW TABLES')), 0);
	mysqli_close($conect);
}

//Verifica colunas
function lista_colunas($tabela) {
	global $conect; //$conect = conectar_mysql();
	$result = mysqli_query($conect, 'SHOW full COLUMNS FROM ' . $tabela);

	if ($result) {
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				$colunas[] = $row;
			}
		}
	}

	//mysqli_close($conect);
	return $colunas;
}

//insere dados em Tabela
function inserir_mysql($tabela, $array) {

	global $conect;
	mysqli_query($conect, "INSERT INTO $tabela (" . implode(',', array_keys($array)) . ") VALUES ( '" . implode("','", $array) . "' );");
	$saida = mysqli_insert_id($conect);
	//mysqli_close($conect);
	return $saida;

}

//Exclui dados em Tabela
function exclui_mysql($tabela, $id, $col = '') {

	global $conect;

	if (!$col) {
		$col = $tabela . "_id";
	}

	return mysqli_query($conect, "DELETE FROM $tabela WHERE $col = $id");
	//mysqli_close($conect);
}

//altera dados em Tabela
function altera_mysql($tabela, $coluna, $referencia, $array) {

	foreach ($array as $k => $v) {
		$itens = $itens . ' ' . $vergula . ' ' . $k . ' = ' . "'$v'";
		$vergula = ',';}
	global $conect;
	mysqli_query($conect, "UPDATE $tabela SET $itens WHERE $coluna = '$referencia';");
	//$saida = mysqli_insert_id($conect);
	//mysqli_close($conect);
	return $saida;
}

//Cosulta uma linha no mysql
function buscar_mysql($tabela, $pesquiza = '', $campo = '') {
	global $conect;
	if (!$campo) {
		$campo = $tabela . '_id';
	}

	if ($pesquiza) {

		if ($pesquiza) {
			$pesquizar = "WHERE $campo LIKE '$pesquiza'";
		}

		$query = mysqli_query($conect, "SELECT * FROM $tabela $pesquizar");

		if ($query) {
			while ($myrow = mysqli_fetch_assoc($query)) {

				foreach ($myrow as $k => $v) {

					$linha[$k] = $v;
				}

			}}

		if ($linha) {
			foreach ($linha as $key => $value) {
				if ($value) {
					$saida[$key] = $value;
				}

			}
		}

		//mysqli_close($conect);
		return $saida;
	}
	//mysqli_close($conect);
}

//Consulta Varias linhas
function consultar_mysql($tabela, $pesquiza = array(), $ordenar = '', $orient = '') {
	global $conect;

	if ($pesquiza[sort]) {$ordenar = $pesquiza[sort];unset($pesquiza[sort]);}
	if ($pesquiza[order]) {$orient = $pesquiza[order];unset($pesquiza[order]);}
	if ($pesquiza[filterRules]) {unset($pesquiza[filterRules]);}

	$reparador = explode('|', $tabela);
	if ($reparador[1]) {
		$tabela = $reparador[0];

		$filter_cols = explode(',', $reparador[1]);
	}

	if (!$ordenar and !is_array($tabela)) {

		//$ordenar = $tabela.'_id';
		//$orient = 'DESC';

	} elseif (is_array($tabela)) {

		$ord = explode('.', $tabela[0][0]);
		$ordenar = $ord[0] . '_id';
		$orient = 'DESC';

	}

	if ($pesquiza[rows] || $pesquiza[page]) {
		$_GET[rows] = $_POST[rows] = $pesquiza[rows];
		$_GET[page] = $_POST[page] = $pesquiza[page];
		unset($pesquiza[rows]);
		unset($pesquiza[page]);
	}

	if ($_GET[page]) {
		$_POST[page] = $_GET[page];unset($_GET[page]);
	}

	if ($_GET[rows]) {
		$_POST[rows] = $_GET[rows];unset($_GET[rows]);
	}

	if ($pesquiza[filtro]) {

		$filtro = $pesquiza[filtro];unset($pesquiza[filtro]);

	}

	$x = 'WHERE';

	if ($pesquiza) {
		//Cosulta mysql
		if (is_array($pesquiza)) {
			foreach ($pesquiza as $k => $v) {
				$sep = explode('|', $k);
				$k = $sep[0];

				if ($v) {

					$tipos = "LIKE '%$v%'";
					if ($sep[1]) {
						$tipos = "$sep[1] '$v'";
					}

					$pesquizar .= $x . " $k $tipos ";
					$x = ' AND';

				}
			}
		}

	} else {

		$pesquizar = '';

	}

	//Define Periodo
	if (is_array($periodo)) {
		foreach ($periodo as $k2 => $v2) {
			$periodos = $x . ' ' . $k2 . " BETWEEN '" . $v2[ini] . "' and '" . $v2[fim] . "'";
		}
	}

	//Paginador
	if ($_POST['page'] >= 1) {
		$pagina = ($_POST['page'] - 1) * $_POST['rows'];
	} else { $pagina = 0;}
	if ($_POST[rows]) {$limite = "LIMIT " . $pagina . " ," . $_POST[rows];} //Limite

	//Cria Relacionamentos
	if (is_array($tabela)) {
		foreach ($tabela as $k => $v) {
			$table1 = explode('.', $v[0]);
			$table2 = explode('.', $v[1]);

			$tabela_vinc .= " left join " . $table2[0] . ' on ' . $v[0] . " = " . $v[1];
			$tab[] = $table1[0];
		}

		$tabela = array();
		$tabela = $tab[0];
	}

	//Organizador
	if ($_POST[sort]) {
		$ordenar = $_POST[sort];
		$orient = $_POST[order];
	} else {

		$query = mysqli_query($conect, "SHOW full COLUMNS FROM $tabela");

		if ($query) {
			while ($coluna = mysqli_fetch_assoc($query)) {
				$colunas[] = $coluna["Field"];
				$cols_detalhes[$coluna["Field"]] = $coluna["Comment"];
			}
		}

		if (!$ordenar) {
			$ordenar = $colunas[0];
			$orient = DESC;
		}

	}

	if ($orient) {$orden = "ORDER BY " . $ordenar . " " . $orient;} //Ordenação

	if (!$reparador[1]) {
		$reparador[1] = '*';
	}

	// efetuando um select na tabela
	$select = mysqli_query($conect, "SELECT " . $tabela . "_id FROM " . $tabela . " $tabela_vinc $pesquizar ");
	if ($select) {
		$cont = $conect->affected_rows;
	}

	//Gera Saida
	$query = mysqli_query($conect, "SELECT " . $reparador[1] . " FROM " . $tabela . " $tabela_vinc $pesquizar $periodos $orden $limite");
	if ($query) {
		$cont_total = mysqli_num_rows($query);
	}

	//Gera Colunas Separadas
	if ($query) {
		while ($myrow = mysqli_fetch_assoc($query)) {

			//Converte campo de data
			foreach ($myrow as $key => $value3) {
				if ($cols_detalhes[$key] == 'date') {

					$myrow[$key] = date('d/m/Y', $value3);
				}
			}

			$saida[rows][] = $myrow;

		}
	}

	if ($saida[rows]) {
		$saida[total][0] = $cont;
	}

	if (!$saida[rows]) {
		$saida[rows] = '';
		$saida[total] = 0;
	}

	//mysqli_close($conect);

	return $saida;
}

////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////

//abrelink
function abrir_url($url, $post = '', $cookie = 'cookie.txt', $api_key = '') {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

	curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . $cookie);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($api_key) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, [key($api_key) . ": " . $api_key[key($api_key)], "cache-control: no-cache"]);
	}

	curl_setopt($ch, CURLOPT_URL, $url);

	if ($post) {
		foreach ($post as $key => $value) {
			$postar[] = $key . '=' . $value;
		}

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $postar));
	}

	$saida = curl_exec($ch);
	curl_close($ch);

	return $saida;
}

//Capturador de conteudos
function captura($pagina, $inicio, $fim, $tipo = '') {

	$pag1 = explode($inicio, $pagina);
	unset($pag1[0]);
	foreach ($pag1 as $key => $value) {

		unset($pag2[0]);

		$pag2 = explode($fim, $value);

		if ($pag2[0]) {

			if ($tipo == "string") {
				$saida .= str_replace("\r", '', $pag2[0]);
			} else {
				$saida[] = str_replace("\r", '', $pag2[0]);
			}
		}

	}

	return $saida;
}

//////////Capturador de Files
function captura_files($pagina, $url = '') {
	$files = captura($pagina, 'src="', '"');
	$files = array_merge($files, captura($pagina, 'href="', '"'));
	foreach ($files as $key => $value) {
		if ($url) {
			$file[] = completa_link($value, $url);
		} else {
			$file[] = $value;
		}
	}
	return $file;
}

function removeCaracterEspecial($string) {

	// matriz de entrada
	$what = array('ä', 'ã', 'à', 'á', 'â', 'ê', 'ë', 'è', 'é', 'ï', 'ì', 'í', 'ö', 'õ', 'ò', 'ó', 'ô', 'ü', 'ù', 'ú', 'û', 'À', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ç', 'Ç', ' ', '_', '-', '(', ')', ',', ';', ':', '|', '!', '"', '#', '$', '%', '&', '/', '=', '?', '~', '^', '>', '<', 'ª', 'º');

	// matriz de saída
	$by = array('a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'A', 'A', 'E', 'I', 'O', 'U', 'n', 'n', 'c', 'C', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

	// devolver a string
	return str_replace($what, $by, $string);
}

function array_sort($array, $on, $order = SORT_ASC) {
	$new_array = array();
	$sortable_array = array();

	if (count($array) > 0) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($k2 == $on) {
						$sortable_array[$k] = $v2;
					}
				}
			} else {
				$sortable_array[$k] = $v;
			}
		}

		switch ($order) {
		case SORT_ASC:
			asort($sortable_array);
			break;
		case SORT_DESC:
			arsort($sortable_array);
			break;
		}

		foreach ($sortable_array as $k => $v) {
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

///Download de Lista de files
function downloads($list, $dir = 'downloads') {
	if ($list) {
		foreach ($list as $key => $value) {
			$pathinfo = pathinfo($value);
			$file = @file_get_contents($value);
			if ($file) {
				file_put_contents($dir . '/' . $pathinfo[basename], $file);
				$qnt++;
			}
		}
		return $qnt;
	}
}

//Array Meses
function mes($mes = '') {
	if (!$mes) {$mes = date('n');}

	$saida = array(1 => 'Janeiro',
		2 => 'Fevereiro',
		3 => 'Março',
		4 => 'Abril',
		5 => 'Maio',
		6 => 'Junho',
		7 => 'Julho',
		8 => 'Agosto',
		9 => 'Setembro',
		10 => 'Outubro',
		11 => 'Novembro',
		12 => 'Dezembro',
	);
	return $saida[$mes];
}

function convert_xml($xml) {
	return json_decode(json_encode((array) simplexml_load_string($xml)), 1);
}

function verificar_email($email) {
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return true;
	} else {
		return false;
	}
}

function valida_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validaCPF($cpf = null) {

	// Verifica se um número foi informado
	if (empty($cpf)) {
		return false;
	}

	// Elimina possivel mascara
	$cpf = preg_replace("/[^0-9]/", "", $cpf);
	$cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

	// Verifica se o numero de digitos informados é igual a 11
	if (strlen($cpf) != 11) {
		return false;
	}
	// Verifica se nenhuma das sequências invalidas abaixo
	// foi digitada. Caso afirmativo, retorna falso
	else if ($cpf == '00000000000' ||
		$cpf == '11111111111' ||
		$cpf == '22222222222' ||
		$cpf == '33333333333' ||
		$cpf == '44444444444' ||
		$cpf == '55555555555' ||
		$cpf == '66666666666' ||
		$cpf == '77777777777' ||
		$cpf == '88888888888' ||
		$cpf == '99999999999') {
		return false;
		// Calcula os digitos verificadores para verificar se o
		// CPF é válido
	} else {

		for ($t = 9; $t < 11; $t++) {

			for ($d = 0, $c = 0; $c < $t; $c++) {
				$d += $cpf{$c} * (($t + 1) - $c);
			}
			$d = ((10 * $d) % 11) % 10;
			if ($cpf{$c} != $d) {
				return false;
			}
		}

		return true;
	}
}

function validaCNPJ($cnpj = null) {

	// Verifica se um número foi informado
	if (empty($cnpj)) {
		return false;
	}

	// Elimina possivel mascara
	$cnpj = preg_replace("/[^0-9]/", "", $cnpj);
	$cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

	// Verifica se o numero de digitos informados é igual a 11
	if (strlen($cnpj) != 14) {
		return false;
	}

	// Verifica se nenhuma das sequências invalidas abaixo
	// foi digitada. Caso afirmativo, retorna falso
	else if ($cnpj == '00000000000000' ||
		$cnpj == '11111111111111' ||
		$cnpj == '22222222222222' ||
		$cnpj == '33333333333333' ||
		$cnpj == '44444444444444' ||
		$cnpj == '55555555555555' ||
		$cnpj == '66666666666666' ||
		$cnpj == '77777777777777' ||
		$cnpj == '88888888888888' ||
		$cnpj == '99999999999999') {
		return false;

		// Calcula os digitos verificadores para verificar se o
		// CPF é válido
	} else {

		$j = 5;
		$k = 6;
		$soma1 = "";
		$soma2 = "";

		for ($i = 0; $i < 13; $i++) {

			$j = $j == 1 ? 9 : $j;
			$k = $k == 1 ? 9 : $k;

			$soma2 += ($cnpj{$i} * $k);

			if ($i < 12) {
				$soma1 += ($cnpj{$i} * $j);
			}

			$k--;
			$j--;

		}

		$digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
		$digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

		return (($cnpj{12} == $digito1) and ($cnpj{13} == $digito2));

	}
}

//Converte data mult
function converte_data($data) {
	$explode_data = explode('/', $data);
	if ($explode_data[2]) {
		return mktime(0, 0, 0, $explode_data[1], $explode_data[0], $explode_data[2]);
	}

	$explode_data2 = explode('-', $data);
	if ($explode_data2[2]) {
		return $explode_data2[2] . '/' . $explode_data2[1] . '/' . $explode_data2[0];
	}

	if ($explode_data[0]) {return date("d/m/Y", $explode_data[0]);}
	;
}

//Gerador moedas
function moeda($valor = '') {
	$saida = 'R$ ' . number_format(0, 2, ',', '.');
	if ($valor) {$saida = 'R$ ' . number_format($valor, 2, ',', '.');}
	;
	return $saida;
}

function numero($valor = '') {
	$saida = number_format($valor, 2, '.', '');
	return $saida;
}

function real_numero($valor) {
	return str_replace(',', '.', str_replace('r$', '', str_replace('R$', '', $valor)));
}

function completa_zero($input, $qnt = '8') {
	return str_pad($input, $qnt, 0, STR_PAD_LEFT);
}

function gerar_codigo($qnt = 7) {
	$upper = implode('', range('A', 'F')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
	//$upper .= implode('', range('J', 'N')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
	//$upper .= implode('', range('P', 'Z')); // ABCDEFGHIJKLMNOPQRSTUVWXYZ
	$nums = implode('', range(1, 9)); // 0123456789

	$alphaNumeric = $upper . $nums; // ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789
	$string = '';
	$len = $qnt; // numero de chars
	for ($i = 0; $i < $len; $i++) {
		$string .= $alphaNumeric[rand(0, strlen($alphaNumeric) - 1)];
	}

	return $string;
}

function ping($host, $port, $timeout) {
	$tB = microtime(true);
	$fP = @fSockOpen($host, $port, $errno, $errstr, $timeout);
	if (!$fP) {return "down";}
	$tA = microtime(true);
	return round((($tA - $tB) * 1000), 0);
}

function data_hora($h = 0, $i = 0, $s = 0, $d = 0, $m = 0, $a = 0) {

	if (!$h) {
		$h = date('H');
	}

	if (!$i) {
		$i = date('i');
	}

	if (!$s) {
		$s = date('s');
	}

	if (!$d) {
		$d = date('d');
	}

	if (!$m) {
		$m = date('m');
	}

	if (!$a) {
		$a = date('Y');
	}

	$saida = date('d/m/Y H:i:s', mktime($h, $i, $s, $m, $d, $a));
	return $saida;
}

function data($d = '', $m = '', $a = '') {

	if (!$d) {
		$d = date('d');
	}

	if (!$m) {
		$m = date('m');
	}

	if (!$a) {
		$a = date('Y');
	}

	$saida = date('Y-m-d', mktime(0, 0, 0, $m, $d, $a));
	return $saida;
}

function add_data_hora($h = 0, $i = 0, $s = 0, $d = 0, $m = 0, $a = 0) {

	$h = date('H') + $h;
	$i = date('i') + $i;
	$s = date('s') + $s;
	$d = date('d') + $d;
	$m = date('m') + $m;
	$a = date('Y') + $a;

	$saida = data_hora($h, $i, $s, $d, $m, $a);
	return $saida;
}

function add_data($d = '', $m = '', $a = '') {

	$d = date('d') + $d;
	$m = date('m') + $m;
	$a = date('Y') + $a;

	$saida = data($d, $m, $a);
	return $saida;
}

function add_mktime($h = 0, $i = 0, $s = 0, $d = 0, $m = 0, $a = 0) {

	$h = date('H') + $h;
	$i = date('i') + $i;
	$s = date('s') + $s;
	$d = date('d') + $d;
	$m = date('m') + $m;
	$a = date('Y') + $a;

	$saida = mktime($h, $i, $s, $m, $d, $a);
	return $saida;
}

function send_email($nome, $destino, $titulo, $corpo, $anexo = '') {

	require '/var/www/html/includes/PHPMailer/class.phpmailer.php';

	$mail = new PHPMailer();
	$mail->IsSMTP(true); // Define que a mensagem será SMTP
	$mail->Host = "smtp.gmail.com"; // Endereço do servidor SMTP
	$mail->Port = 465;
	$mail->SMTPAuth = true; // Usa autenticação SMTP? (opcional)
	$mail->SMTPSecure = 'ssl';
	$mail->Username = GMAIL; // Usuário do servidor SMTP
	$mail->Password = SENHA; // Senha do servidor SMTP
	$mail->From = GMAIL; // Seu e-mail
	$mail->FromName = $nome; // Seu nome
	$mail->AddAddress($destino, '');
	$mail->IsHTML(true); // Define que o e-mail será enviado como HTML
	$mail->Subject = $titulo; // Assunto da mensagem
	$mail->Body = $corpo;
	if ($anexo) {
		if (is_array($anexo)) {
			foreach ($anexo as $key => $value) {
				$mail->AddAttachment($value, basename($value));
			}
			$salvar[anexo] = implode(',', $anexo);
		} else {
			$mail->AddAttachment($anexo, basename($anexo));
			$salvar[anexo] = $anexo;
		}
	}
	$enviado = $mail->Send();
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();

	$salvar[destino] = $destino;
	$salvar[titulo] = $titulo;
	$salvar[corpo] = $corpo;
	$salvar[data] = date('d/m/Y Y:i:s');

	if ($enviado) {
		$salvar[status] = 'Enviado';
		salvar_mysql(send_mail, $salvar);
		return true;
	} else {
		$salvar[status] = 'Erro ao enviar';
		salvar_mysql(send_mail, $salvar);
		return false;
	}

}

function die_json($dados) {
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	die(json_encode($dados));
}

function die_ajax($dados) {
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	die($dados);
}

//////////////////////////////////////// NOVAS FUNCOES
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////

//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////
//////////////////////////

function zip_file($file = '', $ext = '') {
	if (!$dir) {
		$file = realpath(__FILE__);
	}

	if (!$ext) {
		$file . '.zip';
	}

	$zip = new ZipArchive();
	$zip->open($ext, ZIPARCHIVE::CREATE);
	$zip->addFile($file, $file);
	$zip->close();
}

function zip_dir($dir = '', $file = '') {
	if (!$dir) {
		$dir = __DIR__;
	}

	if (!$file) {
		$file = 'arquivo.zip';
	}

	$path = realpath(__DIR__);
	$zip = new ZipArchive();
	$zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach ($files as $name => $file) {
		if ($file->isDir()) {
			flush();
			continue;
		}
		$filePath = $file->getRealPath();
		$relativePath = substr($filePath, strlen($path) + 1);
		$zip->addFile($filePath, $relativePath);
	}
	$zip->close();
	return true;
}

function upload($pasta = '') {
	if (!$pasta) {
		$pasta = 'uploads';
	}

	if (!is_dir($pasta)) {
		mkdir($pasta);
	}

	foreach ($_FILES as $key => $value) {

		if ($value[name]) {
			$name_upload = $pasta . "/" . mktime() . rand(100, 999) . '_' . $value[name];
			move_uploaded_file($value[tmp_name], $name_upload);
			$saida[$key] = $value[name];
			$saida[$key . '_name_upload'] = $name_upload;
		}
	}
	return $saida;
}

//Busca Dados
if ($_GET[buscar_mysql]) {
	$_SERVER[mysql] = buscar_mysql($_GET[buscar_mysql], $_GET[id], $_GET[buscar_mysql] . '_id');
}
//Apaga Item
if ($_GET[excluir]) {
	exclui_mysql($_GET[excluir], $_GET[id], $_GET[col]);
	die_json(array(success => 1));
}
///Salvamento de HTML
if ($_POST[salvar] or $_GET[salvar]) {

	if ($_GET[salvar]) {
		$_POST = $_GET;
	}

	$tabela = $_POST[salvar];unset($_POST[salvar]);
	$redirect = $_POST[redirect];unset($_POST[redirect]);
	$ajax = $_POST[ajax];unset($_POST[ajax]);
	$pasta = $_POST[upload];unset($_POST[upload]);

	//Upload de files
	if ($_FILES) {
		if (!$pasta) {
			$pasta = 'uploads';
		}

		if (!is_dir($pasta)) {
			mkdir($pasta);
		}

		foreach ($_FILES as $key => $value) {

			if ($value[name]) {
				$name_upload = $pasta . "/" . mktime() . rand(100, 999) . '_' . $value[name];
				move_uploaded_file($value[tmp_name], $name_upload);
				$_POST[$key] = $value[name];
				$_POST[$key . '_name_upload'] = $name_upload;
			}

		}
	}

	//Salva Select
	foreach ($_POST as $key => $value) {
		if (is_array($value)) {

			if ($value[key($value)]) {
				$item = buscar_mysql($key, $value[key($value)], $key . '_id');
				if (!$item) {
					$salvar = salvar_mysql($key, $value);
					unset($_POST[$key]);
					$_POST[$key] = $salvar[id];
				} else {
					$_POST[$key] = $item[$key . '_id'];
				}
			} else {
				$_POST[$key] = '';
			}
		}
	}
	;

	//Salva Conteudo
	$id_mysql = $salvar_mysql = salvar_mysql($tabela, $_POST, $_POST[$tabela . "_id"], $tabela . "_id");

	if ($redirect) {
		header('Location: ' . $redirect);
		die;
	}

	$saida[success] = 1;
	$saida[returns] = $salvar_mysql;

	//echo json_encode($saida);
	//die;

}

//////Rega Json
if ($_GET[json]) {

	$tabela = $_GET[json];
	unset($_GET[json]);
	$direc = $_GET[direc];
	unset($_GET[direc]);

	if ($_GET[ordem]) {
		$ordem = $_GET[ordem];
		unset($_GET[ordem]);
	}

	$callback = $_GET['callback'];unset($_GET['callback']);unset($_GET['_']);

	$mesclar = explode('/', $tabela);
	if ($mesclar[1]) {
		unset($tabela);
		foreach ($mesclar as $key => $value) {
			$tabela[] = explode('>', $value);
		}

	}

	if (!$filtro) {
		$dados = consultar_mysql($tabela, $_GET, $ordem, $direc);
	} else {
		$dados = consultar_mysqli_filtro($tabela, $_GET, $ordem, $direc);
	}

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	if ($callback != '') {
		die($callback . '(' . json_encode($dados) . ')');
	} else {
		die(json_encode($dados));
	}

}

if ($_GET[del]) {

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	$dados = buscar_mysql($_GET[buscar], $_GET[id]);
	die(json_encode($dados));

}

if ($_GET[buscar]) {

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	$dados = buscar_mysql($_GET[buscar], $_GET[id]);
	die(json_encode($dados));

}

if ($_GET[limpar_tabela]) {

	mysqli_query(conectar_mysql(), 'DROP TABLE `' . $_GET[limpar_tabela] . '`;');
	echo '<script>javascript:history.back()</script>';
	die;
}

if ($_GET[delete_col]) {
	$del_col = explode('|', $_GET[delete_col]);

	mysqli_query('ALTER TABLE `' . $del_col[0] . '` DROP `' . $del_col[1] . '`;');
}

if ($_GET[forms_mysql]) {
	die(json_encode(form_mysql($_GET[forms_mysql])));
}

if ($_GET[excel]) {

	$dados_excel = consultar_mysql($_GET[excel]);
	foreach ($dados_excel[rows] as $key => $value) {
		$linhas .= implode(';', $value) . "\n";
		$titulo = array_keys($value);
	}

	header('Cache-control: private');
	header('Content-Type: application/octet-stream');
	header('Content-Length: ' . filesize($local_file));
	header('Content-Disposition: filename=' . $_GET[excel] . '.csv');
	echo implode(';', $titulo) . "\n" . $linhas;
	die;
}

if ($_GET[backup]) {

	if ($_GET[email]) {

		$filename = "backup_" . DB_NAME . '_' . date('dmYHi') . '.sql.gz';
		$cmd = "mysqldump -h " . DB_HOST . " -u " . DB_LOGIN . " --password=" . DB_SENHA . " " . DB_NAME . " | gzip > " . $filename;
		exec($cmd);
		send_email('InfoTrucks', $_GET[email], 'Backup ' . date('d/m/Y H:i'), 'Backup em Anexo', '' . $filename);
		unlink($filename);
		echo 'Enviado com Successo';
		die;

	} else {

		$filename = "backup_" . DB_NAME . '_' . date('dmYHi') . '.sql.gz';
		$cmd = "mysqldump -h " . DB_HOST . " -u " . DB_LOGIN . " --password=" . DB_SENHA . " " . DB_NAME . " | gzip > backups/" . $filename;
		exec($cmd);

	}

	header('Content-Type: application/download');
	header('Content-Disposition: attachment; filename="' . $filename . '.zip' . '"');

	$salvar[data_hora] = date('d/m/Y H:i:s');
	$salvar[file] = "backups/" . $filename;
	$salvar[filesize] = number_format((filesize("backups/" . $filename . '') / 1000000), 3, '.', '.') . ' MB';
	$salvar[login] = $_SESSION[login];

	salvar_mysql(backups, $salvar);

	readfile("backups/" . $filename);
	die;
}

if ($_GET[phpinfo]) {

	echo phpinfo();
	die;
}

//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////

function includeString($file) {
	ob_start();
	@include $file;
	$string = ob_get_contents();
	ob_end_clean();
	return $string;
}

function sub_caracter($string, $car, $sub = '') {
	return str_replace($car, $sub, $string);
}

function templater($content) {

	//Captura Strings Server
	$dados = captura($content, '<{$', '}>');
	foreach ($dados as $key => $value) {
		$content = sub_caracter($content, '<{$' . $value . '}>', $_SERVER[$value]);
	}

	return $content;
}

function array_tags($array) {
	foreach ($array as $key => $value) {
		$saida[] = $key . '="' . $value . '"';
	}
	return implode(' ', $saida);
}

function array_json($array) {
	foreach ($array as $key => $value) {
		$saida[] = $key . ":'" . $value . "'";
	}
	return implode(',', $saida);
}

function auto_form($tabela, $campos = '', $tags = '') {

	$fiels = form_mysql($tabela);

	if ($campos) {
		//Ordenar e Capturas campos
		$list = explode(',', $campos);
		foreach ($list as $key1 => $value1) {
			foreach ($fiels as $key => $value) {
				if ($value1 == $value[field]) {
					$campos_est[] = $value;
					unset($list[$key1]);
				}
			}
		}
		if ($list) {
			foreach ($list as $key => $value) {
				$campos_est[] = array(type => '', field => $value, title => $value);
			}
		}
	} else {
		$campos_est = $fiels;
	}

	foreach ($campos_est as $key => $value) {

		if ($value[type] != 'none' && $value[type] != 'hidden') {
			$input = '<input ' . $tags[input] . ' ' . $tags[all_campos] . ' id="' . $value[field] . '" type="' . $value[type] . '" name="' . $value[field] . '">';

			if ($value[type] == 'select') {
				unset($options);
				foreach ($value[options] as $key1 => $value1) {
					$vals = explode(':', $value1);
					$options .= '<option ' . $value[option] . ' value="' . $vals[0] . '">' . $vals[1] . '</option>';
				}
				$input = '<select name="' . $value[field] . '" ' . $tags[select] . ' ' . $tags[all_campos] . '>' . $options . '</select>';
			}

			if ($value[type] == 'textarea') {
				$input = '<textarea ' . $value[textarea] . ' ' . $tags[textarea] . ' ' . $tags[all_campos] . '></textarea>';
			}

			$saida .= '<div ' . $tags[div] . '><label ' . $tags[label] . '>' . $value[title] . ' :</label>' . $input . '</div>';
		} elseif ($value[type] == 'hidden') {
			$hiddens .= '<input id="' . $value[field] . '" type="hidden" name="' . $value[field] . '" ></label></div>';
		}
	}

	return $hiddens . $saida;
	//'';
}

function form_mysql($tabela) {
	$cols = lista_colunas($tabela);

	foreach ($cols as $key => $value) {
		$detalhes = explode('|', $value[Comment]);
		$tipo = explode('/', $detalhes[1]);
		$opcoes = explode(',', $tipo[1]);

		if (!$detalhes[2]) {
			$detalhes[2] = $value[Field];
		}

		if ($tabela . '_id' == $value[Field]) {
			$tipo[0] = 'hidden';
		}

		if ($tabela . '_cod' == $value[Field]) {
			$tipo[0] = 'hidden';
		}

		if ('mktime' == $value[Field]) {
			$tipo[0] = 'hidden';
		}

		$saida[$key][field] = $value[Field];
		$saida[$key][title] = $detalhes[2];
		$saida[$key][type] = $tipo[0];
		if ($opcoes[0]) {
			$saida[$key][options] = $opcoes;
		}

	}
	return $saida;
}

function tirarAcentos($string) {
	return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
}

function json($table, $buscar = '') {

	$dados = consultar_mysql($table, $buscar);
	foreach ($dados[rows] as $key => $value) {
		foreach ($value as $key2 => $value2) {
			if (in_array($key2, listar_tabelas()) and $value2) {
				$dados_seg = json($key2, array(codigo => $value2));

				foreach ($dados_seg[rows] as $key3 => $value3) {
					foreach ($value3 as $key4 => $value4) {
						$dados[rows][$key][$key2 . '_' . $key4] = $value4;
					}
				}
			}
		}
	}

	return $dados;

}

function sigra_tempo($sigla) {

	if ($sigla == 'a') {
		$saida = "Ano(s)";
	}

	if ($sigla == 'm') {
		$saida = "Mês(es)";
	}

	if ($sigla == 'd') {
		$saida = "Dia(s)";
	}

	if ($sigla == 'h') {
		$saida = "Hora(s)";
	}

	if ($sigla == 'i') {
		$saida = "Minuto(s)";
	}

	if ($sigla == 's') {
		$saida = "Segundos(s)";
	}

	if (strtolower($sigla) == 'ano') {
		$saida = "a";
	}

	if (strtolower($sigla) == 'mês') {
		$saida = "m";
	}

	if (strtolower($sigla) == 'dia') {
		$saida = "d";
	}

	if (strtolower($sigla) == 'hora') {
		$saida = "h";
	}

	if (strtolower($sigla) == 'minuto') {
		$saida = "i";
	}

	if (strtolower($sigla) == 'segundos') {
		$saida = "s";
	}

	return $saida;

}

function pastas($dir = '.') {

	if (!$GLOBALS[files_out]) {
		$GLOBALS[files_out] = json_decode(file_get_contents('list.json'), 1);
	}

	$list = scandir($dir);
	$files;
	$x = 1;
	foreach ($list as $key => $value) {
		if (is_dir($dir . '/' . $value)) {

			if ($value != '.' && $value != '..') {
				//echo $value."\n";
				pastas($dir . '/' . $value);
			} else {

			}

		} else {

			$file = $dir . '/' . $value;

			$base = utf8_encode(realpath($file));
			$id_file = md5(utf8_encode(realpath($file)) . filesize($file) . round(filemtime($file) / (60 * 15)));

			if (!$GLOBALS[files_out][$base][$id_file]) {
				$x++;
				$sha256 = hash_file("sha256", realpath($file));
				$GLOBALS['saida'][] = realpath($file);
			}

			$GLOBALS['files'][$base][$id_file] = basename($file);

		}
	}

	return $GLOBALS['files'];
}

function verifica_arquivos($pasta = '.') {
	set_time_limit(0);
	$arquivos = pastas($pasta);
	file_put_contents('list.json', json_encode($arquivos));
	return $GLOBALS['saida'];
}

function altenticacao($file = '/login', $redirect = '') {
	if (!$_SESSION[users_id]) {
		if ($file != '') {
			header('Location: ' . $file);
			die;
		} else {
			return false;
		}
	} else {
		if ($redirect) {
			include_once $redirect;
			die;
		} else {
			return true;
		}
	}
}

function data_mktime($data) {
	$tempo_mk = explode(' ', $data);
	$data_mk = explode('/', $tempo_mk[0]);
	$hora_mk = explode(':', $tempo_mk[1]);
	return mktime($hora_mk[0], $hora_mk[1], $hora_mk[2], $data_mk[1], $data_mk[0], $data_mk[2]);
}

function contador($nun = 10) {
	$_SERVER[countx]++;

	if (!$_SERVER[county]) {
		$_SERVER[county] = 0;
	}

	if (!$_SERVER[countx]) {
		$_SERVER[countx] = 0;
	}

	if ($_SERVER[countx] >= $nun) {
		$_SERVER[countx] = 0;
		$_SERVER[county]++;
	}
	return $_SERVER[county];
}

/////////// Funcoes de Avorizar
function treeze(&$a, $parent_key = 'parent', $children_key = 'children') {

	/* Modelo de Array
		    $ARRAY = array(
		      'a' => array( 'label' => "A" ),
		      'b' => array( 'label' => "B" ),
		      'c' => array( 'label' => "C" ),
		      'd' => array( 'label' => "D" ),
		      5 => array( 'label' => "one", 'parent' => 'a' ),
		      6 => array( 'label' => "two", 'parent' => 'a' ),
		      7 => array( 'label' => "three", 'parent' => 'a' ),
		      8 => array( 'label' => "node 1", 'parent' => 'a' ),
		      9 => array( 'label' => "node 2", 'parent' => '2' ),
		      10 => array( 'label' => "node 3", 'parent' => '2' ),
		      11 => array( 'label' => "I", 'parent' => '9' ),
		      12 => array( 'label' => "II", 'parent' => '9' ),
		      13 => array( 'label' => "III", 'parent' => '9' ),
		      14 => array( 'label' => "IV", 'parent' => '9' ),
		      15 => array( 'label' => "V", 'parent' => '9' ),
		    );
	*/

	$orphans = true;
	$i;
	while ($orphans) {
		$orphans = false;
		foreach ($a as $k => $v) {
			// is there $a[$k] sons?
			$sons = false;
			foreach ($a as $x => $y) {
				if (isset($y[$parent_key]) and $y[$parent_key] != false and $y[$parent_key] == $k) {
					$sons = true;
					$orphans = true;
					break;
				}
			}

			// $a[$k] is a son, without children, so i can move it
			if (!$sons and isset($v[$parent_key]) and $v[$parent_key] != false) {
				$a[$v[$parent_key]][$children_key][$k] = $v;
				unset($a[$k]);
			}
		}
	}
}

function filtroinputs($tags) {
	foreach ($tags as $key => $value) {
		if (in_array($key, array('accept', 'align', 'alt', 'autocomplete', 'autofocus', 'checked', 'disabled', 'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'height', 'list', 'max', 'maxlength', 'min', 'multiple', 'name', 'pattern', 'placeholder', 'readonly', 'required', 'size', 'src', 'rows', 'step', 'type', 'value', 'width'))) {
			$attrib[$key] = $value;
		}
	}
	return $attrib;
}

function forms($tags = '', $template = '', $objs = '') {
	if (is_array(current($tags))) {
		foreach ($tags as $key => $value) {
			if (in_array($objs, $tags[$key][views]) or !$objs) {
				$saidaarray .= forms($value, $template, $grupos);
			}
		}
		return $saidaarray;
	}
	;
	if (!$tags[placeholder]) {$tags[placeholder] = $tags[title];}
	if (!$tags[type]) {
		$tags[type] = 'text';
	}
	if (!$tags[value]) {
		$tags[value] = $_SERVER[mysql][$tags[name]];
	}
	switch ($tags[type]) {

	case in_array($tags[type], array('button', 'checkbox', 'color', 'date', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month', 'number', 'password', 'range', 'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week')):
		$saida = '<input ' . array_tags(filtroinputs($tags)) . ' >';
		break;

	case 'radio':
		foreach ($tags[options] as $key => $value) {
			$option[] = '<input ' . array_tags(filtroinputs($tags)) . ' value="' . $key . '" >' . $value;
		}

		$saida = implode('<br>', $option);
		break;

	case 'select':
		if ($tags[options]) {

			foreach ($tags[options] as $key => $value) {
				unset($select);
				if ($tags[value] == $key and $key) {
					$select = 'selected';
				}
				$option .= '<option value="' . $key . '" ' . $select . ' >' . $value . '</option>';
			}
		}
		$saida = '<select ' . array_tags(filtroinputs($tags)) . '>' . $option . '</select>';
		break;

	case 'textarea':
		unset($tags[textarea]);
		$saida = '<textarea ' . array_tags(filtroinputs($tags)) . ' >' . $tags[value] . '</textarea>';
		break;

	}

	$saida = sub_caracter($template, '{content}', $saida);
	$itens = captura($saida, '{', '}');
	foreach ($itens as $key => $value) {
		$saida = sub_caracter($saida, '{' . $value . '}', $tags[$value]);
	}

	return $saida;
}

function recebeJson() {
	$json = file_get_contents('php://input');
	$resultado = json_decode($json, 1);
	return $resultado;
}

function casos($val) {
	switch ($val) {
	case 'a':
		return 'Em Aberto';
	case 'g':
		return 'Ganhou';
	case 'p':
		return 'Perdeu';
	case 'c':
		return 'Cancelou';
	case 's':
		return 'Sim';
	case 'e':
		return 'Pré Bilhete';
	default:
		return "Não";
	}
}

function consultar($tabela, $where = '', $ordCol = '', $ordDir = '', $cols = '*') {
	global $conect;

	if (is_array($tabela)) {
		$tables = $tabela;
		$tab1 = explode('.', $tabela[0][0]);
		$tabela = $tab1[0];
	}

	////////////////////////////////////////////////
	//Limites Tabela
	////////////////////////////////////////////////
	if ($_REQUEST['page'] >= 1) {
		$limit = "LIMIT " . (($_REQUEST['page'] - 1) * $_POST['rows']) . ' ,' . $_REQUEST[rows];
	}

	////////////////////////////////////////////////
	//Justa tabelas
	////////////////////////////////////////////////
	if ($tables) {
		foreach ($tables as $key => $value) {
			$tab2 = explode('.', $value[1])[0];
			$join .= "LEFT JOIN $tab2 ON $value[0] = $value[1] ";
		}
	}
	//$join = impode('AND ',$joins);

	////////////////////////////////////////////////
	//Sistema de Ordenacao
	////////////////////////////////////////////////
	if ($_REQUEST[sort]) {
		$ordCol = $_REQUEST[sort];
		$ordDir = $_REQUEST[order];
	}
	if ($ordCol) {
		if (!$ordDir) {
			$ordDir = 'DESC';
		}

		$order = "ORDER BY $ordCol $ordDir";
	}

	////////////////////////////////////////////////
	//Sistema de Busca
	////////////////////////////////////////////////
	if ($where) {
		foreach ($where as $key => $value) {
			$sinal = explode('|', $key);
			if ($sinal[1]) {
				$value = $sinal[1] . " '$value'";
			} else {
				$value = " LIKE '%$value%'";
			}
			$wheres[] = $sinal[0] . " " . $value;
		}
		$where = "WHERE " . implode('AND ', $wheres);
	}

	////////////////////////////////////////////////
	//Compilacao Final
	////////////////////////////////////////////////
	$query_qnt = mysqli_query($conect, "SELECT $cols FROM $tabela $join $where");
	if ($query_qnt) {
		$cont = $conect->affected_rows;
	}

	$query = mysqli_query($conect, "SELECT $cols FROM $tabela $join $where $order $limit");
	while ($row = mysqli_fetch_assoc($query)) {
		$saida[rows][] = $row;
	}

	$saida[total][0] = $cont;
	if (!$saida[rows]) {
		$saida[total][0] = $cont;
		$saida[rows] = array();
	}
	return $saida;
}

function dataHoraMktime($data) {
	$data = explode(' ', $data);
	$datas = explode('-', $data[0]);
	$horas = explode(':', $data[1]);

	return mktime($horas[0], $horas[1], $horas[2], $datas[1], $datas[2], $datas[0]);

}

///////////////////API bet 365
function curlbet365($url) {

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	if ($data === false) {
		$info = curl_getinfo($ch);
		curl_close($ch);
		die('error occured during curl exec. Additioanl info: ' . var_export($info));
	}
	curl_close($ch);
	return json_decode($data, 1);
}

///////////////////API bet 365
function curlNew($url) {

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	if ($data === false) {
		$info = curl_getinfo($ch);
		curl_close($ch);
		die('error occured during curl exec. Additioanl info: ' . var_export($info));
	}
	curl_close($ch);
	return json_decode($data, 1);
}

function codificador($sring, $tamanho = 5) {
	return substr(md5($sring), 0, $tamanho);
}

////////////////////////////////////////////////
//Compilacao Final
////////////////////////////////////////////////

function inputs($value) {

	foreach ($value as $key1 => $value1) {
		if ($key1 == 'class') {
			$class = $value;
		} elseif ($key1 == 'cols') {
			$cols = 'col-xs-' . $value1;
		} else {
			$parans .= $key1 . '="' . $value1 . '" ';
		}
	}

	if ($value[type] == 'file') {
		$input = '<input name="' . $value[field] . '" ' . $parans . ' multiple="" type="file" class="id-input-file" /></select>';

	} elseif ($value[type] == 'select') {
		unset($options);
		foreach ($value[options] as $key1 => $value1) {
			$options .= '<option value="' . $key1 . '">' . $value1 . '</option>';
		}
		$input = '<select name="' . $value[field] . '" ' . $parans . ' class="form-control ' . $class . '">' . $options . '</select>';

	} else {
		$input = '<input class="form-control" ' . $parans . '  name="' . $value[field] . '" value="' . $value[value] . '">';
	}
	;

	$input = '<div class="form-group ' . $cols . '"><label >' . $value[title] . ':</label>' . $input . '</div>';

	return $input;
}

/////////////////////////////////////////////////////////

function apiload($url) {
	return json_decode(abrir_url($url), 1);
}

function monta_array($exchange, $api, $mercado = '', $ask = 'ask', $bid = 'bid', $spot = '') {

	if (!$mercado) {
		return $api;
	}

	foreach ($api as $key => $value) {

		$mercaCoin1 = explode('-', $value[$mercado]);
		$mercaCoin2 = explode('_', $value[$mercado]);

		if ($mercaCoin1[1]) {
			$coin1 = $mercaCoin1[0];
			$coin2 = $mercaCoin1[1];
			$conversao = $mercaCoin1[1] . ' > ' . $mercaCoin1[0];
		} elseif ($mercaCoin2[1]) {
			$coin1 = $mercaCoin2[0];
			$coin2 = $mercaCoin2[1];
			$conversao = $mercaCoin2[1] . ' > ' . $mercaCoin2[0];
		} else {
			$conversao = substr($value[$mercado], -3, 3);
			if ($conversao = 'sdt') {
				$conversao = 'USDT';
			}
			$coin1 = substr($value[$mercado], 0, strlen($conversao));
			$coin2 = $conversao;
			$conversao = $conversao . ' > ' . substr($value[$mercado], 0, strlen($conversao));
		}
		$conversao = strtoupper($conversao);

		$_SERVER[coins][] = $saida[] = array(
			exchange => $exchange,
			spot => str_replace("{COIN1}", strtoupper($coin1), str_replace("{COIN2}", strtoupper($coin2), str_replace("{coin1}", strtolower($coin1), str_replace("{coin2}", strtolower($coin2), $spot)))),
			mercado => $value[$mercado],
			conversao => $conversao,
			coin1 => $coin1,
			coin2 => $coin2,
			ref => strtoupper(removeCaracterEspecial($value[$mercado])),
			ref_mercado => $exchange . '_' . strtoupper(removeCaracterEspecial($value[$mercado])),
			ask => number_format($value[$ask], 10, '.', ''),
			bid => number_format($value[$bid], 10, '.', ''),
		);
	}
	return $saida;
}

function monta_array_key($exchange, $api, $mercado = '', $ask = 'ask', $bid = 'bid', $spot = '') {
	foreach ($api as $key => $value) {
		$lista[] = array(symbol => $key, ask => $value[$ask], bid => $value[$bid]);
	}
	return monta_array($exchange, $lista, $mercado, 'ask', 'bid', $spot);
}

function agrupar_coins($cols = 'ref') {
	foreach ($_SERVER[coins] as $key => $value) {
		$saida[$value[$cols]][] = $value;
	}
	return $saida;
}

//agrupar_coins();
function arbitragem() {
	foreach (agrupar_coins() as $key => $value) {
		foreach ($value as $key1 => $value1) {
			foreach ($value as $key2 => $value2) {

				$diferenca = number_format(($value2[bid] - $value1[ask]), 10, '.', '');
				$percentual = number_format(100 - ($value1[ask] * 100) / $value2[bid], 2, '.', '');

				//echo $value2[ref_mercado] . '     =   ' . $value2[bid] . '    >>    ' . $value1[ref_mercado] . '    =   ' . $value1[ask] . '    |   ' . $percentual . "\n";

				if (
					$percentual > $_SERVER[percentualMinimo] and
					$value2[bid] != 0.00000000 and
					$value1[ask] != 0.00000000
				) {

					if ($percentual <= $_SERVER[percentualMaximo]) {
						$saida[] = array(
							mercado => $key,
							conversao => $value1[conversao],
							coin1 => $value1[coin1],
							coin2 => $value1[coin2],
							preco_compra => $value1[ask],
							preco_venda => $value2[bid],
							diferenca => $diferenca,
							percentual => $percentual,
							url_comprar => $value1[spot],
							url_vender => $value2[spot],
							exchage_compra => $value1[exchange],
							exchage_venda => $value2[exchange],

						);
					}
				}
			}

		}
	}
	$sainda = array_sort($saida, 'percentual', SORT_DESC);
	return $sainda;
}