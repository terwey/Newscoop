<?php

/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Newscoop\Entity\Comment\Commenter;

/**
 * Comment entity
 * @ORM\Table(name="comment")
 * @ORM\Entity(repositoryClass="Newscoop\Entity\Repository\CommentRepository")
 */
class Comment
{
    private $allowedEmpty = array( 'br', 'input', 'image' );

    private $allowedTags =
    array(
        'a' => array('title', 'href'),
        'abbr' => array('title'),
        'acronym' => array('title'),
        'b' => array(),
        'blockquote' => array('cite'),
        'cite' => array(),
        'code' => array(),
        'del' => array('datetime'),
        'em' => array(),
        'i' => array(),
        'q' => array('cite'),
        'p' => array(),
        'br' => array(),
        'strike' => array(),
        'strong' => array());

    /**
     * Constants for status
     */

    const STATUS_APPROVED   = 0;
    const STATUS_PENDING    = 1;
    const STATUS_HIDDEN     = 2;
    const STATUS_DELETED    = 3;
    /**
     * @var string to code mapper for status
    static $status_enum = array(
    self::STATUS_APPROVED,
    self::STATUS_PENDING,
    self::STATUS_HIDDEN,
    self::STATUS_DELETED
    );
     */
    /**
     * @var string to code mapper for status
     */
    static $status_enum = array('approved', 'pending', 'hidden', 'deleted');

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Comment\Commenter", inversedBy="comments", cascade={"persist"})
     * @ORM\JoinColumn(name="fk_comment_commenter_id", referencedColumnName="id")
     * @var Newscoop\Entity\Comment\Commenter
     */
    private $commenter;

    /**
     * @ORM\ManyToOne(targetEntity="Publication")
     * @ORM\JoinColumn(name="fk_forum_id", referencedColumnName="Id")
     * @var Newscoop\Entity\Publication
     */
    private $forum;

    /**
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumn(name="fk_parent_id", referencedColumnName="id", onDelete="SET NULL")
     * @var Newscoop\Entity\Comment
     */
    private $parent;

    /**
     * TODO get rid of this when the composite key stuff is done.
     *
     * @ORM\Column(type="integer", name="fk_thread_id")
     * @var int
     */
    private $article_num;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Article")
     * @ORM\JoinColumn(name="fk_thread_id", referencedColumnName="Number")
     * @var Newscoop\Entity\Article
     */
    private $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\Entity\Language")
     * @ORM\JoinColumn(name="fk_language_id", referencedColumnName="Id")
     * @var Newscoop\Entity\Language
     */
    private $language;

    /**
     * @ORM\Column(length=140)
     * @var string
     */
    private $subject;

    /**
     * @ORM\Column()
     * @var text
     */
    private $message;

    /**
     * @ORM\Column(length=4)
     * @var int
     */
    private $thread_level;

    /**
     * @ORM\Column(length=4)
     * @var int
     */
    private $thread_order;

    /**
     * @ORM\Column(length=2)
     * @var int
     */
    private $status;

    /**
     * @ORM\Column(length=39)
     * @var int
     */
    private $ip;

    /**
     * @ORM\Column(type="datetime", name="time_created")
     * @var DateTime
     */
    private $time_created;

    /*
     * @ORM\Column(type="datetime", name="time_updated")
     * @var DateTime
     */
    private $time_updated;

    /**
     * @ORM\Column(length=4)
     * @var int
     */
    private $likes = 0;

    /**
     * @ORM\Column(length=4)
     * @var int
     */
    private $dislikes = 0;

    /**
     * @ORM\Column(length=1)
     * @var int
     */
    private $recommended = 0;

    public function __construct()
    {
        $this->setTimeCreated(new \DateTime());
    }

    /**
     * @ORM\Column(type="datetime", nullable=True)
     * @var DateTime
     */
    private $indexed;

    /**
     * @ORM\Column(type="string", length=60, name="source", nullable=true)
     * @var string
     */
    private $source;

    /**
     * Set id
     *
     * @param int $p_id
     * @return Newscoop\Entity\Comment
     */
    public function setId($p_id)
    {
        return $this->id;
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
     * TODO get rid of this when the composite key stuff is done.
     *
     * @return int
     */
    public function getArticleNumber()
    {
        return $this->article_num;
    }

    /**
     * Set timecreated
     *
     * @param DateTime $p_datetime
     * @return Newscoop\Entity\Comment
     */
    public function setTimeCreated(\DateTime $p_datetime)
    {
        $this->time_created = $p_datetime;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get creation time.
     *
     * @return DateTime
     */
    public function getTimeCreated()
    {
        return $this->time_created;
    }

    /**
     * Set time updated
     *
     * @param DateTime $p_datetime
     * @return Newscoop\Entity\Comment
     */
    public function setTimeUpdated(\DateTime $p_datetime)
    {
        $this->time_updated = $p_datetime;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get creation time.
     *
     * @return DateTime
     */
    public function getTimeUpdated()
    {
        return $this->time_updated;
    }

    /**
     * Set comment subject.
     *
     * @param string $p_subject
     * @return Newscoop\Entity\Comment
     */
    public function setSubject($p_subject)
    {
        $this->subject = (string)$p_subject;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get comment subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set comment message.
     *
     * @param string $p_message
     * @return Newscoop\Entity\Comment
     */
    public function setMessage($p_message)
    {
        $this->message = $this->formatMessage((string)$p_message);
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get comment message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set comment ip address
     *
     * @param string $p_ip
     * @return Newscoop\Entity\Comment
     */
    public function setIp($p_ip)
    {
        // remove subnet & limit to IP_LENGTH
        $ip_array = explode('/', (string)$p_ip);
        $this->ip = substr($ip_array[0], 0, 39);
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get comment ip address
     *
     * @return string
     */
    public function getIp()
    {
        if (is_numeric($this->ip)) { // try to use old format
            static $max = 0xffffffff; // 2^32
            if ($this->ip > 0 && $this->ip < $max) {
                return long2ip($this->ip);
            }
        }
        return (string)$this->ip;
    }

    /**
     * Set recommended
     *
     * @param string $p_recommended
     * @return Newscoop\Entity\Comment
     */
    public function setRecommended($p_recommended)
    {
        if ($p_recommended) $this->recommended = 1;
        else $this->recommended = 0;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get comment recommended
     *
     * @return string
     */
    public function getRecommended()
    {
        return (string)$this->recommended;
    }

    /**
     * Set commenter
     *
     * @param Newscoop\Entity\Comment\Commenter $p_commenter
     * @return Newscoop\Entity\Comment
     */
    public function setCommenter(Commenter $p_commenter)
    {
        $this->commenter = $p_commenter;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get commenter
     *
     * @return Newscoop\Entity\Comment\Commenter
     */
    public function getCommenter()
    {
        return $this->commenter;
    }

    /**
     * Get commenter name
     *
     * @return string
     */
    public function getCommenterName()
    {
        return $this->getCommenter()->getName();
    }

    /**
     * Get commenter email
     *
     * @return string
     */
    public function getCommenterEmail()
    {
        return $this->getCommenter()->getEmail();
    }

    /**
     * Set status string
     *
     * @return Newscoop\Entity\Comment
     */
    public function setStatus($p_status)
    {
        $status_enum = array_flip(self::$status_enum);
        $this->status = $status_enum[$p_status];
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get status string
     *
     * @return string
     */
    public function getStatus()
    {
        return self::$status_enum[$this->status];
    }

    /**
     * Set forum
     *
     * @return Newscoop\Entity\Comment
     */
    public function setForum(Publication $p_forum)
    {
        $this->forum = $p_forum;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get thread
     *
     * @return Newscoop\Entity\Publication
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * Set thread
     *
     * @return Newscoop\Entity\Comment
     */
    public function setThread(Article $p_thread)
    {
        $this->thread = $p_thread;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get thread
     *
     * @return Newscoop\Entity\Articles
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * Set thread level
     *
     * @return Newscoop\Entity\Comment
     */
    public function setThreadLevel($p_level)
    {
        $this->thread_level = $p_level;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get thread level
     *
     * @return integer
     */
    public function getThreadLevel()
    {
        return $this->thread_level;
    }

    /**
     * Set thread order
     *
     * @return Newscoop\Entity\Comment
     */
    public function setThreadOrder($p_order)
    {
        $this->thread_order = $p_order;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get thread level
     *
     * @return integer
     */
    public function getThreadOrder()
    {
        return $this->thread_order;
    }

    /**
     * Set Language
     *
     * @return Newscoop\Entity\Comment
     */
    public function setLanguage(Language $p_language)
    {
        $this->language = $p_language;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get Language
     *
     * @return Newscoop\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set Parent
     *
     * @return Newscoop\Entity\Comment
     */
    public function setParent(Comment $p_parent = null)
    {
        $this->parent = $p_parent;
        // return this for chaining mechanism
        return $this;
    }

    /**
     * Get Parent
     *
     * @return Newscoop\Entity\Comment
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get the likes count
     *
     * @return int
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * Get the dislikes count
     *
     * @return int
     */
    public function getDislikes()
    {
        return $this->dislikes;
    }

    /**
     * Get username witch should be the real name
     *
     * @return string
     * @deprecated legacy from frontend controllers
     */
    public function getRealName()
    {
        $this->getCommenter()->getUserName();
    }

    /**
     * Check if the comment is the same as this one
     *
     * @param Comment $p_comment
     * @return bool
     * @deprecated legacy from frontend controllers
     */
    public function SameAs($p_comment)
    {
        if (is_object($p_comment)) {
            return $p_comment->getId() == $this->getId();
        }

        return false;
    }

    /**
     * Check if the comment exists
     * Test if there is set an id
     *
     * @return bool
     * @deprecated legacy from frontend controllers
     */
    public function exists()
    {
        return !is_null($this->id);
    }

    /**
     * Get an enity property
     *
     * @param $p_key
     * @return mixed
     * @deprecated legacy from frontend controllers
     */
    public function getProperty($p_key)
    {
        if (isset($this->$p_key)) {
            return $this->$p_key;
        } else {
            return null;
        }
    }

    /**
     * Method used to format message from comments
     *
     * @param string $str
     * @return string
     */
    protected function formatMessage($str)
    {
        $parts = explode('<', $str);
        // if no < was found then return the original string
        if (count($parts) === 1) {
            return $str;
        }
        /** @type array vector where the tag list are keeped */
        $tag = array();
        $attrib = array();
        $contentAfter = array(0 => $parts[0]);
        for ($i = 1, $counti = count($parts); $i < $counti; $i++) {
            $tagAndContent = explode('>', $parts[$i], 2);
            $tagAndAttrib = explode(' ', $tagAndContent[0], 2);

            if (isset($tagAndAttrib[1])) {
                /**
                 * @todo make a better attributes filter regex
                 *      this is breaking on not quotes define attibutes
                 *      ex: like checked=true for good parsing should be checked="true" or checked='true'
                 */
                preg_match_all("#([^=]+)=\s*(['\"])?([^\\2]*)\\2#iU", $tagAndAttrib[1], $rez);
                if (isset($rez[1])) {
                    for ($k = 0, $countk = count($rez[1]); $k < $countk; $k++) {
                        $attrib[$i][] = array(strtolower(trim($rez[1][$k])), $rez[3][$k]);
                    }
                }
            }
            $tag[$i] = $tagAndAttrib[0];
            $contentAfter[$i] = isset($tagAndContent[1]) ? $tagAndContent[1] : '';
        }
        $closed = $tag;
        $return = '';
        $allowedNameTags = array_keys($this->allowedTags);
        for ($i = 0, $counti = count($contentAfter); $i < $counti; $i++) {
            $isClosed = isset($tag[$i]) ? (substr($tag[$i], 0, 1) == '/') : false;
            if(isset($tag[$i])) {
                $tagName = $tag[$i];
                if(substr($tagName,-1,1) == '/') {
                   $tagName = substr($tagName, 0, -1);
                }
            }
            if (isset($tag[$i]) && (in_array($tagName, $allowedNameTags))) {
                unset($closed[$i]);
                $good = array_search('/' . $tag[$i], $closed, true);
                if ($good) {
                    unset($closed[$good]);
                    $composeTag = '<' . $tag[$i] . ' ';
                    if (isset($attrib[$i])) {
                        for ($j = 0, $countj = count($attrib[$i]); $j < $countj; $j++) {
                            if ($attrib[$i][$j][0] == 'href') {
                            }
                            $attrib[$i][$j][1] = preg_replace('/(javascript[:]?)/i', '', $attrib[$i][$j][1]);
                            if (in_array($attrib[$i][$j][0], $this->allowedTags[$tag[$i]])) {
                                $composeTag .= $attrib[$i][$j][0] . '="' . $attrib[$i][$j][1] . '" ';
                            }
                        }
                    }
                    $return .= substr($composeTag, 0, -1) . '>' . $contentAfter[$i];
                } else {
                    $composeTag = '<' . $tag[$i] . ' ';
                    $title = false;
                    $cite = false;
                    if (isset($attrib[$i])) {
                        for ($j = 0, $countj = count($attrib[$i]); $j < $countj; $j++) {
                            if (in_array($attrib[$i][$j][0], $this->allowedTags[$tag[$i]])) {
                                if ($attrib[$i][$j][0] == 'href') {
                                    $attrib[$i][$j][1] = preg_replace('/(javascript[:]?)/i', '', $attrib[$i][$j][1]);
                                }
                                if ($attrib[$i][$j][0] == 'title') {
                                    $title = $attrib[$i][$j][1];
                                } elseif ($attrib[$i][$j][0] == 'cite') {
                                    $cite = $attrib[$i][$j][1];
                                } else {
                                    $composeTag .= $attrib[$i][$j][0] . '="' . $attrib[$i][$j][1] . '" ';
                                }
                            }
                        }
                    }
                    // if title is set and is a broken tag use the title like inline text
                    if( in_array($tagName, $this->allowedEmpty)) {
                        $return .= substr($composeTag, 0, -1) . '>' . $contentAfter[$i];
                    }
                    elseif ($title !== false) {
                        $return .= substr($composeTag, 0,
                                          -1) . '>' . $title . '</' . $tag[$i] . '>' . $contentAfter[$i];
                    } // if cite is set and is a broken tag use the cite like inline text
                    elseif ($cite !== false) {
                        $return .= substr($composeTag, 0, -1) . '>' . $cite . '</' . $tag[$i] . '>' . $contentAfter[$i];
                    } // else use the text after
                    else {
                        $return .= substr($composeTag, 0, -1) . '>' . $contentAfter[$i] . '</' . $tag[$i] . '>';
                    }
                }
            } elseif (isset($tag[$i]) && $isClosed && in_array(substr($tag[$i], 1), $allowedNameTags)) {
                unset($closed[$i]);
                $return .= '<' . $tag[$i] . '>' . $contentAfter[$i];
            } else {
                $return .= $contentAfter[$i];
            }
        }
        return $return;
    }

    /**
     * Set indexed
     *
     * @param DateTime $indexed
     * @return void
     */
    public function setIndexed(\DateTime $indexed = null)
    {
        $this->indexed = $indexed;
    }

    /**
     * Get indexed
     *
     * @return DateTime
     */
    public function getIndexed()
    {
        return $this->indexed;
    }

    /**
     * Get comment source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set comment source
     *
     * @param  string $source
     *
     * @return string
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}
