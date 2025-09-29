<?php
add_action( "admin_menu", 'hypeanimations_panel_upload' );
function hypeanimations_panel_upload() {
    global $wpdb, $hypeanimations_table_name;
    $upload_dir = wp_upload_dir();
    $anims_dir = $upload_dir['basedir'] . '/hypeanimations/';

	if (isset($_FILES['file'])) {
		// Require a logged-in user with upload capability for uploads
		if (!is_user_logged_in() || !current_user_can('upload_files')) {
			wp_die(esc_html__('Unauthorized access', 'tumult-hype-animations'));
		}

		$nonce = isset($_POST['upload_check_oam']) ? $_POST['upload_check_oam'] : '';
		if (!wp_verify_nonce($nonce, 'protect_content')) {
			wp_die(esc_html__('Security check failed', 'tumult-hype-animations'));
		}

        $file = $_FILES['file'];
        $allowed_file_types = array('oam' => 'application/octet-stream');

        $upload_overrides = array(
            'test_form' => false,
            'mimes' => $allowed_file_types
        );

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

		if (isset($uploaded_file['error'])) {
			echo esc_html__('Error: ', 'tumult-hype-animations') . esc_html($uploaded_file['error']);
			exit;
		}

			$uploadfile = $uploaded_file['file'];

			// Check the zip file for disallowed files in memory
			$zip_clean = is_zip_clean($uploadfile, apply_filters('tumult_hype_animations_whitelist', array()));
			if (is_wp_error($zip_clean)) {
				// Log the error server-side and return a user-facing message (escaped)
				error_log('[hypeanimations] ZIP validation failed: ' . $zip_clean->get_error_message());
				echo esc_html($zip_clean->get_error_message());
				wp_delete_file($uploadfile); // Delete the uploaded ZIP file to prevent processing
				exit;
			}
 
        WP_Filesystem();
        $uploaddir = $anims_dir . 'tmp/';
        if (!file_exists($uploaddir)) {
            wp_mkdir_p($uploaddir);
        }
        $uploadfinaldir = $anims_dir;
        $unzipfile = unzip_file($uploadfile, $uploaddir);
        if ($unzipfile) {
            if (file_exists($uploadfile)) {
                wp_delete_file($uploadfile);
            }
            if (file_exists($uploaddir . '/config.xml')) {
                wp_delete_file($uploaddir . '/config.xml');
            }
            // Preserve the original filename throughout processing - only sanitize for final storage
            $original_name = str_replace('.oam', '', basename($_FILES['file']['name']));
            $sanitized_name = sanitize_file_name($original_name);

            // Auto-discover the actual structure from the extracted OAM
            $assets_path = $uploaddir . 'Assets/';
            $discovered = discover_oam_structure($assets_path);
            
            if (is_wp_error($discovered)) {
                error_log("Failed to discover OAM structure: " . $discovered->get_error_message());
                return $discovered;
            }

			// Ensure folder name is just a basename (defend in-depth against unexpected path chars)
			$hyperesources_folder = basename($discovered['hyperesources']);
            $html_file = $discovered['html'];
            $html_base = $discovered['html_base'];

			// Use original name for database and URL-encode for web-safe paths
			$new_name = $original_name;

			// Create database entry using original name (preserving spaces)
            $wpdb->insert(
                $hypeanimations_table_name,
                array(
                    'nom' => $original_name,  // Preserve original name with spaces
                    'slug' => sanitize_title($original_name),  // URL-safe slug
                    'code' => '',
                    'updated' => time(),
                    'container' => 'div'
                )
            );
            $lastid = $wpdb->insert_id;

            // Create final storage directory
            $final_dir = $uploadfinaldir . $lastid . '/';
            if (!is_dir($final_dir)) {
                wp_mkdir_p($final_dir);
            }

            // Set file permissions
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploaddir . 'Assets/'), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                if ($file->isFile()) {
                    chmod($file->getRealPath(), 0644);
                }
            }

			// Move hyperesources folder directly using the discovered folder name (preserve export name)
			$source_hyperesources = $assets_path . $hyperesources_folder;
			$target_hyperesources = $final_dir . $hyperesources_folder . '/';

			// Attempt a single rename; if that fails, fall back to a simple recursive copy
			if (!@rename($source_hyperesources, $target_hyperesources)) {
				// If target already exists (unlikely), remove the source. Otherwise attempt recursive copy
				if (is_dir($target_hyperesources)) {
					hyperrmdir($source_hyperesources);
				} else {
					if (!@mkdir($target_hyperesources, 0755, true)) {
						error_log('[hypeanimations] Failed to move hyperesources from ' . $source_hyperesources . ' to ' . $target_hyperesources);
						echo esc_html__('Failed to move resource files.', 'tumult-hype-animations');
						exit();
					}
					$items = scandir($source_hyperesources);
					foreach ($items as $it) {
						if ($it === '.' || $it === '..') continue;
						if (is_dir($source_hyperesources . $it)) {
							$srcDir = $source_hyperesources . $it . '/';
							$dstDir = $target_hyperesources . $it . '/';
							$itFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
							foreach ($itFiles as $f) {
								$destPath = $dstDir . substr($f->getPathname(), strlen($srcDir));
								if ($f->isDir()) {
									@mkdir($destPath, 0755, true);
								} else {
									copy($f->getPathname(), $destPath);
								}
							}
						} else {
							copy($source_hyperesources . $it, $target_hyperesources . $it);
						}
					}
					hyperrmdir($source_hyperesources);
				}
			}

            // Process HTML file - elegant path replacement using URL encoding
            $html_content = file_get_contents($assets_path . $html_file);
            
            // Replace resource paths: original.hyperesources -> URL-encoded web path
			$original_resource_ref = $html_base . '.hyperesources';
			// Use the discovered folder name (URL-encoded) so the web path matches filesystem folder
			$web_resource_url = $upload_dir['baseurl'] . '/hypeanimations/' . $lastid . '/' . rawurlencode($hyperesources_folder);
			$html_content = str_replace($original_resource_ref, $web_resource_url, $html_content);

            // Extract animation container for shortcode (simple approach)
            $animation_container = '';
            $lines = explode("\n", $html_content);
            $recording = false;
            
            foreach ($lines as $line) {
                if (strpos($line, '<div id="') !== false) {
                    $recording = true;
                }
                if ($recording) {
                    $animation_container .= $line . "\n";
                }
                if (strpos($line, '</div>') !== false && $recording) {
                    $recording = false;
                    break;
                }
            }

            // Update database with processed content
            $wpdb->update(
                $hypeanimations_table_name,
                array('code' => addslashes(htmlentities($animation_container))),
                array('id' => $lastid)
            );

            // Save processed HTML file
            file_put_contents($final_dir . 'index.html', $html_content);

            // Cleanup temporary files
            delete_temp_files($uploaddir);
            if (is_dir($uploaddir . 'Assets/')) {
                hyperrmdir($uploaddir . 'Assets/');
            }

			echo intval($lastid);
            exit();
        } else {
            echo esc_html__('Failed to unzip the file.', 'tumult-hype-animations');
            delete_temp_files($uploaddir);
            exit();
        }
    }
}

/**
 * Sanitize a string to be safe for use as a CSS class name.
 * Kept file-scoped to reuse across AJAX handlers.
 */
function sanitize_html_classname($input) {
	// Strip tags to remove any HTML
	$input = wp_strip_all_tags($input);

	// Remove any unwanted characters, allow only a-z, A-Z, 0-9, hyphens, and underscores
	$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $input);

	// Ensure the classname does not start with a digit, two hyphens, or a hyphen followed by a digit
	if (preg_match('/^(\d|-\d|--)/', $sanitized)) {
		// Prepend a letter (e.g., 'x') to ensure validity if it starts with invalid characters
		$sanitized = 'x' . $sanitized;
	}

	return $sanitized;
}

add_action( "admin_footer", 'add_hypeanimations_shortcode_newbutton_footer' );
function add_hypeanimations_shortcode_newbutton_footer() {
	global $hypeanimations_table_name;
	global $wpdb;
	global $upload_mb;
	$nonce_files = wp_nonce_field( 'protect_content', 'upload_check_oam' );
	// Getting ini configuration for 'upload_max_filesize'
	$upload_max_filesize = ini_get('upload_max_filesize');
	//echo 'upload_max_filesize: ' . $upload_max_filesize . "<br/>";

	// Getting ini configuration for 'post_max_size'
	$post_max_size = ini_get('post_max_size');
	//echo 'post_max_size: ' . $post_max_size . "<br/>";

	// Getting ini configuration for 'memory_limit'
	$memory_limit = ini_get('memory_limit');
	//echo 'memory_limit: ' . $memory_limit . "<br/>";

	$tooltip_content = 'upload_max_filesize: ' . $upload_max_filesize . ' '
			. 'post_max_size: ' . $post_max_size . ' '
			. 'memory_limit: ' . $memory_limit;

	$output='
	<div id="openModal1" class="openModal">

<script>
// DZ 5.9.3 update
Dropzone.options.hypeanimdropzone = { // camelized version of the `id`
	paramName: "file", // The name that will be used to transfer the file
	maxFilesize: 2000, // MB
	method: "post",
	url: "admin.php?page=hypeanimations_panel",
	uploadMultiple: false,
	maxFiles: 1,
	acceptedFiles: ".oam",
	timeout: 180000,
	dictDefaultMessage: "'.__( 'Drop .OAM file or click here to upload<br>(Maximum upload size '. $upload_mb .')' , 'tumult-hype-animations' ).'",

	accept: function(file, done) {
		// Allow filenames with spaces; server-side normalisation is used to find the correct resource folder.
		done();
	},
success: function(file, resp) {
	if(isNaN(parseInt(resp))) { // error string instead of numeric short code
		jQuery(".dropzone").after("<div class=\"dropzone2\" style=\"display:none\"><br>" + resp + "</div>");
		jQuery(".dropzone2").css("display", "block");
		jQuery(".dropzone").remove();	
	} else {
		jQuery(".dropzone").after("<div class=\"dropzone2\" style=\"display:none\"><br>'.__( 'Insert the following shortcode where you want to display the animation' , 'tumult-hype-animations' ).':<br><br> <span style=\"font-family:monospace\">[hypeanimations_anim id=\"" + resp + "\"]</span></div>");
		jQuery(".dropzone2").css("display", "block");
		jQuery(".dropzone").remove();	
	}
}
};

</script>
		<div>
			<header>
				<a href="#fermer" alt="close" id="closeDroper" class="closemodal">&#10005;</a>
				<h2>'.__( 'Upload new animation' , 'tumult-hype-animations' ).'</h2>
			</header>
			<section>
				<form action="" class="dropzone" id="hypeanimdropzone" title="'. $tooltip_content .'" method="post" accept-charset="utf-8" enctype="multipart/form-data">
					'.$nonce_files.'
				</form>
			</section>
		</div>
	</div>



	<script>
	jQuery(".closemodal").click(function(e) {
		window.location.href=window.location.href.substr(0, window.location.href.indexOf("#"));
	});
	</script>
	';

	// Only output modal on plugin page
	if ( empty( $_GET['page'] ) || sanitize_text_field( $_GET['page'] ) !== 'hypeanimations_panel' ) {
		return; 
	}

	echo $output;

}
	
function hypeanimations_panel() {
	global $wpdb;
	global $version;
	global $hypeanimations_table_name;
	$upload_dir = wp_upload_dir();
	$anims_dir=$upload_dir['basedir'].'/hypeanimations/';
	
	// Define URLs as variables for better maintainability
	$hype_product_url = 'https://tumult.com/hype?utm_source=wpplugin';
	$help_forum_url = 'https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074';

	$upload_instruction_html = sprintf(
		/* translators: 1: URL to the Tumult Hype product page, 2: URL to the Tumult support forum article. */
		__( 'Upload an .OAM file exported by <a href="%1$s">Tumult Hype</a> and a shortcode will be generated which you can insert in posts and pages. <a href="%2$s" target="_blank">Need help?</a>', 'tumult-hype-animations' ),
		esc_url($hype_product_url),
		esc_url($help_forum_url)
	);

	// Localized confirmation templates for deleting an animation
	$delete_confirm_with_title_json = wp_json_encode(
		sprintf(
			/* translators: %s: animation title. */
			__( 'Delete "%s"? Are you sure you want to continue?', 'tumult-hype-animations' ),
			'%s'
		)
	);
	$delete_confirm_without_title_json = wp_json_encode(
		__( 'Delete this animation? This action is irreversible. Are you sure?', 'tumult-hype-animations' )
	);

	echo '<br><h1>' . esc_html__('Tumult Hype Animations', 'tumult-hype-animations') . ' (v' . esc_html($version) . ')</h1>
	<p>&nbsp;</p>
	</div>
	<h2>'.__( 'Add new animation' , 'tumult-hype-animations' ).'</h2>
	<div class="hypeanimbloc">'
	. $upload_instruction_html . '<br><br>
	<a href="#openModal1" class="button" id="add_hypeanimations_shortcode_newbutton" style="outline: medium none !important; cursor: pointer;" ><i class="dashicons-before dashicons-plus-alt"></i> '.__( 'Upload new animation' , 'tumult-hype-animations' ).'</a>
	</div>';

	// Verify nonce before delete
	$delete = isset($_GET['delete']) ? intval($_GET['delete']) : 0;
	if ($delete > 0) {
		// Capability check - only users who can edit posts may delete animations in this UI
		if (!current_user_can('edit_posts')) {
			wp_die(esc_html__('Unauthorized access', 'tumult-hype-animations'));
		}

		if ( ! isset($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'], 'delete-animation_' . $delete)) {
			wp_die(esc_html__('Security check failed', 'tumult-hype-animations'));
		}

		$animtitle = $wpdb->get_var($wpdb->prepare("SELECT nom FROM {$hypeanimations_table_name} WHERE id=%d", $delete));
		$delete = $wpdb->query($wpdb->prepare("DELETE FROM {$hypeanimations_table_name} WHERE id=%d", $delete));
		hyperrmdir($anims_dir . $delete . '/');

		if (!empty($animtitle)) {
			echo '<p>&nbsp;</p><p><span style="padding:10px;color:#FFF;background:#cc0000;">' . esc_html($animtitle) . ' ' . esc_html__( 'has been deleted.', 'tumult-hype-animations' ) . '</span></p>';
		}
	}
	$hypeupdated = 0;
	if (is_user_logged_in() && isset($_FILES['updatefile']) && isset($_POST['dataid']) && intval($_POST['dataid']) > 0) {

		$nonce = isset($_POST['upload_check_oam']) ? $_POST['upload_check_oam'] : '';
		if ( ! wp_verify_nonce( $nonce, 'protect_content' ) ) {
			wp_die(esc_html__('Security check failed', 'tumult-hype-animations'));
		}
		
		$allowed_types = array(
			'oam' => 'application/octet-stream'
		);
		
		$file_info = wp_check_filetype_and_ext(
			$_FILES['updatefile']['tmp_name'],
			$_FILES['updatefile']['name'],
			$allowed_types
		);

		if (!$file_info['type']) {
			wp_die(__('Only .oam files are allowed for upload.', 'tumult-hype-animations'));
		}

		$zip_clean = is_zip_clean($_FILES['updatefile']['tmp_name'], apply_filters('tumult_hype_animations_whitelist', array()));
		if (is_wp_error($zip_clean)) {
			// show error message displaying the file extension which is not allowed (escaped)
			echo '<p style="font-weight: bold;">' . esc_html($zip_clean->get_error_message()) . '</p>';
			wp_delete_file($_FILES['updatefile']['tmp_name']); // Delete the uploaded ZIP file to prevent processing
			exit;
		}

		else {
		
			// Allow update uploads with spaces. Server-side sanitization and ZIP checks will validate contents.
			$actdataid = ceil($_POST['dataid']);
			$uploaddir = $anims_dir . 'tmp/';
			$uploadfinaldir = $anims_dir;
				if (!file_exists($uploaddir)) {
					wp_mkdir_p($uploaddir);
				}
			$uploadfile = $uploaddir . basename(sanitize_file_name($_FILES['updatefile']['name']));
			if (move_uploaded_file($_FILES['updatefile']['tmp_name'], $uploadfile)) {
				WP_Filesystem();

				// Unzip the file
				$unzipfile = unzip_file($uploadfile, $uploaddir);
				if (file_exists($uploadfile)) {
					wp_delete_file($uploadfile);
				}
				if (file_exists($uploaddir . '/config.xml')) {
					wp_delete_file($uploaddir . '/config.xml');
				}

				// Preserve original name and compute sanitized name
				$original_name = str_replace('.oam', '', basename($_FILES['updatefile']['name']));
				$sanitized_name = sanitize_file_name($original_name);

				// Auto-discover structure (handles spaces in folder names)
				$assets_path_upd = $uploaddir . 'Assets/';
				$discovered_upd = discover_oam_structure($assets_path_upd);
				if (is_wp_error($discovered_upd)) {
					delete_temp_files($uploaddir);
					echo esc_html($discovered_upd->get_error_message());
					exit();
				}

				// Sanitize discovered folder name
				$hyperesources_folder_upd = basename($discovered_upd['hyperesources']);
				$html_file_upd = $discovered_upd['html'];
				$html_base_upd = $discovered_upd['html_base'];

				// Ensure final directory exists
				if (!is_dir($uploadfinaldir . $actdataid . '/')) {
					wp_mkdir_p($uploadfinaldir . $actdataid . '/');
				}

				// Move the discovered hyperesources folder to the final location using the discovered folder name
				$source = $assets_path_upd . $hyperesources_folder_upd;
				$target = $uploadfinaldir . $actdataid . '/' . $hyperesources_folder_upd . '/';

				// If target exists, remove it first to ensure a clean replace
				if (file_exists($target)) {
					hyperrmdir($target);
				}

				// Attempt a single rename; if that fails, fall back to a simple recursive copy
				if (!@rename($source, $target)) {
					if (!@mkdir($target, 0755, true)) {
						error_log('[hypeanimations] Failed to create target directory: ' . $target);
						echo esc_html__('Failed to move resource files.', 'tumult-hype-animations');
						delete_temp_files($uploaddir);
						exit();
					}
					$items = scandir($source);
					foreach ($items as $it) {
						if ($it === '.' || $it === '..') continue;
						if (is_dir($source . $it)) {
							// Recursively copy directories (simple recursive copy)
							$srcDir = $source . $it . '/';
							$dstDir = $target . $it . '/';
							$itFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
							foreach ($itFiles as $f) {
								$destPath = $dstDir . substr($f->getPathname(), strlen($srcDir));
								if ($f->isDir()) {
									@mkdir($destPath, 0755, true);
								} else {
									copy($f->getPathname(), $destPath);
								}
							}
						} else {
							copy($source . $it, $target . $it);
						}
					}
					// Remove source after copying
					hyperrmdir($source);
				}

				// Update database record name
				$wpdb->update(
					$hypeanimations_table_name,
					array('nom' => $original_name, 'updated' => time()),
					array('id' => $actdataid)
				);

				// Replace resource references in HTML with the discovered folder name (URL-encoded)
				$html_content = file_get_contents($assets_path_upd . $html_file_upd);
				$original_resource_ref = $html_base_upd . '.hyperesources';
				$web_resource_url = $upload_dir['baseurl'] . '/hypeanimations/' . $actdataid . '/' . rawurlencode($hyperesources_folder_upd);
				$html_content = str_replace($original_resource_ref, $web_resource_url, $html_content);

				// Extract animation container
				$animation_container = '';
				$lines = explode("\n", $html_content);
				$recording = false;
				foreach ($lines as $line) {
					if (strpos($line, '<div id="') !== false) {
						$recording = true;
					}
					if ($recording) {
						$animation_container .= $line . "\n";
					}
					if (strpos($line, '</div>') !== false && $recording) {
						$recording = false;
						break;
					}
				}

				$wpdb->update(
					$hypeanimations_table_name,
					array('code' => addslashes(htmlentities($animation_container)), 'updated' => time()),
					array('id' => $actdataid)
				);

				// Save processed HTML to final directory
				file_put_contents($uploadfinaldir . $actdataid . '/' . $html_file_upd, $html_content);

				// Cleanup temporary files
				if (file_exists($uploaddir . 'Assets/' . $html_file_upd)) {
					wp_delete_file($uploaddir . 'Assets/' . $html_file_upd);
				}
				if (is_dir($uploaddir . 'Assets/')) {
					hyperrmdir($uploaddir . 'Assets/');
				}
				delete_temp_files($uploaddir);

				$hypeupdated = $actdataid;
				$hypeupdatetd_title = $original_name;
			}
			else {
				wp_die( __( 'Sorry, there was an issue replacing your oam. Check the logs.', 'tumult-hype-animations' ), 401 );
			}
		}
	}
 echo '<p style="line-height:0px;clear:both">&nbsp;</p>
	'. ( $hypeupdated > 0
		? '<p><span style="padding:10px;color:#FFF;background:#009933;">'
			. esc_html( $hypeupdatetd_title ) . ' ' . esc_html__( 'has been updated!', 'tumult-hype-animations' )
			. '</span></p><p>&nbsp;</p>'
		: '' ) .'
	<h2>'.__( 'Manage animations' , 'tumult-hype-animations' ).'</h2>
	<table cellpadding="0" cellspacing="0" id="hypeanimations">
		<thead>
			<tr>
				<th>Animation</th>
				<th>Shortcode</th>
				<th>Notes<br><small>(autosaved)</small></th>
				<th>Options</th>
				<th>'.__( 'Last file update' , 'tumult-hype-animations' ).'</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>';
		$result = $wpdb->get_results($wpdb->prepare("SELECT id,nom,slug,updated,container,notes,containerclass FROM $hypeanimations_table_name Where id > %d ORDER BY updated DESC", 0));
	
		foreach ($result as $results) {
			// Generate a unique nonce for each delete action
			$delete_nonce = wp_create_nonce('delete-animation_'.$results->id);
			//$delete_nonce = wp_create_nonce('update-note_'.$results->id);

			echo '<tr>
				<td>' . esc_html($results->nom) . '</td>
				<td>
					<input class="shortcodeval" type="text" spellcheck="false" value="[hypeanimations_anim id=&quot;' . intval($results->id) . '&quot;]"></input>
				</td>
				<td>
					<textarea name="notes" spellcheck="false" style="resize: vertical; min-height: 20px;">' . esc_textarea(stripslashes($results->notes)) .  '</textarea>
					<div class="hypeanimupdated-notes" data-id="' . esc_attr(intval($results->id)) . '" style="min-height:20px;display:block;"></div>
				</td>
				<td align="left" style="text-align:left;">
					 ' . __( 'Add a container around the animation:', 'tumult-hype-animations' ) . '<br>
					<select class="hypeanimations_container" name="container">
							<option value="div" ' . ($results->container == 'div' ? 'selected' : '') . '>&lt;div&gt;</option>
							<option value="iframe" ' . ($results->container == 'iframe' ? 'selected' : '') . '>&lt;iframe&gt;</option>
						</select><br>
					' . __( 'Container CSS class', 'tumult-hype-animations' ) .': <br>
					<div ' . ($results->container == 'none' ? 'style="display:none;"' : '') . '>
							 <input onkeypress="return preventDot(event);" type="text" name="class" spellcheck="false" placeholder="Myclass" style="width:130px;" value="' . esc_attr($results->containerclass) . '">
					</div>
					<input type="button" value="' . __( 'Update', 'tumult-hype-animations' ) . '" class="updatecontainer" data-id="' . esc_attr(intval($results->id)) . '">
				</td>
				<td>' . ($results->updated == 0 ? '<em>' . __( 'No data', 'tumult-hype-animations' ) . '</em>' : date('Y/m/d', $results->updated) . '<br>' . date('H:i:s', $results->updated)) . '</td>
				<td>
					<a href="javascript:void(0)" id="' . esc_attr(intval($results->id)) . '" class="animcopy">' . __( 'Copy Code', 'tumult-hype-animations' ) . '</a>
					<a href="admin.php?page=hypeanimations_panel&update=' . intval($results->id) . '" class="animupdate" data-id="' . esc_attr(intval($results->id)) . '">' . __( 'Replace OAM', 'tumult-hype-animations' ) . '</a>
					<a href="admin.php?page=hypeanimations_panel&delete=' . intval($results->id) . '&_wpnonce=' . esc_attr($delete_nonce) . '" class="animdelete" data-title="' . esc_attr($results->nom) . '">' . __( 'Delete', 'tumult-hype-animations' ) . '</a>
				</td>
			</tr>';
		}
	
	
	echo '</tbody> 
	</table> 

	<script>
	jQuery(document).ready(function(jQuery){
		jQuery(document).on("click", ".animcopy", function(){
			jQuery("body").append("<div class=\'popup-wrap\'> <div class=\'popup-overlay\'> <div class=\'popup\'><h3 class=\'popup-heading\'>Copy Embed Code</h3><textarea spellcheck=\'false\' class=\'copydata\' rows=\'10\' cols=\'30\' style=\'width:100%; height:250px;\' readonly></textarea><span class=\'close-popup\'>✕</span><span class=\'copied\'>Copied to clipboard.</span></div> </div>");
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					"action": "hypeanimations_getcontent",
					"dataid": jQuery(this).attr("id"),
					"nonce": "' . esc_js(wp_create_nonce('hypeanimations_getcontent_nonce')) . '"
				}
			}).done(function( content ) {
				jQuery(".copydata").text(content);
			});
		});
		jQuery(document).on("click", ".copydata", function(){
			jQuery(this).select();
			document.execCommand("copy");
			jQuery(".copied").show().delay(3000).fadeOut();
		});
		jQuery(document).on("click", ".close-popup", function(){
			jQuery(this).parents(".popup-wrap").remove();
		});

		jQuery(".hypeanimations_container").change(function(){
			if (jQuery(this).val()!="none") {
				jQuery(this).parent().find("div").css("display","block");
			}
			else {
				jQuery(this).parent().find("div").css("display","none");
			}
		});
		jQuery(".updatecontainer").click(function(e){
			e.preventDefault();
			actbutton=jQuery(this);
			actdataid=actbutton.attr("data-id");
			actcontainer=actbutton.parent().find("select[name=container]").val();
			actcontainerclass=actbutton.parent().find("input[name=class]").val();
			actnotestextarea = actbutton.closest("tr").find("td:nth-child(3) textarea[name=notes]");
			actnotes = actnotestextarea.val();

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					"action": "hypeanimations_updatecontainer",
					"dataid": actdataid,
					"container": actcontainer,
					"containerclass": actcontainerclass,
					"notes": actnotes,
					"_wpnonce": "' . esc_js(wp_create_nonce('hypeanimations_updatecontainer')) . '"
				}
			}).done(function( msg ) {
				resp=msg.response;
				if (resp=="ok") {
					// notes update
					if (actbutton.closest("tr").find("textarea[name=notes]").val() !== null) {
						var updated_message = actbutton.closest("tr").find(".hypeanimupdated-notes");
						updated_message.css("display", "block").text("'.__( 'Updated!' , 'tumult-hype-animations' ).'");
						setTimeout(function(){
							updated_message.css("display", "none");
						}, 3000);
					} else { // options update
						if (jQuery(".hypeanimupdated[data-id="+actdataid+"]").length ) { }
						else {
							actbutton.after(\'<div class="hypeanimupdated" data-id="\'+actdataid+\'">'.__( 'Updated!' , 'tumult-hype-animations' ).'</div>\');
							setTimeout(function(){
								jQuery(".hypeanimupdated[data-id="+actdataid+"]").remove();
							}, 3000);
						}
					}
					// Show any added notes
					actnotestextarea.val(actnotes);
				} else {
					alert("'.__( 'Error, please try again!' , 'tumult-hype-animations' ).'");
				}
			});
		});
		jQuery(".animupdate").click(function(e){
			e.preventDefault();
			dataid=jQuery(this).attr("data-id");
			jQuery(this).parent().html(\'<form action="" method="post" accept-charset="utf-8" enctype="multipart/form-data"><input type="hidden" name="dataid" value="\'+dataid+\'">'.wp_nonce_field( "protect_content", "upload_check_oam" ).'<input type="file" name="updatefile"> <input type="submit" name="btn_submit_update" value="'.__( 'Update file' , 'tumult-hype-animations' ).'" /></form>\');
		});

		// Localized confirmation templates for deleting an animation
		var hypeConfirmTemplateWithTitle = ' . $delete_confirm_with_title_json . ';
		var hypeConfirmTemplateWithoutTitle = ' . $delete_confirm_without_title_json . ';

		jQuery(document).on("click", ".animdelete", function(e){
			var el = jQuery(this);
			var title = el.attr("data-title") || "";
			var msg = title ? hypeConfirmTemplateWithTitle.replace("%s", title) : hypeConfirmTemplateWithoutTitle;
			if (!confirm(msg)) {
				e.preventDefault();
				return false;
			}
			// If confirmed, allow the link to proceed (server-side will verify nonce and capability)
		});

		jQuery("#hypeanimations .shortcodeval").click(function(e) {
			this.select();
		});
		jQuery("#hypeanimations").DataTable({
			responsive: true,
			"order": [[ 3, "desc" ]],
			"columns": [
				{"name": "Animation", "orderable": "true"},
				{"name": "Shortcode", "orderable": "true"},
				{"name": "Notes", "orderable": "true", "width": "200px" }, 
				{"name": "Options", "orderable": "false", "width": "260px" },
				{"name": "Last file Update", "orderable": "true", "width": "160px" },
				null
			],
			language: {
				processing:     ' . wp_json_encode( __( 'Processing...', 'tumult-hype-animations' ) ) . ',
				search:         ' . wp_json_encode( __( 'Search:', 'tumult-hype-animations' ) ) . ',
				lengthMenu:     ' . wp_json_encode( sprintf( '%s _MENU_ %s', __( 'Show', 'tumult-hype-animations' ), __( 'animations', 'tumult-hype-animations' ) ) ) . ',
				info:           ' . wp_json_encode( sprintf( '%s _START_ %s _END_ %s _TOTAL_ %s', __( 'Showing', 'tumult-hype-animations' ), __( 'to', 'tumult-hype-animations' ), __( 'of', 'tumult-hype-animations' ), __( 'animations', 'tumult-hype-animations' ) ) ) . ',
				infoEmpty:      ' . wp_json_encode( __( 'No animations found.', 'tumult-hype-animations' ) ) . ',
				loadingRecords: ' . wp_json_encode( __( 'Loading...', 'tumult-hype-animations' ) ) . ',
				zeroRecords:    ' . wp_json_encode( __( 'No animation has been found', 'tumult-hype-animations' ) ) . ',
				emptyTable:     ' . wp_json_encode( __( 'No animation has been added', 'tumult-hype-animations' ) ) . ',
				paginate: {
					first:      ' . wp_json_encode( __( 'First', 'tumult-hype-animations' ) ) . ',
					previous:   ' . wp_json_encode( __( 'Previous', 'tumult-hype-animations' ) ) . ',
					next:       ' . wp_json_encode( __( 'Next', 'tumult-hype-animations' ) ) . ',
					last:       ' . wp_json_encode( __( 'Last', 'tumult-hype-animations' ) ) . '
				}
			}
		});
		});

		function preventDot(e)
		{
			var key = e.charCode ? e.charCode : e.keyCode;
			if (key == 46)
			{				
				return false;
			}    
		}

		jQuery("#choosehypeanimation").click(function (e) {
			e.preventDefault();
			dataid = jQuery("#hypeanimationchoosen").val();
			wp.media.editor.insert("[hypeanimations_anim id=\"" + dataid + "\"]");
			document.location.hash = "";
		});

		function debounce(func, wait) {
			let timeout;
			return function(...args) {
				const context = this;
				clearTimeout(timeout);
				timeout = setTimeout(() => func.apply(context, args), wait);
			};
		}
		const doneTyping = debounce(function() {
			const textarea = jQuery(this);
			textarea.closest("tr").find(".updatecontainer").click(); 
		}, 1000);
		jQuery("textarea[name=notes]").on("keyup", doneTyping);
		</script>';
		}

		add_action('wp_ajax_hypeanimations_updatecontainer', 'hypeanimations_updatecontainer');

		function hypeanimations_updatecontainer() {
			global $wpdb;
			global $hypeanimations_table_name;
			$response = array();
			// Verify capability
			if (!current_user_can('edit_posts')) {
				$response['response'] = 'unauthorized';
				wp_send_json($response);
				exit;
			}

			// Verify the nonce
			$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
			if (!wp_verify_nonce($nonce, 'hypeanimations_updatecontainer')) {
				$response['response'] = 'nonce_verification_failed';
				wp_send_json($response);
				exit;
			}

			if (!empty($_POST['dataid']) && !empty($_POST['container'])) {
				$post_dataid = intval($_POST['dataid']);
				$post_container = sanitize_text_field($_POST['container']);

				$post_notes = isset($_POST['notes']) ? sanitize_text_field($_POST['notes']) : '';
				$post_containerclass = sanitize_html_classname(isset($_POST['containerclass']) ? $_POST['containerclass'] : '');

				// Update the database using a prepared statement
				$wpdb->query($wpdb->prepare("UPDATE {$hypeanimations_table_name} SET container=%s, containerclass=%s, notes=%s WHERE id=%d", $post_container, $post_containerclass, $post_notes, $post_dataid));

				$response['response'] = "ok";
			} else {
				$response['response'] = "error";
			}

		header("Content-Type: application/json");
    if (isset($response)) {
        echo wp_json_encode($response);
    }
    exit();
}

add_action('wp_ajax_hypeanimations_getanimid', 'hypeanimations_getanimid');
function hypeanimations_getanimid(){
    if (!current_user_can('edit_posts')) {
	    wp_die(esc_html__('Unauthorized access', 'tumult-hype-animations'));
    }
    
    global $wpdb;
    global $hypeanimations_table_name;
    $response = array();
	if(!empty($_POST['dataid']) && !empty($_POST['container'])){
		$post_dataid = intval($_POST['dataid']);
		$post_container = sanitize_text_field($_POST['container']);
		$post_containerclass = sanitize_text_field($_POST['containerclass']);
		$update = $wpdb->query($wpdb->prepare("UPDATE {$hypeanimations_table_name} SET container=%s, containerclass=%s WHERE id=%d", $post_container, $post_containerclass, $post_dataid));
        $response['response'] = "ok";
    }
    else { $response['response'] = "error"; }
    header( "Content-Type: application/json" );
    if (isset($response)) { echo wp_json_encode($response); }
    exit();
}

add_action('wp_ajax_hypeanimations_getcontent', 'hypeanimations_getcontent');
function hypeanimations_getcontent(){
	if (!current_user_can('edit_posts')) {
		wp_die(esc_html__('Unauthorized access', 'tumult-hype-animations'));
	}

	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hypeanimations_getcontent_nonce')) {
		wp_die(esc_html__('Invalid nonce.', 'tumult-hype-animations'));
	}

	global $wpdb;
	global $hypeanimations_table_name;
	$response = array();
	if (! empty( $_POST['dataid'] ) ) {
		$post_dataid = intval( $_POST['dataid'] );
		$animcode = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$hypeanimations_table_name} WHERE id = %d LIMIT 1", $post_dataid ) );
		$decoded = html_entity_decode( $animcode );

		// Ensure any .hyperesources references include the uploads base url for this animation.
		// Handles legacy/relative values like "Motion%20Paths.hyperesources/..." by prefixing the uploads path.
		$upload_dir = wp_upload_dir();
		$base_prefix = rtrim( $upload_dir['baseurl'], '/' ) . '/hypeanimations/' . $post_dataid . '/';

		$decoded = preg_replace_callback(
			'#(src=(?:"|\\\'))([^"\\\']+?\.hyperesources/[^"\\\']*)#i',
			function ( $m ) use ( $base_prefix ) {
				$attr = $m[1];
				$url = $m[2];
				// If url already contains protocol or is protocol-relative or absolute path, leave it alone
				if ( preg_match('#^(?:https?:)?//#i', $url) || strpos( $url, '/' ) === 0 ) {
					return $attr . $url;
				}
				// Otherwise prefix with uploads base for this animation.
				// Split the url at the .hyperesources part to safely encode folder names.
				$parts = explode('.hyperesources/', $url, 2);
				if (count($parts) === 2) {
					$folder = rawurlencode($parts[0] . '.hyperesources');
					$rest = $parts[1];
					return $attr . $base_prefix . $folder . '/' . ltrim($rest, '/');
				}
				return $attr . $base_prefix . rawurlencode($url);
			},
			$decoded
		);

		// Convert absolute http/https to protocol-relative (preserve //domain)
		$decoded = str_replace( array( 'https://', 'http://' ), array( '//', '//' ), $decoded );

		echo $decoded;
	} else {
		echo '';
	}
	exit();
}

		// Define an initial allowed extensions array
		$allowlist_tumult_hype_animations = array(
			'images' => array(
				'jpg',
				'jpeg',
				'png',
				'gif',
				'bmp',
				'apng',
				'heic',
				'heif',
				'ico',
				'svg',
				'svgz',
				'tif',
				'tiff',
				'webp',
				'webm',
				'psd',
				'htc', // for ie compatibility
				'pie', // for ie compatibility
			),
			'audio' => array(
				'mp3',
				'wav',
				'aif',
				'ogg',
				'aac',
				'mid',
				'midi',
				'oga',
				'opus',
				'weba',
				'flac',
				'aiff',
			),
			'video' => array(
				'mp4',
				'avi',
				'mov',
				'3g2',
				'3gp',
				'ogv',
				'mpg',
				'm4a',
				'm4v',
				'm4p',
				'mpeg',
				'hevc',
				'm3u8',
				'mpkg',
				'mkv',
				'wmv',
				'flv',
				'wma',
			),
			'fonts' => array(
				'ttf',
				'otf',
				'woff',
				'woff2',
				'eot',
				'ttc',
			),
			'documents' => array(
				'doc',
				'docx',
				'pdf',
				'txt',
				'rtf',
				'rtx',
				'csv',
				'srt',
				'vtt',
				'xls',
				'xlsx',
				'ods',
				'odt',
				'ppt',
				'pptx',
				'epub',
				'odp',
				'key',
				'xhtml',
				'usdz',
				'glb',
			),
			'scripts' => array(
				'js',
				'map', // source map
				'mjs',
				'json',
				'jsonld',
			),
			'stylesheets' => array(
				'css',
				'sass',
				'scss',
				'less',
				'stylus',
			),
			'other' => array(
				'html',
				'htm',
				'plist', // recoverable Tumult Hype plist file
				'xml',
				'yaml',
				'ics',
				'vsd',
				'pps',
				'ppsx',
				'hyperesources' // Tumult Hype resources folder
			),
		);

		function get_flat_allowlist($allowlist_tumult_hype_animations) {
			static $flat_allowlist = null;
			if ($flat_allowlist === null) {
				// Reduce the multidimensional whitelist array into a flat array
				$flat_allowlist = array_reduce($allowlist_tumult_hype_animations, 'array_merge', array());
			}
			return $flat_allowlist;
		}

		// For some reason the function is not working when this isn't called first here. 
		$flat_allowlist = get_flat_allowlist($allowlist_tumult_hype_animations);

		function is_zip_clean($zipFilePath, $allowlist_tumult_hype_animations) {
			$zip = new ZipArchive;
			$disallowedExtensions = [];
			
			// Is it a zip file
			if (!is_readable($zipFilePath) || filesize($zipFilePath) === 0) {
					return new WP_Error('invalid_file', "Invalid or empty file provided");
			}
	
			// Check file signature/magic bytes for ZIP format
			$handle = fopen($zipFilePath, 'rb');
			$magic = fread($handle, 4);
			fclose($handle);
			if ($magic !== "PK\003\004") {
					return new WP_Error('invalid_zip', "File is not a valid ZIP archive");
			}
	
			if ($zip->open($zipFilePath) === TRUE) {
					$flat_allowlist = get_flat_allowlist($allowlist_tumult_hype_animations);
					
					// Check total number of files
					if ($zip->numFiles > 1000) {
							return new WP_Error('too_many_files', "ZIP contains too many files");
					}
	
					// Track required OAM structure
					$has_required_files = false;
	
					for ($i = 0; $i < $zip->numFiles; $i++) {
							$stat = $zip->statIndex($i);
							$filename = $zip->getNameIndex($i);
							$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
							
			    // Check for directory traversal attempts and absolute paths
			    if (strpos($filename, '..') !== false) {
				    return new WP_Error('path_traversal', "Invalid file path detected");
			    }
			    // Reject absolute paths (starting with /) or Windows drive letters (C:\)
			    if (strpos($filename, '/') === 0 || preg_match('/^[A-Za-z]:\\\\/', $filename)) {
				    return new WP_Error('invalid_path', "Invalid file path detected");
			    }
	
							// Verify OAM structure (should have Assets folder and HTML file)
							if (strpos($filename, 'Assets/') === 0 && strpos($filename, '.html') !== false) {
									$has_required_files = true;
							}
	
							// Extension checks
							if (!empty($extension)) {
									if (!in_array($extension, $flat_allowlist)) {
										$disallowedExtensions[] = $extension;
										error_log(sprintf(
											/* translators: 1: disallowed extension, 2: file name containing the extension. */
											__( 'Disallowed file extension detected: %1$s in file %2$s', 'tumult-hype-animations' ),
											$extension,
											$filename
										));
									}
							}
					}
	
					$zip->close();

					// Verify OAM structure
					if (!$has_required_files) {
							return new WP_Error('invalid_oam', "File does not match OAM structure");
					}
	
					if (!empty($disallowedExtensions)) {
						$disallowedExtensionsList = implode(', ', array_unique($disallowedExtensions));
						return new WP_Error(
							'disallowed_file_type', 
							sprintf(
								/* translators: 1: list of blocked file extensions, 2: URL to learn more. */
								__( 'The file contains disallowed extension(s): %1$s. More info: %2$s', 'tumult-hype-animations' ),
								$disallowedExtensionsList,
								'https://forums.tumult.com/t/23637'
							)
						);
					}

					return true;
			}
	
			error_log("Failed to open the zip file: $zipFilePath");
			return new WP_Error('zip_open_failed', "Failed to open the zip file.");
	}
	

function delete_temp_files($directory = null) {
	if ($directory === null) {
		$upload_dir = wp_upload_dir();
		$directory = $upload_dir['basedir'] . '/hypeanimations/tmp/';
	}

	if (!is_dir($directory)) {
		return;
	}

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($files as $fileinfo) {
		$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
		$todo($fileinfo->getRealPath());
	}

	rmdir($directory);
}

/**
 * Elegantly discover the actual structure of an extracted OAM file
 * Handles spaces and special characters in filenames gracefully
 * 
 * @param string $assets_path Path to the Assets/ directory
 * @return array|WP_Error Array with 'hyperesources', 'html', 'html_base' or WP_Error
 */
function discover_oam_structure($assets_path) {
	if (!is_dir($assets_path)) {
		return new WP_Error('assets_missing', "Assets directory not found: $assets_path");
	}
	
	$asset_files = scandir($assets_path);
	$hyperesources_candidates = array();
	$html_candidates = array();
	
	foreach ($asset_files as $file) {
		if ($file === '.' || $file === '..') {
			continue;
		}
		
		if (preg_match('/\.hyperesources$/', $file)) {
			$hyperesources_candidates[] = $file;
		}
		
		if (preg_match('/\.html$/', $file)) {
			$html_candidates[] = $file;
		}
	}
	
	if (empty($hyperesources_candidates)) {
		$msg = "No .hyperesources directory found in extracted Assets";
		error_log('[hypeanimations] ' . $msg . ' (path: ' . $assets_path . ')');
		return new WP_Error('hyperesources_missing', $msg);
	}
	
	if (empty($html_candidates)) {
		$msg = 'No HTML file found in extracted Assets';
		error_log('[hypeanimations] ' . $msg . ' (path: ' . $assets_path . ')');
		return new WP_Error('html_missing', $msg);
	}
	
	// Use the first available files (Hype exports typically have one of each)
	$hyperesources_folder = $hyperesources_candidates[0];
	$html_file = $html_candidates[0];
	$html_base = preg_replace('/\.html$/', '', $html_file);
	
	return array(
		'hyperesources' => $hyperesources_folder,
		'html' => $html_file, 
		'html_base' => $html_base
	);
}
