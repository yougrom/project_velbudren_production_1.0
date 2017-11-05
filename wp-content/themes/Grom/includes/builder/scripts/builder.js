var ET_PageBuilder = ET_PageBuilder || {};

window.wp = window.wp || {};

/**
 * The builder version and product name will be updated by grunt release task. Do not edit!
 */
window.et_builder_version = '3.0.82';
window.et_builder_product_name = 'Divi';

( function($) {
	var et_error_modal_shown = window.et_error_modal_shown,
		et_is_loading_missing_modules = false,
		et_pb_bulder_loading_attempts = 0,
		et_ls_prefix = 'et_pb_templates_',
		et_pb_hovered_item_buffer = {},
		et_pb_all_unsynced_options = {},
		et_pb_all_legacy_synced_options = [],
		et_pb_key_pressed = {
			's' : false,
			'r' : false,
			'c' : false
		};

	function et_builder_maybe_clear_localstorage() {
		var settings_product_version = et_pb_options.product_version,
			forced_clear_setting_name,
			forced_clear;

		forced_clear_setting_name = 'et_forced_localstorage_clear';

		forced_clear = localStorage.getItem( forced_clear_setting_name );

		if ( ! forced_clear ) {
			forced_clear = wpCookies.get( forced_clear_setting_name );
		}

		// attempt to clear localStorage only once
		if ( forced_clear !== settings_product_version ) {
			localStorage.clear();

			wpCookies.set( forced_clear_setting_name, settings_product_version );

			localStorage.setItem( forced_clear_setting_name, settings_product_version );

			setTimeout( function() {
				window.location.reload();
			}, 100 );
		}
	}

	function et_builder_load_backbone_templates( reload_template ) {

		// run et_pb_append_templates as many times as needed
		var et_pb_templates_count = 0,
			date_now              = new Date(),
			today_date            = date_now.getYear() + '_' + date_now.getMonth() + '_' + date_now.getDate(),
			et_ls_all_modules     = ( et_pb_options['et_builder_module_parent_shortcodes'] + '|' + et_pb_options['et_builder_module_child_shortcodes'] ).split( '|' ),
			product_version       = et_pb_options.product_version,
			active_plugins        = et_pb_options.active_plugins.join('|'),
			local_storage_buffer  = '',
			processed_modules_count = 0,
			missing_modules = {
				missing_modules_array: []
			},
			et_pb_templates_interval;

		reload_template = _.isUndefined( reload_template ) ? false : reload_template;

		if ( ! reload_template ) {
			if ( ! $( 'script[src="' + et_pb_options.builder_js_src + '"]' ).length ) {
				$( '.et-pb-cache-update' ).show();
			}

			$( 'body' ).on( 'click', '.et_builder_increase_memory', function() {
				var $this_button = $(this);

				$.ajax({
					type: "POST",
					dataType: 'json',
					url: et_pb_options.ajaxurl,
					data: {
						action : 'et_pb_increase_memory_limit',
						et_admin_load_nonce : et_pb_options.et_admin_load_nonce
					},
					success: function( data ) {
						if ( ! _.isUndefined( data.success ) ) {
							$this_button.addClass( 'et_builder_modal_action_button_success' ).text( et_pb_options.memory_limit_increased );
						} else {
							$this_button.addClass( 'et_builder_modal_action_button_fail' ).prop( 'disabled', true ).text( et_pb_options.memory_limit_not_increased );
						}
					}
				});

				return false;
			} );

			$( 'body' ).on( 'click', '.et_pb_reload_builder', function() {
				location.reload();

				return false;
			} );

		}

		if ( et_should_load_from_local_storage() ) {
			for ( et_ls_module_index in et_ls_all_modules ) {
				var et_ls_module_slug      = et_ls_all_modules[ et_ls_module_index ],
					et_ls_template_slug    = et_ls_prefix + et_ls_module_slug,
					et_ls_template_content = LZString.decompressFromUTF16( localStorage.getItem( et_ls_template_slug ) );

				// count the processed modules
				processed_modules_count++;

				if ( _.isUndefined( et_ls_template_content ) || _.isNull( et_ls_template_content ) || '' === et_ls_template_content ) {
					missing_modules['missing_modules_array'].push( et_ls_module_slug );
				} else {
					local_storage_buffer += LZString.decompressFromUTF16( localStorage.getItem( et_ls_template_slug ) );
				}

				// perform ajax request if missing_modules_array length equals to the templates amount setting or if all the modules processed and we need to retrieve something
				if ( ! et_is_loading_missing_modules && ( ( missing_modules['missing_modules_array'].length === parseInt( et_pb_options.et_builder_templates_amount ) ) || ( missing_modules['missing_modules_array'].length && ( et_ls_all_modules.length === processed_modules_count ) ) ) ) {
					et_is_loading_missing_modules = true;
					$.ajax({
						type: "POST",
						dataType: 'json',
						url: et_pb_options.ajaxurl,
						data: {
							action : 'et_pb_get_backbone_template',
							et_post_type : et_pb_options.post_type,
							et_modules_slugs : JSON.stringify( missing_modules ),
							et_admin_load_nonce : et_pb_options.et_admin_load_nonce
						},
						success: function( data ) {
							et_is_loading_missing_modules = false;

							try {
								localStorage.setItem( et_ls_prefix + data['slug'], LZString.compressToUTF16( data['template'] ) );
							} catch(e) {
								// do not use localStorage if it full or any other error occurs

								et_builder_maybe_clear_localstorage();
							}

							$( 'body' ).append( data.template );
							if ( data.length ) {
								_.each( data, function( single_module ) {
									try {
										localStorage.setItem( et_ls_prefix + single_module['slug'], LZString.compressToUTF16( single_module['template'] ) );
									} catch(e) {
										// do not use localStorage if it full or any other error occurs

										et_builder_maybe_clear_localstorage();
									}

									$( 'body' ).append( single_module['template'] );
								} );
							}
						}
					});

					// reset the array of missing modules
					missing_modules['missing_modules_array'] = [];
				}

			}

			$( 'body' ).append( local_storage_buffer );

		} else {

			// run et_pb_append_templates as many times as needed
			et_pb_templates_interval = setInterval( function() {
				if ( et_pb_templates_count === Math.ceil( et_pb_options.et_builder_modules_count/et_pb_options.et_builder_templates_amount ) ) {
					clearInterval( et_pb_templates_interval );
					return false;
				}

				et_pb_append_templates( et_pb_templates_count * et_pb_options.et_builder_templates_amount );

				et_pb_templates_count++;
			}, 800);

			et_ls_set_transient();

		}

		function et_builder_has_storage_support() {
			try {
				return 'localStorage' in window && window.localStorage !== null;
			} catch (e) {
				return false;
			}
		}

		function et_ls_set_transient() {
			if ( ! et_builder_has_storage_support() ) {
				return false;
			}

			try {
				localStorage.setItem( et_ls_prefix + 'settings_date', today_date );

				localStorage.setItem( et_ls_prefix + 'settings_product_version', product_version );

				localStorage.setItem( et_ls_prefix + 'settings_active_plugins', active_plugins );
			} catch(e) {
				// do not use localStorage if it full or any other error occurs
			}
		}

		function et_should_load_from_local_storage() {
			if ( ! et_builder_has_storage_support() ) {
				return false;
			}

			if ( ! _.isUndefined( et_pb_options.force_cache_purge ) && 'true' === et_pb_options.force_cache_purge ) {
				return false;
			}

			var et_ls_settings_date = localStorage.getItem( et_ls_prefix + 'settings_date' ),
				et_ls_settings_product_version = localStorage.getItem( et_ls_prefix + 'settings_product_version' ),
				et_ls_settings_active_plugins = localStorage.getItem( et_ls_prefix + 'settings_active_plugins' );

			if ( _.isUndefined( et_ls_settings_date ) || _.isNull( et_ls_settings_date ) ) {
				return false;
			}

			if ( _.isUndefined( et_ls_settings_product_version ) || _.isNull( et_ls_settings_product_version ) ) {
				return false;
			}

			if ( et_ls_settings_active_plugins !== active_plugins ) {
				return false;
			}

			if ( today_date != et_ls_settings_date || product_version != et_ls_settings_product_version ) {
				et_remove_ls_templates();

				return false;
			}

			return true;
		}

		function et_remove_ls_templates() {
			if ( ! et_builder_has_storage_support() ) {
				return false;
			}

			_.forEach( _.keys( localStorage ), function( key ) {
				if ( startsWith( key, 'et_pb_templates_' ) ) {
					localStorage.removeItem( key );
				}
			} );
		}

		function et_pb_append_templates( start_from ) {
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: et_pb_options.ajaxurl,
				data: {
					action : 'et_pb_get_backbone_templates',
					et_post_type : et_pb_options.post_type,
					et_admin_load_nonce : et_pb_options.et_admin_load_nonce,
					et_templates_start_from : start_from
				},
				error: function() {
					var $failure_notice_template = $( '#et-builder-failure-notice-template' );

					if ( et_error_modal_shown ) {
						return;
					}

					if ( ! $failure_notice_template.length ) {
						return;
					}

					if ( $( '.et_pb_failure_notification_modal' ).length ) {
						return;
					}

					if ( et_builder_has_storage_support() ) {
						localStorage.removeItem( et_ls_prefix + 'settings_date' );
						localStorage.removeItem( et_ls_prefix + 'settings_product_version' );
						localStorage.removeItem( et_ls_prefix + 'settings_active_plugins' );
					}

					$( 'body' ).addClass( 'et_pb_stop_scroll' ).append( $failure_notice_template.html() );
				},
				success: function( data ) {
					//append retrieved templates to body
					for ( var name in data.templates ) {
						if ( et_builder_has_storage_support() ) {
							try {
								localStorage.setItem( 'et_pb_templates_' + name, LZString.compressToUTF16( data.templates[name] ) );
							} catch(e) {
								// do not use localStorage if it full or any other error occurs

								et_builder_maybe_clear_localstorage();
							}
						}

						$( 'body' ).append( data.templates[name] );
					}
				}
			});
		}

	}
	et_builder_load_backbone_templates();

	/**
	 * Get value from object located at path.
	 *
	 * @see https://stackoverflow.com/a/15643385/2639936
	 *
	 * @param obj
	 * @param path
	 * @return {*}
	 */
	function get( obj, path ) {
		return _.reduce( path.split( '.' ), function ( prev, curr ) {
			return prev ? prev[curr] : undefined;
		}, obj );
	}

	/**
	 * Check if path exists in object.
	 *
	 * @see https://stackoverflow.com/a/42042678/2639936
	 *
	 * @param obj
	 * @param path
	 * @return {boolean}
	 */
	function has( obj, path ) {
		if ( ! path ) {
			return true;
		}

		var path_parts = path.split( '.' );
		var first_part = _.first( path_parts );

		return _.has( obj, first_part ) && has( obj[first_part], _.rest( path_parts ).join( '.' ) );
	}

	/**
	 * Determine whether or not a string ends with another string.
	 *
	 * @param string
	 * @param substring
	 * @return {boolean}
	 */
	function endsWith( string, substring ) {
		return string.substr( string.length - substring.length, string.length ) === substring;
	}

	/**
	 * Determine whether or not a string starts with another string.
	 *
	 * @param string
	 * @param substring
	 * @return {boolean}
	 */
	function startsWith( string, substring ) {
		return string.substr( 0, string.length ) === substring;
	}


	$( document ).ready( function() {

		// Explicitly define ERB-style template delimiters to prevent
		// template delimiters being overwritten by 3rd party plugin
		_.templateSettings = {
			evaluate   : /<%([\s\S]+?)%>/g,
			interpolate: /<%=([\s\S]+?)%>/g,
			escape     : /<%-([\s\S]+?)%>/g
		};

		// Models

		ET_PageBuilder.Module = Backbone.Model.extend( {

			defaults: {
				type : 'element',
				_builder_version : et_pb_options.product_version
			}

		} );

		ET_PageBuilder.SavedTemplate = Backbone.Model.extend( {

			defaults: {
				title : 'template',
				ID : 0,
				shortcode : '',
				is_global : 'false',
				layout_type : '',
				module_type : '',
				categories : [],
				unsynced_options : []
			}

		} );

		ET_PageBuilder.History = Backbone.Model.extend( {

			defaults : {
				timestamp : _.now(),
				shortcode : '',
				current_active_history : false,
				verb : 'did',
				noun : 'something'
			},

			max_history_limit : 100,

			validate : function( attributes, options ) {
				var histories_count = options.collection.length,
					active_history_model = options.collection.findWhere({ current_active_history : true }),
					shortcode            = attributes.shortcode,
					last_model           = _.isUndefined( active_history_model ) ? options.collection.at( ( options.collection.length - 1 ) ) : active_history_model,
					last_shortcode       = _.isUndefined( last_model ) ? false : last_model.get( 'shortcode' ),
					previous_active_histories;

				if ( shortcode === last_shortcode ) {
					return 'duplicate';
				}

				// Turn history tracking off
				ET_PageBuilder_App.enable_history = false;

				// Limit number of history limit
				var histories_count = options.collection.models.length,
					remove_limit = histories_count - ( this.max_history_limit - 1 ),
					ranges,
					deleted_model;

				// Some models are need to be removed
				if ( remove_limit > 0 ) {
					// Loop and shift (remove first model in collection) n-times
					for (var i = 1; i <= remove_limit; i++) {
						options.collection.shift();
					};
				}
			}

		} );

		// helper module
		ET_PageBuilder.Layout = Backbone.Model.extend( {

			defaults: {
				moduleNumber : 0,
				forceRemove : false,
				modules : $.parseJSON( et_pb_options.et_builder_modules ),
				views : [
				]
			},

			initialize : function() {
				// Single and double quotes are replaced with %% in et_builder_modules
				// to avoid js conflicts.
				// Replace them with appropriate signs.
				_.each( this.get( 'modules' ), function( module ) {
					module['title'] = module['title'].replace( /%%/g, '"' );
					module['title'] = module['title'].replace( /\|\|/g, "'" );
				} );
			},

			addView : function( module_cid, view ) {
				var views = this.get( 'views' );

				views[module_cid] = view;
				this.set( { 'views' : views } );
			},

			getView : function( cid ) {
				return this.get( 'views' )[cid];
			},

			getChildViews : function( parent_id ) {
				var views = this.get( 'views' ),
					child_views = {};

				_.each( views, function( view, key ) {
					if ( typeof view !== 'undefined' && view['model']['attributes']['parent'] === parent_id )
						child_views[key] = view;
				} );

				return child_views;
			},

			getChildrenViews : function( parent_id ) {
				var this_el = this,
					views = this_el.get( 'views' ),
					child_views = {},
					grand_children;

				_.each( views, function( view, key ) {
					if ( typeof view !== 'undefined' && view['model']['attributes']['parent'] === parent_id ) {
						grand_children = this_el.getChildrenViews( view['model']['attributes']['cid'] );

						if ( ! _.isEmpty( grand_children ) ) {
							_.extend( child_views, grand_children );
						}

						child_views[key] = view;
					}

				} );

				return child_views;
			},

			getParentViews : function( parent_cid ) {
				var parent_view = this.getView( parent_cid ),
					parent_views = {};

				while( ! _.isUndefined( parent_view ) ) {

					parent_views[parent_view['model']['attributes']['cid']] = parent_view;
					parent_view = this.getView( parent_view['model']['attributes']['parent'] );
				}

				return parent_views;
			},

			getSectionView : function( parent_cid ) {
				var views = this.getParentViews( parent_cid ),
					section_view;

				section_view = _.filter( views, function( item ) {
					if ( item.model.attributes.type === "section" ) {
						return true;
					} else {
						return false;
					}
				} );

				if ( _.isUndefined( section_view[0] ) ) {
					return false;
				} else {
					return section_view[0];
				}
			},

			setNewParentID : function( cid, new_parent_id ) {
				var views = this.get( 'views' );

				views[cid]['model']['attributes']['parent'] = new_parent_id;

				this.set( { 'views' : views } );
			},

			removeView : function( cid ) {
				var views = this.get( 'views' ),
					new_views = {};

				_.each( views, function( value, key ) {
					if ( key != cid )
						new_views[key] = value;
				} );

				this.set( { 'views' : new_views } );
			},

			generateNewId : function() {
				var moduleNumber = this.get( 'moduleNumber' ) + 1;

				this.set( { 'moduleNumber' : moduleNumber } );

				return moduleNumber;
			},

			generateTemplateName : function( name ) {
				var default_elements = [ 'row', 'row_inner', 'section', 'column', 'column_inner'];

				if ( -1 !== $.inArray( name, default_elements ) ) {
					name = 'et_pb_' + name;
				}

				return '#et-builder-' + name + '-module-template';
			},

			getModuleOptionsNames : function( module_type ) {
				var modules = this.get('modules');

				return this.addAdminLabel( _.findWhere( modules, { label : module_type } )['options'] );
			},

			getNumberOf : function( element_name, module_cid ) {
				var views = this.get( 'views' ),
					num = 0;

				_.each( views, function( view ) {
					if ( typeof view !== 'undefined' ) {
						var type = view['model']['attributes']['type'];

						if ( view['model']['attributes']['parent'] === module_cid && ( type === element_name || type === ( element_name + '_inner' ) ) )
							num++;
					}
				} );

				return num;
			},

			getNumberOfModules : function( module_name ) {
				var views = this.get( 'views' ),
					num = 0;

				_.each( views, function( view ) {
					if ( typeof view !== 'undefined' ) {
						if ( view['model']['attributes']['type'] === module_name )
							num++;
					}
				} );

				return num;
			},

			getTitleByShortcodeTag : function ( tag ) {
				var modules = this.get('modules');

				return _.findWhere( modules, { label : tag } )['title'];
			},

			getDefaultAdminLabel : function( module_type ) {
				var is_structure_element = $.inArray( module_type, [ 'section', 'row', 'column', 'row_inner', 'column_inner' ] ) > -1;

				if ( is_structure_element ) {
					return typeof et_pb_options.noun[ module_type ] !== 'undefined' ? et_pb_options.noun[ module_type ] : module_type;
				}

				return this.getTitleByShortcodeTag( module_type );
			},

			isModuleFullwidth : function ( module_type ) {
				var modules = this.get('modules');

				return 'on' === _.findWhere( modules, { label : module_type } )['fullwidth_only'] ? true : false;
			},

			isChildrenLocked : function ( module_cid ) {
				var children_views = this.getChildrenViews( module_cid ),
					children_locked = false;

				_.each( children_views, function( child ) {
					if ( child.model.get( 'et_pb_locked' ) === 'on' || child.model.get( 'et_pb_parent_locked' ) === 'on' ) {
						children_locked = true;
					}
				} );

				return children_locked;
			},

			addAdminLabel : function ( optionsNames ) {
				return _.union( optionsNames, ['admin_label'] );
			},

			removeGlobalAttributes : function ( view, keep_attributes ) {
				var this_class                 = this,
					keep_attributes            = _.isUndefined( keep_attributes ) ? false : keep_attributes,
					global_item_cid            = _.isUndefined( view.model.attributes.global_parent_cid ) ? view.model.attributes.cid : view.model.attributes.global_parent_cid,
					global_item_view           = this.getView( global_item_cid );
					global_item_children_views = this.getChildrenViews( global_item_cid );

				// Modify global item's attributes
				if ( this.is_global( global_item_view.model ) ) {
					if ( keep_attributes ) {
						global_item_view.model.set( 'et_pb_temp_global_module', global_item_view.model.get( 'et_pb_global_module' ) );
					}

					global_item_view.model.unset( 'et_pb_global_module' );
				}

				// Modify global item children's attributes
				_.each( global_item_children_views, function( global_item_children_view ) {
					if ( this_class.is_global_children( global_item_children_view.model ) ) {
						if ( keep_attributes ) {
							global_item_children_view.model.set( 'et_pb_temp_global_parent', global_item_children_view.model.get( 'et_pb_global_parent' ) );
						}

						global_item_children_view.model.unset( 'et_pb_global_parent' );
					}

					if ( this_class.has_global_parent_cid( global_item_children_view.model ) ) {
						if ( keep_attributes ) {
							global_item_children_view.model.set( 'et_pb_temp_global_parent_cid', global_item_children_view.model.get( 'global_parent_cid' ) );
						}

						global_item_children_view.model.unset( 'global_parent_cid' );
					}
				});
			},

			removeTemporaryGlobalAttributes : function ( view, restore_attributes ) {
				var this_class         = this,
					restore_attributes = _.isUndefined( restore_attributes ) ? false : restore_attributes,
					global_item_model = _.isUndefined( view.model.attributes.et_pb_temp_global_module ) ? ET_PageBuilder_Modules.findWhere({ et_pb_temp_global_module : view.model.attributes.et_pb_temp_global_parent }) : view.model,
					global_item_cid   = global_item_model.attributes.cid,
					global_item_view  = ET_PageBuilder_Layout.getView( global_item_cid );
					global_item_children_views = ET_PageBuilder_Layout.getChildrenViews( global_item_cid );

				if ( this.is_temp_global( global_item_view.model ) ) {
					if ( restore_attributes ) {
						global_item_view.model.set( 'et_pb_global_module', global_item_view.model.get( 'et_pb_temp_global_module' ) );
					}

					global_item_view.model.unset( 'et_pb_temp_global_module' );
				}

				_.each( global_item_children_views, function( global_item_children_view ) {
					if ( this_class.is_temp_global_children( global_item_children_view.model ) ) {
						if ( restore_attributes ) {
							global_item_children_view.model.set( 'et_pb_global_parent', global_item_children_view.model.get( 'et_pb_temp_global_parent' ) );
						}

						global_item_children_view.model.unset( 'et_pb_temp_global_parent' );
					}

					if ( this_class.has_temp_global_parent_cid( global_item_children_view.model ) ) {
						if ( restore_attributes ) {
							global_item_children_view.model.set( 'global_parent_cid', global_item_children_view.model.get( 'et_pb_temp_global_parent_cid' ) );
						}

						global_item_children_view.model.unset( 'et_pb_temp_global_parent_cid' );
					}
				});

				if ( restore_attributes ) {
					// Update global template
					et_pb_update_global_template( global_item_cid );
				}
			},

			is_app : function( model ) {
				if ( model.attributes.type === 'app' ) {
					return true;
				}

				return false;
			},

			is_global : function( model ) {
				// App cannot be global module. Its model.get() returns error
				if ( this.is_app( model ) ) {
					return false;
				}

				return model.has( 'et_pb_global_module' ) && model.get( 'et_pb_global_module' ) !== '' ? true : false;
			},

			is_global_children : function( model ) {
				// App cannot be global module. Its model.get() returns error
				if ( this.is_app( model ) ) {
					return false;
				}

				return model.has( 'et_pb_global_parent' ) && model.get( 'et_pb_global_parent' ) !== '' ? true : false;
			},

			has_global_parent_cid : function( model ) {
				return model.has( 'global_parent_cid' ) && model.get( 'global_parent_cid' ) !== '' ? true : false;
			},

			is_temp_global : function( model ) {
				return model.has( 'et_pb_temp_global_module' ) && model.get( 'et_pb_temp_global_module' ) !== '' ? true : false;
			},

			is_temp_global_children : function( model ) {
				return model.has( 'et_pb_temp_global_parent' ) && model.get( 'et_pb_temp_global_parent' ) !== '' ? true : false;
			},

			has_temp_global_parent_cid : function( model ) {
				return model.has( 'et_pb_temp_global_parent_cid' ) && model.get( 'et_pb_temp_global_parent_cid' ) !== '' ? true : false;
			},

			changeColumnStructure: function( that, options, skip_reinit, skip_history ) {
				var layout = options.layout.split(','),
					specialty_columns = options.specialty_columns,
					layout_specialty = options.layout_specialty,
					layout_elements_num = _.size( layout ),
					this_view = that.options.view;
					global_module_cid = et_pb_get_global_parent_cid( that );

				if ( options.is_structure_change ) {
					var row_columns = ET_PageBuilder_Layout.getChildViews( that.model.get( 'cid' ) ),
						columns_structure_old = [],
						index_count = 0;

					_.each( row_columns, function( row_column ) {
						columns_structure_old[index_count] = row_column.model.get( 'cid' );
						index_count = index_count + 1;
					} );
				}

				_.each( layout, function( element, index ) {
					var update_content = layout_elements_num == ( index + 1 )
						? 'true'
						: 'false',
						column_attributes = {
							type : 'column',
							cid : ET_PageBuilder_Layout.generateNewId(),
							parent : that.model.get( 'cid' ),
							layout : element,
							view : this_view
						};

					if ( typeof that.model.get( 'et_pb_global_parent' ) !== 'undefined' && '' !== that.model.get( 'et_pb_global_parent' ) ) {
						column_attributes.et_pb_global_parent = that.model.get( 'et_pb_global_parent' );
						column_attributes.global_parent_cid = that.model.get( 'global_parent_cid' );
					}

					if ( typeof layout_specialty[index] !== 'undefined' && layout_specialty[index] === '1' ) {
						column_attributes.layout_specialty = layout_specialty[index];
						column_attributes.specialty_columns = parseInt( specialty_columns );
					}

					if ( typeof that.model.get( 'specialty_row' ) !== 'undefined' ) {
						that.model.set( 'module_type', 'row_inner', { silent : true } );
						that.model.set( 'type', 'row_inner', { silent : true } );
					}

					that.collection.add( [ column_attributes ], { update_shortcodes : update_content } );
				} );

				if ( options.is_structure_change ) {
					var columns_structure_new = [];

					row_columns = ET_PageBuilder_Layout.getChildViews( that.model.get( 'cid' ) );
					index_count = 0;

					_.each( row_columns, function( row_column ) {
						columns_structure_new[index_count] = row_column.model.get( 'cid' );
						index_count = index_count + 1;
					} );

					// delete old columns IDs
					columns_structure_new.splice( 0, columns_structure_old.length );

					for ( index = 0; index < columns_structure_old.length; index++ ) {
						var is_extra_column = ( columns_structure_old.length > columns_structure_new.length ) && ( index > ( columns_structure_new.length - 1 ) ) ? true : false,
							old_column_cid = columns_structure_old[index],
							new_column_cid = is_extra_column ? columns_structure_new[columns_structure_new.length-1] : columns_structure_new[index],
							column_html = ET_PageBuilder_Layout.getView( old_column_cid ).$el.html(),
							modules = ET_PageBuilder_Layout.getChildViews( old_column_cid ),
							$updated_column,
							column_html_old = '';

						ET_PageBuilder_Layout.getView( old_column_cid ).model.destroy();

						ET_PageBuilder_Layout.getView( old_column_cid ).remove();

						ET_PageBuilder_Layout.removeView( old_column_cid );

						$updated_column = $('.et-pb-column[data-cid="' + new_column_cid + '"]');

						if ( ! is_extra_column ) {
							$updated_column.html( column_html );
						} else {
							$updated_column.find( '.et-pb-insert-module' ).remove();

							column_html_old = $updated_column.html();

							$updated_column.html( column_html_old + column_html );
						}

						_.each( modules, function( module ) {
							module.model.set( 'parent', new_column_cid, { silent : true } );
						} );
					}

					// Enable history saving and set meta for history
					ET_PageBuilder_App.allowHistorySaving( 'edited', 'column' );

					if ( ! skip_reinit ) {
						et_reinitialize_builder_layout();
					}
				}

				if ( typeof that.model.get( 'template_type' ) !== 'undefined' && 'section' === that.model.get( 'template_type' ) && 'on' === that.model.get( 'et_pb_specialty' ) ) {
					et_reinitialize_builder_layout();
				}

				if ( typeof that.model.get( 'et_pb_template_type' ) !== 'undefined' && 'row' === that.model.get( 'et_pb_template_type' ) ) {
					et_add_template_meta( '_et_pb_row_layout', options.layout );
				}

				if ( typeof global_module_cid !== 'undefined' && '' !== global_module_cid ) {
					et_pb_update_global_template( global_module_cid );
				}

				if ( ! skip_history ) {
					// Enable history saving and set meta for history
					ET_PageBuilder_App.allowHistorySaving( 'added', 'column' );
				}

				ET_PageBuilder_Events.trigger( 'et-add:columns' );
			},
		} );

		// Collections

		ET_PageBuilder.Modules = Backbone.Collection.extend( {

			model : ET_PageBuilder.Module

		} );

		ET_PageBuilder.SavedTemplates = Backbone.Collection.extend( {

			model : ET_PageBuilder.SavedTemplate

		} );

		ET_PageBuilder.Histories = Backbone.Collection.extend( {

			model : ET_PageBuilder.History

		} );


		//Views
		ET_PageBuilder.TemplatesView = window.wp.Backbone.View.extend( {
			className : 'et_pb_saved_layouts_list',

			tagName : 'ul',

			render: function() {
				var global_class = '',
					layout_category = typeof this.options.category === 'undefined' ? 'all' : this.options.category;

				this.collection.each( function( single_template ) {
					if ( 'all' === layout_category || ( -1 !== $.inArray( layout_category, single_template.get( 'categories' ) ) ) ) {
						var single_template_view = new ET_PageBuilder.SingleTemplateView( { model: single_template } );
						this.$el.append( single_template_view.el );
						global_class = typeof single_template_view.model.get( 'is_global' ) !== 'undefined' && 'global' === single_template_view.model.get( 'is_global' ) ? 'global' : '';
					}
				}, this );

				if ( 'global' === global_class ) {
					this.$el.addClass( 'et_pb_global' );
				}

				return this;
			}

		} );

		ET_PageBuilder.SingleTemplateView = window.wp.Backbone.View.extend( {
			tagName : 'li',

			template: _.template( $( '#et-builder-saved-entry' ).html() ),

			events: {
				'click' : 'insertSection',
			},

			initialize: function(){
				this.render();
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );

				if ( typeof this.model.get( 'module_type' ) !== 'undefined' && '' !== this.model.get( 'module_type' ) && 'module' === this.model.get( 'layout_type' ) ) {
					this.$el.addClass( this.model.get( 'module_type' ) );
				}
			},

			insertSection : function( event ) {
				var clicked_button     = $( event.target ),
					parent_id          = typeof clicked_button.closest( '.et_pb_modal_settings' ).data( 'parent_cid' ) !== 'undefined' ? clicked_button.closest( '.et_pb_modal_settings' ).data( 'parent_cid' ) : '',
					current_row        = typeof $( '.et-pb-settings-heading' ).data( 'current_row' ) !== 'undefined' ? $( '.et-pb-settings-heading' ).data( 'current_row' ) : '',
					global_id          = 'global' === this.model.get( 'is_global' ) ? this.model.get( 'ID' ) : '',
					specialty_row      = typeof $( '.et-pb-saved-modules-switcher' ).data( 'specialty_columns' ) !== 'undefined' ? 'on' : 'off',
					shortcode          = this.model.get( 'shortcode' ),
					unsynced_options   = this.model.get( 'unsynced_options' ),
					update_global      = false,
					global_holder_id   = 'row' === this.model.get( 'layout_type' ) ? current_row : parent_id,
					global_holder_view = ET_PageBuilder_Layout.getView( global_holder_id ),
					history_noun       = this.options.model.get( 'layout_type' ) === 'row_inner' ? 'saved_row' : 'saved_' + this.options.model.get( 'layout_type' ),
					$modal_container   = clicked_button.closest( '.et_pb_modal_settings_container' ),
					global_parent_cid  = et_pb_get_global_parent_cid( global_holder_view );

					if ( 'on' === specialty_row ) {
						global_holder_id = global_holder_view.model.get( 'parent' );
						global_holder_view = ET_PageBuilder_Layout.getView( global_holder_id );
					}

					if ( 'section' !== this.model.get( 'layout_type' ) && ( '' !== global_parent_cid ) ) {
						update_global = true;
						// reset global id when adding global module into global section or row.
						global_id = '';
					}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'added', history_noun );

				event.preventDefault();

				// apply wpautop to the shortcode to make sure all the line breaks inserted correctly
				if ( typeof window.switchEditors !== 'undefined' ) {
					shortcode = et_pb_fix_shortcodes( window.switchEditors.wpautop( shortcode ) );
				}

				ET_PageBuilder_App.createLayoutFromContent( shortcode , parent_id, '', { ignore_template_tag : 'ignore_template', current_row_cid : current_row, global_id : global_id, after_section : parent_id, is_reinit : 'reinit', 'unsynced_options' : unsynced_options } );
				et_reinitialize_builder_layout();

				if ( true === update_global ) {
					et_pb_update_global_template( global_parent_cid );
				}

				if ( $modal_container.length ) {
					$modal_container.find( '.et-pb-modal-close' ).click();
				}
			}
		} );

		ET_PageBuilder.TemplatesModal = window.wp.Backbone.View.extend( {
			className : 'et_pb_modal_settings',

			template : _.template( $( '#et-builder-load_layout-template' ).html() ),

			events : {
				'click .et-pb-options-tabs-links li a' : 'switchTab'
			},

			render: function() {

				this.$el.html( this.template( { "display_switcher" : "off" } ) );

				this.$el.addClass( 'et_pb_modal_no_tabs' );

				return this;
			},

			switchTab: function( event ) {
				var $this_el = $( event.currentTarget ).parent();
				event.preventDefault();

				et_handle_templates_switching( $this_el, 'section', '' );
			}

		} );

		ET_PageBuilder.SectionView = window.wp.Backbone.View.extend( {

			className : 'et_pb_section',

			template : _.template( $('#et-builder-section-template').html() ),

			events: {
				'click .et-pb-settings-section' : 'showSettings',
				'click .et-pb-clone-section' : 'cloneSection',
				'click .et-pb-remove-section' : 'removeSection',
				'click .et-pb-section-add-main' : 'addSection',
				'click .et-pb-section-add-fullwidth' : 'addFullwidthSection',
				'click .et-pb-section-add-specialty' : 'addSpecialtySection',
				'click .et-pb-section-add-saved' : 'addSavedSection',
				'click .et-pb-expand' : 'expandSection',
				'click .et-pb-insert-row' : 'addFirstRow',
				'contextmenu .et-pb-section-add' : 'showRightClickOptions',
				'click.et_pb_section > .et-pb-controls .et-pb-unlock' : 'unlockSection',
				'contextmenu.et_pb_section > .et-pb-controls' : 'showRightClickOptions',
				'contextmenu.et_pb_row > .et-pb-right-click-trigger-overlay' : 'showRightClickOptions',
				'click.et_pb_section > .et-pb-controls' : 'hideRightClickOptions',
				'click.et_pb_row > .et-pb-right-click-trigger-overlay' : 'hideRightClickOptions',
				'click > .et-pb-locked-overlay' : 'showRightClickOptions',
				'contextmenu > .et-pb-locked-overlay' : 'showRightClickOptions',
				'click' : 'setABTesting',
			},

			initialize : function() {
				this.child_views = [];
				this.listenTo( this.model, 'change:admin_label', this.renameModule );
				this.listenTo( this.model, 'change:et_pb_disabled', this.toggleDisabledClass );
			},

			render : function() {
				this.$el.html( this.template( this.model.toJSON() ) );

				if ( this.model.get( 'et_pb_specialty' ) === 'on' ) {
					this.$el.addClass( 'et_pb_section_specialty' );

					if ( this.model.get( 'et_pb_specialty_placeholder' ) === 'true' ) {
						this.$el.addClass( 'et_pb_section_placeholder' );
					}
				}

				if ( this.model.get( 'et_pb_specialty' ) === 'on' || this.model.get( 'et_pb_fullwidth' ) === 'on' ) {
					this.$el.find( '.et-pb-insert-row' ).remove();
				}

				if ( typeof this.model.get( 'et_pb_global_module' ) !== 'undefined' || ( typeof this.model.get( 'et_pb_template_type' ) !== 'undefined' && 'section' === this.model.get( 'et_pb_template_type' ) && 'global' === et_pb_options.is_global_template ) ) {
					this.$el.addClass( 'et_pb_global' );
				}

				if ( typeof this.model.get( 'et_pb_disabled' ) !== 'undefined' && this.model.get( 'et_pb_disabled' ) === 'on' ) {
					this.$el.addClass( 'et_pb_disabled' );
				}

				if ( typeof this.model.get( 'et_pb_locked' ) !== 'undefined' && this.model.get( 'et_pb_locked' ) === 'on' ) {
					this.$el.addClass( 'et_pb_locked' );
				}

				if ( typeof this.model.get( 'et_pb_collapsed' ) !== 'undefined' && this.model.get( 'et_pb_collapsed' ) === 'on' ) {
					this.$el.addClass( 'et_pb_collapsed' );
				}

				if ( typeof this.model.get( 'pasted_module' ) !== 'undefined' && this.model.get( 'pasted_module' ) ) {
					et_pb_handle_clone_class( this.$el );
				}

				if ( ! _.isUndefined( this.model.get( 'et_pb_temp_global_module' ) ) ) {
					this.$el.addClass( 'et_pb_global_temp' );
				}

				// Split Testing related class
				if ( ET_PageBuilder_AB_Testing.is_active() ) {
					if ( ET_PageBuilder_AB_Testing.is_subject( this.model ) ) {
						this.$el.addClass( 'et_pb_ab_subject' );

						// Apply subject rank coloring
						ET_PageBuilder_AB_Testing.set_subject_rank_coloring( this );
					}

					if ( ET_PageBuilder_AB_Testing.is_goal( this.model ) ) {
						this.$el.addClass( 'et_pb_ab_goal' );
					}

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'section' ) ) {
						this.$el.addClass( 'et_pb_ab_no_permission' );
					}
				}

				this.makeRowsSortable();

				return this;
			},

			showSettings : function( event ) {
				var that = this,
					$current_target = typeof event !== 'undefined' ? $( event.currentTarget ) : '',
					modal_view,
					view_settings = {
						model : this.model,
						collection : this.collection,
						attributes : {
							'data-open_view' : 'module_settings'
						},
						triggered_by_right_click : this.triggered_by_right_click,
						do_preview : this.do_preview
					};

				if ( typeof event !== 'undefined' ) {
					event.preventDefault();
				}

				if ( this.isSectionLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'section' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}
				}

				if ( '' !== $current_target && $current_target.closest( '.et_pb_section_specialty' ).length ) {
					var $specialty_section_columns = $current_target.closest( '.et_pb_section_specialty' ).find( '.et-pb-section-content > .et-pb-column' ),
						columns_layout = '';

					if ( $specialty_section_columns.length ) {
						$specialty_section_columns.each( function() {
							columns_layout += '' === columns_layout ? '1_1' : ',1_1';
						});
					}

					view_settings.model.attributes.columns_layout = columns_layout;

				}

				modal_view = new ET_PageBuilder.ModalView( view_settings );

				et_modal_view_rendered = modal_view.render();

				if ( false === et_modal_view_rendered ) {
					et_builder_load_backbone_templates( true );

					setTimeout( function() {
						that.showSettings();
					}, 500 );

					ET_PageBuilder_Events.trigger( 'et-pb-loading:started' );

					return;
				}

				ET_PageBuilder_Events.trigger( 'et-pb-loading:ended' );

				$('body').append( et_modal_view_rendered.el );

				if ( ( typeof modal_view.model.get( 'et_pb_global_module' ) !== 'undefined' && '' !== modal_view.model.get( 'et_pb_global_module' ) ) || ( typeof this.model.get( 'et_pb_template_type' ) !== 'undefined' && 'section' === this.model.get( 'et_pb_template_type' ) && 'global' === et_pb_options.is_global_template ) ) {
					$( '.et_pb_modal_settings_container' ).addClass( 'et_pb_saved_global_modal' );
				}

				if ( typeof this.model.get( 'et_pb_specialty' ) !== 'undefined' && 'on' === this.model.get( 'et_pb_specialty' ) ) {
					$( '.et_pb_modal_settings_container' ).addClass( 'et_pb_specialty_section_settings' );
				}

				et_pb_open_current_tab();
			},

			addSection : function( event ) {
				var module_id = ET_PageBuilder_Layout.generateNewId();

				event.preventDefault();

				et_pb_close_all_right_click_options();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'added', 'section' );

				this.collection.add( [ {
					type : 'section',
					module_type : 'section',
					et_pb_fullwidth : 'off',
					et_pb_specialty : 'off',
					cid : module_id,
					view : this,
					created : 'auto',
					admin_label : et_pb_options.noun['section']
				} ] );
			},

			addFullwidthSection : function( event ) {
				var module_id = ET_PageBuilder_Layout.generateNewId();

				event.preventDefault();

				et_pb_close_all_right_click_options();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'added', 'fullwidth_section' );

				this.collection.add( [ {
					type : 'section',
					module_type : 'section',
					et_pb_fullwidth : 'on',
					et_pb_specialty : 'off',
					cid : module_id,
					view : this,
					created : 'auto',
					admin_label : et_pb_options.noun['section']
				} ] );
			},

			addSpecialtySection : function( event ) {
				var module_id = ET_PageBuilder_Layout.generateNewId(),
					$event_target = $(event.target),
					template_type = typeof $event_target !== 'undefined' && typeof $event_target.data( 'is_template' ) !== 'undefined' ? 'section' : '';

				event.preventDefault();

				et_pb_close_all_right_click_options();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'added', 'specialty_section' );

				this.collection.add( [ {
					type : 'section',
					module_type : 'section',
					et_pb_fullwidth : 'off',
					et_pb_specialty : 'on',
					cid : module_id,
					template_type : template_type,
					view : this,
					created : 'auto',
					admin_label : et_pb_options.noun['section']
				} ] );
			},

			addSavedSection : function( event ) {
				var parent_cid = this.model.get( 'cid' ),
					view_settings = {
						attributes : {
							'data-open_view' : 'saved_templates',
							'data-parent_cid' : parent_cid
						},
						view : this
					},
					main_view = new ET_PageBuilder.ModalView( view_settings );

				et_pb_close_all_right_click_options();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				$( 'body' ).append( main_view.render().el );

				generate_templates_view( 'include_global', '', 'section', $( '.et-pb-saved-modules-tab' ), 'regular', 0, 'all' );

				event.preventDefault();
			},

			expandSection : function( event ) {
				event.preventDefault();

				var $parent = this.$el.closest('.et_pb_section');

				$parent.removeClass('et_pb_collapsed');

				// Add attribute to shortcode
				this.options.model.attributes.et_pb_collapsed = 'off';

				// Carousel effect for split testing subject
				if ( ET_PageBuilder_AB_Testing.is_active() && this.model.get( 'et_pb_ab_subject' ) === 'on' ) {
					ET_PageBuilder_AB_Testing.subject_carousel( this.model.get( 'cid' ) );
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'expanded', 'section' );

				// Rebuild shortcodes
				ET_PageBuilder_App.saveAsShortcode();
			},

			unlockSection : function( event ) {
				event.preventDefault();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				var this_el = this,
					$parent = this_el.$el.closest('.et_pb_section'),
					request = et_pb_user_lock_permissions(),
					children_views;

				request.done( function ( response ) {
					if ( true === response ) {
						$parent.removeClass('et_pb_locked');

						// Add attribute to shortcode
						this_el.options.model.attributes.et_pb_locked = 'off';

						children_views = ET_PageBuilder_Layout.getChildrenViews( this_el.model.get('cid') );

						_.each( children_views, function( view, key ) {
							view.$el.removeClass('et_pb_parent_locked');
							view.model.set( 'et_pb_parent_locked', 'off', { silent : true } );
						} );

						// Enable history saving and set meta for history
						ET_PageBuilder_App.allowHistorySaving( 'unlocked', 'section' );

						// Rebuild shortcodes
						ET_PageBuilder_App.saveAsShortcode();
					} else {
						alert( et_pb_options.locked_section_permission_alert );
					}
				});
			},

			addFirstRow: function() {
				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				var module_id = ET_PageBuilder_Layout.generateNewId(),
					global_parent = typeof this.model.get( 'et_pb_global_module' ) !== 'undefined' && '' !== this.model.get( 'et_pb_global_module' ) ? this.model.get( 'et_pb_global_module' ) : '',
					global_parent_cid = '' !== global_parent ? this.model.get( 'cid' ) : '',
					new_row_view;

				this.collection.add( [ {
					type : 'row',
					module_type : 'row',
					cid : module_id,
					parent : this.model.get( 'cid' ),
					view : this,
					et_pb_global_parent : global_parent,
					global_parent_cid : global_parent_cid,
					admin_label : et_pb_options.noun['row']
				} ] );
				new_row_view = ET_PageBuilder_Layout.getView( module_id );
				new_row_view.displayColumnsOptions();
			},

			addRow : function( appendAfter ) {
				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				var module_id = ET_PageBuilder_Layout.generateNewId(),
					global_parent = typeof this.model.get( 'et_pb_global_module' ) !== 'undefined' && '' !== this.model.get( 'et_pb_global_module' ) ? this.model.get( 'et_pb_global_module' ) : '',
					global_parent_cid = '' !== global_parent ? this.model.get( 'cid' ) : '',
					new_row_view;

				this.collection.add( [ {
					type : 'row',
					module_type : 'row',
					cid : module_id,
					parent : this.model.get( 'cid' ),
					view : this,
					appendAfter : appendAfter,
					et_pb_global_parent : global_parent,
					global_parent_cid : global_parent_cid,
					admin_label : et_pb_options.noun['row']
				} ] );
				new_row_view = ET_PageBuilder_Layout.getView( module_id );
				new_row_view.displayColumnsOptions();
			},

			cloneSection : function( event ) {
				event.preventDefault();

				if ( this.isSectionLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'section' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}

					if ( ET_PageBuilder_AB_Testing.has_goal( this.model ) && ! ET_PageBuilder_AB_Testing.is_subject( this.model ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_clone_section_has_goal' );
						return;
					}
				}

				var $cloned_element = this.$el.clone(),
					content,
					clone_section,
					view_settings = {
						model      : this.model,
						view       : this.$el,
						view_event : event
					};

				clone_section = new ET_PageBuilder.RightClickOptionsView( view_settings, true );

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'cloned', 'section' );

				clone_section.copy( event );

				clone_section.pasteAfter( event );
			},

			makeRowsSortable : function() {
				var this_el = this,
					sortable_el = this_el.model.get( 'et_pb_fullwidth' ) !== 'on'
						? '.et-pb-section-content'
						: '.et_pb_fullwidth_sortable_area',
					connectWith = ':not(.et_pb_locked) > ' + sortable_el;

				if ( this_el.model.get( 'et_pb_specialty' ) === 'on' ) {
					return;
				}

				// Split Testing adjustment
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Disable sortable of Split testing item for user with no ab_testing permission
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'section' ) ) {
						return;
					}
				}

				this_el.$el.find( sortable_el ).sortable( {
					connectWith: connectWith,
					delay: 100,
					cancel : '.et-pb-settings, .et-pb-clone, .et-pb-remove, .et-pb-row-add, .et-pb-insert-module, .et-pb-insert-column, .et-pb-insert-row, .et_pb_locked, .et-pb-disable-sort',
					update : function( event, ui ) {
						var $sortable_el = this_el.$el.find( sortable_el );

						// Loading process occurs. Dragging is temporarily disabled
						if ( ET_PageBuilder_App.isLoading ) {
							$sortable_el.sortable('cancel');
							return;
						}

						// Split Testing adjustment
						if ( ET_PageBuilder_AB_Testing.is_active() ) {
							// Check for permission user first
							if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( $( ui.item ).children('.et-pb-row-content').attr( 'data-cid' ), 'row' ) ) {
								ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
								$sortable_el.sortable('cancel');
								et_reinitialize_builder_layout();
								return;
							} else {
								// User has proper permission. Verify whether the action is permissible or not
								// IMPORTANT: update event is fired twice, once when the module is moved from its origin and once when the
								// module is landed on its destination. This causes two different way in deciding $sender and $target
								var $item       = $( ui.item ),
									$sender    = _.isEmpty( $( ui.sender ) ) ? $( event.target ).parents('.et_pb_section')  : $( ui.sender ).parents('.et_pb_section'),
									$target    = _.isEmpty( $( ui.sender ) ) ? $( event.toElement ).parents('.et_pb_section') : $( event.target ).parents('.et_pb_section'),
									is_subject  = $item.hasClass('et_pb_ab_subject'),
									is_goal     = $item.hasClass('et_pb_ab_goal'),
									has_subject = $item.find('.et_pb_ab_subject').length,
									has_goal    = $item.find('.et_pb_ab_goal').length,
									is_sender_inside_subject = $sender.closest('.et_pb_ab_subject').length,
									is_target_inside_subject = $target.closest('.et_pb_ab_subject').length,
									is_target_inside_goal = $target.closest('.et_pb_ab_goal').length;

								// Row is goal, being moved to subject-section
								if ( is_goal && ! is_subject && is_target_inside_subject ) {
									ET_PageBuilder_AB_Testing.alert( 'cannot_move_goal_into_subject');
									$sortable_el.sortable('cancel');
									et_reinitialize_builder_layout();
									return;
								}

								// Row has goal, being moved to subject-section
								if ( has_goal && is_target_inside_subject ) {
									ET_PageBuilder_AB_Testing.alert( 'cannot_move_goal_into_subject' );
									$sortable_el.sortable('cancel');
									et_reinitialize_builder_layout();
									return;
								}

								// Row is subject, being moved to goal-section
								if ( is_subject && ! is_goal && is_target_inside_goal ) {
									ET_PageBuilder_AB_Testing.alert( 'cannot_move_subject_into_goal');
									$sortable_el.sortable('cancel');
									et_reinitialize_builder_layout();
									return;
								}

								// Row has subject, being moved to goal-section
								if ( has_subject && is_target_inside_goal ) {
									ET_PageBuilder_AB_Testing.alert( 'cannot_move_subject_into_goal');
									$sortable_el.sortable('cancel');
									et_reinitialize_builder_layout();
									return;
								}

								// Row is a goal inside subject, being moved to anywhere
								if ( is_goal && is_sender_inside_subject ) {
									ET_PageBuilder_AB_Testing.alert( 'cannot_move_row_goal_out_from_subject');
									$sortable_el.sortable('cancel');
									et_reinitialize_builder_layout();
								}
							}
						}

						if ( ! $( ui.item ).closest( event.target ).length ) {

							// don't allow to move the row to another section if the section has only one row
							if ( ! $( event.target ).find( '.et_pb_row' ).length ) {
								$(this).sortable( 'cancel' );
								alert( et_pb_options.section_only_row_dragged_away );
							}

							// do not allow to drag rows into sections where sorting is disabled
							if ( $( ui.item ).closest( '.et-pb-disable-sort').length ) {
								$( event.target ).sortable( 'cancel' );
							}
							// makes sure the code runs one time, if row is dragged into another section
							return;

						}

						var module_cid = $( ui.item ).find( '.et-pb-row-content' ).data( 'cid' );
						var model = this_el.collection.find( function( model ) {
							return model.get('cid') == module_cid;
						} );

						if ( $( ui.item ).closest( '.et_pb_section.et_pb_global' ).length && $( ui.item ).hasClass( 'et_pb_global' ) ) {
							$( ui.sender ).sortable( 'cancel' );
							alert( et_pb_options.global_row_alert );
						} else if ( ( $( ui.item ).closest( '.et_pb_section.et_pb_global' ).length || $( ui.sender ).closest( '.et_pb_section.et_pb_global' ).length ) && '' === et_pb_options.template_post_id ) {
							var	global_module_cid,
								$moving_from,
								$moving_to;

							$moving_from = $( ui.sender ).closest( '.et_pb_section.et_pb_global' );
							$moving_to = $( ui.item ).closest( '.et_pb_section.et_pb_global' );

							if ( $moving_from === $moving_to ) {
								global_module_cid = model.get( 'global_parent_cid' );

								et_pb_update_global_template( global_module_cid );
								et_reinitialize_builder_layout();
							} else {
								var $global_element = $moving_from;

								// remove global parent attributes if moved not to global parent.
								if ( $moving_to.length === 0 && ( ( ! _.isUndefined( model.get( 'et_pb_global_parent' ) ) && '' !== model.get( 'et_pb_global_parent' ) ) || ! _.isUndefined( model.get( 'global_parent_cid' ) ) ) ) {
									model.unset( 'et_pb_global_parent' );
									model.unset( 'global_parent_cid' );
									// remove global attributes from all the child components
									ET_PageBuilder_Layout.removeGlobalAttributes( ET_PageBuilder_Layout.getView( model.get( 'cid' ) ) );
								}

								for ( var i = 1; i <= 2; i++ ) {
									global_module_cid = $global_element.find( '.et-pb-section-content' ).data( 'cid' );

									if ( typeof global_module_cid !== 'undefined' && '' !== global_module_cid ) {

										et_pb_update_global_template( global_module_cid );
										et_reinitialize_builder_layout();
									}

									$global_element = $moving_to;
								}
							}
						}

						ET_PageBuilder_Layout.setNewParentID( ui.item.find( '.et-pb-row-content' ).data( 'cid' ), this_el.model.attributes.cid );


						// Enable history saving and set meta for history
						ET_PageBuilder_App.allowHistorySaving( 'moved', 'row' );

						ET_PageBuilder_Events.trigger( 'et-sortable:update' );

						// Prepare collection sorting based on layout position
						var section_cid       = parseInt( $(this).attr( 'data-cid') ),
							sibling_row_index = 0;

						// Loop row block based on DOM position to ensure its index order
						$(this).find('.et-pb-row-content').each(function(){
							sibling_row_index++;

							var sibling_row_cid = parseInt( $(this).data('cid') ),
								layout_index    = section_cid + sibling_row_index,
								sibling_model   = ET_PageBuilder_Modules.findWhere({ cid : sibling_row_cid });

							// Set layout_index
							sibling_model.set({ layout_index : layout_index });
						});

						// Sort collection based on layout_index
						ET_PageBuilder_Modules.comparator = 'layout_index';
						ET_PageBuilder_Modules.sort();
					},
					start : function( event, ui ) {
						et_pb_close_all_right_click_options();

						// copy row if Alt key pressed
						if ( event.altKey ) {
							var movedRow = ET_PageBuilder_Layout.getView( $( ui.item ).children('.et-pb-row-content').data( 'cid' ) );
							var view_settings = {
								model      : movedRow.model,
								view       : movedRow.$el,
								view_event : event
							};
							var clone_row = new ET_PageBuilder.RightClickOptionsView( view_settings, true );

							clone_row.copy( event, true );

							clone_row.pasteAfter( event, undefined, undefined, undefined, true, true );

							// Enable history saving and set meta for history
							ET_PageBuilder_App.allowHistorySaving( 'cloned', 'row' );
						}
					}
				} );
			},

			addChildView : function( view ) {
				this.child_views.push( view );
			},

			removeChildViews : function() {
				var child_views = ET_PageBuilder_Layout.getChildViews( this.model.attributes.cid );

				_.each( child_views, function( view ) {
					if ( typeof view.model !== 'undefined' )
						view.model.destroy();

					view.remove();
				} );
			},

			removeSection : function( event, remove_all ) {
				var rows,
					remove_last_specialty_section = false;

				if ( event ) event.preventDefault();

				if ( this.isSectionLocked() || ET_PageBuilder_Layout.isChildrenLocked( this.model.get( 'cid' ) ) ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading && _.isUndefined( remove_all ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() && _.isUndefined( remove_all ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'section' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}

					if ( ET_PageBuilder_AB_Testing.is_unremovable_subject( this.model ) && _.isUndefined( remove_all ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						return;
					}

					if ( ET_PageBuilder_AB_Testing.has_goal( this.model ) && ! ET_PageBuilder_AB_Testing.is_subject( this.model ) && _.isUndefined( remove_all ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_remove_section_has_goal' );
						return;
					}

					if ( ET_PageBuilder_AB_Testing.has_unremovable_subject( this.model ) && _.isUndefined( remove_all ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_remove_section_has_unremovable_subject' );
						return;
					}
				}

				if ( this.model.get( 'et_pb_fullwidth' ) === 'on' ) {
					this.removeChildViews();
				} else {
					rows = ET_PageBuilder_Layout.getChildViews( this.model.get('cid') );

					_.each( rows, function( row ) {
						if ( row.model.get( 'type' ) === 'column' ) {
							// remove column in specialty section
							row.removeColumn();
						} else {
							row.removeRow( false, true );
						}
					} );
				}

				// the only section left is specialty or fullwidth section
				if ( ! ET_PageBuilder_Layout.get( 'forceRemove' ) && ( this.model.get( 'et_pb_specialty' ) === 'on' || this.model.get( 'et_pb_fullwidth' ) === 'on' ) && ET_PageBuilder_Layout.getNumberOfModules( 'section' ) === 1 ) {
					remove_last_specialty_section = true;
				}

				// if there is only one section, don't remove it
				// allow to remove all sections if removeSection function is called directly
				// remove the specialty section even if it's the last one on the page
				if ( ET_PageBuilder_Layout.get( 'forceRemove' ) || remove_last_specialty_section || ET_PageBuilder_Layout.getNumberOfModules( 'section' ) > 1 ) {
					this.model.destroy();

					ET_PageBuilder_Layout.removeView( this.model.get('cid') );

					this.remove();
				}

				// start with the clean layout if the user removed the last specialty section on the page
				if ( remove_last_specialty_section ) {
					ET_PageBuilder_App.removeAllSections( true );

					return;
				}

				// Enable history saving and set meta for history
				if ( _.isUndefined( remove_all ) ) {
					ET_PageBuilder_App.allowHistorySaving( 'removed', 'section' );
				} else {
					ET_PageBuilder_App.allowHistorySaving( 'cleared', 'layout' );
				}

				// trigger remove event if the row was removed manually ( using a button )
				if ( event ) {
					ET_PageBuilder_Events.trigger( 'et-module:removed' );
				}

				// Run Split Testing updater
				ET_PageBuilder_AB_Testing.update();
			},

			isSectionLocked : function() {
				if ( 'on' === this.model.get( 'et_pb_locked' ) ) {
					return true;
				}

				return false;
			},

			showRightClickOptions : function( event ) {
				event.preventDefault();

				var et_right_click_options_view,
					view_settings = {
						model      : this.model,
						view       : this.$el,
						view_event : event
					};

				et_right_click_options_view = new ET_PageBuilder.RightClickOptionsView( view_settings );
			},

			hideRightClickOptions : function( event ) {
				event.preventDefault();

				et_pb_close_all_right_click_options();
			},

			renameModule : function() {
				this.$( '.et-pb-section-title' ).html( this.model.get( 'admin_label' ) );
			},

			toggleDisabledClass : function() {
				if ( typeof this.model.get( 'et_pb_disabled' ) !== 'undefined' && 'on' === this.model.get( 'et_pb_disabled' ) ) {
					this.$el.addClass( 'et_pb_disabled' );
				} else {
					this.$el.removeClass( 'et_pb_disabled' );
				}
			},

			setABTesting : function ( event ) {
				event.preventDefault();
				event.stopPropagation();

				ET_PageBuilder_AB_Testing.set( this, event );
			}
		} );

		ET_PageBuilder.RowView = window.wp.Backbone.View.extend( {
			className : 'et_pb_row',

			template : _.template( $('#et-builder-row-template').html() ),

			events : {
				'click .et-pb-settings-row' : 'showSettings',
				'click .et-pb-insert-column' : 'displayColumnsOptions',
				'click .et-pb-clone-row' : 'cloneRow',
				'click .et-pb-row-add' : 'addNewRow',
				'click .et-pb-remove-row' : 'removeRow',
				'click .et-pb-change-structure' : 'changeStructure',
				'click .et-pb-expand' : 'expandRow',
				'contextmenu .et-pb-row-add' : 'showRightClickOptions',
				'click.et_pb_row > .et-pb-controls .et-pb-unlock' : 'unlockRow',
				'contextmenu.et_pb_row > .et-pb-controls' : 'showRightClickOptions',
				'contextmenu.et_pb_row > .et-pb-right-click-trigger-overlay' : 'showRightClickOptions',
				'contextmenu .et-pb-column' : 'showRightClickOptions',
				'click.et_pb_row > .et-pb-controls' : 'hideRightClickOptions',
				'click.et_pb_row > .et-pb-right-click-trigger-overlay' : 'hideRightClickOptions',
				'click > .et-pb-locked-overlay' : 'showRightClickOptions',
				'contextmenu > .et-pb-locked-overlay' : 'showRightClickOptions',
				'click' : 'setABTesting',
			},

			initialize : function() {
				this.listenTo( ET_PageBuilder_Events, 'et-add:columns', this.toggleInsertColumnButton );
				this.listenTo( this.model, 'change:admin_label', this.renameModule );
				this.listenTo( this.model, 'change:et_pb_disabled', this.toggleDisabledClass );
			},

			render : function() {
				var parent_views = ET_PageBuilder_Layout.getParentViews( this.model.get( 'parent' ) );

				if ( typeof this.model.get( 'view' ) !== 'undefined' && typeof this.model.get( 'view' ).model.get( 'layout_specialty' ) !== 'undefined' ) {
					this.model.set( 'specialty_row', '1', { silent : true } );
				}

				this.$el.html( this.template( this.model.toJSON() ) );

				if ( typeof this.model.get( 'et_pb_global_module' ) !== 'undefined' || ( typeof this.model.get( 'et_pb_template_type' ) !== 'undefined' && 'row' === this.model.get( 'et_pb_template_type' ) && 'global' === et_pb_options.is_global_template ) ) {
					this.$el.addClass( 'et_pb_global' );
				}

				if ( typeof this.model.get( 'et_pb_disabled' ) !== 'undefined' && this.model.get( 'et_pb_disabled' ) === 'on' ) {
					this.$el.addClass( 'et_pb_disabled' );
				}

				if ( typeof this.model.get( 'et_pb_locked' ) !== 'undefined' && this.model.get( 'et_pb_locked' ) === 'on' ) {
					this.$el.addClass( 'et_pb_locked' );

					_.each( parent_views, function( parent ) {
						parent.$el.addClass( 'et_pb_children_locked' );
					} );
				}

				if ( typeof this.model.get( 'et_pb_parent_locked' ) !== 'undefined' && this.model.get( 'et_pb_parent_locked' ) === 'on' ) {
					this.$el.addClass( 'et_pb_parent_locked' );
				}

				if ( typeof this.model.get( 'et_pb_collapsed' ) !== 'undefined' && this.model.get( 'et_pb_collapsed' ) === 'on' ) {
					this.$el.addClass( 'et_pb_collapsed' );
				}

				if ( typeof this.model.get( 'pasted_module' ) !== 'undefined' && this.model.get( 'pasted_module' ) ) {
					et_pb_handle_clone_class( this.$el );
				}

				if ( ET_PageBuilder_Layout.is_temp_global( this.model ) ) {
					this.$el.addClass( 'et_pb_global_temp' );
				}

				// Split Testing related class
				if ( ET_PageBuilder_AB_Testing.is_active() ) {
					if ( ET_PageBuilder_AB_Testing.is_subject( this.model ) ) {
						this.$el.addClass( 'et_pb_ab_subject' );

						// Apply subject rank coloring
						ET_PageBuilder_AB_Testing.set_subject_rank_coloring( this );
					}

					if ( ET_PageBuilder_AB_Testing.is_goal( this.model ) ) {
						this.$el.addClass( 'et_pb_ab_goal' );
					}

					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'row' ) ) {
						this.$el.addClass( 'et_pb_ab_no_permission' );
					}
				}

				return this;
			},

			showSettings : function( event ) {
				var that = this,
					modal_view,
					view_settings = {
						model : this.model,
						collection : this.collection,
						attributes : {
							'data-open_view' : 'module_settings'
						},
						triggered_by_right_click : this.triggered_by_right_click,
						do_preview : this.do_preview
					};

				if ( typeof event !== 'undefined' ) {
					event.preventDefault();
				}

				if ( this.isRowLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'row' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}
				}

				modal_view = new ET_PageBuilder.ModalView( view_settings );

				et_modal_view_rendered = modal_view.render();

				if ( false === et_modal_view_rendered ) {
					et_builder_load_backbone_templates( true );

					setTimeout( function() {
						that.showSettings();
					}, 500 );

					ET_PageBuilder_Events.trigger( 'et-pb-loading:started' );

					return;
				}

				ET_PageBuilder_Events.trigger( 'et-pb-loading:ended' );


				$('body').append( et_modal_view_rendered.el );

				if ( ( typeof modal_view.model.get( 'et_pb_global_module' ) !== 'undefined' && '' !== modal_view.model.get( 'et_pb_global_module' ) ) || ( ET_PageBuilder_Layout.getView( modal_view.model.get('cid') ).$el.closest( '.et_pb_global' ).length ) || ( typeof this.model.get( 'et_pb_template_type' ) !== 'undefined' && 'row' === this.model.get( 'et_pb_template_type' ) && 'global' === et_pb_options.is_global_template ) ) {
					$( '.et_pb_modal_settings_container' ).addClass( 'et_pb_saved_global_modal' );
				}
			},

			displayColumnsOptions : function( event ) {
				if ( event ) {
					event.preventDefault();
				}

				if ( this.isRowLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				var view,
					this_view = this;

				this.model.set( 'open_view', 'column_settings', { silent : true } );

				view = new ET_PageBuilder.ModalView( {
					model : this.model,
					collection : this.collection,
					attributes : {
						'data-open_view' : 'column_settings'
					},
					view : this_view
				} );

				$('body').append( view.render().el );

				this.toggleInsertColumnButton();
			},

			changeStructure : function( event ) {
				event.preventDefault();

				var view,
					this_view = this;

				if ( this.isRowLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'row' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}
				}

				this.model.set( 'change_structure', 'true', { silent : true } );

				this.model.set( 'open_view', 'column_settings', { silent : true } );

				ET_PageBuilder.Events = ET_PageBuilder_Events;
				view = new ET_PageBuilder.ModalView( {
					model : this.model,
					collection : this.collection,
					attributes : {
						'data-open_view' : 'column_settings'
					},
					view : this_view
				} );

				$('body').append( view.render().el );
			},

			expandRow : function( event ) {
				event.preventDefault();

				var $parent = this.$el.closest('.et_pb_row');

				$parent.removeClass('et_pb_collapsed');

				// Add attribute to shortcode
				this.options.model.attributes.et_pb_collapsed = 'off';

				// Carousel effect for split testing subject
				if ( ET_PageBuilder_AB_Testing.is_active() && this.model.get( 'et_pb_ab_subject' ) === 'on' ) {
					ET_PageBuilder_AB_Testing.subject_carousel( this.model.get( 'cid' ) );
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'expanded', 'row' );

				// Rebuild shortcodes
				ET_PageBuilder_App.saveAsShortcode();
			},

			unlockRow : function( event ) {
				event.preventDefault();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				var this_el = this,
					$parent = this_el.$el.closest('.et_pb_row'),
					request = et_pb_user_lock_permissions(),
					children_views,
					parent_views;

				request.done( function ( response ) {
					if ( true === response ) {
						$parent.removeClass('et_pb_locked');

						// Add attribute to shortcode
						this_el.options.model.attributes.et_pb_locked = 'off';

						children_views = ET_PageBuilder_Layout.getChildrenViews( this_el.model.get('cid') );

						_.each( children_views, function( view, key ) {
							view.$el.removeClass('et_pb_parent_locked');
							view.model.set( 'et_pb_parent_locked', 'off', { silent : true } );
						} );

						parent_views = ET_PageBuilder_Layout.getParentViews( this_el.model.get('parent') );

						_.each( parent_views, function( view, key ) {
							if ( ! ET_PageBuilder_Layout.isChildrenLocked( view.model.get( 'cid' ) ) ) {
								view.$el.removeClass('et_pb_children_locked');
							}
						} );

						// Enable history saving and set meta for history
						ET_PageBuilder_App.allowHistorySaving( 'unlocked', 'row' );

						// Rebuild shortcodes
						ET_PageBuilder_App.saveAsShortcode();
					} else {
						alert( et_pb_options.locked_row_permission_alert );
					}
				});
			},

			toggleInsertColumnButton : function() {
				// Manually added row inner (ie empty specialty section's specialty column) has no model
				if (typeof this.model === 'undefined') {
					return;
				}

				var model_id = this.model.get( 'cid' ),
					columnsInRow;

				// check if the current row has at least one column
				columnsInRow = this.collection.find( function( model ) {
					return ( model.get( 'type' ) === 'column' || model.get( 'type' ) === 'column_inner' ) && model.get( 'parent' ) === model_id;
				} );

				if ( ! _.isUndefined( columnsInRow ) ) {
					this.$( '.et-pb-insert-column' ).hide();

					// show "change columns structure" icon, if current row's column layout is set
					this.$( '.et-pb-change-structure' ).show();
				}
			},

			addNewRow : function( event ) {
				var $parent_section = this.$el.closest( '.et-pb-section-content' ),
					$current_target = $( event.currentTarget ),
					parent_view_cid = $current_target.closest( '.et-pb-column-specialty' ).length ? $current_target.closest( '.et-pb-column-specialty' ).data( 'cid' ) : $parent_section.data( 'cid' ),
					parent_view = ET_PageBuilder_Layout.getView( parent_view_cid ),
					global_module_cid = '';

				event.preventDefault();

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				et_pb_close_all_right_click_options();

				if ( 'on' === this.model.get( 'et_pb_parent_locked' ) ) {
					return;
				}

				if ( this.$el.closest( '.et_pb_section.et_pb_global' ).length && typeof parent_view.model.get( 'et_pb_template_type' ) === 'undefined' ) {
					global_module_cid = et_pb_get_global_parent_cid( this );
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ), 'add_row' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'added', 'row' );

				parent_view.addRow( this.$el );

				if ( '' !== global_module_cid ) {
					et_pb_update_global_template( global_module_cid );
				}
			},

			cloneRow : function( event ) {
				var global_module_cid = '',
					parent_view = ET_PageBuilder_Layout.getView( this.model.get( 'parent' ) ),
					clone_row,
					view_settings = {
						model      : this.model,
						view       : this.$el,
						view_event : event
					};

				event.preventDefault();

				if ( this.isRowLocked() ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ) ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}

					// Row with goal (unless the row is subject) cannot be cloned
					if ( ET_PageBuilder_AB_Testing.has_goal( this.model ) && ! ET_PageBuilder_AB_Testing.is_subject( this.model ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_clone_row_has_goal' );
						return;
					}
				}

				if ( this.$el.closest( '.et_pb_section.et_pb_global' ).length && typeof parent_view.model.get( 'et_pb_template_type' ) === 'undefined' ) {
					global_module_cid = et_pb_get_global_parent_cid( this );
				}

				clone_row = new ET_PageBuilder.RightClickOptionsView( view_settings, true );

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'cloned', 'row' );

				clone_row.copy( event );

				clone_row.pasteAfter( event );

				if ( '' !== global_module_cid ) {
					et_pb_update_global_template( global_module_cid );
				}
			},

			removeRow : function( event, force ) {
				var columns,
					global_module_cid = '',
					parent_view = ET_PageBuilder_Layout.getView( this.model.get( 'parent' ) );

				if ( this.isRowLocked() || ET_PageBuilder_Layout.isChildrenLocked( this.model.get( 'cid' ) ) ) {
					return;
				}

				if ( ET_PageBuilder_App.isLoading ) {
					return;
				}

				if ( ET_PageBuilder_AB_Testing.is_selecting() && _.isUndefined( force ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
					return;
				}

				// Split Testing-related action
				if ( ET_PageBuilder_AB_Testing.is_active() ) {

					// Check for user permission and module status
					if ( ! ET_PageBuilder_AB_Testing.is_user_has_permission( this.model.get( 'cid' ) ) ) {
						ET_PageBuilder_AB_Testing.alert( 'has_no_permission' );
						return;
					}

					if ( ET_PageBuilder_AB_Testing.is_unremovable_subject( this.model ) && _.isUndefined( force ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						return;
					}

					if ( ET_PageBuilder_AB_Testing.has_goal( this.model ) && ! ET_PageBuilder_AB_Testing.is_subject( this.model ) && _.isUndefined( force ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_remove_row_has_goal' );
						return;
					}

					if ( ET_PageBuilder_AB_Testing.has_unremovable_subject( this.model ) && _.isUndefined( force ) && ! ET_PageBuilder_Layout.get( 'forceRemove' ) ) {
						ET_PageBuilder_AB_Testing.alert( 'cannot_remove_row_has_unremovable_subject' );
						return;
					}
				}

				if ( event ) {
					event.preventDefault();

					// don't allow to remove a specialty section, even if there is only one row in it
					if ( this.$el.closest( '.et-pb-column-specialty' ).length ) {
						event.stopPropagation();
					}

					if ( this.$el.closest( '.et_pb_section.et_pb_global' ).length && typeof parent_view.model.get( 'et_pb_template_type' ) === 'undefined' ) {
						global_module_cid = et_pb_get_global_parent_cid( this );
					}
				}

				columns = ET_PageBuilder_Layout.getChildViews( this.model.get('cid') );

				_.each( columns, function( column ) {
					column.removeColumn();
				} );

				// if there is only one row in the section, don't remove it
				if ( ET_PageBuilder_Layout.get( 'forceRemove' ) || ET_PageBuilder_Layout.getNumberOf( 'row', this.model.get('parent') ) > 1 ) {
					this.model.destroy();

					ET_PageBuilder_Layout.removeView( this.model.get('cid') );

					this.remove();
				} else {
					this.$( '.et-pb-insert-column' ).show();

					// hide "change columns structure" icon, column layout can be re-applied using "Insert column(s)" button
					this.$( '.et-pb-change-structure' ).hide();
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'removed', 'row' );

				// trigger remove event if the row was removed manually ( using a button )
				if ( event ) {
					ET_PageBuilder_Events.trigger( 'et-module:removed' );
				}

				if ( '' !== global_module_cid ) {
					et_pb_update_global_template( global_module_cid );
				}

				// Run Split Testing updater
				ET_PageBuilder_AB_Testing.update();
			},

			isRowLocked : function() {
				if ( 'on' === this.model.get( 'et_pb_locked' ) || 'on' === this.model.get( 'et_pb_parent_locked' ) ) {
					return true;
				}

				return false;
			},

			showRightClickOptions : function( event ) {
				event.preventDefault();
				var $event_target = $( event.target ),
					et_right_click_options_view,
					view_settings;

				// Do nothing if Module or "Insert Module" clicked
				if ( $event_target.closest( '.et-pb-insert-module' ).length || $event_target.closest('.et-pb-insert-row').length || $event_target.hasClass( 'et_pb_module_block' ) || $event_target.closest( '.et_pb_module_block' ).length ) {
					return;
				}

				et_right_click_options_view,
				view_settings = {
					model      : this.model,
					view       : this.$el,
					view_event : event
				};

				et_right_click_options_view = new ET_PageBuilder.RightClickOptionsView( view_settings );
			},

			hideRightClickOptions : function( event ) {
				event.preventDefault();

				et_pb_close_all_right_click_options();
			},

			renameModule : function() {
				this.$( '.et-pb-row-title' ).html( this.model.get( 'admin_label' ) );
			},

			toggleDisabledClass : function() {
				if ( typeof this.model.get( 'et_pb_disabled' ) !== 'undefined' && 'on' === this.model.get( 'et_pb_disabled' ) ) {
					this.$el.addClass( 'et_pb_disabled' );
				} else {
					this.$el.removeClass( 'et_pb_disabled' );
				}
			},

			setABTesting : function ( event ) {
				event.preventDefault();
				event.stopPropagation();

				ET_PageBuilder_AB_Testing.set( this, event );
			}
		} );

		ET_PageBuilder.ModalView = window.wp.Backbone.View.extend( {

			className : 'et_pb_modal_settings_container',

			template : _.template( $('#et-builder-modal-template').html() ),

			events : {
				'click .et-pb-modal-save' : 'saveSettings',
				'click .et-pb-modal-preview-template' : 'preview',
				'click .et-pb-preview-mobile' : 'resizePreviewScreen',
				'click .et-pb-preview-tablet' : 'resizePreviewScreen',
				'click .et-pb-preview-desktop' : 'resizePreviewScreen',
				'click .et-pb-modal-close' : 'closeModal',
				'click .et-pb-modal-save-template' : 'saveTemplate',
				'change #et_pb_select_category' : 'applyFilter'
			},

			initialize : function( attributes ) {
				this.listenTo( ET_PageBuilder_Events, 'et-add:columns', this.removeView );

				// listen to module settings box that is created after the user selects new module to add
				this.listenTo( ET_PageBuilder_Events, 'et-new_module:show_settings', this.removeView );

				this.listenTo( ET_PageBuilder_Events, 'et-saved_layout:loaded', this.removeView );

				this.options = attributes;
			},

			render : function() {
				var view,
					view_settings = {
						model : this.model,
						collection : this.collection,
						view : this.options.view
					},
					fake_value = false;

				this.$el.attr( 'tabindex', 0 ); // set tabindex to make the div focusable

				// update the row view if it has been dragged into another column
				if ( typeof this.model !== 'undefined' && typeof this.model.get( 'view' ) !== 'undefined' && ( this.model.get( 'module_type' ) === 'row_inner' || this.model.get( 'module_type' ) === 'row' ) && this.model.get( 'parent' ) !== this.model.get( 'view' ).$el.data( 'cid' ) ) {
					this.model.set( 'view', ET_PageBuilder_Layout.getView( this.model.get( 'parent' ) ), { silent : true } );
				}

				if ( this.attributes['data-open_view'] === 'all_modules' && this.model.get( 'module_type' ) === 'section' && this.model.get( 'et_pb_fullwidth' ) === 'on' ) {
					this.model.set( 'type', 'column', { silent : true } );
					fake_value = true;
				}

				if ( typeof this.model !== 'undefined' ) {
					var this_parent_view = ET_PageBuilder_Layout.getView( this.model.get( 'parent' ) );

					if ( this.attributes['data-open_view'] === 'column_specialty_settings' ) {
						this.model.set( 'open_view', 'column_specialty_settings', { silent : true } );
					}

					this.$el.html( this.template( this.model.toJSON() ) );

					if ( this.attributes['data-open_view'] === 'column_specialty_settings' ) {
						this.model.unset( 'open_view', 'column_specialty_settings', { silent : true } );
					}
				}
				else
					this.$el.html( this.template() );

				if ( fake_value )
					this.model.set( 'type', 'section', { silent : true } );

				this.container = this.$('.et-pb-modal-container');

				if ( this.attributes['data-open_view'] === 'column_settings' ) {
					view = new ET_PageBuilder.ColumnSettingsView( view_settings );
				} else if ( this.attributes['data-open_view'] === 'all_modules' ) {
					view_settings['attributes'] = {
						'data-parent_cid' : this.model.get( 'cid' )
					}

					view = new ET_PageBuilder.ModulesView( view_settings );
				} else if ( this.attributes['data-open_view'] === 'module_settings' ) {
					var this_module_type = this.model.get( 'module_type' );

					// check the Row parent and if it's inside the column then change the type Row to Row Inner.
					if ( 'row' === this_module_type && ! _.isUndefined( this_parent_view ) && 'column' === this_parent_view.model.get('type') ) {
						this_module_type = 'row_inner';
					}

					view_settings['attributes'] = {
						'data-module_type' : this_module_type
					}

					view_settings['view'] = this;

					view = new ET_PageBuilder.ModuleSettingsView( view_settings );
				} else if ( this.attributes['data-open_view'] === 'save_layout' ) {
					view = new ET_PageBuilder.SaveLayoutSettingsView( view_settings );
				} else if ( this.attributes['data-open_view'] === 'column_specialty_settings' ) {
					view = new ET_PageBuilder.ColumnSettingsView( view_settings );
				} else if ( this.attributes['data-open_view'] === 'saved_templates' ) {
					view = new ET_PageBuilder.TemplatesModal( { attributes: { 'data-parent_cid' : this.attributes['data-parent_cid'] } } );
				} else if ( this.attributes['data-open_view'] === 'help' ) {
					view = new ET_PageBuilder.HelpView();
				}
				// do not proceed and return false if no template for this module exist yet
				if ( typeof view.attributes !== 'undefined' && 'no_template' === view.attributes['data-no_template'] ) {
					return false;
				}

				this.container.append( view.render().el );

				if ( this.attributes['data-open_view'] === 'column_settings' ) {
					// if column settings layout was generated, remove open_view attribute from a row
					// the row module modal window shouldn't have this attribute attached
					this.model.unset( 'open_view', { silent : true } );
				}

				// show only modules that the current element can contain
				if ( this.attributes['data-open_view'] === 'all_modules' ) {
					if ( this.model.get( 'module_type' ) === 'section' && typeof( this.model.get( 'et_pb_fullwidth' ) !== 'undefined' ) && this.model.get( 'et_pb_fullwidth' ) === 'on' ) {
						$( view.render().el ).find( '.et-pb-all-modules li:not(.et_pb_fullwidth_only_module)' ).remove();
					} else {
						$( view.render().el ).find( 'li.et_pb_fullwidth_only_module' ).remove();
					}
				}

				if ( $( '.et_pb_modal_overlay' ).length ) {
					$( '.et_pb_modal_overlay' ).remove();
					$( 'body' ).removeClass( 'et_pb_stop_scroll' );
				}

				if ( $( 'body' ).hasClass( 'et_pb_modal_fade_in' ) ) {
					$( 'body' ).append( '<div class="et_pb_modal_overlay et_pb_no_animation"></div>' );
				} else {
					$( 'body' ).append( '<div class="et_pb_modal_overlay"></div>' );
				}

				$( 'body' ).addClass( 'et_pb_stop_scroll' );

				return this;
			},

			closeModal : function( event ) {
				event.preventDefault();

				if ( $( '.et_modal_on_top' ).length ) {
					$( '.et_modal_on_top' ).remove();
				} else {

					if ( typeof this.model !== 'undefined' && this.model.get( 'type' ) === 'module' && this.$( '#et_pb_content_new' ).length )
						et_pb_tinymce_remove_control( 'et_pb_content_new' );

					et_pb_hide_active_color_picker( this );

					et_pb_close_modal_view( this, 'trigger_event' );
				}
			},

			removeView : function() {
				if ( typeof this.model === 'undefined' || ( this.model.get( 'type' ) === 'row' || this.model.get( 'type' ) === 'column' || this.model.get( 'type' ) === 'row_inner' || this.model.get( 'type' ) === 'column_inner' || ( this.model.get( 'type' ) === 'section' && ( this.model.get( 'et_pb_fullwidth' ) === 'on' || this.model.get( 'et_pb_specialty' ) === 'on' ) ) ) ) {
					if ( typeof this.model !== 'undefined' && typeof this.model.get( 'type' ) !== 'undefined' && ( this.model.get( 'type' ) === 'column' || this.model.get( 'type' ) === 'column_inner' || ( this.model.get( 'type' ) === 'section' &&  this.model.get( 'et_pb_fullwidth' ) === 'on' ) ) ) {
						var that = this,
							$opened_tab = $( that.el ).find( '.et-pb-main-settings.active-container' );

						// if we're adding module from library, then close everything. Otherwise leave overlay in place and add specific classes
						if ( $opened_tab.hasClass( 'et-pb-saved-modules-tab' ) ) {
							et_pb_close_modal_view( that );
						} else {
							that.remove();

							$( 'body' ).addClass( 'et_pb_modal_fade_in' );
							$( '.et_pb_modal_overlay' ).addClass( 'et_pb_no_animation' );
							setTimeout( function() {
								$( '.et_pb_modal_settings_container' ).addClass( 'et_pb_no_animation' );
								$( 'body' ).removeClass( 'et_pb_modal_fade_in' );
							}, 500);
						}
					} else {
						et_pb_close_modal_view( this );
					}
				} else {
					this.removeOverlay();
				}
			},

			saveSettings : function( event, close_modal ) {
				var that = this,
					global_module_cid = '',
					this_view = ET_PageBuilder_Layout.getView( that.model.get( 'cid' ) ),
					this_parent_view = ! _.isUndefined( that.model.get( 'parent' ) ) ? ET_PageBuilder_Layout.getView( that.model.get( 'parent' ) ) : '',
					global_holder_view = this_view,
					update_template_only = false,
					close_modal = _.isUndefined( close_modal ) ? true : close_modal,
					is_disabled = 'not-allowed' === $( event.target ).css( 'cursor' );

				// get the global module ID if exists.
				if ( ! _.isUndefined( global_holder_view.model.get( 'et_pb_global_module' ) )  ) {
					// if the module is global
					global_module_cid = global_holder_view.model.get( 'cid' );
				} else {
					// check all parents to find the global parent if exists
					while ( ! _.isUndefined( global_holder_view.model.get( 'parent' ) ) && '' === global_module_cid ) {
						// Refresh global holder for new loop
						global_holder_view = ET_PageBuilder_Layout.getView( global_holder_view.model.get( 'parent' ) );
						global_module_cid = '' === global_module_cid && ! _.isUndefined( global_holder_view.model.get( 'et_pb_global_module' ) ) ? global_holder_view.model.get( 'cid' ) : global_module_cid;
					}
				}

				event.preventDefault();

				if ( is_disabled ) {
					return;
				}

				// Disabling state and mark it. It takes a while for generating shortcode,
				// so ensure that user doesn't update the page before shortcode generation has completed
				$('#publish').addClass( 'disabled' );

				ET_PageBuilder_App.disable_publish = true;

				if ( ( typeof global_holder_view.model.get( 'global_parent_cid' ) !== 'undefined' && '' !== global_holder_view.model.get( 'global_parent_cid' ) ) || ( typeof global_holder_view.model.get( 'et_pb_global_module' ) !== 'undefined' && '' !== global_holder_view.model.get( 'et_pb_global_module' ) ) ) {
					global_module_cid = typeof global_holder_view.model.get( 'global_parent_cid' ) !== 'undefined' ? global_holder_view.model.get( 'global_parent_cid' ) : global_holder_view.model.get( 'cid' );
				}

				that.performSaving();

				if ( '' !== global_module_cid ) {
					et_pb_update_global_template( global_module_cid );
				}

				// Enable history saving and set meta for history
				ET_PageBuilder_App.allowHistorySaving( 'edited', that.model.get( 'type' ), that.model.get( 'admin_label' ) );

				// In some contexts, closing modal view isn't needed & only settings saving needed
				if ( ! close_modal ) {
					return;
				}

				et_pb_tinymce_remove_control( 'et_pb_content_new' );

				et_pb_hide_active_color_picker( that );

				et_pb_close_modal_view( that, 'trigger_event' );

				if ( ET_PageBuilder_AB_Testing.is_active() ) {
					// Update subject rank coloring and subject ID
					ET_PageBuilder_AB_Testing.set_subject_rank_coloring( this_view );
				}
			},

			preview : function( event ) {
				var cid          = this.model.get( 'cid' ) ,
					shortcode,
					$button      = $( event.target ).is( 'a' ) ? $( event.target ) : $( event.target ).parent( 'a' ),
					$container   = $( event.target ).parents( '.et-pb-modal-container' ),
					request_data,
					section_view,
					msie         = document.documentMode;

				event.preventDefault();

				// Save modified settings, if it is necesarry. Direct preview from right click doesn't need to be saved
				if ( _.isUndefined( this.options.triggered_by_right_click ) ) {
					this.saveSettings( event, false );
				} else {
					// Triggered by right click is one time thing. Remove it as soon as it has been used
					delete this.options.triggered_by_right_click;
				}

				if ( ! _.isUndefined( this.options.do_preview ) ) {
					// Do preview is one time thing. Remove it as soon as it has been used
					delete this.options.do_preview;
				}

				if ( et_pb_options.is_divi_library === "1" && $.inArray( et_pb_options.layout_type, [ "row", "module" ] ) > -1 ) {
					// Divi Library's layout editor auto generates section and row in module and row layout type
					// The auto generates item cause cause an issue during shortcode generation
					// Removing its cid will force ET_PageBuilder_App.generateCompleteShortcode to generate the whole page's layout shortcode which solves the preview issue
					cid = undefined;
				} else if ( this.model.get( 'type' ) !== 'section' ) {
					// Module's layout depends on the column it belongs. Hence, always preview the item in context of section
					section_view = ET_PageBuilder_Layout.getSectionView( this.model.get( 'parent' ) );

					if ( ! _.isUndefined( section_view ) ) {
						cid = section_view.model.attributes.cid;
					}
				}

				// Get shortcode based on section's cid
				shortcode = ET_PageBuilder_App.generateCompleteShortcode( cid );

				request_data = {
					et_pb_preview_nonce : et_pb_options.et_pb_preview_nonce,
					shortcode           : shortcode,
					post_title          : $('#title').val()
				};

				// Toggle button state
				$button.toggleClass( 'active' );

				// Toggle container state
				$container.toggleClass( 'et-pb-item-previewing' );

				if ( $button.hasClass( 'active' ) ) {
					// Create the iFrame on the fly. This will speed up modalView init
					var $iframe = $('<iframe />', {
								 	id : 'et-pb-preview-screen',
								 	src : et_pb_options.preview_url + '&et_pb_preview_nonce=' + et_pb_options.et_pb_preview_nonce
								 } ),
						has_render_page = false;

					// Add the iframe into preview tab
					 $('.et-pb-preview-tab' ).html( $iframe );

					 // Pass the item's setup to the screen
					 $('#et-pb-preview-screen').load( function(){
					 	if ( has_render_page ) {
					 		return;
					 	}

					 	// Get iFrame
						preview = document.getElementById( 'et-pb-preview-screen' );

						// IE9 below fix. They have postMessage, but it has to be in string
						if ( ! _.isUndefined( msie ) && msie < 10 ) {
							request_data = JSON.stringify( request_data );
						}

						// Pass shortcode structure to iFrame to be displayed
						preview.contentWindow.postMessage( request_data, et_pb_options.preview_url );

						has_render_page = true;
					 });
				} else {
					$( '.et-pb-preview-tab' ).empty();

					// Reset active state
					$('.et-pb-preview-screensize-switcher a').removeClass( 'active' );

					// Set desktop as active
					$('.et-pb-preview-desktop').addClass( 'active' );
				}
			},

			resizePreviewScreen : function( event ) {
				event.preventDefault();

				var $link = $( event.target ),
					width = _.isUndefined( $link.data( 'width' ) ) ? '100%' : $link.data( 'width' );

				// Reset active state
				$('.et-pb-preview-screensize-switcher a').removeClass( 'active' );

				// Set current as active
				$link.addClass( 'active' );

				// Set iFrame width
				$('#et-pb-preview-screen').animate({
					'width' : width
				});
			},

			getAttr: function( object, name ) {
				return _.isUndefined( object[ name ] ) ? '' : object[ name ];
			},

			performSaving : function( option_tabs_selector ) {
				var thisClass  = this,
					attributes = {},
					unsetAttrs = [],
					defaults   = {},
					options_selector = typeof option_tabs_selector !== 'undefined' && '' !== option_tabs_selector ? option_tabs_selector : 'input, select, textarea, #et_pb_content_main';

				var $et_form_validation;
				$et_form_validation = $(this)[0].$el.find('form.validate');
				if ( $et_form_validation.length ) {
					validator = $et_form_validation.validate();
					if ( !validator.form() ) {
						et_builder_debug_message('failed form validation');
						et_builder_debug_message('failed elements: ');
						et_builder_debug_message( validator.errorList );
						validator.focusInvalid();
						return;
					}
					et_builder_debug_message('passed form validation');
				}

				var global_module_id = 'global' === et_pb_options.is_global_template ? et_pb_options.template_post_id : this.model.get( 'et_pb_global_module' );

				// update the unsynced options for the module
				if ( $( '.et_pb_global_sync_switcher' ).length > 0 && typeof global_module_id !== 'undefined' && '' !== global_module_id ) {
					var unsynced_options_array = [];

					if ( $( '.et_pb_global_unsynced' ).length > 0 ) {
						$( '.et_pb_global_unsynced' ).each( function() {
							var $this_el = $( this );
							var this_option_name = $this_el.data( 'option_name' );

							unsynced_options_array.push( this_option_name );

							// unsync mobile options if exist
							if ( ! _.isUndefined( $this_el.data( 'additional_options' ) ) && 'mobile' === $this_el.data( 'additional_options' ) ) {
								unsynced_options_array.push( this_option_name + '_tablet' );
								unsynced_options_array.push( this_option_name + '_phone' );
							}
						});
					}

					// Automatically sync/unsync gallery_ids and gallery_orderby on gallery module if src is synced/unsynced
					if ( 'et_pb_gallery' === thisClass.model.get( 'module_type' ) ) {
						if ( _.contains( unsynced_options_array, 'src' ) ) {
							unsynced_options_array = _.union( unsynced_options_array, [ 'gallery_ids', 'gallery_orderby' ] );
						} else {
							unsynced_options_array = _.without( unsynced_options_array, 'gallery_ids', 'gallery_orderby' );
						}
					}

					et_pb_all_unsynced_options[ global_module_id ] = unsynced_options_array;

					// update the value in hidden option so unsynced options will be saved on post Update.
					if ( 'global' === et_pb_options.is_global_template && $( '#et_pb_unsynced_global_attrs' ).length !== 0 ) {
						$( '#et_pb_unsynced_global_attrs' ).val( JSON.stringify( unsynced_options_array ) );
					}
				}

				ET_PageBuilder.Events.trigger( 'et-modal-settings:save', this );

				this.$( options_selector ).each( function() {
					var $this_el = $(this),
						setting_value,
						checked_values = [],
						name = $this_el.is('#et_pb_content_main') ? 'et_pb_content_new' : $this_el.attr('id'),
						default_value = et_pb_get_default_setting_value($this_el) || '',
						custom_css_option_value,
						isEqualToDefault = function (v1, v2) {
							return $this_el.hasClass('et-pb-range-input')
								? _.isEqual(parseFloat(v1), parseFloat(v2))
								: _.isEqual(v1, v2);
						};

					// name attribute is used in normal html checkboxes, use it instead of ID
					if ( $this_el.is( ':checkbox' ) ) {
						name = $this_el.attr('name');
					}