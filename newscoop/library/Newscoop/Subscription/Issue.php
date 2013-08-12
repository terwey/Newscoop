<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Subscription;

use Doctrine\ORM\Mapping AS ORM;
use Newscoop\Entity\Issue as IssueEntity;

/**
 * Subscription Issue relation entity
 * @ORM\Entity(repositoryClass="Newscoop\Entity\Repository\SubscriptionIssueRepository")
 * @ORM\Table(name="SubscriptionIssue")
 */
class Issue
{
    /**
     * @ORM\Id 
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Subscription\Subscription", inversedBy="sections")
     * @ORM\JoinColumn(name="IdSubscription", referencedColumnName="Id")
     * @var Newscoop\Subscription\Subscription
     */
    private $subscription;

    /**
     * @ORM\Column(type="integer", name="issue_number")
     * @var int
     */
    private $issueNumber;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Issue")
     * @ORM\JoinColumn(name="issue_number", referencedColumnName="Number")
     * @var Newscoop\Entity\Issue
     */
    private $issue;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Language")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    private $language;

    /**
     * @ORM\Column(type="date", name="StartDate")
     * @var DateTime
     */
    private $startDate;

    /**
     * @ORM\Column(type="integer", name="Days")
     * @var int
     */
    private $days;

    /**
     * @ORM\Column(type="integer", name="PaidDays")
     * @var int
     */
    private $paidDays;

    /**
     * @ORM\Column(name="NoticeSent")
     * @var string
     */
    private $noticeSent;

    /**
     * @param Subscription $subscription
     * @param IssueEntity $issue
     */
    public function __construct(Subscription $subscription, IssueEntity $issue)
    {
        $this->subscription = $subscription;
        $this->subscription->addIssue($this);

        $this->issueNumber = $issue->getNumber();
        $this->issue = $issue;
        $this->noticeSent = 'N';
        $this->paidDays = 0;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get issue
     *
     * @return string
     */
    public function getIssue()
    {
        return $this->issue;
    }

    /**
     * Get issueNumber
     * 
     * @return int
     */
    public function getIssueNumber()
    {
        return $this->issueNumber;
    }

    /**
     * Set language
     *
     * @param Newscoop\Entity\Language $language
     * @return void
     */
    public function setLanguage(\Newscoop\Entity\Language $language)
    {
        $this->language = $language;
    }

    /**
     * Get language id
     *
     * @return int
     */
    public function getLanguageId()
    {
        try {
            return $this->language ? $this->language->getId() : 0;
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return 0;
        }
    }

    /**
     * Get language name
     *
     * @return string
     */
    public function getLanguageName()
    {
        try {
            return $this->language ? $this->language->getName() : '';
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return '';
        }
    }

    /**
     * Get language
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Test if has language set
     *
     * @return bool
     */
    public function hasLanguage()
    {
        return $this->language !== null;
    }

    /**
     * Set start date
     *
     * @param DateTime $date
     * @return Newscoop\Entity\SubscriptionSection
     */
    public function setStartDate(\DateTime $date)
    {
        $this->startDate = $date;
    }

    /**
     * Get start date
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set days
     *
     * @param int $days
     * @return Newscoop\Entity\SubscriptionSection
     */
    public function setDays($days)
    {
        $this->days = abs($days);
        return $this;
    }

    /**
     * Get days
     *
     * @return int
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set paid days
     *
     * @param int $paidDays
     * @return Newscoop\Entity\SubscriptionSection
     */
    public function setPaidDays($paidDays)
    {
        $this->paidDays = abs($paidDays);
    }

    /**
     * Get paid days
     *
     * @return int
     */
    public function getPaidDays()
    {
        return $this->paidDays;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        if ($this->subscription->getPublication() === null) {
            return '';
        }

        return $this->issue->getName();
    }

    /**
     * Get subscription
     *
     * @return Newscoop\Subscription\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }
    
    /**
     * Get expiration date
     * @return DateTime
     */
    public function getExpirationDate() {
        $startDate = isset($this->startDate) ? $this->startDate : new \DateTime('now');
        $timeSpan = new \DateInterval('P' . $this->days . 'D');
        return $startDate->add($timeSpan);
    }
}
