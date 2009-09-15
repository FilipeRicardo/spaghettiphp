<?php
/**
 *  A classe View é responsável por extrair o conteúdo enviado pelo controller
 *  e associá-lo à view e layout correspondentes, fazendo também a inclusão
 *  dos helpers necessários.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2009, Spaghetti* Framework (http://spaghettiphp.org/)
 *
 */
    
class View extends Object {
    /**
     *  Helpers utilizados pela view, definidos no controller
     */
    public $helpers = array();
    /**
     *  Array que armazena os helpers carregados
     */
    public $loadedHelpers = array();
    /**
     *  Nome do layout
     */
    public $layout;
    /**
     *  Título da página HTML
     */
    public $pageTitle;
    /**
     *  Renderização automática do layout
     */
    public $autoLayout = true;
    /**
     *  Variáveis definidas no controller para serem passadas para a view.
     */
    public $viewData = array();
    /**
     *  Short description.
     */
    public $params = array();

    public function __construct(&$controller = null) {
        if($controller):
            $this->params = $controller->params;
            $this->layout = $controller->layout;
            $this->autoLayout = $controller->autoLayout;
            $this->set($controller->viewData);
            $this->helpers = $controller->helpers;
        endif;
    }
    /**
     *  Faz a inclusão dos helpers necessários solicitados anteriormente pelo
     *  controller e que agora serão usados pela view.
     * 
     *  @return array Array de instâncias dos helpers
     */
    public function loadHelpers() {
        foreach($this->helpers as $helper):
            $class = "{$helper}Helper";
            $this->loadedHelpers[Inflector::underscore($helper)] = ClassRegistry::load($class, "Helper");
            if(!$this->loadedHelpers[Inflector::underscore($helper)]):
                $this->error("missingHelper", array("helper" => $helper));
                return false;
            endif;
        endforeach;
        return $this->loadedHelpers;
    }
    /**
     *  O método View::renderView() recebe o resultado do processamento do
     *  controlador atual e renderiza a view correspondente, retornando um HTML
     *  estático do conteúdo solicitado. Chama também o método responsável por
     *  extrair os helpers e associá-los ao view.
     * 
     *  @param string $filename Nome do arquivo de view
     *  @param array $extract_vars Variáveis a serem passadas para a view
     *  @return string HTML da view renderizada
     */
    public function renderView($filename = null, $extractVars = array()) {
        if(!is_string($filename)):
            return false;
        endif;
        if(empty($this->loadedHelpers) && !empty($this->helpers)):
            $this->loadHelpers();
        endif;
        $extractVars = is_array($extractVars) ? array_merge($extractVars, $this->loadedHelpers) : $this->loadedHelpers;
        extract($extractVars, EXTR_SKIP);
        ob_start();
        include $filename;
        $out = ob_get_clean();
        return $out;
    }
    /**
     *  View::render() é responsável por receber uma ação, um controlador e
     *  um layout e fazer as inclusões necessárias para a renderização da tela,
     *  chamando outros métodos para renderizar o view e o layout.
     * 
     *  @param string $action Nome da ação a ser chamada
     *  @param string $layout Nome do arquivo de layout
     *  @return string HTML final da renderização.
     */
    public function render($action = null, $layout = null) {
        if($action === null):
            $action = "{$this->params['controller']}/{$this->params['action']}";
            $ext = $this->params['extension'];
        else:
            $filename = preg_split("/\./", $action);
            $action = $filename[0];
            $ext = $filename[1] ? $filename[1] : "htm";
        endif;
        $filename = App::path("View", "{$action}.{$ext}");
        if($filename):
            $out = $this->renderView($filename, $this->viewData);
            if($this->autoLayout && $this->layout):
                $layout = $layout === null ? "{$this->layout}.{$ext}" : $layout;
                $out = $this->renderLayout($out, $layout);
            endif;
            return $out;
        else:
            $this->error("missingView", array("controller" => $this->params['controller'], "view" => $action, "extension" => $ext));
            return false;
        endif;
    }
    /**
     *  O método View::render_layout() faz o buffer e a renderização do layout
     *  requisitado, incluindo a view correspondente a requisição atual e passando
     *  as variáveis definidas no controlador. Retorna o HTML processado, sem PHP.
     * 
     *  @param string $content Conteúdo a ser passado para o layout
     *  @param string layout Nome do arquivo de layout
     *  @return string HTML do layout renderizado
     */
    public function renderLayout($content = null, $layout = null) {
        if($layout === null):
            $layout = $this->layout;
            $ext = $this->params['extension'];
        else:
            $filename = preg_split("/\./", $layout);
            $layout = $filename[0];
            $ext = $filename[1] ? $filename[1] : "htm";
        endif;
        $filename = App::path("Layout", "{$layout}.{$ext}");
        $this->contentForLayout = $content;
        if($filename):
            $out = $this->renderView($filename, $this->viewData);
            return $out;
        else:
            $this->error("missingLayout", array("layout" => $layout, "extension" => $ext));
            return false;
        endif;
    }
    /**
     *  O método View::element() retorna o buffer do carregamento de um elemento,
     *  que são arquivos de views que são repetidos muitas vezes, e podem assim
     *  estar em um arquivo só. Isto é bastante útil para trechos repetidos de
     *  código PHTML, para que nem seja necessário criar um novo layout nem repetir
     *  este trecho a cada arquivo onde seja necessário.
     * 
     *  @param string $element Nome do arquivo elemento
     *  @param array $params Parâmetros opcionais a serem passados para o elemento
     *  @return string Buffer do arquivo solicitado
     */
    public function element($element = null, $params = array()) {
        $ext = $this->params['extension'] ? $this->params['extension'] : "htm";
        $element = dirname($element) . DS . "_" . basename($element);
        return $this->renderView(App::path("View", "{$element}.{$ext}"), $params);
    }
    /**
     *  View::set() é o método que grava as variáveis definidas no
     *  controlador que serão passadas para o view em seguida.
     * 
     *  @param mixed $var String com nome da variável ou array de variáveis e valores
     *  @param mixed $content Valor da variável, é aceito qualquer tipo de conteúdo
     *  @return mixed Retorna o conteúdo da variável gravada
     */
    public function set($var = null, $content = null) {
        if(is_array($var)):
            foreach($var as $key => $value):
                $this->set($key, $value);
            endforeach;
        elseif($var !== null):
            $this->viewData[$var] = $content;
            return $this->viewData[$var];
        endif;
        return false;
    }
}

?>