# E-mail Subscribers
E-mail subscribers is a simple addon for Wordpress to manage e-mail subscriptions and automatic e-mail notification for new posts and content. <strong>Note!</strong> This plugin does not support third party e-mail services like Mailchimp, you need to have access to a SMTP server.

### Features include:
・Usage of SMTP server for sending out mail<br>
・Manage and edit subscribers<br>
・Support for HTML templates with TinyMCE<br>
・Shortcodes for dynamic content<br>
・Enable/disable templates functions<br>
・Two-step verification (if enabled)<br>

### ToDo:
・Audit code<br>
・Add changing of intervalls between sending out mail<br>
・External cron jobb script<br>
・Better security for storing authentication password in database<br>
・Add better looking verify/unsubscribe pages<br>
・Add internationalization<br>
・Add generic image if post has no featured image
<br>

Note that this addon is still in early beta. If you encounter a bug, please report it.

### Supported shortcodes:
<table>
  <tr>
    <td>sitename</td>
    <td>The name of your site defined in WordPress settings</td>
  </tr>
  <tr>
      <td>siteurl</td>
      <td>The URL to your site defined in WordPress settings</td>
  </tr>
  <tr>
      <td>sitedescription</td>
      <td>Site description defined in WordPress settings</td>
  </tr>
  <tr>
      <td>subscribername</td>
      <td>Name of the subscriber</td>
  </tr>
  <tr>
      <td>subscriberemail</td>
      <td>The e-mail to the subscriber</td>
  </tr>
  <tr>
      <td>unsubscribelink</td>
      <td>A link to the unsubscribe page to let the user unsubscribe from your list</td>
  </tr>
  <tr>
      <td>verifylink</td>
      <td>A link to the verification page to let the user verify the e-mail provided</td>
  </tr>
	<tr>
      <td>newposts</td>
      <td>The number of new posts since last successful send</td>
  </tr>
	<tr>
      <td>postedby</td>
      <td>The WordPress user that posted the content</td>
  </tr>
	<tr>
      <td>postimage</td>
      <td>Image link to post featured image (just the link, not enclosed by img tags)</td>
  </tr>
	<tr>
      <td>postlink</td>
      <td>Link to the post itself</td>
  </tr>
	<tr>
		<td>postdate</td>
		<td>Date the post was published</td>
	</tr>
	<tr>
		<td>postcontent</td>
		<td>The full post content</td>
	</tr>
	<tr>
    <td>postexcerpt</td>
    <td>A trimmed-down version of the full post</td>
  </tr>
	<tr>
    <td>posttitle</td>
    <td>Title of the post</td>
  </tr>
	<tr>
    <td>loopstart / loopend</td>
    <td>This is the content loop. If there is more than one post, this loop will go thru every new post and insert the content for each post between this shortcodes. Insde the loop you can define how to display the content itself. All post* shortcodes are only valid inside this loop. If no loop shortcodes are defined, only the last post will be inserted.</td>
  </tr>
</table>
