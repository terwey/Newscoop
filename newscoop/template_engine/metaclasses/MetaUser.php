<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Newscoop\Entity\User;

/**
 * Template user
 */
final class MetaUser extends MetaEntity
{
    /** @var Newscoop\Entity\User */
    protected $user;

    /** @var int */
    public $identifier;

    /** @var string */
    public $uname;

    /** @var string */
    public $name;

    /** @var string */
    public $first_name;

    /** @var string */
    public $last_name;

    /** @var string */
    public $email;

    /** @var bool */
    public $defined;

    /** @var string */
    public $created;

    /**
     * @param Newscoop\Entity\User $user
     */
    public function __construct(User $user = NULL)
    {
        $this->user = $user;
        if (!$user) {
            return;
        }

        $this->identifier = $user->getId();
        $this->uname = $user->getUsername();
        $this->email = $user->getEmail();

        $this->first_name = $user->getFirstName();
        $this->last_name = $user->getLastName();
        $this->name = trim($user->getFirstName() . ' ' . $user->getLastName());

        $this->defined = $user->getId() > 0;
        $this->created = $user->getCreated()->format('d.m.Y');
    }

    /**
     * Get user attribute value
     *
     * @param string $property
     */
    public function __get($property)
    {
        try {
            return parent::__get($property);
        } catch (\InvalidArgumentException $e) {
            return (!$this->user) ? null : $this->user->getAttribute($property);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $url = $GLOBALS['controller']->view->url(array('username' => $this->uname), 'user');

        if ($this->user->isPublic()) {
            return "<a href='{$url}'>{$this->name}</a>";
        }
        else {
            return $this->name;
        }
    }

    /**
     * Get subscription
     *
     * @return MetaSubscription
     */
    public function subscription()
    {
        if (empty($this->user)) {
            return new MetaSubscription();
        }

        $publicationId = CampTemplate::singleton()->context()->publication->identifier;
        $subscriptions = Subscription::GetSubscriptions($publicationId, $this->user->getId());
        return empty($subscriptions) ? new MetaSubscription() : new MetaSubscription($subscriptions[0]->getSubscriptionId());
    }

    /**
     * Get the name of the country of the registered user
     *
     * @return string
     */
    public function country()
    {
        require_once dirname(__FILE__) . '/../../classes/Country.php';
        require_once dirname(__FILE__) . '/../../classes/Language.php';

        $countryCode = $this->user->getAttribute('country_code');
        $smartyObj = CampTemplate::singleton();
        $contextObj = $smartyObj->get_template_vars('gimme');
        $country = new Country($countryCode, $contextObj->language->number);
        return !$country->exists() ? '' : $country->getName();
    }

    /**
     * Test if user has permission
     *
     * @param string $permission
     * @return boolean
     */
    public function has_permission($permission)
    {
        return $this->user->hasPermission($permission);
    }

    /**
     * Test if user is admin
     *
     * @return bool
     */
    public function is_admin()
    {
        return $this->user->isAdmin();
    }

    /**
     * Test if user is logged in
     *
     * @return bool
     */
    public function logged_in()
    {
        $auth = Zend_Auth::getInstance();
        return $auth->hasIdentity() && $auth->getIdentity() == $this->user->getId();
    }

    /**
     * Test if user is blocked from commenting
     *
     * @return bool
     */
    public function is_blocked_from_comments()
    {
        require_once dirname(__FILE__) . '/../../include/get_ip.php';

        global $controller;

        $userIp = getIp();
        $publication_id = CampTemplate::singleton()->context()->publication->identifier;
        $repositoryAcceptance = $controller->getHelper('user')->getRepository('Newscoop\user\Comment\Acceptance');
        $repository = $controller->getHelper('user')->getRepository('Newscoop\user\Comment');
        return (int) $repositoryAcceptance->checkParamsBanned($this->name, $this->email, $userIp, $publication_id);
    }

    /**
     * Get image src
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    public function image($width = 80, $height = 80)
    {
        if (!$this->user->getImage()) {
            return '';
        }

        return $GLOBALS['controller']->getHelper('service')->getService('image')
            ->getSrc($this->user->getImage(), $width, $height);
    }

    /**
     * Get topics
     *
     * @return array
     */
    public function topics()
    {
        if (!$this->user->getId()) {
            return array();
        }

        $service = $GLOBALS['controller']->getHelper('service')->getService('user.topic');
        $topics = array();
        foreach ($service->getTopics($this->user) as $topic) {
            $topics[$topic->getTopicId()] = $topic->getName();
        }

        return $this->topics = $topics;
    }
}
