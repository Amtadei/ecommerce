<?php

namespace Hcode;

use Rain\Tpl;

class Mailer {

  const USERNAME = "tadeiantoniomarcos@gmail.com";
  const PASSWORD = "Mhchepjs1306";
  const NAME_FROM = "Asserthy Store";

  private $mail;

	public function __construct($toAddress, $toName, $subject, $tplName, $data = array()) {

    // config
    $config = array(
            "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/email/",
            "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
            "debug"         => false // set to false to improve the speed
    );

    Tpl::configure($config);

    // create the Tpl object
    $tpl = new Tpl;

    foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
    }

    $html = $tpl->draw($tplName, true);

		//Create a new PHPMailer instance
		$this->mail = new \PHPMailer(True);

   	//$this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $this->mail->SMTPDebug = 0;
   	$this->mail->isSMTP();
    $this->mail->SMTPAuth = true;
    $this->mail->Username = Mailer::USERNAME;
    $this->mail->Password = Mailer::PASSWORD;
    $this->mail->Host = 'smtp.gmail.com';
    $this->mail->Port = 587;

   	//$this->mail->Host = 'smtp.gmail.com';
   	
   	//$this->mail->SMTPSecure = 'tls';
   	//$this->mail->Username = 'tadeiantoniomarcos@gmail.com';
   	
   	//$this->mail->SMTPSecure = false;
   	//$this->mail->SMTPAutoTLS = false;

   	//$this->mail->SMTPOptions = array(
    //   		'ssl' => array(
   	//    	'verify_peer' => false,
   	//	   'verify_peer_name' => false,
    // 		'allow_self_signed' => true
    //		));

    //$this->mail->setFrom('tadeiantoniomarcos@gmail.com', 'Antonio Marcos Tadei');
    $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);
    //$this->mail->addAddress('amtadei@uol.com.br', 'Antonio Marcos');
    $this->mail->addAddress($toAddress, $toName);
    $this->mail->isHTML(true);
    $this->mail->Subject = $subject;

     $this->mail->msgHTML($html);

    //$this->mail->Body = 'Chegou o email teste do <strong>Curso PHP 7</strong>';
    //$this->mail->AltBody = 'Chegou o email teste do Curso PHP 7';

	}

    public function send() {

        return $this->mail->send();

    }

}

?>