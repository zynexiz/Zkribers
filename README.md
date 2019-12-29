# E-mail Subscribers
E-mail subscribers is a simple addon for Wordpress to manage e-mail subscriptions and automatic e-mail notification for new posts and content.

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
・Add internationalization
・Add generic image if post has no featured image
<br>

Note that this addon is still in early beta. If you encounter a bug, please report it.

### Supported shortcodes:
・sitename<br>
  The name of your site defined in WordPress settings<br>
・siteurl<br>
  The URL to your site defined in WordPress settings<br>
・sitedescription<br>
  Site description defined in WordPress settings<br>
・subscribername<br>
  Name of the subscriber<br>
・subscriberemail<br>
  The e-mail to the subscriber<br>
・unsubscribelink<br>
  A link to the unsubscribe page to let the user unsubscribe from your list<br>
・verifylink<br>
  A link to the verification page to let the user verify the e-mail provided<br>
・newposts<br>
  The number of new posts since last successful send<br>
・postedby<br>
  The WordPress user that posted the content<br>
・postimage<br>
  Image link to post featured image (just the link, not enclosed by img tags)<br>
・postlink<br>
  Link to the post itself<br>
・postdate<br>
  Date the post was published<br>
・postcontent<br>
  The full post content<br>
・postexcerpt<br>
  A trimmed-down version of the full post<br>
・posttitle<br>
  Title of the post<br>
・loopstart / loopend<br>
  This is the content loop. If there is more than one post, this loop will go thru every new post and insert the content for each post between this shortcodes. Insde the loop you can define how to display the content itself. All post* shortcodes are only valid inside this loop. If no loop shortcodes are defined, only the last post will be inserted.<br>
