<?php
function mail_templates() {
	global $wpdb;
	$table_obj = new Templets_Table(); // Create new table class

	if (!empty($_GET['action'])) {
		$templates =  Templets_Table::get_templets();
		$tid = verify_data( $_GET['id'], 'int');
		switch ($_GET['action']) {
			case 'active':
				$table_name = $wpdb->prefix . 'es_templates';
				$wpdb->update( $table_name,
					array( 'active' => ($templates[$tid-1]['active'] == true ? false : true) ),
					array( 'id' => $tid ),
					array( '%d' ) );
				break;
			case 'edit':
				if (isset($_POST['save'])) {
					$table_name = $wpdb->prefix . 'es_templates';
					$wpdb->update( $table_name,
						array( 'template' =>  base64_encode(stripslashes_deep($_POST['es_edit'])),
							   'subject' => base64_encode(stripslashes_deep($_POST['subject'])) ),
						array( 'id' => $tid ),
						array( '%s', '%s' ) );
				} else {
					tinymce_init( ($templates[$tid-1]['slug'] == 'PT') ? true : false);
					$data = base64_decode($templates[$tid-1]['template']);
					$subject = base64_decode($templates[$tid-1]['subject']);
					echo '<p><h1>Edit '.strtolower($templates[$tid-1]['title']).'</h1></p>';
					echo '<form method="post">
					      E-mail subject <em style="font-size:80%;">(Subject support #sitename#
					      '.(($templates[$tid-1]['slug'] == 'PT') ? ' and #newposts#' : '').' shortcode)</em><br>
					      <input style="width:50%" type="text" value="'.$subject.'" name="subject" placeholder="Subject title for e-mail" required><br><br>
					      <textarea name="es_edit" oncontextmenu="return false;">'.$data.'</textarea><br>
					      <p><input type="submit" name="save" class="button-primary" value="Save" />
						  <a href="?page=es-settings&tab=mail_templates" class="button-primary">Cancel</a></p>
						  </form>';
					return;
				}
				break;
			default:
				show_infobox('It look like there was a problem.', 'Illegal action requested.', '#F98A89');
				break;
		}
	}
	echo '<h2>Manage and edit e-mail templates</h2>';
	$table_obj->prepare_items();
	$table_obj->display();
	echo '<div style="float:right;">
	<span style="color: green;">&#11044;&nbsp</span><em>Templete active</em>
	<span style="color: red; padding-left: 10px;">&#11044;&nbsp</span><em>Templete deactivated</em></div>';
}

/**
 * Create the tables to show the subscribers
 *
 * Extend WP_List_Table class
 */
class Templets_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular'  => 'Template', 		//singular name of the listed records
			'plural'    => 'Templates',   	//plural name of the listed records
			'ajax'      => false			//does this table support ajax?

		]);
	}

/**
* Create templates array
*
* @param int $per_page
* @param int $page_number
*
* @return mixed
*/
	public static function get_templets() {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}es_templates";
		return $wpdb->get_results($sql, 'ARRAY_A');
	}

/**
* Function methods for columns output)
*
* @param array $item
* @param array $column_name
*
* @return string
*/
	function column_active ( $item ) {
		return ($item['active']) ? '<span style="color: green;">&#11044;</span>' : '<span style="color: red;">&#11044;</span>' ;
	}

	function column_modify( $item ) {
		$active = $item['active'] == true ? 'Deactivate' : 'Activate';
		$tab = verify_data($_GET['tab'], 'tabs');
		$tid = verify_data($item['id'], 'int');
		$action = '
			<div style="float: right; padding-left: 15px;"><a href="?page=es-settings&tab='.$tab.'&id='.$tid.'&action=edit" class="button-primary">Edit template</a></div>
			<div style="float: right; padding-left: 10px;"><a href="?page=es-settings&tab='.$tab.'&id='.$tid.'&action=active" class="button-primary">'.$active.'</a></div>
		';
		return $action;
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

/**
*  Associative array of columns
*
* @return array
*/
	function get_columns() {
		$columns = [
			'title' => 'Template',
			'description' => 'Description',
			'active'	=> 'Active',
			'modify'	=> ''
		];

		return $columns;
	}

/**
* Handles data query and filter, sorting, and pagination.
*
*/

	public function prepare_items () {
		echo '<style type="text/css">';
		echo '.wp-list-table .column-title { width: 200px; }';
		echo '.wp-list-table .column-description { width: 65%; }';
		echo '.wp-list-table .column-active { width: 40px; text-align: center;}';
		echo '.wp-list-table .column-modify { width: 240px; }';
		echo '</style>';
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = self::get_templets();
	}
}

/**
 * Initialize TinyMCE instance
 *
 * @param bolean $show_post_tags
 */

function tinymce_init( $show_post_tags = false) { ?>
	<script>tinymce.init({
		selector: 'textarea',
		plugins : 'table autosave lists advlist hr code link image charmap preview imagetools quickbars fullpage nonbreaking',
		toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | forecolor backcolor | numlist bullist | tags",
		menu: {
			file: { title: 'File', items: 'newdocument restoredraft | preview ' },
			edit: { title: 'Edit', items: 'undo redo | cut copy paste | selectall' },
			view: { title: 'View', items: 'code | visualaid visualchars visualblocks | spellchecker | preview fullscreen' },
			insert: { title: 'Insert', items: 'image link media template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime' },
			format: { title: 'Format', items: 'formats fontformats fontsizes | forecolor backcolor | removeformat' },
			table: { title: 'Table', items: 'inserttable row column cell | tableprops deletetable' },
		},
		quickbars_selection_toolbar: 'cut copy paste | quicklink blockquote | removeformat',
		menubar: 'file edit insert view format table',
		code_dialog_width: 200,
		contextmenu: false,
		paste_data_images: true,
		extended_valid_elements: '*',
		branding: false,
		height: 500,
		setup: function (editor) {
			editor.ui.registry.addMenuButton('tags', {
				text: 'Insert shortcode',
				fetch: function (callback) {
					var items = [
						{
							type: 'nestedmenuitem',
							text: 'Subscribers',
							getSubmenuItems: function () {
								return [ {
									type: 'menuitem',
									text: 'Name',
									onAction: function () {	editor.insertContent('#subscribername#'); }
								},
								{
									type: 'menuitem',
									text: 'E-mail',
									onAction: function () {	editor.insertContent('#subscriberemail#'); }
								},
								{
									type: 'menuitem',
									text: 'Verify link',
									onAction: function () { editor.insertContent('#verifylink#'); }
								},
								{
									type: 'menuitem',
									text: 'Unsubscribe link',
									onAction: function () { editor.insertContent('#unsubscribelink#'); }
								} ];
							}
						},
					<?php if ($show_post_tags) { ?>
						{
							type: 'nestedmenuitem',
							text: 'Post loop',
							getSubmenuItems: function () { return [
								{
									type: 'menuitem',
									text: 'Post title',
									onAction: function () { editor.insertContent('#posttitle#'); }
								},
								{
									type: 'menuitem',
									text: 'Post excerpt',
									onAction: function () { editor.insertContent('#postexcerpt#'); }
								},
								{
									type: 'menuitem',
									text: 'Post content',
									onAction: function () { editor.insertContent('#postcontent#'); }
								},
								{
									type: 'menuitem',
									text: 'Posted date',
									onAction: function () { editor.insertContent('#postdate#'); }
								},
								{
									type: 'menuitem',
									text: 'Post link',
									onAction: function () { editor.insertContent('#postlink#'); }
								},
								{
									type: 'menuitem',
									text: 'Image link',
									onAction: function () { editor.insertContent('#postimage#'); }
								},
								{
									type: 'menuitem',
									text: 'Posted by',
									onAction: function () { editor.insertContent('#postedby#'); }
								},
								{
									type: 'menuitem',
									text: '# of new posts',
									onAction: function () { editor.insertContent('#newposts#'); }
								},
								{
									type: 'menuitem',
									text: 'Loop block start',
									onAction: function () { editor.insertContent('#loopstart#'); }
								},
								{
									type: 'menuitem',
									text: 'Loop block end',
									onAction: function () { editor.insertContent('#loopend#'); }
								} ];
							}
						},
					<?php } ?>
						{
						type: 'menuitem',
						text: 'Site name',
						onAction: function () {	editor.insertContent('#sitename#');	}
						},
						{
						type: 'menuitem',
						text: 'Site URL link',
						onAction: function () { editor.insertContent('#siteurl#'); }
						},
						{
						type: 'menuitem',
						text: 'Site description',
						onAction: function () { editor.insertContent('#sitedescription#'); }
						},
					];
					callback(items);
				}
			});
		}
	});</script>
<?php
}
?>
