/**
 * jQuery History Plugin (balupton edition) - Simple History Handler/Remote for Hash, State, Bookmarking, and Forward Back Buttons
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton/projects/jquery_history/
 *
 * This file is part of jQuery History Plugin (balupton edition).
 * 
 * jQuery History Plugin (balupton edition) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * jQuery History Plugin (balupton edition) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with jQuery History Plugin (balupton edition).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @name jqsmarty: jquery.history.js
 * @package jQuery History Plugin (balupton edition)
 * @version 1.0.1-final
 * @date July 11, 2009
 * @category jquery plugin
 * @author Benjamin "balupton" Lupton {@link http://www.balupton.com}
 * @copyright (c) 2008-2009 Benjamin Arthur Lupton {@link http://www.balupton.com}
 * @license GNU Affero General Public License - {@link http://www.gnu.org/licenses/agpl.html}
 * @example Visit {@link http://jquery.com/plugins/project/jquery_history_bal} for more information.
 * 
 * 
 * I would like to take this space to thank the following projects, blogs, articles and people:
 * - jQuery {@link http://jquery.com/}
 * - jQuery UI History - Klaus Hartl {@link http://www.stilbuero.de/jquery/ui_history/}
 * - Really Simple History - Brian Dillard and Brad Neuberg {@link http://code.google.com/p/reallysimplehistory/}
 * - jQuery History Plugin - Taku Sano (Mikage Sawatari) {@link http://www.mikage.to/jquery/jquery_history.html}
 * - jQuery History Remote Plugin - Klaus Hartl {@link http://stilbuero.de/jquery/history/}
 * - Content With Style: Fixing the back button and enabling bookmarking for ajax apps - Mike Stenhouse {@link http://www.contentwithstyle.co.uk/Articles/38/fixing-the-back-button-and-enabling-bookmarking-for-ajax-apps}
 * - Bookmarks and Back Buttons {@link http://ajax.howtosetup.info/options-and-efficiencies/bookmarks-and-back-buttons/}
 * - Ajax: How to handle bookmarks and back buttons - Brad Neuberg {@link http://dev.aol.com/ajax-handling-bookmarks-and-back-button}
 *
 **
 ***
 * CHANGELOG
 **
 * v1.0.1-final, July 11, 2009
 * - Restructured a little bit
 * - Documented
 * - Cleaned go/request
 *
 * v1.0.0-final, June 19, 2009
 * - Been stable for over a year now, pushing live.
 * 
 * v0.1.0-dev, July 24, 2008
 * - Initial Release
 * 
 */

// Start of our jQuery Plugin
(function($)
{	// Create our Plugin function, with $ as the argument (we pass the jQuery object over later)
	// More info: http://docs.jquery.com/Plugins/Authoring#Custom_Alias
	
	// Debug
	
	if (typeof console === 'undefined') {
		console = typeof window.console !== 'undefined' ? window.console : {};
	}
	console.log			= console.log 			|| function(){};
	console.debug		= console.debug 		|| console.log;
	console.warn		= console.warn			|| console.log;
	console.error		= console.error			|| function(){var args = [];for (var i = 0; i < arguments.length; i++) { args.push(arguments[i]); } alert(args.join("\n")); };
	console.trace		= console.trace			|| console.log;
	console.group		= console.group			|| console.log;
	console.groupEnd	= console.groupEnd		|| console.log;
	console.profile		= console.profile		|| console.log;
	console.profileEnd	= console.profileEnd	|| console.log;
	
	
	// Declare our class
	$.History = {
		// Our Plugin definition
		
		// -----------------
		// Options
		
		options: {
			debug: false
		},
		
		// -----------------
		// Variables
		
		state:		'',
		$window:	null,
		$iframe:	null,
		handlers:	{
			generic:	[],
			specific:	{}
		},
		
		// --------------------------------------------------
		// Functions
		
		/**
		 * Format a hash into a proper state
		 * @param {String} hash
		 */
		format: function ( hash ) {
			// Format the hash
			hash = hash.replace(/^.+?#/g,'').replace(/^#?\/?|\/?$/g, '');
			// Return the hash
			return hash;
		},
		
		/**
		 * Get the current state of the application
		 */
        getState: function ( ) {
			var History = $.History;
			// Get the current state
			return History.state;
        },
		/**
		 * Set the current state of the application
		 * @param {String} hash
		 */
		setState: function ( state ) {
			var History = $.History;
			// Format the state
			state = History.format(state)
			// Apply the state
			History.state = state;
			// Return the state
			return History.state;
		},
		
		/**
		 * Get the current hash of the browser
		 */
		getHash: function ( ) {
			var History = $.History;
			// Get hash
			if (parent && !$.browser.msie) {
				var hash = parent.window.location.hash;
			} else {
				var hash = window.location.hash || location.hash;
			}
			// Format the hash
			hash = History.format(hash);
			// Return the hash
			return hash;
		},
		/**
		 * Set the current hash of the browser
		 * @param {String} hash
		 */
		setHash: function ( hash ) {
			var History = $.History;
			// Prepare hash
			hash = $.History.format(hash);
			hash = hash.replace(/^\/?|\/?(\?)|\/?$/g, '/$1');
			
			// Write hash
			if ( typeof window.location.hash !== 'undefined' ) {
				//window.location.hash = hash;
			} else {
				location.hash = hash;
			}
			
			// Update IE<8 History
			if ( $.browser.msie && parseInt($.browser.version, 10) < 8 )
			{	// We are IE<8
				$.History.$iframe.contentWindow.document.open();
				$.History.$iframe.contentWindow.document.close();
				//$.History.$iframe.contentWindow.document.location.hash = $.History.getState();						
			}
			
		},
		
		/**
		 * Go to the specific state - does not force a history entry like setHash
		 * @param {String} state
		 */
		go: function ( state ) {
			var History = $.History;
			
			// Format the state
			state = History.format(state);
			
			// Get the current hash
			var hash = History.getHash();
			
			// Are they different?
			if ( hash !== state ) {
				// Yes, create a history entry
				History.setHash(state);
				// Wait for hashchange
			} else {
				// No change, but update state and fire
				History.setState(state);
				History.trigger();
			}
			
			// Done
			return true;
		},
		
		/**
		 * Fired when the hash is changed, either automaticly or manually
		 * @param {Event} e
		 */
		hashchange: function ( e ) {

			var History = $.History;
			
			// Debug
			if ( History.options.debug ) {
				console.debug('History.hashchange', this, arguments);
			}
			
			// Get Hash
			var hash = History.getHash();
			var state = History.getState();
			
			// Prevent IE 8 from fireing this twice
			if ( (!History.$iframe && state === hash) || (History.$iframe && History.hash === History.$iframe.contentWindow.document.location.hash) ) {
				// For some reason this works
				return false;
			}
			
			// Check
			if ( state === hash ) {
				// Nothing to do
				return false;
			}
			
			// Update the state with the new hash
			History.setState(hash);
			
			// Fire the handler
			History.trigger();
			
			// All done
			return true;
		},
		
		/**
		 * Bind a handler to a hash
		 * @param {Object} state
		 * @param {Object} handler
		 */
		bind: function ( state, handler ) {
			var History = $.History;
			// 
			if ( handler ) {
				// We have a state specific handler
				// Prepare
				if ( typeof History.handlers.specific[state] === 'undefined' )
				{	// Make it an array
					History.handlers.specific[state] = [];
				}
				// Push new handler
				History.handlers.specific[state].push(handler);
			}
			else {
				// We have a generic handler
				handler = state;
				History.handlers.generic.push(handler);
			}
			
			// Done
			return true;
		},
		
		/**
		 * Trigger a handler for a state
		 * @param {String} state
		 */
		trigger: function ( state ) {
			var History = $.History;
			
			// Prepare
			if ( typeof state === 'undefined' ) {
				// Use current
				state = History.getState();
			}
			var i, n, handler, list;
			
			// Fire specific
			if ( typeof History.handlers.specific[state] !== 'undefined' ) {
				// We have specific handlers
				list = History.handlers.specific[state];
				for ( i = 0, n = list.length; i < n; ++i ) {
					// Fire the specific handler
					handler = list[i];
					handler(state);
				}
			}
			
			// Fire generics
			list = History.handlers.generic;
			for ( i = 0, n = list.length; i < n; ++i ) {
				// Fire the specific handler
				handler = list[i];
				handler(state);
			}
			
			// Done
			return true;
		},
		
		// --------------------------------------------------
		// Constructors
		
		/**
		 * Construct our application
		 */
		construct: function ( ) {
			var History = $.History;
			
			// Modify the document
			$(document).ready(function() {
				// Prepare the document
				History.domReady();
			});
			
			// Done
			return true;
		},
		
		/**
		 * Configure our application
		 * @param {Object} options
		 */
		configure: function ( options ) {
			var History = $.History;
			
			// Set options
			History.options = $.extend(History.options, options);
			
			// Done
			return true;
		},
		
		domReadied: false,
		domReady: function ( ) {
			var History = $.History;
			
			// Runonce
			if ( History.domRedied ) {
				return;
			}
			History.domRedied = true;
			
			// Define window
			History.$window = $(window);
			
			// Apply the hashchange function
			History.$window.bind('hashchange', this.hashchange);
			
			// Force hashchange support for all browsers
			setTimeout(History.hashchangeLoader, 200);
			
			// All done
			return true;
		},
		
		/**
		 * Enable hashchange for all browsers
		 */
		hashchangeLoader: function () {
			var History = $.History;
			
			// More is needed for non IE8 browsers
			if ( !($.browser.msie && parseInt($.browser.version) >= 8) ) {	
				// We are not IE8
			
				// State our checker function, it is used to constantly check the location to detect a change
				var checker;
				
				// Handle depending on the browser
				if ( $.browser.msie ) {
					// We are still IE
				
					// Append and $iframe to the document, as $iframes are required for back and forward
					// Create a hidden $iframe for hash change tracking
					History.$iframe = $('<iframe id="jquery-history-iframe" style="display: none;"></$iframe>').prependTo(document.body)[0];
					
					// Create initial history entry
					History.$iframe.contentWindow.document.open();
					History.$iframe.contentWindow.document.close();
					
					// Check for initial state
					var hash = History.getHash();
					if ( hash ) {
						// Apply it to the iframe
						History.$iframe.contentWindow.document.location.hash = hash;
					}
					
					// Define the checker function (for bookmarks)
					checker = function ( ) {
						var iframeHash = History.format(History.$iframe.contentWindow.document.location.hash);
						if ( History.getState() !== iframeHash ) {
							// Back Button Change
							History.setHash(History.$iframe.contentWindow.document.location.hash);
						}
						var hash = History.getHash();
						if ( History.getState() !== hash ) {
							// The has has changed
							History.go(hash);
						}
					};
				}
				else {
					// We are not IE
				
					// Define the checker function (for bookmarks, back, forward)
					checker = function ( ) {
						var hash = History.getHash();
						if ( History.getState() !== hash ) {
							// The has has changed
							History.go(hash);
						}
					};
				}
				
				// Apply the checker function
				if ( !($.browser.msie && parseInt($.browser.version) < 8) ) {
					setInterval(checker, 200);
				} else {
					setInterval(checker, 1500);
				}
			}
			else {
				// We are IE8
				var hash = History.getHash();
				if (hash) {
					History.$window.trigger('hashchange');
				}
			}
			
			// Done
			return true;
		}
	
	}; // We have finished extending/defining our Plugin

	// --------------------------------------------------
	// Finish up
	
	// Instantiate
	$.History.construct();

// Finished definition

})(jQuery); // We are done with our plugin, so lets call it with jQuery as the argument
