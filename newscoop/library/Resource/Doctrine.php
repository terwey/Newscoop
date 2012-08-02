<?php
/**
 * @package Resource
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;

/**
 * Doctrine Zend application resource
 */
class Resource_Doctrine extends \Zend_Application_Resource_ResourceAbstract
{
    /** @var Doctrine\ORM\EntityManager */
    private $em;

    /** @var Doctrine\ORM\EventManager */
    private $evm;

    /** @var Doctrine\ORM\EventManager */
    private $driverChain;

    /** @var DoctrineExtensions\Taggable\TagManager */
    private $tagManager;

    /**
     * Init doctrine
     */
    public function init()
    {
        Zend_Registry::set('doctrine', $this);
        return $this;
    }

    /**
     * Get Entity Manager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        global $Campsite;

        if ($this->em !== NULL && $this->em->isOpen()) {
            return $this->em;
        }

        $config = new Configuration();
        $options = $this->getOptions();

        $cache = new $options['cache'];

        // create a driver chain for metadata reading
        $this->driverChain = new Doctrine\ORM\Mapping\Driver\DriverChain();

        // register default annotation driver for Newscoop entities
        $defaultAnnotationDriver = $config->newDefaultAnnotationDriver(array(realpath($options['entity']['dir'])));
        $this->driverChain->addDriver($defaultAnnotationDriver);
        $this->driverChain->addDriver($defaultAnnotationDriver,'Newscoop\\Entity');

        $this->driverChain->addDriver($defaultAnnotationDriver,'Newscoop\\Image');
        $this->driverChain->addDriver($defaultAnnotationDriver,'Newscoop\\Package');

        $config->setMetadataDriverImpl($this->driverChain);

        // set proxy
        $config->setProxyDir(realpath($options['proxy']['dir']));
        $config->setProxyNamespace($options['proxy']['namespace']);
        $config->setAutoGenerateProxyClasses($options['proxy']['autogenerate']);

        // set cache
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $config_file = APPLICATION_PATH . '/../conf/database_conf.php';
        if (empty($Campsite) && file_exists($config_file)) {
            require_once $config_file;
        }

        // set database
        $database = array(
            'driver' => 'pdo_mysql',
            'host' => $Campsite['DATABASE_SERVER_ADDRESS'],
            'dbname' => $Campsite['DATABASE_NAME'],
            'user' => $Campsite['DATABASE_USER'],
            'password' => $Campsite['DATABASE_PASSWORD'],
            'driverOptions' => array(
                1002 => "SET NAMES 'UTF8'",
            ),
        );

        if (isset($options['database'])) {
            $database = $options['database'];
        }

        foreach ($options['functions'] as $function => $value)
            $config->addCustomNumericFunction(strtoupper($function), $value);

        $this->em = EntityManager::create($database, $config);

        //enable gedmo extension if configured
        if($options['gedmo']['enabled'] == true){
            $this->enableGedmoExtensions();
        }

        $conn = $this->em->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('point', 'string');
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('mediumblob', 'string');
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('geometry', 'string');
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('blob', 'string');

        return $this->em;
    }

    public function enableGedmoExtensions(){
        // autoload namespaces for Gedmo extensions
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\Mapping\Annotation',
            APPLICATION_PATH . '/vendor/gedmo/doctrine-extensions/lib'
        );

        $this->em->getEventManager()->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());
        $this->em->getEventManager()->addEventSubscriber(new \Gedmo\Sluggable\SluggableListener());
        $this->em->getEventManager()->addEventSubscriber(new \Gedmo\Tree\TreeListener());
        //$this->em->getEventManager()->addEventSubscriber(new \Gedmo\Loggable\LoggableListener());
    }
}
