<?php
add_action( "admin_menu", 'hypeanimations_panel_upload' );
function hypeanimations_panel_upload() {
    global $wpdb, $hypeanimations_table_name;
    $upload_dir = wp_upload_dir();
    $anims_dir = $upload_dir['basedir'] . '/hypeanimations/';

    if (is_user_logged_in() && isset($_FILES['file'])) {
        $nonce = $_POST['upload_check_oam'];
        if (!wp_verify_nonce($nonce, 'protect_content')) {
            die('Security check failed');
        }

        $file = $_FILES['file'];
        $allowed_file_types = array('oam' => 'application/octet-stream');

        $upload_overrides = array(
            'test_form' => false,
            'mimes' => $allowed_file_types
        );

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            echo "Error: " . $uploaded_file['error'];
            exit;
        }

        $uploadfile = $uploaded_file['file'];

				// Check the zip file for disallowed files in memory
				$zip_clean = is_zip_clean($uploadfile, apply_filters('tumult_hype_animations_whitelist', array()));
				if (is_wp_error($zip_clean)) {
					// show error message displaying the file extension which is not allowed
					echo $zip_clean->get_error_message();
					wp_delete_file($uploadfile); // Delete the uploaded ZIP file to prevent processing
					exit;
				}
 
        WP_Filesystem();
        $uploaddir = $anims_dir . 'tmp/';
        $uploadfinaldir = $anims_dir;
        $unzipfile = unzip_file($uploadfile, $uploaddir);
        if ($unzipfile) {
            if (file_exists($uploadfile)) {
                wp_delete_file($uploadfile);
            }
            if (file_exists($uploaddir . '/config.xml')) {
                wp_delete_file($uploaddir . '/config.xml');
            }

            $new_name = str_replace('.oam', '', basename(sanitize_file_name($_FILES['file']['name'])));
            rename($uploaddir . 'Assets/' . $new_name . '.hyperesources', $uploaddir . 'Assets/index.hyperesources');

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploaddir . 'Assets/'), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                if ($file->isFile()) {
                    chmod($file->getRealPath(), 0644);
                }
            }

            $files = scandir($uploaddir . 'Assets/');
            foreach ($files as $file) {
                if (preg_match('~.html~', $file)) {
                    $actfile = explode('.html', $file);
                    $maxid = $wpdb->get_var("SELECT MAX(id) FROM $hypeanimations_table_name");
                    $maxid = $maxid ? $maxid + 1 : 1;

                    $wpdb->insert(
                        $hypeanimations_table_name,
                        array(
                            'nom' => $new_name,
                            'slug' => str_replace(' ', '', strtolower($new_name)),
                            'code' => '',
                            'updated' => time(),
                            'container' => 'div'
                        )
                    );
                    $lastid = $wpdb->insert_id;

                    if (!is_dir($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $new_name . '.hyperesources/')) {
                        mkdir($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $new_name . '.hyperesources/', 0755, true);
                    }

                    $jsfiles = scandir($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/');
                    foreach ($jsfiles as $jsfile) {
                        if ($jsfile != '.' && $jsfile != '..' && !is_dir($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $jsfile)) {
                            copy($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $jsfile, $uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $new_name . '.hyperesources/' . $jsfile);
                            wp_delete_file($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/' . $jsfile);
                        }
                    }

                    rename($uploaddir . 'Assets/' . $actfile[0] . '.hyperesources/', $uploadfinaldir . $lastid . '/');

                    $agarder1 = '';
                    $recordlines = 0;
                    $handle = fopen($uploaddir . 'Assets/' . $actfile[0] . '.html', "r");
                    if ($handle) {
                        while (($line = fgets($handle)) !== false) {
                            $line = str_replace($new_name . '.hyperesources', $upload_dir['baseurl'] . '/hypeanimations/' . $lastid . '/' . $new_name . '.hyperesources', $line);
                            if (preg_match('~<div id="~', $line)) {
                                $recordlines = 1;
                            }
                            if ($recordlines == 1) {
                                $agarder1 .= $line;
                            }
                            if (preg_match('~div>~', $line)) {
                                $recordlines = 0;
                            }
                        }
                        fclose($handle);
                    }

                    $wpdb->update(
                        $hypeanimations_table_name,
                        array('code' => addslashes(htmlentities($agarder1))),
                        array('id' => $lastid)
                    );

                    copy($uploaddir . 'Assets/' . $actfile[0] . '.html', $upload_dir['basedir'] . '/hypeanimations/' . $lastid . '/' . $actfile[0] . '.html');

                    if (file_exists($uploaddir . 'Assets/' . $actfile[0] . '.html')) {
                        wp_delete_file($uploaddir . 'Assets/' . $actfile[0] . '.html');
                    }
                    if (file_exists($uploaddir . 'Assets/')) {
                        hyperrmdir($uploaddir . 'Assets/');
                    }
                }
            }
            echo $lastid;
            exit();
        } else {
            echo "Failed to unzip the file.";
            exit();
        }
    }
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
	dictDefaultMessage: "'.__( 'Drop .OAM file or click here to upload<br>(Maximum upload size '. $upload_mb .')' , 'hype-animations' ).'",

	accept: function(file, done) {
		if (hasWhiteSpace(file.name)) {
				done("You seem to have a space in your animation name. Please remove the space and regenerate the animation.");
		} else {
				done();
		}
},
success: function(file, resp) {
	if(isNaN(parseInt(resp))) { // error string instead of numeric short code
		jQuery(".dropzone").after("<div class=\"dropzone2\" style=\"display:none\"><br>" + resp + "</div>");
		jQuery(".dropzone2").css("display", "block");
		jQuery(".dropzone").remove();	
	} else {
		jQuery(".dropzone").after("<div class=\"dropzone2\" style=\"display:none\"><br>'.__( 'Insert the following shortcode where you want to display the animation' , 'hype-animations' ).':<br><br> <span style=\"font-family:monospace\">[hypeanimations_anim id=\"" + resp + "\"]</span></div>");
		jQuery(".dropzone2").css("display", "block");
		jQuery(".dropzone").remove();	
	}
}
};

</script>
		<div>
			<header>
				<a href="#fermer" alt="close" id="closeDroper" class="closemodal">&#10005;</a>
				<h2>'.__( 'Upload new animation' , 'hype-animations' ).'</h2>
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
	if( !isset($_GET['page']) || $_GET['page'] != 'hypeanimations_panel' ) {
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
	echo '<br><h1>Tumult Hype Animations (v'.$version.')</h1>
	<p>&nbsp;</p>
	</div>
	<h2>'.__( 'Add new animation' , 'hype-animations' ).'</h2>
	<div class="hypeanimbloc">
	'.__( 'Upload an .OAM file exported by <a href="https://tumult.com/hype?utm_source=wpplugin">Tumult Hype</a> and a shortcode will be generated which you can insert in posts and pages. <a href="https://forums.tumult.com/t/hype-animations-wordpress-plugin/11074" target="_blank">Need help?</a>' , 'hype-animations' ).'<br><br>
	<a href="#openModal1" class="button" id="add_hypeanimations_shortcode_newbutton" style="outline: medium none !important; cursor: pointer;" ><i class="dashicons-before dashicons-plus-alt"></i> '.__( 'Upload new animation' , 'hype-animations' ).'</a>
	</div>';
	
	// Verify nonce before delete
	$delete = isset($_GET['delete']) ? ceil($_GET['delete']) : 0;
	if ($delete > 0) {
  if ( !wp_verify_nonce($_REQUEST['_wpnonce'], 'delete-animation_' . $delete)) {
    wp_die('Security check failed'); 
  }
			
    $animtitle = $wpdb->get_var($wpdb->prepare("SELECT nom FROM $hypeanimations_table_name WHERE id=%d", ceil($_GET['delete'])));
    $delete = $wpdb->query($wpdb->prepare("DELETE FROM $hypeanimations_table_name WHERE id=%d", ceil($_GET['delete'])));
    hyperrmdir($anims_dir.ceil($_GET['delete']).'/');

		if ($animtitle != '') {
			echo '<p>&nbsp;</p><p><span style="padding:10px;color:#FFF;background:#cc0000;">' . $animtitle . ' ' . __( 'has been deleted.', 'hype-animations' ) . '</span></p>';
		}
}
	$hypeupdated = 0;
	if (is_user_logged_in() && isset($_FILES['updatefile']) && sanitize_text_field($_POST['dataid']>0)) {

		$nonce = $_POST['upload_check_oam'];
		if ( ! wp_verify_nonce( $_POST['upload_check_oam'], 'protect_content' ) ) {
		    die( 'Security check' ); 
		} else {
		
			if(strpos(basename(sanitize_text_field($_FILES['updatefile']['name'])), " ") !== false)
			{
			   echo "<script>alert('You seem to have a space in your animation name. Please remove the space and regenerate the animation.');location.reload();</script>";
			   die;
			}

			$actdataid=ceil($_POST['dataid']);
			$uploaddir = $anims_dir.'tmp/';
			$uploadfinaldir = $anims_dir;
			$uploadfile = $uploaddir . basename(sanitize_file_name($_FILES['updatefile']['name']));
			if (move_uploaded_file($_FILES['updatefile']['tmp_name'], $uploadfile)) {
				WP_Filesystem();

			// Check the zip file for disallowed files in memory
			$zip_clean = is_zip_clean($uploadfile, apply_filters('tumult_hype_animations_whitelist', array()));
			if (is_wp_error($zip_clean)) {
				// show error message displaying the file extension which is not allowed
				echo $zip_clean->get_error_message();
				exit;
			}

				// Unzip the file
				$unzipfile = unzip_file( $uploadfile, $uploaddir);
				if (file_exists($uploadfile)) {
					wp_delete_file($uploadfile);
				}
				if (file_exists($uploaddir.'/config.xml')) {
					wp_delete_file($uploaddir.'/config.xml');
				}
				$new_name = str_replace('.oam', '', basename(sanitize_file_name($_FILES['updatefile']['name'])));
				rename($uploaddir.'Assets/'.$new_name.'.hyperesources', $uploaddir.'Assets/index.hyperesources');

				$files = scandir($uploaddir.'Assets/');
				for ($i=0;isset($files[$i]);$i++) {
					if (preg_match('~.html~',$files[$i])) {
						$actfile=explode('.html',$files[$i]);
						$maxid = $wpdb->get_var($wpdb->prepare("SELECT id FROM $hypeanimations_table_name WHERE id > %d ORDER BY id DESC LIMIT 1", 0));
						if ($maxid>0) {
							$maxid=$maxid+1;
						}
						else {
							$maxid=1;
						}

						$data_updt = array(
							'nom' => $new_name
						);

						$update_name = $wpdb->query( $wpdb->prepare( "UPDATE $hypeanimations_table_name SET `nom` = %s WHERE `id` = %d",$new_name,  $actdataid ) );

						if (file_exists($uploadfinaldir.$actdataid.'/')) {
							hyperrmdir($uploadfinaldir.$actdataid.'/');
						}

						@mkdir($uploaddir.'Assets/'.$actfile[0].'.hyperesources/'.$new_name.'.hyperesources/', 0755, true);

						$jsfiles = scandir($uploaddir.'Assets/'.$actfile[0].'.hyperesources/');
						for ($j=0;isset($jsfiles[$j]);$j++) {
							if($jsfiles[$j] != '.' && $jsfiles[$j] != '..'){
								if(!is_dir($uploaddir.'Assets/'.$actfile[0].'.hyperesources/'.$jsfiles[$j])){
									copy($uploaddir.'Assets/'.$actfile[0].'.hyperesources/'.$jsfiles[$j], $uploaddir.'Assets/'.$actfile[0].'.hyperesources/'.$new_name.'.hyperesources/'.$jsfiles[$j]);
									wp_delete_file($uploaddir.'Assets/'.$actfile[0].'.hyperesources/'.$jsfiles[$j]);
								}
							}
						}
						if (file_exists($uploaddir.'Assets/'.$actfile[0].'.hyperesources/')) {
							rename($uploaddir.'Assets/'.$actfile[0].'.hyperesources/', $uploadfinaldir.$actdataid.'/');
						}
						$agarder1='';
						$recordlines=0;
						$handle = fopen($uploaddir.'Assets/'.$actfile[0].'.html', "r");
						if ($handle) {
							while (($line = fgets($handle)) !== false) {
								$line=str_replace($new_name.'.hyperesources',$upload_dir['baseurl'].'/hypeanimations/'.$actdataid.'/'.$new_name.'.hyperesources',$line);
								if (preg_match('~<div id="~',$line)) {
									$recordlines=1;
								}
								if ($recordlines==1) {
									$agarder1.=$line;
								}
								if (preg_match('~div>~',$line)) {
									$recordlines=0;
								}
								//echo htmlentities($line);
							}

							fclose($handle);
						} else {
							//echo 'error';
						}
						$update = $wpdb -> query($wpdb->prepare("UPDATE $hypeanimations_table_name SET code=%s,updated=%s WHERE `id` = %d",addslashes(htmlentities($agarder1)), time(), $actdataid));
						//copy index.html
						copy($uploaddir.'Assets/'.$actfile[0].'.html', $upload_dir['basedir'].'/hypeanimations/'.$actdataid.'/'.$actfile[0].'.html');

						if (file_exists($uploaddir.'Assets/'.$actfile[0].'.html')) {
							wp_delete_file($uploaddir.'Assets/'.$actfile[0].'.html');
						}
						if (file_exists($uploaddir.'Assets/')) {
							hyperrmdir($uploaddir.'Assets/');
						}
						$hypeupdated=$actdataid;
						$hypeupdatetd_title=$new_name;
					}
				}
			}
			else {
				echo "Erreur";
			}
		}
		//print_r($_FILES);
	}
 echo '<p style="line-height:0px;clear:both">&nbsp;</p>
	'.($hypeupdated>0 ? '<p><span style="padding:10px;color:#FFF;background:#009933;">'.$hypeupdatetd_title.' has been updated!</style></p><p>&nbsp;</p>' : '').'
	<h2>'.__( 'Manage animations' , 'hype-animations' ).'</h2>
	<table cellpadding="0" cellspacing="0" id="hypeanimations">
		<thead>
			<tr>
				<th>Animation</th>
				<th>Shortcode</th>
				<th>Notes<br><small>(autosaved)</small></th>
				<th>Options</th>
				<th>'.__( 'Last file update' , 'hype-animations' ).'</th>
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
				<td>' . $results->nom . '</td>
				<td>
					<input class="shortcodeval" type="text" spellcheck="false" value="[hypeanimations_anim id=&quot;' . $results->id . '&quot;]"></input>
				</td>
				<td>
					<textarea name="notes" spellcheck="false" style="resize: vertical; min-height: 20px;">' . stripslashes($results->notes) .  '</textarea>
				</td>
				<td align="left" style="text-align:left;">
					 ' . __( 'Add a container around the animation:', 'hype-animations' ) . '<br>
					<select class="hypeanimations_container" name="container">
							<option value="div" ' . ($results->container == 'div' ? 'selected' : '') . '>&lt;div&gt;</option>
							<option value="iframe" ' . ($results->container == 'iframe' ? 'selected' : '') . '>&lt;iframe&gt;</option>
						</select><br>
					' . __( 'Container CSS class', 'hype-animations' ) .': <br>
					<div ' . ($results->container == 'none' ? 'style="display:none;"' : '') . '>
							 <input onkeypress="return preventDot(event);" type="text" name="class" spellcheck="false" placeholder="Myclass" style="width:130px;" value="' . esc_attr($results->containerclass) . '">
					</div>
					<input type="button" value="' . __( 'Update', 'hype-animations' ) . '" class="updatecontainer" data-id="' . $results->id . '">
				</td>
				<td>' . ($results->updated == 0 ? '<em>' . __( 'No data', 'hype-animations' ) . '</em>' : date('Y/m/d', $results->updated) . '<br>' . date('H:i:s', $results->updated)) . '</td>
				<td>
					<a href="javascript:void(0)" id="' . $results->id . '" class="animcopy">' . __( 'Copy Code', 'hype-animations' ) . '</a>
					<a href="admin.php?page=hypeanimations_panel&update=' . $results->id . '" class="animupdate" data-id="' . $results->id . '">' . __( 'Replace OAM', 'hype-animations' ) . '</a>
					<a href="admin.php?page=hypeanimations_panel&delete=' . $results->id . '&_wpnonce=' . $delete_nonce . '" class="animdelete">' . __( 'Delete', 'hype-animations' ) . '</a>
				</td>
			</tr>';
		}
	
	
	echo '</tbody> 
	</table> 

	<script>
	jQuery(document).ready(function(jQuery){
		jQuery(document).on("click", ".animcopy", function(){
			jQuery("body").append("<div class=\'popup-wrap\'> <div class=\'popup-overlay\'> <div class=\'popup\'><h3 class=\'popup-heading\'>Copy Embed Code</h3><textarea spellcheck=\'false\' class=\'copydata\' rows=\'10\' cols=\'30\' style=\'width:100%\' readonly></textarea><span class=\'close-popup\'>&#10005;</span><span class=\'copied\'>Copied to clipboard.</span></div> </div>");

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					"action": "hypeanimations_getcontent",
					"dataid": jQuery(this).attr("id")
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
					if (jQuery(".hypeanimupdated[data-id="+actdataid+"]").length ) { }
					else {
						actbutton.after(\'<div class="hypeanimupdated" data-id="\'+actdataid+\'">'.__( 'Updated!' , 'hype-animations' ).'</div>\');
						setTimeout(function(){
							jQuery(".hypeanimupdated[data-id="+actdataid+"]").remove();
						}, 3000);
					}
					// Show any added notes
					actnotestextarea.val(actnotes);
				} else {
					alert("'.__( 'Error, please try again!' , 'hype-animations' ).'");
				}
			});
		});
		jQuery(".animupdate").click(function(e){
			e.preventDefault();
			dataid=jQuery(this).attr("data-id");
			jQuery(this).parent().html(\'<form action="" method="post" accept-charset="utf-8" enctype="multipart/form-data"><input type="hidden" name="dataid" value="\'+dataid+\'">'.wp_nonce_field( "protect_content", "upload_check_oam" ).'<input type="file" name="updatefile"> <input type="submit" name="btn_submit_update" value="'.__( 'Update file' , 'hype-animations' ).'" /></form>\');
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
				processing:     "'.__( 'Processing...' , 'hype-animations' ).'",
				search:         "'.__( 'Search:' , 'hype-animations' ).'",
				lengthMenu:    "'.__( 'Show' , 'hype-animations' ).' _MENU_ '.__( 'animations' , 'hype-animations' ).'",
				info:           "'.__( 'Showing' , 'hype-animations' ).' _START_ '.__( 'to' , 'hype-animations' ).' _END_ '.__( 'of' , 'hype-animations' ).' _TOTAL_ '.__( 'animations' , 'hype-animations' ).'",
				infoEmpty:      "'.__( 'No animations found.' , 'hype-animations' ).'",
				loadingRecords: "'.__( 'Loading...' , 'hype-animations' ).'",
				zeroRecords:    "'.__( 'No animation has been found' , 'hype-animations' ).'",
				emptyTable:     "'.__( 'No animation has been added' , 'hype-animations' ).'",
				paginate: {
					first:      "'.__( 'First' , 'hype-animations' ).'",
					previous:   "'.__( 'Previous' , 'hype-animations' ).'",
					next:       "'.__( 'Next' , 'hype-animations' ).'",
					last:       "'.__( 'Last' , 'hype-animations' ).'"
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

		function hasWhiteSpace(s) {
			return s.indexOf(" ") >= 0;
		}
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

			// Verify the nonce
			$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
			if (!wp_verify_nonce($nonce, 'hypeanimations_updatecontainer')) {
				$response['response'] = 'nonce_verification_failed';
				wp_send_json($response);
				exit;
			}

			if (!empty(sanitize_text_field($_POST['dataid'])) && !empty(sanitize_text_field($_POST['container']))) {
				$post_dataid = sanitize_text_field($_POST['dataid']);
				$post_container = sanitize_text_field($_POST['container']);

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

				$post_notes = sanitize_text_field($_POST['notes'] ?? '');
				$post_containerclass = sanitize_html_classname($_POST['containerclass']);

				// Update the database query to include the 'notes' field
				$wpdb->query($wpdb->prepare("UPDATE $hypeanimations_table_name SET container=%s, containerclass=%s, notes=%s WHERE id=%d", $post_container, $post_containerclass, $post_notes, $post_dataid));

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
	global $wpdb;
	global $hypeanimations_table_name;
    $response = array();
    if(!empty(sanitize_text_field($_POST['dataid'])) && !empty(sanitize_text_field($_POST['container']))){
		$post_dataid = sanitize_text_field($_POST['dataid']);
		$post_container = sanitize_text_field($_POST['container']);
		$post_containerclass = sanitize_text_field($_POST['containerclass']);
		$update = $wpdb->query($wpdb->prepare("UPDATE $hypeanimations_table_name SET container=%s, containerclass=%s WHERE id=%d",$post_container, $post_containerclass, $post_dataid));
		$response['response'] = "ok";
    }
	else { $response['response'] = "error"; }
    header( "Content-Type: application/json" );
    if (isset($response)) { echo wp_json_encode($response); }
    exit();
}

		add_action('wp_ajax_hypeanimations_getcontent', 'hypeanimations_getcontent');
		function hypeanimations_getcontent(){
			global $wpdb;
			global $hypeanimations_table_name;
			$response = array();
			if(!empty(sanitize_text_field($_POST['dataid']))){

				$post_dataid= sanitize_text_field($_POST['dataid']);
				$animcode = $wpdb->get_var($wpdb->prepare("SELECT code FROM $hypeanimations_table_name WHERE id = %d LIMIT 1", $post_dataid));
				$animcode = str_replace("https://", "//", html_entity_decode($animcode));
				$animcode = str_replace("http://", "//", html_entity_decode($animcode));
			}
			echo html_entity_decode($animcode);
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
			$disallowedExtensions = []; // To store disallowed extensions

			if ($zip->open($zipFilePath) === TRUE) {
				$flat_allowlist = get_flat_allowlist($allowlist_tumult_hype_animations);

				// Scan the files in the ZIP archive
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$filename = $zip->getNameIndex($i);
					$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

					// Check if the file extension is in the whitelist
					if (!empty($extension) && !in_array($extension, $flat_allowlist)) {
						if (!in_array($extension, $disallowedExtensions)) {
							$disallowedExtensions[] = $extension;
							error_log(sprintf(
								__('Disallowed file extension detected: %s in file %s', 'hype-animations'),
								$extension,
								$filename
							));
						}
					}
				}

				$zip->close();

				// Check if there are any disallowed extensions
				if (!empty($disallowedExtensions)) {
					$disallowedExtensionsList = implode(', ', $disallowedExtensions);
					//error_log("Cleaning up due to disallowed extension(s): $disallowedExtensionsList");
					$requestmoreinfolink = sprintf(
						__('<br>'.' More info here: %s', 'hype-animations'),
						'https://forums.tumult.com/t/23637'
					);
					return new WP_Error('disallowed_file_type', "The file contains disallowed extension(s): $disallowedExtensionsList. $requestmoreinfolink");
				}

				// If all files are allowed, return true
				return true;
			}

			// Return an error if the zip file failed to open
			error_log("Failed to open the zip file: $zipFilePath");
			return new WP_Error('zip_open_failed', "Failed to open the zip file.");
		}

		function delete_temp_files($directory) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileinfo) {
				$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
				$todo($fileinfo->getRealPath());
			}

			rmdir($directory);
		}
		