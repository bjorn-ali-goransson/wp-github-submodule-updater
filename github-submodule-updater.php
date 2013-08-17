<?php

add_action('admin_menu', function(){
  if(file_exists(get_template_directory() . '/.gitmodules')){
    add_management_page("Update GitHub submodules", "Update GitHub submodules", "edit_theme_options", 'update-github-submodules', 'update_git_submodules');
  }
});

function update_git_submodules(){
  $contents = explode("\n", file_get_contents(get_template_directory() . '/.gitmodules'));

  if(isset($_GET['branch'])){
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
    echo "<pre></pre>";
    for($i = 0; $i < count($contents); $i++){
      $line = $contents[$i];
      
      if(($submodule_name = gitmodules_get_name($line)) && ($submodule_name == $_GET['submodule_name'])){
        $submodule_path = gitmodules_get_path($contents[++$i]);
        $submodule_url = gitmodules_get_url($contents[++$i]);
        
        $submodule_author = gitmodules_get_author($submodule_url);
        $submodule_repo = gitmodules_get_repo($submodule_url);

        if(!$submodule_author || !$submodule_repo){
          return; // not a github repo
        }

        ?>
          <div class="wrap">
            <div id="icon-tools" class="icon32"><br></div>
            <h2>Update <?php echo $submodule_repo; ?></h2>
            <form action="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?>" method="get">
              <?php
                foreach($_GET as $key => $value){
                  ?>
                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
                  <?php
                }
              ?>
              <input type="hidden" name="author" value="<?php echo $submodule_author; ?>" />
              <input type="hidden" name="repo" value="<?php echo $submodule_repo; ?>" />
              <input type="hidden" name="path" value="<?php echo $submodule_path; ?>" />

              <?php
                $branches = json_decode(file_get_contents("https://api.github.com/repos/$submodule_author/$submodule_repo/branches") );

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
                              <option><?php echo $branch->name; ?></option>
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
      }
    }
  } else {
    ?>
      <div class="wrap">
        <div id="icon-tools" class="icon32"><br></div>
        <h2>Update GitHub submodules</h2>
        <ul>
          <?php
            for($i = 0; $i < count($contents); $i++){
              $line = $contents[$i];

              if($submodule_name = gitmodules_get_name($line)){
                $submodule_path = gitmodules_get_path($contents[++$i]);
                $submodule_url = gitmodules_get_url($contents[++$i]);

                if(defined('WPLANG')){
                  setlocale(LC_ALL, WPLANG);
                }

                ?>
                  <li><code title="Folder last modified <?php echo utf8_encode(strftime("%c", filemtime(get_template_directory() . '/' . $submodule_path))); ?>"><?php echo $submodule_name; ?></code> <a href="<?php echo $submodule_url; ?>" target="_blank" class="button">View on GitHub</a> <?php if($old){ ?> <a href="<?php echo add_parameter_to_url('undo_submodule_name', $submodule_name); ?>" class="button">Undo</a> <?php } ?><a href="<?php echo add_parameter_to_url('submodule_name', $submodule_name); ?>" onclick="return confirm('Be careful!');" class="button-primary">Update</a></li>
                <?php
              }
            }
          ?>
        </ul>
      </div>
    <?php
  }
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