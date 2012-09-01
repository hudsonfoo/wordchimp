jQuery(document).ready(function() {
	// Step 1
	jQuery(".template_select_box").click(function() {
		jQuery("#step1_form input[name=template_id]").val(jQuery(this).attr('template_id'));
		jQuery("#step1_form").submit();
	});
	
	// Step 2
	jQuery("#step2_submit").click(function() {
		jQuery("#step2_form").submit();
	});
	

	jQuery('#posts_listing ul li').click(function() {
		if (jQuery('#wordchimp_section_select').val() == '') {
			alert('Please select a template section and try again.');
		} else {
			var selectedPost = jQuery(this);
			jQuery.post(
				ajaxurl, 
				{ action : 'wordchimp_get_post', post_id : jQuery(selectedPost).attr('post_id') },
				function(post) {
					jQuery('textarea#html_' + jQuery('#wordchimp_section_select').val()).val(jQuery('textarea#html_' + jQuery('#wordchimp_section_select').val()).val() + post);
				}
			);
		}
	});
});