<?php foreach($model["rows"] as $key => $row) { ?><tr><td>#<?php echo $row["id"]; ?></td><td> <?php if($row["isMain"]) { ?><span  class="material-symbols-outlined"  title="<?php echo t('Main article'); ?>">verified</span>  <?php } else if(!$row["isPublic"]) { ?><span  class="material-symbols-outlined"  title="<?php echo t('Not public'); ?>">visibility_off</span>  <?php } ?></td><td> <?php if($row["isMain"]) { ?><b><?php echo $row["title"]; ?></b> <?php } else { ?> <?php echo $row["title"]; ?> <?php } ?></td><td> <?php if($row["custom"]) { ?><span  class="material-symbols-outlined">remove_moderator</span>  <i><?php echo $row["author"]; ?></i> <?php } else { ?> <?php echo $row["author"]; ?> <?php } ?></td><td><?php echo $row["alias"]; ?></td><td><?php echo Strings::str_time($row["date"]); ?></td><td><a  href="<?php echo Router::url(); ?>adminv3/article/edit/<?php echo $row['id']; ?>"  class="button"><span  class="material-symbols-outlined">edit</span>   <?php echo t("Edit"); ?></a>   <?php if($row["isDeleted"]) { ?><a  href="#"  class="button"  onclick="undeleteArticle('<?php echo $row["id"]; ?>');return false;"><span  class="material-symbols-outlined"  title="<?php echo t('Restore article'); ?>">recycling</span> </a>   <?php } else { ?><a  href="#"  class="button"  onclick="deleteArticle('<?php echo $row["id"]; ?>');return false;"><span  class="material-symbols-outlined"  title="<?php echo t('Delete article'); ?>">delete</span> </a>   <?php } ?> <?php if($row["isPublic"]) { ?><a  href="#"  class="button"  onclick="stopPublishArticle('<?php echo $row["id"]; ?>');return false;"><span  class="material-symbols-outlined"  title="<?php echo t('Cancel publishing'); ?>">stop_circle</span> </a> <?php } ?></td></tr> <?php } ?><script> var total_rows = <?php echo $model['total']; ?>;
    var page = <?php echo $model['page']; ?>;
    table_articles.pushInfo({total: total_rows, page: page });
</script>