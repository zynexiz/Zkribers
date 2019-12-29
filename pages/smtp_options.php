<?php
function smtp_options() { 
	global $phpmailer;
	$opt = get_option('es_options');
	
	if (!empty($_POST)) {
		if (isset($_POST['save'])) {
			$opt['name'] = $_POST['name'];
			$opt['email'] = $_POST['email'];
			$opt['hostname'] = $_POST['hostname'];
			$opt['authentication'] = (isset($_POST['authentication'])) ? true : false;
			switch ($_POST['protocol']) {
				case 25:
					$opt['protocol']='none'; break;
				case 465:
					$opt['protocol']='ssl'; break;
				case 587:
					$opt['protocol']='tls'; break;
			}
			$opt['port'] = $_POST['port'];
			$opt['login'] = $_POST['login'];
			$opt['pwd'] = base64_encode($_POST['pwd']);
			update_option( 'es_options', $opt);
		}
		if (isset($_POST['testmail'])) {
			$to = (get_userdata( get_current_user_id() ))->user_email;
			$subject = 'SMTP Test';
			$message = 'Hi<br><br>If you can see this e-mail, everything works.';
			$headers = array('Content-Type: text/html; charset=UTF-8');

			$result = wp_mail( $to, $subject, $message, $headers);
			if ( $result ) {
				$sendinfo = 'The test message was sent. Check your email inbox.';
			} else {
				$sendinfo = $phpmailer->ErrorInfo;
			}
		}
	} ?>
	
	<h2>SMTP Options</h2>
	<em>Enter your SMTP server details here. Most servers require authentication before relaying e-mails.<br>
	The test e-mail will be sent to the e-mail you entered in your users contact information.</em>
	<form method="post">
		<table class="form-table">
			<tr>
				<th scope="row"><b>From name</b></th>
				<td><input type="text" name="name" value="<?php echo $opt['name']?>" required autofocus><br>
				<em style="font-size:90%">Name of the sender, fx. your site name </em></td>
			</tr>
			<tr>
				<th scope="row"><b>From e-mail</b></th>
				<td><input type="email" name="email" value="<?php echo $opt['email']?>" required><br>
				<em style="font-size:90%">From e-mail address, fx. no-replay@host.org</em></td>
			</tr>
			<tr>
				<th scope="row" style="width: 150px"><b>Host name</b></th>
				<td><input type="text" name="hostname" value="<?php echo $opt['hostname']?>" required><br>
				<em style="font-size:90%">If your server it's self hosted, use 'localhost'</em></td>
			</tr>
			<tr>
				<th scope="row" style="width: 150px"><b>Encryption and port</b></th>
				<td>
				<select name="protocol" id="protocol" style="margin-top: 1px;margin-bottom: 4px;" onchange = "document.getElementById('port').value = this.value;">
					<option value = "25" <?php echo ($opt['protocol'] == "none") ? 'selected' : ''?>>None</option>
					<option value = "465" <?php echo ($opt['protocol'] == "ssl") ? 'selected' : ''?>>TLS/SSL</option>
					<option value = "587" <?php echo ($opt['protocol'] == "tls") ? 'selected' : ''?>>STARTLS</option>
				</select>
				<input id="port" type="text" name="port" value="<?php echo $opt['port']?>" required>
				<br>
				<em style="font-size:90%">SMTP encryption protocol and port (usually STARTLS on port 587)</em></td>
			</tr>
			<tr>
				<th scope="row"><b>Require authentication</b></th>
				<td><input type="checkbox" name="authentication" onclick="document.getElementById('auth').style.visibility = (this.checked) ? 'visible' : 'collapse';" <?php echo ($opt['authentication']) ? 'checked' : ''?>></td>
			</tr>
			<tbody id="auth" style="visibility:<?php echo ($opt['authentication']) ? 'visible' : 'collapse'?>;">
				<tr>
					<th scope="row"><b>Login e-mail</b></th>
					<td><input type="text" name="login" value="<?php echo $opt['login']?>"><br>
					<em style="font-size:90%">E-mail used for SMTP authentication</em></td>
				</tr>
				<tr>
					<th scope="row"><b>Password</b></th>
					<td><input type="password" name="pwd" value="<?php echo base64_decode($opt['pwd'])?>"><br>
					<em style="font-size:90%"><strong>Note!</strong> Password can be retrieved if your system is compromised</em></td>
				</tr>
			</tbody>
			<tr>
				<td style="height: 50px;">
					<p><input type="submit" name="save" class="button-primary" value="Save" />
					<input type="submit" name="testmail" class="button-primary" value="Send test mail" /></p>
				</td>
				<td>
					<em style="font-size:90%; color:red;"><?php if (isset($sendinfo)) {echo $sendinfo;}?></em>
				</td>
			</tr>
		</table>
	</form>
<?php }
?>