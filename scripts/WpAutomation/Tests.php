<?php
namespace WpAutomation;

use Composer\Script\Event;
use mikehaertl\shellcommand\Command;
use GetOpt\GetOpt;
use GetOpt\Option;

class Tests
{

    protected static $pluginsPath = 'wp-content/plugins';
    protected static $pluginsWithTests = [];
    protected static $rootDir = null;

    private static $_climate = null;
    private static $_getOpt= null;


    public static function generateFiles(Event $event)
    {

        self::init($event);
        
        /* 
           cd wp-src && wp config set DB_HOST $DB_TEST_HOST && 
           cd /var/www/html && wp scaffold plugin-tests <plugin-name> --force --ci=circle && 
           cd - && wp config set DB_HOST $DB_HOST
         */
        $tplCommand = 'cd wp-src && ' .
            'wp config set DB_HOST %1$s && ' .
            'cd /var/www/html && ' .
            'wp scaffold plugin-tests %2$s --force --ci=circle && ' .
            'cd - && wp config set DB_HOST %3$s';

        $args = $event->getArguments();

        self::getOpt()->process($args);
        self::executeArg('plugin', function (\GetOpt\Option $option) use ($tplCommand) {
            $commandToRun = sprintf($tplCommand, $_ENV['DB_TEST_HOST'], $option->getValue(), $_ENV['DB_HOST']);
            $command = new Command($commandToRun);
            if ($command->execute()) {
                self::climate()->blue($command->getOutput());
            } else {
                self::climate()->red($command->getError());
                exit($command->getExitCode());
            }
        });
    }

    public static function install(Event $event)
    {
        self::init($event);

        $dbCredentials = [
            'database' => $_ENV['DB_TEST_NAME'],
            'username' => 'root',
            'password' => $_ENV['DB_ROOT_PASSWORD'],
            'host' => $_ENV['DB_TEST_HOST']
        ];

        if (isset($_ENV['CI']) || isset($_ENV['CIRCLECI'])) {
            $dbCredentials['password'] = isset($_ENV['CI_DB_ROOT_PASSWORD']) ? $_ENV['CI_DB_ROOT_PASSWORD'] : '';
            $dbCredentials['host'] = isset($_ENV['CI_DB_TEST_HOST']) ? $_ENV['CI_DB_TEST_HOST'] : '127.0.0.1';
        }

        // install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
        $tplCommand = "%s/bin/install-wp-tests.sh " . implode($dbCredentials, ' ');

        $args = $event->getArguments();

        $options = self::configureArguments([
            [
                'name' => 'plugin',
                'mode' => GetOpt::OPTIONAL_ARGUMENT
            ]
        ]);

        self::getOpt($options)->process($args);

        $pluginName = self::getOpt()->getOption('plugin');

        if (!empty($pluginName)) {

            self::climate()->green("Installing tests of: '$pluginName' plugin\n");

            $pluginDir = self::$rootDir . "/" . self::$pluginsPath . "/" . $pluginName;
            $commandToRun = sprintf($tplCommand, $pluginDir);

            $command = new Command($commandToRun);
            if ($command->execute()) {
                self::climate()->blue($command->getStdErr());
            } else {
                self::climate()->red($command->getError());
                exit($command->getExitCode());
            }

        } else {
            $pluginsFullPath = self::$rootDir . "/" . self::$pluginsPath;
            $pluginsDir = new \DirectoryIterator($pluginsFullPath);

            self::climate()->green("Get all plugins with php unit test structure from: $pluginsFullPath");

            foreach ($pluginsDir as $dir) {
                if ($dir->isDot()) continue;

                if ($dir->isDir() && file_exists($dir->getRealPath() . '/bin/install-wp-tests.sh')) {

                    self::climate()->green("Installing tests of: '" . $pluginsDir->getBasename() . "' plugin\n");
                    $commandToRun = sprintf($tplCommand, $dir->getRealPath());

                    $command = new Command($commandToRun);
                    if ($command->execute()) {
                        self::climate()->blue($command->getStdErr());
                        self::$pluginsWithTests[$dir->getBasename()] = true;
                    } else {
                        self::climate()->red($command->getError());
                        exit($command->getExitCode());
                    }
                }
            }

            if (count(self::$pluginsWithTests) === 0) {
                self::climate()->red('No scripts /bin/install-wp-tests.sh for install phpunit into plugins folder were found');
                self::climate()->red('Please, run: ' . self::climate()->bold('wp scaffold plugin-tests before'));
                exit(2);
            }
        }
    }

    public static function run(Event $event)
    {
        self::init($event);

        $options = self::configureArguments([
            [
                'name' => 'plugin'
            ],
            [
                'name' => 'install'
            ]
        ]);

        $args = $event->getArguments();
        self::getOpt()->process($args);
        
        $installArg = self::getOpt()->getOption('install');

        if (!empty($installArg)) {
            
            $newEvent = self::removeArgument('--install', $event);
            self::install($newEvent);
        }

        self::executeArg('plugin', function($pluginName) {
            $pluginPath = self::$rootDir . "/" . self::$pluginsPath . "/" . $pluginName;

            $commandToRun = "phpunit --debug --colors=always --configuration $pluginPath/phpunit.xml.dist";
            $command = new Command($commandToRun);
            if ($command->execute()) {
                self::climate()->blue($command->getOutput());
            } else {
                self::climate()->lightRed($command->getError() . "\n");
                self::climate()->red($command->getOutput());
                exit($command->getExitCode());
            }
        });
    }

    protected static function init(Event $event = null)
    {

        if (!empty($event)) {
            $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
            require_once $vendorDir . '/autoload.php';
            self::$rootDir = dirname($vendorDir);

            $args = $event->getArguments();
            self::getOpt()->process($args);
            
            self::executeArg('default-tests-dir', function() {
                $phpunitLibDir = self::$rootDir . "/vendor/wp-phpunit/wp-phpunit";
                $WP_TESTS_DIR = getenv('WP_TESTS_DIR');

                if (empty($WP_TESTS_DIR) && is_dir($phpunitLibDir)) {
                    putenv("WP_TESTS_DIR=" . $phpunitLibDir);
                }
            });
        }

        return self::$rootDir;
    }

    protected static function climate() : \League\CLImate\CLImate
    {
        if (self::$_climate == null) {
            self::$_climate = new \League\CLImate\CLImate;
        }

        self::$_climate->forceAnsiOn();
        return self::$_climate;
    }

    protected static function configureArguments(array $restrictArgs = null) : array
    {
        $resultArguments = [];
        $defaultArguments = [
            // Add --plugin argument
            Option::create('p', 'plugin', GetOpt::REQUIRED_ARGUMENT)
                    ->setDescription('Pass a wp plugin name localized in ' . self::$pluginsPath)
                    ->setArgumentName('plugin-name')
                    ->setValidation(function ($pluginName) {
                        $pluginDir = self::$rootDir . "/" . self::$pluginsPath . "/" . $pluginName;
                        return file_exists($pluginDir);
                    }, 'The plugin "%2$s" was not found in: ' . self::$pluginsPath),
            // Add --install argument
            Option::create('i', 'install', GetOpt::OPTIONAL_ARGUMENT)
                    ->setDescription('Run "install-wp-tests.sh" script to configure phpunit tests for wp plugins')
                    ->setArgumentName('install-phpunit-tests'),
            // Add --default-tests-dir argument, to configure /vendor/wp-phpunit/wp-phpunit like phpunit lib (optionally)
            Option::create('d', 'default-tests-dir', GetOpt::OPTIONAL_ARGUMENT)
                    ->setDescription('Run "install-wp-tests.sh" script to configure phpunit tests for wp plugins')
                    ->setArgumentName('default-phpunit-lib-directory')
        ];

        if ($restrictArgs && count($restrictArgs) > 0) {
            foreach ($restrictArgs as $newArgSetting) {

                foreach ($defaultArguments as $opt) {
                
                    if (isset($newArgSetting['name'])) {
    
                        if ($opt->getName() == $newArgSetting['name']) {
                            if (isset($newArgSetting['mode'])) {
    
                                $opt->setMode($newArgSetting['mode']);
                            }
                            array_push($resultArguments, $opt);
                        }
                    }
                }
            }
        }

        return !empty($resultArguments) ? $resultArguments : $defaultArguments;
    }

    protected static function removeArgument(string $argName, Event $event): Event
    {
        $args = $event->getArguments();
        $argIndex = array_search($argName, $args);
        if ($argIndex >= 0) {
            $argIndex++;
        }

        $newArgs = array_slice($args, $argIndex);
        return new Event($event->getName(), $event->getComposer(), $event->getIO(), $event->isDevMode(), $newArgs);
    }

    protected static function getOpt(array $arguments = null) : GetOpt
    {
        if (self::$_getOpt=== null) {
            $arguments = $arguments ?: self::configureArguments();
            self::$_getOpt= new GetOpt($arguments);
        }else if ($arguments) {

            //Erase and force the arguments, if passed by param
            self::$_getOpt = new GetOpt($arguments);
        }

        return self::$_getOpt;
    }

    protected static function executeArg(String $arg, \Closure $action)
    {
        $option = self::getOpt()->getOption($arg, true);

        if ($option->getMode() === GetOpt::REQUIRED_ARGUMENT) {
            if (empty($option->getValue())) {
                throw new \GetOpt\ArgumentException\Missing("The argument '$arg' is required!");
            }
        }

        if (!empty($option)) {
            $action($option);
        }
    }
}
