<?php use Newscoop\Webcode\Manager; 
  $translator = \Zend_Registry::get('container')->getService('translator');
?>

<div class="ui-widget-content small-block block-shadow">
  <div class="collapsible">
    <h3 class="head ui-accordion-header ui-helper-reset ui-state-default ui-widget">
    <span class="ui-icon"></span>
    <a href="#" tabindex="-1"><?php echo $translator->trans('Info', array(), 'articles'); ?></a></h3>
  </div>
  <div class="padded clearfix">
    <dl class="inline-list">
      <dt><?php echo $translator->trans('Reads'); ?></dt>
      <dd>
        <?php
        if ($articleObj->isPublished()) {
            $requestObject = new RequestObject($articleObj->getProperty('object_id'));
            echo $requestObject->exists() ? $requestObject->getRequestCount() : '0';
        } else {
            echo $translator->trans('N/A');
        }
        ?>
      </dd>
      <dt><?php echo $translator->trans('Type'); ?></dt>
      <dd><?php print htmlspecialchars($articleType->getDisplayName()); ?></dd>
      <dt><?php echo $translator->trans('Number'); ?></dt>
      <dd><?php p($articleObj->getArticleNumber()); ?></dd>
      <dt><?php echo $translator->trans('Created by'); ?></dt>
      <dd><?php p(htmlspecialchars($articleCreator->getRealName())); ?></dd>
      <dt><?php echo $translator->trans('Webcode', array(), 'articles'); ?></dt>
      <dd><?php echo '+', $articleObj->getWebcode(); ?></dd>
      <dt><?php echo $translator->trans('Rating', array(), 'articles'); ?></dt>
      <dd><?php echo $articleObj->getRating(); ?></dd>
      <!-- render here results of admin.article.edit.info events classes -->
    </dl>
  </div>
</div>
