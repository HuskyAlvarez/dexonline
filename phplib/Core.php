<?php

class Core {

  private static $wwwRoot;
  private static $rootPath;
  private static $tempPath;

  static function autoloadLibClass($className) {
    $filename = self::getRootPath() . 'phplib' . DIRECTORY_SEPARATOR . $className . '.php';
    if (file_exists($filename)) {
      require_once $filename;
    }
  }

  static function autoloadModelsClass($className) {
    $filename = self::getRootPath() . 'phplib' . DIRECTORY_SEPARATOR . 'models' .
              DIRECTORY_SEPARATOR . $className . '.php';
    if (file_exists($filename)) {
      require_once $filename;
    }
  }

  static function init() {
    mb_internal_encoding('UTF-8');
    setlocale(LC_ALL, 'ro_RO.utf8');

    spl_autoload_register(); // clear the autoload stack
    spl_autoload_register('Core::autoloadLibClass', false, true);
    spl_autoload_register('Core::autoloadModelsClass', false, true);

    self::defineRootPath();
    self::defineWwwRoot();
    self::defineTempPath();
    self::requireOtherFiles();
    DB::init();
    Session::init(); // init Session before SmartyWrap: SmartyWrap caches the person's nickname.
    if (!Request::isAjax()) {
      FlashMessage::restoreFromSession();
    }
    SmartyWrap::init();
    DebugInfo::init();
    if (Request::isWeb() && Config::get('global.maintenanceMode')) {
      SmartyWrap::display('maintenance.tpl', true);
      exit;
    }
    self::initAdvancedSearchPreference();
  }

  static function initAdvancedSearchPreference() {
    $advancedSearch = Session::userPrefers(Preferences::SHOW_ADVANCED);
    SmartyWrap::assign('advancedSearch', $advancedSearch);
  }

  static function defineRootPath() {
    $ds = DIRECTORY_SEPARATOR;
    $fileName = realpath($_SERVER['SCRIPT_FILENAME']);
    $pos = strrpos($fileName, "{$ds}wwwbase{$ds}");
    // Some offline scripts, such as dict-server.php, run from the tools or phplib directories.
    if ($pos === FALSE) {
      $pos = strrpos($fileName, "{$ds}tools{$ds}");
    }
    if ($pos === FALSE) {
      $pos = strrpos($fileName, "{$ds}phplib{$ds}");
    }
    if ($pos === FALSE) {
      $pos = strrpos($fileName, "{$ds}app{$ds}");
    }
    self::$rootPath = substr($fileName, 0, $pos + 1);
  }

  /**
   * Returns the absolute path of the dexonline folder in the file system.
   */
  static function getRootPath() {
    return self::$rootPath;
  }

  /**
   * Returns the home page URL path.
   * Algorithm: compare the current URL with the absolute file name.
   * Travel up both paths until we encounter /wwwbase/ in the file name.
   **/
  static function defineWwwRoot() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $fileName = realpath($_SERVER['SCRIPT_FILENAME']);
    $pos = strrpos($fileName, '/wwwbase/');
  
    if ($pos === false) {
      $result = '/';     // This shouldn't be the case
    } else {
      $tail = substr($fileName, $pos + strlen('/wwwbase/'));
      $lenTail = strlen($tail);
      if ($tail == substr($scriptName, -$lenTail)) {
        $result = substr($scriptName, 0, -$lenTail);
      } else {
        $result = '/';
      }
    }
    self::$wwwRoot = $result;
  }

  /**
   * Returns the root URL for dexonline (since it could be running in a subdirectory).
   */
  static function getWwwRoot() {
    return self::$wwwRoot;
  }

  static function getImgRoot() {
    return self::getWwwRoot() . 'img'; 
  }

  static function getCssRoot() {
    return self::getWwwRoot() . 'css'; 
  }

  static function requireOtherFiles() {
    $root = self::getRootPath();
    require_once StringUtil::portable("$root/phplib/third-party/smarty/Smarty.class.php");
    require_once StringUtil::portable("$root/phplib/third-party/idiorm/idiorm.php");
    require_once StringUtil::portable("$root/phplib/third-party/idiorm/paris.php");
  }

  static function getTempPath() {
    return self::$tempPath;
  }
  
  static function defineTempPath() {
    $temp = Config::get('global.tempDir', sys_get_temp_dir());
    if ( is_dir( $temp ) && is_writable( $temp ) ) {
      self::$tempPath = $temp;
    } else {
      throw new Exception('Directorul temporar specificat nu poate fi accesat.');    
    }
  }
}

Core::init();
