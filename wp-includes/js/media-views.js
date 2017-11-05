(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/**
 * wp.media.controller.CollectionAdd
 *
 * A state for adding attachments to a collection (e.g. video playlist).
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                         The attributes hash passed to the state.
 * @param {string}                     [attributes.id=library]      Unique identifier.
 * @param {string}                     attributes.title                    Title for the state. Displays in the frame's title region.
 * @param {boolean}                    [attributes.multiple=add]            Whether multi-select is enabled. @todo 'add' doesn't seem do anything special, and gets used as a boolean.
 * @param {wp.media.model.Attachments} [attributes.library]                 The attachments collection to browse.
 *                                                                          If one is not supplied, a collection of attachments of the specified type will be created.
 * @param {boolean|string}             [attributes.filterable=uploaded]     Whether the library is filterable, and if so what filters should be shown.
 *                                                                          Accepts 'all', 'uploaded', or 'unattached'.
 * @param {string}                     [attributes.menu=gallery]            Initial mode for the menu region.
 * @param {string}                     [attributes.content=upload]          Initial mode for the content region.
 *                                                                          Overridden by persistent user setting if 'contentUserSetting' is true.
 * @param {string}                     [attributes.router=browse]           Initial mode for the router region.
 * @param {string}                     [attributes.toolbar=gallery-add]     Initial mode for the toolbar region.
 * @param {boolean}                    [attributes.searchable=true]         Whether the library is searchable.
 * @param {boolean}                    [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
 * @param {boolean}                    [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
 * @param {int}                        [attributes.priority=100]            The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.syncSelection=false]     Whether the Attachments selection should be persisted from the last state.
 *                                                                          Defaults to false because for this state, because the library of the Edit Gallery state is the selection.
 * @param {string}                     attributes.type                   The collection's media type. (e.g. 'video').
 * @param {string}                     attributes.collectionType         The collection type. (e.g. 'playlist').
 */
var Selection = wp.media.model.Selection,
	Library = wp.media.controller.Library,
	CollectionAdd;

CollectionAdd = Library.extend({
	defaults: _.defaults( {
		// Selection defaults. @see media.model.Selection
		multiple:      'add',
		// Attachments browser defaults. @see media.view.AttachmentsBrowser
		filterable:    'uploaded',

		priority:      100,
		syncSelection: false
	}, Library.prototype.defaults ),

	/**
	 * @since 3.9.0
	 */
	initialize: function() {
		var collectionType = this.get('collectionType');

		if ( 'video' === this.get( 'type' ) ) {
			collectionType = 'video-' + collectionType;
		}

		this.set( 'id', collectionType + '-library' );
		this.set( 'toolbar', collectionType + '-add' );
		this.set( 'menu', collectionType );

		// If we haven't been provided a `library`, create a `Selection`.
		if ( ! this.get('library') ) {
			this.set( 'library', wp.media.query({ type: this.get('type') }) );
		}
		Library.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		var library = this.get('library'),
			editLibrary = this.get('editLibrary'),
			edit = this.frame.state( this.get('collectionType') + '-edit' ).get('library');

		if ( editLibrary && editLibrary !== edit ) {
			library.unobserve( editLibrary );
		}

		// Accepts attachments that exist in the original library and
		// that do not exist in gallery's library.
		library.validator = function( attachment ) {
			return !! this.mirroring.get( attachment.cid ) && ! edit.get( attachment.cid ) && Selection.prototype.validator.apply( this, arguments );
		};

		// Reset the library to ensure that all attachments are re-added
		// to the collection. Do so silently, as calling `observe` will
		// trigger the `reset` event.
		library.reset( library.mirroring.models, { silent: true });
		library.observe( edit );
		this.set('editLibrary', edit);

		Library.prototype.activate.apply( this, arguments );
	}
});

module.exports = CollectionAdd;

},{}],2:[function(require,module,exports){
/**
 * wp.media.controller.CollectionEdit
 *
 * A state for editing a collection, which is used by audio and video playlists,
 * and can be used for other collections.
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                      The attributes hash passed to the state.
 * @param {string}                     attributes.title                  Title for the state. Displays in the media menu and the frame's title region.
 * @param {wp.media.model.Attachments} [attributes.library]              The attachments collection to edit.
 *                                                                       If one is not supplied, an empty media.model.Selection collection is created.
 * @param {boolean}                    [attributes.multiple=false]       Whether multi-select is enabled.
 * @param {string}                     [attributes.content=browse]       Initial mode for the content region.
 * @param {string}                     attributes.menu                   Initial mode for the menu region. @todo this needs a better explanation.
 * @param {boolean}                    [attributes.searchable=false]     Whether the library is searchable.
 * @param {boolean}                    [attributes.sortable=true]        Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.date=true]            Whether to show the date filter in the browser's toolbar.
 * @param {boolean}                    [attributes.describe=true]        Whether to offer UI to describe the attachments - e.g. captioning images in a gallery.
 * @param {boolean}                    [attributes.dragInfo=true]        Whether to show instructional text about the attachments being sortable.
 * @param {boolean}                    [attributes.dragInfoText]         Instructional text about the attachments being sortable.
 * @param {int}                        [attributes.idealColumnWidth=170] The ideal column width in pixels for attachments.
 * @param {boolean}                    [attributes.editing=false]        Whether the gallery is being created, or editing an existing instance.
 * @param {int}                        [attributes.priority=60]          The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.syncSelection=false]  Whether the Attachments selection should be persisted from the last state.
 *                                                                       Defaults to false for this state, because the library passed in  *is* the selection.
 * @param {view}                       [attributes.SettingsView]         The view to edit the collection instance settings (e.g. Playlist settings with "Show tracklist" checkbox).
 * @param {view}                       [attributes.AttachmentView]       The single `Attachment` view to be used in the `Attachments`.
 *                                                                       If none supplied, defaults to wp.media.view.Attachment.EditLibrary.
 * @param {string}                     attributes.type                   The collection's media type. (e.g. 'video').
 * @param {string}                     attributes.collectionType         The collection type. (e.g. 'playlist').
 */
var Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	$ = jQuery,
	CollectionEdit;

CollectionEdit = Library.extend({
	defaults: {
		multiple:         false,
		sortable:         true,
		date:             false,
		searchable:       false,
		content:          'browse',
		describe:         true,
		dragInfo:         true,
		idealColumnWidth: 170,
		editing:          false,
		priority:         60,
		SettingsView:     false,
		syncSelection:    false
	},

	/**
	 * @since 3.9.0
	 */
	initialize: function() {
		var collectionType = this.get('collectionType');

		if ( 'video' === this.get( 'type' ) ) {
			collectionType = 'video-' + collectionType;
		}

		this.set( 'id', collectionType + '-edit' );
		this.set( 'toolbar', collectionType + '-edit' );

		// If we haven't been provided a `library`, create a `Selection`.
		if ( ! this.get('library') ) {
			this.set( 'library', new wp.media.model.Selection() );
		}
		// The single `Attachment` view to be used in the `Attachments` view.
		if ( ! this.get('AttachmentView') ) {
			this.set( 'AttachmentView', wp.media.view.Attachment.EditLibrary );
		}
		Library.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		var library = this.get('library');

		// Limit the library to images only.
		library.props.set( 'type', this.get( 'type' ) );

		// Watch for uploaded attachments.
		this.get('library').observe( wp.Uploader.queue );

		this.frame.on( 'content:render:browse', this.renderSettings, this );

		Library.prototype.activate.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	deactivate: function() {
		// Stop watching for uploaded attachments.
		this.get('library').unobserve( wp.Uploader.queue );

		this.frame.off( 'content:render:browse', this.renderSettings, this );

		Library.prototype.deactivate.apply( this, arguments );
	},

	/**
	 * Render the collection embed settings view in the browser sidebar.
	 *
	 * @todo This is against the pattern elsewhere in media. Typically the frame
	 *       is responsible for adding region mode callbacks. Explain.
	 *
	 * @since 3.9.0
	 *
	 * @param {wp.media.view.attachmentsBrowser} The attachments browser view.
	 */
	renderSettings: function( attachmentsBrowserView ) {
		var library = this.get('library'),
			collectionType = this.get('collectionType'),
			dragInfoText = this.get('dragInfoText'),
			SettingsView = this.get('SettingsView'),
			obj = {};

		if ( ! library || ! attachmentsBrowserView ) {
			return;
		}

		library[ collectionType ] = library[ collectionType ] || new Backbone.Model();

		obj[ collectionType ] = new SettingsView({
			controller: this,
			model:      library[ collectionType ],
			priority:   40
		});

		attachmentsBrowserView.sidebar.set( obj );

		if ( dragInfoText ) {
			attachmentsBrowserView.toolbar.set( 'dragInfo', new wp.media.View({
				el: $( '<div class="instructions">' + dragInfoText + '</div>' )[0],
				priority: -40
			}) );
		}

		// Add the 'Reverse order' button to the toolbar.
		attachmentsBrowserView.toolbar.set( 'reverse', {
			text:     l10n.reverseOrder,
			priority: 80,

			click: function() {
				library.reset( library.toArray().reverse() );
			}
		});
	}
});

module.exports = CollectionEdit;

},{}],3:[function(require,module,exports){
/**
 * wp.media.controller.Cropper
 *
 * A state for cropping an image.
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 */
var l10n = wp.media.view.l10n,
	Cropper;

Cropper = wp.media.controller.State.extend({
	defaults: {
		id:          'cropper',
		title:       l10n.cropImage,
		// Region mode defaults.
		toolbar:     'crop',
		content:     'crop',
		router:      false,
		canSkipCrop: false,

		// Default doCrop Ajax arguments to allow the Customizer (for example) to inject state.
		doCropArgs: {}
	},

	activate: function() {
		this.frame.on( 'content:create:crop', this.createCropContent, this );
		this.frame.on( 'close', this.removeCropper, this );
		this.set('selection', new Backbone.Collection(this.frame._selection.single));
	},

	deactivate: function() {
		this.frame.toolbar.mode('browse');
	},

	createCropContent: function() {
		this.cropperView = new wp.media.view.Cropper({
			controller: this,
			attachment: this.get('selection').first()
		});
		this.cropperView.on('image-loaded', this.createCropToolbar, this);
		this.frame.content.set(this.cropperView);

	},
	removeCropper: function() {
		this.imgSelect.cancelSelection();
		this.imgSelect.setOptions({remove: true});
		this.imgSelect.update();
		this.cropperView.remove();
	},
	createCropToolbar: function() {
		var canSkipCrop, toolbarOptions;

		canSkipCrop = this.get('canSkipCrop') || false;

		toolbarOptions = {
			controller: this.frame,
			items: {
				insert: {
					style:    'primary',
					text:     l10n.cropImage,
					priority: 80,
					requires: { library: false, selection: false },

					click: function() {
						var controller = this.controller,
							selection;

						selection = controller.state().get('selection').first();
						selection.set({cropDetails: controller.state().imgSelect.getSelection()});

						this.$el.text(l10n.cropping);
						this.$el.attr('disabled', true);

						controller.state().doCrop( selection ).done( function( croppedImage ) {
							controller.trigger('cropped', croppedImage );
							controller.close();
						}).fail( function() {
							controller.trigger('content:error:crop');
						});
					}
				}
			}
		};

		if ( canSkipCrop ) {
			_.extend( toolbarOptions.items, {
				skip: {
					style:      'secondary',
					text:       l10n.skipCropping,
					priority:   70,
					requires:   { library: false, selection: false },
					click:      function() {
						var selection = this.controller.state().get('selection').first();
						this.controller.state().cropperView.remove();
						this.controller.trigger('skippedcrop', selection);
						this.controller.close();
					}
				}
			});
		}

		this.frame.toolbar.set( new wp.media.view.Toolbar(toolbarOptions) );
	},

	doCrop: function( attachment ) {
		return wp.ajax.post( 'custom-header-crop', _.extend(
			{},
			this.defaults.doCropArgs,
			{
				nonce: attachment.get( 'nonces' ).edit,
				id: attachment.get( 'id' ),
				cropDetails: attachment.get( 'cropDetails' )
			}
		) );
	}
});

module.exports = Cropper;

},{}],4:[function(require,module,exports){
/**
 * wp.media.controller.CustomizeImageCropper
 *
 * A state for cropping an image.
 *
 * @class
 * @augments wp.media.controller.Cropper
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 */
var Controller = wp.media.controller,
	CustomizeImageCropper;

CustomizeImageCropper = Controller.Cropper.extend({
	doCrop: function( attachment ) {
		var cropDetails = attachment.get( 'cropDetails' ),
			control = this.get( 'control' ),
			ratio = cropDetails.width / cropDetails.height;

		// Use crop measurements when flexible in both directions.
		if ( control.params.flex_width && control.params.flex_height ) {
			cropDetails.dst_width  = cropDetails.width;
			cropDetails.dst_height = cropDetails.height;

		// Constrain flexible side based on image ratio and size of the fixed side.
		} else {
			cropDetails.dst_width  = control.params.flex_width  ? control.params.height * ratio : control.params.width;
			cropDetails.dst_height = control.params.flex_height ? control.params.width  / ratio : control.params.height;
		}

		return wp.ajax.post( 'crop-image', {
			wp_customize: 'on',
			nonce: attachment.get( 'nonces' ).edit,
			id: attachment.get( 'id' ),
			context: control.id,
			cropDetails: cropDetails
		} );
	}
});

module.exports = CustomizeImageCropper;

},{}],5:[function(require,module,exports){
/**
 * wp.media.controller.EditImage
 *
 * A state for editing (cropping, etc.) an image.
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                    attributes                      The attributes hash passed to the state.
 * @param {wp.media.model.Attachment} attributes.model                The attachment.
 * @param {string}                    [attributes.id=edit-image]      Unique identifier.
 * @param {string}                    [attributes.title=Edit Image]   Title for the state. Displays in the media menu and the frame's title region.
 * @param {string}                    [attributes.content=edit-image] Initial mode for the content region.
 * @param {string}                    [attributes.toolbar=edit-image] Initial mode for the toolbar region.
 * @param {string}                    [attributes.menu=false]         Initial mode for the menu region.
 * @param {string}                    [attributes.url]                Unused. @todo Consider removal.
 */
var l10n = wp.media.view.l10n,
	EditImage;

EditImage = wp.media.controller.State.extend({
	defaults: {
		id:      'edit-image',
		title:   l10n.editImage,
		menu:    false,
		toolbar: 'edit-image',
		content: 'edit-image',
		url:     ''
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		this.frame.on( 'toolbar:render:edit-image', _.bind( this.toolbar, this ) );
	},

	/**
	 * @since 3.9.0
	 */
	deactivate: function() {
		this.frame.off( 'toolbar:render:edit-image' );
	},

	/**
	 * @since 3.9.0
	 */
	toolbar: function() {
		var frame = this.frame,
			lastState = frame.lastState(),
			previous = lastState && lastState.id;

		frame.toolbar.set( new wp.media.view.Toolbar({
			controller: frame,
			items: {
				back: {
					style: 'primary',
					text:     l10n.back,
					priority: 20,
					click:    function() {
						if ( previous ) {
							frame.setState( previous );
						} else {
							frame.close();
						}
					}
				}
			}
		}) );
	}
});

module.exports = EditImage;

},{}],6:[function(require,module,exports){
/**
 * wp.media.controller.Embed
 *
 * A state for embedding media from a URL.
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object} attributes                         The attributes hash passed to the state.
 * @param {string} [attributes.id=embed]              Unique identifier.
 * @param {string} [attributes.title=Insert From URL] Title for the state. Displays in the media menu and the frame's title region.
 * @param {string} [attributes.content=embed]         Initial mode for the content region.
 * @param {string} [attributes.menu=default]          Initial mode for the menu region.
 * @param {string} [attributes.toolbar=main-embed]    Initial mode for the toolbar region.
 * @param {string} [attributes.menu=false]            Initial mode for the menu region.
 * @param {int}    [attributes.priority=120]          The priority for the state link in the media menu.
 * @param {string} [attributes.type=link]             The type of embed. Currently only link is supported.
 * @param {string} [attributes.url]                   The embed URL.
 * @param {object} [attributes.metadata={}]           Properties of the embed, which will override attributes.url if set.
 */
var l10n = wp.media.view.l10n,
	$ = Backbone.$,
	Embed;

Embed = wp.media.controller.State.extend({
	defaults: {
		id:       'embed',
		title:    l10n.insertFromUrlTitle,
		content:  'embed',
		menu:     'default',
		toolbar:  'main-embed',
		priority: 120,
		type:     'link',
		url:      '',
		metadata: {}
	},

	// The amount of time used when debouncing the scan.
	sensitivity: 400,

	initialize: function(options) {
		this.metadata = options.metadata;
		this.debouncedScan = _.debounce( _.bind( this.scan, this ), this.sensitivity );
		this.props = new Backbone.Model( this.metadata || { url: '' });
		this.props.on( 'change:url', this.debouncedScan, this );
		this.props.on( 'change:url', this.refresh, this );
		this.on( 'scan', this.scanImage, this );
	},

	/**
	 * Trigger a scan of the embedded URL's content for metadata required to embed.
	 *
	 * @fires wp.media.controller.Embed#scan
	 */
	scan: function() {
		var scanners,
			embed = this,
			attributes = {
				type: 'link',
				scanners: []
			};

		// Scan is triggered with the list of `attributes` to set on the
		// state, useful for the 'type' attribute and 'scanners' attribute,
		// an array of promise objects for asynchronous scan operations.
		if ( this.props.get('url') ) {
			this.trigger( 'scan', attributes );
		}

		if ( attributes.scanners.length ) {
			scanners = attributes.scanners = $.when.apply( $, attributes.scanners );
			scanners.always( function() {
				if ( embed.get('scanners') === scanners ) {
					embed.set( 'loading', false );
				}
			});
		} else {
			attributes.scanners = null;
		}

		attributes.loading = !! attributes.scanners;
		this.set( attributes );
	},
	/**
	 * Try scanning the embed as an image to discover its dimensions.
	 *
	 * @param {Object} attributes
	 */
	scanImage: function( attributes ) {
		var frame = this.frame,
			state = this,
			url = this.props.get('url'),
			image = new Image(),
			deferred = $.Deferred();

		attributes.scanners.push( deferred.promise() );

		// Try to load the image and find its width/height.
		image.onload = function() {
			deferred.resolve();

			if ( state !== frame.state() || url !== state.props.get('url') ) {
				return;
			}

			state.set({
				type: 'image'
			});

			state.props.set({
				width:  image.width,
				height: image.height
			});
		};

		image.onerror = deferred.reject;
		image.src = url;
	},

	refresh: function() {
		this.frame.toolbar.get().refresh();
	},

	reset: function() {
		this.props.clear().set({ url: '' });

		if ( this.active ) {
			this.refresh();
		}
	}
});

module.exports = Embed;

},{}],7:[function(require,module,exports){
/**
 * wp.media.controller.FeaturedImage
 *
 * A state for selecting a featured image for a post.
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                          The attributes hash passed to the state.
 * @param {string}                     [attributes.id=featured-image]        Unique identifier.
 * @param {string}                     [attributes.title=Set Featured Image] Title for the state. Displays in the media menu and the frame's title region.
 * @param {wp.media.model.Attachments} [attributes.library]                  The attachments collection to browse.
 *                                                                           If one is not supplied, a collection of all images will be created.
 * @param {boolean}                    [attributes.multiple=false]           Whether multi-select is enabled.
 * @param {string}                     [attributes.content=upload]           Initial mode for the content region.
 *                                                                           Overridden by persistent user setting if 'contentUserSetting' is true.
 * @param {string}                     [attributes.menu=default]             Initial mode for the menu region.
 * @param {string}                     [attributes.router=browse]            Initial mode for the router region.
 * @param {string}                     [attributes.toolbar=featured-image]   Initial mode for the toolbar region.
 * @param {int}                        [attributes.priority=60]              The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.searchable=true]          Whether the library is searchable.
 * @param {boolean|string}             [attributes.filterable=false]         Whether the library is filterable, and if so what filters should be shown.
 *                                                                           Accepts 'all', 'uploaded', or 'unattached'.
 * @param {boolean}                    [attributes.sortable=true]            Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.autoSelect=true]          Whether an uploaded attachment should be automatically added to the selection.
 * @param {boolean}                    [attributes.describe=false]           Whether to offer UI to describe attachments - e.g. captioning images in a gallery.
 * @param {boolean}                    [attributes.contentUserSetting=true]  Whether the content region's mode should be set and persisted per user.
 * @param {boolean}                    [attributes.syncSelection=true]       Whether the Attachments selection should be persisted from the last state.
 */
var Attachment = wp.media.model.Attachment,
	Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	FeaturedImage;

FeaturedImage = Library.extend({
	defaults: _.defaults({
		id:            'featured-image',
		title:         l10n.setFeaturedImageTitle,
		multiple:      false,
		filterable:    'uploaded',
		toolbar:       'featured-image',
		priority:      60,
		syncSelection: true
	}, Library.prototype.defaults ),

	/**
	 * @since 3.5.0
	 */
	initialize: function() {
		var library, comparator;

		// If we haven't been provided a `library`, create a `Selection`.
		if ( ! this.get('library') ) {
			this.set( 'library', wp.media.query({ type: 'image' }) );
		}

		Library.prototype.initialize.apply( this, arguments );

		library    = this.get('library');
		comparator = library.comparator;

		// Overload the library's comparator to push items that are not in
		// the mirrored query to the front of the aggregate collection.
		library.comparator = function( a, b ) {
			var aInQuery = !! this.mirroring.get( a.cid ),
				bInQuery = !! this.mirroring.get( b.cid );

			if ( ! aInQuery && bInQuery ) {
				return -1;
			} else if ( aInQuery && ! bInQuery ) {
				return 1;
			} else {
				return comparator.apply( this, arguments );
			}
		};

		// Add all items in the selection to the library, so any featured
		// images that are not initially loaded still appear.
		library.observe( this.get('selection') );
	},

	/**
	 * @since 3.5.0
	 */
	activate: function() {
		this.updateSelection();
		this.frame.on( 'open', this.updateSelection, this );

		Library.prototype.activate.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 */
	deactivate: function() {
		this.frame.off( 'open', this.updateSelection, this );

		Library.prototype.deactivate.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 */
	updateSelection: function() {
		var selection = this.get('selection'),
			id = wp.media.view.settings.post.featuredImageId,
			attachment;

		if ( '' !== id && -1 !== id ) {
			attachment = Attachment.get( id );
			attachment.fetch();
		}

		selection.reset( attachment ? [ attachment ] : [] );
	}
});

module.exports = FeaturedImage;

},{}],8:[function(require,module,exports){
/**
 * wp.media.controller.GalleryAdd
 *
 * A state for selecting more images to add to a gallery.
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                         The attributes hash passed to the state.
 * @param {string}                     [attributes.id=gallery-library]      Unique identifier.
 * @param {string}                     [attributes.title=Add to Gallery]    Title for the state. Displays in the frame's title region.
 * @param {boolean}                    [attributes.multiple=add]            Whether multi-select is enabled. @todo 'add' doesn't seem do anything special, and gets used as a boolean.
 * @param {wp.media.model.Attachments} [attributes.library]                 The attachments collection to browse.
 *                                                                          If one is not supplied, a collection of all images will be created.
 * @param {boolean|string}             [attributes.filterable=uploaded]     Whether the library is filterable, and if so what filters should be shown.
 *                                                                          Accepts 'all', 'uploaded', or 'unattached'.
 * @param {string}                     [attributes.menu=gallery]            Initial mode for the menu region.
 * @param {string}                     [attributes.content=upload]          Initial mode for the content region.
 *                                                                          Overridden by persistent user setting if 'contentUserSetting' is true.
 * @param {string}                     [attributes.router=browse]           Initial mode for the router region.
 * @param {string}                     [attributes.toolbar=gallery-add]     Initial mode for the toolbar region.
 * @param {boolean}                    [attributes.searchable=true]         Whether the library is searchable.
 * @param {boolean}                    [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
 * @param {boolean}                    [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
 * @param {int}                        [attributes.priority=100]            The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.syncSelection=false]     Whether the Attachments selection should be persisted from the last state.
 *                                                                          Defaults to false because for this state, because the library of the Edit Gallery state is the selection.
 */
var Selection = wp.media.model.Selection,
	Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	GalleryAdd;

GalleryAdd = Library.extend({
	defaults: _.defaults({
		id:            'gallery-library',
		title:         l10n.addToGalleryTitle,
		multiple:      'add',
		filterable:    'uploaded',
		menu:          'gallery',
		toolbar:       'gallery-add',
		priority:      100,
		syncSelection: false
	}, Library.prototype.defaults ),

	/**
	 * @since 3.5.0
	 */
	initialize: function() {
		// If a library wasn't supplied, create a library of images.
		if ( ! this.get('library') ) {
			this.set( 'library', wp.media.query({ type: 'image' }) );
		}

		Library.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 */
	activate: function() {
		var library = this.get('library'),
			edit    = this.frame.state('gallery-edit').get('library');

		if ( this.editLibrary && this.editLibrary !== edit ) {
			library.unobserve( this.editLibrary );
		}

		// Accepts attachments that exist in the original library and
		// that do not exist in gallery's library.
		library.validator = function( attachment ) {
			return !! this.mirroring.get( attachment.cid ) && ! edit.get( attachment.cid ) && Selection.prototype.validator.apply( this, arguments );
		};

		// Reset the library to ensure that all attachments are re-added
		// to the collection. Do so silently, as calling `observe` will
		// trigger the `reset` event.
		library.reset( library.mirroring.models, { silent: true });
		library.observe( edit );
		this.editLibrary = edit;

		Library.prototype.activate.apply( this, arguments );
	}
});

module.exports = GalleryAdd;

},{}],9:[function(require,module,exports){
/**
 * wp.media.controller.GalleryEdit
 *
 * A state for editing a gallery's images and settings.
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                       The attributes hash passed to the state.
 * @param {string}                     [attributes.id=gallery-edit]       Unique identifier.
 * @param {string}                     [attributes.title=Edit Gallery]    Title for the state. Displays in the frame's title region.
 * @param {wp.media.model.Attachments} [attributes.library]               The collection of attachments in the gallery.
 *                                                                        If one is not supplied, an empty media.model.Selection collection is created.
 * @param {boolean}                    [attributes.multiple=false]        Whether multi-select is enabled.
 * @param {boolean}                    [attributes.searchable=false]      Whether the library is searchable.
 * @param {boolean}                    [attributes.sortable=true]         Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.date=true]             Whether to show the date filter in the browser's toolbar.
 * @param {string|false}               [attributes.content=browse]        Initial mode for the content region.
 * @param {string|false}               [attributes.toolbar=image-details] Initial mode for the toolbar region.
 * @param {boolean}                    [attributes.describe=true]         Whether to offer UI to describe attachments - e.g. captioning images in a gallery.
 * @param {boolean}                    [attributes.displaySettings=true]  Whether to show the attachment display settings interface.
 * @param {boolean}                    [attributes.dragInfo=true]         Whether to show instructional text about the attachments being sortable.
 * @param {int}                        [attributes.idealColumnWidth=170]  The ideal column width in pixels for attachments.
 * @param {boolean}                    [attributes.editing=false]         Whether the gallery is being created, or editing an existing instance.
 * @param {int}                        [attributes.priority=60]           The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.syncSelection=false]   Whether the Attachments selection should be persisted from the last state.
 *                                                                        Defaults to false for this state, because the library passed in  *is* the selection.
 * @param {view}                       [attributes.AttachmentView]        The single `Attachment` view to be used in the `Attachments`.
 *                                                                        If none supplied, defaults to wp.media.view.Attachment.EditLibrary.
 */
var Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	GalleryEdit;

GalleryEdit = Library.extend({
	defaults: {
		id:               'gallery-edit',
		title:            l10n.editGalleryTitle,
		multiple:         false,
		searchable:       false,
		sortable:         true,
		date:             false,
		display:          false,
		content:          'browse',
		toolbar:          'gallery-edit',
		describe:         true,
		displaySettings:  true,
		dragInfo:         true,
		idealColumnWidth: 170,
		editing:          false,
		priority:         60,
		syncSelection:    false
	},

	/**
	 * @since 3.5.0
	 */
	initialize: function() {
		// If we haven't been provided a `library`, create a `Selection`.
		if ( ! this.get('library') ) {
			this.set( 'library', new wp.media.model.Selection() );
		}

		// The single `Attachment` view to be used in the `Attachments` view.
		if ( ! this.get('AttachmentView') ) {
			this.set( 'AttachmentView', wp.media.view.Attachment.EditLibrary );
		}

		Library.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 */
	activate: function() {
		var library = this.get('library');

		// Limit the library to images only.
		library.props.set( 'type', 'image' );

		// Watch for uploaded attachments.
		this.get('library').observe( wp.Uploader.queue );

		this.frame.on( 'content:render:browse', this.gallerySettings, this );

		Library.prototype.activate.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 */
	deactivate: function() {
		// Stop watching for uploaded attachments.
		this.get('library').unobserve( wp.Uploader.queue );

		this.frame.off( 'content:render:browse', this.gallerySettings, this );

		Library.prototype.deactivate.apply( this, arguments );
	},

	/**
	 * @since 3.5.0
	 *
	 * @param browser
	 */
	gallerySettings: function( browser ) {
		if ( ! this.get('displaySettings') ) {
			return;
		}

		var library = this.get('library');

		if ( ! library || ! browser ) {
			return;
		}

		library.gallery = library.gallery || new Backbone.Model();

		browser.sidebar.set({
			gallery: new wp.media.view.Settings.Gallery({
				controller: this,
				model:      library.gallery,
				priority:   40
			})
		});

		browser.toolbar.set( 'reverse', {
			text:     l10n.reverseOrder,
			priority: 80,

			click: function() {
				library.reset( library.toArray().reverse() );
			}
		});
	}
});

module.exports = GalleryEdit;

},{}],10:[function(require,module,exports){
/**
 * wp.media.controller.ImageDetails
 *
 * A state for editing the attachment display settings of an image that's been
 * inserted into the editor.
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                    [attributes]                       The attributes hash passed to the state.
 * @param {string}                    [attributes.id=image-details]      Unique identifier.
 * @param {string}                    [attributes.title=Image Details]   Title for the state. Displays in the frame's title region.
 * @param {wp.media.model.Attachment} attributes.image                   The image's model.
 * @param {string|false}              [attributes.content=image-details] Initial mode for the content region.
 * @param {string|false}              [attributes.menu=false]            Initial mode for the menu region.
 * @param {string|false}              [attributes.router=false]          Initial mode for the router region.
 * @param {string|false}              [attributes.toolbar=image-details] Initial mode for the toolbar region.
 * @param {boolean}                   [attributes.editing=false]         Unused.
 * @param {int}                       [attributes.priority=60]           Unused.
 *
 * @todo This state inherits some defaults from media.controller.Library.prototype.defaults,
 *       however this may not do anything.
 */
var State = wp.media.controller.State,
	Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	ImageDetails;

ImageDetails = State.extend({
	defaults: _.defaults({
		id:       'image-details',
		title:    l10n.imageDetailsTitle,
		content:  'image-details',
		menu:     false,
		router:   false,
		toolbar:  'image-details',
		editing:  false,
		priority: 60
	}, Library.prototype.defaults ),

	/**
	 * @since 3.9.0
	 *
	 * @param options Attributes
	 */
	initialize: function( options ) {
		this.image = options.image;
		State.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		this.frame.modal.$el.addClass('image-details');
	}
});

module.exports = ImageDetails;

},{}],11:[function(require,module,exports){
/**
 * wp.media.controller.Library
 *
 * A state for choosing an attachment or group of attachments from the media library.
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 * @mixes media.selectionSync
 *
 * @param {object}                          [attributes]                         The attributes hash passed to the state.
 * @param {string}                          [attributes.id=library]              Unique identifier.
 * @param {string}                          [attributes.title=Media library]     Title for the state. Displays in the media menu and the frame's title region.
 * @param {wp.media.model.Attachments}      [attributes.library]                 The attachments collection to browse.
 *                                                                               If one is not supplied, a collection of all attachments will be created.
 * @param {wp.media.model.Selection|object} [attributes.selection]               A collection to contain attachment selections within the state.
 *                                                                               If the 'selection' attribute is a plain JS object,
 *                                                                               a Selection will be created using its values as the selection instance's `props` model.
 *                                                                               Otherwise, it will copy the library's `props` model.
 * @param {boolean}                         [attributes.multiple=false]          Whether multi-select is enabled.
 * @param {string}                          [attributes.content=upload]          Initial mode for the content region.
 *                                                                               Overridden by persistent user setting if 'contentUserSetting' is true.
 * @param {string}                          [attributes.menu=default]            Initial mode for the menu region.
 * @param {string}                          [attributes.router=browse]           Initial mode for the router region.
 * @param {string}                          [attributes.toolbar=select]          Initial mode for the toolbar region.
 * @param {boolean}                         [attributes.searchable=true]         Whether the library is searchable.
 * @param {boolean|string}                  [attributes.filterable=false]        Whether the library is filterable, and if so what filters should be shown.
 *                                                                               Accepts 'all', 'uploaded', or 'unattached'.
 * @param {boolean}                         [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                         [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
 * @param {boolean}                         [attributes.describe=false]          Whether to offer UI to describe attachments - e.g. captioning images in a gallery.
 * @param {boolean}                         [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
 * @param {boolean}                         [attributes.syncSelection=true]      Whether the Attachments selection should be persisted from the last state.
 */
var l10n = wp.media.view.l10n,
	getUserSetting = window.getUserSetting,
	setUserSetting = window.setUserSetting,
	Library;

Library = wp.media.controller.State.extend({
	defaults: {
		id:                 'library',
		title:              l10n.mediaLibraryTitle,
		multiple:           false,
		content:            'upload',
		menu:               'default',
		router:             'browse',
		toolbar:            'select',
		searchable:         true,
		filterable:         false,
		sortable:           true,
		autoSelect:         true,
		describe:           false,
		contentUserSetting: true,
		syncSelection:      true
	},

	/**
	 * If a library isn't provided, query all media items.
	 * If a selection instance isn't provided, create one.
	 *
	 * @since 3.5.0
	 */
	initialize: function() {
		var selection = this.get('selection'),
			props;

		if ( ! this.get('library') ) {
			this.set( 'library', wp.media.query() );
		}

		if ( ! ( selection instanceof wp.media.model.Selection ) ) {
			props = selection;

			if ( ! props ) {
				props = this.get('library').props.toJSON();
				props = _.omit( props, 'orderby', 'query' );
			}

			this.set( 'selection', new wp.media.model.Selection( null, {
				multiple: this.get('multiple'),
				props: props
			}) );
		}

		this.resetDisplays();
	},

	/**
	 * @since 3.5.0
	 */
	activate: function() {
		this.syncSelection();

		wp.Uploader.queue.on( 'add', this.uploading, this );

		this.get('selection').on( 'add remove reset', this.refreshContent, this );

		if ( this.get( 'router' ) && this.get('contentUserSetting') ) {
			this.frame.on( 'content:activate', this.saveContentMode, this );
			this.set( 'content', getUserSetting( 'libraryContent', this.get('content') ) );
		}
	},

	/**
	 * @since 3.5.0
	 */
	deactivate: function() {
		this.recordSelection();

		this.frame.off( 'content:activate', this.saveContentMode, this );

		// Unbind all event handlers that use this state as the context
		// from the selection.
		this.get('selection').off( null, null, this );

		wp.Uploader.queue.off( null, null, this );
	},

	/**
	 * Reset the library to its initial state.
	 *
	 * @since 3.5.0
	 */
	reset: function() {
		this.get('selection').reset();
		this.resetDisplays();
		this.refreshContent();
	},

	/**
	 * Reset the attachment display settings defaults to the site options.
	 *
	 * If site options don't define them, fall back to a persistent user setting.
	 *
	 * @since 3.5.0
	 */
	resetDisplays: function() {
		var defaultProps = wp.media.view.settings.defaultProps;
		this._displays = [];
		this._defaultDisplaySettings = {
			align: getUserSetting( 'align', defaultProps.align ) || 'none',
			size:  getUserSetting( 'imgsize', defaultProps.size ) || 'medium',
			link:  getUserSetting( 'urlbutton', defaultProps.link ) || 'none'
		};
	},

	/**
	 * Create a model to represent display settings (alignment, etc.) for an attachment.
	 *
	 * @since 3.5.0
	 *
	 * @param {wp.media.model.Attachment} attachment
	 * @returns {Backbone.Model}
	 */
	display: function( attachment ) {
		var displays = this._displays;

		if ( ! displays[ attachment.cid ] ) {
			displays[ attachment.cid ] = new Backbone.Model( this.defaultDisplaySettings( attachment ) );
		}
		return displays[ attachment.cid ];
	},

	/**
	 * Given an attachment, create attachment display settings properties.
	 *
	 * @since 3.6.0
	 *
	 * @param {wp.media.model.Attachment} attachment
	 * @returns {Object}
	 */
	defaultDisplaySettings: function( attachment ) {
		var settings = _.clone( this._defaultDisplaySettings );

		if ( settings.canEmbed = this.canEmbed( attachment ) ) {
			settings.link = 'embed';
		} else if ( ! this.isImageAttachment( attachment ) && settings.link === 'none' ) {
			settings.link = 'file';
		}

		return settings;
	},

	/**
	 * Whether an attachment is image.
	 *
	 * @since 4.4.1
	 *
	 * @param {wp.media.model.Attachment} attachment
	 * @returns {Boolean}
	 */
	isImageAttachment: function( attachment ) {
		// If uploading, we know the filename but not the mime type.
		if ( attachment.get('uploading') ) {
			return /\.(jpe?g|png|gif)$/i.test( attachment.get('filename') );
		}

		return attachment.get('type') === 'image';
	},

	/**
	 * Whether an attachment can be embedded (audio or video).
	 *
	 * @since 3.6.0
	 *
	 * @param {wp.media.model.Attachment} attachment
	 * @returns {Boolean}
	 */
	canEmbed: function( attachment ) {
		// If uploading, we know the filename but not the mime type.
		if ( ! attachment.get('uploading') ) {
			var type = attachment.get('type');
			if ( type !== 'audio' && type !== 'video' ) {
				return false;
			}
		}

		return _.contains( wp.media.view.settings.embedExts, attachment.get('filename').split('.').pop() );
	},


	/**
	 * If the state is active, no items are selected, and the current
	 * content mode is not an option in the state's router (provided
	 * the state has a router), reset the content mode to the default.
	 *
	 * @since 3.5.0
	 */
	refreshContent: function() {
		var selection = this.get('selection'),
			frame = this.frame,
			router = frame.router.get(),
			mode = frame.content.mode();

		if ( this.active && ! selection.length && router && ! router.get( mode ) ) {
			this.frame.content.render( this.get('content') );
		}
	},

	/**
	 * Callback handler when an attachment is uploaded.
	 *
	 * Switch to the Media Library if uploaded from the 'Upload Files' tab.
	 *
	 * Adds any uploading attachments to the selection.
	 *
	 * If the state only supports one attachment to be selected and multiple
	 * attachments are uploaded, the last attachment in the upload queue will
	 * be selected.
	 *
	 * @since 3.5.0
	 *
	 * @param {wp.media.model.Attachment} attachment
	 */
	uploading: function( attachment ) {
		var content = this.frame.content;

		if ( 'upload' === content.mode() ) {
			this.frame.content.mode('browse');
		}

		if ( this.get( 'autoSelect' ) ) {
			this.get('selection').add( attachment );
			this.frame.trigger( 'library:selection:add' );
		}
	},

	/**
	 * Persist the mode of the content region as a user setting.
	 *
	 * @since 3.5.0
	 */
	saveContentMode: function() {
		if ( 'browse' !== this.get('router') ) {
			return;
		}

		var mode = this.frame.content.mode(),
			view = this.frame.router.get();

		if ( view && view.get( mode ) ) {
			setUserSetting( 'libraryContent', mode );
		}
	}
});

// Make selectionSync available on any Media Library state.
_.extend( Library.prototype, wp.media.selectionSync );

module.exports = Library;

},{}],12:[function(require,module,exports){
/**
 * wp.media.controller.MediaLibrary
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 */
var Library = wp.media.controller.Library,
	MediaLibrary;

MediaLibrary = Library.extend({
	defaults: _.defaults({
		// Attachments browser defaults. @see media.view.AttachmentsBrowser
		filterable:      'uploaded',

		displaySettings: false,
		priority:        80,
		syncSelection:   false
	}, Library.prototype.defaults ),

	/**
	 * @since 3.9.0
	 *
	 * @param options
	 */
	initialize: function( options ) {
		this.media = options.media;
		this.type = options.type;
		this.set( 'library', wp.media.query({ type: this.type }) );

		Library.prototype.initialize.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		// @todo this should use this.frame.
		if ( wp.media.frame.lastMime ) {
			this.set( 'library', wp.media.query({ type: wp.media.frame.lastMime }) );
			delete wp.media.frame.lastMime;
		}
		Library.prototype.activate.apply( this, arguments );
	}
});

module.exports = MediaLibrary;

},{}],13:[function(require,module,exports){
/**
 * wp.media.controller.Region
 *
 * A region is a persistent application layout area.
 *
 * A region assumes one mode at any time, and can be switched to another.
 *
 * When mode changes, events are triggered on the region's parent view.
 * The parent view will listen to specific events and fill the region with an
 * appropriate view depending on mode. For example, a frame listens for the
 * 'browse' mode t be activated on the 'content' view and then fills the region
 * with an AttachmentsBrowser view.
 *
 * @class
 *
 * @param {object}        options          Options hash for the region.
 * @param {string}        options.id       Unique identifier for the region.
 * @param {Backbone.View} options.view     A parent view the region exists within.
 * @param {string}        options.selector jQuery selector for the region within the parent view.
 */
var Region = function( options ) {
	_.extend( this, _.pick( options || {}, 'id', 'view', 'selector' ) );
};

// Use Backbone's self-propagating `extend` inheritance method.
Region.extend = Backbone.Model.extend;

_.extend( Region.prototype, {
	/**
	 * Activate a mode.
	 *
	 * @since 3.5.0
	 *
	 * @param {string} mode
	 *
	 * @fires this.view#{this.id}:activate:{this._mode}
	 * @fires this.view#{this.id}:activate
	 * @fires this.view#{this.id}:deactivate:{this._mode}
	 * @fires this.view#{this.id}:deactivate
	 *
	 * @returns {wp.media.controller.Region} Returns itself to allow chaining.
	 */
	mode: function( mode ) {
		if ( ! mode ) {
			return this._mode;
		}
		// Bail if we're trying to change to the current mode.
		if ( mode === this._mode ) {
			return this;
		}

		/**
		 * Region mode deactivation event.
		 *
		 * @event this.view#{this.id}:deactivate:{this._mode}
		 * @event this.view#{this.id}:deactivate
		 */
		this.trigger('deactivate');

		this._mode = mode;
		this.render( mode );

		/**
		 * Region mode activation event.
		 *
		 * @event this.view#{this.id}:activate:{this._mode}
		 * @event this.view#{this.id}:activate
		 */
		this.trigger('activate');
		return this;
	},
	/**
	 * Render a mode.
	 *
	 * @since 3.5.0
	 *
	 * @param {string} mode
	 *
	 * @fires this.view#{this.id}:create:{this._mode}
	 * @fires this.view#{this.id}:create
	 * @fires this.view#{this.id}:render:{this._mode}
	 * @fires this.view#{this.id}:render
	 *
	 * @returns {wp.media.controller.Region} Returns itself to allow chaining
	 */
	render: function( mode ) {
		// If the mode isn't active, activate it.
		if ( mode && mode !== this._mode ) {
			return this.mode( mode );
		}

		var set = { view: null },
			view;

		/**
		 * Create region view event.
		 *
		 * Region view creation takes place in an event callback on the frame.
		 *
		 * @event this.view#{this.id}:create:{this._mode}
		 * @event this.view#{this.id}:create
		 */
		this.trigger( 'create', set );
		view = set.view;

		/**
		 * Render region view event.
		 *
		 * Region view creation takes place in an event callback on the frame.
		 *
		 * @event this.view#{this.id}:create:{this._mode}
		 * @event this.view#{this.id}:create
		 */
		this.trigger( 'render', view );
		if ( view ) {
			this.set( view );
		}
		return this;
	},

	/**
	 * Get the region's view.
	 *
	 * @since 3.5.0
	 *
	 * @returns {wp.media.View}
	 */
	get: function() {
		return this.view.views.first( this.selector );
	},

	/**
	 * Set the region's view as a subview of the frame.
	 *
	 * @since 3.5.0
	 *
	 * @param {Array|Object} views
	 * @param {Object} [options={}]
	 * @returns {wp.Backbone.Subviews} Subviews is returned to allow chaining
	 */
	set: function( views, options ) {
		if ( options ) {
			options.add = false;
		}
		return this.view.views.set( this.selector, views, options );
	},

	/**
	 * Trigger regional view events on the frame.
	 *
	 * @since 3.5.0
	 *
	 * @param {string} event
	 * @returns {undefined|wp.media.controller.Region} Returns itself to allow chaining.
	 */
	trigger: function( event ) {
		var base, args;

		if ( ! this._mode ) {
			return;
		}

		args = _.toArray( arguments );
		base = this.id + ':' + event;

		// Trigger `{this.id}:{event}:{this._mode}` event on the frame.
		args[0] = base + ':' + this._mode;
		this.view.trigger.apply( this.view, args );

		// Trigger `{this.id}:{event}` event on the frame.
		args[0] = base;
		this.view.trigger.apply( this.view, args );
		return this;
	}
});

module.exports = Region;

},{}],14:[function(require,module,exports){
/**
 * wp.media.controller.ReplaceImage
 *
 * A state for replacing an image.
 *
 * @class
 * @augments wp.media.controller.Library
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                     [attributes]                         The attributes hash passed to the state.
 * @param {string}                     [attributes.id=replace-image]        Unique identifier.
 * @param {string}                     [attributes.title=Replace Image]     Title for the state. Displays in the media menu and the frame's title region.
 * @param {wp.media.model.Attachments} [attributes.library]                 The attachments collection to browse.
 *                                                                          If one is not supplied, a collection of all images will be created.
 * @param {boolean}                    [attributes.multiple=false]          Whether multi-select is enabled.
 * @param {string}                     [attributes.content=upload]          Initial mode for the content region.
 *                                                                          Overridden by persistent user setting if 'contentUserSetting' is true.
 * @param {string}                     [attributes.menu=default]            Initial mode for the menu region.
 * @param {string}                     [attributes.router=browse]           Initial mode for the router region.
 * @param {string}                     [attributes.toolbar=replace]         Initial mode for the toolbar region.
 * @param {int}                        [attributes.priority=60]             The priority for the state link in the media menu.
 * @param {boolean}                    [attributes.searchable=true]         Whether the library is searchable.
 * @param {boolean|string}             [attributes.filterable=uploaded]     Whether the library is filterable, and if so what filters should be shown.
 *                                                                          Accepts 'all', 'uploaded', or 'unattached'.
 * @param {boolean}                    [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
 * @param {boolean}                    [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
 * @param {boolean}                    [attributes.describe=false]          Whether to offer UI to describe attachments - e.g. captioning images in a gallery.
 * @param {boolean}                    [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
 * @param {boolean}                    [attributes.syncSelection=true]      Whether the Attachments selection should be persisted from the last state.
 */
var Library = wp.media.controller.Library,
	l10n = wp.media.view.l10n,
	ReplaceImage;

ReplaceImage = Library.extend({
	defaults: _.defaults({
		id:            'replace-image',
		title:         l10n.replaceImageTitle,
		multiple:      false,
		filterable:    'uploaded',
		toolbar:       'replace',
		menu:          false,
		priority:      60,
		syncSelection: true
	}, Library.prototype.defaults ),

	/**
	 * @since 3.9.0
	 *
	 * @param options
	 */
	initialize: function( options ) {
		var library, comparator;

		this.image = options.image;
		// If we haven't been provided a `library`, create a `Selection`.
		if ( ! this.get('library') ) {
			this.set( 'library', wp.media.query({ type: 'image' }) );
		}

		Library.prototype.initialize.apply( this, arguments );

		library    = this.get('library');
		comparator = library.comparator;

		// Overload the library's comparator to push items that are not in
		// the mirrored query to the front of the aggregate collection.
		library.comparator = function( a, b ) {
			var aInQuery = !! this.mirroring.get( a.cid ),
				bInQuery = !! this.mirroring.get( b.cid );

			if ( ! aInQuery && bInQuery ) {
				return -1;
			} else if ( aInQuery && ! bInQuery ) {
				return 1;
			} else {
				return comparator.apply( this, arguments );
			}
		};

		// Add all items in the selection to the library, so any featured
		// images that are not initially loaded still appear.
		library.observe( this.get('selection') );
	},

	/**
	 * @since 3.9.0
	 */
	activate: function() {
		this.updateSelection();
		Library.prototype.activate.apply( this, arguments );
	},

	/**
	 * @since 3.9.0
	 */
	updateSelection: function() {
		var selection = this.get('selection'),
			attachment = this.image.attachment;

		selection.reset( attachment ? [ attachment ] : [] );
	}
});

module.exports = ReplaceImage;

},{}],15:[function(require,module,exports){
/**
 * wp.media.controller.SiteIconCropper
 *
 * A state for cropping a Site Icon.
 *
 * @class
 * @augments wp.media.controller.Cropper
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 */
var Controller = wp.media.controller,
	SiteIconCropper;

SiteIconCropper = Controller.Cropper.extend({
	activate: function() {
		this.frame.on( 'content:create:crop', this.createCropContent, this );
		this.frame.on( 'close', this.removeCropper, this );
		this.set('selection', new Backbone.Collection(this.frame._selection.single));
	},

	createCropContent: function() {
		this.cropperView = new wp.media.view.SiteIconCropper({
			controller: this,
			attachment: this.get('selection').first()
		});
		this.cropperView.on('image-loaded', this.createCropToolbar, this);
		this.frame.content.set(this.cropperView);

	},

	doCrop: function( attachment ) {
		var cropDetails = attachment.get( 'cropDetails' ),
			control = this.get( 'control' );

		cropDetails.dst_width  = control.params.width;
		cropDetails.dst_height = control.params.height;

		return wp.ajax.post( 'crop-image', {
			nonce: attachment.get( 'nonces' ).edit,
			id: attachment.get( 'id' ),
			context: 'site-icon',
			cropDetails: cropDetails
		} );
	}
});

module.exports = SiteIconCropper;

},{}],16:[function(require,module,exports){
/**
 * wp.media.controller.StateMachine
 *
 * A state machine keeps track of state. It is in one state at a time,
 * and can change from one state to another.
 *
 * States are stored as models in a Backbone collection.
 *
 * @since 3.5.0
 *
 * @class
 * @augments Backbone.Model
 * @mixin
 * @mixes Backbone.Events
 *
 * @param {Array} states
 */
var StateMachine = function( states ) {
	// @todo This is dead code. The states collection gets created in media.view.Frame._createStates.
	this.states = new Backbone.Collection( states );
};

// Use Backbone's self-propagating `extend` inheritance method.
StateMachine.extend = Backbone.Model.extend;

_.extend( StateMachine.prototype, Backbone.Events, {
	/**
	 * Fetch a state.
	 *
	 * If no `id` is provided, returns the active state.
	 *
	 * Implicitly creates states.
	 *
	 * Ensure that the `states` collection exists so the `StateMachine`
	 *   can be used as a mixin.
	 *
	 * @since 3.5.0
	 *
	 * @param {string} id
	 * @returns {wp.media.controller.State} Returns a State model
	 *   from the StateMachine collection
	 */
	state: function( id ) {
		this.states = this.states || new Backbone.Collection();

		// Default to the active state.
		id = id || this._state;

		if ( id && ! this.states.get( id ) ) {
			this.states.add({ id: id });
		}
		return this.states.get( id );
	},

	/**
	 * Sets the active state.
	 *
	 * Bail if we're trying to select the current state, if we haven't
	 * created the `states` collection, or are trying to select a state
	 * that does not exist.
	 *
	 * @since 3.5.0
	 *
	 * @param {string} id
	 *
	 * @fires wp.media.controller.State#deactivate
	 * @fires wp.media.controller.State#activate
	 *
	 * @returns {wp.media.controller.StateMachine} Returns itself to allow chaining
	 */
	setState: function( id ) {
		var previous = this.state();

		if ( ( previous && id === previous.id ) || ! this.states || ! this.states.get( id ) ) {
			return this;
		}

		if ( previous ) {
			previous.trigger('deactivate');
			this._lastState = previous.id;
		}

		this._state = id;
		this.state().trigger('activate');

		return this;
	},

	/**
	 * Returns the previous active state.
	 *
	 * Call the `state()` method with no parameters to retrieve the current
	 * active state.
	 *
	 * @since 3.5.0
	 *
	 * @returns {wp.media.controller.State} Returns a State model
	 *    from the StateMachine collection
	 */
	lastState: function() {
		if ( this._lastState ) {
			return this.state( this._lastState );
		}
	}
});

// Map all event binding and triggering on a StateMachine to its `states` collection.
_.each([ 'on', 'off', 'trigger' ], function( method ) {
	/**
	 * @returns {wp.media.controller.StateMachine} Returns itself to allow chaining.
	 */
	StateMachine.prototype[ method ] = function() {
		// Ensure that the `states` collection exists so the `StateMachine`
		// can be used as a mixin.
		this.states = this.states || new Backbone.Collection();
		// Forward the method to the `states` collection.
		this.states[ method ].apply( this.states, arguments );
		return this;
	};
});

module.exports = StateMachine;

},{}],17:[function(require,module,exports){
/**
 * wp.media.controller.State
 *
 * A state is a step in a workflow that when set will trigger the controllers
 * for the regions to be updated as specified in the frame.
 *
 * A state has an event-driven lifecycle:
 *
 *     'ready'      triggers when a state is added to a state machine's collection.
 *     'activate'   triggers when a state is activated by a state machine.
 *     'deactivate' triggers when a state is deactivated by a state machine.
 *     'reset'      is not triggered automatically. It should be invoked by the
 *                  proper controller to reset the state to its default.
 *
 * @class
 * @augments Backbone.Model
 */
var State = Backbone.Model.extend({
	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 */
	constructor: function() {
		this.on( 'activate', this._preActivate, this );
		this.on( 'activate', this.activate, this );
		this.on( 'activate', this._postActivate, this );
		this.on( 'deactivate', this._deactivate, this );
		this.on( 'deactivate', this.deactivate, this );
		this.on( 'reset', this.reset, this );
		this.on( 'ready', this._ready, this );
		this.on( 'ready', this.ready, this );
		/**
		 * Call parent constructor with passed arguments
		 */
		Backbone.Model.apply( this, arguments );
		this.on( 'change:menu', this._updateMenu, this );
	},
	/**
	 * Ready event callback.
	 *
	 * @abstract
	 * @since 3.5.0
	 */
	ready: function() {},

	/**
	 * Activate event callback.
	 *
	 * @abstract
	 * @since 3.5.0
	 */
	activate: function() {},

	/**
	 * Deactivate event callback.
	 *
	 * @abstract
	 * @since 3.5.0
	 */
	deactivate: function() {},

	/**
	 * Reset event callback.
	 *
	 * @abstract
	 * @since 3.5.0
	 */
	reset: function() {},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_ready: function() {
		this._updateMenu();
	},

	/**
	 * @access private
	 * @since 3.5.0
	*/
	_preActivate: function() {
		this.active = true;
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_postActivate: function() {
		this.on( 'change:menu', this._menu, this );
		this.on( 'change:titleMode', this._title, this );
		this.on( 'change:content', this._content, this );
		this.on( 'change:toolbar', this._toolbar, this );

		this.frame.on( 'title:render:default', this._renderTitle, this );

		this._title();
		this._menu();
		this._toolbar();
		this._content();
		this._router();
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_deactivate: function() {
		this.active = false;

		this.frame.off( 'title:render:default', this._renderTitle, this );

		this.off( 'change:menu', this._menu, this );
		this.off( 'change:titleMode', this._title, this );
		this.off( 'change:content', this._content, this );
		this.off( 'change:toolbar', this._toolbar, this );
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_title: function() {
		this.frame.title.render( this.get('titleMode') || 'default' );
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_renderTitle: function( view ) {
		view.$el.text( this.get('title') || '' );
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_router: function() {
		var router = this.frame.router,
			mode = this.get('router'),
			view;

		this.frame.$el.toggleClass( 'hide-router', ! mode );
		if ( ! mode ) {
			return;
		}

		this.frame.router.render( mode );

		view = router.get();
		if ( view && view.select ) {
			view.select( this.frame.content.mode() );
		}
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_menu: function() {
		var menu = this.frame.menu,
			mode = this.get('menu'),
			view;

		this.frame.$el.toggleClass( 'hide-menu', ! mode );
		if ( ! mode ) {
			return;
		}

		menu.mode( mode );

		view = menu.get();
		if ( view && view.select ) {
			view.select( this.id );
		}
	},

	/**
	 * @access private
	 * @since 3.5.0
	 */
	_updateMenu: function() {
		var previous = this.previous('menu'),
			menu = this.get('menu');

		if ( previous ) {
			this.frame.off( 'menu:render:' + previous, this._renderMenu, this );
		}

		if ( menu ) {
			this.frame.on( 'menu:render:' + menu, this._renderMenu, this );
		}
	},

	/**
	 * Create a view in the media menu for the state.
	 *
	 * @access private
	 * @since 3.5.0
	 *
	 * @param {media.view.Menu} view The menu view.
	 */
	_renderMenu: function( view ) {
		var menuItem = this.get('menuItem'),
			title = this.get('title'),
			priority = this.get('priority');

		if ( ! menuItem && title ) {
			menuItem = { text: title };

			if ( priority ) {
				menuItem.priority = priority;
			}
		}

		if ( ! menuItem ) {
			return;
		}

		view.set( this.id, menuItem );
	}
});

_.each(['toolbar','content'], function( region ) {
	/**
	 * @access private
	 */
	State.prototype[ '_' + region ] = function() {
		var mode = this.get( region );
		if ( mode ) {
			this.frame[ region ].render( mode );
		}
	};
});

module.exports = State;

},{}],18:[function(require,module,exports){
/**
 * wp.media.selectionSync
 *
 * Sync an attachments selection in a state with another state.
 *
 * Allows for selecting multiple images in the Insert Media workflow, and then
 * switching to the Insert Gallery workflow while preserving the attachments selection.
 *
 * @mixin
 */
var selectionSync = {
	/**
	 * @since 3.5.0
	 */
	syncSelection: function() {
		var selection = this.get('selection'),
			manager = this.frame._selection;

		if ( ! this.get('syncSelection') || ! manager || ! selection ) {
			return;
		}

		// If the selection supports multiple items, validate the stored
		// attachments based on the new selection's conditions. Record
		// the attachments that are not included; we'll maintain a
		// reference to those. Other attachments are considered in flux.
		if ( selection.multiple ) {
			selection.reset( [], { silent: true });
			selection.validateAll( manager.attachments );
			manager.difference = _.difference( manager.attachments.models, selection.models );
		}

		// Sync the selection's single item with the master.
		selection.single( manager.single );
	},

	/**
	 * Record the currently active attachments, which is a combination
	 * of the selection's attachments and the set of selected
	 * attachments that this specific selection considered invalid.
	 * Reset the difference and record the single attachment.
	 *
	 * @since 3.5.0
	 */
	recordSelection: function() {
		var selection = this.get('selection'),
			manager = this.frame._selection;

		if ( ! this.get('syncSelection') || ! manager || ! selection ) {
			return;
		}

		if ( selection.multiple ) {
			manager.attachments.reset( selection.toArray().concat( manager.difference ) );
			manager.difference = [];
		} else {
			manager.attachments.add( selection.toArray() );
		}

		manager.single = selection._single;
	}
};

module.exports = selectionSync;

},{}],19:[function(require,module,exports){
var media = wp.media,
	$ = jQuery,
	l10n;

media.isTouchDevice = ( 'ontouchend' in document );

// Link any localized strings.
l10n = media.view.l10n = window._wpMediaViewsL10n || {};

// Link any settings.
media.view.settings = l10n.settings || {};
delete l10n.settings;

// Copy the `post` setting over to the model settings.
media.model.settings.post = media.view.settings.post;

// Check if the browser supports CSS 3.0 transitions
$.support.transition = (function(){
	var style = document.documentElement.style,
		transitions = {
			WebkitTransition: 'webkitTransitionEnd',
			MozTransition:    'transitionend',
			OTransition:      'oTransitionEnd otransitionend',
			transition:       'transitionend'
		}, transition;

	transition = _.find( _.keys( transitions ), function( transition ) {
		return ! _.isUndefined( style[ transition ] );
	});

	return transition && {
		end: transitions[ transition ]
	};
}());

/**
 * A shared event bus used to provide events into
 * the media workflows that 3rd-party devs can use to hook
 * in.
 */
media.events = _.extend( {}, Backbone.Events );

/**
 * Makes it easier to bind events using transitions.
 *
 * @param {string} selector
 * @param {Number} sensitivity
 * @returns {Promise}
 */
media.transition = function( selector, sensitivity ) {
	var deferred = $.Deferred();

	sensitivity = sensitivity || 2000;

	if ( $.support.transition ) {
		if ( ! (selector instanceof $) ) {
			selector = $( selector );
		}

		// Resolve the deferred when the first element finishes animating.
		selector.first().one( $.support.transition.end, deferred.resolve );

		// Just in case the event doesn't trigger, fire a callback.
		_.delay( deferred.resolve, sensitivity );

	// Otherwise, execute on the spot.
	} else {
		deferred.resolve();
	}

	return deferred.promise();
};

media.controller.Region = require( './controllers/region.js' );
media.controller.StateMachine = require( './controllers/state-machine.js' );
media.controller.State = require( './controllers/state.js' );

media.selectionSync = require( './utils/selection-sync.js' );
media.controller.Library = require( './controllers/library.js' );
media.controller.ImageDetails = require( './controllers/image-details.js' );
media.controller.GalleryEdit = require( './controllers/gallery-edit.js' );
media.controller.GalleryAdd = require( './controllers/gallery-add.js' );
media.controller.CollectionEdit = require( './controllers/collection-edit.js' );
media.controller.CollectionAdd = require( './controllers/collection-add.js' );
media.controller.FeaturedImage = require( './controllers/featured-image.js' );
media.controller.ReplaceImage = require( './controllers/replace-image.js' );
media.controller.EditImage = require( './controllers/edit-image.js' );
media.controller.MediaLibrary = require( './controllers/media-library.js' );
media.controller.Embed = require( './controllers/embed.js' );
media.controller.Cropper = require( './controllers/cropper.js' );
media.controller.CustomizeImageCropper = require( './controllers/customize-image-cropper.js' );
media.controller.SiteIconCropper = require( './controllers/site-icon-cropper.js' );

media.View = require( './views/view.js' );
media.view.Frame = require( './views/frame.js' );
media.view.MediaFrame = require( './views/media-frame.js' );
media.view.MediaFrame.Select = require( './views/frame/select.js' );
media.view.MediaFrame.Post = require( './views/frame/post.js' );
media.view.MediaFrame.ImageDetails = require( './views/frame/image-details.js' );
media.view.Modal = require( './views/modal.js' );
media.view.FocusManager = require( './views/focus-manager.js' );
media.view.UploaderWindow = require( './views/uploader/window.js' );
media.view.EditorUploader = require( './views/uploader/editor.js' );
media.view.UploaderInline = require( './views/uploader/inline.js' );
media.view.UploaderStatus = require( './views/uploader/status.js' );
media.view.UploaderStatusError = require( './views/uploader/status-error.js' );
media.view.Toolbar = require( './views/toolbar.js' );
media.view.Toolbar.Select = require( './views/toolbar/select.js' );
media.view.Toolbar.Embed = require( './views/toolbar/embed.js' );
media.view.Button = require( './views/button.js' );
media.view.ButtonGroup = require( './views/button-group.js' );
media.view.PriorityList = require( './views/priority-list.js' );
media.view.MenuItem = require( './views/menu-item.js' );
media.view.Menu = require( './views/menu.js' );
media.view.RouterItem = require( './views/router-item.js' );
media.view.Router = require( './views/router.js' );
media.view.Sidebar = require( './views/sidebar.js' );
media.view.Attachment = require( './views/attachment.js' );
media.view.Attachment.Library = require( './views/attachment/library.js' );
media.view.Attachment.EditLibrary = require( './views/attachment/edit-library.js' );
media.view.Attachments = require( './views/attachments.js' );
media.view.Search = require( './views/search.js' );
media.view.AttachmentFilters = require( './views/attachment-filters.js' );
media.view.DateFilter = require( './views/attachment-filters/date.js' );
media.view.AttachmentFilters.Uploaded = require( './views/attachment-filters/uploaded.js' );
media.view.AttachmentFilters.All = require( './views/attachment-filters/all.js' );
media.view.AttachmentsBrowser = require( './views/attachments/browser.js' );
media.view.Selection = require( './views/selection.js' );
media.view.Attachment.Selection = require( './views/attachment/selection.js' );
media.view.Attachments.Selection = require( './views/attachments/selection.js' );
media.view.Attachment.EditSelection = require( './views/attachment/edit-selection.js' );
media.view.Settings = require( './views/settings.js' );
media.view.Settings.AttachmentDisplay = require( './views/settings/attachment-display.js' );
media.view.Settings.Gallery = require( './views/settings/gallery.js' );
media.view.Settings.Playlist = require( './views/settings/playlist.js' );
media.view.Attachment.Details = require( './views/attachment/details.js' );
media.view.AttachmentCompat = require( './views/attachment-compat.js' );
media.view.Iframe = require( './views/iframe.js' );
media.view.Embed = require( './views/embed.js' );
media.view.Label = require( './views/label.js' );
media.view.EmbedUrl = require( './views/embed/url.js' );
media.view.EmbedLink = require( './views/embed/link.js' );
media.view.EmbedImage = require( './views/embed/image.js' );
media.view.ImageDetails = require( './views/image-details.js' );
media.view.Cropper = require( './views/cropper.js' );
media.view.SiteIconCropper = require( './views/site-icon-cropper.js' );
media.view.SiteIconPreview = require( './views/site-icon-preview.js' );
media.view.EditImage = require( './views/edit-image.js' );
media.view.Spinner = require( './views/spinner.js' );

},{"./controllers/collection-add.js":1,"./controllers/collection-edit.js":2,"./controllers/cropper.js":3,"./controllers/customize-image-cropper.js":4,"./controllers/edit-image.js":5,"./controllers/embed.js":6,"./controllers/featured-image.js":7,"./controllers/gallery-add.js":8,"./controllers/gallery-edit.js":9,"./controllers/image-details.js":10,"./controllers/library.js":11,"./controllers/media-library.js":12,"./controllers/region.js":13,"./controllers/replace-image.js":14,"./controllers/site-icon-cropper.js":15,"./controllers/state-machine.js":16,"./controllers/state.js":17,"./utils/selection-sync.js":18,"./views/attachment-compat.js":20,"./views/attachment-filters.js":21,"./views/attachment-filters/all.js":22,"./views/attachment-filters/date.js":23,"./views/attachment-filters/uploaded.js":24,"./views/attachment.js":25,"./views/attachment/details.js":26,"./views/attachment/edit-library.js":27,"./views/attachment/edit-selection.js":28,"./views/attachment/library.js":29,"./views/attachment/selection.js":30,"./views/attachments.js":31,"./views/attachments/browser.js":32,"./views/attachments/selection.js":33,"./views/button-group.js":34,"./views/button.js":35,"./views/cropper.js":36,"./views/edit-image.js":37,"./views/embed.js":38,"./views/embed/image.js":39,"./views/embed/link.js":40,"./views/embed/url.js":41,"./views/focus-manager.js":42,"./views/frame.js":43,"./views/frame/image-details.js":44,"./views/frame/post.js":45,"./views/frame/select.js":46,"./views/iframe.js":47,"./views/image-details.js":48,"./views/label.js":49,"./views/media-frame.js":50,"./views/menu-item.js":51,"./views/menu.js":52,"./views/modal.js":53,"./views/priority-list.js":54,"./views/router-item.js":55,"./views/router.js":56,"./views/search.js":57,"./views/selection.js":58,"./views/settings.js":59,"./views/settings/attachment-display.js":60,"./views/settings/gallery.js":61,"./views/settings/playlist.js":62,"./views/sidebar.js":63,"./views/site-icon-cropper.js":64,"./views/site-icon-preview.js":65,"./views/spinner.js":66,"./views/toolbar.js":67,"./views/toolbar/embed.js":68,"./views/toolbar/select.js":69,"./views/uploader/editor.js":70,"./views/uploader/inline.js":71,"./views/uploader/status-error.js":72,"./views/uploader/status.js":73,"./views/uploader/window.js":74,"./views/view.js":75}],20:[function(require,module,exports){
/**
 * wp.media.view.AttachmentCompat
 *
 * A view to display fields added via the `attachment_fields_to_edit` filter.
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var View = wp.media.View,
	AttachmentCompat;

AttachmentCompat = View.extend({
	tagName:   'form',
	className: 'compat-item',

	events: {
		'submit':          'preventDefault',
		'change input':    'save',
		'change select':   'save',
		'change textarea': 'save'
	},

	initialize: function() {
		this.listenTo( this.model, 'change:compat', this.render );
	},
	/**
	 * @returns {wp.media.view.AttachmentCompat} Returns itself to allow chaining
	 */
	dispose: function() {
		if ( this.$(':focus').length ) {
			this.save();
		}
		/**
		 * call 'dispose' directly on the parent class
		 */
		return View.prototype.dispose.apply( this, arguments );
	},
	/**
	 * @returns {wp.media.view.AttachmentCompat} Returns itself to allow chaining
	 */
	render: function() {
		var compat = this.model.get('compat');
		if ( ! compat || ! compat.item ) {
			return;
		}

		this.views.detach();
		this.$el.html( compat.item );
		this.views.render();
		return this;
	},
	/**
	 * @param {Object} event
	 */
	preventDefault: function( event ) {
		event.preventDefault();
	},
	/**
	 * @param {Object} event
	 */
	save: function( event ) {
		var data = {};

		if ( event ) {
			event.preventDefault();
		}

		_.each( this.$el.serializeArray(), function( pair ) {
			data[ pair.name ] = pair.value;
		});

		this.controller.trigger( 'attachment:compat:waiting', ['waiting'] );
		this.model.saveCompat( data ).always( _.bind( this.postSave, this ) );
	},

	postSave: function() {
		this.controller.trigger( 'attachment:compat:ready', ['ready'] );
	}
});

module.exports = AttachmentCompat;

},{}],21:[function(require,module,exports){
/**
 * wp.media.view.AttachmentFilters
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var $ = jQuery,
	AttachmentFilters;

AttachmentFilters = wp.media.View.extend({
	tagName:   'select',
	className: 'attachment-filters',
	id:        'media-attachment-filters',

	events: {
		change: 'change'
	},

	keys: [],

	initialize: function() {
		this.createFilters();
		_.extend( this.filters, this.options.filters );

		// Build `<option>` elements.
		this.$el.html( _.chain( this.filters ).map( function( filter, value ) {
			return {
				el: $( '<option></option>' ).val( value ).html( filter.text )[0],
				priority: filter.priority || 50
			};
		}, this ).sortBy('priority').pluck('el').value() );

		this.listenTo( this.model, 'change', this.select );
		this.select();
	},

	/**
	 * @abstract
	 */
	createFilters: function() {
		this.filters = {};
	},

	/**
	 * When the selected filter changes, update the Attachment Query properties to match.
	 */
	change: function() {
		var filter = this.filters[ this.el.value ];
		if ( filter ) {
			this.model.set( filter.props );
		}
	},

	select: function() {
		var model = this.model,
			value = 'all',
			props = model.toJSON();

		_.find( this.filters, function( filter, id ) {
			var equal = _.all( filter.props, function( prop, key ) {
				return prop === ( _.isUndefined( props[ key ] ) ? null : props[ key ] );
			});

			if ( equal ) {
				return value = id;
			}
		});

		this.$el.val( value );
	}
});

module.exports = AttachmentFilters;

},{}],22:[function(require,module,exports){
/**
 * wp.media.view.AttachmentFilters.All
 *
 * @class
 * @augments wp.media.view.AttachmentFilters
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var l10n = wp.media.view.l10n,
	All;

All = wp.media.view.AttachmentFilters.extend({
	createFilters: function() {
		var filters = {};

		_.each( wp.media.view.settings.mimeTypes || {}, function( text, key ) {
			filters[ key ] = {
				text: text,
				props: {
					status:  null,
					type:    key,
					uploadedTo: null,
					orderby: 'date',
					order:   'DESC'
				}
			};
		});

		filters.all = {
			text:  l10n.allMediaItems,
			props: {
				status:  null,
				type:    null,
				uploadedTo: null,
				orderby: 'date',
				order:   'DESC'
			},
			priority: 10
		};

		if ( wp.media.view.settings.post.id ) {
			filters.uploaded = {
				text:  l10n.uploadedToThisPost,
				props: {
					status:  null,
					type:    null,
					uploadedTo: wp.media.view.settings.post.id,
					orderby: 'menuOrder',
					order:   'ASC'
				},
				priority: 20
			};
		}

		filters.unattached = {
			text:  l10n.unattached,
			props: {
				status:     null,
				uploadedTo: 0,
				type:       null,
				orderby:    'menuOrder',
				order:      'ASC'
			},
			priority: 50
		};

		if ( wp.media.view.settings.mediaTrash &&
			this.controller.isModeActive( 'grid' ) ) {

			filters.trash = {
				text:  l10n.trash,
				props: {
					uploadedTo: null,
					status:     'trash',
					type:       null,
					orderby:    'date',
					order:      'DESC'
				},
				priority: 50
			};
		}

		this.filters = filters;
	}
});

module.exports = All;

},{}],23:[function(require,module,exports){
/**
 * A filter dropdown for month/dates.
 *
 * @class
 * @augments wp.media.view.AttachmentFilters
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var l10n = wp.media.view.l10n,
	DateFilter;

DateFilter = wp.media.view.AttachmentFilters.extend({
	id: 'media-attachment-date-filters',

	createFilters: function() {
		var filters = {};
		_.each( wp.media.view.settings.months || {}, function( value, index ) {
			filters[ index ] = {
				text: value.text,
				props: {
					year: value.year,
					monthnum: value.month
				}
			};
		});
		filters.all = {
			text:  l10n.allDates,
			props: {
				monthnum: false,
				year:  false
			},
			priority: 10
		};
		this.filters = filters;
	}
});

module.exports = DateFilter;

},{}],24:[function(require,module,exports){
/**
 * wp.media.view.AttachmentFilters.Uploaded
 *
 * @class
 * @augments wp.media.view.AttachmentFilters
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var l10n = wp.media.view.l10n,
	Uploaded;

Uploaded = wp.media.view.AttachmentFilters.extend({
	createFilters: function() {
		var type = this.model.get('type'),
			types = wp.media.view.settings.mimeTypes,
			text;

		if ( types && type ) {
			text = types[ type ];
		}

		this.filters = {
			all: {
				text:  text || l10n.allMediaItems,
				props: {
					uploadedTo: null,
					orderby: 'date',
					order:   'DESC'
				},
				priority: 10
			},

			uploaded: {
				text:  l10n.uploadedToThisPost,
				props: {
					uploadedTo: wp.media.view.settings.post.id,
					orderby: 'menuOrder',
					order:   'ASC'
				},
				priority: 20
			},

			unattached: {
				text:  l10n.unattached,
				props: {
					uploadedTo: 0,
					orderby: 'menuOrder',
					order:   'ASC'
				},
				priority: 50
			}
		};
	}
});

module.exports = Uploaded;

},{}],25:[function(require,module,exports){
/**
 * wp.media.view.Attachment
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var View = wp.media.View,
	$ = jQuery,
	Attachment;

Attachment = View.extend({
	tagName:   'li',
	className: 'attachment',
	template:  wp.template('attachment'),

	attributes: function() {
		return {
			'tabIndex':     0,
			'role':         'checkbox',
			'aria-label':   this.model.get( 'title' ),
			'aria-checked': false,
			'data-id':      this.model.get( 'id' )
		};
	},

	events: {
		'click .js--select-attachment':   'toggleSelectionHandler',
		'change [data-setting]':          'updateSetting',
		'change [data-setting] input':    'updateSetting',
		'change [data-setting] select':   'updateSetting',
		'change [data-setting] textarea': 'updateSetting',
		'click .attachment-close':        'removeFromLibrary',
		'click .check':                   'checkClickHandler',
		'keydown':                        'toggleSelectionHandler'
	},

	buttons: {},

	initialize: function() {
		var selection = this.options.selection,
			options = _.defaults( this.options, {
				rerenderOnModelChange: true
			} );

		if ( options.rerenderOnModelChange ) {
			this.listenTo( this.model, 'change', this.render );
		} else {
			this.listenTo( this.model, 'change:percent', this.progress );
		}
		this.listenTo( this.model, 'change:title', this._syncTitle );
		this.listenTo( this.model, 'change:caption', this._syncCaption );
		this.listenTo( this.model, 'change:artist', this._syncArtist );
		this.listenTo( this.model, 'change:album', this._syncAlbum );

		// Update the selection.
		this.listenTo( this.model, 'add', this.select );
		this.listenTo( this.model, 'remove', this.deselect );
		if ( selection ) {
			selection.on( 'reset', this.updateSelect, this );
			// Update the model's details view.
			this.listenTo( this.model, 'selection:single selection:unsingle', this.details );
			this.details( this.model, this.controller.state().get('selection') );
		}

		this.listenTo( this.controller, 'attachment:compat:waiting attachment:compat:ready', this.updateSave );
	},
	/**
	 * @returns {wp.media.view.Attachment} Returns itself to allow chaining
	 */
	dispose: function() {
		var selection = this.options.selection;

		// Make sure all settings are saved before removing the view.
		this.updateAll();

		if ( selection ) {
			selection.off( null, null, this );
		}
		/**
		 * call 'dispose' directly on the parent class
		 */
		View.prototype.dispose.apply( this, arguments );
		return this;
	},
	/**
	 * @returns {wp.media.view.Attachment} Returns itself to allow chaining
	 */
	render: function() {
		var options = _.defaults( this.model.toJSON(), {
				orientation:   'landscape',
				uploading:     false,
				type:          '',
				subtype:       '',
				icon:          '',
				filename:      '',
				caption:       '',
				title:         '',
				dateFormatted: '',
				width:         '',
				height:        '',
				compat:        false,
				alt:           '',
				description:   ''
			}, this.options );

		options.buttons  = this.buttons;
		options.describe = this.controller.state().get('describe');

		if ( 'image' === options.type ) {
			options.size = this.imageSize();
		}

		options.can = {};
		if ( options.nonces ) {
			options.can.remove = !! options.nonces['delete'];
			options.can.save = !! options.nonces.update;
		}

		if ( this.controller.state().get('allowLocalEdits') ) {
			options.allowLocalEdits = true;
		}

		if ( options.uploading && ! options.percent ) {
			options.percent = 0;
		}

		this.views.detach();
		this.$el.html( this.template( options ) );

		this.$el.toggleClass( 'uploading', options.uploading );

		if ( options.uploading ) {
			this.$bar = this.$('.media-progress-bar div');
		} else {
			delete this.$bar;
		}

		// Check if the model is selected.
		this.updateSelect();

		// Update the save status.
		this.updateSave();

		this.views.render();

		return this;
	},

	progress: function() {
		if ( this.$bar && this.$bar.length ) {
			this.$bar.width( this.model.get('percent') + '%' );
		}
	},

	/**
	 * @param {Object} event
	 */
	toggleSelectionHandler: function( event ) {
		var method;

		// Don't do anything inside inputs and on the attachment check and remove buttons.
		if ( 'INPUT' === event.target.nodeName || 'BUTTON' === event.target.nodeName ) {
			return;
		}

		// Catch arrow events
		if ( 37 === event.keyCode || 38 === event.keyCode || 39 === event.keyCode || 40 === event.keyCode ) {
			this.controller.trigger( 'attachment:keydown:arrow', event );
			return;
		}

		// Catch enter and space events
		if ( 'keydown' === event.type && 13 !== event.keyCode && 32 !== event.keyCode ) {
			return;
		}

		event.preventDefault();

		// In the grid view, bubble up an edit:attachment event to the controller.
		if ( this.controller.isModeActive( 'grid' ) ) {
			if ( this.controller.isModeActive( 'edit' ) ) {
				// Pass the current target to restore focus when closing
				this.controller.trigger( 'edit:attachment', this.model, event.currentTarget );
				return;
			}

			if ( this.controller.isModeActive( 'select' ) ) {
				method = 'toggle';
			}
		}

		if ( event.shiftKey ) {
			method = 'between';
		} else if ( event.ctrlKey || event.metaKey ) {
			method = 'toggle';
		}

		this.toggleSelection({
			method: method
		});

		this.controller.trigger( 'selection:toggle' );
	},
	/**
	 * @param {Object} options
	 */
	toggleSelection: function( options ) {
		var collection = this.collection,
			selection = this.options.selection,
			model = this.model,
			method = options && options.method,
			single, models, singleIndex, modelIndex;

		if ( ! selection ) {
			return;
		}

		single = selection.single();
		method = _.isUndefined( method ) ? selection.multiple : method;

		// If the `method` is set to `between`, select all models that
		// exist between the current and the selected model.
		if ( 'between' === method && single && selection.multiple ) {
			// If the models are the same, short-circuit.
			if ( single === model ) {
				return;
			}

			singleIndex = collection.indexOf( single );
			modelIndex  = collection.indexOf( this.model );

			if ( singleIndex < modelIndex ) {
				models = collection.models.slice( singleIndex, modelIndex + 1 );
			} else {
				models = collection.models.slice( modelIndex, singleIndex + 1 );
			}

			selection.add( models );
			selection.single( model );
			return;

		// If the `method` is set to `toggle`, just flip the selection
		// status, regardless of whether the model is the single model.
		} else if ( 'toggle' === method ) {
			selection[ this.selected() ? 'remove' : 'add' ]( model );
			selection.single( model );
			return;
		} else if ( 'add' === method ) {
			selection.add( model );
			selection.single( model );
			return;
		}

		// Fixes bug that loses focus when selecting a featured image
		if ( ! method ) {
			method = 'add';
		}

		if ( method !== 'add' ) {
			method = 'reset';
		}

		if ( this.selected() ) {
			// If the model is the single model, remove it.
			// If it is not the same as the single model,
			// it now becomes the single model.
			selection[ single === model ? 'remove' : 'single' ]( model );
		} else {
			// If the model is not selected, run the `method` on the
			// selection. By default, we `reset` the selection, but the
			// `method` can be set to `add` the model to the selection.
			selection[ method ]( model );
			selection.single( model );
		}
	},

	updateSelect: function() {
		this[ this.selected() ? 'select' : 'deselect' ]();
	},
	/**
	 * @returns {unresolved|Boolean}
	 */
	selected: function() {
		var selection = this.options.selection;
		if ( selection ) {
			return !! selection.get( this.model.cid );
		}
	},
	/**
	 * @param {Backbone.Model} model
	 * @param {Backbone.Collection} collection
	 */
	select: function( model, collection ) {
		var selection = this.options.selection,
			controller = this.controller;

		// Check if a selection exists and if it's the collection provided.
		// If they're not the same collection, bail; we're in another
		// selection's event loop.
		if ( ! selection || ( collection && collection !== selection ) ) {
			return;
		}

		// Bail if the model is already selected.
		if ( this.$el.hasClass( 'selected' ) ) {
			return;
		}

		// Add 'selected' class to model, set aria-checked to true.
		this.$el.addClass( 'selected' ).attr( 'aria-checked', true );
		//  Make the checkbox tabable, except in media grid (bulk select mode).
		if ( ! ( controller.isModeActive( 'grid' ) && controller.isModeActive( 'select' ) ) ) {
			this.$( '.check' ).attr( 'tabindex', '0' );
		}
	},
	/**
	 * @param {Backbone.Model} model
	 * @param {Backbone.Collection} collection
	 */
	deselect: function( model, collection ) {
		var selection = this.options.selection;

		// Check if a selection exists and if it's the collection provided.
		// If they're not the same collection, bail; we're in another
		// selection's event loop.
		if ( ! selection || ( collection && collection !== selection ) ) {
			return;
		}
		this.$el.removeClass( 'selected' ).attr( 'aria-checked', false )
			.find( '.check' ).attr( 'tabindex', '-1' );
	},
	/**
	 * @param {Backbone.Model} model
	 * @param {Backbone.Collection} collection
	 */
	details: function( model, collection ) {
		var selection = this.options.selection,
			details;

		if ( selection !== collection ) {
			return;
		}

		details = selection.single();
		this.$el.toggleClass( 'details', details === this.model );
	},
	/**
	 * @param {string} size
	 * @returns {Object}
	 */
	imageSize: function( size ) {
		var sizes = this.model.get('sizes'), matched = false;

		size = size || 'medium';

		// Use the provided image size if possible.
		if ( sizes ) {
			if ( sizes[ size ] ) {
				matched = sizes[ size ];
			} else if ( sizes.large ) {
				matched = sizes.large;
			} else if ( sizes.thumbnail ) {
				matched = sizes.thumbnail;
			} else if ( sizes.full ) {
				matched = sizes.full;
			}

			if ( matched ) {
				return _.clone( matched );
			}
		}

		return {
			url:         this.model.get('url'),
			width:       this.model.get('width'),
			height:      this.model.get('height'),
			orientation: this.model.get('orientation')
		};
	},
	/**
	 * @param {Object} event
	 */
	updateSetting: function( event ) {
		var $setting = $( event.target ).closest('[data-setting]'),
			setting, value;

		if ( ! $setting.length ) {
			return;
		}

		setting = $setting.data('setting');
		value   = event.target.value;

		if ( this.model.get( setting ) !== value ) {
			this.save( setting, value );
		}
	},

	/**
	 * Pass all the arguments to the model's save method.
	 *
	 * Records the aggregate status of all save requests and updates the
	 * view's classes accordingly.
	 */
	save: function() {
		var view = this,
			save = this._save = this._save || { status: 'ready' },
			request = this.model.save.apply( this.model, arguments ),
			requests = save.requests ? $.when( request, save.requests ) : request;

		// If we're waiting to remove 'Saved.', stop.
		if ( save.savedTimer ) {
			clearTimeout( save.savedTimer );
		}

		this.updateSave('waiting');
		save.requests = requests;
		requests.always( function() {
			// If we've performed another request since this one, bail.
			if ( save.requests !== requests ) {
				return;
			}

			view.updateSave( requests.state() === 'resolved' ? 'complete' : 'error' );
			save.savedTimer = setTimeout( function() {
				view.updateSave('ready');
				delete save.savedTimer;
			}, 2000 );
		});
	},
	/**
	 * @param {string} status
	 * @returns {wp.media.view.Attachment} Returns itself to allow chaining
	 */
	updateSave: function( status ) {
		var save = this._save = this._save || { status: 'ready' };

		if ( status && status !== save.status ) {
			this.$el.removeClass( 'save-' + save.status );
			save.status = status;
		}

		this.$el.addClass( 'save-' + save.status );
		return this;
	},

	updateAll: function() {
		var $settings = this.$('[data-setting]'),
			model = this.model,
			changed;

		changed = _.chain( $settings ).map( function( el ) {
			var $input = $('input, textarea, select, [value]', el ),
				setting, value;

			if ( ! $input.length ) {
				return;
			}

			setting = $(el).data('setting');
			value = $input.val();

			// Record the value if it changed.
			if ( model.get( setting ) !== value ) {
				return [ setting, value ];
			}
		}).compact().object().value();

		if ( ! _.isEmpty( changed ) ) {
			model.save( changed );
		}
	},
	/**
	 * @param {Object} event
	 */
	removeFromLibrary: function( event ) {
		// Catch enter and space events
		if ( 'keydown' === event.type && 13 !== event.keyCode && 32 !== event.keyCode ) {
			return;
		}

		// Stop propagation so the model isn't selected.
		event.stopPropagation();

		this.collection.remove( this.model );
	},

	/**
	 * Add the model if it isn't in the selection, if it is in the selection,
	 * remove it.
	 *
	 * @param  {[type]} event [description]
	 * @return {[type]}       [description]
	 */
	checkClickHandler: function ( event ) {
		var selection = this.options.selection;
		if ( ! selection ) {
			return;
		}
		event.stopPropagation();
		if ( selection.where( { id: this.model.get( 'id' ) } ).length ) {
			selection.remove( this.model );
			// Move focus back to the attachment tile (from the check).
			this.$el.focus();
		} else {
			selection.add( this.model );
		}
	}
});

// Ensure settings remain in sync between attachment views.
_.each({
	caption: '_syncCaption',
	title:   '_syncTitle',
	artist:  '_syncArtist',
	album:   '_syncAlbum'
}, function( method, setting ) {
	/**
	 * @param {Backbone.Model} model
	 * @param {string} value
	 * @returns {wp.media.view.Attachment} Returns itself to allow chaining
	 */
	Attachment.prototype[ method ] = function( model, value ) {
		var $setting = this.$('[data-setting="' + setting + '"]');

		if ( ! $setting.length ) {
			return this;
		}

		// If the updated value is in sync with the value in the DOM, there
		// is no need to re-render. If we're currently editing the value,
		// it will automatically be in sync, suppressing the re-render for
		// the view we're editing, while updating any others.
		if ( value === $setting.find('input, textarea, select, [value]').val() ) {
			return this;
		}

		return this.render();
	};
});

module.exports = Attachment;

},{}],26:[function(require,module,exports){
/**
 * wp.media.view.Attachment.Details
 *
 * @class
 * @augments wp.media.view.Attachment
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var Attachment = wp.media.view.Attachment,
	l10n = wp.media.view.l10n,
	Details;

Details = Attachment.extend({
	tagName:   'div',
	className: 'attachment-details',
	template:  wp.template('attachment-details'),

	attributes: function() {
		return {
			'tabIndex':     0,
			'data-id':      this.model.get( 'id' )
		};
	},

	events: {
		'change [data-setting]':          'updateSetting',
		'change [data-setting] input':    'updateSetting',
		'change [data-setting] select':   'updateSetting',
		'change [data-setting] textarea': 'updateSetting',
		'click .delete-attachment':       'deleteAttachment',
		'click .trash-attachment':        'trashAttachment',
		'click .untrash-attachment':      'untrashAttachment',
		'click .edit-attachment':         'editAttachment',
		'keydown':                        'toggleSelectionHandler'
	},

	initialize: function() {
		this.options = _.defaults( this.options, {
			rerenderOnModelChange: false
		});

		this.on( 'ready', this.initialFocus );
		// Call 'initialize' directly on the parent class.
		Attachment.prototype.initialize.apply( this, arguments );
	},

	initialFocus: function() {
		if ( ! wp.media.isTouchDevice ) {
			/*
			Previously focused the first ':input' (the readonly URL text field).
			Since the first ':input' is now a button (delete/trash): when pressing
			spacebar on an attachment, Firefox fires deleteAttachment/trashAttachment
			as soon as focus is moved. Explicitly target the first text field for now.
			@todo change initial focus logic, also for accessibility.
			*/
			this.$( 'input[type="text"]' ).eq( 0 ).focus();
		}
	},
	/**
	 * @param {Object} event
	 */
	deleteAttachment: function( event ) {
		event.preventDefault();

		if ( window.confirm( l10n.warnDelete ) ) {
			this.model.destroy();
			// Keep focus inside media modal
			// after image is deleted
			this.controller.modal.focusManager.focus();
		}
	},
	/**
	 * @param {Object} event
	 */
	trashAttachment: function( event ) {
		var library = this.controller.library;
		event.preventDefault();

		if ( wp.media.view.settings.mediaTrash &&
			'edit-metadata' === this.controller.content.mode() ) {

			this.model.set( 'status', 'trash' );
			this.model.save().done( function() {
				library._requery( true );
			} );
		}  else {
			this.model.destroy();
		}
	},
	/**
	 * @param {Object} event
	 */
	untrashAttachment: function( event ) {
		var library = this.controller.library;
		event.preventDefault();

		this.model.set( 'status', 'inherit' );
		this.model.save().done( function() {
			library._requery( true );
		} );
	},
	/**
	 * @param {Object} event
	 */
	editAttachment: function( event ) {
		var editState = this.controller.states.get( 'edit-image' );
		if ( window.imageEdit && editState ) {
			event.preventDefault();

			editState.set( 'image', this.model );
			this.controller.setState( 'edit-image' );
		} else {
			this.$el.addClass('needs-refresh');
		}
	},
	/**
	 * When reverse tabbing(shift+tab) out of the right details panel, deliver
	 * the focus to the item in the list that was being edited.
	 *
	 * @param {Object} event
	 */
	toggleSelectionHandler: function( event ) {
		if ( 'keydown' === event.type && 9 === event.keyCode && event.shiftKey && event.target === this.$( ':tabbable' ).get( 0 ) ) {
			this.controller.trigger( 'attachment:details:shift-tab', event );
			return false;
		}

		if ( 37 === event.keyCode || 38 === event.keyCode || 39 === event.keyCode || 40 === event.keyCode ) {
			this.controller.trigger( 'attachment:keydown:arrow', event );
			return;
		}
	}
});

module.exports = Details;

},{}],27:[function(require,module,exports){
/**
 * wp.media.view.Attachment.EditLibrary
 *
 * @class
 * @augments wp.media.view.Attachment
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var EditLibrary = wp.media.view.Attachment.extend({
	buttons: {
		close: true
	}
});

module.exports = EditLibrary;

},{}],28:[function(require,module,exports){
/**
 * wp.media.view.Attachments.EditSelection
 *
 * @class
 * @augments wp.media.view.Attachment.Selection
 * @augments wp.media.view.Attachment
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var EditSelection = wp.media.view.Attachment.Selection.extend({
	buttons: {
		close: true
	}
});

module.exports = EditSelection;

},{}],29:[function(require,module,exports){
/**
 * wp.media.view.Attachment.Library
 *
 * @class
 * @augments wp.media.view.Attachment
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var Library = wp.media.view.Attachment.extend({
	buttons: {
		check: true
	}
});

module.exports = Library;

},{}],30:[function(require,module,exports){
/**
 * wp.media.view.Attachment.Selection
 *
 * @class
 * @augments wp.media.view.Attachment
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var Selection = wp.media.view.Attachment.extend({
	className: 'attachment selection',

	// On click, just select the model, instead of removing the model from
	// the selection.
	toggleSelection: function() {
		this.options.selection.single( this.model );
	}
});

module.exports = Selection;

},{}],31:[function(require,module,exports){
/**
 * wp.media.view.Attachments
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var View = wp.media.View,
	$ = jQuery,
	Attachments;

Attachments = View.extend({
	tagName:   'ul',
	className: 'attachments',

	attributes: {
		tabIndex: -1
	},

	initialize: function() {
		this.el.id = _.uniqueId('__attachments-view-');

		_.defaults( this.options, {
			refreshSensitivity: wp.media.isTouchDevice ? 300 : 200,
			refreshThreshold:   3,
			AttachmentView:     wp.media.view.Attachment,
			sortable:           false,
			resize:             true,
			idealColumnWidth:   $( window ).width() < 640 ? 135 : 150
		});

		this._viewsByCid = {};
		this.$window = $( window );
		this.resizeEvent = 'resize.media-modal-columns';

		this.collection.on( 'add', function( attachment ) {
			this.views.add( this.createAttachmentView( attachment ), {
				at: this.collection.indexOf( attachment )
			});
		}, this );

		this.collection.on( 'remove', function( attachment ) {
			var view = this._viewsByCid[ attachment.cid ];
			delete this._viewsByCid[ attachment.cid ];

			if ( view ) {
				view.remove();
			}
		}, this );

		this.collection.on( 'reset', this.render, this );

		this.listenTo( this.controller, 'library:selection:add',    this.attachmentFocus );

		// Throttle the scroll handler and bind this.
		this.scroll = _.chain( this.scroll ).bind( this ).throttle( this.options.refreshSensitivity ).value();

		this.options.scrollElement = this.options.scrollElement || this.el;
		$( this.options.scrollElement ).on( 'scroll', this.scroll );

		this.initSortable();

		_.bindAll( this, 'setColumns' );

		if ( this.options.resize ) {
			this.on( 'ready', this.bindEvents );
			this.controller.on( 'open', this.setColumns );

			// Call this.setColumns() after this view has been rendered in the DOM so
			// attachments get proper width applied.
			_.defer( this.setColumns, this );
		}
	},

	bindEvents: function() {
		this.$window.off( this.resizeEvent ).on( this.resizeEvent, _.debounce( this.setColumns, 50 ) );
	},

	attachmentFocus: function() {
		this.$( 'li:first' ).focus();
	},

	restoreFocus: function() {
		this.$( 'li.selected:first' ).focus();
	},

	arrowEvent: function( event ) {
		var attachments = this.$el.children( 'li' ),
			perRow = this.columns,
			index = attachments.filter( ':focus' ).index(),
			row = ( index + 1 ) <= perRow ? 1 : Math.ceil( ( index + 1 ) / perRow );

		if ( index === -1 ) {
			return;
		}

		// Left arrow
		if ( 37 === event.keyCode ) {
			if ( 0 === index ) {
				return;
			}
			attachments.eq( index - 1 ).focus();
		}

		// Up arrow
		if ( 38 === event.keyCode ) {
			if ( 1 === row ) {
				return;
			}
			attachments.eq( index - perRow ).focus();
		}

		// Right arrow
		if ( 39 === event.keyCode ) {
			if ( attachments.length === index ) {
				return;
			}
			attachments.eq( index + 1 ).focus();
		}

		// Down arrow
		if ( 40 === event.keyCode ) {
			if ( Math.ceil( attachments.length / perRow ) === row ) {
				return;
			}
			attachments.eq( index + perRow ).focus();
		}
	},

	dispose: function() {
		this.collection.props.off( null, null, this );
		if ( this.options.resize ) {
			this.$window.off( this.resizeEvent );
		}

		/**
		 * call 'dispose' directly on the parent class
		 */
		View.prototype.dispose.apply( this, arguments );
	},

	setColumns: function() {
		var prev = this.columns,
			width = this.$el.width();

		if ( width ) {
			this.columns = Math.min( Math.round( width / this.options.idealColumnWidth ), 12 ) || 1;

			if ( ! prev || prev !== this.columns ) {
				this.$el.closest( '.media-frame-content' ).attr( 'data-columns', this.columns );
			}
		}
	},

	initSortable: function() {
		var collection = this.collection;

		if ( ! this.options.sortable || ! $.fn.sortable ) {
			return;
		}

		this.$el.sortable( _.extend({
			// If the `collection` has a `comparator`, disable sorting.
			disabled: !! collection.comparator,

			// Change the position of the attachment as soon as the
			// mouse pointer overlaps a thumbnail.
			tolerance: 'pointer',

			// Record the initial `index` of the dragged model.
			start: function( event, ui ) {
				ui.item.data('sortableIndexStart', ui.item.index());
			},

			// Update the model's index in the collection.
			// Do so silently, as the view is already accurate.
			update: function( event, ui ) {
				var model = collection.at( ui.item.data('sortableIndexStart') ),
					comparator = collection.comparator;

				// Temporarily disable the comparator to prevent `add`
				// from re-sorting.
				delete collection.comparator;

				// Silently shift the model to its new index.
				collection.remove( model, {
					silent: true
				});
				collection.add( model, {
					silent: true,
					at:     ui.item.index()
				});

				// Restore the comparator.
				collection.comparator = comparator;

				// Fire the `reset` event to ensure other collections sync.
				collection.trigger( 'reset', collection );

				// If the collection is sorted by menu order,
				// update the menu order.
				collection.saveMenuOrder();
			}
		}, this.options.sortable ) );

		// If the `orderby` property is changed on the `collection`,
		// check to see if we have a `comparator`. If so, disable sorting.
		collection.props.on( 'change:orderby', function() {
			this.$el.sortable( 'option', 'disabled', !! collection.comparator );
		}, this );

		this.collection.props.on( 'change:orderby', this.refreshSortable, this );
		this.refreshSortable();
	},

	refreshSortable: function() {
		if ( ! this.options.sortable || ! $.fn.sortable ) {
			return;
		}

		// If the `collection` has a `comparator`, disable sorting.
		var collection = this.collection,
			orderby = collection.props.get('orderby'),
			enabled = 'menuOrder' === orderby || ! collection.comparator;

		this.$el.sortable( 'option', 'disabled', ! enabled );
	},

	/**
	 * @param {wp.media.model.Attachment} attachment
	 * @returns {wp.media.View}
	 */
	createAttachmentView: function( attachment ) {
		var view = new this.options.AttachmentView({
			controller:           this.controller,
			model:                attachment,
			collection:           this.collection,
			selection:            this.options.selection
		});

		return this._viewsByCid[ attachment.cid ] = view;
	},

	prepare: function() {
		// Create all of the Attachment views, and replace
		// the list in a single DOM operation.
		if ( this.collection.length ) {
			this.views.set( this.collection.map( this.createAttachmentView, this ) );

		// If there are no elements, clear the views and load some.
		} else {
			this.views.unset();
			this.collection.more().done( this.scroll );
		}
	},

	ready: function() {
		// Trigger the scroll event to check if we're within the
		// threshold to query for additional attachments.
		this.scroll();
	},

	scroll: function() {
		var view = this,
			el = this.options.scrollElement,
			scrollTop = el.scrollTop,
			toolbar;

		// The scroll event occurs on the document, but the element
		// that should be checked is the document body.
		if ( el === document ) {
			el = document.body;
			