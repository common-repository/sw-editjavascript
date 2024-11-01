<?php
/*
Plugin Name: SW EditJS
Plugin URI: http://scuderia-web.com/wordpress-plugin/sw_edit_javascript.php
Description: テーマエディターでJSファイルを編集できるようにするプラグイン
Version: 1.1
Author: ScuderiaWeb
Author URI: http://scuderia-web.com/

ReleaseNote
2008.06.04	1.1		add_filter initに変更
2008.06.03	1.0		ﾘﾘｰｽ
*/

$EditJS = new SW_EditJS();
Class SW_EditJS {
	
	/* JSファイルのディレクトリ（テンプレートファイルディレクトリ内のパス。最後の「/」は不要） */
	var $jsfile_dir = "/js";
	
	/* デフォルトのテンプレート名（0:使用しない、1:使用する） */
	var $default_description = 0;
	
	/* テーマエディターに表示する名称（0:ファイル名、1:Template Name、2:File Name）※「File Name」は独自拡張 */
	var $disp_name = 2;
	
	/* 並び順
		0:指定しない
		1:ファイル名順(昇順)
		2:ファイル名順(降順)
		3:テンプレート名順(昇順)
		4:テンプレート名順(降順)
	*/
	var $file_sort = 3;
	
	/* --------------------------------------------------------------------------------------------- */
	
	function SW_EditJS() {
		add_filter('init', array(&$this, 'addJSFile'));
		add_filter('admin_head', array(&$this, 'dispJSFile'));
		add_filter('admin_footer', array(&$this, 'loadJSFile'));
		
	}
	
	function dispJSFile() {
		global $themes, $theme, $file, $wp_file_descriptions;
		if(strcmp($_SERVER['SCRIPT_NAME'],"/wp-admin/theme-editor.php") != 0)
			return;
		
		// デフォルトのテンプレート名の解除
		if($this->default_description == 0)
			$wp_file_descriptions = array();
		
		// 表示名の設定
		$this->set_dispName();
		
		// "Stylesheet Files"からJSファイルを除外
		while(true) {
			if(count($themes[$theme]["Stylesheet Files"]) == $themes[$theme]["JS Files index"])
				break;
			
			array_pop($themes[$theme]["Stylesheet Files"]);
		}
		
		unset($themes[$theme]["JS Files index"]);
		
		// ソート
		switch($this->file_sort) {
			case 1:		// ファイル名順(昇順)
				sort($themes[$theme]["Template Files"]);
				sort($themes[$theme]["Stylesheet Files"]);
				sort($themes[$theme]["Javascript Files"]);
				break;
			case 2:		// ファイル名順(降順)
				rsort($themes[$theme]["Template Files"]);
				rsort($themes[$theme]["Stylesheet Files"]);
				rsort($themes[$theme]["Javascript Files"]);
				break;
			case 3:		// テンプレート名順(昇順)
			case 4:		// テンプレート名順(降順)
				
				// template
				$tmp_template_array = array();
				foreach($themes[$theme]['Template Files'] as $template_file) {
					$description = $this->remove_line(trim(get_file_description($template_file)));
					$tmp_template_array[$description] = $template_file;
				}
				$themes[$theme]["Template Files"] = $tmp_template_array;
				
				// stylesheet
				$tmp_style_array = array();
				foreach($themes[$theme]['Stylesheet Files'] as $style_file) {
					$description = $this->remove_line(trim(get_file_description($style_file)));
					$tmp_style_array[$description] = $style_file;
				}
				$themes[$theme]["Stylesheet Files"] = $tmp_style_array;
				
				// js
				$tmp_js_array = array();
				foreach($themes[$theme]['Javascript Files'] as $js_file) {
					$description = $this->remove_line(trim(get_file_description($js_file)));
					$tmp_js_array[$description] = $js_file;
				}
				$themes[$theme]["Javascript Files"] = $tmp_js_array;
				
				switch($this->file_sort) {
					case 3:
						ksort($themes[$theme]["Template Files"], 3);
						ksort($themes[$theme]["Stylesheet Files"], 3);
						ksort($themes[$theme]["Javascript Files"], 3);
						break;
					case 4:
						krsort($themes[$theme]["Template Files"], 3);
						krsort($themes[$theme]["Stylesheet Files"], 3);
						krsort($themes[$theme]["Javascript Files"], 3);
						break;
				}
				
				break;
			default:
				break;
		}
?>
		<script type="text/javascript">
		<!--
			function addTemplateside(){
				var html = "<h4>JavaScript</h4>";
				html += "<ul>";
<?php
	
	foreach($themes[$theme]['Javascript Files'] as $javascript_file) {
		$description = $this->remove_line(get_file_description($javascript_file));
		
		$javascript_show = basename($javascript_file);
		$filedesc = ( $description != $javascript_file ) ? "$description <span class=\\\"nonessential\\\">($javascript_show)</span>" : "$description";
		$filedesc = ( $javascript_file == $file ) ? "<span class=\\\"highlight\\\">$description <span class=\\\"nonessential\\\">($javascript_show)</span></span>" : $filedesc;
?>
				html += "<li><a href=\"theme-editor.php?file=<?= $javascript_file ?>&amp;theme=<?= urlencode($theme) ?>\"><?php echo $filedesc ?></a></li>";
<?php } ?>
				html += "</ul>";
				document.getElementById("templateside").innerHTML += html;

			}
		// -->
		</script>
<?php
	}
	
	function loadJSFile() {
		if(strcmp($_SERVER['SCRIPT_NAME'],"/wp-admin/theme-editor.php") != 0)
			return;
		
		echo '
		<script type="text/javascript">
		<!--
			addTemplateside();
		// -->
		</script>';
	}
	
	function addJSFile() {
		global $wp_themes, $theme;
		
		if(strcmp($_SERVER['SCRIPT_NAME'],"/wp-admin/theme-editor.php") != 0)
			return;
		
		$wp_themes = get_themes();
		$theme_root = get_theme_root();
		foreach($wp_themes as $theme_name => $value) {
			$wp_themes[$theme_name]["Javascript Files"] = array();
			$wp_themes[$theme_name]["JS Files index"] = count($wp_themes[$theme_name]["Stylesheet Files"]);
			$i = $wp_themes[$theme_name]["JS Files index"];
			$js_dir = get_theme_root().'/'.$wp_themes[$theme_name]["Template"].$this->jsfile_dir;

			if ( $d_handle = @OpenDir($js_dir) ) { 
				while ( $filename = ReadDir( $d_handle ) ) {
					if ( $filename == '.' || $filename == '..')
						continue;
					
					if (strcmp(substr($filename, -3), ".js") == 0) {
						$wp_themes[$theme_name]["Stylesheet Files"][$i] = $wp_themes[$theme_name]["Template Dir"].$this->jsfile_dir."/".$filename;
						array_push($wp_themes[$theme_name]["Javascript Files"], $wp_themes[$theme_name]["Template Dir"].$this->jsfile_dir."/".$filename);
						$i++;
					}
				}
			}
			@CloseDir($d_handle);
			
		}
		
	}
	
	function set_dispName() {
		global $themes, $theme, $wp_file_descriptions;
		
		switch($this->disp_name) {
			case 0:		// ファイル名
				foreach($themes[$theme]['Template Files'] as $template_file) {
					$wp_file_descriptions[basename($template_file)] = basename($template_file);
				}
				foreach($themes[$theme]['Stylesheet Files'] as $style_file) {
					$wp_file_descriptions[basename($style_file)] = basename($style_file);
				}
				foreach($themes[$theme]['Javascript Files'] as $js_file) {
					$wp_file_descriptions[basename($js_file)] = basename($js_file);
				}
				break;
			
			case 1:		// Template Name
				break;
			
			case 2:		// File Name
				foreach($themes[$theme]['Template Files'] as $template_file) {
					$wp_file_descriptions[basename($template_file)] = $this->remove_line($this->get_file_name($template_file));
				}
				foreach($themes[$theme]['Stylesheet Files'] as $style_file) {
					$wp_file_descriptions[basename($style_file)] = $this->remove_line($this->get_file_name($style_file));
				}
				foreach($themes[$theme]['Javascript Files'] as $js_file) {
					$wp_file_descriptions[basename($js_file)] = $this->remove_line($this->get_file_name($js_file));
				}
				break;
		}
	}
	
	function get_file_name($file) {
		global $wp_file_descriptions;

		if ( isset( $wp_file_descriptions[basename( $file )] ) ) {
			return $wp_file_descriptions[basename( $file )];
		}
		elseif ( file_exists( ABSPATH . $file ) && is_file( ABSPATH . $file ) ) {
			$template_data = implode( '', file( ABSPATH . $file ) );
			if ( preg_match( "|File Name:(.*)|i", $template_data, $name )) {
				return $name[1];
			}
		}

		return basename( $file );
	}
	
	// 改行コードを除外
	function remove_line($str) {
		$str = str_replace("\n","",$str);
		$str = str_replace("\r","",$str);
		return $str;
	}
}
?>