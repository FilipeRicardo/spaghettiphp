<?php
/**
 *  ClassRegistry faz o registro e gerenciamento de instâncias das classes utilizadas
 *  pelo Spaghetti, evitando a criação de várias instâncias de uma mesma classe.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2009, Spaghetti* Framework (http://spaghettiphp.org/)
 *
 */

class ClassRegistry {
    /**
     *  Nome das classes a serem utilizados pelo Spaghetti
     */
    public $objects = array();
    public static function &getInstance() {
        static $instance = array();
        if (!$instance):
            $instance[0] = new ClassRegistry();
        endif;
        return $instance[0];
    }
    /**
     *  Carrega a classe, registrando o objeto, retornando uma instância
     *  para a mesma.
     *
     *  @param string $class Classe a ser inicializada
     *  @param string $type Tipo da classe
     *  @return object Instância da classe
     */
    public static function &load($class, $type = "Model") {
        $self =& ClassRegistry::getInstance();
        if($object =& $self->duplicate($class, $class)):
            return $object;
        elseif(!class_exists($class)):
            if(App::path($type, Inflector::underscore($class))):
                App::import($type, Inflector::underscore($class));
            endif;
        endif;
        if(class_exists($class)):
            ${$class} = new $class;
        endif;
        return ${$class};
    }
    /**
     *  Inicializa a classe, registrando o objeto, retornando uma instância
     *  para a mesma.
     * 
     *  @param string $class Classe a ser inicializada
     *  @param string $type Tipo da classe
     *  @return object Instância da classe
     */
    public static function &init($class, $type = "Model") {
        $self =& ClassRegistry::getInstance();
        if($model =& $self->duplicate($class, $class)):
            return $model;
        elseif(class_exists($class) || App::import($type, Inflector::underscore($class))):
            ${$class} = new $class;
        else:
            $this->error("missing{$type}", array(strtolower($type) => $class));
        endif;
        return ${$class};
    }
    /**
     *  Adiciona uma instância de uma classe no registro.
     * 
     *  @param string $key Nome da chave
     *  @param object &$object Referência ao objeto a ser registrado
     *  @return boolean Verdadeiro se a instância foi adicionada, falso se a chave
     *  já existir
     */
    public static function addObject($key, &$object) {
        $self =& ClassRegistry::getInstance();
        if(array_key_exists($key, $self->objects) === false):
            $self->objects[$key] =& $object;
            return true;
        endif;
        return false;
    }
    /**
     *  Remove uma instância de uma classe do registro.
     *  
     *  @param string $key Nome da chave
     *  @return boolean true
     */
    public static function removeObject($key) {
        $self =& ClassRegistry::getInstance();
        if(array_key_exists($key, $self->objects) === true):
            unset($self->objects[$key]);
        endif;
        return true;
    }
    /**
     *  Verifica se uma se uma chave já está registrada.
     * 
     *  @param string $key Nome da chave
     *  @return boolean Verdadeiro se a chave está registrada
     */
    public static function isKeySet($key) {
        $self =& ClassRegistry::getInstance();
        if(array_key_exists($key, $self->objects)):
            return true;
        endif;
        return false;
    }
    /**
     *  Retorna a instância da respectiva chave solicitada.
     * 
     *  @param string $key Nome da chave
     *  @return mixed Objeto correspondente a chave, falso se a chave não existe
     */
    public static function &getObject($key) {
        $self =& ClassRegistry::getInstance();
        $return = false;
        if(self::isKeySet($key)):
            $return =& $self->objects[$key];
        endif;
        return $return;
    }
    /**
     *  Retorna uma cópia de uma instância já registrada.
     * 
     *  @param string $key Chave da instância a ser buscada
     *  @param object $class Instância da classe a ser buscada
     *  @return mixed Instância da classe, falso se não estiver definida no registro
     */
    public static function &duplicate($key, $class) {
        $self =& ClassRegistry::getInstance();
        $duplicate = false;
        if (self::isKeySet($key)):
            $object =& self::getObject($key);
            if($object instanceof $class):
                $duplicate =& $object;
            endif;
            unset($object);
        endif;
        return $duplicate;
    }
    /**
     *  Limpa todos os objetos instanciados do registro.
     * 
     *  @return boolean true
     */
    public static function flush() {
        $self =& ClassRegistry::getInstance();
        $self->objects = array();
        return true;
    }
}

?>