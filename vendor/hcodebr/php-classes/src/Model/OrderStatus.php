<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class OrderStatus extends Model {

	const EM_ABERTO = 1;
	const AGUARDANDO_PAGAMENTO = 2;
	const PAGO = 3;
	const ENTREGUE = 4;

	public function listAll() {

		$sql = new Sql();

		RETURN $sql->select("SELECT * FROM db_ecommerce.tb_ordersstatus ORDER BY desstatus");

	}

}

?>