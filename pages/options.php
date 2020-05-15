<?php
function options() {
	$opt = get_option('zkribers_options');

	if (!empty($_POST)) {
		if (isset($_POST['save'])) {
			$opt['row_per_page'] = verify_data($_POST['rows'], 'int', false);
			$opt['post_type'] = explode(',',verify_data($_POST['inc_post_type'],'post_type'));
			update_option( 'zkribers_options', $opt);
		}
	}

	$post_types = array_merge(array('post' => 'post', 'page' => 'page'), get_post_types( array('public'   => true, '_builtin' => false) ));

?>
	<h2>Zkriber options</h2>
	<em>Gerenall options for Zkribers. Set the number of rows in subscriber table and select which post types you want to include when sending out e-mails.</em>
	<form method="post">
		<table class="form-table">
			<tr>
				<th scope="row"><b>Number of rows in tables</b></th>
				<td><input type="number" name="rows" value="<?php echo $opt['row_per_page']?>" required><br></td>
			</tr>
			<tr>
				<th scope="row" style="width: 150px"><b>Include post types</b></th>
				<td>
				<select name="inc_post_type[]" size="<?php echo max(sizeof($post_types),'6');?>" multiple required style="width: 180px; padding: 0px;">
					<?php foreach ( $post_types as $type ) {
					$selected = in_array($type, $opt['post_type']) ? 'selected' : '';
					echo '<option value="'.$type.'" '.$selected.'>'.$type.'</option>';
					} ?>
				</select></td>
			</tr>
			<tr>
				<td style="height: 50px;" colspan="2">
					<p><input type="submit" name="save" class="button-primary" value="Save" /></p>
				</td>
			</tr>
		</table>
	</form>
<?php }
?>
