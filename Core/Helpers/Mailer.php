<?php

class Mailer
{
	public function send($receivers, $subject, $body)
	{
		$mail = new PHPMailer();

		$mail->isSMTP();
		$mail->Host 		= 'host';
		$mail->SMTPAuth 	= true;
		$mail->Username 	= EMAIL_ADDR;
		$mail->Password 	= EMAIL_PWD;
		$mail->SMTPSecure 	= 'tls';
		$mail->Port 		= 587;
		$mail->From 		= EMAIL_ADDR;
		$mail->FromName 	= "E-Novative Keys";

		foreach($receivers as $name => $addr)
			$mail->addAddress($addr, $name);
		
		$mail->isHTML(true);
		$mail->CharSet 		= 'UTF-8';
		$mail->Subject 		= $subject;
		$mail->Body 		= $body;
		
		return $mail->send();
	}
}

?>