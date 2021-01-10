<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Order extends Model {

	public function save() {

		$sql = new Sql();

		if ($this->getidorder() === NULL) {
			$this->setidorder(0);	
		}

		$results = $sql-> select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)",  [":idorder"=>$this->getidorder(),
			 ":idcart"=>$this->getidcart(),
			 ":iduser"=>$this->getiduser(),
			 ":idstatus"=>$this->getidstatus(),
			 ":idaddress"=>$this->getidaddress(),
			 ":vltotal"=>$this->getvltotal()
		]);

		if (count($results) > 0) {

			$this->setData($results[0]);

		}

	}

	public function get($idorder) {

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM db_ecommerce.tb_orders a
			INNER JOIN db_ecommerce.tb_ordersstatus b USING (idstatus)
			INNER JOIN db_ecommerce.tb_carts c USING (idcart)
			INNER JOIN db_ecommerce.tb_users d ON d.iduser = a.iduser
			INNER JOIN db_ecommerce.tb_addresses e USING(idaddress)
			INNER JOIN db_ecommerce.tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder", [
				':idorder'=>$idorder
		]);

		if (count($results) > 0) {

			$this->setData($results[0]);

		}

	}

}

?>