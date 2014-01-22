<?php
/**
 * @package Newscoop
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Tools\Console\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Newscoop\Installer\Services;

define("DIR_SEP", DIRECTORY_SEPARATOR);

/**
 * Install newscoop with command line
 */
class InstallNewscoopCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('newscoop:install')
            ->setDescription('Update composer.json with plugins after Newscoop upgrade.')
            ->addArgument('alias', InputArgument::OPTIONAL, 'Newscoop instance alias', 'newscoop.dev')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'If set we will try to fix chmods')
            ->addOption('database_server_name', null, InputOption::VALUE_OPTIONAL, 'Database host', 'localhost')
            ->addOption('database_name', null, InputOption::VALUE_OPTIONAL, 'Database name', 'newscoop')
            ->addOption('database_user', null, InputOption::VALUE_OPTIONAL, 'Database user', 'root')
            ->addOption('database_password', null, InputOption::VALUE_REQUIRED, 'Database password')
            ->addOption('database_server_port', null, InputOption::VALUE_OPTIONAL, 'Database server port', '3306')
            ->addOption('database_override', null, InputOption::VALUE_NONE, 'Override existing database')
            ->addArgument('demo_template', InputArgument::OPTIONAL, 'Choose demo publication from: "quetzal, rockstar, the_new_custodian, no_demo"', 'quetzal')
            ->addArgument('site_title', InputArgument::OPTIONAL, 'Publication name', 'Newscoop newspaper')
            ->addArgument('user_email', InputArgument::OPTIONAL, 'Admin email', 'admin@newscoop.dev')
            ->addArgument('user_password', InputArgument::OPTIONAL, 'Admin user password', 'password');
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $output->writeln('<info>Welcome in Newscoop Installer.<info>');

        $symfonyRequirements = new \SymfonyRequirements();
        $requirements = $symfonyRequirements->getRequirements();

        $missingReq = array();
        foreach ($requirements as $req) {
            if (!$req->isFulfilled()) {
                $missingReq[] = $req->getTestMessage().' - '.$req->getHelpText();
            }
        }

        $fixCommonIssues = $input->getOption('fix');
        if (count($missingReq) > 0 && !$fixCommonIssues) {
            $output->writeln('<info>Before we start we need to fix some requirements.<info>');
            $output->writeln('<info>Please read all messages and try to fix them:<info>');
            foreach ($missingReq as $value) {
                $output->writeln('<error>'.$value.'<error>');
            }

            $output->writeln('<error>Use --fix param to fix those errors<error>');

            return;
        } elseif (count($missingReq) > 0 && $fixCommonIssues) {
            $newscoopDir = realpath(__DIR__ . '/../../../../../');
            // set chmods for directories
            exec('chmod -R 777 '.$newscoopDir.'/cache/');
            exec('chmod -R 777 '.$newscoopDir.'/log/');
            exec('chmod -R 777 '.$newscoopDir.'/conf/');
            exec('chmod -R 777 '.$newscoopDir.'/library/Proxy/');
            exec('chmod -R 777 '.$newscoopDir.'/themes/');
            exec('chmod -R 777 '.$newscoopDir.'/plugins/');
            exec('chmod -R 777 '.$newscoopDir.'/public/files/');
            exec('chmod -R 777 '.$newscoopDir.'/images/');
        }

        $dbParams = array(
            'driver'    => 'pdo_mysql',
            'charset'   => 'utf8',
            'host' => $input->getOption('database_server_name'),
            'dbname' => $input->getOption('database_name'),
            'port' => $input->getOption('database_server_port'),
        );

        if ($input->getOption('database_user')) {
            $dbParams['user'] = $input->getOption('database_user');
        }

        if ($input->getOption('database_password')) {
            $dbParams['password'] = $input->getOption('database_password');
        }

        $databaseService = new Services\DatabaseService($container->get('logger'));
        $finishService = new Services\FinishService();
        $demositeService = new Services\DemositeService($container->get('logger'));
        $connection = DriverManager::getConnection($dbParams);
        try {
            $connection->connect();
            if ($connection->getDatabase() === null) {
                $databaseService->createNewscoopDatabase($connection);
            }
        } catch (\Exception $e) {
            if ($e->getCode() == '1049') {
                $databaseService->createNewscoopDatabase($connection);
            } elseif (strpos($e->getMessage(), 'database exists') === false) {
                throw $e;
            }
        }

        $output->writeln('<info>Connected with database.<info>');

        $tables = $connection->fetchAll('SHOW TABLES', array());
        if (count($tables) == 0 || $input->getOption('database_override')) {
            $databaseService->fillNewscoopDatabase($connection);
            $databaseService->loadGeoData($connection);
            $databaseService->saveDatabaseConfiguration($connection);
        } else {
            throw new \Exception('There is already a database named ' . $connection->getDatabase() . '. If you are sure to overwrite it, use option --database_override. If not, just change the Database Name and continue.', 1);
        }
        $output->writeln('<info>Database is created successfully.<info>');

        if ($input->getArgument('demo_template') != 'no_demo') {
            $databaseService->installSampleData($connection, $input->getArgument('alias'));
            $output->writeln('<info>Sample data installed successfully.<info>');
           // $demositeService->copyTemplate('set_'.$input->getArgument('demo_template'));
           // $demositeService->installEmptyTheme();
            $output->writeln('<info>Template installed successfully.<info>');
        }

        $finishService->saveCronjobs();
        $output->writeln('<info>Cronjobs are saved.<info>');
        $finishService->generateProxies();
        $finishService->reloadRenditions();
        $finishService->installAssets();
        $output->writeln('<info>Assets are installed.<info>');
        $finishService->saveInstanceConfig(array(
            'site_title' => $input->getArgument('site_title'),
            'user_email' => $input->getArgument('user_email'),
            'recheck_user_password' => $input->getArgument('user_password')
        ), $connection);
        $output->writeln('<info>Config is saved.<info>');

        $output->writeln('<info>Newscoop is installed.<info>');
    }
}
