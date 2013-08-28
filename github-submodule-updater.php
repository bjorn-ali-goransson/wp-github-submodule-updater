<?php

require dirname(__FILE__) . '/lib/github-submodule-updater/github-submodule-updater.php';

add_action('admin_menu', function(){
  if(file_exists(get_template_directory() . '/.gitmodules')){
    add_management_page(__("Update GitHub submodules"), __("Update GitHub submodules"), "edit_theme_options", 'update-github-submodules', 'update_git_submodules');
  }
});

function update_git_submodules(){
  if(isset($_GET['redo_submodule_name'])){
    $submodule = gitmodules_get_by_name($_GET['redo_submodule_name'], $_GET['gitmodules']);
    
    try{
      github_submodule_updater_redo_update($submodule);
    } catch (Exception $exception){
      ?>
        <div class="wrap">
          <h2><?php _e('Error'); ?></h2>
          <p><?php echo $exception->getMessage(); ?></p>
          <p><a onclick="history.back();" class="button"><?php _e('Back'); ?></a> <a onclick="location.reload();" class="button button-primary"><?php _e('Retry'); ?></a></p>
        </div>
      <?php
      return;
    }

    ?>

      <div class="wrap">
        <p><?php _e('Redoing was successful!'); ?></p>
        <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="return"><?php _e('Return'); ?></a></p>
        <script>
          location.href = document.getElementById("return").href;
        </script>
      </div>
    
    <?php
  } else if(isset($_GET['undo_submodule_name'])){
    $submodule = gitmodules_get_by_name($_GET['undo_submodule_name'], $_GET['gitmodules']);
    
    try{
      github_submodule_updater_undo_update($submodule);
    } catch (Exception $exception){
      ?>
        <div class="wrap">
          <h2><?php _e('Error'); ?></h2>
          <p><?php echo $exception->getMessage(); ?></p>
          <p><a onclick="history.back();" class="button"><?php _e('Back'); ?></a> <a onclick="location.reload();" class="button button-primary"><?php _e('Retry'); ?></a></p>
        </div>
      <?php
      return;
    }

    ?>

      <div class="wrap">
        <p><?php _e('Undoing was successful!'); ?></p>
        <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="return"><?php _e('Return'); ?></a></p>
        <script>
          location.href = document.getElementById("return").href;
        </script>
      </div>

    <?php
  } else if(isset($_GET['branch'])){
      $submodule = gitmodules_get_by_name($_GET['submodule_name'], $_GET['gitmodules']);
      $upload_dir = wp_upload_dir();

      try{
        github_submodule_updater_update($submodule, array('temp_path' => $upload_dir['basedir'] . '/from-github', 'branch' => $_GET['branch']));
      } catch (Exception $exception){
        ?>
          <div class="wrap">
            <h2><?php _e('Error'); ?></h2>
            <p><?php echo $exception->getMessage(); ?></p>
            <p><a onclick="history.back();" class="button"><?php _e('Back'); ?></a> <a onclick="location.reload();" class="button button-primary"><?php _e('Retry'); ?></a></p>
          </div>
        <?php
        return;
      }

    ?>

      <div class="wrap">
        <p><?php _e('Updating was successful!'); ?></p>
        <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="update-github-submodules"><?php _e('Return'); ?></a></p>
        <script>
          location.href = document.getElementById("update-github-submodules").href;
        </script>
      </div>

    <?php
  } else if(isset($_GET['submodule_name'])){
        $submodule = gitmodules_get_by_name($_GET['submodule_name'], $_GET['gitmodules']);
        
        ?>
          <div class="wrap">
            <div id="icon-tools" class="icon32"><br></div>
            <h2><?php printf(__('Update %s', $submodule->repo)); ?></h2>
            <form action="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['REQUEST_URI']; ?>" method="get">
              <?php
                foreach($_GET as $key => $value){
                  ?>
                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
                  <?php
                }
              ?>
              <input type="hidden" name="submodule_name" value="<?php echo $_GET['submodule_name']; ?>" />
              <input type="hidden" name="gitmodules" value="<?php echo $_GET['gitmodules']; ?>" />

              <?php
                $branches = github_submodule_updater_get_branches($submodule);

                if(!$branches){
                  echo __('Couldn\'t get GitHub branches'); return;
                }

                if(count($branches) == 1){
                  $first_branch = array_shift($branches);
                  ?>
                    <input type="hidden" name="branch" value="<?php echo $first_branch->name; ?>" />
                    <p><?php _e('There is only one branch:'); ?> <code><?php echo $first_branch->name; ?></code></p>
                  <?php
                } else {
                  ?>
                      <p><?php _e('There are several branches:'); ?></p>
                      <select name="branch">
                        <?php
                          foreach($branches as $branch){
                            ?>
                              <option<?php if($branch->name == 'master'){echo ' selected="selected"';} ?>><?php echo $branch->name; ?></option>
                            <?php
                          }
                        ?>
                      </select>
                  <?php
                }
              ?>
              <p><a href="tools.php?page=update-github-submodules" class="button"><?php _e('Return'); ?></a> <input type="submit" class="button-primary" value="<?php _e('Update'); ?>" /></p>
            </form>
          </div>
        <?php
  } else {
    ?>
      <div class="wrap">
        <div id="icon-tools" class="icon32"><br></div>
        <h2><?php _e('Update GitHub submodules'); ?></h2>
        <?php
          if(defined('WPLANG')){
            setlocale(LC_ALL, WPLANG);
          }

          function list_submodules($gitmodules_path = '', $level = 0, &$i = 0){
            if($level == 0){
              ?>
                <ul class="subsubsub"></ul>
                <table class="wp-list-table widefat fixed pages" cellspacing="0">
	                <thead>
	                  <tr>
		                  <th scope="col" id="cb" class="manage-column column-cb check-column"></th>
                      <th scope="col" id="title" class="manage-column column-title"><?php _e('Title'); ?></th>
                      <th scope="col" id="author" class="manage-column column-author"><?php _e('Author'); ?></th>
                      <th scope="col" id="date" class="manage-column column-date"><?php _e('Date'); ?></th>
                    </tr>
	                </thead>

	                <tfoot>
	                  <tr>
		                  <th scope="col" class="manage-column column-cb check-column"></th>
                      <th scope="col" class="manage-column column-title "><?php _e('Title'); ?></th>
                      <th scope="col" class="manage-column column-author"><?php _e('Author'); ?></th>
                      <th scope="col" class="manage-column column-date"><?php _e('Date'); ?></th>
                    </tr>
	                </tfoot>

	                <tbody id="the-list">
              <?php
            }

            foreach(gitmodules_get_all($gitmodules_path) as $submodule){
              ?>
				        <tr class="type-page status-publish hentry <?php if($i % 2 == 0) echo 'alternate'; ?> iedit author-self" valign="top">
				          <th scope="row" class="check-column"></th>
			            <td class="post-title page-title column-title">
                    <strong><span class="row-title"><a href="<?php echo str_replace(array('git://', '.git'), array('https://', ''), $submodule->url); ?>" target="_blank" title="<?php _e('View on GitHub'); ?>"><?php echo str_repeat('— ', $level); ?><?php echo $submodule->repo; ?></a></span></strong>
                    <div class="row-actions">
                      <?php
                        if($submodule->path_exists){
                          ?>
                            <?php
                              if(file_exists($submodule->path . '.old')){
                                ?>
                                  <span class="undo"><a href="<?php echo wp_github_submodule_updater_add_parameter_to_url('undo_submodule_name', $submodule->name, wp_github_submodule_updater_add_parameter_to_url('gitmodules', $submodule->parent_path)); ?>"><?php _e('Undo'); ?></a> | </span>
                                <?php
                              }
                              if(file_exists($submodule->path . '.undone')){
                                ?>
                                  <span class="redo"><a href="<?php echo wp_github_submodule_updater_add_parameter_to_url('redo_submodule_name', $submodule->name, wp_github_submodule_updater_add_parameter_to_url('gitmodules', $submodule->parent_path)); ?>"><?php _e('Redo'); ?></a> | </span>
                                <?php
                              }
                            ?>
                            <span class="update"><a href="<?php echo wp_github_submodule_updater_add_parameter_to_url('submodule_name', $submodule->name, wp_github_submodule_updater_add_parameter_to_url('gitmodules', $submodule->parent_path)) ?>"><?php _e('Update'); ?></a></span>
                          <?php
                        } else {
                          ?>
                            <span class="download"><a href="<?php echo wp_github_submodule_updater_add_parameter_to_url('submodule_name', $submodule->name, wp_github_submodule_updater_add_parameter_to_url('gitmodules', $submodule->parent_path)) ?>"><?php _e('Download'); ?></a></span>
                          <?php
                        }
                      ?>
                    </div>
                  </td>
                  <td class="author column-author"><a href="https://github.com/<?php echo $submodule->author; ?>" target="_blank"><?php echo $submodule->author; ?></a></td>
					        <td class="date column-date"><abbr title="<?php echo $submodule->path_exists ? date(__('Y/m/d g:i:s A'), filemtime($submodule->path)) : ''; ?>"><?php echo $submodule->path_exists ? date(__('Y/m/d'), filemtime($submodule->path)) : ''; ?></abbr><br><?php echo $submodule->path_exists ? __('Updated') : ''; ?></td>
                </tr>
              <?php
              if($submodule->gitmodules_exists){
                list_submodules($submodule->path, $level + 1, $i);
              }
            }
            
            if($level == 0){
              ?>
		              </tbody>
                </table>
              <?php
            }
          }

          list_submodules(wp_github_submodule_updater_get_relative_path(getcwd(), get_template_directory()));

        ?>
      </div>
    <?php
  }
}

function wp_github_submodule_updater_get_relative_path($from, $to){
    // Make sure directories have trailing slashes
    if (is_dir($from)) {
        $from = rtrim($from, '\/') . '/';
    }
    if (is_dir($to)) {
        $from = rtrim($from, '\/') . '/';
    }

    // Convert Windows slashes
    $from = str_replace('\\', '/', $from);
    $to = str_replace('\\', '/', $to);

    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if(isset($to[$depth]) && $dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }

    return implode('/', $relPath);
}


function wp_github_submodule_updater_add_parameter_to_url($name, $value, $url = NULL){
  if(!$url){
    $url = 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['REQUEST_URI'];
  }

  if(strpos($url, '?') !== FALSE){
    $separator = '&';
  } else {
    $separator = '?';
  }

  return $url . $separator . $name . '=' . $value;
}