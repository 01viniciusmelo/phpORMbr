<?php

class ConnectionFactory {
    
    private static $instance = null;

    private function __construct() {
        $this->conect();
    }

    public static function getInstance() {
        if (!isset(self::$instance) && is_null(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    private function conect() {
        $host = "caminho_banco";
        $usuario = "usuario_banco";
        $senha = "senha_banco";
        $banco = "nome_banco";

        mysql_connect($host, $usuario, $senha);
        mysql_select_db($banco);
    }

}

?> 
