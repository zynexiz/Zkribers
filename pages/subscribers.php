<?php
function subscribers() {
	$form = isset($_GET['action']) ? $_GET['action'] : NULL;
	
	if ($form == 'adduser' || $form == 'edit') {
		$uid = $form == 'edit' ? $_GET['id'] : NULL;
		if (isset($_POST['save'])) {
			save_post( $uid );
			if (!isset($_POST['verified'])) { es_sendmail( array(array('name' => $_POST['name'], 'email' => $_POST['email'])), 'VT'); }
			if (isset($_POST['send_welcome'])) { es_sendmail( array(array('name' => $_POST['name'], 'email' => $_POST['email'])), 'WT'); }
			redirect( '&tab=subscribers&paged='.$_GET['paged'] );
		} else {
			post_form( $uid );
			return;
		}
	}

	echo '<h2>Subscribers</h2><div style="float:right;">
	<span style="color: green;">&#11044;&nbsp</span><em>Verified</em>&nbsp&nbsp&nbsp
	<span style="color: yellow;">&#11044;&nbsp</span><em>Waiting for verification</em>&nbsp&nbsp&nbsp
	<span style="color: red;">&#11044;&nbsp</span><em>Disabled</em></div>
	<form method="post">'; 
	$table_obj = new Subscribers_Table();
	$table_obj->prepare_items();
	$table_obj->display();
	echo '<p style="clear: both;"><a href="?page=es-settings&action=adduser&paged=' . $table_obj->current_page(). '" class="button-primary">Add user</a></p></form>';
}

/**
 * Layout for adding new subscribers to the database
 *
 * @param int $userID (for editing current user)
 */
function post_form ( $userID = NULL) {
	if ( $userID ) {
		global $wpdb;
		$user = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}es_subscribers WHERE id = {$userID}", 'ARRAY_A' );
	} else {
		$user = array('name' => '','email' => '', 'verified' => 0);
	} ?>
	<h2>Add new subscriber</h2>
	<em>Add a new subscriber to the database. If 'verified' is checked, the user won't get a confirmation that the e-mail has been added.<br>
	If you have 'verification template' activated, and don't check verified, a verification mail will be sent to the user.<br><br></em>

	<form method="post">
		<table  class="form-table">
			<tr>
				<th><b>Name</b></th>
				<td><input type="text" name="name" value="<?php echo $user['name'] ?>" required autofocus></td>
			</tr>
			<tr>
				<th><b>E-mail</b></th>
				<td><input type="email" name="email" value="<?php echo $user['email'] ?>" required></td>
			</tr>
			<tr>
				<th><b>Set as verified</b></th>
				<td><input type="checkbox" name="verified" <?php echo ($user['verified'] == "2") ? 'checked' : '' ?> onclick="document.getElementById('welcome_msg').style.visibility = (this.checked) ? 'visible' : 'collapse';"></td>
			</tr>
			<tbody id="welcome_msg" style="visibility:<?php echo ($user['verified']) ? 'visible' : 'collapse'?>;">
				<tr>
					<th><b>Send welcome message</b></th>
					<td><input type="checkbox" name="send_welcome"></td>
				</tr>
			</tbody>
			<tr colspan="2">
				<td style="height: 50px;">
					<p><input type="submit" name="save" class="button-primary" value="Save" />
					<a href="?page=es-settings&tab=subscribers" class="button-primary">Cancel</a></p>
				</td>
			</tr>
		</table>
	</form> <?php
}

/**
 * Save or update subscriber information to the database
 *
 * @param int $userID
 */
function save_post( $userID = NULL ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'es_subscribers';

	if (isset($userID)) {
		$wpdb->update( $table_name,
			array( 
				'name' => $_POST['name'],
				'email' => $_POST['email'], 
				'verified' => (isset($_POST['verified']) ? 2 : 1),
				'purge_date' => (isset($_POST['verified']) ? NULL : date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp'))))),
			array( 'id' => $userID ),
			array( '%s', '%s', '%d') );
	} else {	
		$wpdb->insert( $table_name,
			array( 
				'name' => $_POST['name'],
				'email' => $_POST['email'], 
				'time' => current_time( 'mysql' ),
				'verified' => (isset($_POST['verified']) ? 2 : 1),
				'purge_date' => (isset($_POST['verified']) ? NULL : date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp'))))),
			array( '%s', '%s', '%s', '%d') );
	}
}

/**
 * Create the tables to show the subscribers
 *
 * Extend WP_List_Table class
 */
class Subscribers_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular'  => 'E-mail', 		//singular name of the listed records
			'plural'    => 'E-mails',   	//plural name of the listed records
			'ajax'      => false			//does this table support ajax?

		]);
	}
	
/**
* Retrieve subscribers data from the database
*
* @param int $per_page
* @param int $page_number
*
* @return mixed
*/
	static function get_subscribers( $per_page = 12, $page_number = 1 ) { 
		global $wpdb;

		$orderby = empty($_REQUEST['orderby']) ? 'name' : $_REQUEST['orderby'];
		$order = empty($_REQUEST['order']) ? 'asc' : $_REQUEST['order'];

		$sql = "SELECT * FROM {$wpdb->prefix}es_subscribers";			
		$sql .= ' ORDER BY ' . esc_sql( $orderby ) . ' ' . esc_sql( $order );
		$sql .= ' LIMIT ' . $per_page;
		$sql .= ' OFFSET ' . ($page_number -1) * $per_page;
		
		return $wpdb->get_results($sql, 'ARRAY_A');
	}

/**
* Delete a subscriber record.
*
* @param int $id subscriber ID
*/
	static function delete_subscriber ( $id ) {
		global $wpdb;
		
		$wpdb->delete(
			"{$wpdb->prefix}es_subscribers",
			['id' => $id],
			['%d']
		);
	}

/**
* Set subscriber as verified or unverified
*
* @param int $id - subscriber ID
* @param int $v - 0 = unverified, 1 = waiting for e-mail verify, 2 = verified
*/

	static function verify_subscriber ( $id , $v) {
		global $wpdb;

		$sql = "UPDATE {$wpdb->prefix}es_subscribers SET verified = $v, purge_date = NULL WHERE id=$id";
		$query = $wpdb->get_results($sql, 'ARRAY_A');
	}

/**
* Resend verification email to users
*
* @param int $id - subscriber ID
* @param int $v - 0 = unverified, 1 = waiting for e-mail verify, 2 = verified
*/

	static function resendverify ( $id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}es_subscribers WHERE id IN ($id)";
		$query = $wpdb->get_results($sql, 'ARRAY_A');
		
		foreach ($query as $subscriber) {
			$to[] = array('name' => $subscriber['name'], 'email' => $subscriber['email']);
		}
		
		$sql = "UPDATE {$wpdb->prefix}es_subscribers SET verified = 1, purge_date = '".date('Y-m-d H:i:s',strtotime("+1 week", current_time('timestamp')))."' WHERE id IN ($id)";
		$query = $wpdb->get_results($sql, 'ARRAY_A');
		es_sendmail($to , 'VT');
	}
	
/**
* Returns the count of records in the database.
*
* @return null|string
*/
	
	static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}es_subscribers";
		
		return $wpdb->get_var( $sql );
	}

	function no_items() {
		_e( 'You have no subscribers yet.', 'sp' );
	}
	
/**
* Function methods for name, verified and default columns output)
*
* @param array $item
* @param array $column_name
*
* @return string
*/
	function column_name( $item ) {
		if ($item['verified'] <= 1) {
			$v_text = 'Verify';
			$v_acction = 'verify';
		} else {
			$v_text = 'Disable';
			$v_acction = 'unverify';
		}
		$actions = [
			'edit' => sprintf( '<a href="?page=%s&action=%s&id=%s&paged=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ),$this->get_pagenum()),
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&paged=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ),$this->get_pagenum()),
			'verify' => sprintf( '<a href="?page=%s&action=%s&id=%s&paged=%s">' . $v_text . '</a>', esc_attr( $_REQUEST['page'] ), $v_acction, absint( $item['id'] ),$this->get_pagenum())
		];
		return $item['name'] . $this->row_actions( $actions );
	}
	
	function column_verified ( $item ) {
		$verified = $item['verified'];
		switch ($verified) {
			case 0:
				$verified = '<span style="color: red;">&#11044;</span>';
				break;
			case 1:
				$verified = '<span title="Expire at '.$item['purge_date'].'" style="color: yellow; cursor: help;">&#11044;</span>';
				break;
			case 2:
				$verified = '<span style="color: green;">&#11044;</span>';
				break;
		}
		return $verified;
	}
	
	function column_default( $item, $column_name ) {
		switch ($column_name) {
			case 'email':
			case 'time':
			case 'verified':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}
	
/**
* Render the bulk edit checkbox
*
* @param array $item
*
* @return string
*/
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['id']
		);
	}
	
/**
*  Associative array of columns
*
* @return array
*/
	function get_columns() {
		$columns = [
			'cb'		=> '<input type="checkbox" />',
			'name'		=> 'Name',
			'email'		=> 'E-mail',
			'verified'	=> 'Verified',
			'time'		=> 'Registered'			
		];

		return $columns;
	}
	
/**
* Columns to make sortable.
*
* @return array
*/
	function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ),
			'email' => array( 'email', false ),
			'verified' => array( 'verified', false ),
			'time' => array( 'time', false )
		);

	return $sortable_columns;
	}
	
/**
* Returns an associative array containing the bulk action
*
* @return array
*/
	function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete',
			'bulk-verify' => 'Verify',
			'bulk-unverify' => 'Unverify',
			'bulk-resendverify' => 'Send verify mail'
		];

		return $actions;
	}

/**
* Process the actions if triggered
*
* @param string $action
* @param array $id
*/
	function process_bulk_action( $action, $id ) {
		switch ($action) {
			case 'delete':
				self::delete_subscriber( $id[0] ) ;
				break;
			case 'verify':
				self::verify_subscriber( $id[0] , 2) ;
				break;
			case 'unverify':
				self::verify_subscriber( $id[0] , 0) ;
				break;
			case 'bulk-delete':
				foreach ( $id as $uid ) {
					self::delete_subscriber( $uid );
				}
				break;
			case 'bulk-verify':
				foreach ( $id as $uid ) {
					self::verify_subscriber( $uid , 2);
				}
				break;
			case 'bulk-unverify':
				foreach ( $id as $uid ) {
					self::verify_subscriber( $uid , 0);
				}
				break;
			case 'bulk-resendverify':
				$ids = implode(",", $id);
				self::resendverify( $ids );
				break;
			default:
				break;
		}
	}

/**
* Returns the current page showing
*
* @return int
*/
	public function current_page() { 
		return $this->get_pagenum();
	}
	
/**
* Handles data query and filter, sorting, and pagination.
*
*/

	public function prepare_items () {
		echo '<style type="text/css">';
		echo '.wp-list-table .column-name { width: 40%; }';
		echo '.wp-list-table .column-email { width: 60%; }';
		echo '.wp-list-table .column-verified { width: 110px;}';
		echo '.wp-list-table .column-time { width: 160px; }';
		echo '</style>';

		$opt = get_option('es_options');
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		if ($this->current_action()) {
			$uid = isset($_POST['bulk-action']) ? $_POST['bulk-action'] : array(absint($_GET['id']));
			$this->process_bulk_action($this->current_action(), $uid);
		}
		
		$per_page		= $opt['row_per_page'];
		$current_page	= $this->get_pagenum();
		$total_items	= self::record_count();
		
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page' => $per_page
		] );
		
		$this->items = self::get_subscribers( $per_page, $current_page );
	}
}
?>