<?php require locate_template( 'templates/emails/partials/header.php' ) ?>
<style>
table.main {
    background: transparent !important;
}
.footer{
	top: -30px !important; 
	position: relative !important;
}
body {
	        background-color: #f6f6f600 !important;
}
.container{
		max-width: 725px !important;
	        padding: 10px  !important;
	        width: 725px !important;
		top: -35px !important; 
		position: relative; 
}
.content{
     max-width: 725px !important;
}
.mbtn{
	border: 1px solid <?php echo $brand ?> !important;
        	border-radius: 0px !important;
}

</style>
<div id="wrapper" dir="ltr" style="background-color: #ffffff00; margin: 0; padding: 70px 0; width: 100%; padding-top: 5px; padding-bottom: 29px; -webkit-text-size-adjust: none;">
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td align="center" valign="top">
<div id="template_header_image">
<p style="margin-top: 0;"><img style="border: none; display: inline-block; font-weight: bold; height: auto; outline: none; text-decoration: none; text-transform: capitalize; vertical-align: middle; font-size: 15px; line-height: 24px; margin-left: 0; margin-right: 0; max-width: 404px;" src="https://highsociety.gr/hsmajormrktplc/2020/02/HighResolutionTransparentFile_glow-e1582303918282.png" alt="HighSociety" /></p>

</div>
<table id="template_container" style="border-top-left-radius: 5px;border-top-right-radius: 5px;background-color: #ffffff; border: 1px solid #303030;  border-top-width: 5px; border-color: #303030; border-right: 0px solid #ffffff; border-bottom: 0px solid #ffffff; border-left: 0px solid #ffffff; box-shadow: 0 1px 12px 3px rgba(0,0,0,0.1);" border="0" width="600" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td align="center" valign="top"><!-- Body -->
<table id="template_body" style="max-width: 630px;" border="0" width="600" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td id="body_content" style="background-color: #ffffff; padding-top: 0px; padding-bottom: 20px;" valign="top"><!-- Content -->
<table border="0" width="100%" cellspacing="0" cellpadding="20">
<tbody>
<tr>
<td style="padding: 48px 48px 32px; padding-left: 20px; padding-right: 20px;" valign="top">
<div id="body_content_inner" style="color: #575f6d; text-align: left; font-size: 15px; line-height: 24px; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-weight: 400;">
<p style="margin: 0 0 16px;"><strong><?php echo __("A new report has been submitted for listing ","my-listing-child");?><?php echo $this->listing->get_name() ?> <?php echo __( "by user", 'my-listing-child');?> <?php echo $this->user->display_name ?><?php echo __(", with the following reason:", 'my-listing-child');?></strong></p>
<br>
<p><?php echo $this->reason ?></p><br>
<p></p>
<br><br><br>
<a class="mbtn mbtn1" style="border-radius=3px;" href="<?php echo c27()->get_edit_post_link($this->report->ID) ?>" target="_blank"><?php echo __("View Report","my-listing-child");?></a> 
<br>
<a class="mbtn" style="border-radius=3px;" href="<?php echo $this->listing->get_link() ?>" target="_blank"><?php echo __("Open Listing","my-listing-child");?></a> 
</div>
</td>
</tr>
</tbody>
</table>
<!-- End Content --></td>
</tr>
</tbody>
</table>
<!-- End Body --></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</div>
<?php require locate_template( 'templates/emails/partials/footer.php' ) ?>

