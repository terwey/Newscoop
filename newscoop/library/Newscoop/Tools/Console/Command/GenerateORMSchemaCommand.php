<?php
/**
 * @package Newscoop
 * @author Yorick Terweijden <yorick.terweijden@sourcefabric.org>
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

define("DIR_SEP", DIRECTORY_SEPARATOR);

/**
 * Install newscoop with command line
 */
class GenerateORMSchemaCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('newscoop:generateOrmSchema')
            ->setDescription('Generates SQL for an ORM Entity')
            ->addArgument('entity', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Single or Multiple Entities');
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->getService('em');

        foreach ($input->getArgument('entity') as $entity) {
            $classMetaData[] = $em->getClassMetadata($entity);   
        }
        
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schema = $tool->getCreateSchemaSql($classMetaData, true);
        $output->writeln($schema);
    }
}
