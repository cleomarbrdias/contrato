<?php
use \CONASS\Model\User;

function getUserName()
{
	$user = User::getFromSession();

	return $user->getnome();
}

function getIdUsuario()
{
	$user = User::getFromSession();

	return $user->getid_usuario();
}

function error_handler($code, $message, $file, $line){
    
    echo json_encode(array(
        "code"      =>$code,
        "message"   =>$message,
        "line"      =>$line,
        "file"      =>$file
       
    ));
}


?>