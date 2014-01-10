<?php

require 'ConnectionFactory.class.php';

class BasicDao {

    private function removerCaracteresComentarios($dados) {
        $remover = array('*', '/');
        return str_replace($remover, "", $dados);
    }

    private function mapearClasse($objeto) {

        $reflectionClass = new ReflectionClass($objeto);
        $propriedades = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

        $tituloJSON = $this->removerCaracteresComentarios($reflectionClass->getDocComment());

        foreach ($propriedades as $i => $p) {
            if ($p->getDocComment() != null) {
                $p->setAccessible(true);
                $camposJSON .= '{';
                $camposJSON .= '"Propriedade":"' . $p->getName() . '",';
                $camposJSON .= '"Valor":"' . $p->getValue($objeto) . '",';
                $camposJSON .= '"get":"get' . strtoupper(substr($p->getName(), 0, 1)) . substr($p->getName(), 1) . '",';
                $camposJSON .= '"set":"set' . strtoupper(substr($p->getName(), 0, 1)) . substr($p->getName(), 1) . '",';
                $camposJSON .= $this->removerCaracteresComentarios($p->getDocComment());
                $camposJSON .= '}';
                if ($propriedades[$i + 1] != null && $propriedades[$i + 1]->getDocComment() != null)
                    $camposJSON .= ',';
                $p->setAccessible(false);
            }
        }

        return '{' . $tituloJSON . ', "Campos": [' . $camposJSON . '] }';
    }

    private function gerarSQLInserir($objeto) {

        $mapearClasse = json_decode($this->mapearClasse($objeto), true);

        $tabela = $mapearClasse["Tabela"];
        $campos = $mapearClasse["Campos"];

        foreach ($campos as $i => $campo) {

            if ($campo["AutoIncremento"] == "sim")
                continue;

            $colunas .= $campo["Coluna"];
            $colunas .= $campos[$i + 1] != null ? ',' : '';

            if ($campo["Tipo"] == "int") {
                $valores .= $campo["Valor"];
            } elseif ($campo["Tipo"] == "varchar") {
                $valores .= "'" . $campo["Valor"] . "'";
            }
            $valores .= $campos[$i + 1] != null ? ',' : '';
        }

        $sql = 'INSERT INTO ' . $tabela . ' ( ' . $colunas . ' ) VALUES ( ' . $valores . ' )';

        return $sql;
    }

    private function gerarSQLExcluir($objeto) {

        $mapearClasse = json_decode($this->mapearClasse($objeto), true);

        $tabela = $mapearClasse["Tabela"];
        $campos = $mapearClasse["Campos"];

        foreach ($campos as $campo) {
            if ($campo["ChavePrimaria"] == "sim") {
                $chavePrimaria["Coluna"] = $campo["Coluna"];
                $chavePrimaria["Valor"] = $campo["Valor"];
                continue;
            }
        }

        $sql = 'DELETE FROM ' . $tabela . ' WHERE ' . $chavePrimaria["Coluna"] . ' = ' . $chavePrimaria["Valor"];

        return $sql;
    }

    private function gerarSQLAtualizar($objeto) {

        $mapearClasse = json_decode($this->mapearClasse($objeto), true);

        $tabela = $mapearClasse["Tabela"];
        $campos = $mapearClasse["Campos"];

        foreach ($campos as $i => $campo) {
            if ($campo["ChavePrimaria"] == "sim") {
                $chavePrimaria["Coluna"] = $campo["Coluna"];
                $chavePrimaria["Valor"] = $campo["Valor"];
                continue;
            }

            if ($campo["AutoIncremento"] == "sim")
                continue;

            if ($campo["Tipo"] == "int") {
                $valores .= $campo["Coluna"] . ' = ' . $campo["Valor"];
            } elseif ($campo["Tipo"] == "varchar") {
                $valores .= $campo["Coluna"] . " = '" . $campo["Valor"] . "'";
            }
            $valores .= $campos[$i + 1] != null ? ', ' : '';
        }

        $sql = 'UPDATE ' . $tabela . ' SET ' . $valores . ' WHERE ' . $chavePrimaria["Coluna"] . ' = ' . $chavePrimaria["Valor"];

        return $sql;
    }

    private function gerarSQLRecuperar($objeto) {
        $mapearClasse = json_decode($this->mapearClasse($objeto), true);

        $tabela = $mapearClasse["Tabela"];
        $campos = $mapearClasse["Campos"];

        foreach ($campos as $campo) {

            if (empty($campo["Valor"]))
                continue;

            $condicoes .= ' AND ';
            if ($campo["Tipo"] == "int") {
                $condicoes .= $campo["Coluna"] . ' = ' . $campo["Valor"];
            } elseif ($campo["Tipo"] == "varchar") {
                $condicoes .= $campo["Coluna"] . " = '" . $campo["Valor"] . "'";
            }
        }

        $sql = "SELECT * FROM " . $tabela . " WHERE 1=1 " . $condicoes;

        return $sql;
    }

    private function verificaExiste($objeto) {

        $mapearClasse = json_decode($this->mapearClasse($objeto), true);

        $tabela = $mapearClasse["Tabela"];
        $campos = $mapearClasse["Campos"];

        foreach ($campos as $campo) {
            if ($campo["ChavePrimaria"] != "sim")
                continue;

            $chavePrimaria["Coluna"] = $campo["Coluna"];
            $chavePrimaria["Valor"] = $campo["Valor"];
        }

        $sql = 'SELECT * FROM ' . $tabela . ' WHERE ' . $chavePrimaria["Coluna"] . ' = ' . $chavePrimaria["Valor"];

        return $sql;
    }

    public function salvar($objeto) {

        ConnectionFactory::getInstance();

        $sqlVerifica = $this->verificaExiste($objeto);

        if (mysql_num_rows(mysql_query($sqlVerifica)) > 0) {
            $sql = $this->gerarSQLAtualizar($objeto);
        } else {
            $sql = $this->gerarSQLInserir($objeto);
        }

        $query = mysql_query($sql);

        if ($query) {
            return true;
        } else {
            return false;
        }
    }

    public function recuperar($objeto) {

        ConnectionFactory::getInstance();

        $sqlRecupera = $this->gerarSQLRecuperar($objeto);

        $executaSql = mysql_query($sqlRecupera);

        $mapearClasse = json_decode($this->mapearClasse($objeto), true);
        $campos = $mapearClasse["Campos"];
        foreach ($campos as $campo) {
            $setters[$campo["Coluna"]] = $campo["set"];
        }

        while ($resultados = mysql_fetch_assoc($executaSql)) {

            $reflectionClass = new ReflectionClass($objeto);
            $o = $reflectionClass->newInstanceArgs();

            foreach ($resultados as $r => $resultado) {
                $metodoSet = new ReflectionMethod(get_class($objeto), $setters[$r]);
                $metodoSet->invoke($o, $resultado);
            }

            $lista[] = $o;
        }

        return $lista;
    }

    public function excluir($objeto) {

        ConnectionFactory::getInstance();

        $sqlRecupera = $this->gerarSQLExcluir($objeto);

        $executaSql = mysql_query($sqlRecupera);

        if ($executaSql) {
            return true;
        } else {
            return false;
        }
    }

}

?>
