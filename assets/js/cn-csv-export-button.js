;jQuery(document).ready( function($) {

	var CN_CSV_Export = {

		init: function() {

			var self = this;

			$( document.body ).on( 'click', '.cn-csv-export-button > a', function(e) {
				e.preventDefault();

				var link = $( this );
				var div  = link.parent();

				if ( ! link.hasClass( 'cn-csv-export-in-progress' ) ) {

					link.addClass( 'cn-csv-export-in-progress' );

					var action = div.data( 'action' );
					var nonce  = div.data( 'nonce' );

					link.text( div.data( 'wait' ) );

					self.submit( action, 1, nonce, div, link, self );
				}
			});

		},

		submit: function( action, step, nonce, div, link, self ) {

			var wp = window.wp;

			wp.ajax.send(
				action,
				{
					success: function( response ) {

						console.log( response );

						if ( 'completed' == response.step ) {

							link.removeClass( 'cn-csv-export-in-progress' );
							link.text( div.data( 'done' ) );

							$.fileDownload( response.url, {
								successCallback: function( url ) {

									link.text( div.data( 'text' ) );
									// window.location = response.url;
								},
								failCallback:    function( html, url ) {

									alert( 'Your file download just failed for this URL:' + url + '\r\n' +
										   'Here was the resulting error HTML: \r\n' + html
									);
								}
							});

						} else {

							link.text( div.data( 'wait' ) + ' ' + response.percentage + '%' );

							self.step( action, response.step, response.nonce, div, link, self );
						}

					},
					error:   function( response ) {

						console.log( response );

						link.removeClass( 'cn-csv-export-in-progress' );
						div.html('<div class="update error"><p>' + response.message + '</p></div>');
					},
					data:    {
						_ajax_nonce: nonce,
						step:        step
					}
				}
			);

		},

		step: function( action, step, nonce, div, link, self ) {

			self.submit( action, step, nonce, div, link, self )
		}
	};

	CN_CSV_Export.init();
});
