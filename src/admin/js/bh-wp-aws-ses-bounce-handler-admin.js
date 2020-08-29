(function( $ ) {
	'use strict';

	$(window).load(function() {

		// check GET page=bh-wp-aws-ses-bounce-handler

		var runSesBounceTestButton = document.getElementById( 'run-ses-bounce-test-button' );

		if(runSesBounceTestButton != null) {

			runSesBounceTestButton.addEventListener('click', function (event) {

				event.preventDefault();

				$('.bounce-test-running-spinner').css('display','inline');

				var data = $('#run-ses-bounce-test-form').serializeArray();

				$.post(ajaxurl, data, function (response) {

					// TODO: Handle 500 error, e.g. maintenance mode

					$('#run-ses-bounce-test-form #_wpnonce').val(response.newNonce);

					var noticeType = response.notice;
					var html = response.html;
					var bounceTestId = response.bounceTestId;

					var content = '<div class="notice notice-' + noticeType + '" id="' + bounceTestId + '">';

					content = content + html;

					content = content + '</div>';

					$('#run-ses-bounce-test-response').append(content);

					setTimeout(function () {

						fetchTestResults( bounceTestId );

					}, 5000);

				});
			});
		}

	});

	function fetchTestResults( bounceTestId ) {

		var nonce = $('#run-ses-bounce-test-form #_wpnonce').val();

		var action = 'fetch_test_results';

		var data = {
			'_wpnonce': nonce,
			'action': action,
			'bounce_test_id': bounceTestId
		};

		$.post(ajaxurl, data, function (data) {

			var testComplete = data.testComplete;

			// Test complete?
			if (testComplete) {

				$('.bounce-test-running-spinner').css('display', 'none');

				var testSuccess = data.testSuccess;

				$('#' + bounceTestId).removeClass('notice-info');

				if (testSuccess) {
					// Set color to green
					$('#' + bounceTestId).addClass('notice-success');
				} else {
					// set color to red.
					$('#' + bounceTestId).addClass('notice-error');
				}

				$('#' + bounceTestId).append(data.html);

				add_delete_data_button(bounceTestId);

			} else {
				setTimeout(function () {

					fetchTestResults(bounceTestId);

				}, 5000);
			}

		}).fail(handleFailure);

	}


	function add_delete_data_button( bounceTestId ) {

		// create button
		var newButton = document.createElement('button');

		newButton.className = 'button';
		newButton.textContent = 'Delete test data';

		// add handler
		newButton.onclick = function() {

			var data = {
				'action': 'delete_test_data',
				'bounce_test_id': bounceTestId,
				'_wpnonce': $('#run-ses-bounce-test-form #_wpnonce').val()
			};

			$.post(ajaxurl, data, function (data) {

				newButton.disabled = true;

			}).fail(handleFailure);
		};

		$('#run-ses-bounce-test-response').append( newButton );

	}

	function handleFailure(jqXHR, textStatus, errorThrown) {

		var html = '<div class="notice inline notice-error"><p>' + jqXHR.responseJSON.data.message + '</p></div>';

		$('#run-ses-bounce-test-response').append(html);

		$('.bounce-test-running-spinner').css('display', 'none');

	}


})( jQuery );
