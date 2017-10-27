<?php
add_shortcode( 'hypeanimations_anim', 'hypeanimations_anim');
function hypeanimations_anim($args){
	global $wpdb;
	global $hypeanimations_table_name;
	$actid=$args['id'];
	$upload_dir = wp_upload_dir();
	$uploadfinaldir = $upload_dir['baseurl'].'/hypeanimations/';
	$output='';	

	$result = $wpdb->get_results($wpdb->prepare("SELECT code,slug,container,containerclass FROM ".$hypeanimations_table_name." WHERE id=%d",$actid));
	
	foreach( $result as $results ) {
		$width = "";
		$height = "";
		$type = "";
		$results->containerclass = sanitize_html_class( $results->containerclass );
		$code = str_replace("https://", "//", html_entity_decode($results->code));
		$code = str_replace("http://", "//", html_entity_decode($results->code));
		list($before, $after) = array_pad(explode('x', $results->slug, 2), -2, null);
		if($before != ""){
			$width = preg_replace('/\D/', '', $before);
		}
		if($after != ""){
			$height = preg_replace('/\D/', '', $after);
			$type = preg_replace('/[0-9]/', '', $after);
		}
		if($type == 'fixed'){
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($width != "" ? 'height="'.$height.'"' : '');
		}else{
			$style_explode_width = explode('width', $results->code);
			$style_explode_height = explode('height', $results->code);
			$style_explode_width = explode(';', $style_explode_width[1]);
			$style_explode_height = explode(';', $style_explode_height[1]);
			$width = str_replace(":", "", $style_explode_width[0]);
			$height = str_replace(":", "", $style_explode_height[0]);
			$temp = ($width != "" ? 'width="'.$width.'"' : '').' '.($width != "" ? 'height="'.$height.'"' : '');
		}
		if ($results->container=='div') { $output.='<div'.($results->containerclass!='' ? ' class="'.$results->containerclass.'"' : '').'>'; }
		if ($results->container=='iframe' && file_exists($upload_dir['basedir'].'/hypeanimations/'.$actid.'/index.html')) { $output.='<iframe style="border:none;" frameborder="0" '.$temp.' '.($results->containerclass!='' ? 'class="'.$results->containerclass.'"' : '').' src="'.wp_upload_dir()['baseurl'].'/hypeanimations/'.$actid.'/index.html">'; }elseif ($results->container=='iframe') { $output.='<iframe '.$temp.' '.($results->containerclass!='' ? 'class="'.$results->containerclass.'"' : '').' src="'.site_url().'?just_hypeanimations='.$actid.'">'; }
		if ($results->container!='iframe') { $output.=$code; }
		if ($results->container=='div') { $output.='</div>'; }
		if ($results->container=='iframe') { $output.='</iframe>'; }
	}
	return $output;
	}