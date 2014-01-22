<?php
/**
 * @package Campsite
 *
 * @author Sebastian Goebel <devel@yellowsunshine.de>
 * @copyright 2010 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */

define('CAMP_FORM_INPUT_TEXT_STANDARD_SIZE', 50);
define('CAMP_FORM_INPUT_TEXT_STANDARD_MAXLENGTH', 256);
define('CAMP_FORM_TEXTAREA_STANDARD_ROWS', 8);
define('CAMP_FORM_TEXTAREA_STANDARD_COLS', 60);
define('CAMP_FORM_MISSINGNOTE', '$1');
define('CAMP_FORM_REQUIREDNOTE',  \Zend_Registry::get('container')->getService('translator')->trans('* Marked fields are mandatory.', array(), 'api'));
define('CAMP_FORM_JS_PREWARNING', \Zend_Registry::get('container')->getService('translator')->trans('The following fields are mandatory:', array(), 'api'));
define('CAMP_FORM_JS_POSTWARNING', '');

/**
 * This class provides functionality to build an form using Pear Quickform
 *
 */
class FormProcessor
{
     /**
     *  ParseArr2Form
     *
     *  Add elements/rules/groups to an given HTML_QuickForm object
     *
     *  @param form object, reference to HTML_QuickForm object
     *  @param mask array, reference to array defining to form elements
     *  @param side string, side where the validation should beeing
     */
    static public function ParseArr2Form(&$form, &$mask, $side='client')
    {
        foreach($mask as $k=>$v) {
            if (!is_array($v)) {
                $v = (array) $v;
            }

            $v += array(
                'groupit' => false,
                'label' => '',
                'required' => false,
                'rule' => '',
                'group' => '',
                'multiple' => array(),
                'text' => '',
                'attributes' => array(),
                'type' => '',
                'element' => '',
                'name' => '',
                'seperator' => '',
                'appendName' => '',
                'grouprule' => '',
            );

            ## set default classes for form elements #######
            $class = '';
            if (!empty($v['attributes']['class'])) {
                $class = $v['attributes']['class'].' ';
            }
            switch ($v['type']) {
                case 'radio':
                    $v['attributes']['class'] = $class.'input_radio';
                break;

                case 'checkbox':
                case 'checkbox_multi':
                    $v['attributes']['class'] = $class.'input_checkbox';
                break;

                case 'select':
                case 'date':
                    $v['attributes']['class'] = $class.'input_select';
                break;

                case 'text':
                    $v['attributes']['class'] = $class.'input_text';
                break;

                case 'textarea':
                    $v['attributes']['class'] = $class.'input_textarea';
                break;

                case 'file':
                    $v['attributes']['class'] = $class.'input_file input_text';
                break;

                case 'button':
                case 'submit':
                case 'reset':
                    $v['attributes']['class'] = $class.'button';
                break;
            }

            ## add elements ########################
            if ($v['type']=='radio') {
                foreach($v['options'] as $rk=>$rv) {
                    $radio[] =& $form->createElement($v['type'], NULL, NULL, $rv, $rk, $v['attributes']);
                }
                $form->addGroup($radio, $v['element'], $v['label']);
                unset($radio);

            } elseif ($v['type']=='checkbox_multi') {
                $checkbox[] =& $form->createElement('hidden', '', '');

                foreach($v['options'] as $rk=>$rv) {
                    $checkbox[$rk] =& $form->createElement('checkbox', is_string($rk) ? $rk : $rv, NULL, $rv, $v['attributes']);

                    if (array_key_exists($rk, array_flip($v['default'])) !== false) {
                        $checkbox[$rk]->setChecked(true);
                    }
                }
                $form->addGroup($checkbox, $v['element'], $v['label']);
                unset($checkbox);

            } elseif ($v['type']=='select') {
                $elem[$v['element']] =& $form->createElement($v['type'], $v['element'], $v['label'], $v['options'], $v['attributes']);
                $elem[$v['element']]->setMultiple($v['multiple']);
                if (isset($v['selected'])) $elem[$v['element']]->setSelected($v['selected']);
                if (!$v['groupit'])        $form->addElement($elem[$v['element']]);

            } elseif ($v['type']=='date') {
                $elem[$v['element']] =& $form->createElement($v['type'], $v['element'], $v['label'], $v['options'], $v['attributes']);
                if (!$v['groupit'])     $form->addElement($elem[$v['element']]);

            } elseif ($v['type']=='checkbox' || $v['type']=='static') {
                $elem[$v['element']] =& $form->createElement($v['type'], $v['element'], $v['label'], $v['text'], $v['attributes']);
                if (!$v['groupit'])     $form->addElement($elem[$v['element']]);

            } elseif ($v['type']=='image') {
                $elem[$v['element']] =& $form->createElement($v['type'], $v['element'], $v['src'], $v['attributes']);
                if (!$v['groupit'])     $form->addElement($elem[$v['element']]);

            } elseif (isset($v['type'])) {
                $elem[$v['element']] =& $form->createElement($v['type'], $v['element'], $v['label'],
                                            ($v['type']=='text' || $v['type']=='file' || $v['type']=='password') ? array_merge(array('size'=>CAMP_FORM_INPUT_TEXT_STANDARD_SIZE, 'maxlength'=>CAMP_FORM_INPUT_TEXT_STANDARD_MAXLENGTH), $v['attributes']) :
                                            ($v['type']=='textarea' ? array_merge(array('rows'=>CAMP_FORM_TEXTAREA_STANDARD_ROWS, 'cols'=>CAMP_FORM_TEXTAREA_STANDARD_COLS), $v['attributes']) : $v['attributes'])
                                        );
                if (!$v['groupit'])     $form->addElement($elem[$v['element']]);
            }
            ## add required rule ###################
            if ($v['required']) {
                $form->addRule($v['element'], isset($v['requiredmsg']) ? $v['requiredmsg'] : $translator->trans(CAMP_FORM_MISSINGNOTE, array('$1' => $v['label'])), 'required', NULL, $side);
            }
            ## add constant value ##################
            if (isset($v['constant'])) {
                $form->setConstants(array($v['element']=>$v['constant']));
            }
            ## add default value ###################
            if (isset($v['default'])) {
                $form->setDefaults(array($v['element']=>$v['default']));
            }
            ## add other rules #####################
            if ($v['rule']) {
                $form->addRule($v['element'], isset($v['rulemsg']) ? $v['rulemsg'] : $translator->trans('$1 is of type $2', array('$1' => $v['element'], '$2' => $translator->trans($v['rule'])), 'api'), $v['rule'] ,$v['format'], $side);
            }
            ## add group ###########################
            if (is_array($v['group'])) {
                foreach($v['group'] as $val) {
                    $groupthose[] =& $elem[$val];
                }
                $form->addGroup($groupthose, $v['name'], $v['label'], $v['seperator'], $v['appendName']);
                if ($v['rule']) {
                    $form->addRule($v['name'], isset($v['rulemsg']) ? $v['rulemsg'] : $translator->trans('$1 is of type $2', array('$1' => $v['name'], '$2' => $translator->trans($v['rule'])), 'api'), $v['rule'], $v['format'], $side);
                }
                if ($v['grouprule']) {
                    $form->addGroupRule($v['name'], $v['arg1'], $v['grouprule'], $v['format'], $v['howmany'], $side, $v['reset']);
                }
                unset($groupthose);
            }
            ## check error on type file ##########
            if ($v['type']=='file') {
                if (isset($_POST[$v['element']]['error'])) {
                    $form->setElementError($v['element'], isset($v['requiredmsg']) ? $v['requiredmsg'] : $translator->trans('Missing value for $1', array('$1' => $v['label']), 'api'));
                }
            }
        }

        reset($mask);
        $form->validate();
        $form->setJsWarnings(CAMP_FORM_JS_PREWARNING, CAMP_FORM_JS_POSTWARNING);
        $form->setRequiredNote(CAMP_FORM_REQUIREDNOTE);
    }
}
