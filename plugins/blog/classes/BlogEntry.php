<?php
/**
 * @package Campsite
 *
 * @author Sebastian Goebel <sebastian.goebel@web.de>
 * @copyright 2008 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.campware.org
 */

class BlogEntry extends DatabaseObject {
    /**
	 * The column names used for the primary key.
	 * @var array
	 */
    var $m_keyColumnNames       = array('entry_id');
    var $m_keyIsAutoIncrement   = true;
    var $m_dbTableName          = 'plugin_blog_entry';

    var $m_columnNames = array(
        'entry_id',
        'fk_blog_id',
        'fk_user_id',
        'published',
        'released',
        'status',
        'title',
        'content',
        'tags',
        'mood',
        'admin_status',
        'comments_online',
        'comments_offline',
        'feature'
    );

    static $m_html_allowed_fields = array('content');
    static $m_html_allowed_tags = '<strong><em><u><a><img><p>';

    /**
	 * Construct by passing in the primary key to access the article in
	 * the database.
	 *
	 * @param int $p_languageId
	 * @param int $p_articleNumber
	 *		Not required when creating an article.
	 */
    function BlogEntry($p_entry_id=null, $p_blog_id=null)
    {
        parent::DatabaseObject($this->m_columnNames);

        $this->m_data['entry_id'] = $p_entry_id;

        if ($this->keyValuesExist()) {
            $this->fetch();
            #$this->m_data['images'] = BlogEntry::_getImagePaths($p_entry_id, true);

        } elseif ($p_blog_id) {
            $this->m_data['fk_blog_id'] = $p_blog_id;
        }
    } // constructor

    function setProperty($p_name, $p_value)
    {
        parent::setProperty($p_name, $p_value);

        if ($p_name == 'status' || $p_name == 'admin_status') {
            require_once 'Blog.php';
            Blog::triggerCounter($this->getProperty('fk_blog_id'));
        }
    }

    /**
	 * A way for internal functions to call the superclass create function.
	 * @param array $p_values
	 */
    function __create($p_values=null) { return parent::create($p_values); }


    function create($p_blog_id, $p_user_id, $p_title=null, $p_content=null, $p_tags=null, $p_mood=null)
    {
        // Create the record
        $values = array(
        'fk_blog_id'    => $p_blog_id,
        'fk_user_id'    => $p_user_id,
        'title'         => $p_title,
        'content'       => $p_content,
        'tags'          => $p_tags,
        'mood'          => $p_mood
        );

        $success = parent::create($values);

        if (!$success) {
            return false;
        }

        $this->fetch();

        require_once 'Blog.php';
        Blog::triggerCounter($p_blog_id);

        return true;
    }


    function delete()
    {
        $entry_id = $this->getProperty('entry_id');
        $blog_id = $this->getProperty('fk_blog_id');
        
        foreach (BlogComment::getComments(array('entry_id' => $this->getProperty('entry_id'))) as $Comment) {
            $Comment->delete();
        }

        parent::delete();

        #BlogEntry::_removeImage($entry_id);
        Blog::triggerCounter($blog_id);
    }

    function getData()
    {
        return $this->m_data;
    }

    function _buildQueryStr($p_cond, $p_checkParent)
    {
        $Blog = new Blog();
        $blogs_tbl = $Blog->m_dbTableName;
        
        $BlogEntry = new BlogEntry();
        $entries_tbl = $BlogEntry->m_dbTableName;
        
        if (array_key_exists('fk_blog_id', $p_cond)) {
            $cond .= " AND e.fk_blog_id = {$p_cond['fk_blog_id']}";
        }

        if (array_key_exists('fk_user_id', $p_cond)) {
            $cond .= " AND e.fk_user_id = {$p_cond['fk_user_id']}";
        }

        if (array_key_exists('status', $p_cond)) {
            $cond .= BlogEntry::_buildSubQuery($p_cond, 'status', $p_checkParent);
        }

        if (array_key_exists('admin_status', $p_cond)) {
            $cond .= BlogEntry::_buildSubQuery($p_cond, 'admin_status', $p_checkParent);
        }

        if (array_key_exists('GROUP BY', $p_cond)) {
            $groupby = 'GROUP BY '.$p_cond['GROUP BY'];
        }

        if (array_key_exists('SELECT', $p_cond)) {
            $select = 'SELECT '.$p_cond['SELECT'];
        } else {
            $select = 'SELECT e.entry_id';
        }

        $queryStr = "$select
                     FROM       $entries_tbl AS e, 
                                $blogs_tbl  AS b
                     WHERE      e.fk_blog_id = b.blog_id 
                                $cond
                     $groupby
                     ORDER BY   entry_id DESC";
        return $queryStr;
    }

    function _buildSubQuery($p_cond, $p_key, $p_checkParent)
    {
        if (is_array($p_cond[$p_key])) {

            foreach ($p_cond[$p_key] as $v) {
                $subcond .= "e.{$p_key} = '{$v}' OR ";
                if ($p_checkParent) $subcond2 .= "b.{$p_key} = '{$v}' OR ";
            }

            $cond .= " AND (".substr($subcond, 0, -3).")";
            if (subcond2) $cond .=  " AND (".substr($subcond2, 0, -3).")";

        } else {
            $cond .= " AND e.{$p_key} = '{$p_cond[$p_key]}'";
            if ($p_checkParent) $cond .= " AND b.{$p_key} = '{$p_cond[$p_key]}'";
        }

        return $cond;
    }

    function getEntries($p_cond=array(), $p_currPage=0, $p_perPage=20, $p_checkParent=false)
    {
        global $g_ado_db;

        $queryStr   = BlogEntry::_buildQueryStr($p_cond, $p_checkParent);
        $query      = $g_ado_db->SelectLimit($queryStr, $p_perPage, ($p_currPage-1) * $p_perPage);
        $entries    = array();

        while ($row = $query->FetchRow()) {
            $tmpEntry =& new BlogEntry($row['entry_id']);
            $entries[] = $tmpEntry;
        }

        return $entries;
    }

    function countEntries($p_cond=array(), $p_checkParent=false)
    {
        global $g_ado_db;

        $queryStr = BlogEntry::_buildQueryStr($p_cond, $p_checkParent);

        $query = $g_ado_db->Execute($queryStr);

        return $query->RecordCount();
    }

    function triggerCounter($p_entry_id)
    {
        global $g_ado_db;
        
        if (!$p_entry_id) {
            return false;   
        }
        
        $BlogEntry = new BlogEntry();
        $entryTbl = $BlogEntry->m_dbTableName;

        $BlogComment = new BlogComment();
        $commentTbl = $BlogComment->m_dbTableName;
        
        $queryStr = "UPDATE $entryTbl
                     SET    comments_online = 
                        (SELECT COUNT(comment_id) 
                         FROM   $commentTbl
                         WHERE  fk_entry_id = $p_entry_id AND status = 'online' AND admin_status = 'online'),
                            comments_offline = 
                        (SELECT COUNT(comment_id) 
                         FROM   $commentTbl
                         WHERE  fk_entry_id = $p_entry_id AND (status != 'online' OR admin_status != 'online'))
                     WHERE  entry_id = $p_entry_id";  
        $g_ado_db->Execute($queryStr);
    }

    function getBlogId($p_entry_id)
    {
        $tmpEntry =& new BlogEntry($p_entry_id);
        return $tmpEntry->getProperty('fk_blog_id');
    }

    function _getFormMask($p_admin=false, $p_owner=false)
    {
        $data = $this->getData();

        foreach ($data as $k => $v) {
            // clean user input
            if (!in_array($k, BlogEntry::$m_html_allowed_fields)) {
                $data[$k] = html_entity_decode_array($v);
            }
        }

        $mask = array(
        'action'    => array(
        'element'   => 'action',
        'type'      => 'hidden',
        'constant'  => $data['entry_id']? 'entry_edit' : 'entry_create'
        ),
        'entry_id'    => $data['entry_id'] ? array(
        'element'   => 'entry_id',
        'type'      => 'hidden',
        'constant'  => $data['entry_id'],
        'required'  => true
        ) : null,
        'fk_blog_id'    => $data['fk_blog_id'] ? array(
        'element'   => 'fk_blog_id',
        'type'      => 'hidden',
        'constant'  => $data['fk_blog_id'],
        'required'  => true
        ) : null,
        'page'      => $_REQUEST['page'] ? array(
        'element'   => 'page',
        'type'      => 'hidden',
        'constant'  => $_REQUEST['page']
        ) : null,
        'tiny_mce'  => array(
        'element'   => 'tiny_mce',
        'text'      => '<script language="javascript" type="text/javascript" src="/phpwrapper/tiny_mce/tiny_mce.js"></script>'.
        '<script language="javascript" type="text/javascript">'.
        '     tinyMCE.init({'.
        '     	mode : "exact",'.
        '        elements : "tiny_mce_box",'.
        '        theme : "advanced",'.
        '        plugins : "emotions, paste", '.
        '        paste_auto_cleanup_on_paste : true, '.
        '        theme_advanced_buttons1 : "bold, italic, underline, undo, redo, link, emotions", '.
        '        theme_advanced_buttons2 : "", '.
        '        theme_advanced_buttons3 : "" '.
        '     });'.
        '</script>',
        'type'      => 'static'
        ),
        'title'     => array(
        'element'   => 'BlogEntry[title]',
        'type'      => 'text',
        'label'     => 'Titel',
        'default'   => $data['title'],
        'required'  => true
        ),
        'content'      => array(
        'element'   => 'BlogEntry[content]',
        'type'      => 'textarea',
        'label'     => 'Eintrag',
        'default'   => $data['content'],
        'required'  => true,
        'attributes'=> array('cols' => 40, 'rows' => 5, 'id' => 'tiny_mce_box')
        ),
        /*
        'tags'      => array(
        'element'   => 'BlogEntry[tags]',
        'type'      => 'checkbox_multi',
        'label'     => 'tags',
        'default'   => explode(', ', $data['tags']),
        'options'   => $this->_getTagList()
        ),
        'mood'      => array(
        'element'   => 'BlogEntry[mood]',
        'type'      => 'checkbox_multi',
        'label'     => 'mood',
        'default'   => explode(', ', $data['mood']),
        'options'   => $this->_getmoodList()
        )
        */
        'Image'     => array(
        'element'   => 'BlogEntry_Image',
        'type'      => 'file',
        'label'     => 'Image (.jpg)',
        ),
        'Image_remove' => array(
        'element'   => 'BlogEntry_Image_remove',
        'type'      => 'checkbox',
        'label'     => 'entfernen',
        ),
        );

        if ($p_owner && $data['entry_id']) {
            $mask += array(
            'status' => array(
            'element'   => 'BlogEntry[status]',
            'type'      => 'select',
            'label'     => 'status',
            'default'   => $data['status'],
            'options'   => array(
            'online'    => 'online',
            'offline'   => 'offline'
            ),
            'required'  => true
            )
            );
        };

        if ($p_admin) {
            $mask += array(
            'admin_status' => array(
            'element'   => 'BlogEntry[admin_status]',
            'type'      => 'select',
            'label'     => 'Admin status',
            'default'   => $data['admin_status'],
            'options'   => array(
            'pending'   => 'pending',
            'online'    => 'online',
            'offline'   => 'offline',
            ),
            'required'  => true
            )
            );
        };

        $mask += array(
        /*
        $p_admin ? null : 'captcha_image' => array(
        'element'       => 'captcha_image',
        'type'          => 'image',
        'src'           => '/look/img/captcha/0f60a7c97b199d88d028c8f483e.jpg',
        'attributes'    => array('onclick' => 'return false')
        ),
        $p_admin ? null : 'captcha'       => array(
        'element'       => 'captcha',
        'type'          => 'text',
        'label'         => 'Code:',
        'required'      => true,
        'requiredmsg'   => 'Bitte die Zeichenfolge auf dem Bild in das darunterliegende Feld eingeben.',
        'attributes'    => array('class' => 'verschicken'),
        ),
        */
        'reset'     => array(
        'element'   => 'reset',
        'type'      => 'reset',
        'label'     => 'Zurücksetzen',
        'groupit'   => true
        ),
        'xsubmit'     => array(
        'element'   => 'xsubmit',
        'type'      => 'button',
        'label'     => 'Abschicken',
        'attributes'=> array('onclick' => 'this.form.submit()'),
        'groupit'   => true
        ),
        'cancel'     => array(
        'element'   => 'cancel',
        'type'      => 'button',
        'label'     => 'Cancel',
        'attributes' => array('onClick' => 'history.back()'),
        'groupit'   => true
        ),
        'buttons'   => array(
        'group'     => array('xsubmit', 'reset')
        )
        );

        return $mask;
    }

    function getForm($p_target, $p_add_hidden_vars=array(), $p_admin=false, $p_owner=false, $p_html=false)
    {
        require_once 'HTML/QuickForm.php';
        require_once $_SERVER['DOCUMENT_ROOT'].'/phpwrapper/functions.php';

        $mask = $this->_getFormMask($p_admin, $p_owner);
        #mergePostParams(&$mask);

        foreach ($p_add_hidden_vars as $k => $v) {
            $mask[] = array(
            'element'   => $k,
            'type'      => 'hidden',
            'constant'  => $v
            );
        }

        $form =& new html_QuickForm('blog_entry', 'post', $p_target, null, null, true);
        parseArr2Form(&$form, &$mask);

        if ($p_html) {
            return $form->toHTML();
        } else {
            require_once 'HTML/QuickForm/Renderer/Array.php';

            $renderer =& new HTML_QuickForm_Renderer_Array(true, true);
            $form->accept($renderer);

            return $renderer->toArray();
        }
    }

    function store($p_admin=false, $p_owner=false, $p_user_id)
    {
        require_once 'HTML/QuickForm.php';
        require_once $_SERVER['DOCUMENT_ROOT'].'/phpwrapper/functions.php';

        $mask = $this->_getFormMask($p_admin, $p_owner);
        #mergePostParams(&$mask);

        $form =& new html_QuickForm('blog_entry', 'post', '', null, null, true);
        parseArr2Form(&$form, &$mask);

        if ($form->validate()){
            $data = $form->getSubmitValues(true);

            foreach ($data['BlogEntry'] as $k => $v) {
                // clean user input
                if (in_array($k, BlogEntry::$m_html_allowed_fields)) {
                    $data['BlogEntry'][$k] = strip_tags($v, Blog::$m_html_allowed_tags);
                } else {
                    $data['BlogEntry'][$k] = htmlspecialchars($v);
                }
            }

            if ($data['entry_id']) {
                foreach ($data['BlogEntry'] as $k => $v) {
                    if (is_array($v)) {
                        foreach($v as $key => $value) {
                            if ($value) {
                                $string .= "$key, ";
                            }
                        }
                        $v = substr($string, 0, -2);
                        unset ($string);

                    }
                    $this->setProperty($k, $v);
                }

                if ($data['BlogEntry_Image_remove']) BlogEntry::_removeImage($data['entry_id']);
                if ($data['BlogEntry_Image'])        BlogEntry::_storeImage($data['BlogEntry_Image'], $data['entry_id']);

                Blog::triggerCounter(BlogEntry::getBlogId($data['entry_id']));

                return true;

            } else {
                if (is_array($data['BlogEntry']['mood'])) {
                    unset ($string);
                    foreach($data['BlogEntry']['mood'] as $key => $value) {
                        if ($value) {
                            $string .= "$key, ";
                        }
                    }
                    $mood = substr($string, 0, -2);
                }
                if (is_array($data['BlogEntry']['tags'])) {
                    unset ($string);
                    foreach($data['BlogEntry']['tags'] as $key => $value) {
                        if ($value) {
                            $string .= "$key, ";
                        }
                    }
                    $tags = substr($string, 0, -2);
                }
                if ($this->create($data['fk_blog_id'], $p_user_id, $data['BlogEntry']['title'], $data['BlogEntry']['content'], $tags, $mood)) {
                    if ($p_owner && $data['BlogEntry']['status'])       $this->setProperty('status', $data['BlogEntry']['status']);
                    if ($p_admin && $data['BlogEntry']['admin_status'])  $this->setProperty('admin_status', $data['BlogEntry']['admin_status']);

                    if ($data['BlogEntry_Image_remove']) BlogEntry::_removeImage($this->getProperty('entry_id'));
                    if ($data['BlogEntry_Image'])        BlogEntry::_storeImage( $data['BlogEntry_Image'], $this->getProperty('entry_id'));

                    Blog::triggerCounter($this->getProperty('fk_blog_id'));

                    return true;
                }
                return false;
            }

        }
        return false;

    }

    function _getImageFormates()
    {
        return array (42 => 42, 90 => 90, 205 => 205);
    }

    function _getImagePaths($p_entry_id, $p_check_exists=false)
    {
        foreach (BlogEntry::_getImageFormates() as $width => $height) {
            $rel_path[$width.'x'.$height] = BLOGENTRY_IMG_PATH."{$width}x{$height}/{$p_entry_id}.jpg";

            if ($p_check_exists) {
                if (!file_exists($_SERVER['DOCUMENT_ROOT'].$rel_path[$width.'x'.$height])) unset ($rel_path[$width.'x'.$height]);
            }
        }

        return $rel_path;
    }

    function _storeImage($p_image, $p_entry_id)
    {
        if ($p_image['error'] == 0 && preg_match('/^image\/(p)?jp(e)?g$/', $p_image['type'])) {

            foreach (BlogEntry::_getImagePaths($p_entry_id) as $dim => $rel_path) {
                list ($width, $height) = explode('x', $dim);
                $d_width = $width * 2;
                $d_height = $width * 2;
                $abs_path = "{$_SERVER['DOCUMENT_ROOT']}$rel_path";

                if (!@opendir($abs_path)) {
                    $mkdir = $_SERVER['DOCUMENT_ROOT'];
                    foreach(explode('/', dirname($rel_path)) as $k => $dir) {
                        $mkdir .= '/'.$dir;
                        @mkdir($mkdir, 0775);
                    }
                }

                $cmd = "convert -resize {$d_width}x -resize 'x{$d_height}<' -resize 50% -gravity center  -crop {$width}x{$height}+0+0 +repage {$p_image['tmp_name']} $abs_path";
                passthru($cmd, $return_value);
            }

            return $return_value;
        }
        return false;
    }

    function _removeImage($p_entry_id)
    {
        foreach (BlogEntry::_getImagePaths($p_entry_id, true) as $path) {
            unlink($_SERVER['DOCUMENT_ROOT'].$path);
        }
    }

    function _getTagList()
    {
        return array('a' => 'film', 'b' => 'poesie', 'm' => 'multimedia');
    }

    function _getmoodList()
    {
        return array('a' => 'happy', 'b' => 'sad');
    }

    function setis_onfrontpage()
    {
        if ($OldEntry = BlogEntry::getis_onfrontpageEntry()) {
            $OldEntry->setProperty('is_onfrontpage', 0);
        }

        $this->setProperty('is_onfrontpage', 1);
    }

    function unsetis_onfrontpage()
    {
        $this->setProperty('is_onfrontpage', 0);
    }

    static function getis_onfrontpageEntry()
    {
        global $g_ado_db;

        $BlogEntry = new BlogEntry();
        $tblName = $BlogEntry->m_dbTableName;

        $query = "SELECT    entry_id
                  FROM      `{$tblName}`
                  WHERE     is_onfrontpage = 1
                  LIMIT     0, 1";
        $res = $g_ado_db->execute($query);

        if ($row = $res->fetchRow()) {
            $Entry = new BlogEntry($row['entry_id']);
            return $Entry;
        }
        return false;
    }
    
    
    /**
     * Get the blogentry identifier
     *
     * @return int
     */
    public function getId()
    {
        return $this->getProperty('entry_id');   
    }
    
    
        /////////////////// Special template engine methods below here /////////////////////////////
    
    /**
     * Gets an blog list based on the given parameters.
     *
     * @param array $p_parameters
     *    An array of ComparisonOperation objects
     * @param string $p_order
     *    An array of columns and directions to order by
     * @param integer $p_start
     *    The record number to start the list
     * @param integer $p_limit
     *    The offset. How many records from $p_start will be retrieved.
     *
     * @return array $issuesList
     *    An array of Issue objects
     */
    public static function GetList($p_parameters, $p_order = null, $p_start = 0, $p_limit = 0, &$p_count)
    {
        global $g_ado_db;
        
        if (!is_array($p_parameters)) {
            return null;
        }
        
        $selectClauseObj = new SQLSelectClause();

        // sets the where conditions
        foreach ($p_parameters as $param) {
            $comparisonOperation = self::ProcessListParameters($param);
            if (empty($comparisonOperation)) {
                continue;
            }
            
            $whereCondition = $comparisonOperation['left'] . ' '
            . $comparisonOperation['symbol'] . " '"
            . $comparisonOperation['right'] . "' ";
            $selectClauseObj->addWhere($whereCondition);
        }
        
        // sets the columns to be fetched
        $tmpBlogEntry = new BlogEntry();
		$columnNames = $tmpBlogEntry->getColumnNames(true);
        foreach ($columnNames as $columnName) {
            $selectClauseObj->addColumn($columnName);
        }

        // sets the main table for the query
        $mainTblName = $tmpBlogEntry->getDbTableName();
        $selectClauseObj->setTable($mainTblName);
        unset($tmpBlogEntry);
                
        if (is_array($p_order)) {
            $order = self::ProcessListOrder($p_order);
            // sets the order condition if any
            foreach ($order as $orderField=>$orderDirection) {
                $selectClauseObj->addOrderBy($orderField . ' ' . $orderDirection);
            }
        }
       
        $sqlQuery = $selectClauseObj->buildQuery();
        
        // count all available results
        $countRes = $g_ado_db->Execute($sqlQuery);
        $p_count = $countRes->recordCount();
        
        //get tlimited rows
        $blogEntryRes = $g_ado_db->SelectLimit($sqlQuery, $p_limit, $p_start);
        
        // builds the array of blog objects
        $blogEntriesList = array();
        while ($blogEntry = $blogEntryRes->FetchRow()) {
            $blogEntryObj = new BlogEntry($blogEntry['entry_id']);
            if ($blogEntryObj->exists()) {
                $blogEntriesList[] = $blogEntryObj;
            }
        }

        return $blogEntriesList;
    } // fn GetList
    
    /**
     * Processes a paremeter (condition) coming from template tags.
     *
     * @param array $p_param
     *      The array of parameters
     *
     * @return array $comparisonOperation
     *      The array containing processed values of the condition
     */
    private static function ProcessListParameters($p_param)
    {
        $comparisonOperation = array();

        $comparisonOperation['left'] = BlogEntriesList::$s_parameters[strtolower($p_param->getLeftOperand())]['field'];

        if (isset($comparisonOperation['left'])) {
            $operatorObj = $p_param->getOperator();
            $comparisonOperation['right'] = $p_param->getRightOperand();
            $comparisonOperation['symbol'] = $operatorObj->getSymbol('sql');
        }

        return $comparisonOperation;
    } // fn ProcessListParameters

                                      
    /**
     * Processes an order directive coming from template tags.
     *
     * @param array $p_order
     *      The array of order directives
     *
     * @return array
     *      The array containing processed values of the condition
     */
    private static function ProcessListOrder(array $p_order)
    {                                      
        $order = array();
        foreach ($p_order as $field=>$direction) {
            $dbField = BlogEntriesList::$s_parameters[substr($field, 2)]['field'];

            if (!is_null($dbField)) {
                $direction = !empty($direction) ? $direction : 'asc';
            }
            $order[$dbField] = $direction;
        }
        if (count($order) == 0) {
            $order['fk_blog_id'] = 'asc';
            $order['entry_id'] = 'asc';  
        }
        return $order;
    }
}
?>