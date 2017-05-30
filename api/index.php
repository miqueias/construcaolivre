<?php
date_default_timezone_set('America/Sao_Paulo');
header("Content-Type: application/json");
require '../lib/vendor/autoload.php';
require '../lib/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;

define('SECRET_KEY','construcaolivre');
define('ALGORITHM','HS256');

$app = new \Slim\App(array('templates.path' => 'templates', 'settings' => ['displayErrorDetails' => true]));
//$app = new \Slim\App(array('templates.path' => 'templates'));

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->get('/', function(Request $request, Response $response, $args) {
	return $response->withJson(['status' => 200, 'message' => "Api Manager Construção Livre"]);
});

$app->post('/login', function(Request $request, Response $response, $args) {
	$data = $request->getParsedBody();
	$arr = login($data[apt],$data[cod_condominio]);
	return $response->withJson($arr, $arr[status]);
});

$app->get('/items', function(Request $request, Response $response, $args) {
	$auth = auth($request);
	if($auth[status] != 200){
		return $response->withJson($auth, $auth[status]);
	}
	$data = $request->getParsedBody();
	$idCidade = $data["id_cidade"];
	$res = getItemsByCidade($idCidade);
	return $response->withJson($res, $res[status]);
});

$app->post('/cadastro', function(Request $request, Response $response, $args) {
	
	$data = $request->getParsedBody();

	$sql = "SELECT * 
			FROM usuario 
			WHERE cpf = '".$cpf."'";

	$stmt = getConn()->query($sql);
	$result = $stmt->fetchAll(PDO::FETCH_OBJ);

	if (count($result) > 0) {
		$res = array('status' => 500, 'message' => 'CPF já cadastrado!');
		return $response->withJson($res, $res[status]);
		die;
	}

	
	$stmt = getConn()->prepare("INSERT INTO usuario (nome, cpf, senha, telefone, ativo, email, id_bairro) 							VALUES (:nome, :cpf, :senha, :telefone, 'A', :email, :id_bairro)");
	$stmt->bindParam(':nome', $nome);
	$stmt->bindParam(':cpf', $cpf);
	$stmt->bindParam(':senha', $senha);
	$stmt->bindParam(':telefone', $telefone);
	$stmt->bindParam(':ativo', "A");
	$stmt->bindParam(':email', $email);
	$stmt->bindParam(':id_bairro', $id_bairro, PDO::PARAM_INT);

	// insert one row
	$nome = filter_var($data['nome'], FILTER_SANITIZE_STRING);
	$cpf = filter_var($data['cpf'], FILTER_SANITIZE_STRING);
	$senha = filter_var($data['senha'], FILTER_SANITIZE_STRING);
	$telefone = filter_var($data['telefone'], FILTER_SANITIZE_STRING);
	$ativo = filter_var($data['ativo'], FILTER_SANITIZE_STRING);
	$email = filter_var($data['email'], FILTER_SANITIZE_STRING);
	$id_bairro = filter_var($data['id_bairro'], FILTER_SANITIZE_NUMBER_INT);
	
	if($stmt->execute()){
		$res = array('status' => 200, 'message' => "Success");
		return $response->withJson($res, $res[status]);
	} else {
		$res = array('status' => 500, 'message' => 'Erro o cadastrar novo usuário');
		return $response->withJson($res, $res[status]);
	}
});

$app->post('/item/novo', function(Request $request, Response $response, $args) {
	$auth = auth($request);
	if($auth[status] != 200){
		return $response->withJson($auth, $auth[status]);
	}
	$idUser = $auth[token]->data->id_usuario;
	$data = $request->getParsedBody();
	
	$stmt = getConn()->prepare("INSERT INTO item (nome, descricao, id_categoria, id_bairro, foto_1, foto_2, foto_3, foto_4, foto_5, ativo, data_criacao, id_usuario)
	 							VALUES (:nome, :descricao, :id_categoria, :id_bairro, :foto_1, :foto_2, :foto_3, :foto_4, :foto_5, 'S', NOW(), :id_usuario)");

	$stmt->bindParam(':nome', $nome);
	$stmt->bindParam(':descricao', $descricao);
	$stmt->bindParam(':id_categoria', $id_categoria);
	$stmt->bindParam(':id_bairro', $id_bairro);
	$stmt->bindParam(':foto_1', $foto_1);
	$stmt->bindParam(':foto_2', $foto_2);
	$stmt->bindParam(':foto_3', $foto_3);
	$stmt->bindParam(':foto_4', $foto_4);
	$stmt->bindParam(':foto_5', $foto_5);
	$stmt->bindParam(':id_usuario', $id_usuario);

	// insert one row
	$id_usuario = $idUser;
	$nome = filter_var($data['nome'], FILTER_SANITIZE_STRING);
	$descricao = filter_var($data['descricao'], FILTER_SANITIZE_STRING);
	$id_categoria = filter_var($data['id_categoria'], FILTER_SANITIZE_NUMBER_INT);
	$id_bairro = filter_var($data['id_bairro'], FILTER_SANITIZE_NUMBER_INT);
	$foto_1 = filter_var($data['foto_1'], FILTER_SANITIZE_STRING);
	$foto_2 = filter_var($data['foto_2'], FILTER_SANITIZE_STRING);
	$foto_3 = filter_var($data['foto_3'], FILTER_SANITIZE_STRING);
	$foto_4 = filter_var($data['foto_4'], FILTER_SANITIZE_STRING);
	$foto_5 = filter_var($data['foto_5'], FILTER_SANITIZE_STRING);
	$id_usuario = filter_var($data['id_usuario'], FILTER_SANITIZE_NUMBER_INT);

	if($stmt->execute()){
		sendMailNovoPedido($id_usuario_app);
		$res = array('status' => 200, 'message' => "Success");
		return $response->withJson($res, $res[status]);
	} else {
		$res = array('status' => 500, 'message' => 'Erro ao cadastrar o item.');
		return $response->withJson($res, $res[status]);
	}
});

$app->get('/estados', function(Request $request, Response $response, $args) {
	$auth = auth($request);
	if($auth[status] != 200){
		return $response->withJson($auth, $auth[status]);
	}
	$res = getEstado();
	return $response->withJson($res, $res[status]);
});

$app->get('/cidades', function(Request $request, Response $response, $args) {
	$auth = auth($request);
	if($auth[status] != 200){
		return $response->withJson($auth, $auth[status]);
	}
	$data = $request->getParsedBody();
	$res = getCidade($data["uf"]);
	return $response->withJson($res, $res[status]);
});

$app->get('/bairros', function(Request $request, Response $response, $args) {
	$auth = auth($request);
	if($auth[status] != 200){
		return $response->withJson($auth, $auth[status]);
	}
	$data = $request->getParsedBody();
	$res = getBairro($data["cidade"]);
	return $response->withJson($res, $res[status]);
});

function getConn() {
	

	return new PDO('mysql:host=localhost;dbname=construcaolivre', 'root', 'root',
			array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	
	//return new PDO('mysql:host=localhost;dbname=aldeiacr_dev', 'aldeiacr_dev', 'voanubo2016',
	//		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
}

function getCategorias() {
	$sql = "SELECT id_categoria, nome
          	FROM categoria
          	ORDER BY nome ASC ";
  	$stmt = getConn()->query($sql);
	$noticias = $stmt->fetchAll(PDO::FETCH_OBJ);
    return array('status' => 200, 'message' => "Success", 'data' => $noticias);
}

function getEstado(){
	$sql = "SELECT estado_id, desc_estado
          	FROM estados
          	ORDER BY desc_estado ASC ";
  	$stmt = getConn()->query($sql);
	$noticias = $stmt->fetchAll(PDO::FETCH_OBJ);
    return array('status' => 200, 'message' => "Success", 'data' => $noticias);
}

function getCidade($uf){
	$sql = "SELECT cidade_id, desc_cidade
          	FROM cidades
          	WHERE flg_estado = '".$uf."'
          	ORDER BY desc_cidade ASC ";
  	$stmt = getConn()->query($sql);
	$noticias = $stmt->fetchAll(PDO::FETCH_OBJ);
    return array('status' => 200, 'message' => "Success", 'data' => $noticias);
}

function getBairro($idCidade){
	$sql = "SELECT bairro_id, desc_bairro
          	FROM bairros
          	WHERE cidade_id = ".$idCidade."
          	ORDER BY desc_bairro ASC ";
  	$stmt = getConn()->query($sql);
	$noticias = $stmt->fetchAll(PDO::FETCH_OBJ);
    return array('status' => 200, 'message' => "Success", 'data' => $noticias);
}


function getPedidos($id) {
	$sql = "SELECT pedido.id_pedido, pedido.id_usuario_app, pedido.qtd_5l, pedido.qtd_10l, pedido.troco, DATE_FORMAT(pedido.data_hora, '%d/%m/%Y') as data_hora, pedido.status, condominio.id_condominio, condominio.nome as nome_condominio, usuario_app.nome as nome_cliente, usuario_app.apt, 
		condominio.rua, condominio.numero, condominio.bairro, condominio.cep, condominio.cidade, condominio.uf,
		condominio.referencia, condominio.nome_sindico, condominio.telefone, entregador.nome as nome_entregador
      FROM pedido, condominio, usuario_app, entregador
      WHERE usuario_app.condominio_id = condominio.id_condominio
      AND usuario_app.id_usuario_app = pedido.id_usuario_app
      AND condominio.id_entregador = entregador.id_entregador 
      AND pedido.id_pedido = ".$id;

	$stmt = getConn()->query($sql);
	$result = $stmt->fetchAll(PDO::FETCH_OBJ);
    echo json_encode($result);
}

function login($cpf, $senha) {
	
	$sql = "SELECT usuario.id_usuario, usuario.nome, usuario.cpf, usuario.senha, usuario.telefone, usuario.ativo, 
					item.id_item, item.nome, item.descricao, item.id_categoria, item.id_bairro, item.foto_1, item.foto_2,
					item.foto_3, item.foto_4, item.foto_5, item.ativo, item.data_criacao,
					categoria.id_categoria, categoria.nome,
					estados.desc_estado, cidades.desc_cidade, bairros.desc_bairro
			FROM usuario, item, categoria, cidades, estados, bairros
			WHERE usuario.id_usuario = item.id_usuario
			AND item.id_categoria = categoria.id_categoria
			AND bairros.cidade_id = cidades.cidade_id
			AND cidades.flg_estado = estados.desc_estado
			AND usuario.ativo = 'S'
			AND item.ativo = 'S' 
			AND usuario.cpf = '".$cpf."' 
			AND usuario.senha = '".$senha."' 
			ORDER BY item.id_item DESC";

	$stmt = getConn()->query($sql);
	$usuario = $stmt->fetchAll(PDO::FETCH_OBJ);
	//echo count($usuario);die;
	if (count($usuario) > 0) {
		
		$tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;  //Adding 10 seconds
        $expire     = $notBefore + 1972000000; // Adding 60 seconds
        $serverName = 'https://construcaolivre.org.br/'; /// set your domain name 

        /*
         * Create the token as an array
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            'data' => $usuario[0] //[                  // Data related to the logged user you can set your required data
	    		//'apt'   => $apt, // id from the users table
	     		//'condominio' => $id_condominio, //  name
            	
                     // ]
        ];
      $secretKey = SECRET_KEY;
      /// Here we will transform this array into JWT:
      $jwt = JWT::encode(
                $data, //Data to be encoded in the JWT
                $secretKey,
                ALGORITHM 
                ); 
     $unencodedArray = ['token'=> $jwt];
     	return array('status' => 200, 'message' => "Success", 'data' => $unencodedArray);
	} else {
		return array('status' => 401, 'message' => 'Usuário não cadastrado!');
	} 
    
}

function getItemsByCidade($idCidade) {
	$sql = "SELECT usuario.id_usuario, usuario.nome, usuario.cpf, usuario.senha, usuario.telefone, usuario.ativo, 
					item.id_item, item.nome, item.descricao, item.id_categoria, item.id_bairro, item.foto_1, item.foto_2,
					item.foto_3, item.foto_4, item.foto_5, item.ativo, item.data_criacao,
					categoria.id_categoria, categoria.nome,
					estados.desc_estado, cidades.desc_cidade, bairros.desc_bairro
			FROM usuario, item, categoria, cidades, estados, bairros
			WHERE usuario.id_usuario = item.id_usuario
			AND item.id_categoria = categoria.id_categoria
			AND bairros.cidade_id = cidades.cidade_id
			AND cidades.flg_estado = estados.desc_estado
			AND usuario.ativo = 'S'
			AND item.ativo = 'S' 
			AND cidades.cidade_id = ".$idCidade." 
			ORDER BY item.id_item DESC 
			LIMIT 20";

  	$stmt = getConn()->query($sql);
	$noticias = $stmt->fetchAll(PDO::FETCH_OBJ);
    return array('status' => 200, 'message' => "Success", 'data' => $noticias);
}

function auth($request) {
	$authorization = $request->getHeaderLine("Authorization");
	
	if (trim($authorization) == "") {
		return array('status' => 500, 'message' => 'Token não informado');
	} else {
		try {
			$token = JWT::decode($authorization, SECRET_KEY, array('HS256'));
			return array('status' => 200, 'token' => $token);
		} catch (Exception $e) {
			return array('status' => 401, 'message' => 'Acesso não autorizado');
		}
	}
}

/*function sendMail($html) {
	$mail = new PHPMailer();
	$mail->IsSMTP(); // Define que a mensagem será SMTP
	$mail->Host = "mail.nubohost.com.br"; // Endereço do servidor SMTP
	$mail->SMTPAuth = true; // Usa autenticação SMTP? (opcional)
	$mail->Username = 'suporte@nubohost.com.br'; // Usuário do servidor SMTP
	$mail->Password = 'voanubo2016'; // Senha do servidor SMTP
	$mail->From = " suporte@nubohost.com.br"; // Seu e-mail
	$mail->FromName = "Aldeia Crystal App"; // Seu nome
	$mail->IsHTML(false); // Define que o e-mail será enviado como HTML	
	$mail->AddAddress('jlucianosales@hotmail.com', 'Luciano Sales');
	$mail->Subject  = "Aldeia Crystal App -  Novo Pedido"; // Assunto da mensagem
	$mail->Body = $html;
	$mail->Send();
}*/


$app->run();