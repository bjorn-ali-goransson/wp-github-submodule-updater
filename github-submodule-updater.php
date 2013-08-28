<?php

require dirname(__FILE__) . '/lib/github-submodule-updater/github-submodule-updater.php';

add_action('admin_menu', function(){
  if(file_exists(get_template_directory() . '/.gitmodules')){
    add_management_page("Update GitHub submodules", "Update GitHub submodules", "edit_theme_options", 'update-github-submodules', 'update_git_submodules');
  }
});

function update_git_submodules(){
  if(isset($_GET['redo_submodule_name'])){
    $submodule_name = $_GET['redo_submodule_name'];

    $submodule = gitmodules_get_by_name($submodule_name);
    
    $repo_dir = get_template_directory() . '/' . $submodule->path;
    $old_repo_dir = $repo_dir . '.old';
    $undone_repo_dir = $repo_dir . '.undone';
    
    if(!file_exists($undone_repo_dir)){
      ?>
        <p>Undone dir doesn't exist, so no redo history exists! (<code><?php echo $undone_repo_dir; ?></code>);</p>
      <?php
      return;
    }
    
    if(file_exists($old_repo_dir)){
      foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($old_repo_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $rmpath) {
        $rmpath->isFile() ? unlink($rmpath->getPathname()) : rmdir($rmpath->getPathname());
      }

      rmdir($old_repo_dir);

      if(file_exists($old_repo_dir)){
        ?>
          <p>Couldn't remove old repo dir (<code><?php echo $old_repo_dir; ?></code>);</p>
        <?php
        return;
      }
    }
    
    if(!rename($repo_dir, $old_repo_dir)){
      ?>
        <p>Couldn't backup current repo dir to old dir(<code><?php echo $repo_dir; ?> to <?php echo $old_repo_dir; ?></code>);</p>
      <?php
      return;
    }
    
    if(!rename($undone_repo_dir, $repo_dir)){
      ?>
        <p>Couldn't redo undone repo dir to current dir(<code><?php echo $undone_repo_dir; ?> to <?php echo $repo_dir; ?></code>);</p>
      <?php
      return;
    }

    ?>

      <p>Redoing was successful!</p>
      <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="return">Return</a></p>
      <script>
        location.href = document.getElementById("return").href;
      </script>

    <?php
  } else if(isset($_GET['undo_submodule_name'])){
    $submodule_name = $_GET['undo_submodule_name'];

    $submodule = gitmodules_get_by_name($submodule_name);
    
    $repo_dir = get_template_directory() . '/' . $submodule->path;
    $old_repo_dir = $repo_dir . '.old';
    $undone_repo_dir = $repo_dir . '.undone';
    
    if(!file_exists($old_repo_dir)){
      ?>
        <p>Old repo dir doesn't exist, so no undo history exists! (<code><?php echo $old_repo_dir; ?></code>);</p>
      <?php
      return;
    }
    
    if(file_exists($undone_repo_dir)){
      foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($undone_repo_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $rmpath) {
        $rmpath->isFile() ? unlink($rmpath->getPathname()) : rmdir($rmpath->getPathname());
      }

      rmdir($undone_repo_dir);

      if(file_exists($undone_repo_dir)){
        ?>
          <p>Couldn't remove undone repo dir (<code><?php echo $undone_repo_dir; ?></code>);</p>
        <?php
        return;
      }
    }
    
    if(!rename($repo_dir, $undone_repo_dir)){
      ?>
        <p>Couldn't backup current repo dir to undone dir(<code><?php echo $repo_dir; ?> to <?php echo $undone_repo_dir; ?></code>);</p>
      <?php
      return;
    }
    
    if(!rename($old_repo_dir, $repo_dir)){
      ?>
        <p>Couldn't undo old repo dir to current dir(<code><?php echo $old_repo_dir; ?> to <?php echo $repo_dir; ?></code>);</p>
      <?php
      return;
    }

    ?>

      <p>Undoing was successful!</p>
      <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="return">Return</a></p>
      <script>
        location.href = document.getElementById("return").href;
      </script>

    <?php
  } else if(isset($_GET['branch'])){
      $branch = $_GET['branch'];
      $author = $_GET['author'];
      $repo = $_GET['repo'];
      $path = $_GET['path'];

      $download_folder_name = 'from-github';
      $upload_dir = wp_upload_dir();
      $download_path = $upload_dir['basedir'] . '/' . $download_folder_name;
      $file_path = $download_path . '/' . $repo . '.zip';

      if(!file_exists($download_path)){
        if(!mkdir($download_path)){
          ?>
            <p>Couldn't create upload directory (<code>wp-content/upload/<?php echo $download_folder_name; ?></code>);</p>
          <?php
          return;
        }
      }

      $url = "https://github.com/$author/$repo/archive/$branch.zip";
      
      $file_contents = file_get_contents($url);

      if(!$file_contents){
        ?>
          <p>Couldn't download file (<code><?php echo $url; ?></code>);</p>
        <?php
        return;
      }

      if(!file_put_contents($file_path, $file_contents)){
        ?>
          <p>Couldn't write file (<code><?php echo $file_path; ?></code>);</p>
        <?php
        return;
      }
      
      $repo_dir = get_template_directory() . '/' . $path;
      $old_repo_dir = $repo_dir . '.old';
      
      if(file_exists($old_repo_dir)){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($old_repo_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $rmpath) {
          $rmpath->isFile() ? unlink($rmpath->getPathname()) : rmdir($rmpath->getPathname());
        }

        rmdir($old_repo_dir);

        if(file_exists($old_repo_dir)){
          ?>
            <p>Couldn't remove old repo dir (<code><?php echo $old_repo_dir; ?></code>);</p>
          <?php
          return;
        }
      }
      
      if(file_exists($repo_dir)){
        if(!rename($repo_dir, $old_repo_dir)){
          ?>
            <p>Couldn't backup old repo dir (<code><?php echo $repo_dir; ?> to <?php echo $old_repo_dir; ?></code>);</p>
          <?php
          return;
        }
      }
      
      if(!file_exists(basename($repo_dir))){
        if(!mkdir(basename($repo_dir))){
          ?>
            <p>Couldn't create path to repo dir (<code><?php echo basename($repo_dir); ?>);</p>
          <?php
          return;
        }
      }
      
      require_once getcwd() . '/includes/class-pclzip.php';

      $archive = new PclZip($file_path);
      if(!$archive->extract(PCLZIP_OPT_PATH, $download_path)){
        ?>
          <p>Couldn't unzip (<code><?php echo $file_path; ?></code> to <code><?php echo $unzipped_path; ?></code>);</p>
        <?php
        return;
      }

      $unzipped_path = $download_path . '/' . $repo . '-' . $branch;

      if(!file_exists($unzipped_path)){
        ?>
          <p>Couldn't find unzipped dir (<code><?php echo $unzipped_path; ?></code>);</p>
        <?php
        return;
      }

      if(!rename($unzipped_path, $repo_dir)){
        ?>
          <p>Couldn't rename unzipped dir (<code><?php echo $unzipped_path; ?></code> to <code><?php echo $repo_dir; ?></code>);</p>
        <?php
        return;
      }

    ?>

      <p>Updating was successful!</p>
      <p><a href="tools.php?page=update-github-submodules" class="button-primary" id="update-github-submodules">Return</a></p>
      <script>
        location.href = document.getElementById("update-github-submodules").href;
      </script>

    <?php
  } else if(isset($_GET['submodule_name'])){
        $submodule = gitmodules_get_by_name($_GET['submodule_name']);
        
        ?>
          <div class="wrap">
            <div id="icon-tools" class="icon32"><br></div>
            <h2>Update <?php echo $submodule->repo; ?></h2>
            <form action="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?>" method="get">
              <?php
                foreach($_GET as $key => $value){
                  ?>
                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
                  <?php
                }
              ?>
              <input type="hidden" name="author" value="<?php echo $submodule->author; ?>" />
              <input type="hidden" name="repo" value="<?php echo $submodule->repo; ?>" />
              <input type="hidden" name="path" value="<?php echo $submodule->path; ?>" />

              <?php
                $branches = json_decode(file_get_contents("https://api.github.com/repos/$submodule->author/$submodule->repo/branches") );

                if(!$branches){
                  echo 'Couldn\'t get GitHub branches'; return;
                }

                if(count($branches) == 1){
                  $first_branch = array_shift($branches);
                  ?>
                    <input type="hidden" name="branch" value="<?php echo $first_branch->name; ?>" />
                    <p>There is only one branch: <code><?php echo $first_branch->name; ?></code></p>
                  <?php
                } else {
                  ?>
                      <p>There are several branches:</p>
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
              <p><a href="tools.php?page=update-github-submodules" class="button">Return</a> <input type="submit" class="button-primary" value="Update" /></p>
            </form>
          </div>
        <?php
  } else {
    ?>
      <div class="wrap">
        <div id="icon-tools" class="icon32"><br></div>
        <h2>Update GitHub submodules</h2>
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
                      <th scope="col" id="title" class="manage-column column-title">Title</th>
                      <th scope="col" id="author" class="manage-column column-author">Author</th>
                      <th scope="col" id="date" class="manage-column column-date">Date</th>
                    </tr>
	                </thead>

	                <tfoot>
	                  <tr>
		                  <th scope="col" class="manage-column column-cb check-column"></th>
                      <th scope="col" class="manage-column column-title ">Title</th>
                      <th scope="col" class="manage-column column-author">Author</th>
                      <th scope="col" class="manage-column column-date">Date</th>
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
                                  <span class="undo"><a href="<?php echo add_parameter_to_url('undo_submodule_name', $submodule->name); ?>">Undo</a> | </span>
                                <?php
                              }
                              if(file_exists($submodule->path . '.undone')){
                                ?>
                                  <span class="redo"><a href="<?php echo add_parameter_to_url('redo_submodule_name', $submodule->name); ?>">Redo</a> | </span>
                                <?php
                              }
                            ?>
                            <span class="update"><a href="<?php echo add_parameter_to_url('submodule_name', $submodule->name) ?>">Update</a></span>
                          <?php
                        } else {
                          ?>
                            <span class="download"><a href="<?php echo add_parameter_to_url('submodule_name', $submodule->name) ?>">Download</a></span>
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

          list_submodules(get_template_directory());

        ?>
      </div>
    <?php
  }
}