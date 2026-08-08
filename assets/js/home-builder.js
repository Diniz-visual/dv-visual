/**
 * DV Visual Home Builder
 *
 * The builder has two connected drop zones:
 * - Blocos ativos: visible on the public Home, in the displayed order.
 * - Todos os blocos: saved but hidden from the public Home.
 *
 * Cards can move with mouse, touch, the +/- button or keyboard-friendly
 * up/down controls. Every change saves order and visibility together.
 */
( function () {
	'use strict';

	if ( ! window.DVHomeBuilder ) {
		return;
	}

	var config = window.DVHomeBuilder;
	var activeList = document.getElementById( 'dv-home-active' );
	var inactiveList = document.getElementById( 'dv-home-inactive' );
	var lists = [ activeList, inactiveList ].filter( Boolean );
	var notice = document.getElementById( 'dv-builder-notice' );
	var saveState = document.getElementById( 'dv-builder-save-state' );
	var preview = document.getElementById( 'dv-home-preview' );
	var library = document.getElementById( 'dv-builder-library' );
	var addButton = document.getElementById( 'dv-builder-add' );
	var dragging = null;
	var touchDragging = null;
	var layoutChanged = false;
	var saveQueue = Promise.resolve();

	function cardsIn( list ) {
		return list ? Array.prototype.filter.call( list.children, function ( child ) {
			return child.hasAttribute( 'data-section-id' );
		} ) : [];
	}

	function sectionIds( list ) {
		return cardsIn( list ).map( function ( card ) {
			return card.getAttribute( 'data-section-id' );
		} );
	}

	function setState( text, isError ) {
		if ( saveState ) {
			saveState.textContent = text;
			saveState.classList.toggle( 'is-error', Boolean( isError ) );
		}
	}

	function showNotice( text, isError ) {
		if ( ! notice ) {
			return;
		}

		notice.textContent = text;
		notice.classList.toggle( 'is-visible', Boolean( text ) );
		notice.classList.toggle( 'is-error', Boolean( isError ) );

		if ( text ) {
			window.setTimeout( function () {
				notice.classList.remove( 'is-visible' );
			}, 2800 );
		}
	}

	function request( data ) {
		var body = new window.URLSearchParams();
		Object.keys( data ).forEach( function ( key ) {
			body.append( key, data[ key ] );
		} );
		body.append( 'nonce', config.nonce );

		return window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( response ) {
			return response.json().then( function ( result ) {
				if ( ! response.ok || ! result.success ) {
					var message = result && result.data && result.data.message ? result.data.message : config.error;
					throw new Error( message );
				}
				return result.data || {};
			} );
		} );
	}

	function refreshPreview() {
		if ( preview && preview.contentWindow ) {
			preview.contentWindow.location.reload();
		}
	}

	function updateCardState( card, isActive ) {
		var label = card.querySelector( '.dv-home-builder__visibility-label' );
		var toggle = card.querySelector( '[data-toggle-section]' );
		var icon = toggle ? toggle.querySelector( '.dashicons' ) : null;

		card.classList.toggle( 'is-active', isActive );
		card.classList.toggle( 'is-inactive', ! isActive );
		card.setAttribute( 'data-section-active', isActive ? 'true' : 'false' );

		if ( label ) {
			label.textContent = isActive ? config.visible : config.hidden;
		}

		if ( toggle ) {
			toggle.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
			toggle.setAttribute( 'aria-label', isActive ? config.hideSection : config.showSection );
		}

		if ( icon ) {
			icon.classList.toggle( 'dashicons-remove', isActive );
			icon.classList.toggle( 'dashicons-plus-alt2', ! isActive );
		}
	}

	function syncLinkedAdminMenus() {
		var map = config.sectionContentTypes || {};
		var states = {};
		var allCards = cardsIn( activeList ).concat( cardsIn( inactiveList ) );

		Object.keys( map ).forEach( function ( sectionKey ) {
			var linkedTypes = Array.isArray( map[ sectionKey ] ) ? map[ sectionKey ] : [];
			var matchingCards = allCards.filter( function ( card ) {
				return sectionKey === card.getAttribute( 'data-section-key' );
			} );

			linkedTypes.forEach( function ( postType ) {
				if ( ! states[ postType ] ) {
					states[ postType ] = { found: false, active: false };
				}

				if ( matchingCards.length ) {
					states[ postType ].found = true;
					states[ postType ].active = states[ postType ].active || matchingCards.some( function ( card ) {
						return activeList && activeList.contains( card );
					} );
				}
			} );
		} );

		Object.keys( states ).forEach( function ( postType ) {
			var visible = ! states[ postType ].found || states[ postType ].active;
			if ( 'function' === typeof window.dvSetHomeContentMenuVisibility ) {
				window.dvSetHomeContentMenuVisibility( postType, visible );
			}
		} );
	}

	function updateInterface() {
		cardsIn( activeList ).forEach( function ( card, index ) {
			var number = card.querySelector( '.dv-home-builder__order' );
			if ( number ) {
				number.textContent = String( index + 1 ).padStart( 2, '0' );
			}
			updateCardState( card, true );
		} );

		cardsIn( inactiveList ).forEach( function ( card ) {
			var number = card.querySelector( '.dv-home-builder__order' );
			if ( number ) {
				number.textContent = '—';
			}
			updateCardState( card, false );
		} );

		var activeCount = document.querySelector( '[data-active-count]' );
		var inactiveCount = document.querySelector( '[data-inactive-count]' );
		if ( activeCount ) {
			activeCount.textContent = String( cardsIn( activeList ).length );
		}
		if ( inactiveCount ) {
			inactiveCount.textContent = String( cardsIn( inactiveList ).length );
		}

		lists.forEach( function ( list ) {
			var lane = list.closest( '[data-home-lane]' );
			if ( lane ) {
				lane.classList.toggle( 'is-empty', 0 === cardsIn( list ).length );
			}
		} );

		syncLinkedAdminMenus();
	}

	function saveLayout() {
		var activeOrder = sectionIds( activeList );
		var inactiveOrder = sectionIds( inactiveList );
		setState( config.saving, false );

		saveQueue = saveQueue.catch( function () {
			return null;
		} ).then( function () {
			return request( {
				action: 'diniz_studio_save_home_builder_order',
				activeOrder: JSON.stringify( activeOrder ),
				inactiveOrder: JSON.stringify( inactiveOrder )
			} );
		} ).then( function () {
			updateInterface();
			setState( config.saved, false );
			refreshPreview();
		} ).catch( function ( error ) {
			setState( config.error, true );
			showNotice( error.message || config.error, true );
			return null;
		} );

		return saveQueue;
	}

	function moveCardVertically( card, direction ) {
		var list = card ? card.parentElement : null;
		if ( ! card || ! list || ! list.matches( '[data-home-zone]' ) ) {
			return;
		}

		if ( 'up' === direction && card.previousElementSibling ) {
			list.insertBefore( card, card.previousElementSibling );
		} else if ( 'down' === direction && card.nextElementSibling ) {
			list.insertBefore( card.nextElementSibling, card );
		} else {
			return;
		}

		updateInterface();
		saveLayout();
		card.focus( { preventScroll: true } );
	}

	function moveCardBetweenZones( card ) {
		if ( ! card || ! activeList || ! inactiveList ) {
			return;
		}

		var currentlyActive = activeList.contains( card );
		( currentlyActive ? inactiveList : activeList ).appendChild( card );
		updateInterface();
		saveLayout();
		card.focus( { preventScroll: true } );
	}

	function insertDraggedCard( list, target, clientY, card ) {
		if ( ! list || ! card ) {
			return;
		}

		if ( target && target !== card ) {
			var rect = target.getBoundingClientRect();
			var after = clientY > rect.top + rect.height / 2;
			list.insertBefore( card, after ? target.nextElementSibling : target );
		} else if ( ! target ) {
			list.appendChild( card );
		}

		layoutChanged = true;
		updateInterface();
	}

	lists.forEach( function ( list ) {
		list.addEventListener( 'dragstart', function ( event ) {
			var handle = event.target.closest( '.dv-home-builder__drag' );
			if ( ! handle ) {
				event.preventDefault();
				return;
			}

			dragging = handle.closest( '[data-section-id]' );
			layoutChanged = false;
			dragging.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			event.dataTransfer.setData( 'text/plain', dragging.getAttribute( 'data-section-id' ) );
		} );

		list.addEventListener( 'dragover', function ( event ) {
			if ( ! dragging ) {
				return;
			}

			event.preventDefault();
			list.closest( '[data-home-lane]' ).classList.add( 'is-drag-over' );
			var target = event.target.closest( '[data-section-id]' );
			insertDraggedCard( list, target, event.clientY, dragging );
		} );

		list.addEventListener( 'drop', function ( event ) {
			if ( dragging ) {
				event.preventDefault();
			}
		} );

		list.addEventListener( 'dragleave', function ( event ) {
			if ( ! list.contains( event.relatedTarget ) ) {
				list.closest( '[data-home-lane]' ).classList.remove( 'is-drag-over' );
			}
		} );
	} );

	document.addEventListener( 'dragend', function () {
		if ( ! dragging ) {
			return;
		}

		dragging.classList.remove( 'is-dragging' );
		dragging = null;
		document.querySelectorAll( '.is-drag-over' ).forEach( function ( lane ) {
			lane.classList.remove( 'is-drag-over' );
		} );
		if ( layoutChanged ) {
			saveLayout();
		}
	} );

	document.addEventListener( 'pointerdown', function ( event ) {
		var handle = event.target.closest( '.dv-home-builder__drag' );
		if ( ! handle || 'mouse' === event.pointerType ) {
			return;
		}

		touchDragging = handle.closest( '[data-section-id]' );
		layoutChanged = false;
		touchDragging.classList.add( 'is-dragging' );
		handle.setPointerCapture( event.pointerId );
		event.preventDefault();
	} );

	document.addEventListener( 'pointermove', function ( event ) {
		if ( ! touchDragging ) {
			return;
		}

		event.preventDefault();
		var element = document.elementFromPoint( event.clientX, event.clientY );
		var list = element ? element.closest( '[data-home-zone]' ) : null;
		var target = element ? element.closest( '[data-section-id]' ) : null;
		if ( ! list ) {
			return;
		}

		document.querySelectorAll( '.is-drag-over' ).forEach( function ( lane ) {
			lane.classList.remove( 'is-drag-over' );
		} );
		list.closest( '[data-home-lane]' ).classList.add( 'is-drag-over' );
		insertDraggedCard( list, target, event.clientY, touchDragging );
	} );

	function endTouchDrag() {
		if ( ! touchDragging ) {
			return;
		}

		touchDragging.classList.remove( 'is-dragging' );
		touchDragging = null;
		document.querySelectorAll( '.is-drag-over' ).forEach( function ( lane ) {
			lane.classList.remove( 'is-drag-over' );
		} );
		if ( layoutChanged ) {
			saveLayout();
		}
	}

	document.addEventListener( 'pointerup', endTouchDrag );
	document.addEventListener( 'pointercancel', endTouchDrag );

	document.addEventListener( 'click', function ( event ) {
		var moveButton = event.target.closest( '[data-move]' );
		if ( moveButton ) {
			moveCardVertically( moveButton.closest( '[data-section-id]' ), moveButton.getAttribute( 'data-move' ) );
			return;
		}

		var toggleButton = event.target.closest( '[data-toggle-section]' );
		if ( toggleButton ) {
			moveCardBetweenZones( toggleButton.closest( '[data-section-id]' ) );
		}
	} );

	function openLibrary() {
		if ( ! library ) {
			return;
		}
		library.hidden = false;
		library.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'dv-home-library-open' );
		var available = library.querySelector( '[data-create-section]:not([disabled])' );
		if ( available ) {
			available.focus();
		}
	}

	function closeLibrary() {
		if ( ! library ) {
			return;
		}
		library.hidden = true;
		library.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'dv-home-library-open' );
		if ( addButton ) {
			addButton.focus();
		}
	}

	if ( addButton ) {
		addButton.addEventListener( 'click', openLibrary );
	}

	if ( library ) {
		library.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-close-library]' ) ) {
				closeLibrary();
				return;
			}

			var createButton = event.target.closest( '[data-create-section]' );
			if ( ! createButton || createButton.disabled ) {
				return;
			}

			createButton.disabled = true;
			setState( config.creating, false );
			request( {
				action: 'diniz_studio_create_home_section',
				sectionKey: createButton.getAttribute( 'data-create-section' )
			} ).then( function () {
				window.sessionStorage.setItem( 'dv-home-builder-message', config.sectionAdded );
				window.location.reload();
			} ).catch( function ( error ) {
				createButton.disabled = false;
				setState( config.error, true );
				showNotice( error.message || config.error, true );
			} );
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && library && ! library.hidden ) {
			closeLibrary();
		}
	} );

	document.querySelectorAll( '[data-preview-device]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var shell = document.querySelector( '.dv-home-builder__frame-shell' );
			document.querySelectorAll( '[data-preview-device]' ).forEach( function ( item ) {
				item.classList.toggle( 'is-active', item === button );
			} );
			if ( shell ) {
				shell.setAttribute( 'data-preview-shell', button.getAttribute( 'data-preview-device' ) );
			}
		} );
	} );

	var refreshButton = document.querySelector( '[data-refresh-preview]' );
	if ( refreshButton ) {
		refreshButton.addEventListener( 'click', refreshPreview );
	}

	var storedMessage = window.sessionStorage.getItem( 'dv-home-builder-message' );
	if ( storedMessage ) {
		window.sessionStorage.removeItem( 'dv-home-builder-message' );
		showNotice( storedMessage, false );
	}

	updateInterface();
}() );
