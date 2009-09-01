<?php
/**
 *  Gerencia os dados para utilização em console.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2009, Spaghetti* Framework (http://spaghettiphp.org/)
 *
 */
class Shell extends Object {
    /**
     *  Expõe o texto do erro e paraliza a execução posteriormente.
     * 
     *  @param string $text Texto a ser exibido
     */
    public function error($text = null) {
        $this->log($text, "error");
        $this->stop();
    }
    /**
     *  Grava as mensagens com o método que a chamou.
     * 
     *  @param string $message Mensagem a ser gravada
     *  @param string $type Tipo da execução
     */
    public function log($message = null, $type = "created") {
        printf("%15s  %s\n", $type, $message);
    }
}

class ShellDispatcher extends Shell {
    public $rawArguments = array();
    public $arguments = array();
    public $params = array();
    public function __construct() {
        $this->rawArguments = $_SERVER["argv"];
        $this->parseArguments();
        $this->dispatch();
    }
    private function parseArguments() {
        if(count($this->rawArguments) > 2):
            $this->params = array_slice($this->rawArguments, 2);
        endif;
        $this->arguments = array(
            "script" => end(explode("/", $this->rawArguments[0])),
            "command" => $this->rawArguments[1],
            "params" => $this->params
        );
        return $this->arguments;
    }
    private function dispatch() {
        if($this->arguments["command"] == "") $this->error("can't call empty command");
        
        $fileName = "{$this->arguments['script']}_{$this->arguments['command']}";
        $className = Inflector::camelize($fileName);
        
        if($command =& ClassRegistry::load($className, "Command")):
            if(can_call_method($command, "execute")):
                call_user_func_array(array($command, "execute"), $this->arguments["params"]);
            else:
                $this->error("can't execute command {$className}");
            endif;
        else:
            $this->error("command {$className} not found");
        endif;
    }
}

?>