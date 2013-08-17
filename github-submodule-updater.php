<?php

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
      $download_path = wp_upload_dir()['basedir'] . '/' . $download_folder_name;
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
        <ul>
          <?php
            foreach(gitmodules_get_all() as $submodule){
                if(!$submodule->is_github){
                  continue;
                }
                
                if(defined('WPLANG')){
                  setlocale(LC_ALL, WPLANG);
                }

                $path = get_template_directory() . '/' . $submodule->path;
                $old_path = $path . '.old';
                $undone_path = $path . '.undone';
                
                $old = file_exists($old_path);
                $undone = file_exists($undone_path);

                ?>
                  <li>
                    <code title="Folder last modified <?php echo utf8_encode(strftime("%c", filemtime($path))); ?>"><?php echo $submodule->name; ?></code>
                    <a href="<?php echo $submodule->url; ?>" title="<?php echo $submodule->url; ?>" target="_blank" class="button">View on GitHub</a>
                    <?php if($old){ ?> <a href="<?php echo add_parameter_to_url('undo_submodule_name', $submodule->name); ?>" title="Undo folder last modified <?php echo utf8_encode(strftime("%c", filemtime($path))); ?>" class="button">Undo</a> <?php } ?>
                    <?php if($undone){ ?> <a href="<?php echo add_parameter_to_url('redo_submodule_name', $submodule->name); ?>" title="Undone folder last modified <?php echo utf8_encode(strftime("%c", filemtime($path))); ?>" class="button">Redo</a> <?php } ?>
                    <a href="<?php echo add_parameter_to_url('submodule_name', $submodule->name); ?>" class="button-primary">Update</a>
                  </li>
                <?php
            }
          ?>
        </ul>
      </div>
    <?php
  }
}

function gitmodules_get_all(){
  $contents = explode("\n", file_get_contents(get_template_directory() . '/.gitmodules'));

  $submodules = array();

  for($i = 0; $i < count($contents); $i++){
    $line = $contents[$i];
      
    if(($submodule_name = gitmodules_get_name($line))){
      $submodule_path = gitmodules_get_path($contents[++$i]);
      $submodule_url = gitmodules_get_url($contents[++$i]);
        
      $submodule_author = gitmodules_get_author($submodule_url);
      $submodule_repo = gitmodules_get_repo($submodule_url);

      $submodule = new stdClass;
      
      $submodule->name = $submodule_name;
      $submodule->path = $submodule_path;
      $submodule->url = $submodule_url;
      $submodule->author = $submodule_author;
      $submodule->repo = $submodule_repo;
      
      $submodule->is_github = strpos($submodule->url, '://github.com') !== FALSE;

      $submodules[] = $submodule;
    }
  }

  return $submodules;
}

function gitmodules_get_by_name($name){
  $submodules = gitmodules_get_all();

  foreach($submodules as $submodule){
    if($submodule->name == $name){
      return $submodule;
    }
  }

  return NULL;
}

function gitmodules_get_name($line){
  if(preg_match('@\[submodule "([^"]+)"\]@', $line, $matches)){
    return $matches[1];
  } else {
    return FALSE;
  }
}

function gitmodules_get_path($line){
  if(preg_match('@\s+path\s+=\s+(.+)@', $line, $matches)){
    return $matches[1];
  } else {
    return FALSE;
  }
}

function gitmodules_get_url($line){
  if(preg_match('@\s+url\s+=\s+(.+)@', $line, $matches)){
    return $matches[1];
  } else {
    return FALSE;
  }
}

function gitmodules_get_author($submodule_url){
  if(preg_match('@://github.com/([^/]+)/@', $submodule_url, $matches)){
    return $matches[1];
  } else {
    return FALSE;
  }
}

function gitmodules_get_repo($submodule_url){
  if(preg_match('@://github.com/[^/]+/([^.]+)\.git@', $submodule_url, $matches)){
    return $matches[1];
  } else {
    return FALSE;
  }
}