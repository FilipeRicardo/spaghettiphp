<?php

// defines the root directory
define('SPAGHETTI_ROOT', dirname(__DIR__));

// adds the root directory to the include path
set_include_path(SPAGHETTI_ROOT . PATH_SEPARATOR . get_include_path());

// includes spaghetti.common
require 'lib/spaghetti/common/Config.php';
require 'lib/spaghetti/common/Inflector.php';
require 'lib/spaghetti/common/Utils.php';
require 'lib/spaghetti/common/String.php';
require 'lib/spaghetti/common/Filesystem.php';
require 'lib/spaghetti/common/Hookable.php';

// includes and initializes spaghetti.debug
require 'lib/spaghetti/debug/Debug.php';

// includes spaghetti.dispatcher
require 'lib/spaghetti/dispatcher/Dispatcher.php';
require 'lib/spaghetti/dispatcher/Mapper.php';

// includes spaghetti.model
require 'lib/spaghetti/model/Model.php';

// includes spaghetti.controller
require 'lib/spaghetti/controller/Controller.php';

// includes spaghetti.view
require 'lib/spaghetti/view/View.php';

// includes application's files
require 'app/controllers/app_controller.php';
require 'app/models/app_model.php';