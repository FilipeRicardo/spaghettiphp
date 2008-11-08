<?php
/**
 *  O arquivo basics.php cont�m quatro classes b�sicas para o funcionamento
 *  do Spaghetti Framework. A classe Object � herdada por praticamente todas
 *  as outras classes existentes dentro do core do Spaghetti. A classe Spaghetti
 *  possui os m�todos para importar os arquivos que ser�o solicitados ao longo
 *  da sua execu��o. A classe Config estabelece as configura��es necess�rias
 *  de banco de dados e de outras prefer�ncias da aplica��o. A classe Error atua
 *  na manipula��o de erros provenientes de qualquer lugar da aplica��o.
 *
 *  Licensed under The MIT License.
 *  Redistributions of files must retain the above copyright notice.
 *  
 *  @package Spaghetti
 *  @subpackage Spaghetti.Core.Basics
 *  @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class Object {
    public function log($message = "") {
        
    }
    public function error($type = "", $details = array()) {
        new Error($type, $details);
    }
    /**
     * O m�todo Object::transmit() transforma um array de op��es em respectivas
     * vari�veis de classe que, portanto, s�o transmitidas para outra classe. Geralmente
     * � utilizada na mudan�a de camadas, entre modelos e controladores ou controladores
     * e visualiza��es.
     *
     * @param array $arr Array de op��es
     * @return boolean
    */
    public function transmit($arr = array()) {
        foreach($arr as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }
}

class Spaghetti extends Object {
    /**
     * O m�todo Spaghetti::import() faz a importa��o dos arquivos necess�rios
     * durante a execu��o do programa.
     *
     * @param string $type Contexto de onde ser� importado o arquivo
     * @param mixed $file Uma string com o nome do arquivo ou um array com nomes de arquivo
     * @param string $ext Extens�o de arquivo do(s) arquivo(s) a ser(em) importado(s)
     * @param boolean $return Define se o metodo retorna o caminho para o arquivo ou a c�pia em buffer
     * @return mixed Buffer do arquivo importado ou falso caso n�o consiga carreg�-lo
    */
    static function import($type = "Core", $file = "", $ext = "php", $return = false) {
        $paths = array(
            "Core" => array(CORE),
            "App" => array(APP, LIB),
            "Lib" => array(LIB),
            "Webroot" => array(WEBROOT),
            "Model" => array(APP . DS . "models", LIB . DS . "models"),
            "Controller" => array(APP . DS . "controllers", LIB . DS . "controllers"),
            "View" => array(APP . DS . "views", LIB . DS . "views"),
            "Layout" => array(APP . DS . "layouts", LIB . DS . "layouts"),
            "Component" => array(APP . DS . "components", LIB . DS . "components"),
            "Helper" => array(APP . DS . "helpers", LIB . DS . "helpers"),
            "Filter" => array(APP . DS . "filters", LIB . DS . "filters")
        );
        foreach($paths[$type] as $path):
            if(is_array($file)):
                foreach($file as $file):
                    $include = Spaghetti::import($type, $file, $ext);
                endforeach;
                return $include;
            else:
                $file_path = $path . DS . "{$file}.{$ext}";
                if(file_exists($file_path)):
                    return $return ? $file_path : include($file_path);
                endif;
            endif;
        endforeach;
        return false;
    }
}

class Config extends Object {
    public $config = array();
    /**
     * O m�todo Config::get_instance() retorna sempre o mesmo link de inst�ncia,
     * para que os m�todos est�ticos possam ser usados com caracter�sticas de
     * inst�ncias de objetos.
     *
     * @return resource
    */
    public function &get_instance() {
        static $instance = array();
        if(!isset($instance[0]) || !$instance[0]):
            $instance[0] =& new Config();
        endif;
        return $instance[0];
    }
    /**
     * O m�todo Config::read() retorna o valor de uma configura��o da aplica��o.
     *
     * @param string $key Nome da chave (vari�vel) da configura��o
     * @return mixed
     */
    static function read($key = "") {
        $self = self::get_instance();
        return $self->config[$key];
    }
    /**
     * O m�todo Config::write() grava o valor de uma configura��o da aplica��o.
     *
     * @param string $key Nome da chave (vari�vel) da configura��o
     * @param string $value Valor da chave (vari�vel) da configura��o
     * @return mixed
     */
    static function write($key = "", $value = "") {
        $self = self::get_instance();
        $self->config[$key] = $value;
        return true;
    }
}

class Error extends Object {
    public function __construct($type = "", $details = array()) {
        pr($type);
        pr($details);
        die();
    }
}

?>