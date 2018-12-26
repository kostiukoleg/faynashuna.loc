(function($){

// When called on a container with a selector, fetches the href with
// ajax into the container or with the data-pjax attribute on the link
// itself.
//
// Tries to make sure the back button and ctrl+click work the way
// you'd expect.
//
// Exported as $.fn.pjax
//
// Accepts a jQuery ajax options object that may include these
// pjax specific options:
//
//
// container - Where to stick the response body. Usually a String selector.
//             $(container).html(xhr.responseBody)
//             (default: current jquery context)
//      push - Whether to pushState the URL. Defaults to true (of course).
//   replace - Want to use replaceState instead? That's cool.
//
// For convenience the second parameter can be either the container or
// the options object.
//
// Returns the jQuery object
function fnPjax(selector, container, options) {
  var context = this
  return this.on('click.pjax', selector, function(event) {
    var opts = $.extend({}, optionsFor(container, options))
    if (!opts.container)
      opts.container = $(this).attr('data-pjax') || context
    handleClick(event, opts)
  })
}

// Public: pjax on click handler
//
// Exported as $.pjax.click.
//
// event   - "click" jQuery.Event
// options - pjax options
//
// Examples
//
//   $(document).on('click', 'a', $.pjax.click)
//   // is the same as
//   $(document).pjax('a')
//
//  $(document).on('click', 'a', function(event) {
//    var container = $(this).closest('[data-pjax-container]')
//    $.pjax.click(event, container)
//  })
//
// Returns nothing.
function handleClick(event, container, options) {
  options = optionsFor(container, options)

  var link = event.currentTarget

  if (link.tagName.toUpperCase() !== 'A')
    throw "$.fn.pjax or $.pjax.click requires an anchor element"

  // Middle click, cmd click, and ctrl click should open
  // links in a new tab as normal.
  if ( event.which > 1 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey )
    return

  // Ignore cross origin links
  if ( location.protocol !== link.protocol || location.hostname !== link.hostname )
    return

  // Ignore case when a hash is being tacked on the current URL
  if ( link.href.indexOf('#') > -1 && stripHash(link) == stripHash(location) )
    return

  // Ignore event with default prevented
  if (event.isDefaultPrevented())
    return

  var defaults = {
    url: link.href,
    container: $(link).attr('data-pjax'),
    target: link
  }

  var opts = $.extend({}, defaults, options)
  var clickEvent = $.Event('pjax:click')
  $(link).trigger(clickEvent, [opts])

  if (!clickEvent.isDefaultPrevented()) {
    pjax(opts)
    event.preventDefault()
    $(link).trigger('pjax:clicked', [opts])
  }
}

// Public: pjax on form submit handler
//
// Exported as $.pjax.submit
//
// event   - "click" jQuery.Event
// options - pjax options
//
// Examples
//
//  $(document).on('submit', 'form', function(event) {
//    var container = $(this).closest('[data-pjax-container]')
//    $.pjax.submit(event, container)
//  })
//
// Returns nothing.
function handleSubmit(event, container, options) {
  options = optionsFor(container, options)

  var form = event.currentTarget

  if (form.tagName.toUpperCase() !== 'FORM')
    throw "$.pjax.submit requires a form element"

  var defaults = {
    type: form.method.toUpperCase(),
    url: form.action,
    container: $(form).attr('data-pjax'),
    target: form
  }

  if (defaults.type !== 'GET' && window.FormData !== undefined) {
    defaults.data = new FormData(form);
    defaults.processData = false;
    defaults.contentType = false;
  } else {
    // Can't handle file uploads, exit
    if ($(form).find(':file').length) {
      return;
    }

    // Fallback to manually serializing the fields
    defaults.data = $(form).serializeArray();
  }

  pjax($.extend({}, defaults, options))

  event.preventDefault()
}

// Loads a URL with ajax, puts the response body inside a container,
// then pushState()'s the loaded URL.
//
// Works just like $.ajax in that it accepts a jQuery ajax
// settings object (with keys like url, type, data, etc).
//
// Accepts these extra keys:
//
// container - Where to stick the response body.
//             $(container).html(xhr.responseBody)
//      push - Whether to pushState the URL. Defaults to true (of course).
//   replace - Want to use replaceState instead? That's cool.
//
// Use it just like $.ajax:
//
//   var xhr = $.pjax({ url: this.href, container: '#main' })
//   console.log( xhr.readyState )
//
// Returns whatever $.ajax returns.
function pjax(options) {
  options = $.extend(true, {}, $.ajaxSettings, pjax.defaults, options)

  if ($.isFunction(options.url)) {
    options.url = options.url()
  }

  var target = options.target

  var hash = parseURL(options.url).hash

  var context = options.context = findContainerFor(options.container)

  // We want the browser to maintain two separate internal caches: one
  // for pjax'd partial page loads and one for normal page loads.
  // Without adding this secret parameter, some browsers will often
  // confuse the two.
  if (!options.data) options.data = {}
  if ($.isArray(options.data)) {
    options.data.push({name: '_pjax', value: context.selector})
  } else {
    options.data._pjax = context.selector
  }

  function fire(type, args, props) {
    if (!props) props = {}
    props.relatedTarget = target
    var event = $.Event(type, props)
    context.trigger(event, args)
    return !event.isDefaultPrevented()
  }

  var timeoutTimer

  options.beforeSend = function(xhr, settings) {
    // No timeout for non-GET requests
    // Its not safe to request the resource again with a fallback method.
    if (settings.type !== 'GET') {
      settings.timeout = 0
    }

    xhr.setRequestHeader('X-PJAX', 'true')
    xhr.setRequestHeader('X-PJAX-Container', context.selector)

    if (!fire('pjax:beforeSend', [xhr, settings]))
      return false

    if (settings.timeout > 0) {
      timeoutTimer = setTimeout(function() {
        if (fire('pjax:timeout', [xhr, options]))
          xhr.abort('timeout')
      }, settings.timeout)

      // Clear timeout setting so jquerys internal timeout isn't invoked
      settings.timeout = 0
    }

    var url = parseURL(settings.url)
    if (hash) url.hash = hash
    options.requestUrl = stripInternalParams(url)
  }

  options.complete = function(xhr, textStatus) {
    if (timeoutTimer)
      clearTimeout(timeoutTimer)

    fire('pjax:complete', [xhr, textStatus, options])

    fire('pjax:end', [xhr, options])
  }

  options.error = function(xhr, textStatus, errorThrown) {
    var container = extractContainer("", xhr, options)

    var allowed = fire('pjax:error', [xhr, textStatus, errorThrown, options])
    if (options.type == 'GET' && textStatus !== 'abort' && allowed) {
      locationReplace(container.url)
    }
  }


  options.success = function(data, status, xhr) {
    var previousState = pjax.state;

    // If $.pjax.defaults.version is a function, invoke it first.
    // Otherwise it can be a static string.
    var currentVersion = (typeof $.pjax.defaults.version === 'function') ?
      $.pjax.defaults.version() :
      $.pjax.defaults.version

    var latestVersion = xhr.getResponseHeader('X-PJAX-Version')

    var container = extractContainer(data, xhr, options)

    var url = parseURL(container.url)
    if (hash) {
      url.hash = hash
      container.url = url.href
    }

    // If there is a layout version mismatch, hard load the new url
    if (currentVersion && latestVersion && currentVersion !== latestVersion) {
      locationReplace(container.url)
      return
    }

    // If the new response is missing a body, hard load the page
    if (!container.contents) {
      locationReplace(container.url)
      return
    }

    pjax.state = {
      id: options.id || uniqueId(),
      url: container.url,
      title: container.title,
      container: context.selector,
      fragment: options.fragment,
      timeout: options.timeout
    }

    if (options.push || options.replace) {
      window.history.replaceState(pjax.state, container.title, container.url)
    }

    // Only blur the focus if the focused element is within the container.
    var blurFocus = $.contains(options.container, document.activeElement)

    // Clear out any focused controls before inserting new page contents.
    if (blurFocus) {
      try {
        document.activeElement.blur()
      } catch (e) { }
    }

    if (container.title) document.title = container.title

    fire('pjax:beforeReplace', [container.contents, options], {
      state: pjax.state,
      previousState: previousState
    })
    context.html(container.contents)

    // FF bug: Won't autofocus fields that are inserted via JS.
    // This behavior is incorrect. So if theres no current focus, autofocus
    // the last field.
    //
    // http://www.w3.org/html/wg/drafts/html/master/forms.html
    var autofocusEl = context.find('input[autofocus], textarea[autofocus]').last()[0]
    if (autofocusEl && document.activeElement !== autofocusEl) {
      autofocusEl.focus();
    }

    executeScriptTags(container.scripts)

    var scrollTo = options.scrollTo

    // Ensure browser scrolls to the element referenced by the URL anchor
    if (hash) {
      var name = decodeURIComponent(hash.slice(1))
      var target = document.getElementById(name) || document.getElementsByName(name)[0]
      if (target) scrollTo = $(target).offset().top
    }

    if (typeof scrollTo == 'number') $(window).scrollTop(scrollTo)

    fire('pjax:success', [data, status, xhr, options])
  }


  // Initialize pjax.state for the initial page load. Assume we're
  // using the container and options of the link we're loading for the
  // back button to the initial page. This ensures good back button
  // behavior.
  if (!pjax.state) {
    pjax.state = {
      id: uniqueId(),
      url: window.location.href,
      title: document.title,
      container: context.selector,
      fragment: options.fragment,
      timeout: options.timeout
    }
    window.history.replaceState(pjax.state, document.title)
  }

  // Cancel the current request if we're already pjaxing
  abortXHR(pjax.xhr)

  pjax.options = options
  var xhr = pjax.xhr = $.ajax(options)

  if (xhr.readyState > 0) {
    if (options.push && !options.replace) {
      // Cache current container element before replacing it
      cachePush(pjax.state.id, cloneContents(context))

      window.history.pushState(null, "", options.requestUrl)
    }

    fire('pjax:start', [xhr, options])
    fire('pjax:send', [xhr, options])
  }

  return pjax.xhr
}

// Public: Reload current page with pjax.
//
// Returns whatever $.pjax returns.
function pjaxReload(container, options) {
  var defaults = {
    url: window.location.href,
    push: false,
    replace: true,
    scrollTo: false
  }

  return pjax($.extend(defaults, optionsFor(container, options)))
}

// Internal: Hard replace current state with url.
//
// Work for around WebKit
//   https://bugs.webkit.org/show_bug.cgi?id=93506
//
// Returns nothing.
function locationReplace(url) {
  window.history.replaceState(null, "", pjax.state.url)
  window.location.replace(url)
}


var initialPop = true
var initialURL = window.location.href
var initialState = window.history.state

// Initialize $.pjax.state if possible
// Happens when reloading a page and coming forward from a different
// session history.
if (initialState && initialState.container) {
  pjax.state = initialState
}

// Non-webkit browsers don't fire an initial popstate event
if ('state' in window.history) {
  initialPop = false
}

// popstate handler takes care of the back and forward buttons
//
// You probably shouldn't use pjax on pages with other pushState
// stuff yet.
function onPjaxPopstate(event) {

  // Hitting back or forward should override any pending PJAX request.
  if (!initialPop) {
    abortXHR(pjax.xhr)
  }

  var previousState = pjax.state
  var state = event.state
  var direction

  if (state && state.container) {
    // When coming forward from a separate history session, will get an
    // initial pop with a state we are already at. Skip reloading the current
    // page.
    if (initialPop && initialURL == state.url) return

    if (previousState) {
      // If popping back to the same state, just skip.
      // Could be clicking back from hashchange rather than a pushState.
      if (previousState.id === state.id) return

      // Since state IDs always increase, we can deduce the navigation direction
      direction = previousState.id < state.id ? 'forward' : 'back'
    }

    var cache = cacheMapping[state.id] || []
    var container = $(cache[0] || state.container), contents = cache[1]

    if (container.length) {
      if (previousState) {
        // Cache current container before replacement and inform the
        // cache which direction the history shifted.
        cachePop(direction, previousState.id, cloneContents(container))
      }

      var popstateEvent = $.Event('pjax:popstate', {
        state: state,
        direction: direction
      })
      container.trigger(popstateEvent)

      var options = {
        id: state.id,
        url: state.url,
        container: container,
        push: false,
        fragment: state.fragment,
        timeout: state.timeout,
        scrollTo: false
      }

      if (contents) {
        container.trigger('pjax:start', [null, options])

        pjax.state = state
        if (state.title) document.title = state.title
        var beforeReplaceEvent = $.Event('pjax:beforeReplace', {
          state: state,
          previousState: previousState
        })
        container.trigger(beforeReplaceEvent, [contents, options])
        container.html(contents)

        container.trigger('pjax:end', [null, options])
      } else {
        pjax(options)
      }

      // Force reflow/relayout before the browser tries to restore the
      // scroll position.
      container[0].offsetHeight
    } else {
      locationReplace(location.href)
    }
  }
  initialPop = false
}

// Fallback version of main pjax function for browsers that don't
// support pushState.
//
// Returns nothing since it retriggers a hard form submission.
function fallbackPjax(options) {
  var url = $.isFunction(options.url) ? options.url() : options.url,
      method = options.type ? options.type.toUpperCase() : 'GET'

  var form = $('<form>', {
    method: method === 'GET' ? 'GET' : 'POST',
    action: url,
    style: 'display:none'
  })

  if (method !== 'GET' && method !== 'POST') {
    form.append($('<input>', {
      type: 'hidden',
      name: '_method',
      value: method.toLowerCase()
    }))
  }

  var data = options.data
  if (typeof data === 'string') {
    $.each(data.split('&'), function(index, value) {
      var pair = value.split('=')
      form.append($('<input>', {type: 'hidden', name: pair[0], value: pair[1]}))
    })
  } else if ($.isArray(data)) {
    $.each(data, function(index, value) {
      form.append($('<input>', {type: 'hidden', name: value.name, value: value.value}))
    })
  } else if (typeof data === 'object') {
    var key
    for (key in data)
      form.append($('<input>', {type: 'hidden', name: key, value: data[key]}))
  }

  $(document.body).append(form)
  form.submit()
}

// Internal: Abort an XmlHttpRequest if it hasn't been completed,
// also removing its event handlers.
function abortXHR(xhr) {
  if ( xhr && xhr.readyState < 4) {
    xhr.onreadystatechange = $.noop
    xhr.abort()
  }
}

// Internal: Generate unique id for state object.
//
// Use a timestamp instead of a counter since ids should still be
// unique across page loads.
//
// Returns Number.
function uniqueId() {
  return (new Date).getTime()
}

function cloneContents(container) {
  var cloned = container.clone()
  // Unmark script tags as already being eval'd so they can get executed again
  // when restored from cache. HAXX: Uses jQuery internal method.
  cloned.find('script').each(function(){
    if (!this.src) jQuery._data(this, 'globalEval', false)
  })
  return [container.selector, cloned.contents()]
}

// Internal: Strip internal query params from parsed URL.
//
// Returns sanitized url.href String.
function stripInternalParams(url) {
  url.search = url.search.replace(/([?&])(_pjax|_)=[^&]*/g, '')
  return url.href.replace(/\?($|#)/, '$1')
}

// Internal: Parse URL components and returns a Locationish object.
//
// url - String URL
//
// Returns HTMLAnchorElement that acts like Location.
function parseURL(url) {
  var a = document.createElement('a')
  a.href = url
  return a
}

// Internal: Return the `href` component of given URL object with the hash
// portion removed.
//
// location - Location or HTMLAnchorElement
//
// Returns String
function stripHash(location) {
  return location.href.replace(/#.*/, '')
}

// Internal: Build options Object for arguments.
//
// For convenience the first parameter can be either the container or
// the options object.
//
// Examples
//
//   optionsFor('#container')
//   // => {container: '#container'}
//
//   optionsFor('#container', {push: true})
//   // => {container: '#container', push: true}
//
//   optionsFor({container: '#container', push: true})
//   // => {container: '#container', push: true}
//
// Returns options Object.
function optionsFor(container, options) {
  // Both container and options
  if ( container && options )
    options.container = container

  // First argument is options Object
  else if ( $.isPlainObject(container) )
    options = container

  // Only container
  else
    options = {container: container}

  // Find and validate container
  if (options.container)
    options.container = findContainerFor(options.container)

  return options
}

// Internal: Find container element for a variety of inputs.
//
// Because we can't persist elements using the history API, we must be
// able to find a String selector that will consistently find the Element.
//
// container - A selector String, jQuery object, or DOM Element.
//
// Returns a jQuery object whose context is `document` and has a selector.
function findContainerFor(container) {
  container = $(container)

  if ( !container.length ) {
    throw "no pjax container for " + container.selector
  } else if ( container.selector !== '' && container.context === document ) {
    return container
  } else if ( container.attr('id') ) {
    return $('#' + container.attr('id'))
  } else {
    throw "cant get selector for pjax container!"
  }
}

// Internal: Filter and find all elements matching the selector.
//
// Where $.fn.find only matches descendants, findAll will test all the
// top level elements in the jQuery object as well.
//
// elems    - jQuery object of Elements
// selector - String selector to match
//
// Returns a jQuery object.
function findAll(elems, selector) {
  return elems.filter(selector).add(elems.find(selector));
}

function parseHTML(html) {
  return $.parseHTML(html, document, true)
}

// Internal: Extracts container and metadata from response.
//
// 1. Extracts X-PJAX-URL header if set
// 2. Extracts inline <title> tags
// 3. Builds response Element and extracts fragment if set
//
// data    - String response data
// xhr     - XHR response
// options - pjax options Object
//
// Returns an Object with url, title, and contents keys.
function extractContainer(data, xhr, options) {
  var obj = {}, fullDocument = /<html/i.test(data)

  // Prefer X-PJAX-URL header if it was set, otherwise fallback to
  // using the original requested url.
  var serverUrl = xhr.getResponseHeader('X-PJAX-URL')
  obj.url = serverUrl ? stripInternalParams(parseURL(serverUrl)) : options.requestUrl

  // Attempt to parse response html into elements
  if (fullDocument) {
    var $head = $(parseHTML(data.match(/<head[^>]*>([\s\S.]*)<\/head>/i)[0]))
    var $body = $(parseHTML(data.match(/<body[^>]*>([\s\S.]*)<\/body>/i)[0]))
  } else {
    var $head = $body = $(parseHTML(data))
  }

  // If response data is empty, return fast
  if ($body.length === 0)
    return obj

  // If there's a <title> tag in the header, use it as
  // the page's title.
  obj.title = findAll($head, 'title').last().text()

  if (options.fragment) {
    // If they specified a fragment, look for it in the response
    // and pull it out.
    if (options.fragment === 'body') {
      var $fragment = $body
    } else {
      var $fragment = findAll($body, options.fragment).first()
    }

    if ($fragment.length) {
      obj.contents = options.fragment === 'body' ? $fragment : $fragment.contents()

      // If there's no title, look for data-title and title attributes
      // on the fragment
      if (!obj.title)
        obj.title = $fragment.attr('title') || $fragment.data('title')
    }

  } else if (!fullDocument) {
    obj.contents = $body
  }

  // Clean up any <title> tags
  if (obj.contents) {
    // Remove any parent title elements
    obj.contents = obj.contents.not(function() { return $(this).is('title') })

    // Then scrub any titles from their descendants
    obj.contents.find('title').remove()

    // Gather all script[src] elements
    obj.scripts = findAll(obj.contents, 'script[src]').remove()
    obj.contents = obj.contents.not(obj.scripts)
  }

  // Trim any whitespace off the title
  if (obj.title) obj.title = $.trim(obj.title)

  return obj
}

// Load an execute scripts using standard script request.
//
// Avoids jQuery's traditional $.getScript which does a XHR request and
// globalEval.
//
// scripts - jQuery object of script Elements
//
// Returns nothing.
function executeScriptTags(scripts) {
  if (!scripts) return

  var existingScripts = $('script[src]')

  scripts.each(function() {
    var src = this.src
    var matchedScripts = existingScripts.filter(function() {
      return this.src === src
    })
    if (matchedScripts.length) return

    var script = document.createElement('script')
    var type = $(this).attr('type')
    if (type) script.type = type
    script.src = $(this).attr('src')
    document.head.appendChild(script)
  })
}

// Internal: History DOM caching class.
var cacheMapping      = {}
var cacheForwardStack = []
var cacheBackStack    = []

// Push previous state id and container contents into the history
// cache. Should be called in conjunction with `pushState` to save the
// previous container contents.
//
// id    - State ID Number
// value - DOM Element to cache
//
// Returns nothing.
function cachePush(id, value) {
  cacheMapping[id] = value
  cacheBackStack.push(id)

  // Remove all entries in forward history stack after pushing a new page.
  trimCacheStack(cacheForwardStack, 0)

  // Trim back history stack to max cache length.
  trimCacheStack(cacheBackStack, pjax.defaults.maxCacheLength)
}

// Shifts cache from directional history cache. Should be
// called on `popstate` with the previous state id and container
// contents.
//
// direction - "forward" or "back" String
// id        - State ID Number
// value     - DOM Element to cache
//
// Returns nothing.
function cachePop(direction, id, value) {
  var pushStack, popStack
  cacheMapping[id] = value

  if (direction === 'forward') {
    pushStack = cacheBackStack
    popStack  = cacheForwardStack
  } else {
    pushStack = cacheForwardStack
    popStack  = cacheBackStack
  }

  pushStack.push(id)
  if (id = popStack.pop())
    delete cacheMapping[id]

  // Trim whichever stack we just pushed to to max cache length.
  trimCacheStack(pushStack, pjax.defaults.maxCacheLength)
}

// Trim a cache stack (either cacheBackStack or cacheForwardStack) to be no
// longer than the specified length, deleting cached DOM elements as necessary.
//
// stack  - Array of state IDs
// length - Maximum length to trim to
//
// Returns nothing.
function trimCacheStack(stack, length) {
  while (stack.length > length)
    delete cacheMapping[stack.shift()]
}

// Public: Find version identifier for the initial page load.
//
// Returns String version or undefined.
function findVersion() {
  return $('meta').filter(function() {
    var name = $(this).attr('http-equiv')
    return name && name.toUpperCase() === 'X-PJAX-VERSION'
  }).attr('content')
}

// Install pjax functions on $.pjax to enable pushState behavior.
//
// Does nothing if already enabled.
//
// Examples
//
//     $.pjax.enable()
//
// Returns nothing.
function enable() {
  $.fn.pjax = fnPjax
  $.pjax = pjax
  $.pjax.enable = $.noop
  $.pjax.disable = disable
  $.pjax.click = handleClick
  $.pjax.submit = handleSubmit
  $.pjax.reload = pjaxReload
  $.pjax.defaults = {
    timeout: 650,
    push: true,
    replace: false,
    type: 'GET',
    dataType: 'html',
    scrollTo: 0,
    maxCacheLength: 20,
    version: findVersion
  }
  $(window).on('popstate.pjax', onPjaxPopstate)
}

// Disable pushState behavior.
//
// This is the case when a browser doesn't support pushState. It is
// sometimes useful to disable pushState for debugging on a modern
// browser.
//
// Examples
//
//     $.pjax.disable()
//
// Returns nothing.
function disable() {
  $.fn.pjax = function() { return this }
  $.pjax = fallbackPjax
  $.pjax.enable = enable
  $.pjax.disable = $.noop
  $.pjax.click = $.noop
  $.pjax.submit = $.noop
  $.pjax.reload = function() { window.location.reload() }

  $(window).off('popstate.pjax', onPjaxPopstate)
}


// Add the state property to jQuery's event object so we can use it in
// $(window).bind('popstate')
if ( $.inArray('state', $.event.props) < 0 )
  $.event.props.push('state')

// Is pjax supported by this browser?
$.support.pjax =
  window.history && window.history.pushState && window.history.replaceState &&
  // pushState isn't reliable on iOS until 5.
  !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]\D|WebApps\/.+CFNetwork)/)

$.support.pjax ? enable() : disable()

})(jQuery);
/* NProgress, (c) 2013, 2014 Rico Sta. Cruz - http://ricostacruz.com/nprogress
 * @license MIT */

;(function(root, factory) {

  if (typeof define === 'function' && define.amd) {
    define(factory);
  } else if (typeof exports === 'object') {
    module.exports = factory();
  } else {
    root.NProgress = factory();
  }

})(this, function() {
  var NProgress = {};

  NProgress.version = '0.2.0';

  var Settings = NProgress.settings = {
    minimum: 0.08,
    easing: 'linear',
    positionUsing: '',
    speed: 200,
    trickle: true,
    trickleSpeed: 200,
    showSpinner: false,
    barSelector: '[role="bar"]',
    spinnerSelector: '[role="spinner"]',
    parent: 'body',
    template: '<div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div>'
  };

  /**
   * Updates configuration.
   *
   *     NProgress.configure({
   *       minimum: 0.1
   *     });
   */
  NProgress.configure = function(options) {
    var key, value;
    for (key in options) {
      value = options[key];
      if (value !== undefined && options.hasOwnProperty(key)) Settings[key] = value;
    }

    return this;
  };

  /**
   * Last number.
   */

  NProgress.status = null;

  /**
   * Sets the progress bar status, where `n` is a number from `0.0` to `1.0`.
   *
   *     NProgress.set(0.4);
   *     NProgress.set(1.0);
   */

  NProgress.set = function(n) {
    var started = NProgress.isStarted();

    n = clamp(n, Settings.minimum, 1);
    NProgress.status = (n === 1 ? null : n);

    var progress = NProgress.render(!started),
        bar      = progress.querySelector(Settings.barSelector),
        speed    = Settings.speed,
        ease     = Settings.easing;

    progress.offsetWidth; /* Repaint */

    queue(function(next) {
      // Set positionUsing if it hasn't already been set
      if (Settings.positionUsing === '') Settings.positionUsing = NProgress.getPositioningCSS();

      // Add transition
      css(bar, barPositionCSS(n, speed, ease));

      if (n === 1) {
        // Fade out
        css(progress, {
          transition: 'none',
          opacity: 1
        });
        progress.offsetWidth; /* Repaint */

        setTimeout(function() {
          css(progress, {
            transition: 'all ' + speed + 'ms linear',
            opacity: 0
          });
          setTimeout(function() {
            NProgress.remove();
            next();
          }, speed);
        }, speed);
      } else {
        setTimeout(next, speed);
      }
    });

    return this;
  };

  NProgress.isStarted = function() {
    return typeof NProgress.status === 'number';
  };

  /**
   * Shows the progress bar.
   * This is the same as setting the status to 0%, except that it doesn't go backwards.
   *
   *     NProgress.start();
   *
   */
  NProgress.start = function() {
    if (!NProgress.status) NProgress.set(0);

    var work = function() {
      setTimeout(function() {
        if (!NProgress.status) return;
        NProgress.trickle();
        work();
      }, Settings.trickleSpeed);
    };

    if (Settings.trickle) work();

    return this;
  };

  /**
   * Hides the progress bar.
   * This is the *sort of* the same as setting the status to 100%, with the
   * difference being `done()` makes some placebo effect of some realistic motion.
   *
   *     NProgress.done();
   *
   * If `true` is passed, it will show the progress bar even if its hidden.
   *
   *     NProgress.done(true);
   */

  NProgress.done = function(force) {
    if (!force && !NProgress.status) return this;

    return NProgress.inc(0.3 + 0.5 * Math.random()).set(1);
  };

  /**
   * Increments by a random amount.
   */

  NProgress.inc = function(amount) {
    var n = NProgress.status;

    if (!n) {
      return NProgress.start();
    } else if(n > 1) {
      return;
    } else {
      if (typeof amount !== 'number') {
        if (n >= 0 && n < 0.2) { amount = 0.1; }
        else if (n >= 0.2 && n < 0.5) { amount = 0.04; }
        else if (n >= 0.5 && n < 0.8) { amount = 0.02; }
        else if (n >= 0.8 && n < 0.99) { amount = 0.005; }
        else { amount = 0; }
      }

      n = clamp(n + amount, 0, 0.994);
      return NProgress.set(n);
    }
  };

  NProgress.trickle = function() {
    return NProgress.inc();
  };

  /**
   * Waits for all supplied jQuery promises and
   * increases the progress as the promises resolve.
   *
   * @param $promise jQUery Promise
   */
  (function() {
    var initial = 0, current = 0;

    NProgress.promise = function($promise) {
      if (!$promise || $promise.state() === "resolved") {
        return this;
      }

      if (current === 0) {
        NProgress.start();
      }

      initial++;
      current++;

      $promise.always(function() {
        current--;
        if (current === 0) {
            initial = 0;
            NProgress.done();
        } else {
            NProgress.set((initial - current) / initial);
        }
      });

      return this;
    };

  })();

  /**
   * (Internal) renders the progress bar markup based on the `template`
   * setting.
   */

  NProgress.render = function(fromStart) {
    if (NProgress.isRendered()) return document.getElementById('nprogress');

    addClass(document.documentElement, 'nprogress-busy');

    var progress = document.createElement('div');
    progress.id = 'nprogress';
    progress.innerHTML = Settings.template;

    var bar      = progress.querySelector(Settings.barSelector),
        perc     = fromStart ? '-100' : toBarPerc(NProgress.status || 0),
        parent   = document.querySelector(Settings.parent),
        spinner;

    css(bar, {
      transition: 'all 0 linear',
      transform: 'translate3d(' + perc + '%,0,0)'
    });

    if (!Settings.showSpinner) {
      spinner = progress.querySelector(Settings.spinnerSelector);
      spinner && removeElement(spinner);
    }

    if (parent != document.body) {
      addClass(parent, 'nprogress-custom-parent');
    }

    parent.appendChild(progress);
    return progress;
  };

  /**
   * Removes the element. Opposite of render().
   */

  NProgress.remove = function() {
    removeClass(document.documentElement, 'nprogress-busy');
    removeClass(document.querySelector(Settings.parent), 'nprogress-custom-parent');
    var progress = document.getElementById('nprogress');
    progress && removeElement(progress);
  };

  /**
   * Checks if the progress bar is rendered.
   */

  NProgress.isRendered = function() {
    return !!document.getElementById('nprogress');
  };

  /**
   * Determine which positioning CSS rule to use.
   */

  NProgress.getPositioningCSS = function() {
    // Sniff on document.body.style
    var bodyStyle = document.body.style;

    // Sniff prefixes
    var vendorPrefix = ('WebkitTransform' in bodyStyle) ? 'Webkit' :
                       ('MozTransform' in bodyStyle) ? 'Moz' :
                       ('msTransform' in bodyStyle) ? 'ms' :
                       ('OTransform' in bodyStyle) ? 'O' : '';

    if (vendorPrefix + 'Perspective' in bodyStyle) {
      // Modern browsers with 3D support, e.g. Webkit, IE10
      return 'translate3d';
    } else if (vendorPrefix + 'Transform' in bodyStyle) {
      // Browsers without 3D support, e.g. IE9
      return 'translate';
    } else {
      // Browsers without translate() support, e.g. IE7-8
      return 'margin';
    }
  };

  /**
   * Helpers
   */

  function clamp(n, min, max) {
    if (n < min) return min;
    if (n > max) return max;
    return n;
  }

  /**
   * (Internal) converts a percentage (`0..1`) to a bar translateX
   * percentage (`-100%..0%`).
   */

  function toBarPerc(n) {
    return (-1 + n) * 100;
  }


  /**
   * (Internal) returns the correct CSS for changing the bar's
   * position given an n percentage, and speed and ease from Settings
   */

  function barPositionCSS(n, speed, ease) {
    var barCSS;

    if (Settings.positionUsing === 'translate3d') {
      barCSS = { transform: 'translate3d('+toBarPerc(n)+'%,0,0)' };
    } else if (Settings.positionUsing === 'translate') {
      barCSS = { transform: 'translate('+toBarPerc(n)+'%,0)' };
    } else {
      barCSS = { 'margin-left': toBarPerc(n)+'%' };
    }

    barCSS.transition = 'all '+speed+'ms '+ease;

    return barCSS;
  }

  /**
   * (Internal) Queues a function to be executed.
   */

  var queue = (function() {
    var pending = [];

    function next() {
      var fn = pending.shift();
      if (fn) {
        fn(next);
      }
    }

    return function(fn) {
      pending.push(fn);
      if (pending.length == 1) next();
    };
  })();

  /**
   * (Internal) Applies css properties to an element, similar to the jQuery
   * css method.
   *
   * While this helper does assist with vendor prefixed property names, it
   * does not perform any manipulation of values prior to setting styles.
   */

  var css = (function() {
    var cssPrefixes = [ 'Webkit', 'O', 'Moz', 'ms' ],
        cssProps    = {};

    function camelCase(string) {
      return string.replace(/^-ms-/, 'ms-').replace(/-([\da-z])/gi, function(match, letter) {
        return letter.toUpperCase();
      });
    }

    function getVendorProp(name) {
      var style = document.body.style;
      if (name in style) return name;

      var i = cssPrefixes.length,
          capName = name.charAt(0).toUpperCase() + name.slice(1),
          vendorName;
      while (i--) {
        vendorName = cssPrefixes[i] + capName;
        if (vendorName in style) return vendorName;
      }

      return name;
    }

    function getStyleProp(name) {
      name = camelCase(name);
      return cssProps[name] || (cssProps[name] = getVendorProp(name));
    }

    function applyCss(element, prop, value) {
      prop = getStyleProp(prop);
      element.style[prop] = value;
    }

    return function(element, properties) {
      var args = arguments,
          prop,
          value;

      if (args.length == 2) {
        for (prop in properties) {
          value = properties[prop];
          if (value !== undefined && properties.hasOwnProperty(prop)) applyCss(element, prop, value);
        }
      } else {
        applyCss(element, args[1], args[2]);
      }
    }
  })();

  /**
   * (Internal) Determines if an element or space separated list of class names contains a class name.
   */

  function hasClass(element, name) {
    var list = typeof element == 'string' ? element : classList(element);
    return list.indexOf(' ' + name + ' ') >= 0;
  }

  /**
   * (Internal) Adds a class to an element.
   */

  function addClass(element, name) {
    var oldList = classList(element),
        newList = oldList + name;

    if (hasClass(oldList, name)) return;

    // Trim the opening space.
    element.className = newList.substring(1);
  }

  /**
   * (Internal) Removes a class from an element.
   */

  function removeClass(element, name) {
    var oldList = classList(element),
        newList;

    if (!hasClass(element, name)) return;

    // Replace the class name.
    newList = oldList.replace(' ' + name + ' ', ' ');

    // Trim the opening and closing spaces.
    element.className = newList.substring(1, newList.length - 1);
  }

  /**
   * (Internal) Gets a space separated list of the class names on the element.
   * The list is wrapped with a single space on each end to facilitate finding
   * matches within the list.
   */

  function classList(element) {
    return (' ' + (element && element.className || '') + ' ').replace(/\s+/gi, ' ');
  }

  /**
   * (Internal) Removes an element from the DOM.
   */

  function removeElement(element) {
    element && element.parentNode && element.parentNode.removeChild(element);
  }

  return NProgress;
});
jQuery(function($) {

topMenu = function() {
	var count = $("#ultopmenu").children().length;
	$('#topmenu li#ml-' + count).css('background-image', 'none');
	$('#topmenu li#ml-' + count + '.mainitem A').css('marginRight', '0px');
	tout = 500;


	/*~~~~~~Меню~~~~~~~~~~~~~~~~*/

	var curMenuitem = null,
	hideMenuTimer = null,
	showSubmenuTimer = null;
	LiWidth = null;
	LiWidth = $('div#topmenu').outerWidth();
	//$('ul#ultopmenu li.mainitem').each(function() {
	//	LiWidth += $(this).outerWidth();
	//});
	//alert(LiWidth);

	$('#topmenu li.mainitem.parent').hover(
	function() {
	var self = $(this),
	   selfId = self.attr('id'),
	   realId = selfId.substr(3,4),
	   lft = self.position().left + $('#submenu-' + selfId).width() >= LiWidth ?
	           LiWidth - $('#submenu-' + selfId).width() - 24 :
	           self.position().left - 16;

	showSubmenuTimer = setTimeout(function() {
	 $('#submenu-' + selfId)
	   .css({
	     left: lft,
	     top:  self.position().top, // + 50
	     position: 'relative'
	   })
	   .show();
	   self.addClass('mainitem paractive');

	if(self.children('.shadow').size() == 0){
	self.wrapInner('<span class="shadow"></span>');
	}
	}, 200);
	curMenuitem = $(this);
	$(this).addClass('hover');
	},

	function() {
	var self = this,
	id = $(this).attr('id');
	hideMenuTimer = setTimeout(function() {
	 $('#submenu-' + id).hide();
	 $(self).removeClass('hover');
	 $(self).removeClass('paractive');
	 $(".mainitem span.shadow a").unwrap();
	}, 50);
	clearInterval(showSubmenuTimer);
	}
	);





	$('.submenu').hover(
	function() {
	clearInterval(hideMenuTimer);
	},
	function() {
	$(this).hide();
	$('.mainitem').removeClass('hover');
	$('.mainitem').removeClass('paractive');
	$(".mainitem span.shadow a").unwrap();
	}
	);

}

topMenu();

});

function dsearchURL() {
	var dcatid = jQuery('#dcatid :selected').val();
    if (dcatid === undefined) {
    	dcatid = jQuery("input[name='catid[]']:checked").val();
    }

	var diski_pcd = jQuery('#product_type_2_diski_pcd :selected').val();
	var diski_diametr = jQuery('#product_type_2_diski_diametr :selected').val();

	if (jQuery("input[name='product_type_2_diski_type[]']").is(':checked')) {
		var diski_type  = new Array();
		jQuery("input[name='product_type_2_diski_type[]']:checked").each(function() {
	    	diski_type.push(jQuery(this).val());
		});
		if (diski_type == '' || diski_type == '0' || diski_type.length == '3') {diski_type = 'all';}
	} else {
		var diski_type = jQuery('#product_type_2_diski_type :selected').val();
		if (diski_type == '' || diski_type == '0' || diski_type === undefined) {diski_type = 'all';}
	}

    if (dcatid == '' || dcatid == '0' || dcatid === undefined) {dcatid = 'all';}
	// остатки  рым киев польша
   	if (jQuery('input[name=dstock_sklad]').is(':checked') && jQuery('input[name=dstock_sklad]:checked').val() > '0') {
		stock_sklad = '?dstock_sklad='+jQuery('input[name=dstock_sklad]:checked').val();
	} else {
		stock_sklad = '';
	}

	diski_pcd = diski_pcd.replace('.', '_');
	diski_diametr = diski_diametr.replace('.', '_');

	var search_url = '/shop/diski-'+diski_pcd+'-R'+diski_diametr+'-w0-etot0-etdo0-dia0-'+diski_type+'-'+dcatid+'-1-0-0-0-0-search'+stock_sklad;
	window.location = search_url;
	//alert(search_url);
}

function ssearchURL() {
	var scatid = jQuery('#scatid :selected').val();
    if (scatid === undefined) {
    	scatid = jQuery("input[name='catid[]']:checked").val();
    }

    var shini_width = jQuery('#product_type_1_shini_width :selected').val();
	var shini_height = jQuery('#product_type_1_shini_height :selected').val();
	var shini_diametr = jQuery('#product_type_1_shini_diametr :selected').val();


	if (jQuery("input[name='product_type_1_shini_season[]']").is(':checked')) {
		var shini_season  = new Array();
		jQuery("input[name='product_type_1_shini_season[]']:checked").each(function() {
	    	shini_season.push(jQuery(this).val());
		});
		if (shini_season == '' || shini_season == '0' || shini_season.length == '3') {shini_season = 'all';}
	} else {
		var shini_season = jQuery('#product_type_1_shini_season :selected').val();
		if (shini_season == '' || shini_season == '0' || shini_season === undefined) {shini_season = 'all';}
	}

	/// грузовые шины - выбираем все сезоны!
	if (shini_width < 135 || shini_diametr == '17.5' || shini_diametr == '19.5' || shini_diametr == '22.5') {shini_season = 'all';}


	if (scatid == '' || scatid == '0' || scatid === undefined) {scatid = 'all';}

	shini_width = shini_width.replace('.', '_');
	shini_height = shini_height.replace('.', '_');
	shini_diametr = shini_diametr.replace('.', '_');

	if (jQuery('#sship').is(':checked')) {shini_ship = '1';} else {shini_ship = '0'}

	var param = new Array();
	// остатки  крым киев польша
    if (jQuery('input[name=sstock_sklad]').is(':checked') && jQuery('input[name=sstock_sklad]:checked').val() > '0') {
		param.push('sstock_sklad='+jQuery('input[name=sstock_sklad]:checked').val());
	}
	if (jQuery('#runflat').is(':checked')) {param.push('runflat=1');}

	var request_uri = param.join('&');
	if (request_uri != '') {
		request_uri = '?'+request_uri;
	}


	var search_url = '/shop/shiny-'+shini_width+'-'+shini_height+'-R'+shini_diametr+'-'+shini_season+'-'+shini_ship+'-'+scatid+'-1-0-0-0-0-search'+request_uri;
	window.location = search_url;
	//alert(scatid);
}



////////////////////////////////////////////////////////////////////////////////
function dpsearchURL() {
    var dcatid  = new Array();
	jQuery("input[name='catid[]']:checked").each(function() {
    	dcatid.push(jQuery(this).val());
	});

	var diski_type  = new Array();
	jQuery("input[name='product_type_2_diski_type[]']:checked").each(function() {
    	diski_type.push(jQuery(this).val());
	});

	var diski_pcd = jQuery('#product_type_2_diski_pcd :selected').val();
	var diski_diametr = jQuery('#product_type_2_diski_diametr :selected').val();
	var diski_dia = jQuery('#product_type_2_diski_dia :selected').val();

	var diski_etot = jQuery('#product_type_2_diski_et_ot').val();
	var diski_etdo = jQuery('#product_type_2_diski_et_do').val();

	var diski_w = jQuery('#product_type_2_diski_w :selected').val();
	var diski_w2 = jQuery('#product_type_2_diski_w2 :selected').val();

	var order_by = jQuery('input[name=order_by]').val();
	var id_table = jQuery('input[name=product_type_id_table]').val();

	diski_pcd = diski_pcd.replace('.', '_');
	diski_diametr = diski_diametr.replace('.', '_');
	diski_dia = diski_dia.replace('.', '_');
	diski_etot = Math.round(diski_etot);
  	diski_etdo = Math.round(diski_etdo);
	if (diski_etot < 0) {diski_etot = 'm'+Math.abs(diski_etot);}
	if (diski_etdo < 0) {diski_etdo = 'm'+Math.abs(diski_etdo);}

    diski_w = diski_w.replace('.', '_');
	diski_w2 = diski_w2.replace('.', '_');

	if (diski_w2 > '0' && jQuery('#show_hide_razmer2').css('visibility')=='visible') {
		var razmer2	= '-w'+diski_w2;
	} else {
		var razmer2 = '';
	}

	if (dcatid == '' || dcatid == '0') {dcatid = 'all';}
	if (dcatid.length == '1' && dcatid != 'all') {dcatid = jQuery('label[for=cat'+dcatid+']').text().replace('-', ' ') + '_'+dcatid;}

	if (diski_type == '' || diski_type == '0' || diski_type.length == '3') {diski_type = 'all';}
	if (jQuery('#dprice').is(':checked')) {price = '1';} else {price = '0'}
	if (jQuery('#dstock').is(':checked')) {stock = '1';} else {stock = '0'}

	/// диапазон цен
	//var price_range = "?rprice=" + jQuery('input[name=price_from]').val() + "_" + jQuery('input[name=price_to]').val();

    // остатки  рым киев польша
   	if (jQuery('input[name=dstock_sklad]').is(':checked') && jQuery('input[name=dstock_sklad]:checked').val() > '0') {
		stock_sklad = '?dstock_sklad='+jQuery('input[name=dstock_sklad]:checked').val();
	} else {
		stock_sklad = '';
	}

	// Dia строго
	if (jQuery('input[name=dia_strogo]').is(':checked')) {
		if (stock_sklad != '') {
			dia_strogo = '&dia_strogo=1';
		} else {
			dia_strogo = '?dia_strogo=1';
		}
	} else {
		dia_strogo = '';
	}


	var search_url = '/shop/diski-'+diski_pcd+'-R'+diski_diametr+'-w'+diski_w+'-etot'+diski_etot+'-etdo'+diski_etdo+'-dia'+diski_dia+'-'+diski_type+'-'+dcatid+'-'+price+'-'+stock+'-'+order_by+'-'+id_table+'-0'+razmer2+'-search'+stock_sklad+dia_strogo;
	window.location = search_url;
}

function spsearchURL() {
	var scatid  = new Array();
	jQuery("input[name='catid[]']:checked").each(function() {
    	scatid.push(jQuery(this).val());
	});

	var shini_season  = new Array();
	jQuery("input[name='product_type_1_shini_season[]']:checked").each(function() {
    	shini_season.push(jQuery(this).val());
	});

    var shini_width = jQuery('#product_type_1_shini_width :selected').val();
	var shini_height = jQuery('#product_type_1_shini_height :selected').val();
	var shini_diametr = jQuery('#product_type_1_shini_diametr :selected').val();

	var shini_width2 = jQuery('#product_type_1_shini_width2 :selected').val();
	var shini_height2 = jQuery('#product_type_1_shini_height2 :selected').val();
	var shini_diametr2 = jQuery('#product_type_1_shini_diametr2 :selected').val();

	var order_by = jQuery('input[name=order_by]').val();
	var id_table = jQuery('input[name=product_type_id_table]').val();

	/// грузовые шины - выбираем все сезоны!
	if (shini_width < 135 || shini_diametr == '17.5' || shini_diametr == '19.5' || shini_diametr == '22.5') {shini_season = 'all';}

	shini_width = shini_width.replace('.', '_');
	shini_height = shini_height.replace('.', '_');
	shini_diametr = shini_diametr.replace('.', '_');

	shini_width2 = shini_width2.replace('.', '_');
	shini_height2 = shini_height2.replace('.', '_');
	shini_diametr2 = shini_diametr2.replace('.', '_');

	if (shini_width2 != '0' && shini_height2 != '0' && shini_diametr2 != '0' && jQuery('#show_hide_razmer2').css('visibility')=='visible') {
		var razmer2	= '-'+shini_width2+'-'+shini_height2+'-R'+shini_diametr2;
	} else {
		var razmer2 = '';
	}

    if (scatid == '' || scatid == '0') {scatid = 'all';}
	if (scatid.length == '1' && scatid != 'all') {scatid = jQuery('label[for=cat'+scatid+']').text().replace('-', ' ') + '_'+scatid;}


	if (shini_season == '' || shini_season == '0' || shini_season.length == '3') {shini_season = 'all';}
	if (jQuery('#sship').is(':checked')) {shini_ship = '1';} else {shini_ship = '0'}
    if (jQuery('#sprice').is(':checked')) {price = '1';} else {price = '0'}
    if (jQuery('#sstock').is(':checked')) {stock = '1';} else {stock = '0'}

    // диапазон цен
	//var price_range = "?rprice=" + jQuery('input[name=price_from]').val() + "_" + jQuery('input[name=price_to]').val();

    var param = new Array();
	// остатки  крым киев польша
    if (jQuery('input[name=sstock_sklad]').is(':checked') && jQuery('input[name=sstock_sklad]:checked').val() > '0') {
		param.push('sstock_sklad='+jQuery('input[name=sstock_sklad]:checked').val());
	}
	if (jQuery('#runflat').is(':checked')) {param.push('runflat=1');}

	var request_uri = param.join('&');
	if (request_uri != '') {
		request_uri = '?'+request_uri;
	}

	var search_url = '/shop/shiny-'+shini_width+'-'+shini_height+'-R'+shini_diametr+'-'+shini_season+'-'+shini_ship+'-'+scatid+'-'+price+'-'+stock+'-'+order_by+'-'+id_table+'-0'+razmer2+'-search'+request_uri;
	window.location = search_url;
	//alert(scatid);
}/*
	Masked Input plugin for jQuery
	Copyright (c) 2007-2011 Josh Bush (digitalbush.com)
	Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license) 
	Version: 1.3
*/
(function($) {
	var pasteEventName = ($.browser.msie ? 'paste' : 'input') + ".mask";
	var iPhone = (window.orientation != undefined);

	$.mask = {
		//Predefined character definitions
		definitions: {
			'9': "[0-9]",
			'a': "[A-Za-z]",
			'*': "[A-Za-z0-9]"
		},
		dataName:"rawMaskFn"
	};

	$.fn.extend({
		//Helper Function for Caret positioning
		caret: function(begin, end) {
			if (this.length == 0) return;
			if (typeof begin == 'number') {
				end = (typeof end == 'number') ? end : begin;
				return this.each(function() {
					if (this.setSelectionRange) {
						this.setSelectionRange(begin, end);
					} else if (this.createTextRange) {
						var range = this.createTextRange();
						range.collapse(true);
						range.moveEnd('character', end);
						range.moveStart('character', begin);
						range.select();
					}
				});
			} else {
				if (this[0].setSelectionRange) {
					begin = this[0].selectionStart;
					end = this[0].selectionEnd;
				} else if (document.selection && document.selection.createRange) {
					var range = document.selection.createRange();
					begin = 0 - range.duplicate().moveStart('character', -100000);
					end = begin + range.text.length;
				}
				return { begin: begin, end: end };
			}
		},
		unmask: function() { return this.trigger("unmask"); },
		mask: function(mask, settings) {
			if (!mask && this.length > 0) {
				var input = $(this[0]);
				return input.data($.mask.dataName)();
			}
			settings = $.extend({
				placeholder: "_",
				completed: null
			}, settings);

			var defs = $.mask.definitions;
			var tests = [];
			var partialPosition = mask.length;
			var firstNonMaskPos = null;
			var len = mask.length;

			$.each(mask.split(""), function(i, c) {
				if (c == '?') {
					len--;
					partialPosition = i;
				} else if (defs[c]) {
					tests.push(new RegExp(defs[c]));
					if(firstNonMaskPos==null)
						firstNonMaskPos =  tests.length - 1;
				} else {
					tests.push(null);
				}
			});

			return this.trigger("unmask").each(function() {
				var input = $(this);
				var buffer = $.map(mask.split(""), function(c, i) { if (c != '?') return defs[c] ? settings.placeholder : c });
				var focusText = input.val();

				function seekNext(pos) {
					while (++pos <= len && !tests[pos]);
					return pos;
				};
				function seekPrev(pos) {
					while (--pos >= 0 && !tests[pos]);
					return pos;
				};

				function shiftL(begin,end) {
					if(begin<0)
					   return;
					for (var i = begin,j = seekNext(end); i < len; i++) {
						if (tests[i]) {
							if (j < len && tests[i].test(buffer[j])) {
								buffer[i] = buffer[j];
								buffer[j] = settings.placeholder;
							} else
								break;
							j = seekNext(j);
						}
					}
					writeBuffer();
					input.caret(Math.max(firstNonMaskPos, begin));
				};

				function shiftR(pos) {
					for (var i = pos, c = settings.placeholder; i < len; i++) {
						if (tests[i]) {
							var j = seekNext(i);
							var t = buffer[i];
							buffer[i] = c;
							if (j < len && tests[j].test(t))
								c = t;
							else
								break;
						}
					}
				};

				function keydownEvent(e) {
					var k=e.which;

					//backspace, delete, and escape get special treatment
					if(k == 8 || k == 46 || (iPhone && k == 127)){
						var pos = input.caret(),
							begin = pos.begin,
							end = pos.end;
						
						if(end-begin==0){
							begin=k!=46?seekPrev(begin):(end=seekNext(begin-1));
							end=k==46?seekNext(end):end;
						}
						clearBuffer(begin, end);
						shiftL(begin,end-1);

						return false;
					} else if (k == 27) {//escape
						input.val(focusText);
						input.caret(0, checkVal());
						return false;
					}
				};

				function keypressEvent(e) {
					var k = e.which,
						pos = input.caret();
					if (e.ctrlKey || e.altKey || e.metaKey || k<32) {//Ignore
						return true;
					} else if (k) {
						if(pos.end-pos.begin!=0){
							clearBuffer(pos.begin, pos.end);
							shiftL(pos.begin, pos.end-1);
						}

						var p = seekNext(pos.begin - 1);
						if (p < len) {
							var c = String.fromCharCode(k);
							if (tests[p].test(c)) {
								shiftR(p);
								buffer[p] = c;
								writeBuffer();
								var next = seekNext(p);
								input.caret(next);
								if (settings.completed && next >= len)
									settings.completed.call(input);
							}
						}
						return false;
					}
				};

				function clearBuffer(start, end) {
					for (var i = start; i < end && i < len; i++) {
						if (tests[i])
							buffer[i] = settings.placeholder;
					}
				};

				function writeBuffer() { return input.val(buffer.join('')).val(); };

				function checkVal(allow) {
					//try to place characters where they belong
					var test = input.val();
					var lastMatch = -1;
					for (var i = 0, pos = 0; i < len; i++) {
						if (tests[i]) {
							buffer[i] = settings.placeholder;
							while (pos++ < test.length) {
								var c = test.charAt(pos - 1);
								if (tests[i].test(c)) {
									buffer[i] = c;
									lastMatch = i;
									break;
								}
							}
							if (pos > test.length)
								break;
						} else if (buffer[i] == test.charAt(pos) && i!=partialPosition) {
							pos++;
							lastMatch = i;
						}
					}
					if (!allow && lastMatch + 1 < partialPosition) {
						input.val("");
						clearBuffer(0, len);
					} else if (allow || lastMatch + 1 >= partialPosition) {
						writeBuffer();
						if (!allow) input.val(input.val().substring(0, lastMatch + 1));
					}
					return (partialPosition ? i : firstNonMaskPos);
				};

				input.data($.mask.dataName,function(){
					return $.map(buffer, function(c, i) {
						return tests[i]&&c!=settings.placeholder ? c : null;
					}).join('');
				})

				if (!input.attr("readonly"))
					input
					.one("unmask", function() {
						input
							.unbind(".mask")
							.removeData($.mask.dataName);
					})
					.bind("focus.mask", function() {
						focusText = input.val();
						var pos = checkVal();
						writeBuffer();
						var moveCaret=function(){
							if (pos == mask.length)
								input.caret(0, pos);
							else
								input.caret(pos);
						};
						($.browser.msie ? moveCaret:function(){setTimeout(moveCaret,0)})();
					})
					.bind("blur.mask", function() {
						checkVal();
						if (input.val() != focusText)
							input.change();
					})
					.bind("keydown.mask", keydownEvent)
					.bind("keypress.mask", keypressEvent)
					.bind(pasteEventName, function() {
						setTimeout(function() { input.caret(checkVal(true)); }, 0);
					});

				checkVal(); //Perform initial check for existing values
			});
		}
	});
})(jQuery);
//
// rating Plugin
// By Chris Richards
// Last Update: 6/21/2011
// Turns a select box into a star rating control.
//
//
// Updated by Dovy Paukstys 21 Sept. 2011
// Fixed the click to clear out stars not working with newer jquery, but may not have ever worked.
// Also fixed it so on a new page load it will properly populate the stars as they should be.
//
//

//Keeps '$' pointing to the jQuery version
(function ($) {

	$.fn.rating = function(options)
	{
		//
		// Settings
		//
		var settings =
		{
			showCancel: false,
			cancelValue: null,
			cancelTitle: "Cancel",
			startValue: null,
			showTarget: false,
			disabled: false
		};

        //
        // Methods
        //
        var methods = {
           hoverOver: function(evt)
			{
				var elm = $(evt.target);

				//Are we over the Cancel or the star?
				if( elm.hasClass("ui-rating-cancel") )
				{
					elm.addClass("ui-rating-cancel-full");
				}
				else
				{
					elm.prevAll().andSelf()
						.not(".ui-rating-cancel")
						.addClass("ui-rating-hover");
				}
			},
			hoverOut: function(evt)
			{
				var elm = $(evt.target);
				//Are we over the Cancel or the star?
				if( elm.hasClass("ui-rating-cancel") )
				{
					elm.addClass("ui-rating-cancel-empty")
						.removeClass("ui-rating-cancel-full");
				}
				else
				{
					elm.prevAll().andSelf()
						.not(".ui-rating-cancel")
						.removeClass("ui-rating-hover");
				}
			},
			click: function(evt)
			{
				var elm = $(evt.target);
				var value = settings.cancelValue;
				//Are we over the Cancel or the star?
				elm.parents(".content-box-content:first").removeClass('formerror');
				if( elm.hasClass("ui-rating-cancel") ) {
                    methods.empty(elm, elm.parent());
				}
				else
				{
					//Set us, and the stars before us as full
					elm.closest(".ui-rating-star").prevAll().andSelf()
						.not(".ui-rating-cancel")
						.prop("className", "ui-rating-star ui-rating-full");
					//Set the stars after us as empty
					elm.closest(".ui-rating-star").nextAll()
						.not(".ui-rating-cancel")
						.prop("className", "ui-rating-star ui-rating-empty");
					//Uncheck the cancel
					elm.siblings(".ui-rating-cancel")
						.prop("className", "ui-rating-cancel ui-rating-cancel-empty");
					//Use our value
					value = elm.attr("value");
				}

				//Set the select box to the new value
				if( !evt.data.hasChanged )
				{
					$(evt.data.selectBox).val( value ).trigger("change");

					// set uiVlaue
					if (true == settings.showTarget) {
						uiValue = $(evt.data.selectBox).find("option:selected").text();
						targetId = "#" + evt.data.selectBox.attr("name");
						$(targetId).text(uiValue);
				    }
				}
			},
			change: function(evt)
			{
				var value =  $(this).val();
				methods.setValue(value, evt.data.container, evt.data.selectBox);
			},
			setValue: function(value, container, selectBox)
			{
				//Set a new target and let the method know the select has already changed.
				var evt = {"target": null, "data": {}};

				evt.target = $(".ui-rating-star[value="+ value +"]", container);
				evt.data.selectBox = selectBox;
				evt.data.hasChanged = true;
				methods.click(evt);
			},
			empty: function(elm, parent)
			{
				// Fix to remove all stars
				parent.find('.ui-rating-star').removeClass('ui-rating-full');
				parent.find('.ui-rating-star').addClass('ui-rating-empty');
				//Clear all of the stars
				elm.prop("className", "ui-rating-cancel ui-rating-cancel-empty")
					.nextAll().prop("className", "ui-rating-star ui-rating-empty");
			}

        };

        //
		// Process the matched elements
		//
		return this.each(function() {
            var self = $(this),
                elm,
                val;

            // we only want to process single select
            if ('select-one' !== this.type) { return; }
            // don't process the same control more than once
            if (self.prop('hasProcessed')) { return; }

            // if options exist, merge with our settings
            if (options) { $.extend( settings, options); }

            // hide the select box because we are going to replace it with our control
            self.hide();
            // mark the element so we don't process it more than once.
            self.prop('hasProcessed', true);

            //
            // create the new HTML element
            //
            // create a div and add it after the select box
            elm = $("<div/>").prop({
                title: this.title,  // if there was a title, preserve it.
                className: "ui-rating"
            }).insertAfter( self );

			// create the p to hold selected value
		   if (true == settings.showTarget) {
				uiValue = $("<p/>").prop({
		         	id: this.name,  // set id to the input name attribute
		         	className: "ui-selected-value"
		    	}).insertAfter( elm );
			}
            // create all of the stars
            $('option', self).each(function() {
                // only convert options with a value
				if(this.value!="")
				{
                    $("<a/>").prop({
                        className: "ui-rating-star ui-rating-empty",
                        title: $(this).text(),   // perserve the option text as a title.
                        value: this.value        // perserve the value.
                    }).appendTo(elm);
				}
            });
            // create the cancel
            if (true == settings.showCancel) {
                $("<a/>").prop({
					className: "ui-rating-cancel ui-rating-cancel-empty",
					title: settings.cancelTitle
				}).appendTo(elm);
            }
            // perserve the selected value
            //
            if ( 0 !==  $('option:selected', self).size() ) {
                //methods.setValue(
                methods.setValue( self.val(), elm, self );
            } else {
                //Use a start value if we have it, otherwise use the cancel value.
				val = null !== settings.startValue ? settings.startValue : settings.cancelValue;
				methods.setValue( val, elm, self );
				//Make sure the selectbox knows our desision
				self.val(val);
            }

            //Should we do any binding?
			if( true !== settings.disabled && self.prop("disabled") !== true )
			{
			    //Bind our events to the container
			    $(elm).bind("mouseover", methods.hoverOver)
				    .bind("mouseout", methods.hoverOut)
				    .bind("click",{"selectBox": self}, methods.click);
			}

            //Update the stars if the selectbox value changes.
			self.bind("change", {"selectBox": self, "container": elm},  methods.change);

        });

	};

})(jQuery);
jQuery(function($) {

///////////////////////// begin Ajax  :not([data-ajax="0"])
 	$(document).pjax('a:not([target], [rel^="lightbox"], [href^=#], .nopjax)', '#page', {
        fragment: '#page',
        timeout: 60000
    });

	$(document).on('submit', 'form:not([data-nopjax]', function(event) {
		$.pjax.submit(event, '#page', {
        	fragment: '#page',
        	timeout: 60000
        });
	});

 	$(document).on('pjax:send', function() {
		NProgress.start();
	})

	/// If back\forward - make work scripts
	$(document).on("pjax:popstate", function() {
	    $(document).one("pjax:end", function(event) {
    	    $.pjax.reload({container: '#page', timeout: false});
      	});
    });

	$(document).on('pjax:complete', function() {
	  	NProgress.done();

    	runPlayer(); //run youtube api
	  	pageReload(); // initialization after pjax
	  	topMenu();
	});

///////////////////////// End Ajax


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////    JQUERY code должен работать после pjax //////////////////////////////////////////////////////////////////////////
var pageReload = function(){

	/////// учитываем статистику
    setStatistics();


	$("a[href^=#]").on('click', function(){
		return false;
	});

	//// если открыто - закрываем
	$.arcticmodal("close");

	//// подсказки отдельно Динамика цены и др.
	$('.tooltip').tooltip({
		track: false,
		delay: 200,
		showURL: false,
		fade: 200,
		top: -32,
		left: 0
	});

	//// Cars select
    $('#vendorlist').msDropdown({
		visibleRows: 15
	});
	$('#vendorlist').change(function() {
        location.href = $(this).val();
        //$.pjax({url: $(this).val(), container: '#page'});
	});


	/// ПРИМЕРКА если страница примерки открылась - грузим авто
    $(document).ready(function(){
	    if ($('#configurator').length) {
            //// если в хеше есть id дисков - то выбираем их в конфигураторе
	     	var get_product_id = window.location.hash.replace('#','');
			if (get_product_id != '') {
				if ($('div[data-pid='+get_product_id+']').length) {
					$('#configurator').attr('data-r', $('div[data-pid='+get_product_id+']').attr('data-r')); //эта выше, иначе дубли селекторов
					$('#configurator').attr('data-pid', get_product_id);
			   	}
			}

	     	$('.color-cell.default').trigger('click');
	    }
    });

    /*
    //// инициализация yandex share
	var myShare = document.getElementById('yandexSocial');
	if (typeof (myShare) != 'undefined' && myShare != null) { // проверяем существует ли, чтобы ошибок не было
		var share = Ya.share2(myShare, {
			content: {
				url: document.URL
			}
		});
	}

    //// инициализация yandex share главная
    var myShareIndex = document.getElementById('yaShareIndex');
    if (typeof (myShareIndex) != 'undefined' && myShareIndex != null) { // проверяем существует ли, чтобы ошибок не было
		var share2 = Ya.share2(myShareIndex, {
			content: {
				url: window.location.protocol + '//'+window.location.hostname
			}
		});
	}
    */

	//////// проверка на верность определения региона
	$('.check_crimea_no').on('click', function() {
    	$.ajax({
			type: 'POST',
			url: '/modules/mod_cart_params/ajax_vote.php',
			data: {'check_crimea': '1'},
			cache: false,
			success: function(data){

			}
		});
	});
    $('#check_crimea').arcticmodal();

	//$("input[data-phone=ukraine]").mask("+38 (099) 999-99-99");//маска для корзины
	$("input[data-phone=russia]").mask("+7 (999) 999-99-99");//маска для корзины

    $('div.contentpaneopen img:not(.notresize),div.contentpane img:not(.notresize)').removeAttr('height').removeAttr('width');
    // удаляем атрибуты размера - для адаптивной верстки

	/// рейтинг в отзывах
	$('.rating').rating();


	//////// загружаем ajax Chart в новостях - динамика спроса, https://www.gstatic.com/charts/loader.js в index
    if ($('div').is('#get_chartAll')) {
		google.charts.load("visualization", "1", {packages:["corechart"]});
		google.charts.setOnLoadCallback(drawChart);
		function drawChart() {
		  	var data = $.ajax({
				url: '/templates/main/php/priceHistory.php?chartAll',
				dataType: 'json',
				async: false,
			}).responseText;

		    var dataView = google.visualization.arrayToDataTable(jQuery.parseJSON(data));


			var options = {
				vAxis: {minValue: 0, textPosition: 'none'},
			    lineWidth: 1,
			    chartArea:{left:0,top:20,width:"100%",height:"100%"},
			    legend: {position: 'top'},
			};

		    var chart = new google.visualization.AreaChart(document.getElementById('get_chartAll'));
			chart.draw(dataView, options);
		}
	}




    // Раскрыть список городов
	$('div#leftmenuexpand_cities ul#leftmenuexpand ul').each(function(index) {
	  	$(this).prev().click(function() {

			if ($(this).next().css('display') == 'none') {
				$(this).parent('li').parent('ul').find('ul').each(function(j) {
					$(this).slideUp(100, function () {
						$(this).prev().removeClass('expanded').removeClass('active').addClass('leftmenuexpand').addClass('collapsed');
					});
				});
		        $(this).next().slideDown(100, function () {
		          	$(this).prev().removeClass('collapsed').removeClass('leftmenuexpand').addClass('expanded').addClass('active');
		        });

			} else {
		        $(this).next().slideUp(100, function () {
		          	$(this).prev().removeClass('expanded').addClass('collapsed');
		          	$(this).find('ul').each(function() {
		            	$(this).hide().prev().removeClass('expanded').addClass('collapsed');
		          	});
		        });
			}
		    return false;
		});
  	});

    // Раскрыть список годов марки машины
  	$('div#leftmenuexpand_cars ul#leftmenuexpand ul').each(function(index) {
	    $(this).prev().click(function() { // нажимаем на первый уровень
			if ($(this).next().css('display') == 'none') { // если следующие скрытые
				$(this).parent('li').parent('ul').find('ul').each(function(j) {  // скрываем все в этой группе
					$(this).slideUp(100);
				});
		        $(this).next().slideDown(100); // открываем нажатый

			} else {  // если показанный, просто скрываем все, даже вложенные
		        $(this).next().slideUp(100, function () {
		          $(this).find('ul').each(function() {
		            $(this).hide();
		          });
		        });
	      	}
	      	return false;
		});
	});


	// Примерка во весь экран
    $("#fullscreen").toggle(
    	function(){
	    	$("#top, #head, .topmenu, #bottom").hide();
	    	$("#left").hide();

			$("#undermenubg").css({'height': '10px'});
			$("#wrapper").css({'float': 'none', 'margin-left': '0'});
			$("#inwrapper").css({'margin-left': '0'});
			$(this).text('Обычный режим');
		},

		function(){
	    	$("#top").show();
	    	$("#head").show();
	    	$(".topmenu").show();
	    	$("#bottom").show();
	    	$("#left").show();

	    	$("#undermenubg").css({'height': '50px'});
			$("#wrapper").css({'float': 'right', 'margin-left': '-220px'});
			$("#inwrapper").css({'margin-left': '220px'});
			$(this).text('Расширенный режим');
		}
	);

	$('table.shoplist td:not(.noclickedRow)').on('click',   // clickedRow
		function(){
			$(this).parents(this).toggleClass('clickedRow');
		}
	);

	$('.priceHistory').on('click', function() { // price history
		var code = $(this).attr('data-code');
		var country = $(this).attr('data-country');
		$.arcticmodal({
		    type: 'ajax',
		    url: '/templates/main/php/priceHistory.php?code='+code+'&country='+country,
		});
	});

	$('a[name=modalframe]').click(function() { // Modal window ifram
			var width = $(this).attr('data-w');
        	var height = $(this).attr('data-h');
		    var c = $('<div class="b-modal" />');
		    c.html('<div class="b-modal-close arcticmodal-close">X</div><iframe src="'+$(this).attr('href')+'" width="'+width+'" height="'+height+'" scrolling="auto" frameborder="0" /></iframe>');
		    $.arcticmodal({
		        content: c
			});
		});

    $('#callbackbtn').on('click', function() { // Callback
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=callback&notpl'
		});
	});

	$('#chatbtn').on('click', function() { // Chat
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=chat&notpl'
		});
	});

	$('#loginbtn').on('click', function() { // Login
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=login&notpl'
		});
	});

	$('#addcarimage').on('click', function() { // Add car image
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&catid='+$(this).attr('data-catid')+'&idclass=addcarimage&notpl'
		});
	});

	$('#setcarlink').on('click', function() { // Set car for search
		type = $(this).attr('data-type');
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=setcar&notpl&'+type,
		});
	});

	$('#add_montazh').on('click', function() { // Кнопка добавить шиномонтаж
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=add_montazh&notpl',
		});
	});

	$('#setcarreset').on('click', function() { // Set car RESET
		$.cookie('id_modification', null, { path: '/' });
		$.cookie('id_modification_cart1', null, { path: '/' });
		$.cookie('id_modification_cart2', null, { path: '/' });
		$.cookie('id_modification_cart3', null, { path: '/' });
	    location.reload();
	});

	$('.cart-setcarname').live('click', function() { // Set car for CART
		id = $(this).attr('data-id');
		$.arcticmodal({
			type: 'ajax',
			url: '/index2.php?option=com_content&task=view&idclass=setcar&notpl&diski&cartid='+id,
		});
	});


	/////////////////////////// Slider price
	var slide_price = $( "#slider-price" ).slider({
	    range: true,
	    slide: function( event, ui ) {
			$( "#price_from" ).val( ui.values[ 0 ]);
			$( "#price_to" ).val( ui.values[ 1 ]);
	    }
    });

	slide_price.slider( "option", "min", Math.round($("#price_min").text()*0.5) );  //делаем запас диапазона
	slide_price.slider( "option", "max", Math.round($("#price_max").text()*1.25) );

    slide_price.slider("values", 0, $("#price_from").val());
    slide_price.slider("values", 1, $("#price_to").val());

    $("#price_from").keyup(function() {
	    slide_price.slider("values", 0, $(this).val());
	});

	$("#price_to").keyup(function() {
	    slide_price.slider("values", 1, $(this).val());
	});




	/////// на главной после ajax чтобы работал выбор
	selectCar();
	calculator();











//////////////////////////////////////////////////////////////////////////////// - вкладки
	///// Диаметры в модели /////
	var get_product_id = window.location.hash.replace("#","");
	if (get_product_id != '') {
		set_product_id = $('tr[data-item='+get_product_id+']');

		if (set_product_id.length > 0) {  //активируем типоразмер если есть такой
			set_product_id.addClass('active');
			var numtabr = $('tr[data-item='+get_product_id+']').parents('div').attr('data-tab') - 1;
			var numr = $('div.tabsr').find('div.tab').size() - numtabr - 1;
	   		$('ul#linetabsr li').removeClass('active').eq(numr).addClass('active').parents('div.tabsr').find('div.tab').eq(numtabr).fadeIn(150).siblings('div.tab').hide();

	   		var active_linetabsr = set_product_id.attr('data-r');  // записываем radius в куки
			$.cookie('active_linetabsr', active_linetabsr, {expires: 30});
	   	}
	}

    $('ul#linetabsr').delegate('li:not(.active)', 'click', function() {
    	if ($('tr[data-item='+get_product_id+']').length === 0) {
        	$.cookie('active_linetabsr', $(this).text(), {expires: 30});
	    }
	    //табсы R13 - в обратном порядке
	    var num = $(this).parents('div.tabsr').find('div.tab').size() - $(this).index() - 1;   // находим верный порядок
       	$(this).addClass('active').siblings().removeClass('active').parents('div.tabsr').find('div.tab').eq(num).fadeIn(150).siblings('div.tab').hide();
	});

    ///// Обычные широкие табсы /////  Маленькие табсы - бренды шин и сезон шин
	$('ul#linetabs, ul#linetabssm').delegate('li:not(.active)', 'click', function() {
		var num = $(this).parents('div.tabs').find('div.tab').size() - $(this).index() - 1;
		$(this).addClass('active').siblings().removeClass('active').parents('div.tabs').find('div.tab').eq(num).fadeIn(150).siblings('div.tab').hide();
	});
	///// R13 в примерке /////
	$('ul#linetabsradius').delegate('li:not(.active)', 'click', function() {
		var num = $(this).index();
		$(this).addClass('active').siblings().removeClass('active').parents('div.tabsradius').find('div.tabradius').eq(num).fadeIn(150).siblings('div.tabradius').hide();
		$(this).parents('div.tabsradius').find('div.tabinforadius').eq(num).fadeIn(150).siblings('div.tabinforadius').hide();
	});


//////////////////////////////////////////////////////////////////////////////// - сортировка
	$('#orderbylink').on('click', function(){
		if ($('#orderbymenu').is(':visible')) {
        	$('#orderbymenu').hide();
        } else {
        	$('#orderbymenu').show();
        }
        return false;
    });

	$('body').on('click', function(){
    	$('#orderbymenu').hide();
    	$('#searchres').hide();
	});

//////////////////////////////////////////////////////////////////////////////// - подробнее
	$('#showhide').on('click', function () {
 		if ($('#showdesc').is(':hidden')) {
			$('#showdesc').fadeIn(200).show();
			$(this).text('Скрыть');
		} else {
			$('#showdesc').hide();
			$(this).text('Подробно');
		}
	});

	$('#showhidephoca').on('click', function () {
 		if ($('#showphoca').is(':hidden')) {
			$('#showphoca').fadeIn(200).show();
			$(this).text('Скрыть');
		} else {
			$('#showphoca').hide();
			$(this).text('Показать все');
		}
	});


//////////////////////////////////////////////////////////////////////////////// - подбор шин
    $('#checkall, #checkbox-catid').on('click', function() {
		if($('#checkall').attr('checked')){
			$('#searchleft-cats input[type=checkbox]').not('#checkall').attr('checked', false);
		}
	});

	$('#searchleft-cats input[type=checkbox]').not('#checkall').on('click',  function() {
		if($('#checkall').attr('checked')){
			$('#checkall').attr('checked', false);
		}
	});

	$('#sprice').on('click',  function() {
		if($(this).attr('checked')){
			$('#divsstock').show();
			$('#divsstock input').removeAttr('disabled');
		} else {
        	$('#divsstock').hide();
            $('#divsstock input').attr('disabled', 'disabled');
		}
	});

	$('#swinter').on('click',  function() {
		if($(this).attr('checked')){
			$('#divsship').show();
			$('#divsship input').removeAttr('disabled');
		} else {
        	$('#divsship').hide();
        	$('#divsship input').attr('disabled', 'disabled');
		}
	});

	$('.show_hide_podbor').on('click',  function() {
		if($('#show_hide_razmer2').css('visibility')=='visible'){
            $('#show_hide_razmer2').css({ position: 'absolute', visibility: 'hidden', display: 'block' });
        	$('#show_hide_razmer2 select').attr('disabled', 'disabled');
		} else {
        	$('#show_hide_razmer2').removeAttr('style');
			$('#show_hide_razmer2 select').removeAttr('disabled');
		}
		return false;
	});

	$('#dia_strogo').on('click', function() {
		if($('#dia_strogo').attr('checked')){
			$('#dia_strogo_ot_ravno').text('Dia =');
		} else {
        	$('#dia_strogo_ot_ravno').text('Dia от');
		}
	});

//////////////////////////////////////////////////////////////////////////////// - подбор дисков
	$('#checkall, #checkbox-catid').on('click', function() {
		if($('#checkall').attr('checked')){
			$('#searchleft-cats input[type=checkbox]').not('#checkall').attr('checked', false);
		}
	});

	$('#searchleft-cats input[type=checkbox]').not('#checkall').on('click',  function() {
		if($('#checkall').attr('checked')){
			$('#checkall').attr('checked', false);
		}
	});

	$('#dprice').on('click',  function() {
		if($(this).attr('checked')){
			$('#divdstock').show();
			$('#divdstock input').removeAttr('disabled');
		} else {
        	$('#divdstock').hide();
            $('#divdstock input').attr('disabled', 'disabled');
		}
	});




//////////////////////////////////////////////////////////////////////////////// - Ajax поиск
	$('form[name=searchkeyword] input').keydown(function(e) {
	    if (e.keyCode == 13) {
	        $('form[name=searchkeyword]').submit();
	    }
	});

	var suggest_count = 0;
	var input_initial_value = '';
	var suggest_selected = 0;
    var timer;

	$('#searchex').on('click', function () {
		var search = $(this).text();
		$('#keyword').val(search);

		var search = $('#keyword').val();
		timer && clearTimeout(timer);
  		ajaxSearch(search);
  		$('#keyword').focus(); // ставим фокус
		return false;
	});

    $('#keyword').bind({
        focus: function() {
            $(this).addClass('active');
        },
        blur: function() {
            $(this).removeClass('active');
        }
    });


	$(document).on('keyup', '#keyword', function (I) {
		var search = $('#keyword').val();
		timer && clearTimeout(timer);

        switch(I.keyCode) {
            // игнорируем нажатия на эти клавишы
            case 13:  // enter
            case 27:  // escape
            case 38:  // стрелка вверх
            case 40:  // стрелка вниз
            break;

            default:
            	ajaxSearch(search);
            break;
        }
		return false;
	});




	$("#keyword").keydown(function(I){
        switch(I.keyCode) {
            case 13: // enter
            case 27: // escape
                $('#searchres').hide();
                return false;
            break;

            case 38: // стрелка вверх
            case 40: // стрелка вниз
                I.preventDefault();
                if(suggest_count){
                    key_activate( I.keyCode-39 );
                }
            break;
        }
    });


    function key_activate(n){
	    $('#searchres a').eq(suggest_selected-1).removeClass('searchactive');

	    if (n == 1 && suggest_selected < suggest_count){
	        suggest_selected++;
	    } else if(n == -1 && suggest_selected > 0){
	        suggest_selected--;
	    }

	    if (suggest_selected > 0){
	        $('#searchres a').eq(suggest_selected-1).addClass('searchactive');
	        $("#keyword").val( $('#searchres a span[data=name]').eq(suggest_selected-1).text() );
	    } else {
	        $("#keyword").val( input_initial_value );
	    }
	}

	function ajaxSearch (search) {
		if(search.length >= 3){
			timer = setTimeout(function() {
				$('div#inputsearch').append('<div class="loading_ajax_search"></div>');
				input_initial_value = search;
				$.ajax({
					type: 'POST',
					url: '/modules/mod_virtuemart_search/search.php',
					data: {'search': search},
					cache: false,
					async: false, //to get ajax response in sync
					success: function(data){
						if (data.length > 0) {
							$('#searchres').show();
							$('#searchres').html(data);
							suggest_count = $('#searchres a').length;
						}
					}
				}).done(function() {
					$('div.loading_ajax_search').remove();
				});
			}, 1000 );
		}
		return false;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////// Image Preview
	$("span.preview").hover(
		function(e){
			var title = $(this).attr('data-title');
			var dwidth = $(this).attr('data-width');
			var dheight = $(this).attr('data-height');
			var c = (title != '') ? '<span>' + title + '</span>' : '';
			$('body').append('<div id="preview"><div><img src="'+$(this).attr('data-link')+'" width="'+dwidth+'" height="'+dheight+'" />'+ c +'</div></div>');
			$('#preview').css('top',(e.pageY - 100) + 'px').css('left',(e.pageX + 30) + 'px').fadeIn('fast');
	    },
		function(){
			$('#preview').remove();
	    }
	);

	$('span.preview').mousemove(function(e){
		$('#preview').css('top',(e.pageY - 100) + 'px').css('left',(e.pageX + 30) + 'px');
	});

	$('.recent-slider .carousel').jcarousel({scroll: 1});
	$('.tabradius').mCustomScrollbar({
		scrollButtons:{
			enable:true,
			scrollSpeed: 20
		},
		theme:"dark-thick",
		scrollInertia: 100
	});


	/////////////////// Примерка дисков
	/// Выбор цвета
	$(document).on('click','.color-cell', function (e) {
		$('.color-cell').removeClass('selected');
		$(this).addClass('selected');
      	var id = $('#configurator').attr('data-id');

      	var color = $(this).attr('data-color');
      	color = color.replace('#', '');

		if ($('#configurator').attr('data-pid')) {
	      	var pid = $('#configurator').attr('data-pid');
	      	var r = $('#configurator').attr('data-r');
      	} else {
        	var pid = $('div[data-pid]:first').attr('data-pid');
	      	var r = $('div[data-pid]:first').attr('data-r');
      	}
      	if (typeof (r) == 'undefined') {r = $('#configurator').attr('data-defr');}
      	configurator(id,color,pid,r);
	});

	/// Выбор дисков
	$(document).on('click','.select_wheel', function (e) {
      	var id = $('#configurator').attr('data-id');
      	var color = $('.color-cell.selected').attr('data-color');
      	color = color.replace('#', '');

      	var pid = $(this).closest('div').attr('data-pid');
      	var r = $(this).closest('div').attr('data-r');
      	configurator(id,color,pid,r);

      	$('#configurator').attr('data-pid', pid); //записываем, чтобы потом вытягивать при выборе цвета
      	$('#configurator').attr('data-r', r);
	});

	////// функция обновления авто в примерке
	function configurator(id,color,pid,r) {
		if (typeof (pid) == 'undefined') {pid = '0';}
		pid = pid.replace('p', '');

    	$('#configurator').prepend('<div class="loading_wheelvision"></div>');
  		var img = '/scripts/car_'+id+'_'+color+'_'+pid+'_'+r+'.jpg';
        /*
        /// обновляем картинку поделитсья
  		if(typeof share != 'undefined') {
	  		share.updateContent({
			    image: window.location.protocol + '//'+window.location.hostname + img
			});
		}
        */
		$('<img src="'+ img +'" id="'+ color + '"/>').load(function() {
        	$('#configurator').append($(this).fadeIn(150, function() {
        		$('#configurator img:not(#'+color+')').remove();
        	}));
        	$('div.loading_wheelvision').remove();
		});
	}


	//////////////////////////////////////////////////////////////////////////////// - ФИЧА ПРИ копировании текста
	var source_link = "\n" + 'Подробнее: ' + location.href + "\n";
	if (window.getSelection) $('#vmMainPage, .contentpaneopen').bind('copy',function() {
	        var selection = window.getSelection();
	        var range = selection.getRangeAt(0);

	        var magic_div = $('<div>').css({ overflow : 'hidden', width: '1px', height : '1px', position : 'absolute', top: '-10000px', left : '-10000px' });
	        magic_div.append(range.cloneContents(), source_link);
	        $('body').append(magic_div);

	        var cloned_range = range.cloneRange();
	        selection.removeAllRanges();

	        var new_range = document.createRange();
	        new_range.selectNode(magic_div.get(0));
	        selection.addRange(new_range);

	        window.setTimeout(
	            function()
	            {
	                selection.removeAllRanges();
	                selection.addRange(cloned_range);
	                magic_div.remove();
	            }, 0
	        );
	    }
	);


	/////////////////////////////// Слайдеры
	$('.slider_text').slides({
		container: 'slides_container_text',
		play: 8000,
		pause: 2500,
		fadeSpeed: 100,
		slideSpeed: 350,
		start: 1,
		effect: 'fade',
		hoverPause: true,
		autoHeight: true,
		slidepaginationClass: 'slidepaginationtext'
	});


	$('.slider_image').slides({
		container: 'slides_container',
		play: 6500,
		pause: 2500,
		fadeSpeed: 150,
		slideSpeed: 350,
		start: 1,
		effect: 'fade',
		hoverPause: true,
		slidepaginationClass: 'slidepaginationimage'
	});



    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////// Yandex Map Skladi - загружаем карту складов в разделе Доставка
	var SkladiYMapsID = document.getElementById('SkladiYMapsID');
	if (typeof (SkladiYMapsID) != 'undefined' && SkladiYMapsID != null) {
		//console.log('Isset');
		/* Yandex Map
		$.getScript("//api-maps.yandex.ru/2.1/?lang=ru_RU&coordorder=longlat").done(function(script, textStatus) {
			//console.log(textStatus);
			var oblast_id = $('#SkladiYMapsID').attr('data-region');
			var city = $('#SkladiYMapsID').attr('data-city');
			var pochta_id = $('#SkladiYMapsID').attr('data-pochta');

			ymaps.ready().done(function (ym) {
				var myMap = new ym.Map('SkladiYMapsID', {
					center: [32.409515, 48.449137],
					zoom: 7,
					controls: ['typeSelector']
				}, {
					searchControlProvider: 'yandex#search'
				});

				$.ajax({
					url: window.location.protocol + '//' +window.location.hostname + '/templates/main/php/get_skladi_map.php?region='+oblast_id+'&city='+city+'&pochta='+pochta_id,
					dataType: 'json'
				}).then(function(res) {
					var objects = ymaps.geoQuery(res).addToMap(myMap).applyBoundsToMap(myMap, {
						checkZoomRange: true
					});
				});
			});
		});
		*/

		//Google Map
		$.getScript("//maps.googleapis.com/maps/api/js?key=AIzaSyDmsLdip4vkkQOtZW_lCasgSRB0cJoiNrQ").done(function(script, textStatus) {  //info@web
	      	var oblast_id = $('#SkladiYMapsID').attr('data-region');
			var city = $('#SkladiYMapsID').attr('data-city');
			var pochta_id = $('#SkladiYMapsID').attr('data-pochta');

			function loadMap() {
	        	var mapOptions = {
	            	center:new google.maps.LatLng(48.44, 32.40),
	            	zoom: 11
	          	}
	          	var map = new google.maps.Map(document.getElementById("SkladiYMapsID"), mapOptions);
	            var infowindow = new google.maps.InfoWindow();
				var bounds = new google.maps.LatLngBounds();  //create empty LatLngBounds object

				// Load JSON Data
				$.getJSON( window.location.protocol + '//' +window.location.hostname + '/templates/main/php/get_skladi_map.php?region='+oblast_id+'&city='+city+'&pochta='+pochta_id, function(addressMarkers) {
		          	// Initialize Google Markers
					for(var i=0; i < addressMarkers.length; i++){
			         	var marker = new google.maps.Marker({
			              	map: map,
				           	position: new google.maps.LatLng(addressMarkers[i].location[0], addressMarkers[i].location[1]),
				            title: addressMarkers[i].title,
				            animation: google.maps.Animation.DROP,
			            });
			            var infowindow = new google.maps.InfoWindow();

						//extend the bounds to include each marker's position
	  					bounds.extend(marker.position);

				        google.maps.event.addListener(marker, 'click', (function(marker, i) {
				            return function() {
				              	infowindow.setContent(addressMarkers[i].name);
				              	infowindow.open(map, marker);
				            }
				        })(marker, i));
					}

					//now fit the map to the newly inclusive bounds
					map.fitBounds(bounds);

					//(optional) restore the zoom level after the map is done scaling
					var listener = google.maps.event.addListener(map, "idle", function () {
					    if (map.getZoom() > 15) {
						    map.setZoom(15);
						    google.maps.event.removeListener(listener);
					    }
					});
				});
			}

			loadMap();

		});
	}
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

pageReload(); /// в конце выполняем функцию


});



function drawChart() {
  	var data = jQuery.ajax({
		url: '/templates/main/php/priceHistory.php?chartAll',
		dataType: 'json',
		async: false,
	}).responseText;

	var dataView = google.visualization.arrayToDataTable(data);

	var options = {
		vAxis: {minValue: 0, textPosition: 'none'},
	    lineWidth: 1,
	    chartArea:{left:0,top:20,width:"100%",height:"80%"},
	    legend: {position: 'top'},
	};

    var chart = new google.visualization.AreaChart(document.getElementById('get_chartAll'));
	chart.draw(dataView, options);
}



//////////////////////////////////////////////////////////////////////////////// - Авторизация на сайте    //window.location.hostname - crodss domain ajax
function loginphone(){
	var phone = jQuery('#login_phone').val();
    var dataString = 'phone='+phone;

	jQuery.ajax({
		type: "POST",
		url: window.location.protocol + '//' +window.location.hostname + "/templates/main/php/login.php",
		data: dataString,
		cache: false,
		success: function(e) {
			if (e != '') {
				jQuery("#loginblock").html(e);
			}
		}
	});
}

function loginsms(){
	var phone = jQuery('#login_phone').val();
	var smscode = jQuery('#login_smscode').val();
    var dataString = 'phone='+phone+'&smscode='+smscode;

	jQuery.ajax({
		type: "POST",
		url: window.location.protocol + '//' +window.location.hostname + "/templates/main/php/login.php",
		data: dataString,
		cache: false,
		success: function(e) {
			if (e == 'logged') {
				jQuery("#loginblock").html('Вы авторизованы <br />Ожидайте перенаправления');
				setTimeout(function() {
				  window.location.href = "/user";
				}, 2000);

			} else {
				jQuery("#loginblock").html(e);
			}
		}
	});
}



function sendCallback(){
	pname = jQuery('input[name=nomer]').val();
	pmess = jQuery('textarea[name=mess]').val();

	var callback = function(responseText){
 		jQuery('#sendform').html('<br /><br /><br /><h2>Спасибо, мы Вам перезвоним в ближайшее время!</h2><br /><br /><br />');
     }
     var opt = {
     	method: 'post',
     	onComplete: callback,
     	data: {
     		nomer: pname,
     		mess: pmess
     	}
	}

	if (pname != '') {
		new Ajax(  window.location.protocol + '//' +window.location.hostname + '/templates/main/php/callback.php', opt ).request();
	}


}










////////////////////////////////////////////////////////////////////////////////
// Load the IFrame Player API code asynchronously. - AUTOPLAY YOUTUBE
jQuery(function($) {
	if(document.getElementById('iframe_api') === null){
		var tag = document.createElement('script');
		tag.src = 'https://www.youtube.com/iframe_api';
		tag.id = "iframe_api";
		var firstScriptTag = document.getElementsByTagName('script')[0];
		firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
	}
});

onYouTubeIframeAPIReady = function onYouTubeIframeAPIReady() {
	runPlayer();
}

////////////////////when api ready = run youtube api
function runPlayer(){
	if (jQuery.cookie('set_mobile') == 1) {
		var set_quality = 'default';
	} else {
     	var set_quality = 'hd720';
	}
	jQuery('.youtube-video').inViewAutoplay({
		autohide: 1,
		modestbranding: 0, //logo youtube
		rel: 0,
		loop: 1,
		listType: 'playlist',
		list: 'PL3A2LQMieedKUaH0pPn1502nGptv5ylFz',
		quality: set_quality
	});

	//jQuery('iframe[src*="youtube"], object').wrap('<div class="videowrapper"></div>');  отключил, т.к. обычные видео на всю страницу получаются!
}



/////// учитываем статистику Liveinternet Google Yandex
function setStatistics() {
	/////////// Liveinternet statistics ajax
	new Image().src = "//counter.yadro.ru/hit?r"+
	escape(document.referrer)+((typeof(screen)=="undefined")?"":
	";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?
	screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+
	";"+Math.random();

	window.onload = function() {
		/// yandex Metrika Ajax
		//yaCounter21622003.hit(document.location.href);
		ym(21622003, 'hit', document.location.href);
		/// Google Analytics Ajax
 		ga('send', 'pageview', {page: document.location.pathname, title: document.title});
	}
}


////// выбор авто
function selectCar() {
	var vendor = jQuery('#vendor');
	var model = jQuery('#model');
	var year = jQuery('#year');
	var modification = jQuery('#modification');

	vendor.selectChain({
	    target: model,
	    url: '/modules/mod_select_by_auto/ajax.php',
	    type: 'post',
	    data: { ajax: true }
	});

	model.selectChain({
	    target: year,
	    url: '/modules/mod_select_by_auto/ajax.php',
	    type: 'post',
	    data: { ajax: true  }
	});

	year.selectChain({
	    target: modification,
	    url: '/modules/mod_select_by_auto/ajax.php',
	    type: 'post',
	    data: { ajax: true  }
	});
}


/// Шинный калькулятор
function calculator() {

	var calc_find = document.getElementById('oldWidth');
	if (typeof (calc_find) != 'undefined' && calc_find != null) {

		/* ======================================== */
		/* legend */
		initLegend = function () {
			if (
				document.getElementById
				&& document.getElementById('shemeLegend')
			) {
				oSheme = {
					'nLegend' : document.getElementById('shemeLegend'),
					'aLabels' : new Array()
				}
				initLegendLabel('L');
				initLegendLabel('H');
				initLegendLabel('D');
				initLegendLabel('DD');
			}
		}

		initLegendLabel = function(sLabel) {
			if (
				oSheme
				&& document.getElementById
				&& document.getElementById('labelSheme' + sLabel)
			) {
				document.getElementById('labelSheme' + sLabel).onmouseover = function() {legendMouseOver(sLabel);};
				document.getElementById('labelSheme' + sLabel).onmouseout = function() {legendMouseOut();};
				oSheme.aLabels.push(sLabel);
				if (document.getElementById('sheme' + sLabel)) {
					doPreload(document.getElementById('sheme' + sLabel).src);
				}
			}
		}

		legendMouseOver = function(sLabel) {
			legendMouseOut();
			addClass(oSheme.nLegend, 'label' + sLabel);
		}


		legendMouseOut = function() {
			for (i in oSheme.aLabels) {
				removeClass(oSheme.nLegend, 'label' + oSheme.aLabels[i]);
			}
		}


		/* ======================================== */
		/* calculator */
		initCalculator = function() {
			oCalculator = {
				'oldWidth' : document.getElementById('oldWidth'),
				'oldDiameter' : document.getElementById('oldDiameter'),
				'oldProfile' : document.getElementById('oldProfile'),
				'newWidth' : document.getElementById('newWidth'),
				'newDiameter' : document.getElementById('newDiameter'),
				'newProfile' : document.getElementById('newProfile'),

				'oldL' : document.getElementById('oldL'),
				'newL' : document.getElementById('newL'),
				'deltaL' : document.getElementById('deltaL'),

				'oldH' : document.getElementById('oldH'),
				'newH' : document.getElementById('newH'),
				'deltaH' : document.getElementById('deltaH'),

				'oldD' : document.getElementById('oldD'),
				'newD' : document.getElementById('newD'),
				'deltaD' : document.getElementById('deltaD'),

				'oldDD' : document.getElementById('oldDD'),
				'newDD' : document.getElementById('newDD'),
				'deltaDD' : document.getElementById('deltaDD')
			}

			oSpeedometer = new Array();
			var i=10;
			while (document.getElementById('speed' + i)) {
				oSpeedometer[i] = document.getElementById('speed' + i);
				i += 10;
			}


			applyCalculateEvent(oCalculator.oldWidth);
			applyCalculateEvent(oCalculator.oldDiameter);
			applyCalculateEvent(oCalculator.oldProfile);
			applyCalculateEvent(oCalculator.newWidth);
			applyCalculateEvent(oCalculator.newDiameter);
			applyCalculateEvent(oCalculator.newProfile);

			doCalculate();
		}

		applyCalculateEvent = function (nNode) {
			nNode.onchange = function () {doCalculate();};
		}



		doCalculate = function() {
			var iOldL = oCalculator.oldWidth.value;
			var iNewL = oCalculator.newWidth.value;

			var iOldD = Math.round(oCalculator.oldDiameter.value * 25.4);
			var iNewD = Math.round(oCalculator.newDiameter.value * 25.4);

			var iOldDD = Math.round(oCalculator.oldWidth.value*oCalculator.oldProfile.value*0.02 + oCalculator.oldDiameter.value*25.4);
			var iNewDD = Math.round(oCalculator.newWidth.value*oCalculator.newProfile.value*0.02 + oCalculator.newDiameter.value*25.4);

			var iOldH = Math.round((iOldDD - iOldD)/2);
			var iNewH = Math.round((iNewDD - iNewD)/2);
			var iSpeedCoeff = iNewDD/iOldDD;

			setTextValue(oCalculator.oldL, iOldL);
			setTextValue(oCalculator.newL, iNewL);
			setTextValue(oCalculator.deltaL, iNewL - iOldL);

			setTextValue(oCalculator.oldH, iOldH);
			setTextValue(oCalculator.newH, iNewH);
			setTextValue(oCalculator.deltaH, iNewH - iOldH);

			setTextValue(oCalculator.oldD, iOldD);
			setTextValue(oCalculator.newD, iNewD);
			setTextValue(oCalculator.deltaD, iNewD - iOldD);

			setTextValue(oCalculator.oldDD, iOldDD);
			setTextValue(oCalculator.newDD, iNewDD);
			setTextValue(oCalculator.deltaDD, iNewDD - iOldDD);

			for (i in oSpeedometer) {
				setTextValue(oSpeedometer[i], Math.round(i*iSpeedCoeff * 10)/10);
			}

			/*==== additional info ====*/

			if (iOldL < iNewL){
				jQuery("#d2").hide();
				jQuery("#d1").show();
			}
			if (iOldL > iNewL){
				jQuery("#d1").hide();
				jQuery("#d2").show();
			}

			if (iOldH < iNewH){
				jQuery("#c2").hide();
				jQuery("#c1").show();
			}
			if (iOldH > iNewH){
				jQuery("#c1").hide();
				jQuery("#c2").show();
			}

			if (iOldDD < iNewDD){
				jQuery("#a2").hide();
				jQuery("#a1").show();
			}
			if (iOldDD > iNewDD){
				jQuery("#a1").hide();
				jQuery("#a2").show();
			}
		}




		/* ======================================== */
		/* disc */
		initDiscCalculator = function () {
			if (
				document.getElementById
				&& document.getElementById('tireWidth')
				&& document.getElementById('tireDiameter')
				&& document.getElementById('tireProfile')
				&& document.getElementById('discDiameter')
				&& document.getElementById('discWidthMin')
				&& document.getElementById('discWidthMax')
			) {
				oDiscCalculator = {
					'tireWidth' : document.getElementById('tireWidth'),
					'tireDiameter' : document.getElementById('tireDiameter'),
					'tireProfile' : document.getElementById('tireProfile'),
					'discDiameter' : document.getElementById('discDiameter'),
					'discWidthMin' : document.getElementById('discWidthMin'),
					'discWidthMax' : document.getElementById('discWidthMax')
				}
				applyCalculateDiscEvent(oDiscCalculator.tireWidth);
				applyCalculateDiscEvent(oDiscCalculator.tireDiameter);
				applyCalculateDiscEvent(oDiscCalculator.tireProfile);

				doCalculateDisc();
			}
		}


		applyCalculateDiscEvent = function (nNode) {
			nNode.onchange = function () {doCalculateDisc();};
		}

		doCalculateDisc = function() {
			if (oDiscCalculator) {
				var iWidth = oDiscCalculator.tireWidth.value;
				var iProfile = oDiscCalculator.tireProfile.value;
				var iDiameter = oDiscCalculator.tireDiameter.value;

				iWidthMin = (Math.round(((iWidth*((iProfile < 50) ? 0.85 : 0.7))*0.03937)*2))/2;
				iWidthMax = (iWidthMin+1.5);

				setTextValue(oDiscCalculator.discDiameter, iDiameter);
				setTextValue(oDiscCalculator.discWidthMin, iWidthMin);
				setTextValue(oDiscCalculator.discWidthMax, iWidthMax);
			}
		}





		/* ======================================== */
		/* useful */
		setTextValue = function (nNode, sValue) {
			sValue = String(sValue);
			sValue = sValue.replace(/\./, ',');
			nNode.innerHTML = sValue;
		}

		isClass = function(nNode, sClassName) {
			return (nNode.className.indexOf(sClassName) >= 0);
		}

		addClass = function(nNode, sClassName) {
			if (nNode.className) {
				var aClass = nNode.className.split(' ');
				for (var i in aClass) {
					if (sClassName == aClass[i]) {
						sClassName = '';
					}
				}
				if (sClassName) {
					aClass.push(sClassName);
				}
				nNode.className = aClass.join(' ');
			}
			else {
				nNode.className = sClassName;
			}
		}

		removeClass = function(nNode, sClassName) {
			if (nNode.className) {
				var aClass = nNode.className.split(' ');
				for (var i in aClass) {
					if (sClassName == aClass[i]) {
						aClass.splice(i,1);
						break;
					}
				}
				nNode.className = aClass.join(' ');
			}
		}


		doPreload = function(sImg) {
			var oPreload = new Image();
			oPreload.src = sImg;
		}


		/* ======================================== */
		/* init */

		initLegend();
		initCalculator();
		initDiscCalculator();


	}

}/*

arcticModal — jQuery plugin
Version: 0.1
Author: Sergey Predvoditelev (sergey.predvoditelev@gmail.com)
Company: Arctic Laboratory (http://arcticlab.ru/)

Docs & Examples: http://arcticlab.ru/arcticmodal/

*/
(function($) {


var default_options = {

type: 'html', // ajax или html
content: '',
url: '',
ajax: {},
ajax_request: null,

closeOnEsc: true,
closeOnOverlayClick: true,

clone: false,

overlay: {
block: undefined,
tpl: '<div class="arcticmodal-overlay"></div>',
css: {
backgroundColor: '#000',
opacity: .3
}
},

container: {
block: undefined,
tpl: '<div class="arcticmodal-container"><table class="arcticmodal-container_i"><tr><td class="arcticmodal-container_i2"></td></tr></table></div>'
},

wrap: undefined,
body: undefined,

errors: {
tpl: '<div class="arcticmodal-error arcticmodal-close"></div>',
autoclose_delay: 2000,
ajax_unsuccessful_load: 'Error'
},

openEffect: {
type: 'none',
speed: 400
},
closeEffect: {
type: 'none',
speed: 400
},

beforeOpen: $.noop,
afterOpen: $.noop,
beforeClose: $.noop,
afterClose: $.noop,
afterLoading: $.noop,
afterLoadingOnShow: $.noop,
errorLoading: $.noop

};


var modalID = 0;
var modals = $();


var utils = {


// Определяет произошло ли событие вне блока block
isEventOut: function(blocks, e) {
var r = true;
$(blocks).each(function() {
if ($(e.target).get(0)==$(this).get(0)) r = false;
if ($(e.target).closest('HTML', $(this).get(0)).length==0) r = false;
});
return r;
}


};


var modal = {


// Переход
transition: function(el, action, options, callback) {
callback = callback==undefined ? $.noop : callback;
switch (options.type) {
case 'fade':
action=='show' ? el.fadeIn(options.speed, callback) : el.fadeOut(options.speed, callback);
break;
case 'none':
action=='show' ? el.show() : el.hide();
callback();
break;
}
},


// Подготвка содержимого окна
prepare_body: function(D, $this) {

// Обработчик закрытия
$('.arcticmodal-close', D.body).click(function() {
$this.arcticmodal('close');
return false;
});

},


// Инициализация элемента
init_el: function($this, options) {
var D = $this.data('arcticmodal');
if (D) return;

D = options;
modalID++;
D.modalID = modalID;

// Overlay
D.overlay.block = $(D.overlay.tpl);
D.overlay.block.css(D.overlay.css);

// Container
D.container.block = $(D.container.tpl);

// BODY
D.body = $('.arcticmodal-container_i2', D.container.block);
if (options.clone) {
D.body.html($this.clone(true));
} else {
$this.before('<div id="arcticmodalReserve' + D.modalID + '" style="display: none" />');
D.body.html($this);
}

// Подготовка содержимого
modal.prepare_body(D, $this);

// Закрытие при клике на overlay
if (D.closeOnOverlayClick)
D.overlay.block.add(D.container.block).click(function(e) {
if (utils.isEventOut($('>*', D.body), e))
$this.arcticmodal('close');
});

// Запомним настройки
$this.data('arcticmodal', D);
modals = modals.add($this);

// Показать
$.proxy(actions.show, $this)();
if (D.type=='html') return $this;

// Ajax-загрузка
if (D.ajax.beforeSend!=undefined) {
	var fn_beforeSend = D.ajax.beforeSend;
	delete D.ajax.beforeSend;
}
if (D.ajax.success!=undefined) {
	var fn_success = D.ajax.success;
	delete D.ajax.success;
}
if (D.ajax.error!=undefined) {
	var fn_error = D.ajax.error;
	delete D.ajax.error;
}
var o = $.extend(true, {
	url: D.url,
	crossDomain: true,
	beforeSend: function() {
		if (fn_beforeSend==undefined) {
		D.body.html('<div class="arcticmodal-loading" />');
	} else {
		fn_beforeSend(D, $this);
	}
},
success: function(responce) {

// Событие после загрузки до показа содержимого
$this.trigger('afterLoading');
D.afterLoading(D, $this, responce);

if (fn_success==undefined) {
	D.body.html(responce);
} else {
	fn_success(D, $this, responce);
}
modal.prepare_body(D, $this);

// Событие после загрузки после отображения содержимого
$this.trigger('afterLoadingOnShow');
D.afterLoadingOnShow(D, $this, responce);

},
error: function() {

// Событие при ошибке загрузки
$this.trigger('errorLoading');
D.errorLoading(D, $this);

if (fn_error==undefined) {
	D.body.html(D.errors.tpl);
	$('.arcticmodal-error', D.body).html(D.errors.ajax_unsuccessful_load);
	$('.arcticmodal-close', D.body).click(function() {
		$this.arcticmodal('close');
		return false;
	});

	if (D.errors.autoclose_delay)
		setTimeout(function() {
			$this.arcticmodal('close');
		}, D.errors.autoclose_delay);
	} else {
		fn_error(D, $this);
	}
}
}, D.ajax);
D.ajax_request = $.ajax(o);

// Запомнить настройки
$this.data('arcticmodal', D);

},


// Инициализация
init: function(options) {
options = $.extend(true, {}, default_options, options);
if ($.isFunction(this)) {
if (options==undefined) {
$.error('jquery.arcticmodal: Uncorrect parameters');
return;
}
if (options.type=='') {
$.error('jquery.arcticmodal: Don\'t set parameter "type"');
return;
}
switch (options.type) {
case 'html':
if (options.content=='') {
$.error('jquery.arcticmodal: Don\'t set parameter "content"');
return
}
var c = options.content;
options.content = '';

return modal.init_el($(c), options);
break;
case 'ajax':
if (options.url=='') {
$.error('jquery.arcticmodal: Don\'t set parameter "url"');
return;
}
return modal.init_el($('<div />'), options);
break;
}
} else {
return this.each(function() {
modal.init_el($(this), options);
});
}
}


};


var actions = {


// Показать
show: function() {
var $this = $(this);
var D = $this.data('arcticmodal');
if (!D) {
$.error('jquery.arcticmodal: Uncorrect call');
return;
}

// Добавить overlay и container
D.overlay.block.hide();
D.container.block.hide();
$('BODY').append(D.overlay.block);
$('BODY').append(D.container.block);

// Событие
D.beforeOpen(D, $this);
$this.trigger('beforeOpen');

// Wrap
/* BY RUSYA
if (D.wrap.css('overflow')!='hidden') {
D.wrap.data('arcticmodalOverflow', D.wrap.css('overflow'));
var w1 = D.wrap.outerWidth(true);
D.wrap.css('overflow', 'hidden');
var w2 = D.wrap.outerWidth(true);
if (w2!=w1)
D.wrap.css('marginRight', (w2 - w1) + 'px');
}
*/

// Скрыть предыдущие оверлеи
modals.not($this).each(function() {
var d = $(this).data('arcticmodal');
d.overlay.block.hide();
});

// Показать
modal.transition(D.overlay.block, 'show', modals.length>1 ? {type: 'none'} : D.openEffect);
modal.transition(D.container.block, 'show', modals.length>1 ? {type: 'none'} : D.openEffect, function() {
D.afterOpen(D, $this);
$this.trigger('afterOpen');
});

return $this;
},


// Закрыть
close: function() {
if ($.isFunction(this)) {
modals.each(function() {
$(this).arcticmodal('close');
});
} else {
return this.each(function() {
var $this = $(this);
var D = $this.data('arcticmodal');
if (!D) {
$.error('jquery.arcticmodal: Uncorrect call');
return;
}

// Событие перед закрытием
if (D.beforeClose(D, $this)===false) return;
$this.trigger('beforeClose');

// Показать предыдущие оверлеи
modals.not($this).last().each(function() {
var d = $(this).data('arcticmodal');
d.overlay.block.show();
});

modal.transition(D.overlay.block, 'hide', modals.length>1 ? {type: 'none'} : D.closeEffect);
modal.transition(D.container.block, 'hide', modals.length>1 ? {type: 'none'} : D.closeEffect, function() {

// Событие после закрытия
D.afterClose(D, $this);
$this.trigger('afterClose');

// Если не клонировали - вернём на место
if (!D.clone)
$('#arcticmodalReserve' + D.modalID).replaceWith(D.body.find('>*'));

D.overlay.block.remove();
D.container.block.remove();
$this.data('arcticmodal', null);
if (!$('.arcticmodal-container').length) {
if (D.wrap.data('arcticmodalOverflow'))
D.wrap.css('overflow', D.wrap.data('arcticmodalOverflow'));
D.wrap.css('marginRight', 0);
}

});

if (D.type=='ajax')
D.ajax_request.abort();

modals = modals.not($this);
});
}
},


// Установить опции по-умолчанию
setDefault: function(options) {
$.extend(true, default_options, options);
}


};


$(function() {
default_options.wrap = $((document.all && !document.querySelector) ? 'html' : 'body');
});


// Закрытие при нажатии Escape
$(document).bind('keyup.arcticmodal', function(e) {
var m = modals.last();
if (!m.length) return;
var D = m.data('arcticmodal');
if (D.closeOnEsc && (e.keyCode===27))
m.arcticmodal('close');
});


$.arcticmodal = $.fn.arcticmodal = function(method) {

if (actions[method]) {
return actions[method].apply(this, Array.prototype.slice.call(arguments, 1));
} else if (typeof method==='object' || !method) {
return modal.init.apply(this, arguments);
} else {
$.error('jquery.arcticmodal: Method ' + method + ' does not exist');
}

};


})(jQuery);// MSDropDown - jquery.dd.js
// author: Marghoob Suleman - http://www.marghoobsuleman.com/
// Date: 10 Nov, 2012
// Version: 3.3
// Revision: 22
// web: www.marghoobsuleman.com
/*
// msDropDown is free jQuery Plugin: you can redistribute it and/or modify
// it under the terms of the either the MIT License or the Gnu General Public License (GPL) Version 2
*/
var msBeautify = msBeautify || {};
(function ($) {
msBeautify = {
	version: {msDropdown:'3.3'},
	author: "Marghoob Suleman",
	counter: 20,
	debug: function (v) {
		if (v !== false) {
			$(".ddOutOfVision").css({height: 'auto', position: 'relative'});
		} else {
			$(".ddOutOfVision").css({height: '0px', position: 'absolute'});
		}
	},
	oldDiv: '',
	create: function (id, settings, type) {
		type = type || "dropdown";
		var data;
		switch (type.toLowerCase()) {
		case "dropdown":
		case "select":
			data = $(id).msDropdown(settings).data("dd");
			break;
		}
		return data;
	}
};

$.msDropDown = {};
$.msDropdown = {};
$.extend(true, $.msDropDown, msBeautify);
$.extend(true, $.msDropdown, msBeautify);
// make compatibiliy with old and new jquery
if ($.fn.prop === undefined) {$.fn.prop = $.fn.attr;}
if ($.fn.on === undefined) {$.fn.on = $.fn.bind;$.fn.off = $.fn.unbind;}
if (typeof $.expr.createPseudo === 'function') {
	//jQuery 1.8  or greater
	$.expr[':'].Contains = $.expr.createPseudo(function (arg) {return function (elem) { return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0; }; });
} else {
	//lower version
	$.expr[':'].Contains = function (a, i, m) {return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0; };
}
//dropdown class
function dd(element, settings) {
	var _settings = $.extend(true,{byJson: {data: null, selectedIndex: 0, name: null, size: 0, multiple: false, width: 250},
		mainCSS: 'dd',
		height: 120, //not using currently
		visibleRows: 7,
		rowHeight: 0,
		showIcon: true,
		zIndex: 9999,
		useSprite: false,
		animStyle: '',
		event:'click',
		openDirection: 'auto', //auto || alwaysUp
		jsonTitle: true,
		style: '',
		disabledOpacity: 0.7,
		disabledOptionEvents: true,
		childWidth:0,
		enableCheckbox:false, //this needs to multiple or it will set element to multiple
		checkboxNameSuffix:'_mscheck',
		append:'',
		prepend:'',
		on: {create: null,open: null,close: null,add: null,remove: null,change: null,blur: null,click: null,dblclick: null,mousemove: null,mouseover: null,mouseout: null,focus: null,mousedown: null,mouseup: null}
		}, settings);
	var _this = this; //this class
	var _holderId = {postElementHolder: '_msddHolder', postID: '_msdd', postTitleID: '_title',postTitleTextID: '_titleText', postChildID: '_child'};
	var _styles = {dd:_settings.mainCSS, ddTitle: 'ddTitle', arrow: 'arrow arrowoff', ddChild: 'ddChild', ddTitleText: 'ddTitleText',disabled: 'disabled', enabled: 'enabled', ddOutOfVision: 'ddOutOfVision', borderTop: 'borderTop', noBorderTop: 'noBorderTop', selected: 'selected', divider: 'divider', optgroup: "optgroup", optgroupTitle: "optgroupTitle", description: "description", label: "ddlabel",hover: 'hover',disabledAll: 'disabledAll'};
	var _styles_i = {li: '_msddli_',borderRadiusTp: 'borderRadiusTp',ddChildMore: 'border shadow',fnone: "fnone"};
	var _isList = false, _isMultiple=false,_isDisabled=false, _cacheElement = {}, _element, _orginial = {}, _isOpen=false;
	var DOWN_ARROW = 40, UP_ARROW = 38, LEFT_ARROW=37, RIGHT_ARROW=39, ESCAPE = 27, ENTER = 13, ALPHABETS_START = 47, SHIFT=16 , CONTROL = 17;
	var _shiftHolded=false, _controlHolded=false,_lastTarget=null,_forcedTrigger=false, _oldSelected, _isCreated = false;
	var _doc = document, _ua = window.navigator.userAgent, _isIE = _ua.match(/msie/i);
	var msieversion = function()
   	{
      var msie = _ua.indexOf("MSIE");
      if ( msie > 0 ) {      // If Internet Explorer, return version number
         return parseInt (_ua.substring (msie+5, _ua.indexOf (".", msie)));
	  } else {                // If another browser, return 0
         return 0;
	  };
   	};
	var _checkDataSetting = function() {
		_settings.mainCSS = $("#"+_element).data("maincss") || _settings.mainCSS;
		_settings.visibleRows = $("#"+_element).data("visiblerows") || _settings.visibleRows;
		if($("#"+_element).data("showicon")==false) {_settings.showIcon = $("#"+_element).data("showicon");};
		_settings.useSprite = $("#"+_element).data("usesprite") || _settings.useSprite;
		_settings.animStyle = $("#"+_element).data("animstyle") || _settings.animStyle;
		_settings.event = $("#"+_element).data("event") || _settings.event;
		_settings.openDirection = $("#"+_element).data("opendirection") || _settings.openDirection;
		_settings.jsonTitle = $("#"+_element).data("jsontitle") || _settings.jsonTitle;
		_settings.disabledOpacity = $("#"+_element).data("disabledopacity") || _settings.disabledOpacity;
		_settings.childWidth = $("#"+_element).data("childwidth") || _settings.childWidth;
		_settings.enableCheckbox = $("#"+_element).data("enablecheckbox") || _settings.enableCheckbox;
		_settings.checkboxNameSuffix = $("#"+_element).data("checkboxnamesuffix") || _settings.checkboxNameSuffix;
		_settings.append = $("#"+_element).data("append") || _settings.append;
		_settings.prepend = $("#"+_element).data("prepend") || _settings.prepend;
	};
	var getElement = function(ele) {
		if (_cacheElement[ele] === undefined) {
			_cacheElement[ele] = _doc.getElementById(ele);
		}
		return _cacheElement[ele];
	};
	var _getIndex = function(opt) {
		var childid = _getPostID("postChildID");
		return $("#"+childid + " li."+_styles_i.li).index(opt);
	};
	var _createByJson = function() {
		if (_settings.byJson.data) {
				var validData = ["description","image",  "title"];
				try {
					if (!element.id) {
						element.id = "dropdown"+msBeautify.counter;
					};
					_settings.byJson.data = eval(_settings.byJson.data);
					//change element
					var id = "msdropdown"+(msBeautify.counter++);
					var obj = {};
					obj.id = id;
					obj.name = _settings.byJson.name || element.id; //its name
					if (_settings.byJson.size>0) {
						obj.size = _settings.byJson.size;
					};
					obj.multiple = _settings.byJson.multiple;
					var oSelect = _createElement("select", obj);
					for(var i=0;i<_settings.byJson.data.length;i++) {
						var current = _settings.byJson.data[i];
						var opt = new Option(current.text, current.value);
						for(var p in current) {
							if (p.toLowerCase() != 'text') {
								var key = ($.inArray(p.toLowerCase(), validData)!=-1) ? "data-" : "";
								opt.setAttribute(key+p, current[p]);
							};
						};
						oSelect.options[i] = opt;
					};
					getElement(element.id).appendChild(oSelect);
					oSelect.selectedIndex = _settings.byJson.selectedIndex;
					$(oSelect).css({width: _settings.byJson.width+'px'});
					//now change element for access other things
					element = oSelect;
				} catch(e) {
					throw "There is an error in json data.";
				};
		};
	};
	var _construct = function() {
		 //set properties
		 _createByJson();
		if (!element.id) {
			element.id = "msdrpdd"+(msBeautify.counter++);
		};
		_element = element.id;
		_this._element = _element;
		_checkDataSetting();
		_isDisabled = getElement(_element).disabled;
		var useCheckbox = _settings.enableCheckbox;
		if(Boolean(useCheckbox)===true) {
			getElement(_element).multiple = true;
			_settings.enableCheckbox = true;
		};
		_isList = (getElement(_element).size>1 || getElement(_element).multiple==true) ? true : false;
		//trace("_isList "+_isList);
		if (_isList) {_isMultiple = getElement(_element).multiple;};
		_mergeAllProp();
		//create layout
		_createLayout();
		//set ui prop
		_updateProp("uiData", _getDataAndUI());
		_updateProp("selectedOptions", $("#"+_element +" option:selected"));
		var childid = _getPostID("postChildID");
		_oldSelected = $("#" + childid + " li." + _styles.selected);

	 };
	 /********************************************************************************************/
	var _getPostID = function (id) {
		return _element+_holderId[id];
	};
	var _getInternalStyle = function(ele) {
		 var s = (ele.style === undefined) ? "" : ele.style.cssText;
		 return s;
	};
	var _parseOption = function(opt) {
		var imagePath = '', title ='', description='', value=-1, text='', className='', imagecss = '';
		if (opt !== undefined) {
			var attrTitle = opt.title || "";
			//data-title
			if (attrTitle!="") {
				var reg = /^\{.*\}$/;
				var isJson = reg.test(attrTitle);
				if (isJson && _settings.jsonTitle) {
					var obj =  eval("["+attrTitle+"]");
				};
				title = (isJson && _settings.jsonTitle) ? obj[0].title : title;
				description = (isJson && _settings.jsonTitle) ? obj[0].description : description;
				imagePath = (isJson && _settings.jsonTitle) ? obj[0].image : attrTitle;
				imagecss = (isJson && _settings.jsonTitle) ? obj[0].imagecss : imagecss;
			};

			text = opt.text || '';
			value = opt.value || '';
			className = opt.className || "";
			//ignore title attribute if playing with data tags
			title = $(opt).prop("data-title") || $(opt).data("title") || (title || "");
			description = $(opt).prop("data-description") || $(opt).data("description") || (description || "");
			imagePath = $(opt).prop("data-image") || $(opt).data("image") || (imagePath || "");
			imagecss = $(opt).prop("data-imagecss") || $(opt).data("imagecss") || (imagecss || "");

		};
		var o = {image: imagePath, title: title, description: description, value: value, text: text, className: className, imagecss:imagecss};
		return o;
	};
	var _createElement = function(nm, attr, html) {
		var tag = _doc.createElement(nm);
		if (attr) {
		 for(var i in attr) {
			 switch(i) {
				 case "style":
					tag.style.cssText  = attr[i];
				 break;
				 default:
					tag[i]  = attr[i];
				 break;
			 };
		 };
		};
		if (html) {
		 tag.innerHTML = html;
		};
		return tag;
	};
	 /********************************************************************************************/
	  /*********************** <layout> *************************************/
	var _hideOriginal = function() {
		var hidid = _getPostID("postElementHolder");
		if ($("#"+hidid).length==0) {
			var obj = {style: 'height: 0px;overflow: hidden;position: absolute;',className: _styles.ddOutOfVision};
			obj.id = hidid;
			var oDiv = _createElement("div", obj);
			$("#"+_element).after(oDiv);
			$("#"+_element).appendTo($("#"+hidid));
		} else {
			$("#"+hidid).css({height: 0,overflow: 'hidden',position: 'absolute'});
		};
	};
	var _createWrapper = function () {
		var obj = {
			className: _styles.dd + " ddcommon borderRadius"
		};
		var styles = _getInternalStyle(getElement(_element));
		var w = $("#" + _element).outerWidth();
		obj.style = "width: " + w + "px;";
		if (styles.length > 0) {
			obj.style = obj.style + "" + styles;
		};
		obj.id = _getPostID("postID");
		var oDiv = _createElement("div", obj);
		return oDiv;
	};
	var _createTitle = function () {
		var selectedOption;
		if(getElement(_element).selectedIndex>=0) {
			selectedOption = getElement(_element).options[getElement(_element).selectedIndex];
		} else {
			selectedOption = {value:'', text:''};
		}
		var spriteClass = "", selectedClass = "";
		//check sprite
		var useSprite = $("#"+_element).data("usesprite");
		if(useSprite) { _settings.useSprite = useSprite; };
		if (_settings.useSprite != false) {
			spriteClass = " " + _settings.useSprite;
			selectedClass = " " + selectedOption.className;
		};
		var oTitle = _createElement("div", {className: _styles.ddTitle + spriteClass + " " + _styles_i.borderRadiusTp});
		//divider
		//var oDivider = _createElement("span", {className: _styles.divider});
		//arrow
		var oArrow = _createElement("span", {className: _styles.arrow});
		//title Text
		var titleid = _getPostID("postTitleID");
		var oTitleText = _createElement("span", {className: _styles.ddTitleText + selectedClass, id: titleid});

		var parsed = _parseOption(selectedOption);
		var arrowPath = parsed.image;
		var sText = parsed.text || "";
		if (arrowPath != "" && _settings.showIcon) {
			var oIcon = _createElement("img");
			oIcon.src = arrowPath;
			if(parsed.imagecss!="") {
				oIcon.className = parsed.imagecss+" ";
			};
		};
		var oTitleText_in = _createElement("span", {className: _styles.label}, sText);
		//oTitle.appendChild(oDivider);
		oTitle.appendChild(oArrow);
		if (oIcon) {
			oTitleText.appendChild(oIcon);
		};
		oTitleText.appendChild(oTitleText_in);
		oTitle.appendChild(oTitleText);
		//var oDescription = _createElement("span", {className: _styles.description}, parsed.description);
		//oTitleText.appendChild(oDescription);
		return oTitle;
	};
	var _createFilterBox = function () {
		var tid = _getPostID("postTitleTextID");
		var sText = _createElement("input", {id: tid, type: 'text', value: '', autocomplete: 'off', className: 'text shadow borderRadius', style: 'display: none'});
		return sText;
	};
	var _createChild = function (opt) {
		var obj = {};
		var styles = _getInternalStyle(opt);
		if (styles.length > 0) {obj.style = styles; };
		var css = (opt.disabled) ? _styles.disabled : _styles.enabled;
		css = (opt.selected) ? (css + " " + _styles.selected) : css;
		css = css + " " + _styles_i.li;
		obj.className = css;
		if (_settings.useSprite != false) {
			obj.className = css + " " + opt.className;
		};
		var li = _createElement("li", obj);
		var parsed = _parseOption(opt);
		if (parsed.title != "") {
			li.title = parsed.title;
		};
		var arrowPath = parsed.image;
		if (arrowPath != "" && _settings.showIcon) {
			var oIcon = _createElement("img");
			oIcon.src = arrowPath;
			if(parsed.imagecss!="") {
				oIcon.className = parsed.imagecss+" ";
			};
		};

		if (parsed.description != "") {
			var oDescription = _createElement("span", {
				className: _styles.description
			}, parsed.description);
		};
		var sText = opt.text || "";
		var oTitleText = _createElement("span", {
			className: _styles.label
		}, sText);
		//checkbox
		if(_settings.enableCheckbox===true) {
			var chkbox = _createElement("input", {
			type: 'checkbox', name:_element+_settings.checkboxNameSuffix+'[]', value:opt.value||""}); //this can be used for future
			li.appendChild(chkbox);
			if(_settings.enableCheckbox===true) {
				chkbox.checked = (opt.selected) ? true : false;
			};
		};
		if (oIcon) {
			li.appendChild(oIcon);
		};
		li.appendChild(oTitleText);
		if (oDescription) {
			li.appendChild(oDescription);
		} else {
			if (oIcon) {
				oIcon.className = oIcon.className+_styles_i.fnone;
			};
		};
		var oClear = _createElement("div", {className: 'clear'});
		li.appendChild(oClear);
		return li;
	};
	var _createChildren = function () {
		var childid = _getPostID("postChildID");
		var obj = {className: _styles.ddChild + " ddchild_ " + _styles_i.ddChildMore, id: childid};
		if (_isList == false) {
			obj.style = "z-index: " + _settings.zIndex;
		} else {
			obj.style = "z-index:1";
		};
		var childWidth = $("#"+_element).data("childwidth") || _settings.childWidth;
		if(childWidth) {
			obj.style =  (obj.style || "") + ";width:"+childWidth;
		};
		var oDiv = _createElement("div", obj);
		var ul = _createElement("ul");
		if (_settings.useSprite != false) {
			ul.className = _settings.useSprite;
		};
		var allOptions = getElement(_element).children;
		for (var i = 0; i < allOptions.length; i++) {
			var current = allOptions[i];
			var li;
			if (current.nodeName.toLowerCase() == "optgroup") {
				//create ul
				li = _createElement("li", {className: _styles.optgroup});
				var span = _createElement("span", {className: _styles.optgroupTitle}, current.label);
				li.appendChild(span);
				var optChildren = current.children;
				var optul = _createElement("ul");
				for (var j = 0; j < optChildren.length; j++) {
					var opt_li = _createChild(optChildren[j]);
					optul.appendChild(opt_li);
				};
				li.appendChild(optul);
			} else {
				li = _createChild(current);
			};
			ul.appendChild(li);
		};
		oDiv.appendChild(ul);
		return oDiv;
	};
	var _childHeight = function (val) {
		var childid = _getPostID("postChildID");
		if (val) {
			if (val == -1) { //auto
				$("#"+childid).css({height: "auto", overflow: "auto"});
			} else {
				$("#"+childid).css("height", val+"px");
			};
			return false;
		};
		//else return height
		var iHeight;
		if (getElement(_element).options.length > _settings.visibleRows) {
			var margin = parseInt($("#" + childid + " li:first").css("padding-bottom")) + parseInt($("#" + childid + " li:first").css("padding-top"));
			if(_settings.rowHeight===0) {
				$("#" + childid).css({visibility:'hidden',display:'block'}); //hack for first child
				_settings.rowHeight = Math.round($("#" + childid + " li:first").height());
				$("#" + childid).css({visibility:'visible'});
				if(!_isList || _settings.enableCheckbox===true) {
					$("#" + childid).css({display:'none'});
				};
			};
			iHeight = ((_settings.rowHeight + margin) * _settings.visibleRows);
		} else if (_isList) {
			iHeight = $("#" + _element).height(); //get height from original element
		};
		return iHeight;
	};
	var _applyChildEvents = function () {
		var childid = _getPostID("postChildID");
		$("#" + childid).on("click", function (e) {
			if (_isDisabled === true) return false;
			//prevent body click
			e.preventDefault();
			e.stopPropagation();
			if (_isList) {
				_bind_on_events();
			};
		});
		$("#" + childid + " li." + _styles.enabled).on("click", function (e) {
			if(e.target.nodeName.toLowerCase() !== "input") {
				_close(this);
			};
		});
		$("#" + childid + " li." + _styles.enabled).on("mousedown", function (e) {
			if (_isDisabled === true) return false;
			_oldSelected = $("#" + childid + " li." + _styles.selected);
			_lastTarget = this;
			e.preventDefault();
			e.stopPropagation();
			//select current input
			if(_settings.enableCheckbox===true) {
				if(e.target.nodeName.toLowerCase() === "input") {
					_controlHolded = true;
				};
			};
			if (_isList === true) {
				if (_isMultiple) {
					if (_shiftHolded === true) {
						$(this).addClass(_styles.selected);
						var selected = $("#" + childid + " li." + _styles.selected);
						var lastIndex = _getIndex(this);
						if (selected.length > 1) {
							var items = $("#" + childid + " li." + _styles_i.li);
							var ind1 = _getIndex(selected[0]);
							var ind2 = _getIndex(selected[1]);
							if (lastIndex > ind2) {
								ind1 = (lastIndex);
								ind2 = ind2 + 1;
							};
							for (var i = Math.min(ind1, ind2); i <= Math.max(ind1, ind2); i++) {
								var current = items[i];
								if ($(current).hasClass(_styles.enabled)) {
									$(current).addClass(_styles.selected);
								};
							};
						};
					} else if (_controlHolded === true) {
						$(this).toggleClass(_styles.selected); //toggle
						if(_settings.enableCheckbox===true) {
							var checkbox = this.childNodes[0];
							checkbox.checked = !checkbox.checked; //toggle
						};
					} else {
						$("#" + childid + " li." + _styles.selected).removeClass(_styles.selected);
						$("#" + childid + " input:checkbox").prop("checked", false);
						$(this).addClass(_styles.selected);
						if(_settings.enableCheckbox===true) {
							this.childNodes[0].checked = true;
						};
					};
				} else {
					$("#" + childid + " li." + _styles.selected).removeClass(_styles.selected);
					$(this).addClass(_styles.selected);
				};
				//fire event on mouseup
			} else {
				$("#" + childid + " li." + _styles.selected).removeClass(_styles.selected);
				$(this).addClass(_styles.selected);
			};
		});
		$("#" + childid + " li." + _styles.enabled).on("mouseenter", function (e) {
			if (_isDisabled === true) return false;
			e.preventDefault();
			e.stopPropagation();
			if (_lastTarget != null) {
				if (_isMultiple) {
					$(this).addClass(_styles.selected);
					if(_settings.enableCheckbox===true) {
						this.childNodes[0].checked = true;
					};
				};
			};
		});

		$("#" + childid + " li." + _styles.enabled).on("mouseover", function (e) {
			if (_isDisabled === true) return false;
			$(this).addClass(_styles.hover);
		});
		$("#" + childid + " li." + _styles.enabled).on("mouseout", function (e) {
			if (_isDisabled === true) return false;
			$("#" + childid + " li." + _styles.hover).removeClass(_styles.hover);
		});

		$("#" + childid + " li." + _styles.enabled).on("mouseup", function (e) {
			if (_isDisabled === true) return false;
			e.preventDefault();
			e.stopPropagation();
			if(_settings.enableCheckbox===true) {
				_controlHolded = false;
			};
			var selected = $("#" + childid + " li." + _styles.selected).length;
			_forcedTrigger = (_oldSelected.length != selected || selected == 0) ? true : false;
			_fireAfterItemClicked();
			_unbind_on_events(); //remove old one
			_bind_on_events();
			_lastTarget = null;
		});

		/* options events */
		if (_settings.disabledOptionEvents == false) {
			$("#" + childid + " li." + _styles_i.li).on("click", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "click");
			});
			$("#" + childid + " li." + _styles_i.li).on("mouseenter", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "mouseenter");
			});
			$("#" + childid + " li." + _styles_i.li).on("mouseover", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "mouseover");
			});
			$("#" + childid + " li." + _styles_i.li).on("mouseout", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "mouseout");
			});
			$("#" + childid + " li." + _styles_i.li).on("mousedown", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "mousedown");
			});
			$("#" + childid + " li." + _styles_i.li).on("mouseup", function (e) {
				if (_isDisabled === true) return false;
				fireOptionEventIfExist(this, "mouseup");
			});
		};
	};
	var _removeChildEvents = function () {
		var childid = _getPostID("postChildID");
		$("#" + childid).off("click");
		$("#" + childid + " li." + _styles.enabled).off("mouseenter");
		$("#" + childid + " li." + _styles.enabled).off("click");
		$("#" + childid + " li." + _styles.enabled).off("mouseover");
		$("#" + childid + " li." + _styles.enabled).off("mouseout");
		$("#" + childid + " li." + _styles.enabled).off("mousedown");
		$("#" + childid + " li." + _styles.enabled).off("mouseup");
	};
	var _applyEvents = function () {
		var id = _getPostID("postID");
		var childid = _getPostID("postChildID");
		$("#" + id).on(_settings.event, function (e) {
			if (_isDisabled === true) return false;
			fireEventIfExist("click");
			//prevent body click
			e.preventDefault();
			e.stopPropagation();
			_open(e);
		});
		_applyChildEvents();
		$("#" + id).on("dblclick", on_dblclick);
		$("#" + id).on("mousemove", on_mousemove);
		$("#" + id).on("mouseenter", on_mouseover);
		$("#" + id).on("mouseleave", on_mouseout);
		$("#" + id).on("mousedown", on_mousedown);
		$("#" + id).on("mouseup", on_mouseup);
	};
	//after create
	var _fixedForList = function () {
		var id = _getPostID("postID");
		var childid = _getPostID("postChildID");
		if (_isList === true && _settings.enableCheckbox===false) {
			$("#" + id + " ." + _styles.ddTitle).hide();
			$("#" + childid).css({display: 'block', position: 'relative'});
			//_open();
		} else {
			if(_settings.enableCheckbox===false) {
				_isMultiple = false; //set multiple off if this is not a list
			};
			$("#" + id + " ." + _styles.ddTitle).show();
			$("#" + childid).css({display: 'none', position: 'absolute'});
			//set value
			var first = $("#" + childid + " li." + _styles.selected)[0];
			$("#" + childid + " li." + _styles.selected).removeClass(_styles.selected);
			var index = _getIndex($(first).addClass(_styles.selected));
			_setValue(index);
		};
		_childHeight(_childHeight()); //get and set height
	};
	var _fixedForDisabled = function () {
		var id = _getPostID("postID");
		var opc = (_isDisabled == true) ? _settings.disabledOpacity : 1;
		if (_isDisabled === true) {
			$("#" + id).addClass(_styles.disabledAll);
		} else {
			$("#" + id).removeClass(_styles.disabledAll);
		};
	};
	var _fixedSomeUI = function () {
		//auto filter
		var tid = _getPostID("postTitleTextID");
		$("#" + tid).on("keyup", _applyFilters);
		//if is list
		_fixedForList();
		_fixedForDisabled();
	};
	var _createLayout = function () {
		var oDiv = _createWrapper();
		var oTitle = _createTitle();
		oDiv.appendChild(oTitle);
		//auto filter box
		var oFilterBox = _createFilterBox();
		oDiv.appendChild(oFilterBox);

		var oChildren = _createChildren();
		oDiv.appendChild(oChildren);
		$("#" + _element).after(oDiv);
		_hideOriginal(); //hideOriginal
		_fixedSomeUI();
		_applyEvents();

		var childid = _getPostID("postChildID");
		//append
		if(_settings.append!='') {
			$("#" + childid).append(_settings.append);
		};
		//prepend
		if(_settings.prepend!='') {
			$("#" + childid).prepend(_settings.prepend);
		};
		if (typeof _settings.on.create == "function") {
			_settings.on.create.apply(_this, arguments);
		};
	};
	var _selectMutipleOptions = function (bySelected) {
		var childid = _getPostID("postChildID");
		var selected = bySelected || $("#" + childid + " li." + _styles.selected); //bySelected or by argument
		for (var i = 0; i < selected.length; i++) {
			var ind = _getIndex(selected[i]);
			getElement(_element).options[ind].selected = "selected";
		};
		_setValue(selected);
	};
	var _fireAfterItemClicked = function () {
		//console.log("_fireAfterItemClicked")
		var childid = _getPostID("postChildID");
		var selected = $("#" + childid + " li." + _styles.selected);
		if (_isMultiple && (_shiftHolded || _controlHolded) || _forcedTrigger) {
			getElement(_element).selectedIndex = -1; //reset old
		};
		var index;
		if (selected.length == 0) {
			index = -1;
		} else if (selected.length > 1) {
			//selected multiple
			_selectMutipleOptions(selected);
			//index = $("#" + childid + " li." + _styles.selected);

		} else {
			//if one selected
			index = _getIndex($("#" + childid + " li." + _styles.selected));
		};
		if ((getElement(_element).selectedIndex != index || _forcedTrigger) && selected.length<=1) {
			_forcedTrigger = false;
			var evt = has_handler("change");
			getElement(_element).selectedIndex = index;
			_setValue(index);
			//local
			if (typeof _settings.on.change == "function") {
				var d = _getDataAndUI();
				_settings.on.change(d.data, d.ui);
			};
			$("#" + _element).trigger("change");
		};
	};
	var _setValue = function (index, byvalue) {
		if (index !== undefined) {
			var selectedIndex, value, selectedText;
			if (index == -1) {
				selectedIndex = -1;
				value = "";
				selectedText = "";
				_updateTitleUI(-1);
			} else {
				//by index or byvalue
				if (typeof index != "object") {
					var opt = getElement(_element).options[index];
					getElement(_element).selectedIndex = index;
					selectedIndex = index;
					value = _parseOption(opt);
					selectedText = (index >= 0) ? getElement(_element).options[index].text : "";
					_updateTitleUI(undefined, value);
					value = value.value; //for bottom
				} else {
					//this is multiple or by option
					selectedIndex = (byvalue && byvalue.index) || getElement(_element).selectedIndex;
					value = (byvalue && byvalue.value) || getElement(_element).value;
					selectedText = (byvalue && byvalue.text) || getElement(_element).options[getElement(_element).selectedIndex].text || "";
					_updateTitleUI(selectedIndex);
				};
			};
			_updateProp("selectedIndex", selectedIndex);
			_updateProp("value", value);
			_updateProp("selectedText", selectedText);
			_updateProp("children", getElement(_element).children);
			_updateProp("uiData", _getDataAndUI());
			_updateProp("selectedOptions", $("#" + _element + " option:selected"));
		};
	};
	var has_handler = function (name) {
		//True if a handler has been added in the html.
		var evt = {byElement: false, byJQuery: false, hasEvent: false};
		var obj = $("#" + _element);
		//console.log(name)
		try {
			//console.log(obj.prop("on" + name) + " "+name);
			if (obj.prop("on" + name) !== null) {
				evt.hasEvent = true;
				evt.byElement = true;
			};
		} catch(e) {
			//console.log(e.message);
		}
		// True if a handler has been added using jQuery.
		var evs;
		if (typeof $._data == "function") { //1.8
			evs = $._data(obj[0], "events");
		} else {
			evs = obj.data("events");
		};
		if (evs && evs[name]) {
			evt.hasEvent = true;
			evt.byJQuery = true;
		};
		return evt;
	};
	var _bind_on_events = function () {
		_unbind_on_events();
		$("body").on("click", _close);
		//bind more events
		$(document).on("keydown", on_keydown);
		$(document).on("keyup", on_keyup);
		//focus will work on this
	};
	var _unbind_on_events = function () {
		$("body").off("click", _close);
		//bind more events
		$(document).off("keydown", on_keydown);
		$(document).off("keyup", on_keyup);
	};
	var _applyFilters = function () {
		var childid = _getPostID("postChildID");
		var tid = _getPostID("postTitleTextID");
		var sText = getElement(tid).value;
		if (sText.length == 0) {
			$("#" + childid + " li:hidden").show(); //show if hidden
			_childHeight(_childHeight());
		} else {
			$("#" + childid + " li").hide();
			$("#" + childid + " li:Contains('" + sText + "')").show();
			if ($("#" + childid + " li:visible").length <= _settings.visibleRows) {
				_childHeight(-1); //set autoheight
			};
		};
	};
	var _showFilterBox = function () {
		var tid = _getPostID("postTitleTextID");
		if ($("#" + tid + ":hidden").length > 0 && _controlHolded == false) {
			$("#" + tid + ":hidden").show().val("");
			getElement(tid).focus();
		};
	};
	var _hideFilterBox = function () {
		var tid = _getPostID("postTitleTextID");
		if ($("#" + tid + ":visible").length > 0) {
			$("#" + tid + ":visible").hide();
			getElement(tid).blur();
		};
	};
	var on_keydown = function (evt) {
		var tid = _getPostID("postTitleTextID");
		var childid = _getPostID("postChildID");
		switch (evt.keyCode) {
			case DOWN_ARROW:
			case RIGHT_ARROW:
				evt.preventDefault();
				evt.stopPropagation();
				//_hideFilterBox();
				_next();
				break;
			case UP_ARROW:
			case LEFT_ARROW:
				evt.preventDefault();
				evt.stopPropagation();
				//_hideFilterBox();
				_previous();
				break;
			case ESCAPE:
			case ENTER:
				evt.preventDefault();
				evt.stopPropagation();
				_close();
				var selected = $("#" + childid + " li." + _styles.selected).length;
				_forcedTrigger = (_oldSelected.length != selected || selected == 0) ? true : false;
				_fireAfterItemClicked();
				_unbind_on_events(); //remove old one
				_lastTarget = null;
				break;
			case SHIFT:
				_shiftHolded = true;
				break;
			case CONTROL:
				_controlHolded = true;
				break;
			default:
				if (evt.keyCode >= ALPHABETS_START && _isList === false) {
					_showFilterBox();
				};
				break;
		};
		if (_isDisabled === true) return false;
		fireEventIfExist("keydown");
	};
	var on_keyup = function (evt) {
		switch (evt.keyCode) {
			case SHIFT:
				_shiftHolded = false;
				break;
			case CONTROL:
				_controlHolded = false;
				break;
		};
		if (_isDisabled === true) return false;
		fireEventIfExist("keyup");
	};
	var on_dblclick = function (evt) {
		if (_isDisabled === true) return false;
		fireEventIfExist("dblclick");
	};
	var on_mousemove = function (evt) {
		if (_isDisabled === true) return false;
		fireEventIfExist("mousemove");
	};

	var on_mouseover = function (evt) {
		if (_isDisabled === true) return false;
		evt.preventDefault();
		fireEventIfExist("mouseover");
	};
	var on_mouseout = function (evt) {
		if (_isDisabled === true) return false;
		evt.preventDefault();
		fireEventIfExist("mouseout");
	};
	var on_mousedown = function (evt) {
		if (_isDisabled === true) return false;
		fireEventIfExist("mousedown");
	};
	var on_mouseup = function (evt) {
		if (_isDisabled === true) return false;
		fireEventIfExist("mouseup");
	};
	var option_has_handler = function (opt, name) {
		//True if a handler has been added in the html.
		var evt = {byElement: false, byJQuery: false, hasEvent: false};
		if ($(opt).prop("on" + name) != undefined) {
			evt.hasEvent = true;
			evt.byElement = true;
		};
		// True if a handler has been added using jQuery.
		var evs = $(opt).data("events");
		if (evs && evs[name]) {
			evt.hasEvent = true;
			evt.byJQuery = true;
		};
		return evt;
	};
	var fireOptionEventIfExist = function (li, evt_n) {
		if (_settings.disabledOptionEvents == false) {
			var opt = getElement(_element).options[_getIndex(li)];
			//check if original has some
			if (option_has_handler(opt, evt_n).hasEvent === true) {
				if (option_has_handler(opt, evt_n).byElement === true) {
					opt["on" + evt_n]();
				};
				if (option_has_handler(opt, evt_n).byJQuery === true) {
					switch (evt_n) {
						case "keydown":
						case "keyup":
							//key down/up will check later
							break;
						default:
							$(opt).trigger(evt_n);
							break;
					};
				};
				return false;
			};
		};
	};
	var fireEventIfExist = function (evt_n) {
		//local
		if (typeof _settings.on[evt_n] == "function") {
			_settings.on[evt_n].apply(this, arguments);
		};
		//check if original has some
		if (has_handler(evt_n).hasEvent === true) {
			if (has_handler(evt_n).byElement === true) {
				getElement(_element)["on" + evt_n]();
			};
			if (has_handler(evt_n).byJQuery === true) {
				switch (evt_n) {
					case "keydown":
					case "keyup":
						//key down/up will check later
						break;
					default:
						$("#" + _element).trigger(evt_n);
						break;
				};
			};
			return false;
		};
	};
	/******************************* navigation **********************************************/
	var _scrollToIfNeeded = function (opt) {
		var childid = _getPostID("postChildID");
		//if scroll is needed
		opt = (opt !== undefined) ? opt : $("#" + childid + " li." + _styles.selected);
		if (opt.length > 0) {
			var pos = parseInt(($(opt).position().top));
			var ch = parseInt($("#" + childid).height());
			if (pos > ch) {
				var top = pos + $("#" + childid).scrollTop() - (ch/2);
				$("#" + childid).animate({scrollTop:top}, 0);
			};
		};
	};
	var _next = function () {
		var childid = _getPostID("postChildID");
		var items = $("#" + childid + " li:visible." + _styles_i.li);
		var selected = $("#" + childid + " li:visible." + _styles.selected);
		selected = (selected.length==0) ? items[0] : selected;
		var index = $("#" + childid + " li:visible." + _styles_i.li).index(selected);
		if ((index < items.length - 1)) {
			index = getNext(index);
			if (index < items.length) { //check again - hack for last disabled
				if (!_shiftHolded || !_isList || !_isMultiple) {
					$("#" + childid + " ." + _styles.selected).removeClass(_styles.selected);
				};
				$(items[index]).addClass(_styles.selected);
				_updateTitleUI(index);
				if (_isList == true) {
					_fireAfterItemClicked();
				};
				_scrollToIfNeeded($(items[index]));
			};
			if (!_isList) {
				_adjustOpen();
			};
		};
		function getNext(ind) {
			ind = ind + 1;
			if (ind > items.length) {
				return ind;
			};
			if ($(items[ind]).hasClass(_styles.enabled) === true) {
				return ind;
			};
			return ind = getNext(ind);
		};
	};
	var _previous = function () {
		var childid = _getPostID("postChildID");
		var selected = $("#" + childid + " li:visible." + _styles.selected);
		var items = $("#" + childid + " li:visible." + _styles_i.li);
		var index = $("#" + childid + " li:visible." + _styles_i.li).index(selected[0]);
		if (index >= 0) {
			index = getPrev(index);
			if (index >= 0) { //check again - hack for disabled
				if (!_shiftHolded || !_isList || !_isMultiple) {
					$("#" + childid + " ." + _styles.selected).removeClass(_styles.selected);
				};
				$(items[index]).addClass(_styles.selected);
				_updateTitleUI(index);
				if (_isList == true) {
					_fireAfterItemClicked();
				};
				if (parseInt(($(items[index]).position().top + $(items[index]).height())) <= 0) {
					var top = ($("#" + childid).scrollTop() - $("#" + childid).height()) - $(items[index]).height();
					$("#" + childid).animate({scrollTop: top}, 500);
				};
			};
			if (!_isList) {
				_adjustOpen();
			};
		};

		function getPrev(ind) {
			ind = ind - 1;
			if (ind < 0) {
				return ind;
			};
			if ($(items[ind]).hasClass(_styles.enabled) === true) {
				return ind;
			};
			return ind = getPrev(ind);
		};
	};
	var _adjustOpen = function () {
		var id = _getPostID("postID");
		var childid = _getPostID("postChildID");
		var pos = $("#" + id).offset();
		var mH = $("#" + id).height();
		var wH = $(window).height();
		var st = $(window).scrollTop();
		var cH = $("#" + childid).height();
		var top = $("#" + id).height(); //this close so its title height
		if ((wH + st) < Math.floor(cH + mH + pos.top) || _settings.openDirection.toLowerCase() == 'alwaysup') {
			top = cH;
			$("#" + childid).css({top: "-" + top + "px", display: 'block', zIndex: _settings.zIndex});
			$("#" + id).removeClass("borderRadius borderRadiusTp").addClass("borderRadiusBtm");
			var top = $("#" + childid).offset().top;
			if (top < -10) {
				$("#" + childid).css({top: (parseInt($("#" + childid).css("top")) - top + 20 + st) + "px", zIndex: _settings.zIndex});
				$("#" + id).removeClass("borderRadiusBtm borderRadiusTp").addClass("borderRadius");
			};
		} else {
			$("#" + childid).css({top: top + "px", zIndex: _settings.zIndex});
			$("#" + id).removeClass("borderRadius borderRadiusBtm").addClass("borderRadiusTp");
		};
		//hack for ie zindex
		//i hate ie :D
		if(_isIE) {
			if(msieversion()<=7) {
				$('div.ddcommon').css("zIndex", _settings.zIndex-10);
				$("#" + id).css("zIndex", _settings.zIndex+5);
			};
		};
	};
	var _open = function (e) {
		if (_isDisabled === true) return false;
		var id = _getPostID("postID");
		var childid = _getPostID("postChildID");
		if (!_isOpen) {
			_isOpen = true;
			if (msBeautify.oldDiv != '') {
				$("#" + msBeautify.oldDiv).css({display: "none"}); //hide all
			};
			msBeautify.oldDiv = childid;
			$("#" + childid + " li:hidden").show(); //show if hidden
			_adjustOpen();
			var animStyle = _settings.animStyle;
			if(animStyle=="" || animStyle=="none") {
				$("#" + childid).css({display:"block"});
				_scrollToIfNeeded();
				if (typeof _settings.on.open == "function") {
					var d = _getDataAndUI();
					_settings.on.open(d.data, d.ui);
				};
			} else {
				$("#" + childid)[animStyle]("fast", function () {
					_scrollToIfNeeded();
					if (typeof _settings.on.open == "function") {
						var d = _getDataAndUI();
						_settings.on.open(d.data, d.ui);
					};
				});
			};
			_bind_on_events();
		} else {
			if(_settings.event!=='mouseover') {
				_close();
			};
		};
	};
	var _close = function (e) {
		_isOpen = false;
		var id = _getPostID("postID");
		var childid = _getPostID("postChildID");
		if (_isList === false || _settings.enableCheckbox===true) {
			$("#" + childid).css({display: "none"});
			$("#" + id).removeClass("borderRadiusTp borderRadiusBtm").addClass("borderRadius");
		};
		_unbind_on_events();
		if (typeof _settings.on.close == "function") {
			var d = _getDataAndUI();
			_settings.on.close(d.data, d.ui);
		};
		//rest some old stuff
		_hideFilterBox();
		_childHeight(_childHeight()); //its needed after filter applied
		$("#" + childid).css({zIndex:1})
	};
	/*********************** </layout> *************************************/
	var _mergeAllProp = function () {
		try {
			_orginial = $.extend(true, {}, getElement(_element));
			for (var i in _orginial) {
				if (typeof _orginial[i] != "function") {
					_this[i] = _orginial[i]; //properties
				};
			};
		} catch(e) {
			//silent
		};
		_this.selectedText = (getElement(_element).selectedIndex >= 0) ? getElement(_element).options[getElement(_element).selectedIndex].text : "";
		_this.version = msBeautify.version.msDropdown;
		_this.author = msBeautify.author;
	};
	var _getDataAndUIByOption = function (opt) {
		if (opt != null && typeof opt != "undefined") {
			var childid = _getPostID("postChildID");
			var data = _parseOption(opt);
			var ui = $("#" + childid + " li." + _styles_i.li + ":eq(" + (opt.index) + ")");
			return {data: data, ui: ui, option: opt, index: opt.index};
		};
		return null;
	};
	var _getDataAndUI = function () {
		var childid = _getPostID("postChildID");
		var ele = getElement(_element);
		var data, ui, option, index;
		if (ele.selectedIndex == -1) {
			data = null;
			ui = null;
			option = null;
			index = -1;
		} else {
			ui = $("#" + childid + " li." + _styles.selected);
			if (ui.length > 1) {
				var d = [], op = [], ind = [];
				for (var i = 0; i < ui.length; i++) {
					var pd = _getIndex(ui[i]);
					d.push(pd);
					op.push(ele.options[pd]);
				};
				data = d;
				option = op;
				index = d;
			} else {
				option = ele.options[ele.selectedIndex];
				data = _parseOption(option);
				index = ele.selectedIndex;
			};
		};
		return {data: data, ui: ui, index: index, option: option};
	};
	var _updateTitleUI = function (index, byvalue) {
		var titleid = _getPostID("postTitleID");
		var value = {};
		if (index == -1) {
			value.text = "&nbsp;";
			value.className = "";
			value.description = "";
			value.image = "";
		} else if (typeof index != "undefined") {
			var opt = getElement(_element).options[index];
			value = _parseOption(opt);
		} else {
			value = byvalue;
		};
		//update title and current
		$("#" + titleid).find("." + _styles.label).html(value.text);
		getElement(titleid).className = _styles.ddTitleText + " " + value.className;
		//update desction
		if (value.description != "") {
			$("#" + titleid).find("." + _styles.description).html(value.description).show();
		} else {
			$("#" + titleid).find("." + _styles.description).html("").hide();
		};
		//update icon
		var img = $("#" + titleid).find("img");
		if (img.length > 0) {
			$(img).remove();
		};
		if (value.image != "" && _settings.showIcon) {
			img = _createElement("img", {src: value.image});
			$("#" + titleid).prepend(img);
			if(value.imagecss!="") {
				img.className = value.imagecss+" ";
			};
			if (value.description == "") {
				img.className = img.className+_styles_i.fnone;
			};
		};
	};
	var _updateProp = function (p, v) {
		_this[p] = v;
	};
	var _updateUI = function (a, opt, i) { //action, index, opt
		var childid = _getPostID("postChildID");
		var wasSelected = false;
		switch (a) {
			case "add":
				var li = _createChild(opt || getElement(_element).options[i]);
				var index;
				if (arguments.length == 3) {
					index = i;
				} else {
					index = $("#" + childid + " li." + _styles_i.li).length - 1;
				};
				if (index < 0 || !index) {
					$("#" + childid + " ul").append(li);
				} else {
					var at = $("#" + childid + " li." + _styles_i.li)[index];
					$(at).before(li);
				};
				_removeChildEvents();
				_applyChildEvents();
				if (_settings.on.add != null) {
					_settings.on.add.apply(this, arguments);
				};
				break;
			case "remove":
				wasSelected = $($("#" + childid + " li." + _styles_i.li)[i]).hasClass(_styles.selected);
				$("#" + childid + " li." + _styles_i.li + ":eq(" + i + ")").remove();
				var items = $("#" + childid + " li." + _styles.enabled);
				if (wasSelected == true) {
					if (items.length > 0) {
						$(items[0]).addClass(_styles.selected);
						var ind = $("#" + childid + " li." + _styles_i.li).index(items[0]);
						_setValue(ind);
					};
				};
				if (items.length == 0) {
					_setValue(-1);
				};
				if ($("#" + childid + " li." + _styles_i.li).length < _settings.visibleRows && !_isList) {
					_childHeight(-1); //set autoheight
				};
				if (_settings.on.remove != null) {
					_settings.on.remove.apply(this, arguments);
				};
				break;
		};
	};
	/************************** public methods/events **********************/
	this.act = function () {
		var action = arguments[0];
		Array.prototype.shift.call(arguments);
		switch (action) {
			case "add":
				_this.add.apply(this, arguments);
				break;
			case "remove":
				_this.remove.apply(this, arguments);
				break;
			default:
				try {
					getElement(_element)[action].apply(getElement(_element), arguments);
				} catch (e) {
					//there is some error.
				};
				break;
		};
	};

	this.add = function () {
		var text, value, title, image, description;
		var obj = arguments[0];
		if (typeof obj == "string") {
			text = obj;
			value = text;
			opt = new Option(text, value);
		} else {
			text = obj.text || '';
			value = obj.value || text;
			title = obj.title || '';
			image = obj.image || '';
			description = obj.description || '';
			//image:imagePath, title:title, description:description, value:opt.value, text:opt.text, className:opt.className||""
			opt = new Option(text, value);
			$(opt).data("description", description);
			$(opt).data("image", image);
			$(opt).data("title", title);
		};
		arguments[0] = opt; //this option
		getElement(_element).add.apply(getElement(_element), arguments);
		_updateProp("children", getElement(_element)["children"]);
		_updateProp("length", getElement(_element).length);
		_updateUI("add", opt, arguments[1]);
	};
	this.remove = function (i) {
		getElement(_element).remove(i);
		_updateProp("children", getElement(_element)["children"]);
		_updateProp("length", getElement(_element).length);
		_updateUI("remove", undefined, i);
	};
	this.set = function (prop, val) {
		if (typeof prop == "undefined" || typeof val == "undefined") return false;
		prop = prop.toString();
		try {
			_updateProp(prop, val);
		} catch (e) {/*this is ready only */};

		switch (prop) {
			case "size":
				getElement(_element)[prop] = val;
				if (val == 0) {
					getElement(_element).multiple = false; //if size is zero multiple should be false
				};
				_isList = (getElement(_element).size > 1 || getElement(_element).multiple == true) ? true : false;
				_fixedForList();
				break;
			case "multiple":
				getElement(_element)[prop] = val;
				_isList = (getElement(_element).size > 1 || getElement(_element).multiple == true) ? true : false;
				_isMultiple = getElement(_element).multiple;
				_fixedForList();
				_updateProp(prop, val);
				break;
			case "disabled":
				getElement(_element)[prop] = val;
				_isDisabled = val;
				_fixedForDisabled();
				break;
			case "selectedIndex":
			case "value":
				getElement(_element)[prop] = val;
				var childid = _getPostID("postChildID");
				$("#" + childid + " li." + _styles_i.li).removeClass(_styles.selected);
				$($("#" + childid + " li." + _styles_i.li)[getElement(_element).selectedIndex]).addClass(_styles.selected);
				_setValue(getElement(_element).selectedIndex);
				break;
			case "length":
				var childid = _getPostID("postChildID");
				if (val < getElement(_element).length) {
					getElement(_element)[prop] = val;
					if (val == 0) {
						$("#" + childid + " li." + _styles_i.li).remove();
						_setValue(-1);
					} else {
						$("#" + childid + " li." + _styles_i.li + ":gt(" + (val - 1) + ")").remove();
						if ($("#" + childid + " li." + _styles.selected).length == 0) {
							$("#" + childid + " li." + _styles.enabled + ":eq(0)").addClass(_styles.selected);
						};
					};
					_updateProp(prop, val);
					_updateProp("children", getElement(_element)["children"]);
				};
				break;
			case "id":
				//please i need this. so preventing to change it. will work on this later
				break;
			default:
				//check if this is not a readonly properties
				try {
					getElement(_element)[prop] = val;
					_updateProp(prop, val);
				} catch (e) {
					//silent
				};
				break;
		}
	};
	this.get = function (prop) {
		return _this[prop] || getElement(_element)[prop]; //return if local else from original
	};
	this.visible = function (val) {
		var id = _getPostID("postID");
		if (val === true) {
			$("#" + id).show();
		} else if (val === false) {
			$("#" + id).hide();
		} else {
			return ($("#" + id).css("display")=="none") ? false : true;
		};
	};
	this.debug = function (v) {
		msBeautify.debug(v);
	};
	this.close = function () {
		_close();
	};
	this.open = function () {
		_open();
	};
	this.showRows = function (r) {
		if (typeof r == "undefined" || r == 0) {
			return false;
		};
		_settings.visibleRows = r;
		_childHeight(_childHeight());
	};
	this.visibleRows = this.showRows;
	this.on = function (type, fn) {
		$("#" + _element).on(type, fn);
	};
	this.off = function (type, fn) {
		$("#" + _element).off(type, fn);
	};
	this.addMyEvent = this.on;
	this.getData = function () {
		return _getDataAndUI()
	};
	this.namedItem = function () {
		var opt = getElement(_element).namedItem.apply(getElement(_element), arguments);
		return _getDataAndUIByOption(opt);
	};
	this.item = function () {
		var opt = getElement(_element).item.apply(getElement(_element), arguments);
		return _getDataAndUIByOption(opt);
	};
	//v 3.2
	this.setIndexByValue = function(val) {
		this.set("value", val);
	};
	this.destroy = function () {
		var hidid = _getPostID("postElementHolder");
		var id = _getPostID("postID");
		$("#" + id + ", #" + id + " *").off();
		$("#" + id).remove();
		$("#" + _element).parent().replaceWith($("#" + _element));
		$("#" + _element).data("dd", null);
	};
	//Create msDropDown
	_construct();
};
//bind in jquery
$.fn.extend({
			msDropDown: function(settings)
			{
				return this.each(function()
				{
					if (!$(this).data('dd')){
						var mydropdown = new dd(this, settings);
						$(this).data('dd', mydropdown);
					};
				});
			}
});
$.fn.msDropdown = $.fn.msDropDown; //make a copy
})(jQuery);(function($) {
    $.fn.selectChain = function (options) {
        var defaults = {
            key: "id",
            value: "label",
            class_s: "class"
        };


        var settings = $.extend({}, defaults, options);

        if (!(settings.target instanceof $)) settings.target = $(settings.target);

        return this.each(function () {
            var $$ = $(this);
            $$.change(function () {
                var data = null;
                if (typeof settings.data == 'string') {
                    data = settings.data + '&' + this.name + '=' + $$.val();
                } else if (typeof settings.data == 'object') {
                    data = settings.data;
                    data[this.name] = $$.val();
                    //alert(escape(data[this.name]));
                }

				settings.target.empty();

                $.ajax({
                    url: settings.url,
                    data: data,
                    type: (settings.type || 'get'),
                    dataType: 'json',
                    success: function (j) {
                    	//alert(j);
                        var options = [], i = 0, o = null;
                        for (i = 0; i < j.length; i++) {
                            // required to get around IE bug (http://support.microsoft.com/?scid=kb%3Ben-us%3B276228)
                            o = document.createElement("OPTION");
                            o.value = typeof j[i] == 'object' ? j[i][settings.key] : j[i];
                            o.text = typeof j[i] == 'object' ? j[i][settings.value] : j[i];
                            if (j[i][settings.class_s] !== undefined) {
                            	o.className = typeof j[i] == 'object' ? j[i][settings.class_s] : j[i];
                            }
                            settings.target.get(0).options[i] = o;
                        }

						// hand control back to browser for a moment
						setTimeout(function () {
                        	if (settings.valselect != '' && settings.valselect !== undefined) {
                        		//alert(settings.valselect);
                            	$("[value='"+settings.valselect+"']",settings.target).attr('selected', 'selected').parent('select').trigger('change');
                        	} else {
								settings.target.find('option:first').attr('selected', 'selected').parent('select').trigger('change');
						    }
						}, 0);
                    },
                    error: function (xhr, desc, er) {
                        // add whatever debug you want here.
						//alert(settings.url);
                    }
                });

            });
        });
    };
})(jQuery);function selectURL() {
    var modification = jQuery('#modification :selected').val();
	var autoname = jQuery('#vendor :selected').text();
	var modelsname = jQuery('#model :selected').text();
	var yearname = jQuery('#year :selected').text();

	var select_url = '/diski/car-' + autoname + '-' + modelsname + '-' + yearname + '-' + modification;
	window.location = select_url;
	//alert(select_url);
}
(function($){
	$.fn.slides = function( option ) {
		// override defaults with specified option
		option = $.extend( {}, $.fn.slides.option, option );

		return this.each(function(){
			// wrap slides in control container, make sure slides are block level
			$('.' + option.container, $(this)).children().wrapAll('<div class="slides_control"/>');

			var elem = $(this),
				control = $('.slides_control',elem),
				total = control.children().size(),
				width = control.children().outerWidth(),
				height = control.children().outerHeight(),
				start = option.start - 1,
				effect = option.effect.indexOf(',') < 0 ? option.effect : option.effect.replace(' ', '').split(',')[0],
				paginationEffect = option.effect.indexOf(',') < 0 ? effect : option.effect.replace(' ', '').split(',')[1],
				next = 0, prev = 0, number = 0, current = 0, loaded, active, clicked, position, direction, imageParent, pauseTimeout, playInterval;

			// is there only one slide?

			if (total < 2) {
				// Fade in .slides_container
				$('.' + option.container, $(this)).fadeIn(option.fadeSpeed, option.fadeEasing, function(){
					// let the script know everything is loaded
					loaded = true;
					// call the loaded funciton
					option.slidesLoaded();
				});
				// Hide the next/previous buttons
				$('.' + option.next + ', .' + option.prev).fadeOut(0);
				return false;
			}

			// animate slides
			function animate(direction, effect, clicked) {
				if (!active && loaded) {
					active = true;
					// start of animation
					option.animationStart(current + 1);
					switch(direction) {
						case 'next':
							// change current slide to previous
							prev = current;
							// get next from current + 1
							next = current + 1;
							// if last slide, set next to first slide
							next = total === next ? 0 : next;
							// set position of next slide to right of previous
							position = width*2;
							// distance to slide based on width of slides
							direction = -width*2;
							// store new current slide
							current = next;
						break;
						case 'prev':
							// change current slide to previous
							prev = current;
							// get next from current - 1
							next = current - 1;
							// if first slide, set next to last slide
							next = next === -1 ? total-1 : next;
							// set position of next slide to left of previous
							position = 0;
							// distance to slide based on width of slides
							direction = 0;
							// store new current slide
							current = next;
						break;
						case 'pagination':
							// get next from pagination item clicked, convert to number
							next = parseInt(clicked,10);
							// get previous from pagination item with class of current
							prev = $('.' + option.paginationClass + ' li.'+ option.currentClass +' a', elem).attr('href').match('[^#/]+$');
							// if next is greater then previous set position of next slide to right of previous
							if (next > prev) {
								position = width*2;
								direction = -width*2;
							} else {
							// if next is less then previous set position of next slide to left of previous
								position = 0;
								direction = 0;
							}
							// store new current slide
							current = next;
						break;
					}

					// fade animation
					if (effect === 'fade') {
						// fade animation with crossfade
						if (option.crossfade) {
							// put hidden next above current
							control.children(':eq('+ next +')', elem).css({
								zIndex: 10
							// fade in next
							}).fadeIn(option.fadeSpeed, option.fadeEasing, function(){
								if (option.autoHeight) {
									// animate container to height of next
									control.animate({
										height: control.children(':eq('+ next +')', elem).outerHeight()
									}, option.autoHeightSpeed, function(){
										// hide previous
										control.children(':eq('+ prev +')', elem).css({
											display: 'none',
											zIndex: 0
										});
										// reset z index
										control.children(':eq('+ next +')', elem).css({
											zIndex: 0
										});
										// end of animation
										option.animationComplete(next + 1);
										active = false;
									});
								} else {
									// hide previous
									control.children(':eq('+ prev +')', elem).css({
										display: 'none',
										zIndex: 0
									});
									// reset zindex
									control.children(':eq('+ next +')', elem).css({
										zIndex: 0
									});
									// end of animation
									option.animationComplete(next + 1);
									active = false;
								}
							});
						} else {
							// fade animation with no crossfade
							control.children(':eq('+ prev +')', elem).fadeOut(option.fadeSpeed,  option.fadeEasing, function(){
								// animate to new height
								if (option.autoHeight) {
									control.animate({
										// animate container to height of next
										height: control.children(':eq('+ next +')', elem).outerHeight()
									}, option.autoHeightSpeed,
									// fade in next slide
									function(){
										control.children(':eq('+ next +')', elem).fadeIn(option.fadeSpeed, option.fadeEasing);
									});
								} else {
								// if fixed height
									control.children(':eq('+ next +')', elem).fadeIn(option.fadeSpeed, option.fadeEasing, function(){
										// fix font rendering in ie, lame
										if($.browser.msie) {
											$(this).get(0).style.removeAttribute('filter');
										}
									});
								}
								// end of animation
								option.animationComplete(next + 1);
								active = false;
							});
						}
					// slide animation
					} else {
						// move next slide to right of previous
						control.children(':eq('+ next +')').css({
							left: position,
							display: 'block'
						});
						// animate to new height
						if (option.autoHeight) {
							control.animate({
								left: direction,
								height: control.children(':eq('+ next +')').outerHeight()
							},option.slideSpeed, option.slideEasing, function(){
								control.css({
									left: -width
								});
								control.children(':eq('+ next +')').css({
									left: width,
									zIndex: 5
								});
								// reset previous slide
								control.children(':eq('+ prev +')').css({
									left: width,
									display: 'none',
									zIndex: 0
								});
								// end of animation
								option.animationComplete(next + 1);
								active = false;
							});
							// if fixed height
							} else {
								// animate control
								control.animate({
									left: direction
								},option.slideSpeed, option.slideEasing, function(){
									// after animation reset control position
									control.css({
										left: -width
									});
									// reset and show next
									control.children(':eq('+ next +')').css({
										left: width,
										zIndex: 5
									});
									// reset previous slide
									control.children(':eq('+ prev +')').css({
										left: width,
										display: 'none',
										zIndex: 0
									});
									// end of animation
									option.animationComplete(next + 1);
									active = false;
								});
							}
						}
					// set current state for pagination
					if (option.pagination) {
						// remove current class from all
						$('.'+ option.paginationClass +' li.' + option.currentClass, elem).removeClass(option.currentClass);
						// add current class to next
						$('.' + option.paginationClass + ' li:eq('+ next +')', elem).addClass(option.currentClass);
					}
				}
			} // end animate function

			function stop() {
				// clear interval from stored id
				clearInterval(elem.data('interval'));
			}

			function pause() {
				if (option.pause) {
					// clear timeout and interval
					clearTimeout(elem.data('pause'));
					clearInterval(elem.data('interval'));
					// pause slide show for option.pause amount
					pauseTimeout = setTimeout(function() {
						// clear pause timeout
						clearTimeout(elem.data('pause'));
						// start play interval after pause
						playInterval = setInterval(	function(){
							animate("next", effect);
						},option.play);
						// store play interval
						elem.data('interval',playInterval);
					},option.pause);
					// store pause interval
					elem.data('pause',pauseTimeout);
				} else {
					// if no pause, just stop
					stop();
				}
			}

			// 2 or more slides required
			if (total < 2) {
				//return;
			}

			// error corection for start slide
			if (start < 0) {
				start = 0;
			}

			if (start > total) {
				start = total - 1;
			}

			// change current based on start option number
			if (option.start) {
				current = start;
			}

			// randomizes slide order
			if (option.randomize) {
				control.randomize();
			}

			// make sure overflow is hidden, width is set
			$('.' + option.container, elem).css({
				overflow: 'hidden',
				// fix for ie
				position: 'relative'
			});

			// set css for slides
			control.children().css({
				position: 'absolute',
				top: 0,
				left: control.children().outerWidth(),
				zIndex: 0,
				display: 'none'
			 });

			// set css for control div
			control.css({
				position: 'relative',
				// size of control 3 x slide width
				width: (width * 3),
				// set height to slide height
				height: height,
				// center control to slide
				left: -width
			});

			// show slides
			$('.' + option.container, elem).css({
				display: 'block'
			});

			// if autoHeight true, get and set height of first slide
			if (option.autoHeight) {
				control.children().css({
					height: 'auto'
				});
				control.animate({
					height: control.children(':eq('+ start +')').outerHeight()
				},option.autoHeightSpeed);
			}

			// checks if image is loaded
			if (option.preload && control.find('img:eq(' + start + ')').length) {
				// adds preload image
				$('.' + option.container, elem).css({
					background: 'url(' + option.preloadImage + ') no-repeat 50% 50%'
				});

				// gets image src, with cache buster
				var img = control.find('img:eq(' + start + ')').attr('src') + '?' + (new Date()).getTime();

				// check if the image has a parent
				if ($('img', elem).parent().attr('class') != 'slides_control') {
					// If image has parent, get tag name
					imageParent = control.children(':eq(0)')[0].tagName.toLowerCase();
				} else {
					// Image doesn't have parent, use image tag name
					imageParent = control.find('img:eq(' + start + ')');
				}

				// checks if image is loaded
				control.find('img:eq(' + start + ')').attr('src', img).load(function() {
					// once image is fully loaded, fade in
					control.find(imageParent + ':eq(' + start + ')').fadeIn(option.fadeSpeed, option.fadeEasing, function(){
						$(this).css({
							zIndex: 5
						});
						// removes preload image
						$('.' + option.container, elem).css({
							background: ''
						});
						// let the script know everything is loaded
						loaded = true;
						// call the loaded funciton
						option.slidesLoaded();
					});
				});
			} else {
				// if no preloader fade in start slide
				control.children(':eq(' + start + ')').fadeIn(option.fadeSpeed, option.fadeEasing, function(){
					// let the script know everything is loaded
					loaded = true;
					// call the loaded funciton
					option.slidesLoaded();
				});
			}

			// click slide for next
			if (option.bigTarget) {
				// set cursor to pointer
				control.children().css({
					cursor: 'pointer'
				});
				// click handler
				control.children().click(function(){
					// animate to next on slide click
					animate('next', effect);
					return false;
				});
			}

			// pause on mouseover
			if (option.hoverPause && option.play) {
				control.bind('mouseover',function(){
					// on mouse over stop
					stop();
				});
				control.bind('mouseleave',function(){
					// on mouse leave start pause timeout
					pause();
				});
			}

			// generate next/prev buttons
			if (option.generateNextPrev) {
				$('.' + option.container, elem).after('<a href="#" class="'+ option.prev +'"></a>');
				$('.' + option.prev, elem).after('<a href="#" class="'+ option.next +'"></a>');
			}

			// next button
			$('.' + option.next ,elem).click(function(e){
				e.preventDefault();
				if (option.play) {
					pause();
				}
				animate('next', effect);
			});

			// previous button
			$('.' + option.prev, elem).click(function(e){
				e.preventDefault();
				if (option.play) {
					 pause();
				}
				animate('prev', effect);
			});

			// generate pagination
			if (option.generatePagination) {
				// create unordered list
				if (option.prependPagination) {
					elem.prepend('<div id="'+ option.slidepaginationClass +'"><ul class='+ option.paginationClass +'></ul></div>');
				} else {
					elem.append('<div id="'+ option.slidepaginationClass +'"><ul class='+ option.paginationClass +'></ul></div>');
				}
				// for each slide create a list item and link
				control.children().each(function(){
					$('.' + option.paginationClass, elem).append('<li><a href="#'+ number +'">'+ (number+1) +'</a></li>');
					number++;
				});
			} else {
				// if pagination exists, add href w/ value of item number to links
				$('.' + option.paginationClass + ' li a', elem).each(function(){
					$(this).attr('href', '#' + number);
					number++;
				});
			}

			// add current class to start slide pagination
			$('.' + option.paginationClass + ' li:eq('+ start +')', elem).addClass(option.currentClass);

			// click handling
			$('.' + option.paginationClass + ' li a', elem ).click(function(){
				// pause slideshow
				if (option.play) {
					 pause();
				}
				// get clicked, pass to animate function
				clicked = $(this).attr('href').match('[^#/]+$');
				// if current slide equals clicked, don't do anything
				if (current != clicked) {
					animate('pagination', paginationEffect, clicked);
				}
				return false;
			});

			// click handling
			$('a.link', elem).click(function(){
				// pause slideshow
				if (option.play) {
					 pause();
				}
				// get clicked, pass to animate function
				clicked = $(this).attr('href').match('[^#/]+$') - 1;
				// if current slide equals clicked, don't do anything
				if (current != clicked) {
					animate('pagination', paginationEffect, clicked);
				}
				return false;
			});

			if (option.play) {
				// set interval
				playInterval = setInterval(function() {
					animate('next', effect);
				}, option.play);
				// store interval id
				elem.data('interval',playInterval);
			}
		});
	};

	// default options
	$.fn.slides.option = {
		preload: false, // boolean, Set true to preload images in an image based slideshow
		preloadImage: '/img/loading.gif', // string, Name and location of loading image for preloader. Default is "/img/loading.gif"
		container: 'slides_container', // string, Class name for slides container. Default is "slides_container"
		generateNextPrev: false, // boolean, Auto generate next/prev buttons
		next: 'slidenext', // string, Class name for next button
		prev: 'slideprev', // string, Class name for previous button
		pagination: true, // boolean, If you're not using pagination you can set to false, but don't have to
		generatePagination: true, // boolean, Auto generate pagination
		prependPagination: false, // boolean, prepend pagination
		paginationClass: 'pagination-slider', // string, Class name for pagination
		slidepaginationClass: 'slidepaginationimage', // string, Class name for pagination
		currentClass: 'current', // string, Class name for current class
		fadeSpeed: 350, // number, Set the speed of the fading animation in milliseconds
		fadeEasing: '', // string, must load jQuery's easing plugin before http://gsgd.co.uk/sandbox/jquery/easing/
		slideSpeed: 350, // number, Set the speed of the sliding animation in milliseconds
		slideEasing: '', // string, must load jQuery's easing plugin before http://gsry/easing/gd.co.uk/sandbox/jque
		start: 1, // number, Set the speed of the sliding animation in milliseconds
		effect: 'slide', // string, '[next/prev], [pagination]', e.g. 'slide, fade' or simply 'fade' for both
		crossfade: false, // boolean, Crossfade images in a image based slideshow
		randomize: false, // boolean, Set to true to randomize slides
		play: 0, // number, Autoplay slideshow, a positive number will set to true and be the time between slide animation in milliseconds
		pause: 0, // number, Pause slideshow on click of next/prev or pagination. A positive number will set to true and be the time of pause in milliseconds
		hoverPause: false, // boolean, Set to true and hovering over slideshow will pause it
		autoHeight: false, // boolean, Set to true to auto adjust height
		autoHeightSpeed: 350, // number, Set auto height animation time in milliseconds
		bigTarget: false, // boolean, Set to true and the whole slide will link to next slide on click
		animationStart: function(){}, // Function called at the start of animation
		animationComplete: function(){}, // Function called at the completion of animation
		slidesLoaded: function() {} // Function is called when slides is fully loaded
	};

	// Randomize slide order on load
	$.fn.randomize = function(callback) {
		function randomizeOrder() { return(Math.round(Math.random())-0.5); }
			return($(this).each(function() {
			var $this = $(this);
			var $children = $this.children();
			var childCount = $children.length;
			if (childCount > 1) {
				$children.hide();
				var indices = [];
				for (i=0;i<childCount;i++) { indices[indices.length] = i; }
				indices = indices.sort(randomizeOrder);
				$.each(indices,function(j,k) {
					var $child = $children.eq(k);
					var $clone = $child.clone(true);
					$clone.show().appendTo($this);
					if (callback !== undefined) {
						callback($child, $clone);
					}
				$child.remove();
			});
			}
		}));
	};
})(jQuery);/*!
 * jCarousel - Riding carousels with jQuery
 *   http://sorgalla.com/jcarousel/
 *
 * Copyright (c) 2006 Jan Sorgalla (http://sorgalla.com)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Built on top of the jQuery library
 *   http://jquery.com
 *
 * Inspired by the "Carousel Component" by Bill Scott
 *   http://billwscott.com/carousel/
 */

(function($) {
    /**
     * Creates a carousel for all matched elements.
     *
     * @example $("#mycarousel").jcarousel();
     * @before <ul id="mycarousel" class="jcarousel-skin-name"><li>First item</li><li>Second item</li></ul>
     * @result
     *
     * <div class="jcarousel-skin-name">
     *   <div class="jcarousel-container">
     *     <div class="jcarousel-clip">
     *       <ul class="jcarousel-list">
     *         <li class="jcarousel-item-1">First item</li>
     *         <li class="jcarousel-item-2">Second item</li>
     *       </ul>
     *     </div>
     *     <div disabled="disabled" class="jcarousel-prev jcarousel-prev-disabled"></div>
     *     <div class="jcarousel-next"></div>
     *   </div>
     * </div>
     *
     * @method jcarousel
     * @return jQuery
     * @param o {Hash|String} A set of key/value pairs to set as configuration properties or a method name to call on a formerly created instance.
     */
    $.fn.jcarousel = function(o) {
        if (typeof o == 'string') {
            var instance = $(this).data('jcarousel'), args = Array.prototype.slice.call(arguments, 1);
            return instance[o].apply(instance, args);
        } else
            return this.each(function() {
                $(this).data('jcarousel', new $jc(this, o));
            });
    };

    // Default configuration properties.
    var defaults = {
        vertical: false,
        start: 1,
        offset: 1,
        size: null,
        scroll: 3,
        visible: null,
        animation: 'normal',
        easing: 'swing',
        auto: 0,
        wrap: null,
        initCallback: null,
        reloadCallback: null,
        itemLoadCallback: null,
        itemFirstInCallback: null,
        itemFirstOutCallback: null,
        itemLastInCallback: null,
        itemLastOutCallback: null,
        itemVisibleInCallback: null,
        itemVisibleOutCallback: null,
        buttonNextHTML: '<div></div>',
        buttonPrevHTML: '<div></div>',
        buttonNextEvent: 'click',
        buttonPrevEvent: 'click',
        buttonNextCallback: null,
        buttonPrevCallback: null
    };

    /**
     * The jCarousel object.
     *
     * @constructor
     * @class jcarousel
     * @param e {HTMLElement} The element to create the carousel for.
     * @param o {Object} A set of key/value pairs to set as configuration properties.
     * @cat Plugins/jCarousel
     */
    $.jcarousel = function(e, o) {
        this.options    = $.extend({}, defaults, o || {});

        this.locked     = false;

        this.container  = null;
        this.clip       = null;
        this.list       = null;
        this.buttonNext = null;
        this.buttonPrev = null;

        this.wh = !this.options.vertical ? 'width' : 'height';
        this.lt = !this.options.vertical ? 'left' : 'top';

        // Extract skin class
        var skin = '', split = e.className.split(' ');

        for (var i = 0; i < split.length; i++) {
            if (split[i].indexOf('jcarousel-skin') != -1) {
                $(e).removeClass(split[i]);
                skin = split[i];
                break;
            }
        }

        if (e.nodeName == 'UL' || e.nodeName == 'OL') {
            this.list = $(e);
            this.container = this.list.parent();

            if (this.container.hasClass('jcarousel-clip')) {
                if (!this.container.parent().hasClass('jcarousel-container'))
                    this.container = this.container.wrap('<div></div>');

                this.container = this.container.parent();
            } else if (!this.container.hasClass('jcarousel-container'))
                this.container = this.list.wrap('<div></div>').parent();
        } else {
            this.container = $(e);
            this.list = this.container.find('ul,ol').eq(0);
        }

        if (skin != '' && this.container.parent()[0].className.indexOf('jcarousel-skin') == -1)
            this.container.wrap('<div class=" '+ skin + '"></div>');

        this.clip = this.list.parent();

        if (!this.clip.length || !this.clip.hasClass('jcarousel-clip'))
            this.clip = this.list.wrap('<div></div>').parent();

        this.buttonNext = $('.jcarousel-next', this.container);

        if (this.buttonNext.size() == 0 && this.options.buttonNextHTML != null)
            this.buttonNext = this.clip.after(this.options.buttonNextHTML).next();

        this.buttonNext.addClass(this.className('jcarousel-next'));

        this.buttonPrev = $('.jcarousel-prev', this.container);

        if (this.buttonPrev.size() == 0 && this.options.buttonPrevHTML != null)
            this.buttonPrev = this.clip.after(this.options.buttonPrevHTML).next();

        this.buttonPrev.addClass(this.className('jcarousel-prev'));

        this.clip.addClass(this.className('jcarousel-clip')).css({
            overflow: 'hidden',
            position: 'relative'
        });
        this.list.addClass(this.className('jcarousel-list')).css({
            overflow: 'hidden',
            position: 'relative',
            top: 0,
            left: 0,
            margin: 0,
            padding: 0
        });
        this.container.addClass(this.className('jcarousel-container')).css({
            position: 'relative'
        });

        var di = this.options.visible != null ? Math.ceil(this.clipping() / this.options.visible) : null;
        var li = this.list.children('li');

        var self = this;

        if (li.size() > 0) {
            var wh = 0, i = this.options.offset;
            li.each(function() {
                self.format(this, i++);
                wh += self.dimension(this, di);
            });

            this.list.css(this.wh, wh + 'px');

            // Only set if not explicitly passed as option
            if (!o || o.size === undefined)
                this.options.size = li.size();
        }

        // For whatever reason, .show() does not work in Safari...
        this.container.css('display', 'block');
        this.buttonNext.css('display', 'block');
        this.buttonPrev.css('display', 'block');

        this.funcNext   = function() { self.next(); };
        this.funcPrev   = function() { self.prev(); };
        this.funcResize = function() { self.reload(); };

        if (this.options.initCallback != null)
            this.options.initCallback(this, 'init');

        if ($.browser.safari) {
            this.buttons(false, false);
            $(window).bind('load.jcarousel', function() { self.setup(); });
        } else
            this.setup();
    };

    // Create shortcut for internal use
    var $jc = $.jcarousel;

    $jc.fn = $jc.prototype = {
        jcarousel: '0.2.4'
    };

    $jc.fn.extend = $jc.extend = $.extend;

    $jc.fn.extend({
        /**
         * Setups the carousel.
         *
         * @method setup
         * @return undefined
         */
        setup: function() {
            this.first     = null;
            this.last      = null;
            this.prevFirst = null;
            this.prevLast  = null;
            this.animating = false;
            this.timer     = null;
            this.tail      = null;
            this.inTail    = false;

            if (this.locked)
                return;

            this.list.css(this.lt, this.pos(this.options.offset) + 'px');
            var p = this.pos(this.options.start);
            this.prevFirst = this.prevLast = null;
            this.animate(p, false);

            $(window).unbind('resize.jcarousel', this.funcResize).bind('resize.jcarousel', this.funcResize);
        },

        /**
         * Clears the list and resets the carousel.
         *
         * @method reset
         * @return undefined
         */
        reset: function() {
            this.list.empty();

            this.list.css(this.lt, '0px');
            this.list.css(this.wh, '10px');

            if (this.options.initCallback != null)
                this.options.initCallback(this, 'reset');

            this.setup();
        },

        /**
         * Reloads the carousel and adjusts positions.
         *
         * @method reload
         * @return undefined
         */
        reload: function() {
            if (this.tail != null && this.inTail)
                this.list.css(this.lt, $jc.intval(this.list.css(this.lt)) + this.tail);

            this.tail   = null;
            this.inTail = false;

            if (this.options.reloadCallback != null)
                this.options.reloadCallback(this);

            if (this.options.visible != null) {
                var self = this;
                var di = Math.ceil(this.clipping() / this.options.visible), wh = 0, lt = 0;
                $('li', this.list).each(function(i) {
                    wh += self.dimension(this, di);
                    if (i + 1 < self.first)
                        lt = wh;
                });

                this.list.css(this.wh, wh + 'px');
                this.list.css(this.lt, -lt + 'px');
            }

            this.scroll(this.first, false);
        },

        /**
         * Locks the carousel.
         *
         * @method lock
         * @return undefined
         */
        lock: function() {
            this.locked = true;
            this.buttons();
        },

        /**
         * Unlocks the carousel.
         *
         * @method unlock
         * @return undefined
         */
        unlock: function() {
            this.locked = false;
            this.buttons();
        },

        /**
         * Sets the size of the carousel.
         *
         * @method size
         * @return undefined
         * @param s {Number} The size of the carousel.
         */
        size: function(s) {
            if (s != undefined) {
                this.options.size = s;
                if (!this.locked)
                    this.buttons();
            }

            return this.options.size;
        },

        /**
         * Checks whether a list element exists for the given index (or index range).
         *
         * @method get
         * @return bool
         * @param i {Number} The index of the (first) element.
         * @param i2 {Number} The index of the last element.
         */
        has: function(i, i2) {
            if (i2 == undefined || !i2)
                i2 = i;

            if (this.options.size !== null && i2 > this.options.size)
                i2 = this.options.size;

            for (var j = i; j <= i2; j++) {
                var e = this.get(j);
                if (!e.length || e.hasClass('jcarousel-item-placeholder'))
                    return false;
            }

            return true;
        },

        /**
         * Returns a jQuery object with list element for the given index.
         *
         * @method get
         * @return jQuery
         * @param i {Number} The index of the element.
         */
        get: function(i) {
            return $('.jcarousel-item-' + i, this.list);
        },

        /**
         * Adds an element for the given index to the list.
         * If the element already exists, it updates the inner html.
         * Returns the created element as jQuery object.
         *
         * @method add
         * @return jQuery
         * @param i {Number} The index of the element.
         * @param s {String} The innerHTML of the element.
         */
        add: function(i, s) {
            var e = this.get(i), old = 0, add = 0;

            if (e.length == 0) {
                var c, e = this.create(i), j = $jc.intval(i);
                while (c = this.get(--j)) {
                    if (j <= 0 || c.length) {
                        j <= 0 ? this.list.prepend(e) : c.after(e);
                        break;
                    }
                }
            } else
                old = this.dimension(e);

            e.removeClass(this.className('jcarousel-item-placeholder'));
            typeof s == 'string' ? e.html(s) : e.empty().append(s);

            var di = this.options.visible != null ? Math.ceil(this.clipping() / this.options.visible) : null;
            var wh = this.dimension(e, di) - old;

            if (i > 0 && i < this.first)
                this.list.css(this.lt, $jc.intval(this.list.css(this.lt)) - wh + 'px');

            this.list.css(this.wh, $jc.intval(this.list.css(this.wh)) + wh + 'px');

            return e;
        },

        /**
         * Removes an element for the given index from the list.
         *
         * @method remove
         * @return undefined
         * @param i {Number} The index of the element.
         */
        remove: function(i) {
            var e = this.get(i);

            // Check if item exists and is not currently visible
            if (!e.length || (i >= this.first && i <= this.last))
                return;

            var d = this.dimension(e);

            if (i < this.first)
                this.list.css(this.lt, $jc.intval(this.list.css(this.lt)) + d + 'px');

            e.remove();

            this.list.css(this.wh, $jc.intval(this.list.css(this.wh)) - d + 'px');
        },

        /**
         * Moves the carousel forwards.
         *
         * @method next
         * @return undefined
         */
        next: function() {
            this.stopAuto();

            if (this.tail != null && !this.inTail)
                this.scrollTail(false);
            else
                this.scroll(((this.options.wrap == 'both' || this.options.wrap == 'last') && this.options.size != null && this.last == this.options.size) ? 1 : this.first + this.options.scroll);
        },

        /**
         * Moves the carousel backwards.
         *
         * @method prev
         * @return undefined
         */
        prev: function() {
            this.stopAuto();

            if (this.tail != null && this.inTail)
                this.scrollTail(true);
            else
                this.scroll(((this.options.wrap == 'both' || this.options.wrap == 'first') && this.options.size != null && this.first == 1) ? this.options.size : this.first - this.options.scroll);
        },

        /**
         * Scrolls the tail of the carousel.
         *
         * @method scrollTail
         * @return undefined
         * @param b {Boolean} Whether scroll the tail back or forward.
         */
        scrollTail: function(b) {
            if (this.locked || this.animating || !this.tail)
                return;

            var pos  = $jc.intval(this.list.css(this.lt));

            !b ? pos -= this.tail : pos += this.tail;
            this.inTail = !b;

            // Save for callbacks
            this.prevFirst = this.first;
            this.prevLast  = this.last;

            this.animate(pos);
        },

        /**
         * Scrolls the carousel to a certain position.
         *
         * @method scroll
         * @return undefined
         * @param i {Number} The index of the element to scoll to.
         * @param a {Boolean} Flag indicating whether to perform animation.
         */
        scroll: function(i, a) {
            if (this.locked || this.animating)
                return;

            this.animate(this.pos(i), a);
        },

        /**
         * Prepares the carousel and return the position for a certian index.
         *
         * @method pos
         * @return {Number}
         * @param i {Number} The index of the element to scoll to.
         */
        pos: function(i) {
            var pos  = $jc.intval(this.list.css(this.lt));

            if (this.locked || this.animating)
                return pos;

            if (this.options.wrap != 'circular')
                i = i < 1 ? 1 : (this.options.size && i > this.options.size ? this.options.size : i);

            var back = this.first > i;

            // Create placeholders, new list width/height
            // and new list position
            var f = this.options.wrap != 'circular' && this.first <= 1 ? 1 : this.first;
            var c = back ? this.get(f) : this.get(this.last);
            var j = back ? f : f - 1;
            var e = null, l = 0, p = false, d = 0, g;

            while (back ? --j >= i : ++j < i) {
                e = this.get(j);
                p = !e.length;
                if (e.length == 0) {
                    e = this.create(j).addClass(this.className('jcarousel-item-placeholder'));
                    c[back ? 'before' : 'after' ](e);

                    if (this.first != null && this.options.wrap == 'circular' && this.options.size !== null && (j <= 0 || j > this.options.size)) {
                        g = this.get(this.index(j));
                        if (g.length)
                            this.add(j, g.children().clone(true));
                    }
                }

                c = e;
                d = this.dimension(e);

                if (p)
                    l += d;

                if (this.first != null && (this.options.wrap == 'circular' || (j >= 1 && (this.options.size == null || j <= this.options.size))))
                    pos = back ? pos + d : pos - d;
            }

            // Calculate visible items
            var clipping = this.clipping();
            var cache = [];
            var visible = 0, j = i, v = 0;
            var c = this.get(i - 1);

            while (++visible) {
                e = this.get(j);
                p = !e.length;
                if (e.length == 0) {
                    e = this.create(j).addClass(this.className('jcarousel-item-placeholder'));
                    // This should only happen on a next scroll
                    c.length == 0 ? this.list.prepend(e) : c[back ? 'before' : 'after' ](e);

                    if (this.first != null && this.options.wrap == 'circular' && this.options.size !== null && (j <= 0 || j > this.options.size)) {
                        g = this.get(this.index(j));
                        if (g.length)
                            this.add(j, g.find('>*').clone(true));
                    }
                }

                c = e;
                var d = this.dimension(e);
                if (d == 0) {
                    //alert('jCarousel: No width/height set for items. This will cause an infinite loop. Aborting...');      // Commenting BY RUSYA
                    return 0;
                }

                if (this.options.wrap != 'circular' && this.options.size !== null && j > this.options.size)
                    cache.push(e);
                else if (p)
                    l += d;

                v += d;

                if (v >= clipping)
                    break;

                j++;
            }

             // Remove out-of-range placeholders
            for (var x = 0; x < cache.length; x++)
                cache[x].remove();

            // Resize list
            if (l > 0) {
                this.list.css(this.wh, this.dimension(this.list) + l + 'px');

                if (back) {
                    pos -= l;
                    this.list.css(this.lt, $jc.intval(this.list.css(this.lt)) - l + 'px');
                }
            }

            // Calculate first and last item
            var last = i + visible - 1;
            if (this.options.wrap != 'circular' && this.options.size && last > this.options.size)
                last = this.options.size;

            if (j > last) {
                visible = 0, j = last, v = 0;
                while (++visible) {
                    var e = this.get(j--);
                    if (!e.length)
                        break;
                    v += this.dimension(e);
                    if (v >= clipping)
                        break;
                }
            }

            var first = last - visible + 1;
            if (this.options.wrap != 'circular' && first < 1)
                first = 1;

            if (this.inTail && back) {
                pos += this.tail;
                this.inTail = false;
            }

            this.tail = null;
            if (this.options.wrap != 'circular' && last == this.options.size && (last - visible + 1) >= 1) {
                var m = $jc.margin(this.get(last), !this.options.vertical ? 'marginRight' : 'marginBottom');
                if ((v - m) > clipping)
                    this.tail = v - clipping - m;
            }

            // Adjust position
            while (i-- > first)
                pos += this.dimension(this.get(i));

            // Save visible item range
            this.prevFirst = this.first;
            this.prevLast  = this.last;
            this.first     = first;
            this.last      = last;

            return pos;
        },

        /**
         * Animates the carousel to a certain position.
         *
         * @method animate
         * @return undefined
         * @param p {Number} Position to scroll to.
         * @param a {Boolean} Flag indicating whether to perform animation.
         */
        animate: function(p, a) {
            if (this.locked || this.animating)
                return;

            this.animating = true;

            var self = this;
            var scrolled = function() {
                self.animating = false;

                if (p == 0)
                    self.list.css(self.lt,  0);

                if (self.options.wrap == 'circular' || self.options.wrap == 'both' || self.options.wrap == 'last' || self.options.size == null || self.last < self.options.size)
                    self.startAuto();

                self.buttons();
                self.notify('onAfterAnimation');
            };

            this.notify('onBeforeAnimation');

            // Animate
            if (!this.options.animation || a == false) {
                this.list.css(this.lt, p + 'px');
                scrolled();
            } else {
                var o = !this.options.vertical ? {'left': p} : {'top': p};
                this.list.animate(o, this.options.animation, this.options.easing, scrolled);
            }
        },

        /**
         * Starts autoscrolling.
         *
         * @method auto
         * @return undefined
         * @param s {Number} Seconds to periodically autoscroll the content.
         */
        startAuto: function(s) {
            if (s != undefined)
                this.options.auto = s;

            if (this.options.auto == 0)
                return this.stopAuto();

            if (this.timer != null)
                return;

            var self = this;
            this.timer = setTimeout(function() { self.next(); }, this.options.auto * 1000);
        },

        /**
         * Stops autoscrolling.
         *
         * @method stopAuto
         * @return undefined
         */
        stopAuto: function() {
            if (this.timer == null)
                return;

            clearTimeout(this.timer);
            this.timer = null;
        },

        /**
         * Sets the states of the prev/next buttons.
         *
         * @method buttons
         * @return undefined
         */
        buttons: function(n, p) {
            if (n == undefined || n == null) {
                var n = !this.locked && this.options.size !== 0 && ((this.options.wrap && this.options.wrap != 'first') || this.options.size == null || this.last < this.options.size);
                if (!this.locked && (!this.options.wrap || this.options.wrap == 'first') && this.options.size != null && this.last >= this.options.size)
                    n = this.tail != null && !this.inTail;
            }

            if (p == undefined || p == null) {
                var p = !this.locked && this.options.size !== 0 && ((this.options.wrap && this.options.wrap != 'last') || this.first > 1);
                if (!this.locked && (!this.options.wrap || this.options.wrap == 'last') && this.options.size != null && this.first == 1)
                    p = this.tail != null && this.inTail;
            }

            var self = this;

            this.buttonNext[n ? 'bind' : 'unbind'](this.options.buttonNextEvent + '.jcarousel', this.funcNext)[n ? 'removeClass' : 'addClass'](this.className('jcarousel-next-disabled')).attr('disabled', n ? false : true);
            this.buttonPrev[p ? 'bind' : 'unbind'](this.options.buttonPrevEvent + '.jcarousel', this.funcPrev)[p ? 'removeClass' : 'addClass'](this.className('jcarousel-prev-disabled')).attr('disabled', p ? false : true);

            if (this.buttonNext.length > 0 && (this.buttonNext[0].jcarouselstate == undefined || this.buttonNext[0].jcarouselstate != n) && this.options.buttonNextCallback != null) {
                this.buttonNext.each(function() { self.options.buttonNextCallback(self, this, n); });
                this.buttonNext[0].jcarouselstate = n;
            }

            if (this.buttonPrev.length > 0 && (this.buttonPrev[0].jcarouselstate == undefined || this.buttonPrev[0].jcarouselstate != p) && this.options.buttonPrevCallback != null) {
                this.buttonPrev.each(function() { self.options.buttonPrevCallback(self, this, p); });
                this.buttonPrev[0].jcarouselstate = p;
            }
        },

        /**
         * Notify callback of a specified event.
         *
         * @method notify
         * @return undefined
         * @param evt {String} The event name
         */
        notify: function(evt) {
            var state = this.prevFirst == null ? 'init' : (this.prevFirst < this.first ? 'next' : 'prev');

            // Load items
            this.callback('itemLoadCallback', evt, state);

            if (this.prevFirst !== this.first) {
                this.callback('itemFirstInCallback', evt, state, this.first);
                this.callback('itemFirstOutCallback', evt, state, this.prevFirst);
            }

            if (this.prevLast !== this.last) {
                this.callback('itemLastInCallback', evt, state, this.last);
                this.callback('itemLastOutCallback', evt, state, this.prevLast);
            }

            this.callback('itemVisibleInCallback', evt, state, this.first, this.last, this.prevFirst, this.prevLast);
            this.callback('itemVisibleOutCallback', evt, state, this.prevFirst, this.prevLast, this.first, this.last);
        },

        callback: function(cb, evt, state, i1, i2, i3, i4) {
            if (this.options[cb] == undefined || (typeof this.options[cb] != 'object' && evt != 'onAfterAnimation'))
                return;

            var callback = typeof this.options[cb] == 'object' ? this.options[cb][evt] : this.options[cb];

            if (!$.isFunction(callback))
                return;

            var self = this;

            if (i1 === undefined)
                callback(self, state, evt);
            else if (i2 === undefined)
                this.get(i1).each(function() { callback(self, this, i1, state, evt); });
            else {
                for (var i = i1; i <= i2; i++)
                    if (i !== null && !(i >= i3 && i <= i4))
                        this.get(i).each(function() { callback(self, this, i, state, evt); });
            }
        },

        create: function(i) {
            return this.format('<li></li>', i);
        },

        format: function(e, i) {
            var $e = $(e).addClass(this.className('jcarousel-item')).addClass(this.className('jcarousel-item-' + i));
            //.css({
             //   'float': 'left',
             //   'list-style': 'none'
            //})
            $e.attr('jcarouselindex', i);
            return $e;
        },

        className: function(c) {
            return c + ' ' + c + (!this.options.vertical ? '-horizontal' : '-vertical');
        },

        dimension: function(e, d) {
            var el = e.jquery != undefined ? e[0] : e;

            var old = !this.options.vertical ?
                el.offsetWidth + $jc.margin(el, 'marginLeft') + $jc.margin(el, 'marginRight') :
                el.offsetHeight + $jc.margin(el, 'marginTop') + $jc.margin(el, 'marginBottom');

            if (d == undefined || old == d)
                return old;

            var w = !this.options.vertical ?
                d - $jc.margin(el, 'marginLeft') - $jc.margin(el, 'marginRight') :
                d - $jc.margin(el, 'marginTop') - $jc.margin(el, 'marginBottom');

            $(el).css(this.wh, w + 'px');

            return this.dimension(el);
        },

        clipping: function() {
            return !this.options.vertical ?
                this.clip[0].offsetWidth - $jc.intval(this.clip.css('borderLeftWidth')) - $jc.intval(this.clip.css('borderRightWidth')) :
                this.clip[0].offsetHeight - $jc.intval(this.clip.css('borderTopWidth')) - $jc.intval(this.clip.css('borderBottomWidth'));
        },

        index: function(i, s) {
            if (s == undefined)
                s = this.options.size;

            return Math.round((((i-1) / s) - Math.floor((i-1) / s)) * s) + 1;
        }
    });

    $jc.extend({
        /**
         * Gets/Sets the global default configuration properties.
         *
         * @method defaults
         * @return {Object}
         * @param d {Object} A set of key/value pairs to set as configuration properties.
         */
        defaults: function(d) {
            return $.extend(defaults, d || {});
        },

        margin: function(e, p) {
            if (!e)
                return 0;

            var el = e.jquery != undefined ? e[0] : e;

            if (p == 'marginRight' && $.browser.safari) {
                var old = {'display': 'block', 'float': 'none', 'width': 'auto'}, oWidth, oWidth2;

                $.swap(el, old, function() { oWidth = el.offsetWidth; });

                old['marginRight'] = 0;
                $.swap(el, old, function() { oWidth2 = el.offsetWidth; });

                return oWidth2 - oWidth;
            }

            return $jc.intval($.css(el, p));
        },

        intval: function(v) {
            v = parseInt(v);
            return isNaN(v) ? 0 : v;
        }
    });

})(jQuery);
/*
== malihu jquery custom scrollbar plugin == 
Version: 3.1.5 
Plugin URI: http://manos.malihu.gr/jquery-custom-content-scroller 
Author: malihu
Author URI: http://manos.malihu.gr
License: MIT License (MIT)
*/

/*
Copyright Manos Malihutsakis (email: manos@malihu.gr)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
The code below is fairly long, fully commented and should be normally used in development. 
For production, use either the minified jquery.mCustomScrollbar.min.js script or 
the production-ready jquery.mCustomScrollbar.concat.min.js which contains the plugin 
and dependencies (minified). 
*/

(function(factory){
	if(typeof define==="function" && define.amd){
		define(["jquery"],factory);
	}else if(typeof module!=="undefined" && module.exports){
		module.exports=factory;
	}else{
		factory(jQuery,window,document);
	}
}(function($){
(function(init){
	var _rjs=typeof define==="function" && define.amd, /* RequireJS */
		_njs=typeof module !== "undefined" && module.exports, /* NodeJS */
		_dlp=("https:"==document.location.protocol) ? "https:" : "http:", /* location protocol */
		_url="cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js";
	if(!_rjs){
		if(_njs){
			require("jquery-mousewheel")($);
		}else{
			/* load jquery-mousewheel plugin (via CDN) if it's not present or not loaded via RequireJS 
			(works when mCustomScrollbar fn is called on window load) */
			$.event.special.mousewheel || $("head").append(decodeURI("%3Cscript src="+_dlp+"//"+_url+"%3E%3C/script%3E"));
		}
	}
	init();
}(function(){
	
	/* 
	----------------------------------------
	PLUGIN NAMESPACE, PREFIX, DEFAULT SELECTOR(S) 
	----------------------------------------
	*/
	
	var pluginNS="mCustomScrollbar",
		pluginPfx="mCS",
		defaultSelector=".mCustomScrollbar",
	
	
		
	
	
	/* 
	----------------------------------------
	DEFAULT OPTIONS 
	----------------------------------------
	*/
	
		defaults={
			/*
			set element/content width/height programmatically 
			values: boolean, pixels, percentage 
				option						default
				-------------------------------------
				setWidth					false
				setHeight					false
			*/
			/*
			set the initial css top property of content  
			values: string (e.g. "-100px", "10%" etc.)
			*/
			setTop:0,
			/*
			set the initial css left property of content  
			values: string (e.g. "-100px", "10%" etc.)
			*/
			setLeft:0,
			/* 
			scrollbar axis (vertical and/or horizontal scrollbars) 
			values (string): "y", "x", "yx"
			*/
			axis:"y",
			/*
			position of scrollbar relative to content  
			values (string): "inside", "outside" ("outside" requires elements with position:relative)
			*/
			scrollbarPosition:"inside",
			/*
			scrolling inertia
			values: integer (milliseconds)
			*/
			scrollInertia:950,
			/* 
			auto-adjust scrollbar dragger length
			values: boolean
			*/
			autoDraggerLength:true,
			/*
			auto-hide scrollbar when idle 
			values: boolean
				option						default
				-------------------------------------
				autoHideScrollbar			false
			*/
			/*
			auto-expands scrollbar on mouse-over and dragging
			values: boolean
				option						default
				-------------------------------------
				autoExpandScrollbar			false
			*/
			/*
			always show scrollbar, even when there's nothing to scroll 
			values: integer (0=disable, 1=always show dragger rail and buttons, 2=always show dragger rail, dragger and buttons), boolean
			*/
			alwaysShowScrollbar:0,
			/*
			scrolling always snaps to a multiple of this number in pixels
			values: integer, array ([y,x])
				option						default
				-------------------------------------
				snapAmount					null
			*/
			/*
			when snapping, snap with this number in pixels as an offset 
			values: integer
			*/
			snapOffset:0,
			/* 
			mouse-wheel scrolling
			*/
			mouseWheel:{
				/* 
				enable mouse-wheel scrolling
				values: boolean
				*/
				enable:true,
				/* 
				scrolling amount in pixels
				values: "auto", integer 
				*/
				scrollAmount:"auto",
				/* 
				mouse-wheel scrolling axis 
				the default scrolling direction when both vertical and horizontal scrollbars are present 
				values (string): "y", "x" 
				*/
				axis:"y",
				/* 
				prevent the default behaviour which automatically scrolls the parent element(s) when end of scrolling is reached 
				values: boolean
					option						default
					-------------------------------------
					preventDefault				null
				*/
				/*
				the reported mouse-wheel delta value. The number of lines (translated to pixels) one wheel notch scrolls.  
				values: "auto", integer 
				"auto" uses the default OS/browser value 
				*/
				deltaFactor:"auto",
				/*
				normalize mouse-wheel delta to -1 or 1 (disables mouse-wheel acceleration) 
				values: boolean
					option						default
					-------------------------------------
					normalizeDelta				null
				*/
				/*
				invert mouse-wheel scrolling direction 
				values: boolean
					option						default
					-------------------------------------
					invert						null
				*/
				/*
				the tags that disable mouse-wheel when cursor is over them
				*/
				disableOver:["select","option","keygen","datalist","textarea"]
			},
			/* 
			scrollbar buttons
			*/
			scrollButtons:{ 
				/*
				enable scrollbar buttons
				values: boolean
					option						default
					-------------------------------------
					enable						null
				*/
				/*
				scrollbar buttons scrolling type 
				values (string): "stepless", "stepped"
				*/
				scrollType:"stepless",
				/*
				scrolling amount in pixels
				values: "auto", integer 
				*/
				scrollAmount:"auto"
				/*
				tabindex of the scrollbar buttons
				values: false, integer
					option						default
					-------------------------------------
					tabindex					null
				*/
			},
			/* 
			keyboard scrolling
			*/
			keyboard:{ 
				/*
				enable scrolling via keyboard
				values: boolean
				*/
				enable:true,
				/*
				keyboard scrolling type 
				values (string): "stepless", "stepped"
				*/
				scrollType:"stepless",
				/*
				scrolling amount in pixels
				values: "auto", integer 
				*/
				scrollAmount:"auto"
			},
			/*
			enable content touch-swipe scrolling 
			values: boolean, integer, string (number)
			integer values define the axis-specific minimum amount required for scrolling momentum
			*/
			contentTouchScroll:25,
			/*
			enable/disable document (default) touch-swipe scrolling 
			*/
			documentTouchScroll:true,
			/*
			advanced option parameters
			*/
			advanced:{
				/*
				auto-expand content horizontally (for "x" or "yx" axis) 
				values: boolean, integer (the value 2 forces the non scrollHeight/scrollWidth method, the value 3 forces the scrollHeight/scrollWidth method)
					option						default
					-------------------------------------
					autoExpandHorizontalScroll	null
				*/
				/*
				auto-scroll to elements with focus
				*/
				autoScrollOnFocus:"input,textarea,select,button,datalist,keygen,a[tabindex],area,object,[contenteditable='true']",
				/*
				auto-update scrollbars on content, element or viewport resize 
				should be true for fluid layouts/elements, adding/removing content dynamically, hiding/showing elements, content with images etc. 
				values: boolean
				*/
				updateOnContentResize:true,
				/*
				auto-update scrollbars each time each image inside the element is fully loaded 
				values: "auto", boolean
				*/
				updateOnImageLoad:"auto",
				/*
				auto-update scrollbars based on the amount and size changes of specific selectors 
				useful when you need to update the scrollbar(s) automatically, each time a type of element is added, removed or changes its size 
				values: boolean, string (e.g. "ul li" will auto-update scrollbars each time list-items inside the element are changed) 
				a value of true (boolean) will auto-update scrollbars each time any element is changed
					option						default
					-------------------------------------
					updateOnSelectorChange		null
				*/
				/*
				extra selectors that'll allow scrollbar dragging upon mousemove/up, pointermove/up, touchend etc. (e.g. "selector-1, selector-2")
					option						default
					-------------------------------------
					extraDraggableSelectors		null
				*/
				/*
				extra selectors that'll release scrollbar dragging upon mouseup, pointerup, touchend etc. (e.g. "selector-1, selector-2")
					option						default
					-------------------------------------
					releaseDraggableSelectors	null
				*/
				/*
				auto-update timeout 
				values: integer (milliseconds)
				*/
				autoUpdateTimeout:60
			},
			/* 
			scrollbar theme 
			values: string (see CSS/plugin URI for a list of ready-to-use themes)
			*/
			theme:"light",
			/*
			user defined callback functions
			*/
			callbacks:{
				/*
				Available callbacks: 
					callback					default
					-------------------------------------
					onCreate					null
					onInit						null
					onScrollStart				null
					onScroll					null
					onTotalScroll				null
					onTotalScrollBack			null
					whileScrolling				null
					onOverflowY					null
					onOverflowX					null
					onOverflowYNone				null
					onOverflowXNone				null
					onImageLoad					null
					onSelectorChange			null
					onBeforeUpdate				null
					onUpdate					null
				*/
				onTotalScrollOffset:0,
				onTotalScrollBackOffset:0,
				alwaysTriggerOffsets:true
			}
			/*
			add scrollbar(s) on all elements matching the current selector, now and in the future 
			values: boolean, string 
			string values: "on" (enable), "once" (disable after first invocation), "off" (disable)
			liveSelector values: string (selector)
				option						default
				-------------------------------------
				live						false
				liveSelector				null
			*/
		},
	
	
	
	
	
	/* 
	----------------------------------------
	VARS, CONSTANTS 
	----------------------------------------
	*/
	
		totalInstances=0, /* plugin instances amount */
		liveTimers={}, /* live option timers */
		oldIE=(window.attachEvent && !window.addEventListener) ? 1 : 0, /* detect IE < 9 */
		touchActive=false,touchable, /* global touch vars (for touch and pointer events) */
		/* general plugin classes */
		classes=[
			"mCSB_dragger_onDrag","mCSB_scrollTools_onDrag","mCS_img_loaded","mCS_disabled","mCS_destroyed","mCS_no_scrollbar",
			"mCS-autoHide","mCS-dir-rtl","mCS_no_scrollbar_y","mCS_no_scrollbar_x","mCS_y_hidden","mCS_x_hidden","mCSB_draggerContainer",
			"mCSB_buttonUp","mCSB_buttonDown","mCSB_buttonLeft","mCSB_buttonRight"
		],
		
	
	
	
	
	/* 
	----------------------------------------
	METHODS 
	----------------------------------------
	*/
	
		methods={
			
			/* 
			plugin initialization method 
			creates the scrollbar(s), plugin data object and options
			----------------------------------------
			*/
			
			init:function(options){
				
				var options=$.extend(true,{},defaults,options),
					selector=_selector.call(this); /* validate selector */
				
				/* 
				if live option is enabled, monitor for elements matching the current selector and 
				apply scrollbar(s) when found (now and in the future) 
				*/
				if(options.live){
					var liveSelector=options.liveSelector || this.selector || defaultSelector, /* live selector(s) */
						$liveSelector=$(liveSelector); /* live selector(s) as jquery object */
					if(options.live==="off"){
						/* 
						disable live if requested 
						usage: $(selector).mCustomScrollbar({live:"off"}); 
						*/
						removeLiveTimers(liveSelector);
						return;
					}
					liveTimers[liveSelector]=setTimeout(function(){
						/* call mCustomScrollbar fn on live selector(s) every half-second */
						$liveSelector.mCustomScrollbar(options);
						if(options.live==="once" && $liveSelector.length){
							/* disable live after first invocation */
							removeLiveTimers(liveSelector);
						}
					},500);
				}else{
					removeLiveTimers(liveSelector);
				}
				
				/* options backward compatibility (for versions < 3.0.0) and normalization */
				options.setWidth=(options.set_width) ? options.set_width : options.setWidth;
				options.setHeight=(options.set_height) ? options.set_height : options.setHeight;
				options.axis=(options.horizontalScroll) ? "x" : _findAxis(options.axis);
				options.scrollInertia=options.scrollInertia>0 && options.scrollInertia<17 ? 17 : options.scrollInertia;
				if(typeof options.mouseWheel!=="object" &&  options.mouseWheel==true){ /* old school mouseWheel option (non-object) */
					options.mouseWheel={enable:true,scrollAmount:"auto",axis:"y",preventDefault:false,deltaFactor:"auto",normalizeDelta:false,invert:false}
				}
				options.mouseWheel.scrollAmount=!options.mouseWheelPixels ? options.mouseWheel.scrollAmount : options.mouseWheelPixels;
				options.mouseWheel.normalizeDelta=!options.advanced.normalizeMouseWheelDelta ? options.mouseWheel.normalizeDelta : options.advanced.normalizeMouseWheelDelta;
				options.scrollButtons.scrollType=_findScrollButtonsType(options.scrollButtons.scrollType); 
				
				_theme(options); /* theme-specific options */
				
				/* plugin constructor */
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if(!$this.data(pluginPfx)){ /* prevent multiple instantiations */
					
						/* store options and create objects in jquery data */
						$this.data(pluginPfx,{
							idx:++totalInstances, /* instance index */
							opt:options, /* options */
							scrollRatio:{y:null,x:null}, /* scrollbar to content ratio */
							overflowed:null, /* overflowed axis */
							contentReset:{y:null,x:null}, /* object to check when content resets */
							bindEvents:false, /* object to check if events are bound */
							tweenRunning:false, /* object to check if tween is running */
							sequential:{}, /* sequential scrolling object */
							langDir:$this.css("direction"), /* detect/store direction (ltr or rtl) */
							cbOffsets:null, /* object to check whether callback offsets always trigger */
							/* 
							object to check how scrolling events where last triggered 
							"internal" (default - triggered by this script), "external" (triggered by other scripts, e.g. via scrollTo method) 
							usage: object.data("mCS").trigger
							*/
							trigger:null,
							/* 
							object to check for changes in elements in order to call the update method automatically 
							*/
							poll:{size:{o:0,n:0},img:{o:0,n:0},change:{o:0,n:0}}
						});
						
						var d=$this.data(pluginPfx),o=d.opt,
							/* HTML data attributes */
							htmlDataAxis=$this.data("mcs-axis"),htmlDataSbPos=$this.data("mcs-scrollbar-position"),htmlDataTheme=$this.data("mcs-theme");
						 
						if(htmlDataAxis){o.axis=htmlDataAxis;} /* usage example: data-mcs-axis="y" */
						if(htmlDataSbPos){o.scrollbarPosition=htmlDataSbPos;} /* usage example: data-mcs-scrollbar-position="outside" */
						if(htmlDataTheme){ /* usage example: data-mcs-theme="minimal" */
							o.theme=htmlDataTheme;
							_theme(o); /* theme-specific options */
						}
						
						_pluginMarkup.call(this); /* add plugin markup */
						
						if(d && o.callbacks.onCreate && typeof o.callbacks.onCreate==="function"){o.callbacks.onCreate.call(this);} /* callbacks: onCreate */
						
						$("#mCSB_"+d.idx+"_container img:not(."+classes[2]+")").addClass(classes[2]); /* flag loaded images */
						
						methods.update.call(null,$this); /* call the update method */
					
					}
					
				});
				
			},
			/* ---------------------------------------- */
			
			
			
			/* 
			plugin update method 
			updates content and scrollbar(s) values, events and status 
			----------------------------------------
			usage: $(selector).mCustomScrollbar("update");
			*/
			
			update:function(el,cb){
				
				var selector=el || _selector.call(this); /* validate selector */
				
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if($this.data(pluginPfx)){ /* check if plugin has initialized */
						
						var d=$this.data(pluginPfx),o=d.opt,
							mCSB_container=$("#mCSB_"+d.idx+"_container"),
							mCustomScrollBox=$("#mCSB_"+d.idx),
							mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")];
						
						if(!mCSB_container.length){return;}
						
						if(d.tweenRunning){_stop($this);} /* stop any running tweens while updating */
						
						if(cb && d && o.callbacks.onBeforeUpdate && typeof o.callbacks.onBeforeUpdate==="function"){o.callbacks.onBeforeUpdate.call(this);} /* callbacks: onBeforeUpdate */
						
						/* if element was disabled or destroyed, remove class(es) */
						if($this.hasClass(classes[3])){$this.removeClass(classes[3]);}
						if($this.hasClass(classes[4])){$this.removeClass(classes[4]);}
						
						/* css flexbox fix, detect/set max-height */
						mCustomScrollBox.css("max-height","none");
						if(mCustomScrollBox.height()!==$this.height()){mCustomScrollBox.css("max-height",$this.height());}
						
						_expandContentHorizontally.call(this); /* expand content horizontally */
						
						if(o.axis!=="y" && !o.advanced.autoExpandHorizontalScroll){
							mCSB_container.css("width",_contentWidth(mCSB_container));
						}
						
						d.overflowed=_overflowed.call(this); /* determine if scrolling is required */
						
						_scrollbarVisibility.call(this); /* show/hide scrollbar(s) */
						
						/* auto-adjust scrollbar dragger length analogous to content */
						if(o.autoDraggerLength){_setDraggerLength.call(this);}
						
						_scrollRatio.call(this); /* calculate and store scrollbar to content ratio */
						
						_bindEvents.call(this); /* bind scrollbar events */
						
						/* reset scrolling position and/or events */
						var to=[Math.abs(mCSB_container[0].offsetTop),Math.abs(mCSB_container[0].offsetLeft)];
						if(o.axis!=="x"){ /* y/yx axis */
							if(!d.overflowed[0]){ /* y scrolling is not required */
								_resetContentPosition.call(this); /* reset content position */
								if(o.axis==="y"){
									_unbindEvents.call(this);
								}else if(o.axis==="yx" && d.overflowed[1]){
									_scrollTo($this,to[1].toString(),{dir:"x",dur:0,overwrite:"none"});
								}
							}else if(mCSB_dragger[0].height()>mCSB_dragger[0].parent().height()){
								_resetContentPosition.call(this); /* reset content position */
							}else{ /* y scrolling is required */
								_scrollTo($this,to[0].toString(),{dir:"y",dur:0,overwrite:"none"});
								d.contentReset.y=null;
							}
						}
						if(o.axis!=="y"){ /* x/yx axis */
							if(!d.overflowed[1]){ /* x scrolling is not required */
								_resetContentPosition.call(this); /* reset content position */
								if(o.axis==="x"){
									_unbindEvents.call(this);
								}else if(o.axis==="yx" && d.overflowed[0]){
									_scrollTo($this,to[0].toString(),{dir:"y",dur:0,overwrite:"none"});
								}
							}else if(mCSB_dragger[1].width()>mCSB_dragger[1].parent().width()){
								_resetContentPosition.call(this); /* reset content position */
							}else{ /* x scrolling is required */
								_scrollTo($this,to[1].toString(),{dir:"x",dur:0,overwrite:"none"});
								d.contentReset.x=null;
							}
						}
						
						/* callbacks: onImageLoad, onSelectorChange, onUpdate */
						if(cb && d){
							if(cb===2 && o.callbacks.onImageLoad && typeof o.callbacks.onImageLoad==="function"){
								o.callbacks.onImageLoad.call(this);
							}else if(cb===3 && o.callbacks.onSelectorChange && typeof o.callbacks.onSelectorChange==="function"){
								o.callbacks.onSelectorChange.call(this);
							}else if(o.callbacks.onUpdate && typeof o.callbacks.onUpdate==="function"){
								o.callbacks.onUpdate.call(this);
							}
						}
						
						_autoUpdate.call(this); /* initialize automatic updating (for dynamic content, fluid layouts etc.) */
						
					}
					
				});
				
			},
			/* ---------------------------------------- */
			
			
			
			/* 
			plugin scrollTo method 
			triggers a scrolling event to a specific value
			----------------------------------------
			usage: $(selector).mCustomScrollbar("scrollTo",value,options);
			*/
		
			scrollTo:function(val,options){
				
				/* prevent silly things like $(selector).mCustomScrollbar("scrollTo",undefined); */
				if(typeof val=="undefined" || val==null){return;}
				
				var selector=_selector.call(this); /* validate selector */
				
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if($this.data(pluginPfx)){ /* check if plugin has initialized */
					
						var d=$this.data(pluginPfx),o=d.opt,
							/* method default options */
							methodDefaults={
								trigger:"external", /* method is by default triggered externally (e.g. from other scripts) */
								scrollInertia:o.scrollInertia, /* scrolling inertia (animation duration) */
								scrollEasing:"mcsEaseInOut", /* animation easing */
								moveDragger:false, /* move dragger instead of content */
								timeout:60, /* scroll-to delay */
								callbacks:true, /* enable/disable callbacks */
								onStart:true,
								onUpdate:true,
								onComplete:true
							},
							methodOptions=$.extend(true,{},methodDefaults,options),
							to=_arr.call(this,val),dur=methodOptions.scrollInertia>0 && methodOptions.scrollInertia<17 ? 17 : methodOptions.scrollInertia;
						
						/* translate yx values to actual scroll-to positions */
						to[0]=_to.call(this,to[0],"y");
						to[1]=_to.call(this,to[1],"x");
						
						/* 
						check if scroll-to value moves the dragger instead of content. 
						Only pixel values apply on dragger (e.g. 100, "100px", "-=100" etc.) 
						*/
						if(methodOptions.moveDragger){
							to[0]*=d.scrollRatio.y;
							to[1]*=d.scrollRatio.x;
						}
						
						methodOptions.dur=_isTabHidden() ? 0 : dur; //skip animations if browser tab is hidden
						
						setTimeout(function(){ 
							/* do the scrolling */
							if(to[0]!==null && typeof to[0]!=="undefined" && o.axis!=="x" && d.overflowed[0]){ /* scroll y */
								methodOptions.dir="y";
								methodOptions.overwrite="all";
								_scrollTo($this,to[0].toString(),methodOptions);
							}
							if(to[1]!==null && typeof to[1]!=="undefined" && o.axis!=="y" && d.overflowed[1]){ /* scroll x */
								methodOptions.dir="x";
								methodOptions.overwrite="none";
								_scrollTo($this,to[1].toString(),methodOptions);
							}
						},methodOptions.timeout);
						
					}
					
				});
				
			},
			/* ---------------------------------------- */
			
			
			
			/*
			plugin stop method 
			stops scrolling animation
			----------------------------------------
			usage: $(selector).mCustomScrollbar("stop");
			*/
			stop:function(){
				
				var selector=_selector.call(this); /* validate selector */
				
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if($this.data(pluginPfx)){ /* check if plugin has initialized */
										
						_stop($this);
					
					}
					
				});
				
			},
			/* ---------------------------------------- */
			
			
			
			/*
			plugin disable method 
			temporarily disables the scrollbar(s) 
			----------------------------------------
			usage: $(selector).mCustomScrollbar("disable",reset); 
			reset (boolean): resets content position to 0 
			*/
			disable:function(r){
				
				var selector=_selector.call(this); /* validate selector */
				
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if($this.data(pluginPfx)){ /* check if plugin has initialized */
						
						var d=$this.data(pluginPfx);
						
						_autoUpdate.call(this,"remove"); /* remove automatic updating */
						
						_unbindEvents.call(this); /* unbind events */
						
						if(r){_resetContentPosition.call(this);} /* reset content position */
						
						_scrollbarVisibility.call(this,true); /* show/hide scrollbar(s) */
						
						$this.addClass(classes[3]); /* add disable class */
					
					}
					
				});
				
			},
			/* ---------------------------------------- */
			
			
			
			/*
			plugin destroy method 
			completely removes the scrollbar(s) and returns the element to its original state
			----------------------------------------
			usage: $(selector).mCustomScrollbar("destroy"); 
			*/
			destroy:function(){
				
				var selector=_selector.call(this); /* validate selector */
				
				return $(selector).each(function(){
					
					var $this=$(this);
					
					if($this.data(pluginPfx)){ /* check if plugin has initialized */
					
						var d=$this.data(pluginPfx),o=d.opt,
							mCustomScrollBox=$("#mCSB_"+d.idx),
							mCSB_container=$("#mCSB_"+d.idx+"_container"),
							scrollbar=$(".mCSB_"+d.idx+"_scrollbar");
					
						if(o.live){removeLiveTimers(o.liveSelector || $(selector).selector);} /* remove live timers */
						
						_autoUpdate.call(this,"remove"); /* remove automatic updating */
						
						_unbindEvents.call(this); /* unbind events */
						
						_resetContentPosition.call(this); /* reset content position */
						
						$this.removeData(pluginPfx); /* remove plugin data object */
						
						_delete(this,"mcs"); /* delete callbacks object */
						
						/* remove plugin markup */
						scrollbar.remove(); /* remove scrollbar(s) first (those can be either inside or outside plugin's inner wrapper) */
						mCSB_container.find("img."+classes[2]).removeClass(classes[2]); /* remove loaded images flag */
						mCustomScrollBox.replaceWith(mCSB_container.contents()); /* replace plugin's inner wrapper with the original content */
						/* remove plugin classes from the element and add destroy class */
						$this.removeClass(pluginNS+" _"+pluginPfx+"_"+d.idx+" "+classes[6]+" "+classes[7]+" "+classes[5]+" "+classes[3]).addClass(classes[4]);
					
					}
					
				});
				
			}
			/* ---------------------------------------- */
			
		},
	
	
	
	
		
	/* 
	----------------------------------------
	FUNCTIONS
	----------------------------------------
	*/
	
		/* validates selector (if selector is invalid or undefined uses the default one) */
		_selector=function(){
			return (typeof $(this)!=="object" || $(this).length<1) ? defaultSelector : this;
		},
		/* -------------------- */
		
		
		/* changes options according to theme */
		_theme=function(obj){
			var fixedSizeScrollbarThemes=["rounded","rounded-dark","rounded-dots","rounded-dots-dark"],
				nonExpandedScrollbarThemes=["rounded-dots","rounded-dots-dark","3d","3d-dark","3d-thick","3d-thick-dark","inset","inset-dark","inset-2","inset-2-dark","inset-3","inset-3-dark"],
				disabledScrollButtonsThemes=["minimal","minimal-dark"],
				enabledAutoHideScrollbarThemes=["minimal","minimal-dark"],
				scrollbarPositionOutsideThemes=["minimal","minimal-dark"];
			obj.autoDraggerLength=$.inArray(obj.theme,fixedSizeScrollbarThemes) > -1 ? false : obj.autoDraggerLength;
			obj.autoExpandScrollbar=$.inArray(obj.theme,nonExpandedScrollbarThemes) > -1 ? false : obj.autoExpandScrollbar;
			obj.scrollButtons.enable=$.inArray(obj.theme,disabledScrollButtonsThemes) > -1 ? false : obj.scrollButtons.enable;
			obj.autoHideScrollbar=$.inArray(obj.theme,enabledAutoHideScrollbarThemes) > -1 ? true : obj.autoHideScrollbar;
			obj.scrollbarPosition=$.inArray(obj.theme,scrollbarPositionOutsideThemes) > -1 ? "outside" : obj.scrollbarPosition;
		},
		/* -------------------- */
		
		
		/* live option timers removal */
		removeLiveTimers=function(selector){
			if(liveTimers[selector]){
				clearTimeout(liveTimers[selector]);
				_delete(liveTimers,selector);
			}
		},
		/* -------------------- */
		
		
		/* normalizes axis option to valid values: "y", "x", "yx" */
		_findAxis=function(val){
			return (val==="yx" || val==="xy" || val==="auto") ? "yx" : (val==="x" || val==="horizontal") ? "x" : "y";
		},
		/* -------------------- */
		
		
		/* normalizes scrollButtons.scrollType option to valid values: "stepless", "stepped" */
		_findScrollButtonsType=function(val){
			return (val==="stepped" || val==="pixels" || val==="step" || val==="click") ? "stepped" : "stepless";
		},
		/* -------------------- */
		
		
		/* generates plugin markup */
		_pluginMarkup=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				expandClass=o.autoExpandScrollbar ? " "+classes[1]+"_expand" : "",
				scrollbar=["<div id='mCSB_"+d.idx+"_scrollbar_vertical' class='mCSB_scrollTools mCSB_"+d.idx+"_scrollbar mCS-"+o.theme+" mCSB_scrollTools_vertical"+expandClass+"'><div class='"+classes[12]+"'><div id='mCSB_"+d.idx+"_dragger_vertical' class='mCSB_dragger' style='position:absolute;'><div class='mCSB_dragger_bar' /></div><div class='mCSB_draggerRail' /></div></div>","<div id='mCSB_"+d.idx+"_scrollbar_horizontal' class='mCSB_scrollTools mCSB_"+d.idx+"_scrollbar mCS-"+o.theme+" mCSB_scrollTools_horizontal"+expandClass+"'><div class='"+classes[12]+"'><div id='mCSB_"+d.idx+"_dragger_horizontal' class='mCSB_dragger' style='position:absolute;'><div class='mCSB_dragger_bar' /></div><div class='mCSB_draggerRail' /></div></div>"],
				wrapperClass=o.axis==="yx" ? "mCSB_vertical_horizontal" : o.axis==="x" ? "mCSB_horizontal" : "mCSB_vertical",
				scrollbars=o.axis==="yx" ? scrollbar[0]+scrollbar[1] : o.axis==="x" ? scrollbar[1] : scrollbar[0],
				contentWrapper=o.axis==="yx" ? "<div id='mCSB_"+d.idx+"_container_wrapper' class='mCSB_container_wrapper' />" : "",
				autoHideClass=o.autoHideScrollbar ? " "+classes[6] : "",
				scrollbarDirClass=(o.axis!=="x" && d.langDir==="rtl") ? " "+classes[7] : "";
			if(o.setWidth){$this.css("width",o.setWidth);} /* set element width */
			if(o.setHeight){$this.css("height",o.setHeight);} /* set element height */
			o.setLeft=(o.axis!=="y" && d.langDir==="rtl") ? "989999px" : o.setLeft; /* adjust left position for rtl direction */
			$this.addClass(pluginNS+" _"+pluginPfx+"_"+d.idx+autoHideClass+scrollbarDirClass).wrapInner("<div id='mCSB_"+d.idx+"' class='mCustomScrollBox mCS-"+o.theme+" "+wrapperClass+"'><div id='mCSB_"+d.idx+"_container' class='mCSB_container' style='position:relative; top:"+o.setTop+"; left:"+o.setLeft+";' dir='"+d.langDir+"' /></div>");
			var mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container");
			if(o.axis!=="y" && !o.advanced.autoExpandHorizontalScroll){
				mCSB_container.css("width",_contentWidth(mCSB_container));
			}
			if(o.scrollbarPosition==="outside"){
				if($this.css("position")==="static"){ /* requires elements with non-static position */
					$this.css("position","relative");
				}
				$this.css("overflow","visible");
				mCustomScrollBox.addClass("mCSB_outside").after(scrollbars);
			}else{
				mCustomScrollBox.addClass("mCSB_inside").append(scrollbars);
				mCSB_container.wrap(contentWrapper);
			}
			_scrollButtons.call(this); /* add scrollbar buttons */
			/* minimum dragger length */
			var mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")];
			mCSB_dragger[0].css("min-height",mCSB_dragger[0].height());
			mCSB_dragger[1].css("min-width",mCSB_dragger[1].width());
		},
		/* -------------------- */
		
		
		/* calculates content width */
		_contentWidth=function(el){
			var val=[el[0].scrollWidth,Math.max.apply(Math,el.children().map(function(){return $(this).outerWidth(true);}).get())],w=el.parent().width();
			return val[0]>w ? val[0] : val[1]>w ? val[1] : "100%";
		},
		/* -------------------- */
		
		
		/* expands content horizontally */
		_expandContentHorizontally=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				mCSB_container=$("#mCSB_"+d.idx+"_container");
			if(o.advanced.autoExpandHorizontalScroll && o.axis!=="y"){
				/* calculate scrollWidth */
				mCSB_container.css({"width":"auto","min-width":0,"overflow-x":"scroll"});
				var w=Math.ceil(mCSB_container[0].scrollWidth);
				if(o.advanced.autoExpandHorizontalScroll===3 || (o.advanced.autoExpandHorizontalScroll!==2 && w>mCSB_container.parent().width())){
					mCSB_container.css({"width":w,"min-width":"100%","overflow-x":"inherit"});
				}else{
					/* 
					wrap content with an infinite width div and set its position to absolute and width to auto. 
					Setting width to auto before calculating the actual width is important! 
					We must let the browser set the width as browser zoom values are impossible to calculate.
					*/
					mCSB_container.css({"overflow-x":"inherit","position":"absolute"})
						.wrap("<div class='mCSB_h_wrapper' style='position:relative; left:0; width:999999px;' />")
						.css({ /* set actual width, original position and un-wrap */
							/* 
							get the exact width (with decimals) and then round-up. 
							Using jquery outerWidth() will round the width value which will mess up with inner elements that have non-integer width
							*/
							"width":(Math.ceil(mCSB_container[0].getBoundingClientRect().right+0.4)-Math.floor(mCSB_container[0].getBoundingClientRect().left)),
							"min-width":"100%",
							"position":"relative"
						}).unwrap();
				}
			}
		},
		/* -------------------- */
		
		
		/* adds scrollbar buttons */
		_scrollButtons=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				mCSB_scrollTools=$(".mCSB_"+d.idx+"_scrollbar:first"),
				tabindex=!_isNumeric(o.scrollButtons.tabindex) ? "" : "tabindex='"+o.scrollButtons.tabindex+"'",
				btnHTML=[
					"<a href='#' class='"+classes[13]+"' "+tabindex+" />",
					"<a href='#' class='"+classes[14]+"' "+tabindex+" />",
					"<a href='#' class='"+classes[15]+"' "+tabindex+" />",
					"<a href='#' class='"+classes[16]+"' "+tabindex+" />"
				],
				btn=[(o.axis==="x" ? btnHTML[2] : btnHTML[0]),(o.axis==="x" ? btnHTML[3] : btnHTML[1]),btnHTML[2],btnHTML[3]];
			if(o.scrollButtons.enable){
				mCSB_scrollTools.prepend(btn[0]).append(btn[1]).next(".mCSB_scrollTools").prepend(btn[2]).append(btn[3]);
			}
		},
		/* -------------------- */
		
		
		/* auto-adjusts scrollbar dragger length */
		_setDraggerLength=function(){
			var $this=$(this),d=$this.data(pluginPfx),
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")],
				ratio=[mCustomScrollBox.height()/mCSB_container.outerHeight(false),mCustomScrollBox.width()/mCSB_container.outerWidth(false)],
				l=[
					parseInt(mCSB_dragger[0].css("min-height")),Math.round(ratio[0]*mCSB_dragger[0].parent().height()),
					parseInt(mCSB_dragger[1].css("min-width")),Math.round(ratio[1]*mCSB_dragger[1].parent().width())
				],
				h=oldIE && (l[1]<l[0]) ? l[0] : l[1],w=oldIE && (l[3]<l[2]) ? l[2] : l[3];
			mCSB_dragger[0].css({
				"height":h,"max-height":(mCSB_dragger[0].parent().height()-10)
			}).find(".mCSB_dragger_bar").css({"line-height":l[0]+"px"});
			mCSB_dragger[1].css({
				"width":w,"max-width":(mCSB_dragger[1].parent().width()-10)
			});
		},
		/* -------------------- */
		
		
		/* calculates scrollbar to content ratio */
		_scrollRatio=function(){
			var $this=$(this),d=$this.data(pluginPfx),
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")],
				scrollAmount=[mCSB_container.outerHeight(false)-mCustomScrollBox.height(),mCSB_container.outerWidth(false)-mCustomScrollBox.width()],
				ratio=[
					scrollAmount[0]/(mCSB_dragger[0].parent().height()-mCSB_dragger[0].height()),
					scrollAmount[1]/(mCSB_dragger[1].parent().width()-mCSB_dragger[1].width())
				];
			d.scrollRatio={y:ratio[0],x:ratio[1]};
		},
		/* -------------------- */
		
		
		/* toggles scrolling classes */
		_onDragClasses=function(el,action,xpnd){
			var expandClass=xpnd ? classes[0]+"_expanded" : "",
				scrollbar=el.closest(".mCSB_scrollTools");
			if(action==="active"){
				el.toggleClass(classes[0]+" "+expandClass); scrollbar.toggleClass(classes[1]); 
				el[0]._draggable=el[0]._draggable ? 0 : 1;
			}else{
				if(!el[0]._draggable){
					if(action==="hide"){
						el.removeClass(classes[0]); scrollbar.removeClass(classes[1]);
					}else{
						el.addClass(classes[0]); scrollbar.addClass(classes[1]);
					}
				}
			}
		},
		/* -------------------- */
		
		
		/* checks if content overflows its container to determine if scrolling is required */
		_overflowed=function(){
			var $this=$(this),d=$this.data(pluginPfx),
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				contentHeight=d.overflowed==null ? mCSB_container.height() : mCSB_container.outerHeight(false),
				contentWidth=d.overflowed==null ? mCSB_container.width() : mCSB_container.outerWidth(false),
				h=mCSB_container[0].scrollHeight,w=mCSB_container[0].scrollWidth;
			if(h>contentHeight){contentHeight=h;}
			if(w>contentWidth){contentWidth=w;}
			return [contentHeight>mCustomScrollBox.height(),contentWidth>mCustomScrollBox.width()];
		},
		/* -------------------- */
		
		
		/* resets content position to 0 */
		_resetContentPosition=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")];
			_stop($this); /* stop any current scrolling before resetting */
			if((o.axis!=="x" && !d.overflowed[0]) || (o.axis==="y" && d.overflowed[0])){ /* reset y */
				mCSB_dragger[0].add(mCSB_container).css("top",0);
				_scrollTo($this,"_resetY");
			}
			if((o.axis!=="y" && !d.overflowed[1]) || (o.axis==="x" && d.overflowed[1])){ /* reset x */
				var cx=dx=0;
				if(d.langDir==="rtl"){ /* adjust left position for rtl direction */
					cx=mCustomScrollBox.width()-mCSB_container.outerWidth(false);
					dx=Math.abs(cx/d.scrollRatio.x);
				}
				mCSB_container.css("left",cx);
				mCSB_dragger[1].css("left",dx);
				_scrollTo($this,"_resetX");
			}
		},
		/* -------------------- */
		
		
		/* binds scrollbar events */
		_bindEvents=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt;
			if(!d.bindEvents){ /* check if events are already bound */
				_draggable.call(this);
				if(o.contentTouchScroll){_contentDraggable.call(this);}
				_selectable.call(this);
				if(o.mouseWheel.enable){ /* bind mousewheel fn when plugin is available */
					function _mwt(){
						mousewheelTimeout=setTimeout(function(){
							if(!$.event.special.mousewheel){
								_mwt();
							}else{
								clearTimeout(mousewheelTimeout);
								_mousewheel.call($this[0]);
							}
						},100);
					}
					var mousewheelTimeout;
					_mwt();
				}
				_draggerRail.call(this);
				_wrapperScroll.call(this);
				if(o.advanced.autoScrollOnFocus){_focus.call(this);}
				if(o.scrollButtons.enable){_buttons.call(this);}
				if(o.keyboard.enable){_keyboard.call(this);}
				d.bindEvents=true;
			}
		},
		/* -------------------- */
		
		
		/* unbinds scrollbar events */
		_unbindEvents=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				namespace=pluginPfx+"_"+d.idx,
				sb=".mCSB_"+d.idx+"_scrollbar",
				sel=$("#mCSB_"+d.idx+",#mCSB_"+d.idx+"_container,#mCSB_"+d.idx+"_container_wrapper,"+sb+" ."+classes[12]+",#mCSB_"+d.idx+"_dragger_vertical,#mCSB_"+d.idx+"_dragger_horizontal,"+sb+">a"),
				mCSB_container=$("#mCSB_"+d.idx+"_container");
			if(o.advanced.releaseDraggableSelectors){sel.add($(o.advanced.releaseDraggableSelectors));}
			if(o.advanced.extraDraggableSelectors){sel.add($(o.advanced.extraDraggableSelectors));}
			if(d.bindEvents){ /* check if events are bound */
				/* unbind namespaced events from document/selectors */
				$(document).add($(!_canAccessIFrame() || top.document)).unbind("."+namespace);
				sel.each(function(){
					$(this).unbind("."+namespace);
				});
				/* clear and delete timeouts/objects */
				clearTimeout($this[0]._focusTimeout); _delete($this[0],"_focusTimeout");
				clearTimeout(d.sequential.step); _delete(d.sequential,"step");
				clearTimeout(mCSB_container[0].onCompleteTimeout); _delete(mCSB_container[0],"onCompleteTimeout");
				d.bindEvents=false;
			}
		},
		/* -------------------- */
		
		
		/* toggles scrollbar visibility */
		_scrollbarVisibility=function(disabled){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				contentWrapper=$("#mCSB_"+d.idx+"_container_wrapper"),
				content=contentWrapper.length ? contentWrapper : $("#mCSB_"+d.idx+"_container"),
				scrollbar=[$("#mCSB_"+d.idx+"_scrollbar_vertical"),$("#mCSB_"+d.idx+"_scrollbar_horizontal")],
				mCSB_dragger=[scrollbar[0].find(".mCSB_dragger"),scrollbar[1].find(".mCSB_dragger")];
			if(o.axis!=="x"){
				if(d.overflowed[0] && !disabled){
					scrollbar[0].add(mCSB_dragger[0]).add(scrollbar[0].children("a")).css("display","block");
					content.removeClass(classes[8]+" "+classes[10]);
				}else{
					if(o.alwaysShowScrollbar){
						if(o.alwaysShowScrollbar!==2){mCSB_dragger[0].css("display","none");}
						content.removeClass(classes[10]);
					}else{
						scrollbar[0].css("display","none");
						content.addClass(classes[10]);
					}
					content.addClass(classes[8]);
				}
			}
			if(o.axis!=="y"){
				if(d.overflowed[1] && !disabled){
					scrollbar[1].add(mCSB_dragger[1]).add(scrollbar[1].children("a")).css("display","block");
					content.removeClass(classes[9]+" "+classes[11]);
				}else{
					if(o.alwaysShowScrollbar){
						if(o.alwaysShowScrollbar!==2){mCSB_dragger[1].css("display","none");}
						content.removeClass(classes[11]);
					}else{
						scrollbar[1].css("display","none");
						content.addClass(classes[11]);
					}
					content.addClass(classes[9]);
				}
			}
			if(!d.overflowed[0] && !d.overflowed[1]){
				$this.addClass(classes[5]);
			}else{
				$this.removeClass(classes[5]);
			}
		},
		/* -------------------- */
		
		
		/* returns input coordinates of pointer, touch and mouse events (relative to document) */
		_coordinates=function(e){
			var t=e.type,o=e.target.ownerDocument!==document && frameElement!==null ? [$(frameElement).offset().top,$(frameElement).offset().left] : null,
				io=_canAccessIFrame() && e.target.ownerDocument!==top.document && frameElement!==null ? [$(e.view.frameElement).offset().top,$(e.view.frameElement).offset().left] : [0,0];
			switch(t){
				case "pointerdown": case "MSPointerDown": case "pointermove": case "MSPointerMove": case "pointerup": case "MSPointerUp":
					return o ? [e.originalEvent.pageY-o[0]+io[0],e.originalEvent.pageX-o[1]+io[1],false] : [e.originalEvent.pageY,e.originalEvent.pageX,false];
					break;
				case "touchstart": case "touchmove": case "touchend":
					var touch=e.originalEvent.touches[0] || e.originalEvent.changedTouches[0],
						touches=e.originalEvent.touches.length || e.originalEvent.changedTouches.length;
					return e.target.ownerDocument!==document ? [touch.screenY,touch.screenX,touches>1] : [touch.pageY,touch.pageX,touches>1];
					break;
				default:
					return o ? [e.pageY-o[0]+io[0],e.pageX-o[1]+io[1],false] : [e.pageY,e.pageX,false];
			}
		},
		/* -------------------- */
		
		
		/* 
		SCROLLBAR DRAG EVENTS
		scrolls content via scrollbar dragging 
		*/
		_draggable=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				namespace=pluginPfx+"_"+d.idx,
				draggerId=["mCSB_"+d.idx+"_dragger_vertical","mCSB_"+d.idx+"_dragger_horizontal"],
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				mCSB_dragger=$("#"+draggerId[0]+",#"+draggerId[1]),
				draggable,dragY,dragX,
				rds=o.advanced.releaseDraggableSelectors ? mCSB_dragger.add($(o.advanced.releaseDraggableSelectors)) : mCSB_dragger,
				eds=o.advanced.extraDraggableSelectors ? $(!_canAccessIFrame() || top.document).add($(o.advanced.extraDraggableSelectors)) : $(!_canAccessIFrame() || top.document);
			mCSB_dragger.bind("contextmenu."+namespace,function(e){
				e.preventDefault(); //prevent right click
			}).bind("mousedown."+namespace+" touchstart."+namespace+" pointerdown."+namespace+" MSPointerDown."+namespace,function(e){
				e.stopImmediatePropagation();
				e.preventDefault();
				if(!_mouseBtnLeft(e)){return;} /* left mouse button only */
				touchActive=true;
				if(oldIE){document.onselectstart=function(){return false;}} /* disable text selection for IE < 9 */
				_iframe.call(mCSB_container,false); /* enable scrollbar dragging over iframes by disabling their events */
				_stop($this);
				draggable=$(this);
				var offset=draggable.offset(),y=_coordinates(e)[0]-offset.top,x=_coordinates(e)[1]-offset.left,
					h=draggable.height()+offset.top,w=draggable.width()+offset.left;
				if(y<h && y>0 && x<w && x>0){
					dragY=y; 
					dragX=x;
				}
				_onDragClasses(draggable,"active",o.autoExpandScrollbar); 
			}).bind("touchmove."+namespace,function(e){
				e.stopImmediatePropagation();
				e.preventDefault();
				var offset=draggable.offset(),y=_coordinates(e)[0]-offset.top,x=_coordinates(e)[1]-offset.left;
				_drag(dragY,dragX,y,x);
			});
			$(document).add(eds).bind("mousemove."+namespace+" pointermove."+namespace+" MSPointerMove."+namespace,function(e){
				if(draggable){
					var offset=draggable.offset(),y=_coordinates(e)[0]-offset.top,x=_coordinates(e)[1]-offset.left;
					if(dragY===y && dragX===x){return;} /* has it really moved? */
					_drag(dragY,dragX,y,x);
				}
			}).add(rds).bind("mouseup."+namespace+" touchend."+namespace+" pointerup."+namespace+" MSPointerUp."+namespace,function(e){
				if(draggable){
					_onDragClasses(draggable,"active",o.autoExpandScrollbar); 
					draggable=null;
				}
				touchActive=false;
				if(oldIE){document.onselectstart=null;} /* enable text selection for IE < 9 */
				_iframe.call(mCSB_container,true); /* enable iframes events */
			});
			function _drag(dragY,dragX,y,x){
				mCSB_container[0].idleTimer=o.scrollInertia<233 ? 250 : 0;
				if(draggable.attr("id")===draggerId[1]){
					var dir="x",to=((draggable[0].offsetLeft-dragX)+x)*d.scrollRatio.x;
				}else{
					var dir="y",to=((draggable[0].offsetTop-dragY)+y)*d.scrollRatio.y;
				}
				_scrollTo($this,to.toString(),{dir:dir,drag:true});
			}
		},
		/* -------------------- */
		
		
		/* 
		TOUCH SWIPE EVENTS
		scrolls content via touch swipe 
		Emulates the native touch-swipe scrolling with momentum found in iOS, Android and WP devices 
		*/
		_contentDraggable=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				namespace=pluginPfx+"_"+d.idx,
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")],
				draggable,dragY,dragX,touchStartY,touchStartX,touchMoveY=[],touchMoveX=[],startTime,runningTime,endTime,distance,speed,amount,
				durA=0,durB,overwrite=o.axis==="yx" ? "none" : "all",touchIntent=[],touchDrag,docDrag,
				iframe=mCSB_container.find("iframe"),
				events=[
					"touchstart."+namespace+" pointerdown."+namespace+" MSPointerDown."+namespace, //start
					"touchmove."+namespace+" pointermove."+namespace+" MSPointerMove."+namespace, //move
					"touchend."+namespace+" pointerup."+namespace+" MSPointerUp."+namespace //end
				],
				touchAction=document.body.style.touchAction!==undefined && document.body.style.touchAction!=="";
			mCSB_container.bind(events[0],function(e){
				_onTouchstart(e);
			}).bind(events[1],function(e){
				_onTouchmove(e);
			});
			mCustomScrollBox.bind(events[0],function(e){
				_onTouchstart2(e);
			}).bind(events[2],function(e){
				_onTouchend(e);
			});
			if(iframe.length){
				iframe.each(function(){
					$(this).bind("load",function(){
						/* bind events on accessible iframes */
						if(_canAccessIFrame(this)){
							$(this.contentDocument || this.contentWindow.document).bind(events[0],function(e){
								_onTouchstart(e);
								_onTouchstart2(e);
							}).bind(events[1],function(e){
								_onTouchmove(e);
							}).bind(events[2],function(e){
								_onTouchend(e);
							});
						}
					});
				});
			}
			function _onTouchstart(e){
				if(!_pointerTouch(e) || touchActive || _coordinates(e)[2]){touchable=0; return;}
				touchable=1; touchDrag=0; docDrag=0; draggable=1;
				$this.removeClass("mCS_touch_action");
				var offset=mCSB_container.offset();
				dragY=_coordinates(e)[0]-offset.top;
				dragX=_coordinates(e)[1]-offset.left;
				touchIntent=[_coordinates(e)[0],_coordinates(e)[1]];
			}
			function _onTouchmove(e){
				if(!_pointerTouch(e) || touchActive || _coordinates(e)[2]){return;}
				if(!o.documentTouchScroll){e.preventDefault();} 
				e.stopImmediatePropagation();
				if(docDrag && !touchDrag){return;}
				if(draggable){
					runningTime=_getTime();
					var offset=mCustomScrollBox.offset(),y=_coordinates(e)[0]-offset.top,x=_coordinates(e)[1]-offset.left,
						easing="mcsLinearOut";
					touchMoveY.push(y);
					touchMoveX.push(x);
					touchIntent[2]=Math.abs(_coordinates(e)[0]-touchIntent[0]); touchIntent[3]=Math.abs(_coordinates(e)[1]-touchIntent[1]);
					if(d.overflowed[0]){
						var limit=mCSB_dragger[0].parent().height()-mCSB_dragger[0].height(),
							prevent=((dragY-y)>0 && (y-dragY)>-(limit*d.scrollRatio.y) && (touchIntent[3]*2<touchIntent[2] || o.axis==="yx"));
					}
					if(d.overflowed[1]){
						var limitX=mCSB_dragger[1].parent().width()-mCSB_dragger[1].width(),
							preventX=((dragX-x)>0 && (x-dragX)>-(limitX*d.scrollRatio.x) && (touchIntent[2]*2<touchIntent[3] || o.axis==="yx"));
					}
					if(prevent || preventX){ /* prevent native document scrolling */
						if(!touchAction){e.preventDefault();} 
						touchDrag=1;
					}else{
						docDrag=1;
						$this.addClass("mCS_touch_action");
					}
					if(touchAction){e.preventDefault();} 
					amount=o.axis==="yx" ? [(dragY-y),(dragX-x)] : o.axis==="x" ? [null,(dragX-x)] : [(dragY-y),null];
					mCSB_container[0].idleTimer=250;
					if(d.overflowed[0]){_drag(amount[0],durA,easing,"y","all",true);}
					if(d.overflowed[1]){_drag(amount[1],durA,easing,"x",overwrite,true);}
				}
			}
			function _onTouchstart2(e){
				if(!_pointerTouch(e) || touchActive || _coordinates(e)[2]){touchable=0; return;}
				touchable=1;
				e.stopImmediatePropagation();
				_stop($this);
				startTime=_getTime();
				var offset=mCustomScrollBox.offset();
				touchStartY=_coordinates(e)[0]-offset.top;
				touchStartX=_coordinates(e)[1]-offset.left;
				touchMoveY=[]; touchMoveX=[];
			}
			function _onTouchend(e){
				if(!_pointerTouch(e) || touchActive || _coordinates(e)[2]){return;}
				draggable=0;
				e.stopImmediatePropagation();
				touchDrag=0; docDrag=0;
				endTime=_getTime();
				var offset=mCustomScrollBox.offset(),y=_coordinates(e)[0]-offset.top,x=_coordinates(e)[1]-offset.left;
				if((endTime-runningTime)>30){return;}
				speed=1000/(endTime-startTime);
				var easing="mcsEaseOut",slow=speed<2.5,
					diff=slow ? [touchMoveY[touchMoveY.length-2],touchMoveX[touchMoveX.length-2]] : [0,0];
				distance=slow ? [(y-diff[0]),(x-diff[1])] : [y-touchStartY,x-touchStartX];
				var absDistance=[Math.abs(distance[0]),Math.abs(distance[1])];
				speed=slow ? [Math.abs(distance[0]/4),Math.abs(distance[1]/4)] : [speed,speed];
				var a=[
					Math.abs(mCSB_container[0].offsetTop)-(distance[0]*_m((absDistance[0]/speed[0]),speed[0])),
					Math.abs(mCSB_container[0].offsetLeft)-(distance[1]*_m((absDistance[1]/speed[1]),speed[1]))
				];
				amount=o.axis==="yx" ? [a[0],a[1]] : o.axis==="x" ? [null,a[1]] : [a[0],null];
				durB=[(absDistance[0]*4)+o.scrollInertia,(absDistance[1]*4)+o.scrollInertia];
				var md=parseInt(o.contentTouchScroll) || 0; /* absolute minimum distance required */
				amount[0]=absDistance[0]>md ? amount[0] : 0;
				amount[1]=absDistance[1]>md ? amount[1] : 0;
				if(d.overflowed[0]){_drag(amount[0],durB[0],easing,"y",overwrite,false);}
				if(d.overflowed[1]){_drag(amount[1],durB[1],easing,"x",overwrite,false);}
			}
			function _m(ds,s){
				var r=[s*1.5,s*2,s/1.5,s/2];
				if(ds>90){
					return s>4 ? r[0] : r[3];
				}else if(ds>60){
					return s>3 ? r[3] : r[2];
				}else if(ds>30){
					return s>8 ? r[1] : s>6 ? r[0] : s>4 ? s : r[2];
				}else{
					return s>8 ? s : r[3];
				}
			}
			function _drag(amount,dur,easing,dir,overwrite,drag){
				if(!amount){return;}
				_scrollTo($this,amount.toString(),{dur:dur,scrollEasing:easing,dir:dir,overwrite:overwrite,drag:drag});
			}
		},
		/* -------------------- */
		
		
		/* 
		SELECT TEXT EVENTS 
		scrolls content when text is selected 
		*/
		_selectable=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,seq=d.sequential,
				namespace=pluginPfx+"_"+d.idx,
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent(),
				action;
			mCSB_container.bind("mousedown."+namespace,function(e){
				if(touchable){return;}
				if(!action){action=1; touchActive=true;}
			}).add(document).bind("mousemove."+namespace,function(e){
				if(!touchable && action && _sel()){
					var offset=mCSB_container.offset(),
						y=_coordinates(e)[0]-offset.top+mCSB_container[0].offsetTop,x=_coordinates(e)[1]-offset.left+mCSB_container[0].offsetLeft;
					if(y>0 && y<wrapper.height() && x>0 && x<wrapper.width()){
						if(seq.step){_seq("off",null,"stepped");}
					}else{
						if(o.axis!=="x" && d.overflowed[0]){
							if(y<0){
								_seq("on",38);
							}else if(y>wrapper.height()){
								_seq("on",40);
							}
						}
						if(o.axis!=="y" && d.overflowed[1]){
							if(x<0){
								_seq("on",37);
							}else if(x>wrapper.width()){
								_seq("on",39);
							}
						}
					}
				}
			}).bind("mouseup."+namespace+" dragend."+namespace,function(e){
				if(touchable){return;}
				if(action){action=0; _seq("off",null);}
				touchActive=false;
			});
			function _sel(){
				return 	window.getSelection ? window.getSelection().toString() : 
						document.selection && document.selection.type!="Control" ? document.selection.createRange().text : 0;
			}
			function _seq(a,c,s){
				seq.type=s && action ? "stepped" : "stepless";
				seq.scrollAmount=10;
				_sequentialScroll($this,a,c,"mcsLinearOut",s ? 60 : null);
			}
		},
		/* -------------------- */
		
		
		/* 
		MOUSE WHEEL EVENT
		scrolls content via mouse-wheel 
		via mouse-wheel plugin (https://github.com/brandonaaron/jquery-mousewheel)
		*/
		_mousewheel=function(){
			if(!$(this).data(pluginPfx)){return;} /* Check if the scrollbar is ready to use mousewheel events (issue: #185) */
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				namespace=pluginPfx+"_"+d.idx,
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_dragger=[$("#mCSB_"+d.idx+"_dragger_vertical"),$("#mCSB_"+d.idx+"_dragger_horizontal")],
				iframe=$("#mCSB_"+d.idx+"_container").find("iframe");
			if(iframe.length){
				iframe.each(function(){
					$(this).bind("load",function(){
						/* bind events on accessible iframes */
						if(_canAccessIFrame(this)){
							$(this.contentDocument || this.contentWindow.document).bind("mousewheel."+namespace,function(e,delta){
								_onMousewheel(e,delta);
							});
						}
					});
				});
			}
			mCustomScrollBox.bind("mousewheel."+namespace,function(e,delta){
				_onMousewheel(e,delta);
			});
			function _onMousewheel(e,delta){
				_stop($this);
				if(_disableMousewheel($this,e.target)){return;} /* disables mouse-wheel when hovering specific elements */
				var deltaFactor=o.mouseWheel.deltaFactor!=="auto" ? parseInt(o.mouseWheel.deltaFactor) : (oldIE && e.deltaFactor<100) ? 100 : e.deltaFactor || 100,
					dur=o.scrollInertia;
				if(o.axis==="x" || o.mouseWheel.axis==="x"){
					var dir="x",
						px=[Math.round(deltaFactor*d.scrollRatio.x),parseInt(o.mouseWheel.scrollAmount)],
						amount=o.mouseWheel.scrollAmount!=="auto" ? px[1] : px[0]>=mCustomScrollBox.width() ? mCustomScrollBox.width()*0.9 : px[0],
						contentPos=Math.abs($("#mCSB_"+d.idx+"_container")[0].offsetLeft),
						draggerPos=mCSB_dragger[1][0].offsetLeft,
						limit=mCSB_dragger[1].parent().width()-mCSB_dragger[1].width(),
						dlt=o.mouseWheel.axis==="y" ? (e.deltaY || delta) : e.deltaX;
				}else{
					var dir="y",
						px=[Math.round(deltaFactor*d.scrollRatio.y),parseInt(o.mouseWheel.scrollAmount)],
						amount=o.mouseWheel.scrollAmount!=="auto" ? px[1] : px[0]>=mCustomScrollBox.height() ? mCustomScrollBox.height()*0.9 : px[0],
						contentPos=Math.abs($("#mCSB_"+d.idx+"_container")[0].offsetTop),
						draggerPos=mCSB_dragger[0][0].offsetTop,
						limit=mCSB_dragger[0].parent().height()-mCSB_dragger[0].height(),
						dlt=e.deltaY || delta;
				}
				if((dir==="y" && !d.overflowed[0]) || (dir==="x" && !d.overflowed[1])){return;}
				if(o.mouseWheel.invert || e.webkitDirectionInvertedFromDevice){dlt=-dlt;}
				if(o.mouseWheel.normalizeDelta){dlt=dlt<0 ? -1 : 1;}
				if((dlt>0 && draggerPos!==0) || (dlt<0 && draggerPos!==limit) || o.mouseWheel.preventDefault){
					e.stopImmediatePropagation();
					e.preventDefault();
				}
				if(e.deltaFactor<5 && !o.mouseWheel.normalizeDelta){
					//very low deltaFactor values mean some kind of delta acceleration (e.g. osx trackpad), so adjusting scrolling accordingly
					amount=e.deltaFactor; dur=17;
				}
				_scrollTo($this,(contentPos-(dlt*amount)).toString(),{dir:dir,dur:dur});
			}
		},
		/* -------------------- */
		
		
		/* checks if iframe can be accessed */
		_canAccessIFrameCache=new Object(),
		_canAccessIFrame=function(iframe){
		    var result=false,cacheKey=false,html=null;
		    if(iframe===undefined){
				cacheKey="#empty";
		    }else if($(iframe).attr("id")!==undefined){
				cacheKey=$(iframe).attr("id");
		    }
			if(cacheKey!==false && _canAccessIFrameCache[cacheKey]!==undefined){
				return _canAccessIFrameCache[cacheKey];
			}
			if(!iframe){
				try{
					var doc=top.document;
					html=doc.body.innerHTML;
				}catch(err){/* do nothing */}
				result=(html!==null);
			}else{
				try{
					var doc=iframe.contentDocument || iframe.contentWindow.document;
					html=doc.body.innerHTML;
				}catch(err){/* do nothing */}
				result=(html!==null);
			}
			if(cacheKey!==false){_canAccessIFrameCache[cacheKey]=result;}
			return result;
		},
		/* -------------------- */
		
		
		/* switches iframe's pointer-events property (drag, mousewheel etc. over cross-domain iframes) */
		_iframe=function(evt){
			var el=this.find("iframe");
			if(!el.length){return;} /* check if content contains iframes */
			var val=!evt ? "none" : "auto";
			el.css("pointer-events",val); /* for IE11, iframe's display property should not be "block" */
		},
		/* -------------------- */
		
		
		/* disables mouse-wheel when hovering specific elements like select, datalist etc. */
		_disableMousewheel=function(el,target){
			var tag=target.nodeName.toLowerCase(),
				tags=el.data(pluginPfx).opt.mouseWheel.disableOver,
				/* elements that require focus */
				focusTags=["select","textarea"];
			return $.inArray(tag,tags) > -1 && !($.inArray(tag,focusTags) > -1 && !$(target).is(":focus"));
		},
		/* -------------------- */
		
		
		/* 
		DRAGGER RAIL CLICK EVENT
		scrolls content via dragger rail 
		*/
		_draggerRail=function(){
			var $this=$(this),d=$this.data(pluginPfx),
				namespace=pluginPfx+"_"+d.idx,
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent(),
				mCSB_draggerContainer=$(".mCSB_"+d.idx+"_scrollbar ."+classes[12]),
				clickable;
			mCSB_draggerContainer.bind("mousedown."+namespace+" touchstart."+namespace+" pointerdown."+namespace+" MSPointerDown."+namespace,function(e){
				touchActive=true;
				if(!$(e.target).hasClass("mCSB_dragger")){clickable=1;}
			}).bind("touchend."+namespace+" pointerup."+namespace+" MSPointerUp."+namespace,function(e){
				touchActive=false;
			}).bind("click."+namespace,function(e){
				if(!clickable){return;}
				clickable=0;
				if($(e.target).hasClass(classes[12]) || $(e.target).hasClass("mCSB_draggerRail")){
					_stop($this);
					var el=$(this),mCSB_dragger=el.find(".mCSB_dragger");
					if(el.parent(".mCSB_scrollTools_horizontal").length>0){
						if(!d.overflowed[1]){return;}
						var dir="x",
							clickDir=e.pageX>mCSB_dragger.offset().left ? -1 : 1,
							to=Math.abs(mCSB_container[0].offsetLeft)-(clickDir*(wrapper.width()*0.9));
					}else{
						if(!d.overflowed[0]){return;}
						var dir="y",
							clickDir=e.pageY>mCSB_dragger.offset().top ? -1 : 1,
							to=Math.abs(mCSB_container[0].offsetTop)-(clickDir*(wrapper.height()*0.9));
					}
					_scrollTo($this,to.toString(),{dir:dir,scrollEasing:"mcsEaseInOut"});
				}
			});
		},
		/* -------------------- */
		
		
		/* 
		FOCUS EVENT
		scrolls content via element focus (e.g. clicking an input, pressing TAB key etc.)
		*/
		_focus=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				namespace=pluginPfx+"_"+d.idx,
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent();
			mCSB_container.bind("focusin."+namespace,function(e){
				var el=$(document.activeElement),
					nested=mCSB_container.find(".mCustomScrollBox").length,
					dur=0;
				if(!el.is(o.advanced.autoScrollOnFocus)){return;}
				_stop($this);
				clearTimeout($this[0]._focusTimeout);
				$this[0]._focusTimer=nested ? (dur+17)*nested : 0;
				$this[0]._focusTimeout=setTimeout(function(){
					var	to=[_childPos(el)[0],_childPos(el)[1]],
						contentPos=[mCSB_container[0].offsetTop,mCSB_container[0].offsetLeft],
						isVisible=[
							(contentPos[0]+to[0]>=0 && contentPos[0]+to[0]<wrapper.height()-el.outerHeight(false)),
							(contentPos[1]+to[1]>=0 && contentPos[0]+to[1]<wrapper.width()-el.outerWidth(false))
						],
						overwrite=(o.axis==="yx" && !isVisible[0] && !isVisible[1]) ? "none" : "all";
					if(o.axis!=="x" && !isVisible[0]){
						_scrollTo($this,to[0].toString(),{dir:"y",scrollEasing:"mcsEaseInOut",overwrite:overwrite,dur:dur});
					}
					if(o.axis!=="y" && !isVisible[1]){
						_scrollTo($this,to[1].toString(),{dir:"x",scrollEasing:"mcsEaseInOut",overwrite:overwrite,dur:dur});
					}
				},$this[0]._focusTimer);
			});
		},
		/* -------------------- */
		
		
		/* sets content wrapper scrollTop/scrollLeft always to 0 */
		_wrapperScroll=function(){
			var $this=$(this),d=$this.data(pluginPfx),
				namespace=pluginPfx+"_"+d.idx,
				wrapper=$("#mCSB_"+d.idx+"_container").parent();
			wrapper.bind("scroll."+namespace,function(e){
				if(wrapper.scrollTop()!==0 || wrapper.scrollLeft()!==0){
					$(".mCSB_"+d.idx+"_scrollbar").css("visibility","hidden"); /* hide scrollbar(s) */
				}
			});
		},
		/* -------------------- */
		
		
		/* 
		BUTTONS EVENTS
		scrolls content via up, down, left and right buttons 
		*/
		_buttons=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,seq=d.sequential,
				namespace=pluginPfx+"_"+d.idx,
				sel=".mCSB_"+d.idx+"_scrollbar",
				btn=$(sel+">a");
			btn.bind("contextmenu."+namespace,function(e){
				e.preventDefault(); //prevent right click
			}).bind("mousedown."+namespace+" touchstart."+namespace+" pointerdown."+namespace+" MSPointerDown."+namespace+" mouseup."+namespace+" touchend."+namespace+" pointerup."+namespace+" MSPointerUp."+namespace+" mouseout."+namespace+" pointerout."+namespace+" MSPointerOut."+namespace+" click."+namespace,function(e){
				e.preventDefault();
				if(!_mouseBtnLeft(e)){return;} /* left mouse button only */
				var btnClass=$(this).attr("class");
				seq.type=o.scrollButtons.scrollType;
				switch(e.type){
					case "mousedown": case "touchstart": case "pointerdown": case "MSPointerDown":
						if(seq.type==="stepped"){return;}
						touchActive=true;
						d.tweenRunning=false;
						_seq("on",btnClass);
						break;
					case "mouseup": case "touchend": case "pointerup": case "MSPointerUp":
					case "mouseout": case "pointerout": case "MSPointerOut":
						if(seq.type==="stepped"){return;}
						touchActive=false;
						if(seq.dir){_seq("off",btnClass);}
						break;
					case "click":
						if(seq.type!=="stepped" || d.tweenRunning){return;}
						_seq("on",btnClass);
						break;
				}
				function _seq(a,c){
					seq.scrollAmount=o.scrollButtons.scrollAmount;
					_sequentialScroll($this,a,c);
				}
			});
		},
		/* -------------------- */
		
		
		/* 
		KEYBOARD EVENTS
		scrolls content via keyboard 
		Keys: up arrow, down arrow, left arrow, right arrow, PgUp, PgDn, Home, End
		*/
		_keyboard=function(){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,seq=d.sequential,
				namespace=pluginPfx+"_"+d.idx,
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent(),
				editables="input,textarea,select,datalist,keygen,[contenteditable='true']",
				iframe=mCSB_container.find("iframe"),
				events=["blur."+namespace+" keydown."+namespace+" keyup."+namespace];
			if(iframe.length){
				iframe.each(function(){
					$(this).bind("load",function(){
						/* bind events on accessible iframes */
						if(_canAccessIFrame(this)){
							$(this.contentDocument || this.contentWindow.document).bind(events[0],function(e){
								_onKeyboard(e);
							});
						}
					});
				});
			}
			mCustomScrollBox.attr("tabindex","0").bind(events[0],function(e){
				_onKeyboard(e);
			});
			function _onKeyboard(e){
				switch(e.type){
					case "blur":
						if(d.tweenRunning && seq.dir){_seq("off",null);}
						break;
					case "keydown": case "keyup":
						var code=e.keyCode ? e.keyCode : e.which,action="on";
						if((o.axis!=="x" && (code===38 || code===40)) || (o.axis!=="y" && (code===37 || code===39))){
							/* up (38), down (40), left (37), right (39) arrows */
							if(((code===38 || code===40) && !d.overflowed[0]) || ((code===37 || code===39) && !d.overflowed[1])){return;}
							if(e.type==="keyup"){action="off";}
							if(!$(document.activeElement).is(editables)){
								e.preventDefault();
								e.stopImmediatePropagation();
								_seq(action,code);
							}
						}else if(code===33 || code===34){
							/* PgUp (33), PgDn (34) */
							if(d.overflowed[0] || d.overflowed[1]){
								e.preventDefault();
								e.stopImmediatePropagation();
							}
							if(e.type==="keyup"){
								_stop($this);
								var keyboardDir=code===34 ? -1 : 1;
								if(o.axis==="x" || (o.axis==="yx" && d.overflowed[1] && !d.overflowed[0])){
									var dir="x",to=Math.abs(mCSB_container[0].offsetLeft)-(keyboardDir*(wrapper.width()*0.9));
								}else{
									var dir="y",to=Math.abs(mCSB_container[0].offsetTop)-(keyboardDir*(wrapper.height()*0.9));
								}
								_scrollTo($this,to.toString(),{dir:dir,scrollEasing:"mcsEaseInOut"});
							}
						}else if(code===35 || code===36){
							/* End (35), Home (36) */
							if(!$(document.activeElement).is(editables)){
								if(d.overflowed[0] || d.overflowed[1]){
									e.preventDefault();
									e.stopImmediatePropagation();
								}
								if(e.type==="keyup"){
									if(o.axis==="x" || (o.axis==="yx" && d.overflowed[1] && !d.overflowed[0])){
										var dir="x",to=code===35 ? Math.abs(wrapper.width()-mCSB_container.outerWidth(false)) : 0;
									}else{
										var dir="y",to=code===35 ? Math.abs(wrapper.height()-mCSB_container.outerHeight(false)) : 0;
									}
									_scrollTo($this,to.toString(),{dir:dir,scrollEasing:"mcsEaseInOut"});
								}
							}
						}
						break;
				}
				function _seq(a,c){
					seq.type=o.keyboard.scrollType;
					seq.scrollAmount=o.keyboard.scrollAmount;
					if(seq.type==="stepped" && d.tweenRunning){return;}
					_sequentialScroll($this,a,c);
				}
			}
		},
		/* -------------------- */
		
		
		/* scrolls content sequentially (used when scrolling via buttons, keyboard arrows etc.) */
		_sequentialScroll=function(el,action,trigger,e,s){
			var d=el.data(pluginPfx),o=d.opt,seq=d.sequential,
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				once=seq.type==="stepped" ? true : false,
				steplessSpeed=o.scrollInertia < 26 ? 26 : o.scrollInertia, /* 26/1.5=17 */
				steppedSpeed=o.scrollInertia < 1 ? 17 : o.scrollInertia;
			switch(action){
				case "on":
					seq.dir=[
						(trigger===classes[16] || trigger===classes[15] || trigger===39 || trigger===37 ? "x" : "y"),
						(trigger===classes[13] || trigger===classes[15] || trigger===38 || trigger===37 ? -1 : 1)
					];
					_stop(el);
					if(_isNumeric(trigger) && seq.type==="stepped"){return;}
					_on(once);
					break;
				case "off":
					_off();
					if(once || (d.tweenRunning && seq.dir)){
						_on(true);
					}
					break;
			}
			
			/* starts sequence */
			function _on(once){
				if(o.snapAmount){seq.scrollAmount=!(o.snapAmount instanceof Array) ? o.snapAmount : seq.dir[0]==="x" ? o.snapAmount[1] : o.snapAmount[0];} /* scrolling snapping */
				var c=seq.type!=="stepped", /* continuous scrolling */
					t=s ? s : !once ? 1000/60 : c ? steplessSpeed/1.5 : steppedSpeed, /* timer */
					m=!once ? 2.5 : c ? 7.5 : 40, /* multiplier */
					contentPos=[Math.abs(mCSB_container[0].offsetTop),Math.abs(mCSB_container[0].offsetLeft)],
					ratio=[d.scrollRatio.y>10 ? 10 : d.scrollRatio.y,d.scrollRatio.x>10 ? 10 : d.scrollRatio.x],
					amount=seq.dir[0]==="x" ? contentPos[1]+(seq.dir[1]*(ratio[1]*m)) : contentPos[0]+(seq.dir[1]*(ratio[0]*m)),
					px=seq.dir[0]==="x" ? contentPos[1]+(seq.dir[1]*parseInt(seq.scrollAmount)) : contentPos[0]+(seq.dir[1]*parseInt(seq.scrollAmount)),
					to=seq.scrollAmount!=="auto" ? px : amount,
					easing=e ? e : !once ? "mcsLinear" : c ? "mcsLinearOut" : "mcsEaseInOut",
					onComplete=!once ? false : true;
				if(once && t<17){
					to=seq.dir[0]==="x" ? contentPos[1] : contentPos[0];
				}
				_scrollTo(el,to.toString(),{dir:seq.dir[0],scrollEasing:easing,dur:t,onComplete:onComplete});
				if(once){
					seq.dir=false;
					return;
				}
				clearTimeout(seq.step);
				seq.step=setTimeout(function(){
					_on();
				},t);
			}
			/* stops sequence */
			function _off(){
				clearTimeout(seq.step);
				_delete(seq,"step");
				_stop(el);
			}
		},
		/* -------------------- */
		
		
		/* returns a yx array from value */
		_arr=function(val){
			var o=$(this).data(pluginPfx).opt,vals=[];
			if(typeof val==="function"){val=val();} /* check if the value is a single anonymous function */
			/* check if value is object or array, its length and create an array with yx values */
			if(!(val instanceof Array)){ /* object value (e.g. {y:"100",x:"100"}, 100 etc.) */
				vals[0]=val.y ? val.y : val.x || o.axis==="x" ? null : val;
				vals[1]=val.x ? val.x : val.y || o.axis==="y" ? null : val;
			}else{ /* array value (e.g. [100,100]) */
				vals=val.length>1 ? [val[0],val[1]] : o.axis==="x" ? [null,val[0]] : [val[0],null];
			}
			/* check if array values are anonymous functions */
			if(typeof vals[0]==="function"){vals[0]=vals[0]();}
			if(typeof vals[1]==="function"){vals[1]=vals[1]();}
			return vals;
		},
		/* -------------------- */
		
		
		/* translates values (e.g. "top", 100, "100px", "#id") to actual scroll-to positions */
		_to=function(val,dir){
			if(val==null || typeof val=="undefined"){return;}
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent(),
				t=typeof val;
			if(!dir){dir=o.axis==="x" ? "x" : "y";}
			var contentLength=dir==="x" ? mCSB_container.outerWidth(false)-wrapper.width() : mCSB_container.outerHeight(false)-wrapper.height(),
				contentPos=dir==="x" ? mCSB_container[0].offsetLeft : mCSB_container[0].offsetTop,
				cssProp=dir==="x" ? "left" : "top";
			switch(t){
				case "function": /* this currently is not used. Consider removing it */
					return val();
					break;
				case "object": /* js/jquery object */
					var obj=val.jquery ? val : $(val);
					if(!obj.length){return;}
					return dir==="x" ? _childPos(obj)[1] : _childPos(obj)[0];
					break;
				case "string": case "number":
					if(_isNumeric(val)){ /* numeric value */
						return Math.abs(val);
					}else if(val.indexOf("%")!==-1){ /* percentage value */
						return Math.abs(contentLength*parseInt(val)/100);
					}else if(val.indexOf("-=")!==-1){ /* decrease value */
						return Math.abs(contentPos-parseInt(val.split("-=")[1]));
					}else if(val.indexOf("+=")!==-1){ /* inrease value */
						var p=(contentPos+parseInt(val.split("+=")[1]));
						return p>=0 ? 0 : Math.abs(p);
					}else if(val.indexOf("px")!==-1 && _isNumeric(val.split("px")[0])){ /* pixels string value (e.g. "100px") */
						return Math.abs(val.split("px")[0]);
					}else{
						if(val==="top" || val==="left"){ /* special strings */
							return 0;
						}else if(val==="bottom"){
							return Math.abs(wrapper.height()-mCSB_container.outerHeight(false));
						}else if(val==="right"){
							return Math.abs(wrapper.width()-mCSB_container.outerWidth(false));
						}else if(val==="first" || val==="last"){
							var obj=mCSB_container.find(":"+val);
							return dir==="x" ? _childPos(obj)[1] : _childPos(obj)[0];
						}else{
							if($(val).length){ /* jquery selector */
								return dir==="x" ? _childPos($(val))[1] : _childPos($(val))[0];
							}else{ /* other values (e.g. "100em") */
								mCSB_container.css(cssProp,val);
								methods.update.call(null,$this[0]);
								return;
							}
						}
					}
					break;
			}
		},
		/* -------------------- */
		
		
		/* calls the update method automatically */
		_autoUpdate=function(rem){
			var $this=$(this),d=$this.data(pluginPfx),o=d.opt,
				mCSB_container=$("#mCSB_"+d.idx+"_container");
			if(rem){
				/* 
				removes autoUpdate timer 
				usage: _autoUpdate.call(this,"remove");
				*/
				clearTimeout(mCSB_container[0].autoUpdate);
				_delete(mCSB_container[0],"autoUpdate");
				return;
			}
			upd();
			function upd(){
				clearTimeout(mCSB_container[0].autoUpdate);
				if($this.parents("html").length===0){
					/* check element in dom tree */
					$this=null;
					return;
				}
				mCSB_container[0].autoUpdate=setTimeout(function(){
					/* update on specific selector(s) length and size change */
					if(o.advanced.updateOnSelectorChange){
						d.poll.change.n=sizesSum();
						if(d.poll.change.n!==d.poll.change.o){
							d.poll.change.o=d.poll.change.n;
							doUpd(3);
							return;
						}
					}
					/* update on main element and scrollbar size changes */
					if(o.advanced.updateOnContentResize){
						d.poll.size.n=$this[0].scrollHeight+$this[0].scrollWidth+mCSB_container[0].offsetHeight+$this[0].offsetHeight+$this[0].offsetWidth;
						if(d.poll.size.n!==d.poll.size.o){
							d.poll.size.o=d.poll.size.n;
							doUpd(1);
							return;
						}
					}
					/* update on image load */
					if(o.advanced.updateOnImageLoad){
						if(!(o.advanced.updateOnImageLoad==="auto" && o.axis==="y")){ //by default, it doesn't run on vertical content
							d.poll.img.n=mCSB_container.find("img").length;
							if(d.poll.img.n!==d.poll.img.o){
								d.poll.img.o=d.poll.img.n;
								mCSB_container.find("img").each(function(){
									imgLoader(this);
								});
								return;
							}
						}
					}
					if(o.advanced.updateOnSelectorChange || o.advanced.updateOnContentResize || o.advanced.updateOnImageLoad){upd();}
				},o.advanced.autoUpdateTimeout);
			}
			/* a tiny image loader */
			function imgLoader(el){
				if($(el).hasClass(classes[2])){doUpd(); return;}
				var img=new Image();
				function createDelegate(contextObject,delegateMethod){
					return function(){return delegateMethod.apply(contextObject,arguments);}
				}
				function imgOnLoad(){
					this.onload=null;
					$(el).addClass(classes[2]);
					doUpd(2);
				}
				img.onload=createDelegate(img,imgOnLoad);
				img.src=el.src;
			}
			/* returns the total height and width sum of all elements matching the selector */
			function sizesSum(){
				if(o.advanced.updateOnSelectorChange===true){o.advanced.updateOnSelectorChange="*";}
				var total=0,sel=mCSB_container.find(o.advanced.updateOnSelectorChange);
				if(o.advanced.updateOnSelectorChange && sel.length>0){sel.each(function(){total+=this.offsetHeight+this.offsetWidth;});}
				return total;
			}
			/* calls the update method */
			function doUpd(cb){
				clearTimeout(mCSB_container[0].autoUpdate);
				methods.update.call(null,$this[0],cb);
			}
		},
		/* -------------------- */
		
		
		/* snaps scrolling to a multiple of a pixels number */
		_snapAmount=function(to,amount,offset){
			return (Math.round(to/amount)*amount-offset); 
		},
		/* -------------------- */
		
		
		/* stops content and scrollbar animations */
		_stop=function(el){
			var d=el.data(pluginPfx),
				sel=$("#mCSB_"+d.idx+"_container,#mCSB_"+d.idx+"_container_wrapper,#mCSB_"+d.idx+"_dragger_vertical,#mCSB_"+d.idx+"_dragger_horizontal");
			sel.each(function(){
				_stopTween.call(this);
			});
		},
		/* -------------------- */
		
		
		/* 
		ANIMATES CONTENT 
		This is where the actual scrolling happens
		*/
		_scrollTo=function(el,to,options){
			var d=el.data(pluginPfx),o=d.opt,
				defaults={
					trigger:"internal",
					dir:"y",
					scrollEasing:"mcsEaseOut",
					drag:false,
					dur:o.scrollInertia,
					overwrite:"all",
					callbacks:true,
					onStart:true,
					onUpdate:true,
					onComplete:true
				},
				options=$.extend(defaults,options),
				dur=[options.dur,(options.drag ? 0 : options.dur)],
				mCustomScrollBox=$("#mCSB_"+d.idx),
				mCSB_container=$("#mCSB_"+d.idx+"_container"),
				wrapper=mCSB_container.parent(),
				totalScrollOffsets=o.callbacks.onTotalScrollOffset ? _arr.call(el,o.callbacks.onTotalScrollOffset) : [0,0],
				totalScrollBackOffsets=o.callbacks.onTotalScrollBackOffset ? _arr.call(el,o.callbacks.onTotalScrollBackOffset) : [0,0];
			d.trigger=options.trigger;
			if(wrapper.scrollTop()!==0 || wrapper.scrollLeft()!==0){ /* always reset scrollTop/Left */
				$(".mCSB_"+d.idx+"_scrollbar").css("visibility","visible");
				wrapper.scrollTop(0).scrollLeft(0);
			}
			if(to==="_resetY" && !d.contentReset.y){
				/* callbacks: onOverflowYNone */
				if(_cb("onOverflowYNone")){o.callbacks.onOverflowYNone.call(el[0]);}
				d.contentReset.y=1;
			}
			if(to==="_resetX" && !d.contentReset.x){
				/* callbacks: onOverflowXNone */
				if(_cb("onOverflowXNone")){o.callbacks.onOverflowXNone.call(el[0]);}
				d.contentReset.x=1;
			}
			if(to==="_resetY" || to==="_resetX"){return;}
			if((d.contentReset.y || !el[0].mcs) && d.overflowed[0]){
				/* callbacks: onOverflowY */
				if(_cb("onOverflowY")){o.callbacks.onOverflowY.call(el[0]);}
				d.contentReset.x=null;
			}
			if((d.contentReset.x || !el[0].mcs) && d.overflowed[1]){
				/* callbacks: onOverflowX */
				if(_cb("onOverflowX")){o.callbacks.onOverflowX.call(el[0]);}
				d.contentReset.x=null;
			}
			if(o.snapAmount){ /* scrolling snapping */
				var snapAmount=!(o.snapAmount instanceof Array) ? o.snapAmount : options.dir==="x" ? o.snapAmount[1] : o.snapAmount[0];
				to=_snapAmount(to,snapAmount,o.snapOffset);
			}
			switch(options.dir){
				case "x":
					var mCSB_dragger=$("#mCSB_"+d.idx+"_dragger_horizontal"),
						property="left",
						contentPos=mCSB_container[0].offsetLeft,
						limit=[
							mCustomScrollBox.width()-mCSB_container.outerWidth(false),
							mCSB_dragger.parent().width()-mCSB_dragger.width()
						],
						scrollTo=[to,to===0 ? 0 : (to/d.scrollRatio.x)],
						tso=totalScrollOffsets[1],
						tsbo=totalScrollBackOffsets[1],
						totalScrollOffset=tso>0 ? tso/d.scrollRatio.x : 0,
						totalScrollBackOffset=tsbo>0 ? tsbo/d.scrollRatio.x : 0;
					break;
				case "y":
					var mCSB_dragger=$("#mCSB_"+d.idx+"_dragger_vertical"),
						property="top",
						contentPos=mCSB_container[0].offsetTop,
						limit=[
							mCustomScrollBox.height()-mCSB_container.outerHeight(false),
							mCSB_dragger.parent().height()-mCSB_dragger.height()
						],
						scrollTo=[to,to===0 ? 0 : (to/d.scrollRatio.y)],
						tso=totalScrollOffsets[0],
						tsbo=totalScrollBackOffsets[0],
						totalScrollOffset=tso>0 ? tso/d.scrollRatio.y : 0,
						totalScrollBackOffset=tsbo>0 ? tsbo/d.scrollRatio.y : 0;
					break;
			}
			if(scrollTo[1]<0 || (scrollTo[0]===0 && scrollTo[1]===0)){
				scrollTo=[0,0];
			}else if(scrollTo[1]>=limit[1]){
				scrollTo=[limit[0],limit[1]];
			}else{
				scrollTo[0]=-scrollTo[0];
			}
			if(!el[0].mcs){
				_mcs();  /* init mcs object (once) to make it available before callbacks */
				if(_cb("onInit")){o.callbacks.onInit.call(el[0]);} /* callbacks: onInit */
			}
			clearTimeout(mCSB_container[0].onCompleteTimeout);
			_tweenTo(mCSB_dragger[0],property,Math.round(scrollTo[1]),dur[1],options.scrollEasing);
			if(!d.tweenRunning && ((contentPos===0 && scrollTo[0]>=0) || (contentPos===limit[0] && scrollTo[0]<=limit[0]))){return;}
			_tweenTo(mCSB_container[0],property,Math.round(scrollTo[0]),dur[0],options.scrollEasing,options.overwrite,{
				onStart:function(){
					if(options.callbacks && options.onStart && !d.tweenRunning){
						/* callbacks: onScrollStart */
						if(_cb("onScrollStart")){_mcs(); o.callbacks.onScrollStart.call(el[0]);}
						d.tweenRunning=true;
						_onDragClasses(mCSB_dragger);
						d.cbOffsets=_cbOffsets();
					}
				},onUpdate:function(){
					if(options.callbacks && options.onUpdate){
						/* callbacks: whileScrolling */
						if(_cb("whileScrolling")){_mcs(); o.callbacks.whileScrolling.call(el[0]);}
					}
				},onComplete:function(){
					if(options.callbacks && options.onComplete){
						if(o.axis==="yx"){clearTimeout(mCSB_container[0].onCompleteTimeout);}
						var t=mCSB_container[0].idleTimer || 0;
						mCSB_container[0].onCompleteTimeout=setTimeout(function(){
							/* callbacks: onScroll, onTotalScroll, onTotalScrollBack */
							if(_cb("onScroll")){_mcs(); o.callbacks.onScroll.call(el[0]);}
							if(_cb("onTotalScroll") && scrollTo[1]>=limit[1]-totalScrollOffset && d.cbOffsets[0]){_mcs(); o.callbacks.onTotalScroll.call(el[0]);}
							if(_cb("onTotalScrollBack") && scrollTo[1]<=totalScrollBackOffset && d.cbOffsets[1]){_mcs(); o.callbacks.onTotalScrollBack.call(el[0]);}
							d.tweenRunning=false;
							mCSB_container[0].idleTimer=0;
							_onDragClasses(mCSB_dragger,"hide");
						},t);
					}
				}
			});
			/* checks if callback function exists */
			function _cb(cb){
				return d && o.callbacks[cb] && typeof o.callbacks[cb]==="function";
			}
			/* checks whether callback offsets always trigger */
			function _cbOffsets(){
				return [o.callbacks.alwaysTriggerOffsets || contentPos>=limit[0]+tso,o.callbacks.alwaysTriggerOffsets || contentPos<=-tsbo];
			}
			/* 
			populates object with useful values for the user 
			values: 
				content: this.mcs.content
				content top position: this.mcs.top 
				content left position: this.mcs.left 
				dragger top position: this.mcs.draggerTop 
				dragger left position: this.mcs.draggerLeft 
				scrolling y percentage: this.mcs.topPct 
				scrolling x percentage: this.mcs.leftPct 
				scrolling direction: this.mcs.direction
			*/
			function _mcs(){
				var cp=[mCSB_container[0].offsetTop,mCSB_container[0].offsetLeft], /* content position */
					dp=[mCSB_dragger[0].offsetTop,mCSB_dragger[0].offsetLeft], /* dragger position */
					cl=[mCSB_container.outerHeight(false),mCSB_container.outerWidth(false)], /* content length */
					pl=[mCustomScrollBox.height(),mCustomScrollBox.width()]; /* content parent length */
				el[0].mcs={
					content:mCSB_container, /* original content wrapper as jquery object */
					top:cp[0],left:cp[1],draggerTop:dp[0],draggerLeft:dp[1],
					topPct:Math.round((100*Math.abs(cp[0]))/(Math.abs(cl[0])-pl[0])),leftPct:Math.round((100*Math.abs(cp[1]))/(Math.abs(cl[1])-pl[1])),
					direction:options.dir
				};
				/* 
				this refers to the original element containing the scrollbar(s)
				usage: this.mcs.top, this.mcs.leftPct etc. 
				*/
			}
		},
		/* -------------------- */
		
		
		/* 
		CUSTOM JAVASCRIPT ANIMATION TWEEN 
		Lighter and faster than jquery animate() and css transitions 
		Animates top/left properties and includes easings 
		*/
		_tweenTo=function(el,prop,to,duration,easing,overwrite,callbacks){
			if(!el._mTween){el._mTween={top:{},left:{}};}
			var callbacks=callbacks || {},
				onStart=callbacks.onStart || function(){},onUpdate=callbacks.onUpdate || function(){},onComplete=callbacks.onComplete || function(){},
				startTime=_getTime(),_delay,progress=0,from=el.offsetTop,elStyle=el.style,_request,tobj=el._mTween[prop];
			if(prop==="left"){from=el.offsetLeft;}
			var diff=to-from;
			tobj.stop=0;
			if(overwrite!=="none"){_cancelTween();}
			_startTween();
			function _step(){
				if(tobj.stop){return;}
				if(!progress){onStart.call();}
				progress=_getTime()-startTime;
				_tween();
				if(progress>=tobj.time){
					tobj.time=(progress>tobj.time) ? progress+_delay-(progress-tobj.time) : progress+_delay-1;
					if(tobj.time<progress+1){tobj.time=progress+1;}
				}
				if(tobj.time<duration){tobj.id=_request(_step);}else{onComplete.call();}
			}
			function _tween(){
				if(duration>0){
					tobj.currVal=_ease(tobj.time,from,diff,duration,easing);
					elStyle[prop]=Math.round(tobj.currVal)+"px";
				}else{
					elStyle[prop]=to+"px";
				}
				onUpdate.call();
			}
			function _startTween(){
				_delay=1000/60;
				tobj.time=progress+_delay;
				_request=(!window.requestAnimationFrame) ? function(f){_tween(); return setTimeout(f,0.01);} : window.requestAnimationFrame;
				tobj.id=_request(_step);
			}
			function _cancelTween(){
				if(tobj.id==null){return;}
				if(!window.requestAnimationFrame){clearTimeout(tobj.id);
				}else{window.cancelAnimationFrame(tobj.id);}
				tobj.id=null;
			}
			function _ease(t,b,c,d,type){
				switch(type){
					case "linear": case "mcsLinear":
						return c*t/d + b;
						break;
					case "mcsLinearOut":
						t/=d; t--; return c * Math.sqrt(1 - t*t) + b;
						break;
					case "easeInOutSmooth":
						t/=d/2;
						if(t<1) return c/2*t*t + b;
						t--;
						return -c/2 * (t*(t-2) - 1) + b;
						break;
					case "easeInOutStrong":
						t/=d/2;
						if(t<1) return c/2 * Math.pow( 2, 10 * (t - 1) ) + b;
						t--;
						return c/2 * ( -Math.pow( 2, -10 * t) + 2 ) + b;
						break;
					case "easeInOut": case "mcsEaseInOut":
						t/=d/2;
						if(t<1) return c/2*t*t*t + b;
						t-=2;
						return c/2*(t*t*t + 2) + b;
						break;
					case "easeOutSmooth":
						t/=d; t--;
						return -c * (t*t*t*t - 1) + b;
						break;
					case "easeOutStrong":
						return c * ( -Math.pow( 2, -10 * t/d ) + 1 ) + b;
						break;
					case "easeOut": case "mcsEaseOut": default:
						var ts=(t/=d)*t,tc=ts*t;
						return b+c*(0.499999999999997*tc*ts + -2.5*ts*ts + 5.5*tc + -6.5*ts + 4*t);
				}
			}
		},
		/* -------------------- */
		
		
		/* returns current time */
		_getTime=function(){
			if(window.performance && window.performance.now){
				return window.performance.now();
			}else{
				if(window.performance && window.performance.webkitNow){
					return window.performance.webkitNow();
				}else{
					if(Date.now){return Date.now();}else{return new Date().getTime();}
				}
			}
		},
		/* -------------------- */
		
		
		/* stops a tween */
		_stopTween=function(){
			var el=this;
			if(!el._mTween){el._mTween={top:{},left:{}};}
			var props=["top","left"];
			for(var i=0; i<props.length; i++){
				var prop=props[i];
				if(el._mTween[prop].id){
					if(!window.requestAnimationFrame){clearTimeout(el._mTween[prop].id);
					}else{window.cancelAnimationFrame(el._mTween[prop].id);}
					el._mTween[prop].id=null;
					el._mTween[prop].stop=1;
				}
			}
		},
		/* -------------------- */
		
		
		/* deletes a property (avoiding the exception thrown by IE) */
		_delete=function(c,m){
			try{delete c[m];}catch(e){c[m]=null;}
		},
		/* -------------------- */
		
		
		/* detects left mouse button */
		_mouseBtnLeft=function(e){
			return !(e.which && e.which!==1);
		},
		/* -------------------- */
		
		
		/* detects if pointer type event is touch */
		_pointerTouch=function(e){
			var t=e.originalEvent.pointerType;
			return !(t && t!=="touch" && t!==2);
		},
		/* -------------------- */
		
		
		/* checks if value is numeric */
		_isNumeric=function(val){
			return !isNaN(parseFloat(val)) && isFinite(val);
		},
		/* -------------------- */
		
		
		/* returns element position according to content */
		_childPos=function(el){
			var p=el.parents(".mCSB_container");
			return [el.offset().top-p.offset().top,el.offset().left-p.offset().left];
		},
		/* -------------------- */
		
		
		/* checks if browser tab is hidden/inactive via Page Visibility API */
		_isTabHidden=function(){
			var prop=_getHiddenProp();
			if(!prop) return false;
			return document[prop];
			function _getHiddenProp(){
				var pfx=["webkit","moz","ms","o"];
				if("hidden" in document) return "hidden"; //natively supported
				for(var i=0; i<pfx.length; i++){ //prefixed
				    if((pfx[i]+"Hidden") in document) 
				        return pfx[i]+"Hidden";
				}
				return null; //not supported
			}
		};
		/* -------------------- */
		
	
	
	
	
	/* 
	----------------------------------------
	PLUGIN SETUP 
	----------------------------------------
	*/
	
	/* plugin constructor functions */
	$.fn[pluginNS]=function(method){ /* usage: $(selector).mCustomScrollbar(); */
		if(methods[method]){
			return methods[method].apply(this,Array.prototype.slice.call(arguments,1));
		}else if(typeof method==="object" || !method){
			return methods.init.apply(this,arguments);
		}else{
			$.error("Method "+method+" does not exist");
		}
	};
	$[pluginNS]=function(method){ /* usage: $.mCustomScrollbar(); */
		if(methods[method]){
			return methods[method].apply(this,Array.prototype.slice.call(arguments,1));
		}else if(typeof method==="object" || !method){
			return methods.init.apply(this,arguments);
		}else{
			$.error("Method "+method+" does not exist");
		}
	};
	
	/* 
	allow setting plugin default options. 
	usage: $.mCustomScrollbar.defaults.scrollInertia=500; 
	to apply any changed default options on default selectors (below), use inside document ready fn 
	e.g.: $(document).ready(function(){ $.mCustomScrollbar.defaults.scrollInertia=500; });
	*/
	$[pluginNS].defaults=defaults;
	
	/* 
	add window object (window.mCustomScrollbar) 
	usage: if(window.mCustomScrollbar){console.log("custom scrollbar plugin loaded");}
	*/
	window[pluginNS]=true;
	
	$(window).bind("load",function(){
		
		$(defaultSelector)[pluginNS](); /* add scrollbars automatically on default selector */
		
		/* extend jQuery expressions */
		$.extend($.expr[":"],{
			/* checks if element is within scrollable viewport */
			mcsInView:$.expr[":"].mcsInView || function(el){
				var $el=$(el),content=$el.parents(".mCSB_container"),wrapper,cPos;
				if(!content.length){return;}
				wrapper=content.parent();
				cPos=[content[0].offsetTop,content[0].offsetLeft];
				return 	cPos[0]+_childPos($el)[0]>=0 && cPos[0]+_childPos($el)[0]<wrapper.height()-$el.outerHeight(false) && 
						cPos[1]+_childPos($el)[1]>=0 && cPos[1]+_childPos($el)[1]<wrapper.width()-$el.outerWidth(false);
			},
			/* checks if element or part of element is in view of scrollable viewport */
			mcsInSight:$.expr[":"].mcsInSight || function(el,i,m){
				var $el=$(el),elD,content=$el.parents(".mCSB_container"),wrapperView,pos,wrapperViewPct,
					pctVals=m[3]==="exact" ? [[1,0],[1,0]] : [[0.9,0.1],[0.6,0.4]];
				if(!content.length){return;}
				elD=[$el.outerHeight(false),$el.outerWidth(false)];
				pos=[content[0].offsetTop+_childPos($el)[0],content[0].offsetLeft+_childPos($el)[1]];
				wrapperView=[content.parent()[0].offsetHeight,content.parent()[0].offsetWidth];
				wrapperViewPct=[elD[0]<wrapperView[0] ? pctVals[0] : pctVals[1],elD[1]<wrapperView[1] ? pctVals[0] : pctVals[1]];
				return 	pos[0]-(wrapperView[0]*wrapperViewPct[0][0])<0 && pos[0]+elD[0]-(wrapperView[0]*wrapperViewPct[0][1])>=0 && 
						pos[1]-(wrapperView[1]*wrapperViewPct[1][0])<0 && pos[1]+elD[1]-(wrapperView[1]*wrapperViewPct[1][1])>=0;
			},
			/* checks if element is overflowed having visible scrollbar(s) */
			mcsOverflow:$.expr[":"].mcsOverflow || function(el){
				var d=$(el).data(pluginPfx);
				if(!d){return;}
				return d.overflowed[0] || d.overflowed[1];
			}
		});
	
	});

}))}));/**
 * Create YouTube players from <div> placeholders with IFrame API.
 * Each <div> must have id, width, height and data-video-id attributes to init the player.
 * Start playing the video when iframe is scrolled into view.
 * Pause playing when it is scrolled out of view.
 *
 * Depends on https://github.com/protonet/jquery.inview

 AUTOPLAY INVIEW
 */
(function ($) {
    $.fn.inViewAutoplay = function (options) {
        return this.filter('[id][width][height][data-video-id]').each(function (index,value) {
            new YT.Player(this.getAttribute('id'), {
                width: this.getAttribute('width'),
                height: this.getAttribute('height'),
                videoId: this.getAttribute('data-video-id'),
                playerVars: options,
                events: {
                    'onReady': onPlayerReady
                }
            });

            function onPlayerReady(event) {
                var player = event.target;

                $(player.getIframe()).bind('inview', {player: player}, onPlayerInView);

                if (options.quality) {
                    player.setPlaybackQuality(options.quality);
                }
            }

            function onPlayerInView(event, visible) {
                if (visible == true && index == 0 && this.getAttribute('data-mobile') == 0) {  // воспроизводим только 1-ое видео
                    event.data.player.playVideo();
                } else {
                    event.data.player.pauseVideo(); // BY RUSYA   pause
                }
            }
        });
    };

})(window.jQuery || window.Zepto);



/**
 * author Christopher Blum
 *    - based on the idea of Remy Sharp, http://remysharp.com/2009/01/26/element-in-view-event-plugin/
 *    - forked from http://github.com/zuk/jquery.inview/
 */
(function (factory) {
  if (typeof define == 'function' && define.amd) {
    // AMD
    define(['jquery'], factory);
  } else if (typeof exports === 'object') {
    // Node, CommonJS
    module.exports = factory(require('jquery'));
  } else {
      // Browser globals
    factory(jQuery);
  }
}(function ($) {

  var inviewObjects = [], viewportSize, viewportOffset,
      d = document, w = window, documentElement = d.documentElement, timer;

  $.event.special.inview = {
    add: function(data) {
      inviewObjects.push({ data: data, $element: $(this), element: this });
      // Use setInterval in order to also make sure this captures elements within
      // "overflow:scroll" elements or elements that appeared in the dom tree due to
      // dom manipulation and reflow
      // old: $(window).scroll(checkInView);
      //
      // By the way, iOS (iPad, iPhone, ...) seems to not execute, or at least delays
      // intervals while the user scrolls. Therefore the inview event might fire a bit late there
      //
      // Don't waste cycles with an interval until we get at least one element that
      // has bound to the inview event.
      if (!timer && inviewObjects.length) {
         timer = setInterval(checkInView, 250);
      }
    },

    remove: function(data) {
      for (var i=0; i<inviewObjects.length; i++) {
        var inviewObject = inviewObjects[i];
        if (inviewObject.element === this && inviewObject.data.guid === data.guid) {
          inviewObjects.splice(i, 1);
          break;
        }
      }

      // Clear interval when we no longer have any elements listening
      if (!inviewObjects.length) {
         clearInterval(timer);
         timer = null;
      }
    }
  };

  function getViewportSize() {
    var mode, domObject, size = { height: w.innerHeight, width: w.innerWidth };

    // if this is correct then return it. iPad has compat Mode, so will
    // go into check clientHeight/clientWidth (which has the wrong value).
    if (!size.height) {
      mode = d.compatMode;
      if (mode || !$.support.boxModel) { // IE, Gecko
        domObject = mode === 'CSS1Compat' ?
          documentElement : // Standards
          d.body; // Quirks
        size = {
          height: domObject.clientHeight,
          width:  domObject.clientWidth
        };
      }
    }

    return size;
  }

  function getViewportOffset() {
    return {
      top:  w.pageYOffset || documentElement.scrollTop   || d.body.scrollTop,
      left: w.pageXOffset || documentElement.scrollLeft  || d.body.scrollLeft
    };
  }

  function checkInView() {
    if (!inviewObjects.length) {
      return;
    }

    var i = 0, $elements = $.map(inviewObjects, function(inviewObject) {
      var selector  = inviewObject.data.selector,
          $element  = inviewObject.$element;
      return selector ? $element.find(selector) : $element;
    });

    viewportSize   = viewportSize   || getViewportSize();
    viewportOffset = viewportOffset || getViewportOffset();

    for (; i<inviewObjects.length; i++) {
      // Ignore elements that are not in the DOM tree
      if (!$.contains(documentElement, $elements[i][0])) {
        continue;
      }

      var $element      = $($elements[i]),
          elementSize   = { height: $element[0].offsetHeight, width: $element[0].offsetWidth },
          elementOffset = $element.offset(),
          inView        = $element.data('inview');

      // Don't ask me why because I haven't figured out yet:
      // viewportOffset and viewportSize are sometimes suddenly null in Firefox 5.
      // Even though it sounds weird:
      // It seems that the execution of this function is interferred by the onresize/onscroll event
      // where viewportOffset and viewportSize are unset
      if (!viewportOffset || !viewportSize) {
        return;
      }

      if (elementOffset.top + elementSize.height > viewportOffset.top &&
          elementOffset.top < viewportOffset.top + viewportSize.height &&
          elementOffset.left + elementSize.width > viewportOffset.left &&
          elementOffset.left < viewportOffset.left + viewportSize.width) {
        if (!inView) {
          $element.data('inview', true).trigger('inview', [true]);
        }
      } else if (inView) {
        $element.data('inview', false).trigger('inview', [false]);
      }
    }
  }

  $(w).on("scroll resize scrollstop", function() {
    viewportSize = viewportOffset = null;
  });

  // IE < 9 scrolls to focused elements without firing the "scroll" event
  if (!documentElement.addEventListener && documentElement.attachEvent) {
    documentElement.attachEvent("onfocusin", function() {
      viewportOffset = null;
    });
  }
}));


/*
 * jQuery Tooltip plugin 1.3
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 * http://docs.jquery.com/Plugins/Tooltip
 *
 * Copyright (c) 2006 - 2008 Jörn Zaefferer
 *
 * $Id: jquery.tooltip.js 5741 2008-06-21 15:22:16Z joern.zaefferer $
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

(function($) {

		// the tooltip element
	var helper = {},
		// the current tooltipped element
		current,
		// the title of the current element, used for restoring
		title,
		// timeout id for delayed tooltips
		tID,
		// IE 5.5 or 6
		IE = $.browser.msie && /MSIE\s(5\.5|6\.)/.test(navigator.userAgent),
		// flag for mouse tracking
		track = false;

	$.tooltip = {
		blocked: false,
		defaults: {
			delay: 200,
			fade: false,
			showURL: true,
			extraClass: "",
			top: 15,
			left: 15,
			id: "tooltip"
		},
		block: function() {
			$.tooltip.blocked = !$.tooltip.blocked;
		}
	};

	$.fn.extend({
		tooltip: function(settings) {
			settings = $.extend({}, $.tooltip.defaults, settings);
			createHelper(settings);
			return this.each(function() {
					$.data(this, "tooltip", settings);
					this.tOpacity = 1; //helper.parent.css("opacity");     BY RUSYA
					// copy tooltip into its own expando and remove the title
					this.tooltipText = this.title;
					//alert($(this).selector);
					//$(this).removeAttr("title");
					this.title = "";
					// also remove alt attribute to prevent default tooltip in IE
					this.alt = "";
				})
				.mouseover(save)
				.mouseout(hide)
				.click(hide);
		},
		fixPNG: IE ? function() {
			return this.each(function () {
				var image = $(this).css('backgroundImage');
				/*
				if (image.match(/^url\(["']?(.*\.png)["']?\)$/i)) {
					image = RegExp.$1;
					$(this).css({
						'backgroundImage': 'none',
						'filter': "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='" + image + "')"
					}).each(function () {
						var position = $(this).css('position');
						if (position != 'absolute' && position != 'relative')
							$(this).css('position', 'relative');
					});
				} */
			});
		} : function() { return this; },
		unfixPNG: IE ? function() {
			return this.each(function () {
				$(this).css({'filter': '', backgroundImage: ''});
			});
		} : function() { return this; },
		hideWhenEmpty: function() {
			return this.each(function() {
				$(this)[ $(this).html() ? "show" : "hide" ]();
			});
		},
		url: function() {
			return this.attr('href') || this.attr('src');
		}
	});

	function createHelper(settings) {
		// there can be only one tooltip helper
		if( helper.parent )
			return;
		// create the helper, h3 for title, div for url
		helper.parent = $('<div id="' + settings.id + '"><h3></h3><div class="body"></div></div>')
			// add to document
			.appendTo(document.body)
			// hide it at first
			.hide();

		// apply bgiframe if available
		if ( $.fn.bgiframe )
			helper.parent.bgiframe();

		// save references to title and url elements
		helper.title = $('h3', helper.parent);
		helper.body = $('div.body', helper.parent);
		helper.url = $('div.url', helper.parent);
	}

	function settings(element) {
		return $.data(element, "tooltip");
	}

	// main event handler to start showing tooltips
	function handle(event) {
		// show helper, either with timeout or on instant
		if( settings(this).delay )
			tID = setTimeout(show, settings(this).delay);
		else
			show();

		// if selected, update the helper position when the mouse moves
		track = !!settings(this).track;
		$(document.body).bind('mousemove', update);

		// update at least once
		update(event);
	}

	// save elements title before the tooltip is displayed
	function save() {
		// if this is the current source, or it has no title (occurs with click event), stop
		if ( $.tooltip.blocked || this == current || (!this.tooltipText && !settings(this).bodyHandler) )
			return;

		// save current
		current = this;
		title = this.tooltipText;

		if ( settings(this).bodyHandler ) {
			helper.title.hide();
			var bodyContent = settings(this).bodyHandler.call(this);
			if (bodyContent.nodeType || bodyContent.jquery) {
				helper.body.empty().append(bodyContent)
			} else {
				helper.body.html( bodyContent );
			}
			helper.body.show();
		} else if ( settings(this).showBody ) {
			var parts = title.split(settings(this).showBody);
			helper.title.html(parts.shift()).show();
			helper.body.empty();
			for(var i = 0, part; (part = parts[i]); i++) {
				if(i > 0)
					helper.body.append("<br/>");
				helper.body.append(part);
			}
			helper.body.hideWhenEmpty();
		} else {
			helper.title.html(title).show();
			helper.body.hide();
		}

		// if element has href or src, add and show it, otherwise hide it
		if( settings(this).showURL && $(this).url() )
			helper.url.html( $(this).url().replace('http://', '') ).show();
		else
			helper.url.hide();

		// add an optional class for this tip
		helper.parent.addClass(settings(this).extraClass);

		// fix PNG background for IE
		if (settings(this).fixPNG )
			helper.parent.fixPNG();

		handle.apply(this, arguments);
	}

	// delete timeout and show helper
	function show() {
		tID = null;
		if ((!IE || !$.fn.bgiframe) && settings(current).fade) {
			if (helper.parent.is(":animated"))
				helper.parent.stop().show().fadeTo(settings(current).fade, current.tOpacity);
			else
				helper.parent.is(':visible') ? helper.parent.fadeTo(settings(current).fade, current.tOpacity) : helper.parent.fadeIn(settings(current).fade);
		} else {
			helper.parent.show();
		}
		update();
	}

	/**
	 * callback for mousemove
	 * updates the helper position
	 * removes itself when no current element
	 */
	function update(event)	{
		//alert();
		//console.log($(this).attr('class'));
		if($.tooltip.blocked)
			return;

		if (event && event.target.tagName == "OPTION") {
			return;
		}

		// stop updating when tracking is disabled and the tooltip is visible
		if ( !track && helper.parent.is(":visible")) {
			$(document.body).unbind('mousemove', update)
		}

		// if no current element is available, remove this listener
		if( current == null ) {
			$(document.body).unbind('mousemove', update);
			return;
		}

		// remove position helper classes
		helper.parent.removeClass("viewport-right").removeClass("viewport-bottom");



		var left = helper.parent[0].offsetLeft;
		var top = helper.parent[0].offsetTop;
		if (event) {
			// position the helper 15 pixel to bottom right, starting from mouse position
			//left = event.pageX + settings(current).left;
			//top = event.pageY + settings(current).top;
                   // object                  // settings				// object to center                       // tooltip
			left = $(current).offset().left + settings(current).left + Math.round($(current).outerWidth()/2) - Math.round(helper.parent.outerWidth()/2);
			top = $(current).offset().top + settings(current).top;

			var right='auto';
			if (settings(current).positionLeft) {
				right = $(window).width() - left;
				left = 'auto';
			}

			//// first position
			helper.parent.css({
				left: left,
				right: right,
				top: top
			});
		}

		var v = viewport(),
			h = helper.parent[0];
		// check horizontal position
		if (v.x + v.cx < h.offsetLeft + h.offsetWidth) {
			left -= h.offsetWidth + 20 + settings(current).left;
			helper.parent.css({left: left + 'px'}).addClass("viewport-right");
		}
		// check vertical position
		if (v.y + v.cy < h.offsetTop + h.offsetHeight) {
			top -= h.offsetHeight + 20 + settings(current).top;
			helper.parent.css({top: top + 'px'}).addClass("viewport-bottom");
		}
	}

	function viewport() {
		return {
			x: $(window).scrollLeft(),
			y: $(window).scrollTop(),
			cx: $(window).width(),
			cy: $(window).height()
		};
	}

	// hide helper and restore added classes and the title
	function hide(event) {
		if($.tooltip.blocked)
			return;
		// clear timeout if possible
		if(tID)
			clearTimeout(tID);
		// no more current element
		current = null;

		var tsettings = settings(this);
		function complete() {
			helper.parent.removeClass( tsettings.extraClass ).hide().css("opacity", "");
		}
		if ((!IE || !$.fn.bgiframe) && tsettings.fade) {
			if (helper.parent.is(':animated'))
				helper.parent.stop().fadeTo(tsettings.fade, 0, complete);
			else
				helper.parent.stop().fadeOut(tsettings.fade, complete);
		} else
			complete();

		if( settings(this).fixPNG )
			helper.parent.unfixPNG();
	}

})(jQuery);
/*
* @version		$Id: extravote.js 2008 vargas $ @package Joomla
*/
function JVXVote(id,i,total,total_count,xid,counter){
	var currentURL = window.location;
	var live_site = currentURL.protocol+'//'+currentURL.host; //+sfolder
	var lsXmlHttp = '';

	var div = document.getElementById('extravote_'+id+'_'+xid);
	if (div.className != 'extravote-count voted') {
		div.innerHTML='<img src="'+live_site+'/plugins/content/extravote/loading.gif" border="0" align="absmiddle" /> '+'<small>'+extravote_text[1]+'</small>';
		try	{
			lsXmlHttp=new XMLHttpRequest();
		} catch (e) {
			try	{ lsXmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try { lsXmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					alert(extravote_text[0]);
					return false;
				}
			}
		}
	}
	div.className = 'extravote-count voted';
	if ( lsXmlHttp != '' ) {
		lsXmlHttp.onreadystatechange=function() {
			var response;
			if(lsXmlHttp.readyState==4){
				setTimeout(function(){
					response = lsXmlHttp.responseText;
					if(response=='thanks') div.innerHTML='<small>'+extravote_text[2]+'</small>';
					if(response=='login') div.innerHTML='<small>'+extravote_text[3]+'</small>';
					if(response=='voted') div.innerHTML='<small>'+extravote_text[4]+'</small>';
				},500);
				setTimeout(function(){
					if(response=='thanks'){
						var newtotal = total_count+1;
						var percentage = ((total + i)/(newtotal));
						document.getElementById('rating_'+id+'_'+xid).style.width=parseInt(percentage*20)+'%';
					}
					if(counter!=0){
						if(response=='thanks'){
							if(newtotal!=1)
								var newvotes=extravote_text[5].replace('%s', newtotal );
							else
								var newvotes=extravote_text[6].replace('%s', newtotal );
							div.innerHTML='<small>( '+newvotes+' )</small>';
						} else {
							if(total_count!=0 || counter!=-1) {
								if(total_count!=1)
									var votes=extravote_text[5].replace('%s', total_count );
								else
									var votes=extravote_text[6].replace('%s', total_count );
								div.innerHTML='<small>( '+votes+' )</small>';
							} else {
								div.innerHTML='';
							}
						}
					} else {
						div.innerHTML='';
					}
				},2000);
			}
		}
		lsXmlHttp.open("GET",live_site+"/plugins/content/extravote/ajax.php?task=vote&user_rating="+i+"&cid="+id+"&xid="+xid,true);
		lsXmlHttp.send(null);
	}
}