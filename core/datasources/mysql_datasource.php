<?php
/**
 *  Short Description
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2009, Spaghetti* Framework (http://spaghettiphp.org/)
 *
 */

class MysqlDatasource extends Datasource {
	private $schema = array();
    private $connection;
	private $results;
    public $connected = false;

    /**
     *  Conecta ao banco de dados.
     *
     *  @return resource Conexão com o banco de dados
     */
    public function connect() {
        $this->connection = mysql_connect($this->config["host"], $this->config["user"], $this->config["password"]);
        if(mysql_select_db($this->config["database"], $this->connection)):
            $this->connected = true;
        endif;
        return $this->connection;
    }
    /**
     *  Desconecta do banco de dados.
     *
     *  @return boolean Verdadeiro caso a conexão tenha sido desfeita
     */
    public function disconnect() {
		if(mysql_close($this->connection)):
			$this->connected = false;
			$this->connection = null;
		endif;
		return !$this->connected;
    }
	/**
	 *  Retorna a conexão com o banco de dados, ou conecta caso a conexão ainda
	 *  não tenha sido estabelecida.
	 *
	 *  @return resource Conexão com o banco de dados
	 */
    public function &getConnection() {
		if(!$this->connected):
			$this->connect();
		endif;
        return $this->connection;
    }
    /**
     *  Executa uma consulta SQL.
     *
     *  @param string $sql Consulta SQL
     *  @return mixed Resultado da consulta
     */
    public function query($sql = null) {
        $this->results = mysql_query($sql, $this->getConnection());
        return $this->results;
    }
    /**
     *  Retorna um resultado de uma consulta SQL.
     *
     *  @param string $sql Consulta SQL
     *  @return mixed Resultado obtido, falso caso não hajam outros resultados
     */
    public function fetch($sql = null) {
        if(!is_null($sql) && !$this->query($sql)):
            return false;
        elseif($this->hasResult()):
            return $this->fetchRow();
        else:
            return null;
        endif;
    }
    /**
     *  Retorna todos os resultados de uma consulta SQL.
     *
     *  @param string $sql Consulta SQL
     *  @return mixed Resultados obtidos ou falso em caso de erro.
     */
    public function fetchAll($sql = null) {
        if(!is_null($sql) && !$this->query($sql)):
            return false;
        elseif($this->hasResult()):
            $results = array();
            while($result = $this->fetch()):
                $results []= $result;
            endwhile;
            return $results;
        else:
            return null;
        endif;
    }
    /**
     *  Retorna o próximo resultado obtido em uma consulta SQL.
     *
     *  @param resource $results Conjunto de resultados
     *  @return mixed Linha de resultados ou falso caso não hajam mais resultados
     */
    public function fetchRow($results = null) {
        $results = is_null($results) ? $this->results : $results;
        return mysql_fetch_assoc($results);
    }
    /**
     *  Verifica se a última consulta possui resultados.
     *
     *  @return boolean Verdadeiro caso hajam resultados
     */
    public function hasResult() {
        return is_resource($this->results);
    }
    /**
     *  Retorna o tipo básico de uma coluna baseada na sua descrição no banco de dados.
     *
     *  @param string $column Descrição da coluna no banco de dados
     *  @return string Tipo básico da coluna
     */
    public function column($column = "") {
        preg_match("/([a-z]*)\(?([^\)]*)?\)?/", $column, $type);
        list($column, $type, $limit) = $type;
        if(in_array($type, array("date", "time", "datetime", "timestamp"))):
            return $type;
        elseif(($type == "tinyint" && $limit == 1) || $type == "boolean"):
            return "boolean";
        elseif(strstr($type, "int")):
            return "integer";
        elseif(strstr($type, "char") || $type == "tinytext"):
            return "string";
        elseif(strstr($type, "text")):
            return "text";
        elseif(strstr($type, "blob") || $type == "binary"):
            return "binary";
        elseif(in_array($type, array("float", "double", "real", "decimal"))):
            return "float";
        elseif($type == "enum" || $type = "set"):
            return "{$type}($limit)";
        endif;
    }
    /**
     *  Descreve uma tabela do banco de dados.
     *
     *  @param string $table Tabela a ser descrita
     *  @return array Descrição da tabela
     */
    public function describe($table = null) {
        if(!isset($this->schema[$table])):
            if(!$this->query("SHOW COLUMNS FROM {$table}")) return false;
            $columns = $this->fetchAll();
            $schema = array();
            foreach($columns as $column):
                $schema[$column["Field"]] = array(
                    "type" => $column["Type"],
                    "null" => $column["Null"] == "YES" ? true : false,
                    "default" => $column["Default"],
                    "key" => $column["Key"],
                    "extra" => $column["Extra"]
                );
            endforeach;
            $this->schema[$table] = $schema;
        endif;
        return $this->schema[$table];
    }

	/**
	 *  Short description.
	 */
	public function create($table = null, $params = array()) {
		$query = $this->renderSql("insert", array(
			"table" => $table,
			"fields" => join(",", array_keys($params)),
			"values" => "'" . join("','", $params) . "'"
		));
		return $this->query($query);
	}
	/**
	 *  Short description.
	 */
	public function read($table = null, $params = array()) {
		$query = $this->renderSql("select", array(
			"table" => $table,
			"fields" => (is_array($f = $params["fields"])) ? join(",", $f) : $f,
			"conditions" => ($c = $this->sqlConditions($table, $params["conditions"])) ? "WHERE {$c}" : "",
			"order" => is_null($params["order"]) ? "" : "ORDER BY {$params['order']}",
			"limit" => is_null($params["limit"]) ? "" : "LIMIT {$params['limit']}"
		));
        return $this->fetchAll($query);
	}
	/**
	 *  Short description.
	 */
	public function update($table = null, $params = array()) {
		$query = $this->renderSql("update", array(
			"table" => $table,
			"conditions" => ($c = $this->sqlConditions($table, $params["conditions"])) ? "WHERE {$c}" : "",
			"order" => is_null($params["order"]) ? "" : "ORDER BY {$params['order']}",
			"limit" => is_null($params["limit"]) ? "" : "LIMIT {$params['limit']}",
			"values" => $this->sqlSet($this->sqlConditions($table, $params["data"]))
		));
		return $this->query($query);
	}
	/**
	 *  Short description.
	 */
	public function delete($table = null, $params = array()) {
		$query = $this->renderSql("delete", array(
			"table" => $table,
			"conditions" => ($c = $this->sqlConditions($table, $params["conditions"])) ? "WHERE {$c}" : "",
			"order" => is_null($params["order"]) ? "" : "ORDER BY {$params['order']}",
			"limit" => is_null($params["limit"]) ? "" : "LIMIT {$params['limit']}"
		));
		return $this->query($query) ? true : false;
	}
	
	
	private function renderSql($type, $data = array()) {
		switch($type):
			case "select":
				return "SELECT {$data['fields']} FROM {$data['table']} {$data['conditions']}";
			case "delete":
				return "DELETE FROM {$data['table']} {$data['conditions']} {$data['order']} {$data['limit']}";
			case "insert":
				return "INSERT INTO {$data['table']}({$data['fields']}) VALUES({$data['values']})";
			case "update":
				return "UPDATE {$data['table']} SET {$data['values']} {$data['conditions']} {$data['order']} {$data['limit']}";
		endswitch;
	}
	
	
    public function sqlSet($data = "") {
        return preg_replace("/' AND /", "', ", $data);
    }
    public function sqlConditions($table, $conditions) {
        $sql = "";
        $logic = array("or", "or not", "||", "xor", "and", "and not", "&&", "not");
        $comparison = array("=", "<>", "!=", "<=", "<", ">=", ">", "<=>", "LIKE");
        if(is_array($conditions)):
            foreach($conditions as $field => $value):
                if(is_string($value) && is_numeric($field)):
                    $sql .= "{$value} AND ";
                elseif(is_array($value)):
                    if(is_numeric($field)):
                        $field = "OR";
                    elseif(in_array($field, $logic)):
                        $field = strtoupper($field);
                    elseif(preg_match("/([a-z]*) BETWEEN/", $field, $parts) && $this->schema[$table][$parts[1]]):
                        $sql .= "{$field} '" . join("' AND '", $value) . "'";
                        continue;
                    else:
                        $values = array();
                        foreach($value as $item):
                            $values []= $this->sqlConditions($table, array($field => $item));
                        endforeach;
                        $sql .= "(" . join(" OR ", $values) . ") AND ";
                        continue;
                    endif;
                    $sql .= preg_replace("/' AND /", "' {$field} ", $this->sqlConditions($table, $value));
                else:
                    if(preg_match("/([a-z]*) (" . join("|", $comparison) . ")/", $field, $parts) && $this->schema[$table][$parts[1]]):
                        $value = $this->escape($value);
                        $sql .= "{$parts[1]} {$parts[2]} '{$value}' AND ";
                    elseif($this->schema[$table][$field]):
                        $value = $this->escape($value);
                        $sql .= "{$field} = '{$value}' AND ";
                    endif;
                endif;
            endforeach;
            $sql = trim($sql, " AND ");
        else:
            $sql = $conditions;
        endif;
        return $sql;
    }
    public function escape($data) {
        if(get_magic_quotes_gpc()):
            $data = stripslashes($data);
        endif;
        return mysql_real_escape_string($data, $this->getConnection());
    }
}

?>