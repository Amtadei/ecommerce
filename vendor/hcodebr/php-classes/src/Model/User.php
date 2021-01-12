<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {

	const SUCCESS = "UserSuccess";
	const SESSION = "User";
	const ERROR = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";

	public static function getFromSession() {

		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

	}

	public static function checkLogin($inadmin = true) {

		if (!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0) 
		{
			
			//Não esta logado
			return false;

		} else {

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true) {

				return true;

			} else if ($inadmin === false) {

				return true;

			} else {

				return false;
			}			
		}

	}

	public static function login($login,  $password) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM db_ecommerce.tb_users a INNER JOIN db_ecommerce.tb_persons b USING(idperson) WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0)
		{	
			throw new \Exception("Usuário inexistente ou senha inválida.", 1);
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception("Usuário inexistente ou senha inválida.", 1);	
		}

	}

	public static function verifyLogin($inadmin = true) {

		if (!User::checkLogin($inadmin))
		{
			
			if ($inadmin) {
				header("Location: /admin/login");
			} else {
				header("Location: /login");	
			}
			exit;
		}

	}

	public static function logout() {

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll() {

		$sql = new Sql();

		return $sql->select("SELECT * FROM db_ecommerce.tb_users a INNER JOIN db_ecommerce.tb_persons b USING(idperson) ORDER BY b.desperson");

	}

	public function save() {

		$sql = new Sql();

		$results = $sql-> select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function get($iduser) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM db_ecommerce.tb_users a INNER JOIN db_ecommerce.tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(":iduser"=>$iduser
		));

		$data = $results[0];

		$data['desperson'] = utf8_encode($data['desperson']);

		$this->setData($data);

	}

	public function update() {

		$sql = new Sql();

		$results = $sql-> select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			 	":iduser"=>$this->getiduser(),
				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function delete() {

		$sql = new Sql();

		$results = $sql->query("CALL sp_users_delete(:iduser)", array(":iduser"=>$this->getiduser()));

	}

	public static function getForgot($email, $inadmin = true) {

		define('SECRET_IV', pack('a16','senha'));
		define('SECRET', pack('a16','senha'));

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM db_ecommerce.tb_users a INNER JOIN db_ecommerce.tb_persons b USING(idperson) WHERE b.desemail = :email", array(":email"=>$email
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			
			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
			 		":iduser"=>$data["iduser"],
					":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");		
			}
			else
			{
				$dataRecovery = $results2[0];

				$code = openssl_encrypt($dataRecovery["idrecovery"], 'AES-128-CBC', SECRET, 0,	SECRET_IV);

				if ($inadmin === true) {

					$link = "http://www.asserthy-ecommerce.com.br/admin/forgot/reset?code=$code";

				} else {

					$link = "http://www.asserthy-ecommerce.com.br/forgot/reset?code=$code";

				}

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Asserthy Store", 
					"forgot", array(
					 				"name"=>$data["desperson"],
					 				"link"=>$link
				));

				$mailer->send();

				return $data;

			}

		}	

	}

	public static function validForgotDescrypt($code) {

		define('SECRET_IV', pack('a16','senha'));
		define('SECRET', pack('a16','senha'));

		// Por algum motivo que desconheço o sinal de + na URL vem como branco
		$code = str_replace(' ','+', $code);

		$idrecovery = openssl_decrypt($code,'AES-128-CBC', SECRET,	0,	SECRET_IV);

		$sql = new Sql();

		$results = $sql->select("SELECT * 
								FROM db_ecommerce.tb_userspasswordsrecoveries a
								INNER JOIN db_ecommerce.tb_users b USING(iduser)
								INNER JOIN db_ecommerce.Tb_persons c USING(idperson)
								WHERE a.idrecovery= :idrecovery
									AND a.dtrecovery IS NULL
        							AND DATE_ADD(a.dtregister,INTERVAL 1 HOUR) > NOW()",
        				 array(":idrecovery"=>$idrecovery));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0];
		}

	}

	public static function setForgotUsed($idrecovery) {

		$sql = new Sql();

		$results = $sql->query("UPDATE db_ecommerce.tb_userspasswordsrecoveries SET dtrecovery = NOW()
								WHERE idrecovery= :idrecovery",
        				 array(":idrecovery"=>$idrecovery));

	}

	public function setPassword($password) {

		$sql = new Sql();

		$sql->query("UPDATE db_ecommerce.tb_users SET despassword = :password
		 			WHERE iduser = :iduser", array(
		 				":password"=>$password, 
        				":iduser"=>$this->getiduser()
        ));

	}

	public static function setSuccess($msg) {

		$_SESSION[User::SUCCESS] = $msg;

	}

	public static function setError($msg) {

		$_SESSION[User::ERROR] = $msg;

	}

	public static function setErrorRegister($msg) {

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	public static function getSuccess() {

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;

	}

	public static function getError() {

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;

	}

	public static function getErrorRegister() {

		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}

	public static function clearSuccess() {

		$_SESSION[User::SUCCESS] = NULL;

	}

	public static function clearError() {

		$_SESSION[User::ERROR] = NULL;

	}

	public static function clearErrorRegister() {

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

	public static function checkLoginExist($login) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM db_ecommerce.tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);

		return (count($results) > 0);

	}

	public static function getPasswordHash($password) {

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);

	}

	public function getOrders() {

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM db_ecommerce.tb_orders a
			INNER JOIN db_ecommerce.tb_ordersstatus b USING (idstatus)
			INNER JOIN db_ecommerce.tb_carts c USING (idcart)
			INNER JOIN db_ecommerce.tb_users d ON d.iduser = a.iduser
			INNER JOIN db_ecommerce.tb_addresses e USING(idaddress)
			INNER JOIN db_ecommerce.tb_persons f ON f.idperson = d.idperson
			WHERE a.iduser = :iduser", [
				':iduser'=>$this->getiduser()
		]);

		return $results;

	}

	public static function getUsersPage($page = 1, $itensPerPage = 10) {

		$start = ($page -1) * $itensPerPage;

		$sql = new Sql();	
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS * 
			FROM db_ecommerce.tb_users a
			INNER JOIN db_ecommerce.tb_persons b USING(idperson) ORDER BY b.desperson
			LIMIT $start, $itensPerPage;"
		);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
		];

	}

	public static function getUsersPageSearch($search, $page = 1, $itensPerPage = 10) {

		$start = ($page -1) * $itensPerPage;

		$sql = new Sql();	
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS * 
			FROM db_ecommerce.tb_users a
			INNER JOIN db_ecommerce.tb_persons b USING(idperson) 
			WHERE b.desperson LIKE :search OR b.desemail LIKE :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
			LIMIT $start, $itensPerPage;", [
				':search'=>'%'.$search.'%'
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
		];

	}


}

?>