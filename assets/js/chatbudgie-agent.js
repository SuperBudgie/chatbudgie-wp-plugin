/*
 * Standalone ChatBudgie Agent
 *
 * A dependency-free browser controller for chat widgets.
 * Load it with a normal script tag on any website:
 *
 *   <script src="/chatbudgie-agent.js"></script>
 *
 * Then use:
 *
 *   const controller = window.createChatBudgieAgent({ highlight: true })
 *   const response = await controller.call({ id: '1', method: 'getBrowserState' })
 *
 * This file intentionally does not import anything from the monorepo.
 *
 * It mirrors the important browser automation ideas in native JS:
 * 1. Observe: scan the live DOM and find visible, top-layer interactive nodes.
 * 2. Dehydrate: turn those nodes into compact indexed text for an LLM.
 * 3. Act: map the LLM's index back to the live HTMLElement and click/type/scroll.
 *
 * ChatBudgieAgent is the portable implementation for browser chat widgets. This file
 * is the portable version for "paste it onto any website" chat widgets.
 */
;(function () {
	'use strict'

	var DEFAULT_INCLUDE_ATTRIBUTES = [
		'title',
		'type',
		'checked',
		'name',
		'role',
		'value',
		'placeholder',
		'alt',
		'href',
		'aria-label',
		'aria-expanded',
		'aria-haspopup',
		'aria-controls',
		'aria-owns',
		'aria-checked',
		'aria-selected',
		'aria-pressed',
		'data-state',
		'id',
		'for',
		'target',
		'contenteditable',
	]

	var INTERACTIVE_TAGS = {
		a: true,
		button: true,
		input: true,
		select: true,
		textarea: true,
		details: true,
		summary: true,
		label: true,
		option: true,
	}

	var INTERACTIVE_ROLES = {
		button: true,
		link: true,
		menuitem: true,
		menuitemradio: true,
		menuitemcheckbox: true,
		radio: true,
		checkbox: true,
		tab: true,
		switch: true,
		slider: true,
		spinbutton: true,
		combobox: true,
		searchbox: true,
		textbox: true,
		listbox: true,
		option: true,
		scrollbar: true,
	}

	var INTERACTIVE_ARIA_ATTRS = [
		'aria-expanded',
		'aria-checked',
		'aria-selected',
		'aria-pressed',
		'aria-haspopup',
		'aria-controls',
		'aria-owns',
		'aria-activedescendant',
		'aria-valuenow',
		'aria-valuetext',
		'aria-valuemax',
		'aria-valuemin',
		'aria-autocomplete',
	]

	var INTERACTIVE_CURSORS = {
		pointer: true,
		move: true,
		text: true,
		grab: true,
		grabbing: true,
		cell: true,
		copy: true,
		alias: true,
		'all-scroll': true,
		'col-resize': true,
		'context-menu': true,
		crosshair: true,
		'e-resize': true,
		'ew-resize': true,
		help: true,
		'n-resize': true,
		'ne-resize': true,
		'nesw-resize': true,
		'ns-resize': true,
		'nw-resize': true,
		'nwse-resize': true,
		'row-resize': true,
		's-resize': true,
		'se-resize': true,
		'sw-resize': true,
		'vertical-text': true,
		'w-resize': true,
		'zoom-in': true,
		'zoom-out': true,
	}

	var NON_INTERACTIVE_CURSORS = {
		'not-allowed': true,
		'no-drop': true,
		wait: true,
		progress: true,
		initial: true,
		inherit: true,
	}

	var DISTINCT_INTERACTIVE_TAGS = {
		a: true,
		button: true,
		input: true,
		select: true,
		textarea: true,
		summary: true,
		details: true,
		label: true,
		option: true,
		li: true,
		iframe: true,
	}

	var BLOCKED_TAGS = {
		script: true,
		style: true,
		link: true,
		meta: true,
		noscript: true,
		template: true,
		svg: true,
	}

	var HIGHLIGHT_CONTAINER_ID = 'chatbudgie-agent-highlight-container'
	var activeHighlightContainer = null
	var highlightedElements = []
	var highlightUpdateFrame = null
	var highlightScrollHandler = null
	var highlightResizeHandler = null

	function ChatBudgieAgent(options) {
		this.options = options || {}
		this.options.viewportExpansion =
			typeof this.options.viewportExpansion === 'number' ? this.options.viewportExpansion : -1
		this.selectorMap = new Map()
		this.elementTextMap = new Map()
		this.simplifiedHTML = '<EMPTY>'
		this.lastUpdateTime = 0
	}

	ChatBudgieAgent.prototype.call = async function (call) {
		// Transport-safe RPC wrapper. It never throws across the boundary; every
		// backend call receives either { ok: true, result } or { ok: false, error }.
		try {
			var result = await this.execute(call || {})
			return { id: call && call.id, ok: true, result: result }
		} catch (error) {
			return {
				id: call && call.id,
				ok: false,
				error: String(error && error.message ? error.message : error),
			}
		}
	}

	ChatBudgieAgent.prototype.execute = async function (call) {
		// This is the entire public control surface exposed to the backend agent.
		// Keep it narrow: the LLM decides what to do, but only these methods can
		// touch the browser page.
		var params = call.params || {}

		switch (call.method) {
			case 'getBrowserState':
				return this.getBrowserState()
			case 'getCurrentUrl':
				return window.location.href
			case 'getLastUpdateTime':
				return this.lastUpdateTime
			case 'clickElement':
				return this.clickElement(requireNumber(params.index, 'index'))
			case 'inputText':
				return this.inputText(
					requireNumber(params.index, 'index'),
					requireString(params.text, 'text')
				)
			case 'selectOption':
				return this.selectOption(
					requireNumber(params.index, 'index'),
					requireString(params.text === undefined ? params.optionText : params.text, 'text')
				)
			case 'scroll':
				return this.scroll({
					down: requireBoolean(params.down, 'down'),
					numPages:
						requireOptionalNumber(
							params.numPages === undefined ? params.num_pages : params.numPages,
							'numPages'
						) || 1,
					pixels: requireOptionalNumber(params.pixels, 'pixels'),
					index: requireOptionalNumber(params.index, 'index'),
				})
			case 'scrollHorizontally':
				return this.scrollHorizontally({
					right: requireBoolean(params.right, 'right'),
					pixels: requireNumber(params.pixels, 'pixels'),
					index: requireOptionalNumber(params.index, 'index'),
				})
			case 'cleanUpHighlights':
				this.cleanUpHighlights()
				return undefined
			default:
				throw new Error('Unknown controller method: ' + call.method)
		}
	}

	ChatBudgieAgent.prototype.getBrowserState = async function () {
		// BrowserState is the text snapshot sent to the backend LLM loop. It
		// matches the shape used by the TypeScript ChatBudgieAgent:
		// { url, title, header, content, footer }.
		var url = window.location.href
		var title = document.title
		var pageInfo = getPageInfo()

		await this.updateTree()

		var titleLine = 'Current Page: [' + title + '](' + url + ')'
		var pageInfoLine =
			'Page info: ' +
			pageInfo.viewport_width +
			'x' +
			pageInfo.viewport_height +
			'px viewport, ' +
			pageInfo.page_width +
			'x' +
			pageInfo.page_height +
			'px total page size, ' +
			pageInfo.pages_above.toFixed(1) +
			' pages above, ' +
			pageInfo.pages_below.toFixed(1) +
			' pages below, ' +
			pageInfo.total_pages.toFixed(1) +
			' total pages, at ' +
			(pageInfo.current_page_position * 100).toFixed(0) +
			'% of page'

		var header =
			titleLine +
			'\n' +
			pageInfoLine +
			'\n\nInteractive elements from the current page:\n\n' +
			(pageInfo.pixels_above > 4
				? '... ' + pageInfo.pixels_above + ' pixels above - scroll to see more ...'
				: '[Start of page]')

		var footer =
			pageInfo.pixels_below > 4
				? '... ' + pageInfo.pixels_below + ' pixels below - scroll to see more ...'
				: '[End of page]'

		return {
			url: url,
			title: title,
			header: header,
			content: this.simplifiedHTML,
			footer: footer,
		}
	}

	ChatBudgieAgent.prototype.updateTree = async function () {
		// Observe/dehydrate phase:
		// - clear stale indexes and highlights
		// - scan the live DOM
		// - keep only visible top-layer interactive/scrollable elements
		// - assign stable indexes for this observation
		// - remember index -> HTMLElement for future actions
		this.lastUpdateTime = Date.now()
		this.selectorMap.clear()
		this.elementTextMap.clear()
		this.cleanUpHighlights()

		var lines = []
		var index = 0
		var root = document.body || document.documentElement
		var elements = Array.prototype.slice.call(root.querySelectorAll('*'))

		for (var i = 0; i < elements.length; i++) {
			var element = getInteractiveOwner(elements[i])
			if (isElementIndexed(element, this.selectorMap)) continue
			if (!shouldIncludeElement(element, this.options)) continue
			if (
				hasAcceptedInteractiveAncestor(element, this.selectorMap) &&
				!isElementDistinctInteraction(element)
			) {
				continue
			}

			var text = getElementText(element)
			var attrs = getIncludedAttributes(element, this.options.includeAttributes)
			var scrollData = getScrollableData(element)
			var line = '[' + index + ']<' + element.tagName.toLowerCase()
			var attrsText = attributesToText(attrs)

			if (attrsText) line += ' ' + attrsText
			if (scrollData) line += ' data-scrollable="' + scrollData + '"'

			if (text) line += '>' + text + '</' + element.tagName.toLowerCase() + '>'
			else line += ' />'

			lines.push(line)
			this.selectorMap.set(index, element)
			this.elementTextMap.set(index, line)

			if (this.options.highlight !== false) {
				highlightElement(element, index, this.options)
			}

			index++
		}

		this.simplifiedHTML = lines.length ? lines.join('\n') : '<EMPTY>'
		return this.simplifiedHTML
	}

	ChatBudgieAgent.prototype.getElementByIndex = function (index) {
		// The hidden selector map is the "hands" half of the system: the LLM sees
		// only text indexes, while this map keeps the live HTMLElement reference.
		var element = this.selectorMap.get(index)
		if (!element) throw new Error('No interactive element found at index ' + index)
		return element
	}

	ChatBudgieAgent.prototype.clickElement = async function (index) {
		// Action phase: replay a browser-like click sequence against the indexed
		// live element. This is intentionally DOM-native, not Playwright.
		var element = this.getElementByIndex(index)
		try {
			await clickElement(element)
			return {
				success: true,
				message: 'Clicked element (' + (this.elementTextMap.get(index) || index) + ').',
			}
		} catch (error) {
			return { success: false, message: 'Failed to click element: ' + error }
		}
	}

	ChatBudgieAgent.prototype.inputText = async function (index, text) {
		// Action phase: set input/textarea/contenteditable text and dispatch the
		// events frameworks commonly listen to.
		var element = this.getElementByIndex(index)
		try {
			await inputTextElement(element, text)
			return {
				success: true,
				message: 'Input text into element (' + (this.elementTextMap.get(index) || index) + ').',
			}
		} catch (error) {
			return { success: false, message: 'Failed to input text: ' + error }
		}
	}

	ChatBudgieAgent.prototype.selectOption = async function (index, optionText) {
		var element = this.getElementByIndex(index)
		try {
			if (element.tagName !== 'SELECT') throw new Error('Element is not a select element')
			var options = Array.prototype.slice.call(element.options)
			var option = options.find(function (item) {
				return (item.textContent || '').trim() === optionText.trim()
			})
			if (!option) throw new Error('Option not found: ' + optionText)
			element.value = option.value
			element.dispatchEvent(new Event('change', { bubbles: true }))
			await waitFor(100)
			return { success: true, message: 'Selected option (' + optionText + ').' }
		} catch (error) {
			return { success: false, message: 'Failed to select option: ' + error }
		}
	}

	ChatBudgieAgent.prototype.scroll = async function (options) {
		// Page scroll if no index is provided; targeted container scroll when the
		// LLM chooses an indexed data-scrollable element.
		try {
			var amount = options.pixels || options.numPages * window.innerHeight
			if (!options.down) amount = -amount
			var element = options.index === undefined ? null : this.getElementByIndex(options.index)
			var message = scrollElementOrPage(element, 0, amount)
			await waitFor(100)
			return { success: true, message: message }
		} catch (error) {
			return { success: false, message: 'Failed to scroll: ' + error }
		}
	}

	ChatBudgieAgent.prototype.scrollHorizontally = async function (options) {
		try {
			var amount = options.right ? options.pixels : -options.pixels
			var element = options.index === undefined ? null : this.getElementByIndex(options.index)
			var message = scrollElementOrPage(element, amount, 0)
			await waitFor(100)
			return { success: true, message: message }
		} catch (error) {
			return { success: false, message: 'Failed to scroll horizontally: ' + error }
		}
	}

	ChatBudgieAgent.prototype.cleanUpHighlights = function () {
		restoreElementHighlights()
		var container = document.getElementById(HIGHLIGHT_CONTAINER_ID)
		if (container) container.remove()
		activeHighlightContainer = null
		if (highlightScrollHandler) {
			window.removeEventListener('scroll', highlightScrollHandler, true)
			highlightScrollHandler = null
		}
		if (highlightResizeHandler) {
			window.removeEventListener('resize', highlightResizeHandler)
			highlightResizeHandler = null
		}
		if (highlightUpdateFrame) {
			cancelAnimationFrame(highlightUpdateFrame)
			highlightUpdateFrame = null
		}
	}

	ChatBudgieAgent.prototype.dispose = function () {
		this.cleanUpHighlights()
		this.selectorMap.clear()
		this.elementTextMap.clear()
		this.simplifiedHTML = '<EMPTY>'
	}

	function shouldIncludeElement(element, options) {
		if (!element || element.nodeType !== 1) return false
		if (
			element.hasAttribute('data-chatbudgie-agent-ignore') ||
			element.hasAttribute('data-browser-use-ignore')
		)
			return false
		if (isInList(element, options.interactiveBlacklist)) return false
		if (isInList(element, options.interactiveWhitelist)) return true

		var tagName = element.tagName.toLowerCase()
		if (BLOCKED_TAGS[tagName]) return false
		if (element.getAttribute('aria-hidden') === 'true') return false
		if (!isVisible(element, options.viewportExpansion)) return false
		if (!isTopElement(element, options.viewportExpansion)) return false
		if (isDisabled(element)) return false

		return isInteractive(element) || !!getScrollableData(element)
	}

	function isInList(element, list) {
		if (!list || !list.length) return false
		for (var i = 0; i < list.length; i++) {
			var item = typeof list[i] === 'function' ? list[i]() : list[i]
			if (item === element) return true
		}
		return false
	}

	function hasAcceptedInteractiveAncestor(element, selectorMap) {
		var current = element.parentElement
		while (current && current !== document.body) {
			var iterator = selectorMap.values()
			var next = iterator.next()
			while (!next.done) {
				if (next.value === current) return true
				next = iterator.next()
			}
			current = current.parentElement
		}
		return false
	}

	function isElementIndexed(element, selectorMap) {
		var iterator = selectorMap.values()
		var next = iterator.next()
		while (!next.done) {
			if (next.value === element) return true
			next = iterator.next()
		}
		return false
	}

	function getInteractiveOwner(element) {
		if (!element || element.namespaceURI !== 'http://www.w3.org/2000/svg') return element

		var outermostSvg = element
		var current = element.parentElement
		while (current) {
			if (current.namespaceURI === 'http://www.w3.org/2000/svg') {
				outermostSvg = current
				current = current.parentElement
				continue
			}
			if (isInteractive(current)) return current
			break
		}

		return outermostSvg
	}

	function hasInteractiveAria(element) {
		for (var i = 0; i < INTERACTIVE_ARIA_ATTRS.length; i++) {
			if (element.hasAttribute(INTERACTIVE_ARIA_ATTRS[i])) return true
		}
		return false
	}

	function isInteractive(element) {
		// Browser-use's practical lesson: no single signal is enough. Real apps
		// mix semantic tags, ARIA roles, pointer cursors, event attributes, and
		// custom div-based controls, so we combine all of them.
		var tagName = element.tagName.toLowerCase()
		var role = element.getAttribute('role')
		var ariaRole = element.getAttribute('aria-role')
		var style = window.getComputedStyle(element)

		if (style.cursor && NON_INTERACTIVE_CURSORS[style.cursor]) return false
		if (style.cursor && INTERACTIVE_CURSORS[style.cursor]) return true
		if (INTERACTIVE_TAGS[tagName]) return true
		if (role && INTERACTIVE_ROLES[role]) return true
		if (ariaRole && INTERACTIVE_ROLES[ariaRole]) return true
		if (element.isContentEditable || element.getAttribute('contenteditable') === 'true') return true
		if (element.hasAttribute('onclick') || typeof element.onclick === 'function') return true
		if (element.hasAttribute('tabindex')) return true
		if (hasInteractiveAria(element)) return true
		if (
			element.classList &&
			(element.classList.contains('button') ||
				element.classList.contains('dropdown-toggle') ||
				element.getAttribute('data-index') ||
				element.getAttribute('data-toggle') === 'dropdown')
		) {
			return true
		}

		try {
			var eventAttrs = [
				'onmousedown',
				'onmouseup',
				'ondblclick',
				'onkeydown',
				'onkeyup',
				'onsubmit',
				'onchange',
				'oninput',
				'onfocus',
				'onblur',
			]
			for (var i = 0; i < eventAttrs.length; i++) {
				if (element.hasAttribute(eventAttrs[i])) return true
			}
		} catch (_) {
			// Event-listener introspection is browser/devtools dependent. If it
			// fails, the other semantic/style checks still carry the detection.
		}

		return false
	}

	function isElementDistinctInteraction(element) {
		// Avoid over-indexing wrapper + child when they trigger the same action,
		// but keep children that represent a separate actionable target.
		var tagName = element.tagName.toLowerCase()
		var role = element.getAttribute('role')

		if (DISTINCT_INTERACTIVE_TAGS[tagName]) return true
		if (role && INTERACTIVE_ROLES[role]) return true
		if (element.isContentEditable || element.getAttribute('contenteditable') === 'true') return true
		if (
			element.hasAttribute('data-testid') ||
			element.hasAttribute('data-cy') ||
			element.hasAttribute('data-test')
		) {
			return true
		}
		if (element.hasAttribute('onclick') || typeof element.onclick === 'function') return true
		if (hasInteractiveAria(element)) return true
		if (getScrollableData(element)) return true

		return false
	}

	function isDisabled(element) {
		return Boolean(
			element.disabled ||
			element.readOnly ||
			element.inert ||
			element.hasAttribute('disabled') ||
			element.hasAttribute('readonly') ||
			element.getAttribute('aria-disabled') === 'true'
		)
	}

	function isVisible(element, viewportExpansion) {
		// Visibility is geometry + CSS. For full-page mode (-1), any non-empty
		// rect counts; otherwise the rect must intersect the expanded viewport.
		var style = window.getComputedStyle(element)
		if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0')
			return false

		var rects = element.getClientRects()
		if (!rects || rects.length === 0) return false

		var expansion = typeof viewportExpansion === 'number' ? viewportExpansion : -1
		for (var i = 0; i < rects.length; i++) {
			var rect = rects[i]
			if (rect.width <= 0 || rect.height <= 0) continue
			if (expansion === -1) return true
			if (
				rect.bottom >= -expansion &&
				rect.top <= window.innerHeight + expansion &&
				rect.right >= -expansion &&
				rect.left <= window.innerWidth + expansion
			) {
				return true
			}
		}
		return false
	}

	function isTopElement(element, viewportExpansion) {
		// Match browser-use's "top layer" idea: if another element covers the
		// sample points, clicking this node would not reach it.
		if (viewportExpansion === -1 || viewportExpansion === undefined) return true

		var rect = element.getBoundingClientRect()
		if (!rect.width || !rect.height) return false
		var points = [
			[rect.left + rect.width / 2, rect.top + rect.height / 2],
			[rect.left + 4, rect.top + 4],
			[rect.right - 4, rect.bottom - 4],
		]

		for (var i = 0; i < points.length; i++) {
			var x = Math.max(0, Math.min(window.innerWidth - 1, points[i][0]))
			var y = Math.max(0, Math.min(window.innerHeight - 1, points[i][1]))
			var topElement = document.elementFromPoint(x, y)
			while (topElement && topElement !== document.documentElement) {
				if (topElement === element) return true
				topElement = topElement.parentElement
			}
		}
		return false
	}

	function getScrollableData(element) {
		// Scrollable containers are included as interactive targets so the
		// backend agent can ask for targeted scrolling by index.
		var style = window.getComputedStyle(element)
		var canScrollY =
			/(auto|scroll|overlay)/.test(style.overflowY) &&
			element.scrollHeight > element.clientHeight + 4
		var canScrollX =
			/(auto|scroll|overlay)/.test(style.overflowX) && element.scrollWidth > element.clientWidth + 4
		if (!canScrollX && !canScrollY) return ''

		var parts = []
		if (element.scrollLeft) parts.push('left=' + Math.round(element.scrollLeft))
		if (element.scrollTop) parts.push('top=' + Math.round(element.scrollTop))
		if (canScrollX)
			parts.push(
				'right=' + Math.round(element.scrollWidth - element.clientWidth - element.scrollLeft)
			)
		if (canScrollY)
			parts.push(
				'bottom=' + Math.round(element.scrollHeight - element.clientHeight - element.scrollTop)
			)
		return parts.join(', ')
	}

	function getIncludedAttributes(element, includeAttributes) {
		// Keep attributes that help the LLM identify controls without dumping the
		// whole DOM. This mirrors flatTreeToString's default attribute allowlist.
		var attrs = {}
		var names = DEFAULT_INCLUDE_ATTRIBUTES.concat(includeAttributes || [])
		for (var i = 0; i < names.length; i++) {
			var name = names[i]
			var value = element.getAttribute(name)
			if (value && String(value).trim()) attrs[name] = String(value).trim()
		}

		if (element.tagName === 'INPUT' && (element.type === 'checkbox' || element.type === 'radio')) {
			attrs.checked = element.checked ? 'true' : 'false'
		}

		return attrs
	}

	function attributesToText(attrs) {
		// Render attributes as compact pseudo-HTML: placeholder=Search role=button.
		var parts = []
		for (var key in attrs) {
			if (!Object.prototype.hasOwnProperty.call(attrs, key)) continue
			parts.push(key + '=' + truncate(attrs[key], 40))
		}
		return parts.join(' ')
	}

	function getElementText(element) {
		// Browser-use dehydrates an element with its descendant text until the next
		// clickable element. This standalone version uses visible innerText and
		// caps length to keep prompts small.
		var aria = element.getAttribute('aria-label')
		var text = aria || element.innerText || element.textContent || element.value || ''
		text = String(text).replace(/\s+/g, ' ').trim()
		return truncate(text, 160)
	}

	function truncate(text, max) {
		text = String(text || '')
		return text.length > max ? text.slice(0, max) + '...' : text
	}

	async function clickElement(element) {
		scrollIntoViewIfNeeded(element)
		await waitFor(50)

		var rect = element.getBoundingClientRect()
		var x = rect.left + rect.width / 2
		var y = rect.top + rect.height / 2
		var mouseOptions = { bubbles: true, cancelable: true, clientX: x, clientY: y, button: 0 }
		var pointerOptions = {
			bubbles: true,
			cancelable: true,
			clientX: x,
			clientY: y,
			button: 0,
			pointerType: 'mouse',
		}

		if (typeof PointerEvent === 'function') {
			element.dispatchEvent(new PointerEvent('pointerover', pointerOptions))
			element.dispatchEvent(new PointerEvent('pointerenter', pointerOptions))
			element.dispatchEvent(new PointerEvent('pointerdown', pointerOptions))
		}
		element.dispatchEvent(new MouseEvent('mouseover', mouseOptions))
		element.dispatchEvent(new MouseEvent('mouseenter', mouseOptions))
		element.dispatchEvent(new MouseEvent('mousedown', mouseOptions))
		if (typeof element.focus === 'function') element.focus({ preventScroll: true })
		if (typeof PointerEvent === 'function') {
			element.dispatchEvent(new PointerEvent('pointerup', pointerOptions))
		}
		element.dispatchEvent(new MouseEvent('mouseup', mouseOptions))
		element.click()
		await waitFor(100)
	}

	async function inputTextElement(element, text) {
		scrollIntoViewIfNeeded(element)
		await clickElement(element)

		if (element.isContentEditable) {
			element.focus()
			if (
				typeof InputEvent !== 'undefined' &&
				element.dispatchEvent(
					new InputEvent('beforeinput', {
						bubbles: true,
						cancelable: true,
						inputType: 'deleteContent',
					})
				)
			) {
				element.textContent = ''
			}
			if (
				typeof InputEvent !== 'undefined' &&
				element.dispatchEvent(
					new InputEvent('beforeinput', {
						bubbles: true,
						cancelable: true,
						inputType: 'insertText',
						data: text,
					})
				)
			) {
				element.textContent = text
			} else {
				element.textContent = text
			}
			element.dispatchEvent(createInputLikeEvent('input', 'insertText', text))
			element.dispatchEvent(new Event('change', { bubbles: true }))
			return
		}

		if (element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA') {
			throw new Error('Element is not an input, textarea, or contenteditable')
		}

		var setter = Object.getOwnPropertyDescriptor(Object.getPrototypeOf(element), 'value')
		if (setter && setter.set) setter.set.call(element, text)
		else element.value = text

		element.dispatchEvent(new Event('input', { bubbles: true }))
		element.dispatchEvent(new Event('change', { bubbles: true }))
		await waitFor(100)
	}

	function scrollElementOrPage(element, dx, dy) {
		var target = element
			? findScrollableParent(element, dx, dy)
			: document.scrollingElement || document.documentElement
		var beforeX =
			target === document.scrollingElement || target === document.documentElement
				? window.scrollX
				: target.scrollLeft
		var beforeY =
			target === document.scrollingElement || target === document.documentElement
				? window.scrollY
				: target.scrollTop

		if (
			target === document.scrollingElement ||
			target === document.documentElement ||
			target === document.body
		) {
			window.scrollBy(dx, dy)
			return (
				'Scrolled page by x=' +
				(window.scrollX - beforeX) +
				', y=' +
				(window.scrollY - beforeY) +
				'.'
			)
		}

		target.scrollBy(dx, dy)
		return (
			'Scrolled container (' +
			target.tagName +
			') by x=' +
			(target.scrollLeft - beforeX) +
			', y=' +
			(target.scrollTop - beforeY) +
			'.'
		)
	}

	function findScrollableParent(element, dx, dy) {
		var current = element
		while (current && current !== document.body) {
			var style = window.getComputedStyle(current)
			var canY =
				dy &&
				/(auto|scroll|overlay)/.test(style.overflowY) &&
				current.scrollHeight > current.clientHeight
			var canX =
				dx &&
				/(auto|scroll|overlay)/.test(style.overflowX) &&
				current.scrollWidth > current.clientWidth
			if (canY || canX) return current
			current = current.parentElement
		}
		return document.scrollingElement || document.documentElement
	}

	function scrollIntoViewIfNeeded(element) {
		if (typeof element.scrollIntoViewIfNeeded === 'function') {
			element.scrollIntoViewIfNeeded()
		} else {
			element.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' })
		}
	}

	function highlightElement(element, index, options) {
		var container = document.getElementById(HIGHLIGHT_CONTAINER_ID)
		if (!container) {
			container = document.createElement('div')
			container.id = HIGHLIGHT_CONTAINER_ID
			container.setAttribute('data-chatbudgie-agent-ignore', 'true')
			container.style.cssText =
				'position:fixed;inset:0;pointer-events:none;z-index:2147483640;background:transparent;'
			container._chatbudgieHighlightItems = []
			document.body.appendChild(container)
			activeHighlightContainer = container
			ensureHighlightListeners()
		}

		var rect = element.getBoundingClientRect()
		if (!rect.width || !rect.height) return

		var color = options.highlightColor || '#3b82f6'
		applyElementHighlight(element, color)

		var label = document.createElement('div')
		label.textContent = String(index)
		label.style.cssText =
			'position:fixed;background:' +
			color +
			';color:white;font:11px/1.4 system-ui,sans-serif;padding:1px 4px;border-radius:4px;'

		container.appendChild(label)
		container._chatbudgieHighlightItems.push({
			element: element,
			label: label,
		})
		updateHighlightLabel(element, label)
	}

	function applyElementHighlight(element, color) {
		if (!element._chatbudgieOriginalHighlightStyle) {
			element._chatbudgieOriginalHighlightStyle = {
				outline: element.style.outline,
				outlineOffset: element.style.outlineOffset,
				boxShadow: element.style.boxShadow,
			}
			highlightedElements.push(element)
		}

		element.style.outline = '2px solid ' + color
		element.style.outlineOffset = '2px'
		element.style.boxShadow = '0 0 0 3px rgba(59,130,246,.14)'
	}

	function restoreElementHighlights() {
		for (var i = 0; i < highlightedElements.length; i++) {
			var element = highlightedElements[i]
			var original = element && element._chatbudgieOriginalHighlightStyle
			if (!original) continue

			element.style.outline = original.outline
			element.style.outlineOffset = original.outlineOffset
			element.style.boxShadow = original.boxShadow
			delete element._chatbudgieOriginalHighlightStyle
		}
		highlightedElements = []
	}

	function ensureHighlightListeners() {
		if (!highlightScrollHandler) {
			highlightScrollHandler = scheduleHighlightUpdate
			window.addEventListener('scroll', highlightScrollHandler, true)
		}
		if (!highlightResizeHandler) {
			highlightResizeHandler = scheduleHighlightUpdate
			window.addEventListener('resize', highlightResizeHandler)
		}
	}

	function scheduleHighlightUpdate() {
		if (highlightUpdateFrame) return
		highlightUpdateFrame = requestAnimationFrame(function () {
			highlightUpdateFrame = null
			updateHighlightPositions()
		})
	}

	function updateHighlightPositions() {
		var container = activeHighlightContainer
		if (!container || !container.isConnected || !container._chatbudgieHighlightItems) return

		for (var i = 0; i < container._chatbudgieHighlightItems.length; i++) {
			var item = container._chatbudgieHighlightItems[i]
			updateHighlightLabel(item.element, item.label)
		}
	}

	function updateHighlightLabel(element, label) {
		if (!element || !element.isConnected) {
			label.style.display = 'none'
			return
		}

		var rect = element.getBoundingClientRect()
		if (!rect.width || !rect.height) {
			label.style.display = 'none'
			return
		}

		label.style.display = ''
		label.style.left = Math.max(0, rect.left) + 'px'
		label.style.top = Math.max(0, rect.top - 18) + 'px'
	}

	function getPageInfo() {
		// The LLM needs scroll context. A page with useful content below should
		// prompt a scroll action instead of a premature final answer.
		var doc = document.documentElement
		var body = document.body || doc
		var viewportWidth = window.innerWidth
		var viewportHeight = window.innerHeight
		var pageWidth = Math.max(
			body.scrollWidth,
			doc.scrollWidth,
			body.offsetWidth,
			doc.offsetWidth,
			viewportWidth
		)
		var pageHeight = Math.max(
			body.scrollHeight,
			doc.scrollHeight,
			body.offsetHeight,
			doc.offsetHeight,
			viewportHeight
		)
		var scrollX = window.scrollX || doc.scrollLeft || body.scrollLeft || 0
		var scrollY = window.scrollY || doc.scrollTop || body.scrollTop || 0
		var pixelsBelow = Math.max(0, pageHeight - viewportHeight - scrollY)

		return {
			viewport_width: viewportWidth,
			viewport_height: viewportHeight,
			page_width: pageWidth,
			page_height: pageHeight,
			pixels_above: Math.round(scrollY),
			pixels_below: Math.round(pixelsBelow),
			pages_above: scrollY / Math.max(1, viewportHeight),
			pages_below: pixelsBelow / Math.max(1, viewportHeight),
			total_pages: pageHeight / Math.max(1, viewportHeight),
			current_page_position:
				pageHeight <= viewportHeight ? 0 : scrollY / (pageHeight - viewportHeight),
			scroll_x: scrollX,
			scroll_y: scrollY,
		}
	}

	function waitFor(ms) {
		return new Promise(function (resolve) {
			setTimeout(resolve, ms)
		})
	}

	function createInputLikeEvent(type, inputType, data) {
		if (typeof InputEvent !== 'undefined') {
			return new InputEvent(type, { bubbles: true, inputType: inputType, data: data })
		}
		return new Event(type, { bubbles: true })
	}

	function requireString(value, name) {
		if (typeof value !== 'string') throw new Error(name + ' must be a string')
		return value
	}

	function requireNumber(value, name) {
		if (typeof value !== 'number' || !isFinite(value))
			throw new Error(name + ' must be a finite number')
		return value
	}

	function requireOptionalNumber(value, name) {
		if (value === undefined) return undefined
		return requireNumber(value, name)
	}

	function requireBoolean(value, name) {
		if (typeof value !== 'boolean') throw new Error(name + ' must be a boolean')
		return value
	}

	window.ChatBudgieAgent = ChatBudgieAgent
	window.createChatBudgieAgent = function (options) {
		return new ChatBudgieAgent(options)
	}

	if (document.currentScript) {
		var src = document.currentScript.getAttribute('src')
		var autoInit = src && src.indexOf('autoInit=true') !== -1
		if (autoInit) {
			window.chatbudgieAgent = new ChatBudgieAgent({ highlight: true })
		}
	}
})()
