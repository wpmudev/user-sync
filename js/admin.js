(function($) {
	var button = false;
	var spinner = false;

	$(document).on('ready', function(){
		$('#user-sync-sync-all').click(function(event){
			event.preventDefault();

			button = $(this);
			button.prop("disabled", true);

			spinner = $('#user-sync-spinner');

			sync_all(1, 1, $(this));
		});
		
	});

	function sync_all(page, site, button) {
		args = {
			page: page,
			site: site,
			action: 'user_sync_sync_all'
		};

		if(spinner) {
			spinner.css('visibility', 'visible');
		}

		$.post(ajaxurl, args, function(response) {
			if(response.success) {
				var finished = 0;

				if(response.data.sites_end) {
					finished = 1;
				}
				else if(response.data.users_end) {
					site = site + 1;
				}
				else {
					page = page + 1;
				}

				if(!finished) {
					sync_all(page, site, button);
				}
				else {
					window.location.href = response.data.redirect_url;
				}
			}
			else {
				if(button) {
					button.prop("disabled", false);
				}
				if(spinner) {
					spinner.css('visibility', 'hidden');
				}
			}
		});
	}
})(jQuery);