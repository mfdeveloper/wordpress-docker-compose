<?php
    namespace WpAutomation;
    use Composer\Script\Event;

    class Tests {

        protected static $pluginsPath = 'wp-content/plugins';
        protected static $pluginsWithTests = [];
        protected static $rootDir = null;
        private static $_climate = null;

        public static function install(Event $event, $pluginName = '')
        {
            self::init($event);
            
            // install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
            $tplCommand = "%s/bin/install-wp-tests.sh {$_ENV['DB_TEST_NAME']} root {$_ENV['DB_ROOT_PASSWORD']} {$_ENV['DB_TEST_HOST']}";

            if (!empty($pluginName)) {
                $pluginDir = self::$rootDir . "/" . self::$pluginsPath . "/" . $pluginName;
                if (file_exists($pluginDir)) {
                    
                    self::climate()->green("Installing tests of: '$pluginName' plugin\n");
                    $command = sprintf($tplCommand, $pluginDir);
                    $retVal = 0;

                    $result = system($command, $retVal);
                    if ($result && $retVal != 1) {
                        self::climate()->blue($result);
                    }else{
                        self::climate()->red($result);
                    }
                }
            }else{
                $pluginsDir = new \DirectoryIterator(self::$rootDir . "/" . self::$pluginsPath);
    
                foreach ($pluginsDir as $dir) {
                    if($dir->isDot()) continue;
    
                    if ($dir->isDir() && file_exists($dir->getRealPath().'/bin/install-wp-tests.sh')) {
                        
                        self::climate()->green("Installing tests of: '" . $pluginsDir->getBasename() . "' plugin\n");
                        $command = sprintf($tplCommand, $dir->getRealPath());
                        $result = shell_exec($command);
                        if (preg_match_all('/(Warning)|(Fatal Error)/', $result)) {
                            self::climate()->red($result);
                        }else{
                            self::climate()->blue($result);
                        }
    
                        self::$pluginsWithTests[$dir->getBasename()] = true;
                    }
                }
    
                if ( count(self::$pluginsWithTests) === 0 ) {
                    self::climate()->red('No scripts /bin/install-wp-tests.sh for install phpunit into plugins folder were found');
                    self::climate()->red('Please, run: '. self::climate()->bold('wp scaffold plugin-tests before'));
                }
            }
        }

        public static function run(Event $event)
        {
            self::init($event);
            $args = $event->getArguments();
            
            if (count($args) > 0) {
                if (!in_array('--plugin', $args)) {
                    throw new \Exception('The argument "--plugin" is required!');
                }
                
                $key = array_search('--plugin', $args);

                if (!array_key_exists($key + 1, $args)) {
                    throw new \Exception('The argument --plugin require a wordpress plugin name string');
                }

                $pluginName = $args[$key + 1];
                $pluginPath = self::$rootDir . "/" . self::$pluginsPath . "/" . $pluginName;
                if (!is_dir($pluginPath)) {
                    throw new \Exception("The plugin: '$pluginPath' does not exists");
                }
                
                if(in_array('--install', $args)) {
                    self::install($event, $pluginName);
                }
                $command = "phpunit --debug --colors=always --configuration $pluginPath/phpunit.xml.dist";
                $result = shell_exec($command);
                if (preg_match_all('/(Warning)|(Fatal Error)/', $result)) {
                    self::climate()->red($result);
                }else{
                    self::climate()->blue($result);
                }
            }else{
                throw new \Exception('The argument "--plugin" is required!');
            }
        }

        protected static function init(Event $event = null)
        {
            if (!empty($event)) {
                $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
                require_once $vendorDir . '/autoload.php';
                self::$rootDir = dirname($vendorDir);

                $args = $event->getArguments();
                if(!in_array('--default-tests-dir', $args)) {
                    $phpunitLibDir = self::$rootDir . "/vendor/wp-phpunit/wp-phpunit";
                    $WP_TESTS_DIR = getenv('WP_TESTS_DIR');
                    
                    if (empty($WP_TESTS_DIR) && is_dir($phpunitLibDir)) {
                        putenv("WP_TESTS_DIR=" . $phpunitLibDir);
                    }
                }
            }

            return self::$rootDir;
        }

        protected static function climate(): \League\CLImate\CLImate
        {
            if (self::$_climate == null) {
                self::$_climate = new \League\CLImate\CLImate;
            }
            return self::$_climate;
        }
    }
